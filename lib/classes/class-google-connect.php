<?php
/**
 * Google Connect
 *
 * Hosted OAuth with a browser return or pairing code, rendered in WordPress without an iframe.
 * Provisions storage in the administrator's Google account. Site and network setup keep
 * separate credentials, pending sign-in state, settings and capability checks.
 */

namespace wpCloud\StatelessMedia;

use Exception;
use WP_Error;

if (!class_exists('wpCloud\StatelessMedia\GoogleConnect')) {

  class GoogleConnect {
    use Singleton;

    const PAGE            = 'stateless-connect';
    const OPTION          = 'sm_google_connect';
    const CLIENT_OPTION   = 'sm_google_connect_client';
    const LOOPBACK_URI    = 'http://127.0.0.1:19190/';
    const PROOFS_OPTION   = 'sm_gc_proofs';
    const LAST_OPTION     = 'sm_gc_last';
    const PENDING_KEYS_OPTION = 'sm_gc_pending_key_revocations';
    const BROKER          = 'https://stateless.ci';
    const SCOPES          = 'openid email https://www.googleapis.com/auth/cloud-platform';
    const AUTH_ENDPOINT   = 'https://accounts.google.com/o/oauth2/v2/auth';
    const TOKEN_ENDPOINT  = 'https://oauth2.googleapis.com/token';
    const REVOKE_ENDPOINT = 'https://oauth2.googleapis.com/revoke';
    const NONCE           = 'sm_google_connect';

    /** @var string|null */
    private $page_hook = null;
    private $network_hook = null;

    /** @var string Why the hosted sign-in could not be prepared, for the page to show. */
    public $start_error = '';

    protected function __construct() {
      add_action('rest_api_init', array($this, 'rest_routes'));
      if (!is_admin()) return;
      add_action('admin_init', array($this, 'redirect_legacy_setup'));
      add_action('admin_menu', array($this, 'admin_menu'), 20);
      add_action('network_admin_menu', array($this, 'network_admin_menu'), 20);
      add_action('admin_enqueue_scripts', array($this, 'enqueue'));
      add_action('admin_post_sm_gc_client', array($this, 'handle_client'));
      add_action('admin_post_sm_gc_finish', array($this, 'handle_finish'));
      add_action('admin_post_sm_gc_provision', array($this, 'handle_provision'));
      add_action('admin_post_sm_gc_disconnect', array($this, 'handle_disconnect'));
      add_action('admin_post_sm_gc_restart', array($this, 'handle_restart'));
      add_action('wp_ajax_sm_gc_buckets', array($this, 'ajax_buckets'));
      add_action('wp_ajax_sm_gc_project_billing', array($this, 'ajax_project_billing'));
    }

    /* ------------------------------------------------------------------ */
    /* Admin UI                                                             */
    /* ------------------------------------------------------------------ */

    public function redirect_legacy_setup() {
      if (isset($_GET['page']) && $_GET['page'] === 'stateless-setup' && current_user_can($this->capability())) {
        wp_safe_redirect($this->page_url());
        exit;
      }
    }

    /**
     * The network "Setup Assistant: Hidden" setting (WP_STATELESS_MEDIA_HIDE_SETUP_ASSISTANT) hid the
     * former setup wizard from site admins; keep honoring it for Connect so an already configured
     * site sees nothing new. The page itself stays registered: a network sign-in returns to this
     * site's upload.php and must be able to finish, so only the menu entry and site-scope views go.
     */
    public static function hidden_for_site() {
      return ud_get_stateless_media()->get('sm.hide_setup_assistant') === 'true';
    }

    public function admin_menu() {
      $this->legacy_menu_alias('upload.php');
      $this->page_hook = add_media_page(
        __('Stateless Connect', ud_get_stateless_media()->domain),
        __('Stateless Connect', ud_get_stateless_media()->domain),
        $this->capability(),
        self::PAGE,
        array($this, 'render_page')
      );
      // Runs before any output, so a return from the broker can finish and redirect.
      add_action('load-' . $this->page_hook, array($this, 'maybe_finish_from_return'));
      if (self::hidden_for_site()) remove_submenu_page('upload.php', self::PAGE);
    }

    private function legacy_menu_alias($parent) {
      $hook = add_submenu_page($parent, 'Stateless Setup', 'Stateless Setup', $this->capability(), 'stateless-setup', array($this, 'render_page'));
      remove_submenu_page($parent, 'stateless-setup');
      add_action('load-' . $hook, array($this, 'redirect_legacy_setup'));
    }

    public function network_scope() {
      return is_multisite() && (is_network_admin() || (isset($_REQUEST['sm_gc_network']) && $_REQUEST['sm_gc_network'] === '1'));
    }

    public function capability() {
      return $this->network_scope() ? 'manage_network_options' : 'manage_options';
    }

    public function nonce_action() {
      return self::NONCE . ($this->network_scope() ? '_network_' . get_current_network_id() : '');
    }

    private function read_option($key, $default = false) {
      return $this->network_scope() ? get_network_option(get_current_network_id(), $key, $default) : get_option($key, $default);
    }

    private function write_option($key, $value, $autoload = false) {
      return $this->network_scope() ? update_network_option(get_current_network_id(), $key, $value) : update_option($key, $value, $autoload);
    }

    private function remove_option($key) {
      return $this->network_scope() ? delete_network_option(get_current_network_id(), $key) : delete_option($key);
    }

    private function state_key($key) {
      if (!is_multisite()) return 'sm_gc_' . $key;
      return 'sm_gc_' . ($this->network_scope() ? 'network_' . get_current_network_id() : 'site_' . get_current_blog_id()) . '_' . $key;
    }

    public function network_admin_menu() {
      $this->legacy_menu_alias('settings.php');
      $this->network_hook = add_submenu_page('settings.php', __('Stateless Connect', ud_get_stateless_media()->domain), __('Stateless Connect', ud_get_stateless_media()->domain), 'manage_network_options', self::PAGE, array($this, 'render_page'));
      add_action('load-' . $this->network_hook, array($this, 'maybe_finish_from_return'));
    }

    /** Assets carry a content hash so an update is never served from a stale browser cache. */
    public function enqueue($hook) {
      if ($hook !== $this->page_hook && $hook !== $this->network_hook) return;
      $plugin = ud_get_stateless_media();
      $css = $plugin->path('static/styles/stateless-connect.css', 'dir');
      $js  = $plugin->path('static/scripts/stateless-connect.js', 'dir');
      wp_enqueue_style('stateless-connect', $plugin->path('static/styles/stateless-connect.css', 'url'), array(), self::asset_version($css));
      wp_enqueue_script('stateless-connect', $plugin->path('static/scripts/stateless-connect.js', 'url'), array(), self::asset_version($js), true);
      wp_add_inline_script('stateless-connect', 'window.smConnectPicker = ' . wp_json_encode(array(
        'url'   => add_query_arg('sm_gc_network', $this->network_scope() ? '1' : '0', admin_url('admin-ajax.php')),
        'nonce' => wp_create_nonce($this->nonce_action()),
        'draft' => $this->state_get('draft') ?: null,
      )) . ';', 'before');
    }

    private static function asset_version($file) {
      return Bootstrap::$version . '-' . (is_readable($file) ? substr(hash_file('sha256', $file), 0, 12) : '0');
    }

    public function page_url($args = array()) {
      if ($this->network_scope()) $args['sm_gc_network'] = '1';
      return add_query_arg(array_merge(array('page' => self::PAGE), $args), $this->network_scope() ? network_admin_url('settings.php') : admin_url('upload.php'));
    }

    public function render_page() {
      if (!current_user_can($this->capability())) wp_die(__('Insufficient permissions.'));
      if (self::hidden_for_site() && !$this->network_scope()) {
        wp_die(__('The network administrator has hidden the setup assistant for this site; media storage is managed from the network.', ud_get_stateless_media()->domain), 403);
      }

      $client     = $this->get_client();
      $connection = $this->get_connection();
      $uid        = get_current_user_id();
      $log        = $this->state_get('log', $uid);
      $error      = $this->state_get('error', $uid);
      $this->state_delete('log', $uid);
      $this->state_delete('error', $uid);

      $auth_url  = '';
      $projects  = array();
      $billing   = array();
      $proj_err  = null;
      $billing_err = '';

      if ($client && !$connection) {
        $auth_url = $this->build_auth_url($client, $uid);
      }

      if ($client && $connection) {
        $page_token = '';
        $pages      = 0;
        do {
          $url = 'https://cloudresourcemanager.googleapis.com/v1/projects?filter=lifecycleState%3AACTIVE&pageSize=200';
          if ($page_token !== '') $url .= '&pageToken=' . rawurlencode($page_token);
          $res = $this->api('GET', $url);
          if (is_wp_error($res)) {
            $proj_err = $res->get_error_message();
            break;
          }
          $projects = array_merge($projects, isset($res['projects']) ? $res['projects'] : array());
          $page_token = isset($res['nextPageToken']) ? $res['nextPageToken'] : '';
        } while ($page_token !== '' && ++$pages < 10);
        usort($projects, function ($a, $b) {
          return strcasecmp(isset($a['name']) ? $a['name'] : $a['projectId'], isset($b['name']) ? $b['name'] : $b['projectId']);
        });
        $billing_page = ''; $billing_seen = array(); $billing_err = '';
        do {
          $res = $this->api('GET', 'https://cloudbilling.googleapis.com/v1/billingAccounts?pageSize=100' . ($billing_page !== '' ? '&pageToken=' . rawurlencode($billing_page) : ''));
          if (is_wp_error($res)) { $billing_err = $res->get_error_message(); break; }
          foreach (isset($res['billingAccounts']) ? $res['billingAccounts'] : array() as $b) if (!empty($b['open'])) $billing[] = $b;
          $billing_seen[] = $billing_page;
          $billing_page = isset($res['nextPageToken']) ? $res['nextPageToken'] : '';
        } while ($billing_page !== '' && !in_array($billing_page, $billing_seen, true) && count($billing_seen) < 20);

      }

      $host = parse_url(home_url(), PHP_URL_HOST);
      $suggested_bucket = 'wp-stateless-' . preg_replace('/[^a-z0-9-]/', '-', strtolower($host)) . '-' . substr(md5(wp_salt() . $host), 0, 4);
      $suggested_bucket = substr(trim($suggested_bucket, '-'), 0, 63);

      // What this site used last time, so a different Google account starts from the same project and
      // bucket and only has to change what it wants to change.
      $last = $this->read_option(self::LAST_OPTION, array());
      if (!is_array($last)) $last = array();
      $default_project = !empty($connection['project']) ? $connection['project'] : (!empty($last['project']) ? $last['project'] : '');
      $default_bucket  = !empty($connection['bucket']) ? $connection['bucket'] : (!empty($last['bucket']) ? $last['bucket'] : '');

      $requires_stateless = $this->requires_stateless_mode();
      $draft = $this->state_get('draft');
      $settings = (object) ud_get_stateless_media()->get('sm');
      if ($this->network_scope()) {
        foreach (array('bucket', 'key_json', 'mode') as $key) $settings->$key = $this->read_option('sm_' . $key, $key === 'mode' ? 'disabled' : '');
      }

      include ud_get_stateless_media()->path('static/views/connect.php', 'dir');
    }

    /** Read-only discovery: credentials stay on the server. Each request reads one page. */
    public function ajax_buckets() {
      if (!current_user_can($this->capability())) wp_send_json_error(array('message' => 'Insufficient permissions.'), 403);
      check_ajax_referer($this->nonce_action(), 'nonce');
      if (!$this->get_connection()) wp_send_json_error(array('message' => 'Sign in to Google first.'), 401);
      $project = isset($_GET['project']) && is_string($_GET['project']) ? sanitize_text_field(wp_unslash($_GET['project'])) : '';
      $page = isset($_GET['page_token']) && is_string($_GET['page_token']) ? wp_unslash($_GET['page_token']) : '';
      if (!preg_match('/^[a-z][a-z0-9-]{4,29}$/', $project) || strlen($page) > 4096) wp_send_json_error(array('message' => 'Invalid project or page.'), 400);
      $url = 'https://storage.googleapis.com/storage/v1/b?project=' . rawurlencode($project) . '&maxResults=200';
      if ($page !== '') $url .= '&pageToken=' . rawurlencode($page);
      $res = $this->api('GET', $url);
      if (is_wp_error($res)) wp_send_json_error(array('message' => wp_strip_all_tags($res->get_error_message())), 400);
      $buckets = array();
      foreach (isset($res['items']) ? $res['items'] : array() as $b) {
        $buckets[] = array('name' => $b['name'], 'location' => isset($b['location']) ? $b['location'] : '', 'locationType' => isset($b['locationType']) ? $b['locationType'] : '', 'storageClass' => isset($b['storageClass']) ? $b['storageClass'] : '');
      }
      wp_send_json_success(array('buckets' => $buckets, 'next' => isset($res['nextPageToken']) ? $res['nextPageToken'] : ''));
    }

    public function ajax_project_billing() {
      if (!current_user_can($this->capability())) wp_send_json_error(array('message' => 'Insufficient permissions.'), 403);
      check_ajax_referer($this->nonce_action(), 'nonce');
      if (!$this->get_connection()) wp_send_json_error(array('message' => 'Sign in to Google first.'), 401);
      $project = isset($_GET['project']) && is_string($_GET['project']) ? sanitize_text_field(wp_unslash($_GET['project'])) : '';
      if (!preg_match('/^[a-z][a-z0-9-]{4,29}$/', $project)) wp_send_json_error(array('message' => 'Invalid project.'), 400);
      $res = $this->api('GET', 'https://cloudbilling.googleapis.com/v1/projects/' . rawurlencode($project) . '/billingInfo');
      if (is_wp_error($res)) wp_send_json_error(array('message' => 'Billing status could not be checked. Verify billing in Google Cloud before creating storage.'), 400);
      wp_send_json_success(array('enabled' => !empty($res['billingEnabled']), 'account' => isset($res['billingAccountName']) ? $res['billingAccountName'] : ''));
    }

    public function requires_stateless_mode() {
      $uploads = wp_upload_dir();
      return (bool) apply_filters('wp_stateless_is_app_engine', false) || !is_writable($uploads['basedir']);
    }

    /**
     * Settings pinned by a constant, a key file, the environment or network settings win over anything
     * setup saves, so provisioning would report success while uploads kept the old configuration.
     * Returns an explanation when that is the case, or an empty string when setup may proceed.
     */
    public function overridden_settings() {
      $domain   = ud_get_stateless_media()->domain;
      $readonly = ud_get_stateless_media()->get('sm.readonly');
      if (!is_array($readonly)) return '';
      // Only the credentials decide whether setup can take effect. The storage mode is shown locked instead (see locked_mode()).
      $labels = array('bucket' => __('bucket', $domain), 'key_json' => __('service account key', $domain));
      foreach ($labels as $key => $label) {
        if (empty($readonly[$key])) continue;
        $source = $readonly[$key];
        if ($source === 'network' && $this->network_scope()) continue; // network setup writes network settings itself
        $next = $source === 'network'
          ? __('Run Stateless Connect from the Network Admin to change it for the whole network.', $domain)
          : __('Remove that override to let setup manage it, or finish the configuration there instead.', $domain);
        return sprintf(__('The %1$s is set by %2$s, so setup cannot change it. %3$s', $domain), $label, self::readonly_source_label($source), $next);
      }
      return '';
    }

    /**
     * The storage mode this site will actually run with when something other than its own setting decides it:
     * a constant, App Engine, or (for a site inside a network) the network. Settings::refresh() applies the
     * network's value whenever it is non-empty, which on a multisite includes the network default, so the
     * mode is presented as fixed rather than offered as a choice that would not take effect.
     * Returns array('mode' => ..., 'source' => label) or null when the site chooses freely.
     */
    public function locked_mode() {
      $readonly = ud_get_stateless_media()->get('sm.readonly');
      if (!is_array($readonly) || empty($readonly['mode'])) return null;
      if ($readonly['mode'] === 'network' && $this->network_scope()) return null;
      $mode = ud_get_stateless_media()->get('sm.mode');
      if (!in_array($mode, array('disabled', 'backup', 'cdn', 'ephemeral', 'stateless'), true)) return null;
      return array('mode' => $mode, 'source' => self::readonly_source_label($readonly['mode']));
    }

    /**
     * Why the storage step cannot run right now: the bucket or key is pinned elsewhere, or the storage
     * mode is pinned to Disabled, in which case provisioning would report readiness while uploads stay off.
     */
    public function storage_blocked() {
      $overridden = $this->overridden_settings();
      if ($overridden) return $overridden;
      $locked = $this->locked_mode();
      if ($locked && $locked['mode'] === 'disabled') {
        return sprintf(__('The storage mode is set to Disabled by %s, so WP-Stateless would not process uploads after setup. Change it there first, then come back to choose storage.', ud_get_stateless_media()->domain), $locked['source']);
      }
      return '';
    }

    private static function readonly_source_label($source) {
      $domain  = ud_get_stateless_media()->domain;
      $sources = array(
        'constant'    => __('a constant in wp-config.php', $domain),
        'environment' => __('the GOOGLE_APPLICATION_CREDENTIALS environment variable', $domain),
        'network'     => __('network settings', $domain),
        'app_engine'  => __('the App Engine environment', $domain),
      );
      return isset($sources[$source]) ? $sources[$source] : $source;
    }

    /** Preserve conditional bindings; grant this site a separate unconditional bucket role. */
    public static function bucket_bindings($bindings, $member) {
      foreach ($bindings as &$binding) {
        if ($binding['role'] === 'roles/storage.admin' && empty($binding['condition'])) {
          if (!in_array($member, $binding['members'], true)) $binding['members'][] = $member;
          return $bindings;
        }
      }
      unset($binding);
      $bindings[] = array('role' => 'roles/storage.admin', 'members' => array($member));
      return $bindings;
    }

    /** True when an unconditional binding lets allUsers read objects. */
    /** A default object ACL that grants allUsers read makes new objects public without per-object ACL writes. */
    public static function default_acl_is_public($items) {
      foreach ((array) $items as $item) {
        if (isset($item['entity'], $item['role']) && $item['entity'] === 'allUsers' && in_array($item['role'], array('READER', 'OWNER'), true)) return true;
      }
      return false;
    }

    public static function bucket_is_public($bindings) {
      $readers = array('roles/storage.objectViewer', 'roles/storage.legacyObjectReader', 'roles/storage.objectUser', 'roles/storage.objectAdmin', 'roles/storage.admin');
      foreach ((array) $bindings as $binding) {
        if (!empty($binding['condition']) || empty($binding['role']) || empty($binding['members'])) continue;
        if (in_array($binding['role'], $readers, true) && in_array('allUsers', (array) $binding['members'], true)) return true;
      }
      return false;
    }

    public static function bucket_name_error($bucket) {
      $limit = strpos($bucket, '.') !== false ? 222 : 63;
      if (strlen($bucket) < 3 || strlen($bucket) > $limit || !preg_match('/^[a-z0-9][a-z0-9._-]*[a-z0-9]$/', $bucket)) return 'Use 3 to 63 lowercase letters, numbers, dashes or underscores. Dotted names can be up to 222 characters.';
      foreach (explode('.', $bucket) as $part) if ($part === '' || strlen($part) > 63) return 'Each part of a dotted bucket name must contain 1 to 63 characters.';
      if (strpos($bucket, 'goog') === 0 || preg_match('/g[o0][o0]gle/', $bucket)) return 'Bucket names cannot start with goog or contain google or its close misspellings.';
      if (filter_var($bucket, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return 'A bucket name cannot be an IP address.';
      return '';
    }

    /* ------------------------------------------------------------------ */
    /* Client configuration (installed-app OAuth client, public by design)  */
    /* ------------------------------------------------------------------ */

    public function get_client() {
      if (defined('WP_STATELESS_GOOGLE_CLIENT_ID') && defined('WP_STATELESS_GOOGLE_CLIENT_SECRET')) {
        return array(
          'id'           => WP_STATELESS_GOOGLE_CLIENT_ID,
          'secret'       => WP_STATELESS_GOOGLE_CLIENT_SECRET,
          'redirect_uri' => defined('WP_STATELESS_GOOGLE_REDIRECT_URI') ? WP_STATELESS_GOOGLE_REDIRECT_URI : self::LOOPBACK_URI,
          'source'       => 'constant',
        );
      }
      $opt = $this->read_option(self::CLIENT_OPTION);
      if (!empty($opt['id']) && !empty($opt['secret'])) {
        return array(
          'id'           => $opt['id'],
          'secret'       => $opt['secret'],
          'redirect_uri' => self::LOOPBACK_URI,
          'source'       => 'option',
        );
      }
      return $this->broker_client();
    }

    /* ------------------------------------------------------------------ */
    /* Broker: stateless.ci holds the web client secret and the callback    */
    /* ------------------------------------------------------------------ */

    public static function broker_url() {
      return untrailingslashit(defined('WP_STATELESS_CONNECT_BROKER') ? WP_STATELESS_CONNECT_BROKER : self::BROKER);
    }

    private function broker_request($path, $body = null) {
      $args = array(
        'timeout'    => 25,
        'user-agent' => defined('WP_STATELESS_CONNECT_BROKER_UA') ? WP_STATELESS_CONNECT_BROKER_UA : 'WP-Stateless (' . home_url() . ')',
      );
      if ($body !== null) {
        $args['body'] = $body;
        $res = wp_remote_post(self::broker_url() . $path, $args);
      } else {
        $res = wp_remote_get(self::broker_url() . $path, $args);
      }
      if (is_wp_error($res)) return $res;
      $data = json_decode(wp_remote_retrieve_body($res), true);
      $code = (int) wp_remote_retrieve_response_code($res);
      if (!is_array($data)) {
        return new WP_Error('broker', sprintf(__('Unexpected reply from %1$s (HTTP %2$d).', ud_get_stateless_media()->domain), wp_parse_url(self::broker_url(), PHP_URL_HOST), $code));
      }
      $data['_status'] = $code;
      return $data;
    }

    private function broker_client() {
      $cached = get_transient('sm_gc_broker_client');
      if (is_array($cached) && !empty($cached['id']) && $cached['broker'] === self::broker_url()) return $cached;
      $data = $this->broker_request('/connect/google/client');
      if (is_wp_error($data) || empty($data['client_id']) || empty($data['redirect_uri'])) return null;
      $client = array(
        'id'           => $data['client_id'],
        'secret'       => '',
        'redirect_uri' => $data['redirect_uri'],
        'source'       => 'broker',
        'broker'       => self::broker_url(),
      );
      set_transient('sm_gc_broker_client', $client, HOUR_IN_SECONDS);
      return $client;
    }

    /**
     * True when Google will land the browser on a page that shows the code (stateless.ci/connect),
     * false when it lands on the loopback address and the admin copies the whole URL.
     */
    public static function is_hosted_redirect($uri) {
      return strpos($uri, 'http://127.0.0.1') !== 0 && strpos($uri, 'http://localhost') !== 0 && strpos($uri, 'http://[::1]') !== 0;
    }

    public function handle_client() {
      $this->guard();
      $id     = isset($_POST['client_id']) ? trim(sanitize_text_field($_POST['client_id'])) : '';
      $secret = isset($_POST['client_secret']) ? trim(sanitize_text_field($_POST['client_secret'])) : '';
      if (!$id || !$secret) {
        $this->fail(__('Both the client ID and client secret are required.', ud_get_stateless_media()->domain));
      }
      // Own clients are Desktop clients: Google sends the browser to the loopback address and the admin pastes it.
      $this->write_option(self::CLIENT_OPTION, array('id' => $id, 'secret' => $secret, 'redirect_uri' => self::LOOPBACK_URI), false);
      $this->done(array('sm_gc' => 'client-saved'));
    }

    /* ------------------------------------------------------------------ */
    /* OAuth: authorization URL, code exchange, refresh, revoke             */
    /* ------------------------------------------------------------------ */

    private function build_auth_url($client, $uid) {
      if ($client['source'] === 'broker') {
        return $this->broker_auth_url($uid);
      }
      $pkce = $this->state_get('pkce', $uid);
      if (empty($pkce['verifier']) || empty($pkce['state'])) {
        $pkce = array(
          'verifier' => self::b64url(random_bytes(48)),
          'state'    => self::b64url(random_bytes(18)),
        );
        $this->state_set('pkce', $pkce, 20 * MINUTE_IN_SECONDS, $uid);
      }
      $challenge = self::b64url(hash('sha256', $pkce['verifier'], true));

      return self::AUTH_ENDPOINT . '?' . http_build_query(array(
        'client_id'             => $client['id'],
        'redirect_uri'          => $client['redirect_uri'],
        'response_type'         => 'code',
        'scope'                 => self::SCOPES,
        'code_challenge'        => $challenge,
        'code_challenge_method' => 'S256',
        'state'                 => $pkce['state'],
        'access_type'           => 'offline',
        'prompt'                => 'consent',
      ), '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Opens a transaction with the broker. The broker owns the Google PKCE pair and the state; this site
     * keeps a verifier whose S256 goes along as the delivery binding, a verify token it serves back on a
     * public REST route so the broker can confirm the site opened the attempt, and a return token that
     * must come back in the return address before the finish step runs. One transaction per attempt,
     * reopened before the broker's record expires.
     */
    private function broker_auth_url($uid) {
      $att = $this->state_get('pkce', $uid);
      if (!empty($att['auth_url']) && !empty($att['site_verifier']) && !empty($att['rt']) && !empty($att['state']) && $att['registered'] > time() - 15 * MINUTE_IN_SECONDS) {
        // Still open at the broker? An attempt it has forgotten (expired, cancelled, failed) would send
        // the admin through Google only to be refused, so reopen instead.
        $probe = $this->broker_request('/connect/google/status?txn=' . rawurlencode($att['state']));
        if (!is_array($probe) || empty($probe['error']) || $probe['error'] !== 'unknown_transaction') {
          return $att['auth_url']; // open, unreachable, or a broker without the probe: keep the attempt
        }
        $this->state_delete('pkce', $uid);
      }
      $admin_parts = wp_parse_url(admin_url());
      $loopback = isset($admin_parts['scheme'], $admin_parts['host']) && in_array($admin_parts['scheme'], array('http', 'https'), true) && in_array(strtolower($admin_parts['host']), array('localhost', '127.0.0.1', '[::1]'), true);
      if (strpos(admin_url(), 'https://') !== 0 && !$loopback) {
        $this->start_error = __('The hosted sign-in requires HTTPS, except on localhost or a literal loopback address.', ud_get_stateless_media()->domain);
        return '';
      }
      $verifier = self::b64url(random_bytes(32));
      $rt       = self::b64url(random_bytes(18));
      $start = array(
        'return_url'     => add_query_arg(array('page' => self::PAGE, 'sm_gc_rt' => $rt, 'sm_gc_network' => $this->network_scope() ? '1' : '0'), admin_url('upload.php')),
        'site_challenge' => self::b64url(hash('sha256', $verifier, true)),
      );
      if ($loopback) {
        $start['loopback_return_b64'] = base64_encode($start['return_url']);
        unset($start['return_url']);
      }
      $r = $this->broker_request('/connect/google/start', $start);
      if (is_wp_error($r)) {
        $this->start_error = $r->get_error_message();
        return '';
      }
      if (empty($r['txn']) || empty($r['verify_token']) || empty($r['auth_url']) || strpos($r['auth_url'], self::AUTH_ENDPOINT . '?') !== 0) {
        $this->start_error = sprintf(__('%1$s did not open a sign-in (%2$s).', ud_get_stateless_media()->domain), wp_parse_url(self::broker_url(), PHP_URL_HOST), isset($r['error']) ? $r['error'] : 'HTTP ' . $r['_status']);
        return '';
      }
      $this->proof_add($r['txn'], $r['verify_token']);
      $this->state_set('pkce', array(
        'state'         => $r['txn'],
        'site_verifier' => $verifier,
        'rt'            => $rt,
        'auth_url'      => $r['auth_url'],
        'registered'    => time(),
      ), 20 * MINUTE_IN_SECONDS, $uid);
      return $r['auth_url'];
    }

    /* ------------------------------------------------------------------ */
    /* Proof of possession for the broker                                   */
    /* GET /wp-json/wp-stateless/v1/connect-proof?txn=... returns the verify  */
    /* token the broker handed out when this site opened the transaction.   */
    /* ------------------------------------------------------------------ */

    public function rest_routes() {
      register_rest_route('wp-stateless/v1', '/connect-proof', array(
        'methods'             => 'GET',
        'permission_callback' => '__return_true',
        'args'                => array('txn' => array('required' => true, 'type' => 'string')),
        'callback'            => array($this, 'rest_proof'),
      ));
    }

    public function rest_proof($request) {
      $txn = (string) $request['txn'];
      $all = $this->proofs();
      if (!preg_match('/^[A-Za-z0-9_-]{32}$/', $txn) || empty($all[$txn])) {
        return new WP_Error('unknown_transaction', 'Unknown transaction', array('status' => 404));
      }
      $res = rest_ensure_response(array('txn' => $txn, 'verify_token' => $all[$txn]['token']));
      $res->header('Cache-Control', 'no-store');
      return $res;
    }

    private function proofs() {
      $all = get_option(self::PROOFS_OPTION, array());
      if (!is_array($all)) $all = array();
      $live = array_filter($all, function ($p) { return is_array($p) && isset($p['exp'], $p['token']) && $p['exp'] > time(); });
      if (count($live) !== count($all)) update_option(self::PROOFS_OPTION, $live, false);
      return $live;
    }

    private function proof_add($txn, $token) {
      $all       = $this->proofs();
      $all[$txn] = array('token' => $token, 'exp' => time() + 20 * MINUTE_IN_SECONDS);
      update_option(self::PROOFS_OPTION, $all, false);
    }

    /** Drops the pending attempt so the next page view opens a fresh one. */
    public function handle_restart() {
      $this->guard();
      $this->state_delete('pkce');
      $this->state_delete('error');
      $this->done(array());
    }

    public function handle_finish() {
      $this->guard();
      $pasted = isset($_POST['redirect_url']) ? trim(wp_unslash($_POST['redirect_url'])) : '';
      $this->finish($pasted);
    }

    /**
     * The broker sent the browser back here with ?sm_gc_pair=XXXX-XXXX. Finish the attempt the current
     * admin started. The pair only redeems together with this attempt's state, so a stray or forged
     * link cannot complete anything but the admin's own pending sign-in.
     */
    public function maybe_finish_from_return() {
      if (empty($_GET['sm_gc_pair']) || empty($_GET['sm_gc_rt']) || !current_user_can($this->capability()) || $this->get_connection()) return;
      $pair = sanitize_text_field(wp_unslash($_GET['sm_gc_pair']));
      $rt   = (string) wp_unslash($_GET['sm_gc_rt']);
      $pkce = $this->state_get('pkce');
      if (empty($pkce['rt']) || !hash_equals($pkce['rt'], $rt)) return; // not this attempt's return; show the page as usual
      $this->finish($pair);
    }

    private function finish($pasted) {
      // A stale form or a second tab must not turn an established connection into an error.
      if ($this->get_connection()) {
        $this->state_delete('error');
        $this->done(array('sm_gc' => 'connected'));
      }
      $client = $this->get_client();
      if (!$client) $this->fail(__('OAuth client is not configured.', ud_get_stateless_media()->domain));

      $uid  = get_current_user_id();
      $pkce = $this->state_get('pkce', $uid);
      // A hosted attempt holds the site verifier; an own-client attempt holds the PKCE verifier.
      if (empty($pkce) || (empty($pkce['verifier']) && empty($pkce['site_verifier']))) {
        $this->fail(__('The sign-in attempt expired. Start again.', ud_get_stateless_media()->domain));
      }

      $params = $this->parse_redirect($pasted);
      if (!empty($params['sm_gc_pair']) && preg_match('/^[A-Za-z0-9]{4}-?[A-Za-z0-9]{4}$/', $params['sm_gc_pair'])) {
        $params['pair'] = strtoupper(str_replace('-', '', $params['sm_gc_pair']));
      }

      if ($client['source'] === 'broker') {
        if (empty($pkce['site_verifier'])) {
          $this->fail(__('The sign-in attempt expired. Start again.', ud_get_stateless_media()->domain));
        }
        if (empty($params['pair'])) {
          $this->fail(__('Type the eight character code shown after you confirmed the connection, or let that page bring you back here.', ud_get_stateless_media()->domain));
        }
        $r = $this->broker_request('/connect/google/redeem', array('pair' => $params['pair'], 'site_verifier' => $pkce['site_verifier']));
        if (is_wp_error($r)) $this->fail($r->get_error_message());
        if (empty($r['refresh_token']) || empty($r['access_token']) || empty($r['connection_id']) || empty($r['connection_secret'])) {
          if ($this->get_connection()) {
            $this->state_delete('error');
            $this->done(array('sm_gc' => 'connected'));
          }
          $this->fail(__('That code was not recognized, was already used, or has expired. Check the code and try again. If it still fails, click Sign in with Google to start again.', ud_get_stateless_media()->domain));
        }
        $this->store_connection($client, $r, isset($r['email']) ? $r['email'] : '', array('id' => $r['connection_id'], 'secret' => $r['connection_secret']), $uid);
      }

      if (!empty($params['pair'])) {
        $this->fail(__('Pairing codes come from the hosted sign-in page. With your own client, paste the full address Google sent you to instead.', ud_get_stateless_media()->domain));
      }
      if (!empty($params['error'])) {
        $this->fail(sprintf(__('Google returned an error: %s', ud_get_stateless_media()->domain), esc_html($params['error'])));
      }
      if (empty($params['code'])) {
        $this->fail(__('No authorization code found in what you pasted. Paste the code Google showed you, or the full address of the page it sent you to.', ud_get_stateless_media()->domain));
      }
      // A pasted bare code carries no state. The code is still bound to this attempt by PKCE.
      if (!empty($params['state']) && !hash_equals($pkce['state'], $params['state'])) {
        $this->fail(__('State mismatch. The pasted address does not belong to this sign-in attempt. Start again.', ud_get_stateless_media()->domain));
      }

      $token = $this->token_request(array(
        'grant_type'    => 'authorization_code',
        'code'          => $params['code'],
        'code_verifier' => $pkce['verifier'],
        'redirect_uri'  => $client['redirect_uri'],
      ), $client);
      if (is_wp_error($token)) $this->fail($token->get_error_message());
      if (empty($token['refresh_token'])) {
        $this->fail(__('Google did not return a refresh token. Remove WP-Stateless from your Google account permissions and try again.', ud_get_stateless_media()->domain));
      }
      $this->store_connection($client, $token, '', null, $uid);
    }

    private function store_connection($client, $token, $email, $broker_conn, $uid) {
      if ($email === '') {
        $info = wp_remote_get('https://openidconnect.googleapis.com/v1/userinfo', array(
          'headers' => array('Authorization' => 'Bearer ' . $token['access_token']),
          'timeout' => 20,
        ));
        if (!is_wp_error($info)) {
          $body  = json_decode(wp_remote_retrieve_body($info), true);
          $email = isset($body['email']) && is_string($body['email']) ? sanitize_email($body['email']) : '';
        }
      }
      $connection = array(
        'email'         => $email,
        'client_id'     => $client['id'],
        'source'        => $client['source'],
        'refresh_token' => $this->encrypt($token['refresh_token']),
        'access_token'  => $this->encrypt($token['access_token']),
        'expires'       => time() + intval($token['expires_in']) - 60,
        'scope'         => isset($token['scope']) ? $token['scope'] : '',
        'connected_at'  => time(),
        'connected_by'  => $uid,
      );
      if ($broker_conn) {
        $connection['broker']      = self::broker_url();
        $connection['broker_conn'] = $this->encrypt(wp_json_encode($broker_conn));
      }
      $this->write_option(self::OPTION, $connection, false);
      $this->state_delete('pkce', $uid);
      $this->state_delete('error', $uid);
      $this->done(array('sm_gc' => 'connected'));
    }

    private function broker_conn($connection) {
      if (empty($connection['broker_conn'])) return null;
      $c = json_decode($this->decrypt($connection['broker_conn']), true);
      return (is_array($c) && !empty($c['id']) && !empty($c['secret'])) ? $c : null;
    }

    /** True when a stored connection exists but its secrets no longer decrypt (salts changed, sodium gone). */
    public function connection_unreadable($connection = null) {
      $connection = $connection ? $connection : $this->get_connection();
      if (!$connection) return false;
      if ($this->decrypt($connection['refresh_token']) === '') return true;
      return !empty($connection['broker_conn']) && $this->broker_conn($connection) === null;
    }

    public function handle_disconnect() {
      $this->guard();
      $connection = $this->get_connection();
      $domain = ud_get_stateless_media()->domain;
      $unreadable = $connection && $this->connection_unreadable($connection);
      if ($unreadable && empty($_POST['sm_gc_force'])) {
        // Nothing can be revoked from here, so do not report that it was.
        $this->fail(__('The stored Google credentials cannot be read, usually because the WordPress authentication salts changed or the sodium extension is gone, so this site cannot revoke its own access. Restore the previous salts and disconnect again, or revoke access under Third-party apps in your Google Account and then remove the connection without revoking.', $domain));
      }
      if ($connection && !$unreadable) {
        $problems = array();
        $refresh  = $this->decrypt($connection['refresh_token']);
        if ($refresh) {
          // 200 means revoked. Google answers 400 invalid_token for a grant that is already gone, which is the same outcome.
          $res  = wp_remote_post(self::REVOKE_ENDPOINT, array('body' => array('token' => $refresh), 'timeout' => 20));
          $code = is_wp_error($res) ? 0 : (int) wp_remote_retrieve_response_code($res);
          $gone = $code === 400 && strpos((string) wp_remote_retrieve_body($res), 'invalid_token') !== false;
          if ($code !== 200 && !$gone) {
            $problems[] = sprintf(__('Google did not confirm the revocation (%s)', $domain), is_wp_error($res) ? $res->get_error_message() : 'HTTP ' . $code);
          }
        }
        $conn = $this->broker_conn($connection);
        if ($conn) {
          $r = $this->broker_request('/connect/google/disconnect', array('connection_id' => $conn['id'], 'connection_secret' => $conn['secret']));
          // ok, or 401 invalid_connection when the broker already forgot this connection.
          if (is_wp_error($r)) {
            $problems[] = sprintf(__('%1$s did not confirm the disconnect (%2$s)', $domain), wp_parse_url(self::broker_url(), PHP_URL_HOST), $r->get_error_message());
          } elseif (empty($r['ok']) && (int) $r['_status'] !== 401) {
            $problems[] = sprintf(__('%1$s did not confirm the disconnect (HTTP %2$d)', $domain), wp_parse_url(self::broker_url(), PHP_URL_HOST), (int) $r['_status']);
          }
        }
        if ($problems) {
          // Keep the connection so the revocation can be retried instead of reporting success while access remains.
          $this->fail(sprintf(__('Access could not be revoked yet: %s. The connection was kept so you can try again.', $domain), implode('; ', $problems)));
        }
      }
      $this->remove_option(self::OPTION);
      $this->done(array('sm_gc' => $unreadable ? 'disconnected-unrevoked' : 'disconnected'));
    }

    /* ------------------------------------------------------------------ */
    /* Per-user attempt state                                               */
    /* Lives in user meta, not transients: a misconfigured object cache     */
    /* drops transients between requests (seen on a dev pod whose memcached */
    /* was unreachable), and the PKCE verifier must survive from the        */
    /* sign-in click to the return. The error and log are read once.        */
    /* ------------------------------------------------------------------ */

    private function state_get($key, $uid = null) {
      $uid = $uid ? $uid : get_current_user_id();
      $rec = get_user_meta($uid, $this->state_key($key), true);
      if (!is_array($rec) || !isset($rec['exp'], $rec['v'])) return false;
      if ($rec['exp'] < time()) {
        delete_user_meta($uid, $this->state_key($key));
        return false;
      }
      return $rec['v'];
    }

    private function state_set($key, $value, $ttl, $uid = null) {
      $uid = $uid ? $uid : get_current_user_id();
      update_user_meta($uid, $this->state_key($key), array('v' => $value, 'exp' => time() + $ttl));
    }

    private function state_delete($key, $uid = null) {
      $uid = $uid ? $uid : get_current_user_id();
      delete_user_meta($uid, $this->state_key($key));
    }

    private function parse_redirect($pasted) {
      $out = array();
      if ($pasted === '') return $out;
      // A full URL: query and fragment may both carry parameters.
      if (strpos($pasted, '?') !== false || strpos($pasted, '#') !== false || strpos($pasted, '://') !== false) {
        $parts = wp_parse_url($pasted);
        foreach (array('query', 'fragment') as $k) {
          if (!empty($parts[$k])) {
            parse_str($parts[$k], $tmp);
            $out = array_merge($out, array_filter($tmp, 'is_string'));
          }
        }
        return $out;
      }
      // A bare code or a bare "state=..&code=.." string.
      if (strpos($pasted, '=') !== false) {
        parse_str($pasted, $out);
        return array_filter($out, 'is_string');
      }
      // An eight character pairing code from the hosted page, with or without the dash.
      if (preg_match('/^[A-Za-z0-9]{4}-?[A-Za-z0-9]{4}$/', $pasted)) {
        return array('pair' => strtoupper(str_replace('-', '', $pasted)));
      }
      return array('code' => $pasted);
    }

    private function token_request($fields, $client) {
      if (!empty($fields['broker_conn'])) {
        // Hosted connection: the broker checks the connection credential and adds the client secret.
        $conn = $fields['broker_conn'];
        $body = $this->broker_request('/connect/google/refresh', array(
          'connection_id'     => $conn['id'],
          'connection_secret' => $conn['secret'],
          'refresh_token'     => $fields['refresh_token'],
        ));
        if (is_wp_error($body)) return $body;
        $code = $body['_status'];
        unset($body['_status']);
      } else {
        unset($fields['broker_conn']);
        $fields['client_id']     = $client['id'];
        $fields['client_secret'] = $client['secret'];
        $res = wp_remote_post(self::TOKEN_ENDPOINT, array('body' => $fields, 'timeout' => 30));
        if (is_wp_error($res)) return $res;
        $body = json_decode(wp_remote_retrieve_body($res), true);
        $code = (int) wp_remote_retrieve_response_code($res);
      }
      if ($code !== 200 || empty($body['access_token'])) {
        $msg = isset($body['error_description']) ? $body['error_description'] : (isset($body['error']) ? $body['error'] : 'HTTP ' . $code);
        return new WP_Error('token', sprintf(__('Token exchange failed: %s', ud_get_stateless_media()->domain), $msg));
      }
      return $body;
    }

    public function get_connection() {
      $c = $this->read_option(self::OPTION);
      return (!empty($c['refresh_token'])) ? $c : null;
    }

    private function access_token() {
      $connection = $this->get_connection();
      if (!$connection) return new WP_Error('not_connected', __('Not connected to Google.', ud_get_stateless_media()->domain));
      if (!empty($connection['access_token']) && $connection['expires'] > time()) {
        $plain = $this->decrypt($connection['access_token']);
        if ($plain !== '') return $plain;
      }
      $conn = $this->broker_conn($connection);
      if ($conn) {
        $client = array('id' => $connection['client_id'], 'secret' => '', 'source' => 'broker');
      } else {
        $client = $this->get_client();
        if (!$client) return new WP_Error('no_client', __('OAuth client is not configured.', ud_get_stateless_media()->domain));
      }
      $token = $this->token_request(array(
        'grant_type'    => 'refresh_token',
        'refresh_token' => $this->decrypt($connection['refresh_token']),
        'broker_conn'   => $conn,
      ), $client);
      if (is_wp_error($token)) return $token;
      $connection['access_token'] = $this->encrypt($token['access_token']);
      $connection['expires']      = time() + intval($token['expires_in']) - 60;
      $this->write_option(self::OPTION, $connection, false);
      return $token['access_token'];
    }

    /* ------------------------------------------------------------------ */
    /* Google API helper                                                    */
    /* ------------------------------------------------------------------ */

    public function api($method, $url, $body = null) {
      $token = $this->access_token();
      if (is_wp_error($token)) return $token;
      $args = array(
        'method'  => $method,
        'timeout' => 45,
        'headers' => array(
          'Authorization' => 'Bearer ' . $token,
          'Content-Type'  => 'application/json',
          'Accept'        => 'application/json',
        ),
      );
      if ($body !== null) $args['body'] = wp_json_encode($body);
      $res = wp_remote_request($url, $args);
      if (is_wp_error($res)) return $res;
      $code = wp_remote_retrieve_response_code($res);
      $raw  = wp_remote_retrieve_body($res);
      $data = json_decode($raw, true);
      if ($code >= 400) {
        $msg = isset($data['error']['message']) ? $data['error']['message'] : (isset($data['error_description']) ? $data['error_description'] : substr($raw, 0, 300));
        $err = new WP_Error('google_' . $code, $msg, array('status' => $code, 'body' => $data));
        return $err;
      }
      return is_array($data) ? $data : array();
    }

    private function wait_operation($url, $seconds = 90) {
      $deadline = time() + $seconds;
      do {
        $op = $this->api('GET', $url);
        if (is_wp_error($op)) return $op;
        if (!empty($op['done'])) {
          if (!empty($op['error'])) {
            return new WP_Error('operation', isset($op['error']['message']) ? $op['error']['message'] : 'Operation failed');
          }
          return $op;
        }
        sleep(3);
      } while (time() < $deadline);
      return new WP_Error('timeout', __('Google operation did not finish in time.', ud_get_stateless_media()->domain));
    }

    /* ------------------------------------------------------------------ */
    /* Provisioning: project, APIs, bucket, service account, role, key      */
    /* ------------------------------------------------------------------ */

    public function handle_provision() {
      $this->guard();
      if (!$this->get_connection()) $this->fail(__('Not connected to Google.', ud_get_stateless_media()->domain));
      $blocked = $this->storage_blocked();
      if ($blocked) $this->fail($blocked);
      @set_time_limit(300);

      $log = array();
      $uid = get_current_user_id();

      $post           = wp_unslash($_POST); // WordPress slashes request data; a name like John's website must reach Google unescaped
      $project_choice = isset($post['project']) ? sanitize_text_field($post['project']) : '';
      $new_project    = isset($post['new_project_name']) ? sanitize_text_field($post['new_project_name']) : '';
      $billing_choice = isset($post['billing_account']) ? sanitize_text_field($post['billing_account']) : '';
      $bucket_choice  = isset($post['bucket_choice']) ? sanitize_text_field($post['bucket_choice']) : 'new';
      $bucket         = isset($post['bucket_name']) ? strtolower(trim(sanitize_text_field($post['bucket_name']))) : '';
      $location       = isset($post['location']) ? sanitize_text_field($post['location']) : 'US';
      $mode           = isset($post['mode']) ? sanitize_text_field($post['mode']) : 'ephemeral';
      if (!in_array($mode, array('backup', 'cdn', 'ephemeral', 'stateless'), true)) $mode = 'ephemeral';
      $locked_mode    = $this->locked_mode();
      if ($locked_mode) $mode = $locked_mode['mode']; // the form shows the locked mode; never trust a posted one

      $draft = array('project' => $project_choice, 'new_project_name' => $new_project, 'billing_account' => $billing_choice, 'bucket_choice' => $bucket_choice, 'bucket_name' => $bucket, 'location' => $location, 'mode' => $mode);
      $this->state_set('draft', $draft, HOUR_IN_SECONDS, $uid);
      if ($this->requires_stateless_mode() && $mode !== 'stateless') {
        $this->fail($locked_mode
          ? sprintf(__('This environment requires Stateless mode, but the storage mode is set to %1$s by %2$s. Change it there before continuing.', ud_get_stateless_media()->domain), $mode, $locked_mode['source'])
          : __('This environment requires Stateless mode. Select it before continuing.', ud_get_stateless_media()->domain));
      }
      $name_error = self::bucket_name_error($bucket);
      if ($name_error) $this->fail($name_error);
      if ($project_choice === '__new__' && !$billing_choice) $this->fail(__('Choose an open billing account before creating a project.', ud_get_stateless_media()->domain));
      if (!in_array($bucket_choice, array('new', 'existing'), true)) $this->fail(__('Choose whether to create or reuse a bucket.', ud_get_stateless_media()->domain));

      try {
        /* 1. Project */
        if ($project_choice === '__new__') {
          if (!$new_project) throw new Exception(__('Give the new project a name.', ud_get_stateless_media()->domain));
          $project_id = substr(preg_replace('/[^a-z0-9-]/', '-', strtolower($new_project)), 0, 20) . '-' . substr(md5(uniqid('', true)), 0, 6);
          $project_id = trim(preg_replace('/-+/', '-', $project_id), '-');
          if (!preg_match('/^[a-z]/', $project_id)) $project_id = 'wp-' . $project_id;
          $op = $this->api('POST', 'https://cloudresourcemanager.googleapis.com/v1/projects', array(
            'projectId' => $project_id,
            'name'      => $new_project,
          ));
          $this->check($op, 'Create project');
          $log[] = sprintf('Creating project %s (%s)', $new_project, $project_id);
          $this->check($this->wait_operation('https://cloudresourcemanager.googleapis.com/v1/' . $op['name']), 'Create project');
          $log[] = 'Project created';

          if ($billing_choice) {
            $r = $this->api('PUT', 'https://cloudbilling.googleapis.com/v1/projects/' . rawurlencode($project_id) . '/billingInfo', array(
              'billingAccountName' => $billing_choice,
            ));
            $this->check($r, 'Link billing account');
            $log[] = 'Billing account linked';
          } else {
            $log[] = 'No billing account linked. Google requires one before a bucket can be created.';
          }
          // Checkpoint: a retry after a later failure reuses this project instead of creating another one.
          $draft['project'] = $project_id;
          $draft['new_project_name'] = '';
          $draft['billing_account'] = '';
          $this->state_set('draft', $draft, HOUR_IN_SECONDS, $uid);
        } else {
          $project_id = $project_choice;
          if (!preg_match('/^[a-z][a-z0-9-]{4,29}$/', $project_id)) throw new Exception(__('Pick a project.', ud_get_stateless_media()->domain));
          $log[] = sprintf('Using project %s', $project_id);
        }

        // Check a manually entered bucket before creating accounts, keys or changing permissions.
        if ($bucket_choice === 'existing') {
          $existing_bucket = $this->api('GET', 'https://storage.googleapis.com/storage/v1/b/' . rawurlencode($bucket));
          $this->check($existing_bucket, 'Find bucket');
          $project_info = $this->api('GET', 'https://cloudresourcemanager.googleapis.com/v1/projects/' . rawurlencode($project_id));
          $this->check($project_info, 'Check bucket project');
          if (empty($existing_bucket['projectNumber']) || empty($project_info['projectNumber']) || (string) $existing_bucket['projectNumber'] !== (string) $project_info['projectNumber']) {
            throw new Exception(__('This bucket belongs to a different project. Select its project before continuing.', ud_get_stateless_media()->domain));
          }
          $uniform = !empty($existing_bucket['iamConfiguration']['uniformBucketLevelAccess']['enabled']);
          if ($uniform && (!defined('WP_STATELESS_SKIP_ACL_SET') || !WP_STATELESS_SKIP_ACL_SET)) {
            throw new Exception(__('This bucket uses uniform access. Configure WP_STATELESS_SKIP_ACL_SET before connecting it, or choose a bucket with fine-grained access.', ud_get_stateless_media()->domain));
          }
          // CDN, Ephemeral and Stateless serve media through unsigned public URLs. The plugin sets a public-read
          // ACL per object on fine-grained buckets, but it cannot do that on a uniform-access bucket, and no ACL
          // helps when public access prevention is enforced, so those buckets must already be readable by allUsers.
          if ($mode !== 'backup') {
            if (isset($existing_bucket['iamConfiguration']['publicAccessPrevention']) && $existing_bucket['iamConfiguration']['publicAccessPrevention'] === 'enforced') {
              throw new Exception(__('This bucket enforces public access prevention, so media URLs would be refused in CDN, Ephemeral or Stateless mode. Turn it off for this bucket, or choose Backup mode.', ud_get_stateless_media()->domain));
            }
            // With WP_STATELESS_SKIP_ACL_SET the plugin writes no object ACLs on fine-grained buckets either,
            // so readability must already come from the bucket: an allUsers IAM binding or a public default object ACL.
            $skip_acl = defined('WP_STATELESS_SKIP_ACL_SET') && WP_STATELESS_SKIP_ACL_SET;
            if ($uniform || $skip_acl) {
              $policy = $this->api('GET', 'https://storage.googleapis.com/storage/v1/b/' . rawurlencode($bucket) . '/iam?optionsRequestedPolicyVersion=3');
              $this->check($policy, 'Read bucket permissions');
              $public = self::bucket_is_public(isset($policy['bindings']) ? $policy['bindings'] : array());
              if (!$public && !$uniform) {
                $default_acl = $this->api('GET', 'https://storage.googleapis.com/storage/v1/b/' . rawurlencode($bucket) . '/defaultObjectAcl');
                $public = !is_wp_error($default_acl) && self::default_acl_is_public(isset($default_acl['items']) ? $default_acl['items'] : array());
              }
              if (!$public) {
                throw new Exception($uniform
                  ? __('This bucket uses uniform access but is not publicly readable, so media URLs would return 403 in CDN, Ephemeral or Stateless mode. Grant allUsers the Storage Object Viewer role on the bucket, or choose Backup mode.', ud_get_stateless_media()->domain)
                  : __('WP_STATELESS_SKIP_ACL_SET is enabled, so the plugin will not make uploads public itself, and this bucket is not publicly readable: media URLs would return 403 in CDN, Ephemeral or Stateless mode. Grant allUsers the Storage Object Viewer role on the bucket, give it a public default object ACL, or choose Backup mode.', ud_get_stateless_media()->domain));
              }
            }
          }
        }

        /* 2. APIs */
        $op = $this->api('POST', 'https://serviceusage.googleapis.com/v1/projects/' . rawurlencode($project_id) . '/services:batchEnable', array(
          'serviceIds' => array('storage.googleapis.com', 'iam.googleapis.com'),
        ));
        $this->check($op, 'Enable Cloud Storage and IAM APIs');
        if (!empty($op['name']) && empty($op['done'])) {
          $this->check($this->wait_operation('https://serviceusage.googleapis.com/v1/' . $op['name']), 'Enable APIs');
        }
        $log[] = 'Cloud Storage and IAM APIs enabled';

        /* 3. Bucket */
        if ($bucket_choice === 'new') {
          $r = $this->api('POST', 'https://storage.googleapis.com/storage/v1/b?project=' . rawurlencode($project_id) . '&predefinedDefaultObjectAcl=publicRead', array(
            'name'             => $bucket,
            'location'         => $location,
            'storageClass'     => 'STANDARD',
            'iamConfiguration' => array(
              'uniformBucketLevelAccess' => array('enabled' => false),
              'publicAccessPrevention'   => 'inherited',
            ),
          ));
          if (is_wp_error($r) && $r->get_error_code() === 'google_409') {
            throw new Exception(sprintf(__('The bucket name %s is already taken. Bucket names are global across Google Cloud, pick another.', ud_get_stateless_media()->domain), $bucket));
          }
          $this->check($r, 'Create bucket');
          $log[] = sprintf('Bucket %s created in %s, new objects public by default', $bucket, $location);
          // Checkpoint: the name is taken now, so a retry must reuse the bucket rather than create it again.
          $draft['bucket_choice'] = 'existing';
          $this->state_set('draft', $draft, HOUR_IN_SECONDS, $uid);
        } else {
          $r = $this->api('GET', 'https://storage.googleapis.com/storage/v1/b/' . rawurlencode($bucket));
          $this->check($r, 'Find bucket');
          $log[] = sprintf('Using existing bucket %s', $bucket);
        }

        /* 4. Service account */
        $account_id = 'wp-stateless-' . substr(md5(home_url() . ($this->network_scope() ? ':network:' . get_current_network_id() : '')), 0, 8);
        $sa_email   = $account_id . '@' . $project_id . '.iam.gserviceaccount.com';
        $r = $this->api('POST', 'https://iam.googleapis.com/v1/projects/' . rawurlencode($project_id) . '/serviceAccounts', array(
          'accountId'      => $account_id,
          'serviceAccount' => array(
            'displayName' => 'WP-Stateless ' . parse_url(home_url(), PHP_URL_HOST),
            'description' => 'Created by WP-Stateless from ' . home_url(),
          ),
        ));
        if (is_wp_error($r) && $r->get_error_code() === 'google_409') {
          $log[] = sprintf('Service account %s already exists, reusing it', $sa_email);
        } else {
          $this->check($r, 'Create service account');
          $log[] = sprintf('Service account %s created', $sa_email);
        }

        /* 5. Bucket-scoped role (never project-wide) */
        $attempt = 0;
        do {
          $attempt++;
          $policy = $this->api('GET', 'https://storage.googleapis.com/storage/v1/b/' . rawurlencode($bucket) . '/iam?optionsRequestedPolicyVersion=3');
          $this->check($policy, 'Read bucket permissions');
          $bindings = isset($policy['bindings']) ? $policy['bindings'] : array();
          $member   = 'serviceAccount:' . $sa_email;
          $bindings = self::bucket_bindings($bindings, $member);
          $r = $this->api('PUT', 'https://storage.googleapis.com/storage/v1/b/' . rawurlencode($bucket) . '/iam', array(
            'version'  => isset($policy['version']) ? $policy['version'] : 1,
            'bindings' => $bindings,
            'etag'     => isset($policy['etag']) ? $policy['etag'] : null,
          ));
          if (is_wp_error($r) && $attempt < 6 && in_array($r->get_error_code(), array('google_400', 'google_404', 'google_409', 'google_412'), true)) {
            sleep(4); // new service account not yet visible to IAM
            continue;
          }
          $this->check($r, 'Grant bucket access');
          break;
        } while (true);
        $log[] = 'Service account granted Storage Admin on this bucket only';

        /* 6. Key. Remember the key this site used before so it can be revoked once the new one is saved. */
        $previous_key = json_decode((string) $this->read_option('sm_key_json', ''), true);
        $attempt = 0;
        do {
          $attempt++;
          $r = $this->api('POST', 'https://iam.googleapis.com/v1/projects/-/serviceAccounts/' . rawurlencode($sa_email) . '/keys', array(
            'privateKeyType' => 'TYPE_GOOGLE_CREDENTIALS_FILE',
          ));
          if (is_wp_error($r) && $attempt < 6 && $r->get_error_code() === 'google_404') {
            sleep(4);
            continue;
          }
          $this->check($r, 'Create service account key');
          break;
        } while (true);
        $key_json = base64_decode($r['privateKeyData']);
        if (!json_decode($key_json)) throw new Exception(__('Google returned an unreadable key.', ud_get_stateless_media()->domain));
        $log[] = 'Key issued to this site';

        /* 7. Verify with the new key itself before anything is replaced (IAM can lag a few seconds). A site that
         * already works keeps its configuration when the check fails; a first setup is saved with a warning
         * because there is nothing to lose and the check usually passes a moment later. */
        $verified      = $this->verify_key($key_json, $bucket, 10);
        $new_key       = json_decode($key_json, true);
        $reconfiguring = is_array($previous_key) && !empty($previous_key['private_key_id']) && (string) $this->read_option('sm_bucket', '') !== '';
        if ($verified !== true && $reconfiguring) {
          $removed = $this->revoke_key($sa_email, $new_key['private_key_id']);
          $log[] = $removed === true ? 'New key removed again' : 'New key could not be removed yet; it will be retried on the next setup';
          throw new Exception(sprintf(__('The new credentials could not reach the bucket (%s), so the current configuration was left unchanged. IAM changes can take a minute to apply; try again shortly.', ud_get_stateless_media()->domain), $verified));
        }

        /* 8. Save plugin settings */
        $this->write_option('sm_bucket', $bucket);
        $this->write_option('sm_key_json', $key_json);
        $this->write_option('sm_mode', $mode);
        if (apply_filters('wp_stateless_is_app_engine', false)) {
          $modules = $this->read_option('stateless-modules', array());
          $modules['google-app-engine'] = 'true';
          $this->write_option('stateless-modules', $modules);
        }
        delete_transient('sm::is_connected_to_gs');
        $connection = $this->get_connection();
        $connection['project']  = $project_id;
        $connection['bucket']   = $bucket;
        $connection['sa_email'] = $sa_email;
        $this->write_option(self::OPTION, $connection, false);
        $this->write_option(self::LAST_OPTION, array('project' => $project_id, 'bucket' => $bucket), false);
        $log[] = sprintf('Plugin configured: mode %s, bucket %s', $mode, $bucket);

        /* 8b. Revoke the key this site held before, so repeated setups do not pile up keys on the account. */
        if (is_array($previous_key) && !empty($previous_key['private_key_id']) && !empty($previous_key['client_email'])
            && $previous_key['client_email'] === $sa_email && $previous_key['private_key_id'] !== $new_key['private_key_id']) {
          $revoked = $this->revoke_key($sa_email, $previous_key['private_key_id']);
          $log[] = $revoked === true
            ? 'Previous key for this site revoked'
            : 'Previous key for this site could not be revoked yet (' . $revoked->get_error_message() . '); it will be retried on the next setup';
        }
        $this->retry_pending_revocations($log);

        $log[] = $verified === true
          ? 'Verified: the site reached the bucket with its own credentials'
          : 'Saved, but the first connection check did not pass yet: ' . $verified . ' (IAM changes can take a minute to apply; reload Stateless Settings in a moment)';

        $this->state_set('log', $log, 10 * MINUTE_IN_SECONDS, $uid);
        $this->state_delete('draft', $uid);
        $this->done(array('sm_gc' => 'provisioned'));
      } catch (Exception $e) {
        $log[] = 'Stopped: ' . $e->getMessage();
        $this->state_set('log', $log, 10 * MINUTE_IN_SECONDS, $uid);
        $this->fail($e->getMessage());
      }
    }

    /**
     * Deletes a service account key. A failure is remembered in an option so a later setup retries it,
     * instead of leaving active keys behind until someone cleans them up by hand.
     * Returns true, or the WP_Error of the failed request.
     */
    private function revoke_key($sa_email, $key_id) {
      $r = $this->api('DELETE', 'https://iam.googleapis.com/v1/projects/-/serviceAccounts/' . rawurlencode($sa_email) . '/keys/' . rawurlencode($key_id));
      $pending = $this->read_option(self::PENDING_KEYS_OPTION, array());
      if (!is_array($pending)) $pending = array();
      $name = $sa_email . '/' . $key_id;
      if (is_wp_error($r) && $r->get_error_code() !== 'google_404') {
        $pending[$name] = time();
        $this->write_option(self::PENDING_KEYS_OPTION, $pending, false);
        return $r;
      }
      if (isset($pending[$name])) {
        unset($pending[$name]);
        $pending ? $this->write_option(self::PENDING_KEYS_OPTION, $pending, false) : $this->remove_option(self::PENDING_KEYS_OPTION);
      }
      return true;
    }

    private function retry_pending_revocations(&$log) {
      $pending = $this->read_option(self::PENDING_KEYS_OPTION, array());
      if (!is_array($pending) || !$pending) return;
      foreach (array_keys($pending) as $name) {
        list($email, $key_id) = array_pad(explode('/', $name, 2), 2, '');
        if ($email === '' || $key_id === '') continue;
        $r = $this->revoke_key($email, $key_id);
        $log[] = $r === true ? 'Earlier key ' . $key_id . ' revoked' : 'Earlier key ' . $key_id . ' still pending revocation: ' . $r->get_error_message();
      }
    }

    private function verify_key($key_json, $bucket, $attempts = 6) {
      if (!class_exists('\Google\Cloud\Storage\StorageClient')) {
        include_once(ud_get_stateless_media()->path('lib/Google/vendor/autoload.php', 'dir'));
      }
      $last = '';
      for ($i = 0; $i < $attempts; $i++) {
        try {
          $client = new \Google\Cloud\Storage\StorageClient(array('keyFile' => json_decode($key_json, true)));
          $client->bucket($bucket)->info();
          return true;
        } catch (\Throwable $e) {
          $last = $e->getMessage();
          $decoded = json_decode($last, true);
          if (isset($decoded['error']['message'])) $last = $decoded['error']['message'];
          sleep(4);
        }
      }
      return $last;
    }

    private function check($result, $step) {
      if (is_wp_error($result)) {
        throw new Exception($step . ': ' . $result->get_error_message());
      }
    }

    /* ------------------------------------------------------------------ */
    /* Helpers                                                              */
    /* ------------------------------------------------------------------ */

    private function guard() {
      if (!current_user_can($this->capability())) wp_die(__('Insufficient permissions.'));
      check_admin_referer($this->nonce_action());
    }

    private function done($args) {
      wp_safe_redirect($this->page_url($args));
      exit;
    }

    private function fail($message) {
      $this->state_set('error', wp_strip_all_tags((string) $message), 10 * MINUTE_IN_SECONDS);
      $this->done(array('sm_gc' => 'error'));
    }

    private static function b64url($bin) {
      return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    private function crypto_key() {
      return hash('sha256', wp_salt('auth') . '|' . wp_salt('secure_auth') . '|sm_google_connect', true);
    }

    private function encrypt($plain) {
      if (!function_exists('sodium_crypto_secretbox')) return 'plain:' . $plain;
      $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
      return 'v1:' . base64_encode($nonce . sodium_crypto_secretbox($plain, $nonce, $this->crypto_key()));
    }

    private function decrypt($stored) {
      if (strpos($stored, 'plain:') === 0) return substr($stored, 6);
      if (strpos($stored, 'v1:') !== 0 || !function_exists('sodium_crypto_secretbox_open')) return '';
      $raw   = base64_decode(substr($stored, 3));
      $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
      $box   = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
      $plain = sodium_crypto_secretbox_open($box, $nonce, $this->crypto_key());
      return $plain === false ? '' : $plain;
    }
  }
}
