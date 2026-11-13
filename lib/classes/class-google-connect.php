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
    /** A key Google issued during a setup that has not finished yet, kept encrypted so a resumed setup reuses it. */
    const SETUP_KEY_OPTION = 'sm_gc_pending_setup_key';
    /** Name of the connected bucket when it uses uniform access; uploads to it leave per-object permissions alone. */
    const UNIFORM_OPTION = 'sm_uniform_bucket';
    /** Seconds after which a claim on a setup scope runs out where the database cannot tell that its owner died. */
    const PROVISION_STALE = 900;
    const FUNNEL_OPTION   = 'sm_gc_funnel';
    const BROKER          = 'https://stateless.ci';
    const SCOPES          = 'openid email https://www.googleapis.com/auth/cloud-platform';
    const AUTH_ENDPOINT   = 'https://accounts.google.com/o/oauth2/v2/auth';
    const TOKEN_ENDPOINT  = 'https://oauth2.googleapis.com/token';
    const REVOKE_ENDPOINT = 'https://oauth2.googleapis.com/revoke';
    const NONCE           = 'sm_google_connect';

    /** @var string|null */
    private $page_hook = null;
    private $network_hook = null;
    private $provision_job = null;
    private $provision_lock = false;
    private $scope_taken = false;
    private $scope_since = 0;

    /** @var string Why the hosted sign-in could not be prepared, for the page to show. */
    public $start_error = '';

    protected function __construct() {
      add_action('rest_api_init', array($this, 'rest_routes'));
      add_action('sm_gc_funnel_cleanup', array($this, 'clear_expired_funnel'), 10, 2);
      add_filter('wp_stateless_media_synced', array($this, 'track_first_upload'), 999, 4);
      if (!is_admin()) return;
      add_action('admin_init', array($this, 'redirect_legacy_setup'));
      add_action('admin_menu', array($this, 'admin_menu'), 20);
      add_action('network_admin_menu', array($this, 'network_admin_menu'), 20);
      add_action('admin_enqueue_scripts', array($this, 'enqueue'));
      add_action('admin_post_sm_gc_begin', array($this, 'handle_begin'));
      add_action('admin_post_sm_gc_client', array($this, 'handle_client'));
      add_action('admin_post_sm_gc_finish', array($this, 'handle_finish'));
      add_action('admin_post_sm_gc_provision', array($this, 'handle_provision'));
      add_action('admin_post_sm_gc_billing_recheck', array($this, 'handle_billing_recheck'));
      add_action('admin_post_sm_gc_disconnect', array($this, 'handle_disconnect'));
      add_action('admin_post_sm_gc_restart', array($this, 'handle_restart'));
      add_action('wp_ajax_sm_gc_provision_status', array($this, 'ajax_provision_status'));
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
      $this->assert_provision_lock();
      return $this->network_scope() ? update_network_option(get_current_network_id(), $key, $value) : update_option($key, $value, $autoload);
    }

    private function remove_option($key) {
      $this->assert_provision_lock();
      return $this->network_scope() ? delete_network_option(get_current_network_id(), $key) : delete_option($key);
    }

    /**
     * Makes the next read of an option come from the database. WordPress answers option reads from the copy a
     * request already has, which is wrong for the few records that requests use to learn what another one did
     * in the meantime.
     */
    private function reread_option($key, $network_id = 0) {
      $network_id ? wp_cache_delete($network_id . ':' . $key, 'site-options') : wp_cache_delete($key, 'options');
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
      list($log, $error) = $this->last_result($uid);

      $auth_url  = '';
      $projects  = array();
      $billing   = array();
      $proj_err  = null;
      $proj_note = '';
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
        } while ($page_token !== '' && ++$pages < 50);
        if ($page_token !== '' && !$proj_err) {
          $proj_note = sprintf(__('Only the first %d projects are listed. If yours is missing, create a new project here or ask your Google Cloud administrator to move the bucket into one of the listed projects.', ud_get_stateless_media()->domain), count($projects));
        }
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
      if (is_array($draft)) {
        if (isset($draft['project'])) $default_project = $draft['project'];
        if (isset($draft['bucket_name'])) $default_bucket = $draft['bucket_name'];
      }
      $settings = (object) ud_get_stateless_media()->get('sm');
      if ($this->network_scope()) {
        foreach (array('bucket', 'key_json', 'mode') as $key) $settings->$key = $this->read_option('sm_' . $key, $key === 'mode' ? 'disabled' : '');
      }
      if ($default_project === '' && (empty($settings->key_json) || empty($settings->bucket))) $default_project = '__new__';
      $suggested_project = preg_replace('/[^a-zA-Z0-9\x20\x21\x22\x27-]/', '-', get_bloginfo('name'));
      $suggested_project = trim(substr(preg_replace('/\s+/', ' ', $suggested_project), 0, 30));
      if (strlen($suggested_project) < 4) $suggested_project = trim(substr($suggested_project . ' media', 0, 30));
      if (strlen($suggested_project) < 4) $suggested_project = 'WordPress media';
      $billing_recovery = is_array($draft) && !empty($draft['billing_required']) && !empty($draft['project']) && $draft['project'] !== '__new__';
      $billing_unavailable = $default_project === '__new__' && !$billing;
      // An existing bucket the last attempt found private: the screen then offers the choice to make it public.
      $public_required = is_array($draft) && !empty($draft['public_required']) && isset($draft['bucket_choice']) && $draft['bucket_choice'] === 'existing';
      $bucket_public   = is_array($draft) && !empty($draft['bucket_public']) && isset($draft['bucket_name']) && $draft['bucket_public'] === $draft['bucket_name'];

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
        $buckets[] = array('name' => $b['name'], 'location' => isset($b['location']) ? $b['location'] : '', 'locationType' => isset($b['locationType']) ? $b['locationType'] : '', 'storageClass' => isset($b['storageClass']) ? $b['storageClass'] : '', 'uniform' => !empty($b['iamConfiguration']['uniformBucketLevelAccess']['enabled']));
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

    /** Lets allUsers read the bucket's objects; every other binding, conditional ones included, is kept as it is. */
    public static function public_bindings($bindings) {
      if (self::bucket_is_public($bindings)) return $bindings;
      foreach ($bindings as &$binding) {
        if (isset($binding['role']) && $binding['role'] === 'roles/storage.objectViewer' && empty($binding['condition'])) {
          $binding['members'][] = 'allUsers';
          return $bindings;
        }
      }
      unset($binding);
      $bindings[] = array('role' => 'roles/storage.objectViewer', 'members' => array('allUsers'));
      return $bindings;
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

    public function handle_begin() {
      $this->guard();
      $this->clear_result();
      if ($this->get_connection()) $this->done(array('sm_gc' => 'connected'));
      $client = $this->get_client();
      if (!$client) $this->fail(__('OAuth client is not configured.', ud_get_stateless_media()->domain));
      $auth_url = $this->build_auth_url($client, get_current_user_id());
      if (!$auth_url || strpos($auth_url, self::AUTH_ENDPOINT . '?') !== 0) {
        $this->fail($this->start_error ?: __('The sign-in could not be prepared. Check the connection to the sign-in service and try again.', ud_get_stateless_media()->domain));
      }
      $this->track_event('clicked');
      wp_redirect($auth_url, 303);
      exit;
    }

    private function funnel_valid($state) {
      if (!is_array($state) || empty($state['txn']) || empty($state['token']) || empty($state['created']) || !is_numeric($state['created'])) return false;
      if (!is_string($state['txn']) || !is_string($state['token'])) return false;
      if (!preg_match('/^[A-Za-z0-9_-]{32}$/', $state['txn']) || !preg_match('/^[A-Za-z0-9_-]{32}$/', $state['token'])) return false;
      return (int) $state['created'] >= time() - DAY_IN_SECONDS;
    }

    private function funnel_option($uid = 0) {
      return $uid ? self::FUNNEL_OPTION . '_u_' . (int) $uid : self::FUNNEL_OPTION;
    }

    /** The sign-in attempt on record for this scope, or for one administrator, as it was saved. Null when there is none. */
    private function funnel_stored($network_id = 0, $uid = 0) {
      $option = $this->funnel_option($uid);
      return $network_id && is_multisite() ? get_network_option($network_id, $option, null) : get_option($option, null);
    }

    private function funnel_state($network_id = 0, $uid = 0) {
      $state = $this->funnel_stored($network_id, $uid);
      if ($state === null) return null;
      if (!$this->funnel_valid($state)) {
        $this->forget_funnel($network_id, $state, true, $uid);
        return null;
      }
      return $state;
    }

    /**
     * Removes the sign-in attempt on record. With $conditional only while it still is $expected, so that an
     * attempt another request saved in the meantime stays.
     */
    private function forget_funnel($network_id = 0, $expected = null, $conditional = false, $uid = 0) {
      $option  = $this->funnel_option($uid);
      $network = $network_id && is_multisite();
      if ($conditional) {
        // Compare with the record as it is now, not as this request first saw it: a bulk sync runs for minutes.
        // What remains is the instant between this look and the removal, and what is at stake is one measurement.
        $this->reread_option($option, $network ? $network_id : 0);
        $stored = $this->funnel_stored($network_id, $uid);
        if ($stored === null || maybe_serialize($stored) !== maybe_serialize($expected)) return false;
        return $network ? delete_network_option($network_id, $option) : delete_option($option);
      }
      $network ? delete_network_option($network_id, $option) : delete_option($option);
      return true;
    }

    private function schedule_funnel_cleanup($created, $network_id, $uid = 0) {
      if (!wp_next_scheduled('sm_gc_funnel_cleanup', array($network_id, $uid))) {
        wp_schedule_single_event((int) $created + DAY_IN_SECONDS, 'sm_gc_funnel_cleanup', array($network_id, $uid));
      }
    }

    public function clear_expired_funnel($network_id = 0, $uid = 0) {
      $stored = $this->funnel_stored((int) $network_id, $uid);
      if ($stored === null) return;
      if (!$this->funnel_valid($stored)) {
        if (!$this->forget_funnel($network_id, $stored, true, $uid)) {
          $current = $this->funnel_state($network_id, $uid);
          if ($current) $this->schedule_funnel_cleanup($current['created'], $network_id, $uid);
        }
        return;
      }
      $this->schedule_funnel_cleanup($stored['created'], $network_id, $uid);
    }

    private function remember_funnel($txn, $token) {
      $network_id = $this->network_scope() ? get_current_network_id() : 0;
      $uid = get_current_user_id();
      $state = array('txn' => $txn, 'token' => $token, 'created' => time());
      $this->write_option($this->funnel_option($uid), $state, false);
      $this->schedule_funnel_cleanup($state['created'], $network_id, $uid);
    }

    private function track_event($event, $reason = '', $network_id = null, $expected = null) {
      if ($network_id === null) $network_id = $this->network_scope() ? get_current_network_id() : 0;
      if ($expected === null && in_array($event, array('viewed', 'clicked'), true)) {
        // Viewing and clicking belong to this administrator's own sign-in attempt. Every later milestone
        // belongs to the attempt that connected the site, whoever is signed in when it happens.
        $state = $this->funnel_state($network_id, get_current_user_id());
      } else {
        $state = $this->funnel_state($network_id);
      }
      if (!$state) return false;
      if ($expected !== null) {
        if (!is_array($expected) || empty($expected['txn']) || empty($expected['token']) || !is_string($expected['txn']) || !is_string($expected['token'])) return false;
        if (!hash_equals($expected['txn'], $state['txn']) || !hash_equals($expected['token'], $state['token'])) return false;
      }
      $body = array('txn' => $state['txn'], 'verify_token' => $state['token'], 'event' => $event);
      if ($reason !== '') $body['reason'] = $reason;
      $response = wp_remote_post(self::broker_url() . '/connect/google/event', array(
        'timeout'    => 2,
        'user-agent' => 'WP-Stateless/' . Bootstrap::$version,
        'body'       => $body,
      ));
      if (is_wp_error($response) || (int) wp_remote_retrieve_response_code($response) !== 200) return false;
      $data = json_decode(wp_remote_retrieve_body($response), true);
      return is_array($data) && !empty($data['ok']);
    }

    /**
     * Sync attempts seen in this request while a setup funnel is open. WordPress saves attachment metadata
     * several times while it generates image sizes, so the outcome is judged once, after the last pass.
     */
    private $funnel_uploads = array();

    /** @var array Setup attempts already told about a failed upload by this process. */
    private $funnel_failures = array();

    /** @var array Setup attempts whose first upload this process has already reported, confirmed or not. */
    private $funnel_done = array();

    /** @var int Sync attempts this process has judged. A bulk run is no longer measured after 100. */
    private $funnel_judged = 0;

    /** @var array When this process last found no setup attempt to measure on a site, keyed by blog ID. */
    private $funnel_idle = array();

    public function track_first_upload($metadata, $attachment_id, $force = false, $args = array()) {
      $blog_id = get_current_blog_id();
      if ($this->funnel_judged >= 100) return $metadata;
      // A site with no setup attempt open is looked up at most once a minute by a process, not on every sync.
      if (isset($this->funnel_idle[$blog_id]) && time() - $this->funnel_idle[$blog_id] < MINUTE_IN_SECONDS) return $metadata;
      $network_id = $this->network_scope() ? get_current_network_id() : 0;
      $state = $this->funnel_state($network_id);
      if (!$state && is_multisite()) {
        $candidate = get_current_network_id();
        $network_bucket = get_network_option($candidate, 'sm_bucket', '');
        if ($network_bucket !== '' && $network_bucket === ud_get_stateless_media()->get('sm.bucket')) {
          $state = $this->funnel_state($candidate);
          if ($state) $network_id = $candidate;
        }
      }
      if (!$state) {
        $this->funnel_idle[$blog_id] = time();
        return $metadata;
      }
      $attempt = $network_id . ':' . $state['txn'];
      if (!empty($this->funnel_done[$attempt])) return $metadata;
      $key = $blog_id . ':' . (int) $attachment_id;
      // A bulk sync can touch thousands of attachments in one process. The ones seen earlier are finished
      // by the time another arrives, so judge those now and keep the list short.
      if (!isset($this->funnel_uploads[$key]) && count($this->funnel_uploads) >= 10) {
        $this->report_first_upload();
        if ($this->funnel_judged >= 100 || !empty($this->funnel_done[$attempt])) return $metadata;
      }
      if (!$this->funnel_uploads) add_action('shutdown', array($this, 'report_first_upload'), 1000);
      $this->funnel_uploads[$key] = array($blog_id, (int) $attachment_id, $network_id, $state);
      return $metadata;
    }

    public function report_first_upload() {
      $pending = $this->funnel_uploads;
      $this->funnel_uploads = array();
      // Send the response first where the server allows it, so the checks below never hold up an upload.
      if ($pending && function_exists('fastcgi_finish_request') && doing_action('shutdown')) fastcgi_finish_request();
      foreach ($pending as $upload) {
        list($blog_id, $attachment_id, $network_id, $state) = $upload;
        $attempt = $network_id . ':' . $state['txn'];
        if (!empty($this->funnel_done[$attempt])) continue;
        $this->funnel_judged++;
        $switched = is_multisite() && get_current_blog_id() !== $blog_id;
        if ($switched) switch_to_blog($blog_id);
        try {
          try {
            $result = $this->first_upload_result($attachment_id, $state);
          } catch (\Throwable $e) {
            $result = 'failed';
          }
          if ($result === 'failed' && empty($this->funnel_failures[$attempt])) {
            $this->funnel_failures[$attempt] = true;
            $this->track_event('upload_failed', 'storage', $network_id, $state);
          }
          if ($result === 'uploaded') {
            // One report per process. If it is not confirmed the record stays open for a later request.
            $this->funnel_done[$attempt] = true;
            if ($this->track_event('first_upload', '', $network_id, $state)) $this->forget_funnel($network_id, $state, true);
          }
        } catch (\Throwable $e) {
          // Setup measurement must never interrupt a sync.
        } finally {
          if ($switched) restore_current_blog();
        }
      }
    }

    /**
     * 'uploaded' when the original and every generated size are in the bucket and the original was written
     * after setup started, 'failed' when an upload failed or an object is missing, '' when nothing new was sent.
     */
    private function first_upload_result($attachment_id, $state) {
      if (Utility::upload_errors($attachment_id)) return 'failed';
      $cloud = get_post_meta($attachment_id, 'sm_cloud', true);
      if (empty($cloud['name']) || empty($cloud['bucket'])) return '';
      // Syncing media that was already in the bucket is not a first upload. Allow for clock drift.
      $object = ud_get_stateless_media()->get_client()->get_media($cloud['name']);
      if (!$object) return 'failed';
      $written = !empty($object->timeCreated) ? strtotime($object->timeCreated) : 0;
      if (!$written || $written < (int) $state['created'] - 5 * MINUTE_IN_SECONDS) return '';
      return Utility::missing_cloud_object($attachment_id) !== '' ? 'failed' : 'uploaded';
    }

    public function handle_billing_recheck() {
      $this->guard();
      $this->clear_result();
      if (!$this->get_connection()) $this->fail(__('Sign in to Google first.', ud_get_stateless_media()->domain));
      $uid = get_current_user_id();
      $draft = $this->state_get('draft', $uid);
      $project = is_array($draft) && !empty($draft['project']) ? $draft['project'] : '';
      if (!preg_match('/^[a-z][a-z0-9-]{4,29}$/', $project)) {
        $this->fail(__('Choose a Google Cloud project before checking its billing status.', ud_get_stateless_media()->domain));
      }
      $info = $this->api('GET', 'https://cloudbilling.googleapis.com/v1/projects/' . rawurlencode($project) . '/billingInfo');
      if (is_wp_error($info)) {
        $this->fail(__('Billing status could not be checked. Confirm the project billing in Google Cloud, then try again.', ud_get_stateless_media()->domain));
      }
      if (empty($info['billingEnabled'])) {
        $draft['billing_required'] = true;
        $this->state_set('draft', $draft, DAY_IN_SECONDS, $uid);
        $this->fail(__('Billing is not enabled for this project yet. Enable it in Google Cloud, then choose Check again. Your storage choices are saved.', ud_get_stateless_media()->domain));
      }
      unset($draft['billing_required']);
      $this->state_set('draft', $draft, DAY_IN_SECONDS, $uid);
      $this->state_delete('error', $uid);
      $this->done(array('sm_gc' => 'billing-ready'));
    }

    private function provisioning_reason($e) {
      if ($e->getCode() === 1001) return 'billing_missing';
      if ($e->getCode() === 1002) return 'public_access'; // a private bucket that was not, or could not be, made public
      if ($e->getCode() === 1003) return 'other';         // a bucket that lives in another project
      $message = strtolower($e->getMessage());
      if (strpos($message, 'billing') !== false) return 'billing_missing';
      if (strpos($message, 'already taken') !== false) return 'bucket_taken';
      if (strpos($message, 'bucket name') !== false) return 'bucket_name';
      if (strpos($message, 'public access') !== false) return 'public_access';
      if (strpos($message, 'forbidden') !== false || strpos($message, 'permission') !== false) return 'permission_denied';
      if (strpos($message, 'credential') !== false || strpos($message, 'key') !== false) return 'credentials';
      return 'google_api';
    }

    public function handle_client() {
      $this->guard();
      $this->clear_result();
      $id     = isset($_POST['client_id']) ? trim(sanitize_text_field($_POST['client_id'])) : '';
      $secret = isset($_POST['client_secret']) ? trim(sanitize_text_field($_POST['client_secret'])) : '';
      if (!$id || !$secret) {
        $this->fail(__('Both the client ID and client secret are required.', ud_get_stateless_media()->domain));
      }
      // A refresh token belongs to the client that issued it, so the client cannot change under a live connection.
      $connection = $this->get_connection();
      $current    = $this->read_option(self::CLIENT_OPTION);
      if ($connection && !$this->broker_conn($connection) && (!is_array($current) || $current['id'] !== $id || $current['secret'] !== $secret)) {
        $this->fail(__('This site is connected to Google through the current OAuth client. Disconnect first, then save the new client and sign in again.', ud_get_stateless_media()->domain));
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
      $this->remember_funnel($r['txn'], $r['verify_token']);
      $this->track_event('viewed');
      return $r['auth_url'];
    }

    /* ------------------------------------------------------------------ */
    /* Proof of possession for the broker                                   */
    /* GET /wp-json/wp-stateless/v1/connect-proof?txn=... returns the verify  */
    /* token the broker handed out when this site opened the transaction.   */
    /* The route is public on purpose: the broker fetches it from this       */
    /* site's own origin to confirm the site opened the attempt. It never    */
    /* accepts a token pushed by a client, so reading the token grants       */
    /* nothing; the attempt itself is completed by this site's administrator  */
    /* with the pairing code and the site verifier. Proofs live for twenty   */
    /* minutes and are dropped as soon as the attempt finishes or restarts.  */
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

    /** The proof is only needed while the broker may still verify the attempt. */
    private function proof_delete($txn) {
      $all = $this->proofs();
      if ($txn === '' || !isset($all[$txn])) return;
      unset($all[$txn]);
      update_option(self::PROOFS_OPTION, $all, false);
    }

    /** Drops the pending attempt so the next page view opens a fresh one. */
    public function handle_restart() {
      $this->guard();
      $pkce = $this->state_get('pkce');
      if (!empty($pkce['site_verifier']) && !empty($pkce['state'])) $this->proof_delete($pkce['state']);
      $this->state_delete('pkce');
      $this->clear_result();
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
      $this->clear_result();
      // A stale form or a second tab must not turn an established connection into an error.
      if ($this->get_connection()) {
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
        if (!empty($pkce['state'])) $this->proof_delete($pkce['state']);
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
      $network_id = $this->network_scope() ? get_current_network_id() : 0;
      $pending = $this->funnel_state($network_id, $uid);
      if ($pending) {
        $this->write_option(self::FUNNEL_OPTION, $pending, false);
        $this->schedule_funnel_cleanup($pending['created'], $network_id);
        $this->forget_funnel($network_id, $pending, true, $uid);
      }
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
      $this->clear_result();
      $connection = $this->get_connection();
      $domain = ud_get_stateless_media()->domain;
      // A disconnect and a setup worker must not overlap: disconnecting removes the key an unfinished setup saved,
      // which a worker in flight could be about to store as the site's configuration. So the disconnect takes the
      // scope the way a setup does and keeps it until this request ends; no setup can start meanwhile either.
      if (!$this->take_scope(2)) {
        $this->fail(__('A storage setup is still running. Wait for it to finish, then disconnect.', $domain));
      }
      $unreadable = $connection && $this->connection_unreadable($connection);
      if ($unreadable && empty($_POST['sm_gc_force'])) {
        // Nothing can be revoked from here, so do not report that it was.
        $this->fail(__('The stored Google credentials cannot be read, usually because the WordPress authentication salts changed or the sodium extension is gone, so this site cannot revoke its own access. Restore the previous salts and disconnect again, or revoke access under Third-party apps in your Google Account and then remove the connection without revoking.', $domain));
      }
      $stuck = array();
      if ($connection && !$unreadable) {
        // Before this site gives up its access, it removes the keys it still has on record at Google: the key of
        // a setup that stopped after Google issued it (disconnecting ends that setup), and keys whose removal
        // failed before. When one cannot be removed for a reason that passes, the connection is kept, so that
        // disconnecting again finishes the job instead of leaving a key that nothing can remove any more.
        $this->discard_setup_key(true);
        $steps = array();
        $stuck = $this->retry_pending_revocations($steps);
        foreach ($stuck as $error) {
          if (self::worth_retrying($error)) {
            $this->fail(sprintf(__('A service account key this site no longer uses could not be removed at Google yet (%s). The connection was kept so you can try again.', $domain), $error->get_error_message()));
          }
        }
        $problems = array();
        $refresh  = $this->decrypt($connection['refresh_token']);
        $this->assert_provision_lock();
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
        $this->assert_provision_lock();
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
      } else {
        $this->discard_setup_key(false); // without a readable connection only the stored copy can be removed
      }
      $this->remove_option(self::OPTION);
      $network_id = $this->network_scope() ? get_current_network_id() : 0;
      $this->forget_funnel($network_id);
      $this->forget_funnel($network_id, null, false, get_current_user_id());
      if ($stuck) {
        // Google refused to remove a key and repeating the request would not change that, so the disconnect went
        // ahead. The key stays on record for the next setup; meanwhile say which one it is.
        $names = array();
        foreach (array_keys($stuck) as $name) {
          list($email, $key_id) = array_pad(explode('/', $name, 2), 2, '');
          $names[] = sprintf('%1$s (%2$s)', $key_id, $email);
        }
        $this->fail(sprintf(__('Google account disconnected and access revoked. Google did not let this site remove a service account key it no longer uses (%1$s). Delete it in the Google Cloud console under IAM, Service Accounts: %2$s.', $domain), current($stuck)->get_error_message(), implode(', ', $names)));
      }
      $this->done(array('sm_gc' => $unreadable ? 'disconnected-unrevoked' : 'disconnected'));
    }

    /* ------------------------------------------------------------------ */
    /* Per-user attempt state                                               */
    /* Lives in user meta, not transients: a misconfigured object cache     */
    /* drops transients between requests (seen on a dev pod whose memcached */
    /* was unreachable), and the PKCE verifier must survive from the        */
    /* sign-in click to the return.                                         */
    /* ------------------------------------------------------------------ */

    /**
     * The outcome of the last action, for the screen: the steps of a setup and, when it failed, the explanation.
     * Showing it does not use it up. It stays until the next action starts or it expires, so neither a reload nor
     * a reload that crosses the end of a setup loses the explanation or a warning in the steps.
     */
    private function last_result($uid) {
      return array($this->state_get('log', $uid), $this->state_get('error', $uid));
    }

    /** Every action starts by dropping the previous outcome; it reports its own when it ends. */
    private function clear_result() {
      $this->state_delete('error');
      $this->state_delete('log');
    }

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
      $this->assert_provision_lock();
      $uid = $uid ? $uid : get_current_user_id();
      update_user_meta($uid, $this->state_key($key), array('v' => $value, 'exp' => time() + $ttl));
    }

    private function state_delete($key, $uid = null) {
      $this->assert_provision_lock();
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
      $this->assert_provision_lock();
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
      $this->assert_provision_lock();
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

    /** Waits until a project that Google is still creating becomes readable. */
    private function wait_project($project_id, $seconds = 90) {
      $deadline = time() + $seconds;
      do {
        $project = $this->api('GET', 'https://cloudresourcemanager.googleapis.com/v1/projects/' . rawurlencode($project_id));
        if (!is_wp_error($project)) return $project;
        if (!in_array($project->get_error_code(), array('google_403', 'google_404'), true)) return $project;
        sleep(3);
      } while (time() < $deadline);
      return new WP_Error('timeout', __('Google operation did not finish in time.', ud_get_stateless_media()->domain));
    }

    /* ------------------------------------------------------------------ */
    /* Provisioning: project, APIs, bucket, service account, role, key      */
    /* ------------------------------------------------------------------ */

    /**
     * One setup at a time. Nothing here queries the options table: the claim on a scope is WordPress core's own
     * lock, and the job record is an ordinary option.
     */

    /** Names the scope a setup runs in, this site or the network: its claim, its lock and its job record. */
    private function provision_job_key() {
      return 'sm_gc_job_' . ($this->network_scope() ? 'network_' . get_current_network_id() : 'site_' . get_current_blog_id());
    }

    /**
     * The job record: who is setting up storage in this scope, how far it got and how it ended. Always read from
     * the database, because a status check, a second submission and a worker all act on what another request
     * wrote a moment ago.
     */
    private function provision_job_read() {
      $key = $this->provision_job_key();
      $this->reread_option($key, $this->network_scope() ? get_current_network_id() : 0);
      $job = $this->read_option($key, null);
      return is_array($job) && isset($job['id'], $job['status']) ? $job : null;
    }

    /** Saves the job record. Not through write_option(), whose ownership check reads this very record. */
    private function provision_job_save($job) {
      $key = $this->provision_job_key();
      return $this->network_scope() ? update_network_option(get_current_network_id(), $key, $job) : update_option($key, $job, false);
    }

    private function provision_job_write($changes, $guard = true) {
      if (!$this->provision_job) return;
      if ($guard) $this->assert_provision_lock();
      $job = $this->provision_job_read();
      if (!is_array($job) || $job['id'] !== $this->provision_job) return;
      $this->provision_job_save(array_merge($job, $changes, array('updated' => time())));
    }

    /**
     * Claims or gives up a scope with the lock WordPress core uses for its own updaters, which holds on every
     * database. A network setup keeps its claim with the network's main site.
     */
    private function scope_claim($take) {
      if (!class_exists('WP_Upgrader')) require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
      $elsewhere = $this->network_scope() && get_current_blog_id() !== get_main_site_id();
      if ($elsewhere) switch_to_blog(get_main_site_id());
      $done = $take ? \WP_Upgrader::create_lock($this->provision_job_key(), self::PROVISION_STALE) : \WP_Upgrader::release_lock($this->provision_job_key());
      if ($elsewhere) restore_current_blog();
      return (bool) $done;
    }

    /** The name of the database lock for this scope. Such locks are shared by everything on the server. */
    private function provision_lock_name() {
      global $wpdb;
      return 'sm_gc_' . md5(DB_NAME . ':' . $wpdb->base_prefix . ':' . $this->provision_job_key());
    }

    /**
     * Takes the database's connection-owned lock for this scope, waiting up to $wait seconds. The database frees
     * it the moment its owner's connection ends, however the owner died, and that is the only way to know at once
     * that a setup worker is gone. WordPress has no API for it, hence these three direct calls; none touches a
     * table. Returns '1' when this connection owns the lock, '0' when another connection does, and null when the
     * database has no such locks (SQLite, some clusters). Then a claim only runs out with time.
     */
    private function take_provision_lock($wait = 0) {
      global $wpdb;
      // On a replicated database the lock must live on the primary, or two workers could each take it.
      if (method_exists($wpdb, 'send_reads_to_masters')) $wpdb->send_reads_to_masters();
      $quiet = $wpdb->suppress_errors(true); // an unsupported lock function is an answer here, not an error to print
      $got   = $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, %d)', $this->provision_lock_name(), $wait));
      $owned = (string) $got === '1' && $this->owns_provision_lock();
      $wpdb->suppress_errors($quiet);
      if ((string) $got === '0') return '0';
      return $owned ? '1' : null;
    }

    private function owns_provision_lock() {
      global $wpdb;
      return (string) $wpdb->get_var($wpdb->prepare('SELECT IS_USED_LOCK(%s) = CONNECTION_ID()', $this->provision_lock_name())) === '1';
    }

    private function release_provision_lock() {
      global $wpdb;
      $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $this->provision_lock_name()));
      $this->provision_lock = false;
    }

    /**
     * Takes this scope for the request, a setup or a disconnect. Returns false while a live owner exists.
     * Where the database has connection-owned locks the owner holds that lock as well, so a claim whose owner
     * died is recognised here at once and cleared, instead of blocking the scope until it runs out.
     */
    private function take_scope($wait = 0) {
      $lock = $this->take_provision_lock($wait);
      if ($lock === '0') return false;
      if ($lock === '1') {
        $this->provision_lock = true;
        $this->scope_claim(false); // nobody alive holds the lock, so a claim that is still there was left behind
      }
      if (!$this->scope_claim(true)) {
        if ($this->provision_lock) $this->release_provision_lock();
        return false;
      }
      $this->scope_taken = true;
      $this->scope_since = time();
      register_shutdown_function(array($this, 'release_scope'));
      // This request owns the scope now, so a job that is still recorded as running has no worker any more.
      $left = $this->provision_job_read();
      if (is_array($left) && $left['status'] === 'running') $this->provision_job_interrupt($left);
      return true;
    }

    /**
     * Whether this request still holds the scope it took. With the database lock that is known exactly. Without
     * it, a claim cannot have been taken over before it ran out, so a younger one is still this request's own.
     */
    private function holds_scope() {
      if (!$this->scope_taken) return false;
      return $this->provision_lock ? $this->owns_provision_lock() : time() - $this->scope_since < self::PROVISION_STALE - 10;
    }

    /**
     * Gives the scope back: when the request ends, and earlier when a setup finishes. A claim that may be
     * another request's by now is left alone.
     */
    public function release_scope() {
      if ($this->scope_taken) {
        $own = $this->holds_scope();
        $this->scope_taken = false;
        if ($own) $this->scope_claim(false);
      }
      if ($this->provision_lock) $this->release_provision_lock();
    }

    /**
     * Stops a request that no longer holds the scope it took, a setup worker or a disconnect. Checked around
     * everything such a request does at Google and around what it saves or removes.
     */
    private function assert_provision_lock() {
      if (!$this->scope_taken) return;
      $domain = ud_get_stateless_media()->domain;
      if ($this->provision_job && !$this->provision_lock) {
        // Without a database lock a claim runs out with time. A worker whose scope was taken over stops, and so
        // does one that is about to run out, which then hands the scope back itself.
        $job = $this->provision_job_read();
        if (!is_array($job) || $job['id'] !== $this->provision_job || $job['status'] !== 'running') {
          $this->provision_job = null;
          $this->scope_taken   = false;
          wp_die(__('This setup stopped responding and another one took over. Reload to see its progress.', $domain));
        }
        if (time() - (int) $job['started'] > self::PROVISION_STALE - 3 * MINUTE_IN_SECONDS) {
          wp_die(__('This setup ran out of time. Reload to resume the saved setup.', $domain));
        }
      }
      if ($this->holds_scope()) return;
      // wpdb can reconnect silently, and with the old connection the lock is gone. A former owner must never
      // continue, and neither the lock nor the claim is its own to give up any more.
      $worker = (bool) $this->provision_job;
      $this->provision_job  = null;
      $this->provision_lock = false;
      $this->scope_taken    = false;
      wp_die($worker
        ? __('The setup worker lost its database connection. Reload to resume the saved setup.', $domain)
        : __('The database connection was lost before this request finished. Reload and try again.', $domain));
    }

    /** Records that the worker of a job is gone, so the screen offers to resume. */
    private function provision_job_interrupt($job) {
      $job['status']  = 'interrupted';
      $job['message'] = __('The setup worker stopped. Your saved storage choices are ready to resume.', ud_get_stateless_media()->domain);
      $job['updated'] = time();
      $this->provision_job_save($job);
      return $job;
    }

    /**
     * Marks a job recorded as running as interrupted once its worker is gone. With a database lock that is known
     * at once: the lock can be taken only after its owner's connection ended, and holding it while the record
     * changes keeps a new setup from starting in between. Without one, only time can tell.
     */
    private function recover_provision_job($job) {
      if (!is_array($job) || $job['status'] !== 'running') return $job;
      $lock = !empty($job['worker_lock']) ? $this->take_provision_lock(0) : null;
      if ($lock === '0') return $job; // its worker is alive
      if ($lock === '1' || time() - (int) $job['started'] >= self::PROVISION_STALE) {
        $current = $this->provision_job_read();
        $job = is_array($current) && $current['id'] === $job['id'] && $current['status'] === 'running' ? $this->provision_job_interrupt($current) : $current;
      }
      if ($lock === '1') {
        $this->scope_claim(false); // its owner is gone for certain, so the claim it left goes too
        $this->release_provision_lock();
      }
      return $job;
    }

    private function begin_provision_job() {
      $domain = ud_get_stateless_media()->domain;
      $id = isset($_POST['sm_gc_job']) ? sanitize_text_field(wp_unslash($_POST['sm_gc_job'])) : wp_generate_uuid4();
      if (!preg_match('/^[a-zA-Z0-9-]{16,64}$/D', $id)) wp_die(__('Invalid setup request.', $domain));
      register_shutdown_function(array($this, 'release_provision_worker'));
      // A status poll can hold the database lock for a moment, so wait briefly instead of refusing a genuine submission.
      if (!$this->take_scope(3)) {
        wp_send_json_error(array('message' => __('Another setup worker is active. Checking its progress.', $domain)), 409);
      }
      // A repeated POST never repeats Google mutations, even after the first request finished.
      $previous = $this->provision_job_read();
      if (is_array($previous) && $previous['id'] === $id) {
        wp_send_json_error(array('message' => __('A storage setup is already recorded. Checking its progress.', $domain)), 409);
      }
      $this->provision_job_save(array('id' => $id, 'uid' => get_current_user_id(), 'status' => 'running', 'worker_lock' => (bool) $this->provision_lock, 'started' => time(), 'updated' => time(), 'message' => __('Preparing your storage.', $domain)));
      $this->provision_job = $id;
      // Reverse proxies may close the HTTP connection while Google is still working.
      ignore_user_abort(true);
    }

    public function release_provision_worker() {
      if ($this->provision_job) {
        // Unguarded: this runs at shutdown, and the write only ever touches this worker's own record.
        $this->provision_job_write(array('status' => 'interrupted', 'message' => __('Setup stopped before completion. Reload to resume the saved storage choices.', ud_get_stateless_media()->domain)), false);
        $this->provision_job = null;
      }
      $this->release_scope();
    }

    public function ajax_provision_status() {
      if (!current_user_can($this->capability())) wp_send_json_error(array('message' => __('Insufficient permissions.')), 403);
      check_ajax_referer($this->nonce_action(), 'nonce');
      // Only a job recorded as running needs a liveness check: an idle poll must not contend for the lock with a
      // new submission. Taking the lock proves no live worker owns this scope; age alone proves nothing.
      $job = $this->recover_provision_job($this->provision_job_read());
      if (!is_array($job)) wp_send_json_success(array('status' => 'none'));
      if ((int) $job['uid'] !== get_current_user_id()) {
        wp_send_json_success(array('status' => $job['status'] === 'running' ? 'busy' : 'none'));
      }
      $requested = isset($_GET['job']) ? sanitize_text_field(wp_unslash($_GET['job'])) : '';
      if ($requested !== $job['id'] && $job['status'] !== 'running') wp_send_json_success(array('status' => 'none'));
      // Never automatically resubmit a long-running or lost request: it may have created a project.
      if ($job['status'] === 'running' && time() - $job['started'] > 360) {
        $job['message'] = __('Google setup is taking longer than expected. Keep this page open; do not start another setup.', ud_get_stateless_media()->domain);
      }
      unset($job['uid']);
      wp_send_json_success($job);
    }

    public function handle_provision() {
      $this->guard();
      $this->begin_provision_job();
      $this->clear_result();
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
      $mode           = isset($post['mode']) ? sanitize_text_field($post['mode']) : 'cdn';
      if (!in_array($mode, array('backup', 'cdn', 'ephemeral', 'stateless'), true)) $mode = 'cdn';
      $locked_mode    = $this->locked_mode();
      if ($locked_mode) $mode = $locked_mode['mode']; // the form shows the locked mode; never trust a posted one
      // The administrator's choice for an existing bucket whose files are public or private together. The form
      // sends the name of the bucket the choice was made for, so it cannot apply to a bucket picked afterwards.
      $public_for     = isset($post['bucket_public']) && is_string($post['bucket_public']) ? strtolower(trim(sanitize_text_field($post['bucket_public']))) : '';
      $make_public    = $bucket_choice === 'existing' && $bucket !== '' && $public_for === $bucket;

      $draft = array('project' => $project_choice, 'new_project_name' => $new_project, 'billing_account' => $billing_choice, 'bucket_choice' => $bucket_choice, 'bucket_name' => $bucket, 'location' => $location, 'mode' => $mode, 'bucket_public' => $make_public ? $bucket : '');
      // A project this attempt created earlier carries its explicit billing choice until the link succeeds.
      $previous = $this->state_get('draft', $uid);
      if (is_array($previous) && !empty($previous['created_project']) && $previous['created_project'] === $project_choice) {
        $draft['created_project'] = $previous['created_project'];
        $draft['pending_billing'] = isset($previous['pending_billing']) ? (string) $previous['pending_billing'] : '';
        if (!empty($previous['project_create_name'])) $draft['project_create_name'] = $previous['project_create_name'];
        if (!empty($previous['project_create_op'])) $draft['project_create_op'] = $previous['project_create_op'];
      }
      if (is_array($previous) && isset($previous['bucket_name'], $previous['project']) && $previous['bucket_name'] === $bucket && $previous['project'] === $project_choice && !empty($previous['bucket_create_marker'])) {
        $draft['bucket_create_marker'] = $previous['bucket_create_marker'];
      }
      $this->state_set('draft', $draft, DAY_IN_SECONDS, $uid);
      if ($this->requires_stateless_mode() && $mode !== 'stateless') {
        $this->fail($locked_mode
          ? sprintf(__('This environment requires Stateless mode, but the storage mode is set to %1$s by %2$s. Change it there before continuing.', ud_get_stateless_media()->domain), $mode, $locked_mode['source'])
          : __('This environment requires Stateless mode. Select it before continuing.', ud_get_stateless_media()->domain));
      }
      $name_error = self::bucket_name_error($bucket);
      if ($name_error) $this->fail($name_error);
      if ($project_choice === '__new__' && !preg_match('/^[a-zA-Z0-9\x20\x21\x22\x27-]{4,30}$/D', $new_project)) {
        $this->fail(__('Use 4 to 30 characters for the project name: letters, numbers, spaces, hyphens, quotes or exclamation marks. Periods are not allowed.', ud_get_stateless_media()->domain));
      }
      if ($project_choice === '__new__' && !$billing_choice) $this->fail(__('Choose an open billing account before creating a project.', ud_get_stateless_media()->domain));
      if (!in_array($bucket_choice, array('new', 'existing'), true)) $this->fail(__('Choose whether to create or reuse a bucket.', ud_get_stateless_media()->domain));

      try {
        $this->provision_job_write(array('message' => __('Preparing your Google Cloud project and billing.', ud_get_stateless_media()->domain)));
        /* 1. Project */
        if ($project_choice === '__new__') {
          if (!$new_project) throw new Exception(__('Give the new project a name.', ud_get_stateless_media()->domain));
          $project_id = substr(preg_replace('/[^a-z0-9-]/', '-', strtolower($new_project)), 0, 20) . '-' . substr(md5(uniqid('', true)), 0, 6);
          $project_id = trim(preg_replace('/-+/', '-', $project_id), '-');
          if (!preg_match('/^[a-z]/', $project_id)) $project_id = 'wp-' . $project_id;
          $draft['project'] = $project_id;
          $draft['new_project_name'] = '';
          $draft['created_project'] = $project_id;
          $draft['pending_billing'] = $billing_choice;
          $draft['project_create_name'] = $new_project;
          $this->state_set('draft', $draft, DAY_IN_SECONDS, $uid);
          $op = $this->api('POST', 'https://cloudresourcemanager.googleapis.com/v1/projects', array(
            'projectId' => $project_id,
            'name'      => $new_project,
          ));
          $this->check($op, 'Create project');
          $log[] = sprintf('Creating project %s (%s)', $new_project, $project_id);
          // Save the accepted operation before polling: an interrupted or slow wait resumes it instead of creating another project.
          $draft['project_create_op'] = isset($op['name']) ? (string) $op['name'] : '';
          $this->state_set('draft', $draft, DAY_IN_SECONDS, $uid);
          $this->check($this->wait_operation('https://cloudresourcemanager.googleapis.com/v1/' . $op['name']), 'Create project');
          $log[] = 'Project created';

          if ($billing_choice) {
            $this->link_billing($project_id, $billing_choice);
            $log[] = 'Billing account linked';
            $draft['billing_account'] = '';
            $draft['pending_billing'] = '';
            $this->state_set('draft', $draft, DAY_IN_SECONDS, $uid);
          } else {
            $log[] = 'No billing account linked. Google requires one before a bucket can be created.';
          }
        } else {
          $project_id = $project_choice;
          if (!preg_match('/^[a-z][a-z0-9-]{4,29}$/', $project_id)) throw new Exception(__('Pick a project.', ud_get_stateless_media()->domain));
          if (!empty($draft['project_create_name'])) {
            // The project an earlier attempt asked Google to create. Google answers 403, not 404, for a project
            // that does not exist yet, so an unreadable one is either still being created or was never accepted.
            $project = $this->api('GET', 'https://cloudresourcemanager.googleapis.com/v1/projects/' . rawurlencode($project_id));
            if (is_wp_error($project) && in_array($project->get_error_code(), array('google_403', 'google_404'), true)) {
              if (!empty($draft['project_create_op'])) {
                $this->check($this->wait_operation('https://cloudresourcemanager.googleapis.com/v1/' . $draft['project_create_op']), 'Resume project creation');
              } else {
                $op = $this->api('POST', 'https://cloudresourcemanager.googleapis.com/v1/projects', array('projectId' => $project_id, 'name' => $draft['project_create_name']));
                if (is_wp_error($op) && $op->get_error_code() === 'google_409') {
                  // The interrupted request did reach Google: wait for that project instead of asking for another.
                  $this->check($this->wait_project($project_id), 'Resume project creation');
                } else {
                  $this->check($op, 'Resume project creation');
                  $draft['project_create_op'] = isset($op['name']) ? (string) $op['name'] : '';
                  $this->state_set('draft', $draft, DAY_IN_SECONDS, $uid);
                  $this->check($this->wait_operation('https://cloudresourcemanager.googleapis.com/v1/' . $op['name']), 'Resume project creation');
                }
              }
              $log[] = 'Project created';
            } else {
              $this->check($project, 'Find saved project');
            }
          }
          $log[] = sprintf('Using project %s', $project_id);
          // Only a project this attempt created, whose billing link failed, gets billing linked here, and only
          // to the account the administrator chose for it then. Billing of other existing projects is never changed:
          // the form's billing selector is hidden for them and its submitted value is not a choice.
          if (!empty($draft['created_project']) && $draft['created_project'] === $project_id && !empty($draft['pending_billing'])) {
            $info = $this->api('GET', 'https://cloudbilling.googleapis.com/v1/projects/' . rawurlencode($project_id) . '/billingInfo');
            $this->check($info, 'Check billing'); // a failed lookup stops here and keeps the pending link for the next retry
            if (empty($info['billingEnabled'])) {
              $this->link_billing($project_id, $draft['pending_billing']);
              $log[] = 'Billing account linked';
            }
            $draft['pending_billing'] = '';
            $this->state_set('draft', $draft, DAY_IN_SECONDS, $uid);
          }
        }

        $this->provision_job_write(array('message' => __('Checking billing and preparing bucket access.', ud_get_stateless_media()->domain)));
        if ($bucket_choice === 'new') {
          $billing_info = $this->api('GET', 'https://cloudbilling.googleapis.com/v1/projects/' . rawurlencode($project_id) . '/billingInfo');
          if (!is_wp_error($billing_info) && empty($billing_info['billingEnabled'])) {
            $draft['billing_required'] = true;
            $this->state_set('draft', $draft, DAY_IN_SECONDS, $uid);
            throw new Exception(__('Google Cloud billing is not enabled for this project. Enable billing, then choose Check again. Your storage choices are saved.', ud_get_stateless_media()->domain), 1001);
          }
          unset($draft['billing_required']);
          $this->state_set('draft', $draft, DAY_IN_SECONDS, $uid);
        }

        // Check a manually entered bucket before creating accounts, keys or changing permissions.
        $uniform     = false; // the bucket takes no per-object permissions, so uploads leave them alone
        $prevented   = false; // the bucket itself refuses public access
        $open_bucket = false; // a private uniform-access bucket the administrator chose to make public
        if ($bucket_choice === 'existing') {
          $existing_bucket = $this->api('GET', 'https://storage.googleapis.com/storage/v1/b/' . rawurlencode($bucket));
          $this->check($existing_bucket, 'Find bucket');
          $project_info = $this->api('GET', 'https://cloudresourcemanager.googleapis.com/v1/projects/' . rawurlencode($project_id));
          $this->check($project_info, 'Check bucket project');
          if (empty($existing_bucket['projectNumber']) || empty($project_info['projectNumber']) || (string) $existing_bucket['projectNumber'] !== (string) $project_info['projectNumber']) {
            throw new Exception(__('This bucket belongs to a different project. Select its project before continuing.', ud_get_stateless_media()->domain), 1003);
          }
          $uniform   = !empty($existing_bucket['iamConfiguration']['uniformBucketLevelAccess']['enabled']);
          $prevented = isset($existing_bucket['iamConfiguration']['publicAccessPrevention']) && $existing_bucket['iamConfiguration']['publicAccessPrevention'] === 'enforced';
          // CDN, Ephemeral and Stateless serve media through unsigned public URLs. On a fine-grained bucket the plugin
          // sets a public-read ACL per object. A uniform-access bucket has no per-object permissions: its files are
          // public or private together, through the bucket's own permissions, and that is the administrator's choice.
          if ($mode !== 'backup' && $uniform) {
            $policy = $this->api('GET', 'https://storage.googleapis.com/storage/v1/b/' . rawurlencode($bucket) . '/iam?optionsRequestedPolicyVersion=3');
            $this->check($policy, 'Read bucket permissions');
            if ($prevented || !self::bucket_is_public(isset($policy['bindings']) ? $policy['bindings'] : array())) {
              if (!$make_public) {
                $draft['public_required'] = true;
                $this->state_set('draft', $draft, DAY_IN_SECONDS, $uid);
                throw new Exception(__('This bucket is private, and its files can only be public or private together. To serve media from it, choose "Make the files in this bucket public" under the bucket name and connect again. To keep the bucket private, choose Backup mode.', ud_get_stateless_media()->domain), 1002);
              }
              $open_bucket = true;
            }
          } elseif ($mode !== 'backup') {
            if ($prevented) {
              throw new Exception(__('This bucket enforces public access prevention, so media URLs would be refused in CDN, Ephemeral or Stateless mode. Turn it off for this bucket, or choose Backup mode.', ud_get_stateless_media()->domain));
            }
            // With WP_STATELESS_SKIP_ACL_SET the plugin writes no object ACLs on fine-grained buckets either,
            // so readability must already come from the bucket: an allUsers IAM binding or a public default object ACL.
            if (defined('WP_STATELESS_SKIP_ACL_SET') && WP_STATELESS_SKIP_ACL_SET) {
              $policy = $this->api('GET', 'https://storage.googleapis.com/storage/v1/b/' . rawurlencode($bucket) . '/iam?optionsRequestedPolicyVersion=3');
              $this->check($policy, 'Read bucket permissions');
              $public = self::bucket_is_public(isset($policy['bindings']) ? $policy['bindings'] : array());
              if (!$public) {
                $default_acl = $this->api('GET', 'https://storage.googleapis.com/storage/v1/b/' . rawurlencode($bucket) . '/defaultObjectAcl');
                $public = !is_wp_error($default_acl) && self::default_acl_is_public(isset($default_acl['items']) ? $default_acl['items'] : array());
              }
              if (!$public) {
                throw new Exception(__('WP_STATELESS_SKIP_ACL_SET is enabled, so the plugin will not make uploads public itself, and this bucket is not publicly readable: media URLs would return 403 in CDN, Ephemeral or Stateless mode. Grant allUsers the Storage Object Viewer role on the bucket, give it a public default object ACL, or choose Backup mode.', ud_get_stateless_media()->domain));
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
          if (empty($draft['bucket_create_marker'])) $draft['bucket_create_marker'] = str_replace('-', '', wp_generate_uuid4());
          $this->state_set('draft', $draft, DAY_IN_SECONDS, $uid);
          $r = $this->api('POST', 'https://storage.googleapis.com/storage/v1/b?project=' . rawurlencode($project_id) . '&predefinedDefaultObjectAcl=publicRead', array(
            'name'             => $bucket,
            'location'         => $location,
            'storageClass'     => 'STANDARD',
            'labels'           => array('wp-stateless-setup' => $draft['bucket_create_marker']),
            'iamConfiguration' => array(
              'uniformBucketLevelAccess' => array('enabled' => false),
              'publicAccessPrevention'   => 'inherited',
            ),
          ));
          if (is_wp_error($r) && $r->get_error_code() === 'google_409') {
            $saved_bucket = $this->api('GET', 'https://storage.googleapis.com/storage/v1/b/' . rawurlencode($bucket));
            $saved_project = $this->api('GET', 'https://cloudresourcemanager.googleapis.com/v1/projects/' . rawurlencode($project_id));
            if (is_wp_error($saved_bucket) || is_wp_error($saved_project) || empty($saved_bucket['labels']['wp-stateless-setup'])
                || $saved_bucket['labels']['wp-stateless-setup'] !== $draft['bucket_create_marker']
                || empty($saved_project['projectNumber']) || (string) $saved_bucket['projectNumber'] !== (string) $saved_project['projectNumber']) {
              throw new Exception(sprintf(__('The bucket name %s is already taken. Bucket names are global across Google Cloud, pick another.', ud_get_stateless_media()->domain), $bucket));
            }
            $r = $saved_bucket;
          }
          if (is_wp_error($r) && $r->get_error_code() === 'google_412') {
            throw new Exception(self::public_access_prevention_message($project_id));
          }
          $this->check($r, 'Create bucket');
          $log[] = sprintf('Bucket %s created in %s, new objects public by default', $bucket, $location);
          // Checkpoint: the name is taken now, so a retry must reuse the bucket rather than create it again.
          $draft['bucket_choice'] = 'existing';
          $this->state_set('draft', $draft, DAY_IN_SECONDS, $uid);
          // Serving modes need anonymous reads. An organization policy can enforce public access prevention on
          // the new bucket even though it was created as "inherited", so prove a public read before continuing.
          if ($mode !== 'backup' && (!defined('WP_STATELESS_SKIP_ACL_SET') || !WP_STATELESS_SKIP_ACL_SET)) {
            $this->verify_public_read($bucket, $project_id);
            $log[] = 'Public read verified';
          }
        } else {
          $r = $this->api('GET', 'https://storage.googleapis.com/storage/v1/b/' . rawurlencode($bucket));
          $this->check($r, 'Find bucket');
          $log[] = sprintf('Using existing bucket %s', $bucket);
          if ($open_bucket) {
            $this->open_bucket($bucket, $prevented);
            $log[] = 'Bucket made public, as chosen: anyone with a link can read its files';
            $readable = $this->await_public_read($bucket);
            $log[] = $readable === true
              ? 'Public read verified'
              : 'Google did not pass the public read check yet (' . $readable . '); media links can be refused for a few minutes';
          }
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
        $configured_key = json_decode((string) $this->read_option('sm_key_json', ''), true);
        $previous_key   = $configured_key;
        $checkpoint = json_decode($this->decrypt((string) $this->read_option(self::SETUP_KEY_OPTION, '')), true);
        if (is_array($checkpoint) && isset($checkpoint['bucket'], $checkpoint['sa_email'], $checkpoint['key']) && $checkpoint['bucket'] === $bucket && $checkpoint['sa_email'] === $sa_email) {
          // An interrupted attempt for this same storage already holds a key: reuse it instead of issuing another.
          $key_json = $checkpoint['key'];
          $previous_key = $this->resumed_previous_key($checkpoint, $configured_key);
          // That can be another key than the one on record, when the configuration was changed by hand after the
          // attempt stopped. It goes on record first, so that it is still known should this attempt stop as well.
          $key_id   = function ($key) { return is_array($key) && !empty($key['private_key_id']) ? $key['private_key_id'] : ''; };
          $recorded = isset($checkpoint['previous']) ? $checkpoint['previous'] : null;
          if ($key_id($recorded) !== $key_id($previous_key)) {
            $checkpoint['previous'] = $previous_key;
            $this->write_option(self::SETUP_KEY_OPTION, $this->encrypt(wp_json_encode($checkpoint)), false);
          }
        } else {
          // A key saved by an attempt for different storage is abandoned now. It must not stay active, and it must
          // not stay reusable: once queued for revocation, a later reuse would end with the site's key deleted.
          // If that attempt had already replaced the site's key, the key it replaced is revoked here as well.
          if (is_array($checkpoint) && !empty($checkpoint['key'])) {
            foreach ($this->setup_leftover_keys($checkpoint, $configured_key) as $leftover) {
              $removed = $this->revoke_key($leftover['email'], $leftover['id']); // a failure is queued and retried
              if ($leftover['kind'] === 'unused') {
                $log[] = $removed === true ? 'Unused key from an earlier attempt removed' : 'Unused key from an earlier attempt could not be removed yet; it will be retried';
              } else {
                $log[] = $removed === true ? 'Key replaced by an earlier attempt revoked' : 'Key replaced by an earlier attempt could not be revoked yet; it will be retried';
              }
            }
            $this->remove_option(self::SETUP_KEY_OPTION);
          }
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
          $this->write_option(self::SETUP_KEY_OPTION, $this->encrypt(wp_json_encode(array('bucket' => $bucket, 'sa_email' => $sa_email, 'key' => $key_json, 'previous' => $previous_key))), false);
        }
        $log[] = 'Key issued to this site';

        /* 7. Verify with the new key itself before anything is replaced (IAM can lag a few seconds). A site that
         * already works keeps its configuration when the check fails; a first setup is saved with a warning
         * because there is nothing to lose and the check usually passes a moment later. */
        $verified      = $this->verify_key($key_json, $bucket, 10);
        $new_key       = json_decode($key_json, true);
        // An interrupted attempt may already have saved this key as the site's configuration. The site runs on it
        // then, so nothing is revoked while it cannot be verified, and the saved setup stays resumable.
        $committed     = is_array($configured_key) && !empty($configured_key['private_key_id']) && $configured_key['private_key_id'] === $new_key['private_key_id'];
        if ($verified !== true && $committed) {
          throw new Exception(sprintf(__('The saved credentials could not reach the bucket (%s). Nothing was changed. IAM changes can take a minute to apply; try again shortly.', ud_get_stateless_media()->domain), $verified));
        }
        $reconfiguring = is_array($previous_key) && !empty($previous_key['private_key_id']) && (string) $this->read_option('sm_bucket', '') !== '';
        if ($verified !== true && $reconfiguring) {
          $removed = $this->revoke_key($sa_email, $new_key['private_key_id']);
          $this->remove_option(self::SETUP_KEY_OPTION);
          $log[] = $removed === true ? 'New key removed again' : 'New key could not be removed yet; it will be retried on the next setup';
          throw new Exception(sprintf(__('The new credentials could not reach the bucket (%s), so the current configuration was left unchanged. IAM changes can take a minute to apply; try again shortly.', ud_get_stateless_media()->domain), $verified));
        }

        /* 8. Save plugin settings */
        $this->assert_provision_lock();
        $this->write_option('sm_bucket', $bucket);
        $this->write_option('sm_key_json', $key_json);
        $this->write_option('sm_mode', $mode);
        // Uploads to a uniform-access bucket must leave per-object permissions alone; see Utility::skip_acl_set().
        if ($uniform) {
          $this->write_option(self::UNIFORM_OPTION, $bucket);
        } else {
          $this->remove_option(self::UNIFORM_OPTION);
        }
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
        $this->remove_option(self::SETUP_KEY_OPTION);

        $log[] = $verified === true
          ? 'Verified: the site reached the bucket with its own credentials'
          : 'Saved, but the first connection check did not pass yet: ' . $verified . ' (IAM changes can take a minute to apply; reload Stateless Settings in a moment)';

        $this->state_set('log', $log, 10 * MINUTE_IN_SECONDS, $uid);
        $this->state_delete('draft', $uid);
        $this->track_event('provisioned');
        $this->done(array('sm_gc' => 'provisioned'));
      } catch (Exception $e) {
        $log[] = 'Stopped: ' . $e->getMessage();
        $this->state_set('log', $log, 10 * MINUTE_IN_SECONDS, $uid);
        $this->track_event('provision_failed', $this->provisioning_reason($e));
        $this->fail($e->getMessage(), true);
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

    private function forget_pending_revocation($name) {
      $pending = $this->read_option(self::PENDING_KEYS_OPTION, array());
      if (!is_array($pending) || !isset($pending[$name])) return;
      unset($pending[$name]);
      $pending ? $this->write_option(self::PENDING_KEYS_OPTION, $pending, false) : $this->remove_option(self::PENDING_KEYS_OPTION);
    }

    /**
     * The keys a stored setup left behind that the site does not run on. Either the key that setup issued, when
     * it never became the site's configuration. Or, when it did and the setup stopped before its last step, the
     * key the site held until then; as in a completed setup, only a key of the same service account, so a key
     * someone configured by hand is never touched. The key the site is configured with is never returned.
     */
    private function setup_leftover_keys($checkpoint, $current) {
      $issued = is_array($checkpoint) && !empty($checkpoint['key']) ? json_decode($checkpoint['key'], true) : null;
      if (!is_array($issued) || empty($issued['private_key_id']) || empty($issued['client_email'])) return array();
      $current_id = is_array($current) && !empty($current['private_key_id']) ? $current['private_key_id'] : '';
      if ($issued['private_key_id'] !== $current_id) {
        return array(array('email' => $issued['client_email'], 'id' => $issued['private_key_id'], 'kind' => 'unused'));
      }
      $previous = isset($checkpoint['previous']) ? $checkpoint['previous'] : null;
      if (is_array($previous) && !empty($previous['private_key_id']) && !empty($previous['client_email'])
          && $previous['client_email'] === $issued['client_email'] && $previous['private_key_id'] !== $current_id) {
        return array(array('email' => $previous['client_email'], 'id' => $previous['private_key_id'], 'kind' => 'replaced'));
      }
      return array();
    }

    /**
     * The key a resumed setup replaces: the one the site is configured with now, which may have changed since
     * the setup stopped. Only when the saved key already is the site's configuration did the stopped setup
     * replace an older key, and that one is on record. So a configuration entered by hand in between is
     * protected like any working configuration when the saved key cannot be verified.
     */
    private function resumed_previous_key($checkpoint, $configured_key) {
      $issued = is_array($checkpoint) && !empty($checkpoint['key']) ? json_decode($checkpoint['key'], true) : null;
      $live   = is_array($issued) && !empty($issued['private_key_id']) && is_array($configured_key)
                && !empty($configured_key['private_key_id']) && $issued['private_key_id'] === $configured_key['private_key_id'];
      if (!$live) return $configured_key;
      return isset($checkpoint['previous']) ? $checkpoint['previous'] : null;
    }

    /** True when repeating a failed Google request can succeed: the network or Google failed, not the request. */
    private static function worth_retrying($error) {
      $code = (string) $error->get_error_code();
      if (strpos($code, 'google_') !== 0) return $code === 'http_request_failed';
      $status = (int) substr($code, 7);
      return $status === 429 || $status >= 500;
    }

    /**
     * Ends a setup that stopped after Google issued its key: with $revoke the keys it left at Google go on the
     * list of keys to remove, then the stored copy is dropped. So such a key is never both awaiting removal and
     * still reusable, and none stays active without a record. The caller removes what is on the list.
     */
    private function discard_setup_key($revoke) {
      $stored = (string) $this->read_option(self::SETUP_KEY_OPTION, '');
      if ($stored === '') return;
      if ($revoke) {
        $current = json_decode((string) $this->read_option('sm_key_json', ''), true);
        $leftovers = $this->setup_leftover_keys(json_decode($this->decrypt($stored), true), $current);
        if ($leftovers) {
          $pending = $this->read_option(self::PENDING_KEYS_OPTION, array());
          if (!is_array($pending)) $pending = array();
          foreach ($leftovers as $leftover) $pending[$leftover['email'] . '/' . $leftover['id']] = time();
          $this->write_option(self::PENDING_KEYS_OPTION, $pending, false);
        }
      }
      $this->remove_option(self::SETUP_KEY_OPTION);
    }

    /** Tries once to remove every key on the list. Returns the errors of those still there, by key name. */
    private function retry_pending_revocations(&$log) {
      $failed  = array();
      $pending = $this->read_option(self::PENDING_KEYS_OPTION, array());
      if (!is_array($pending) || !$pending) return $failed;
      // Never revoke the key the site is configured with, however it got onto this list.
      $current = json_decode((string) $this->read_option('sm_key_json', ''), true);
      $in_use  = is_array($current) && !empty($current['client_email']) && !empty($current['private_key_id']) ? $current['client_email'] . '/' . $current['private_key_id'] : '';
      foreach (array_keys($pending) as $name) {
        if ($name === $in_use) {
          $this->forget_pending_revocation($name);
          continue;
        }
        list($email, $key_id) = array_pad(explode('/', $name, 2), 2, '');
        if ($email === '' || $key_id === '') continue;
        $r = $this->revoke_key($email, $key_id);
        if ($r !== true) $failed[$name] = $r;
        $log[] = $r === true ? 'Earlier key ' . $key_id . ' revoked' : 'Earlier key ' . $key_id . ' still pending revocation: ' . $r->get_error_message();
      }
      return $failed;
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

    private function link_billing($project_id, $billing_account) {
      $r = $this->api('PUT', 'https://cloudbilling.googleapis.com/v1/projects/' . rawurlencode($project_id) . '/billingInfo', array(
        'billingAccountName' => $billing_account,
      ));
      $this->check($r, 'Link billing account');
    }

    private static function public_access_prevention_message($project_id) {
      return sprintf(__('Public access prevention is enforced for project %s (an organization policy), so files in a new bucket there cannot be served to visitors. Pick a project where the constraint is not enforced, ask your Google Cloud administrator to exempt this project, or use Backup mode.', ud_get_stateless_media()->domain), $project_id);
    }

    /**
     * Uploads a tiny public object to the new bucket, reads it back without credentials and deletes it.
     * Fails with an actionable message when visitors would get 403s from this bucket.
     */
    private function verify_public_read($bucket, $project_id) {
      $name = '.wp-stateless-public-read-check-' . substr(md5(uniqid('', true)), 0, 8);
      $up   = $this->api('POST', 'https://storage.googleapis.com/upload/storage/v1/b/' . rawurlencode($bucket) . '/o?uploadType=media&predefinedAcl=publicRead&name=' . rawurlencode($name), array('wp-stateless' => 'public read check'));
      if (is_wp_error($up) && $up->get_error_code() === 'google_412') {
        throw new Exception(self::public_access_prevention_message($project_id));
      }
      $this->check($up, 'Verify public read');
      $probe = wp_remote_get('https://storage.googleapis.com/' . rawurlencode($bucket) . '/' . rawurlencode($name), array('timeout' => 20, 'headers' => array('Cache-Control' => 'no-cache')));
      $code  = is_wp_error($probe) ? 0 : (int) wp_remote_retrieve_response_code($probe);
      $this->api('DELETE', 'https://storage.googleapis.com/storage/v1/b/' . rawurlencode($bucket) . '/o/' . rawurlencode($name));
      if ($code === 403) {
        throw new Exception(self::public_access_prevention_message($project_id));
      }
      if ($code !== 200) {
        throw new Exception(sprintf(__('Verify public read: a test file in bucket %1$s could not be read anonymously (%2$s). Check the bucket\'s public access settings and retry.', ud_get_stateless_media()->domain), $bucket, $code ? 'HTTP ' . $code : $probe->get_error_message()));
      }
    }

    /**
     * Makes a uniform-access bucket readable by anyone, at the administrator's request: lifts the bucket's own
     * public access prevention when it is on, then lets allUsers read its objects. Other permissions are kept.
     * An organization policy can forbid public buckets altogether, which Google answers with 412.
     */
    private function open_bucket($bucket, $prevented) {
      $url = 'https://storage.googleapis.com/storage/v1/b/' . rawurlencode($bucket);
      if ($prevented) {
        $r = $this->api('PATCH', $url . '?fields=iamConfiguration', array('iamConfiguration' => array('publicAccessPrevention' => 'inherited')));
        $this->check($r, 'Allow public access');
      }
      $attempt = 0;
      do {
        $attempt++;
        $policy = $this->api('GET', $url . '/iam?optionsRequestedPolicyVersion=3');
        $this->check($policy, 'Read bucket permissions');
        $r = $this->api('PUT', $url . '/iam', array(
          'version'  => isset($policy['version']) ? $policy['version'] : 1,
          'bindings' => self::public_bindings(isset($policy['bindings']) ? $policy['bindings'] : array()),
          'etag'     => isset($policy['etag']) ? $policy['etag'] : null,
        ));
        // 409 and 412 can also mean the permissions changed between the read and the write: read them again.
        if (is_wp_error($r) && $attempt < 3 && in_array($r->get_error_code(), array('google_409', 'google_412'), true)) {
          sleep(2);
          continue;
        }
        if (is_wp_error($r) && $r->get_error_code() === 'google_412') {
          throw new Exception(sprintf(__('Google Cloud refused to make this bucket public (%s). An organization policy usually enforces public access prevention or limits sharing to your organization. Ask your Google Cloud administrator for an exemption, or choose Backup mode to keep the bucket private.', ud_get_stateless_media()->domain), $r->get_error_message()), 1002);
        }
        $this->check($r, 'Make bucket public');
        break;
      } while (true);
    }

    /**
     * Whether a file in a bucket that was just made public can be read without credentials: true, or what the
     * last attempt answered. Google applies a permission change within seconds to a few minutes, so this waits
     * for it and reports, rather than failing a setup whose permissions are already in place.
     */
    private function await_public_read($bucket, $attempts = 10) {
      $name = '.wp-stateless-public-read-check-' . substr(md5(uniqid('', true)), 0, 8);
      $up   = $this->api('POST', 'https://storage.googleapis.com/upload/storage/v1/b/' . rawurlencode($bucket) . '/o?uploadType=media&name=' . rawurlencode($name), array('wp-stateless' => 'public read check'));
      if (is_wp_error($up)) return $up->get_error_message();
      $last = '';
      for ($i = 0; $i < $attempts; $i++) {
        if ($i) sleep(4);
        $probe = wp_remote_get('https://storage.googleapis.com/' . rawurlencode($bucket) . '/' . rawurlencode($name), array('timeout' => 20, 'headers' => array('Cache-Control' => 'no-cache')));
        $code  = is_wp_error($probe) ? 0 : (int) wp_remote_retrieve_response_code($probe);
        if ($code === 200) {
          $last = true;
          break;
        }
        $last = $code ? 'HTTP ' . $code : $probe->get_error_message();
      }
      $this->api('DELETE', 'https://storage.googleapis.com/storage/v1/b/' . rawurlencode($bucket) . '/o/' . rawurlencode($name));
      return $last;
    }

    /* ------------------------------------------------------------------ */
    /* Helpers                                                              */
    /* ------------------------------------------------------------------ */

    private function guard() {
      if (!current_user_can($this->capability())) wp_die(__('Insufficient permissions.'));
      check_admin_referer($this->nonce_action());
    }

    private function done($args) {
      // What stays on the screen belongs to the action that just ended. One that ends well leaves no earlier
      // failure behind, such as a disconnect that was refused in another tab while this setup was running, and
      // only a finished setup leaves its steps.
      $outcome = isset($args['sm_gc']) ? $args['sm_gc'] : '';
      if ($outcome !== 'error') {
        $this->state_delete('error');
        if ($outcome !== 'provisioned') $this->state_delete('log');
      }
      if ($this->provision_job) {
        $this->provision_job_write(array('status' => isset($args['sm_gc']) && $args['sm_gc'] === 'provisioned' ? 'complete' : 'failed', 'url' => $this->page_url($args)));
        $this->provision_job = null;
        $this->release_scope();
        if (isset($_POST['sm_gc_job'])) wp_send_json_success(array('finished' => true));
      }
      wp_safe_redirect($this->page_url($args));
      exit;
    }

    /** Ends an action with an explanation. $with_steps keeps the steps a stopped setup saved just before. */
    private function fail($message, $with_steps = false) {
      if (!$with_steps) $this->state_delete('log'); // steps of another action must not appear under this explanation
      $this->state_set('error', wp_strip_all_tags((string) $message), 10 * MINUTE_IN_SECONDS);
      $this->done(array('sm_gc' => 'error'));
    }

    private static function b64url($bin) {
      return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    private function crypto_key() {
      return hash('sha256', wp_salt('auth') . '|' . wp_salt('secure_auth') . '|sm_google_connect', true);
    }

    /**
     * Tokens at rest: libsodium secretbox (v1), else OpenSSL AES-256-GCM (v2). A host without either
     * extension is not supported by the plugin's requirements, so nothing is ever stored in clear.
     */
    private function encrypt($plain) {
      if (function_exists('sodium_crypto_secretbox')) {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return 'v1:' . base64_encode($nonce . sodium_crypto_secretbox($plain, $nonce, $this->crypto_key()));
      }
      if (function_exists('openssl_encrypt') && in_array('aes-256-gcm', openssl_get_cipher_methods(), true)) {
        $iv  = random_bytes(12);
        $tag = '';
        $box = openssl_encrypt($plain, 'aes-256-gcm', $this->crypto_key(), OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        if ($box !== false) return 'v2:' . base64_encode($iv . $tag . $box);
      }
      throw new Exception(__('Neither the sodium nor the OpenSSL PHP extension is available, so Google credentials cannot be stored securely.', ud_get_stateless_media()->domain));
    }

    private function decrypt($stored) {
      $stored = (string) $stored;
      if (strpos($stored, 'v1:') === 0 && function_exists('sodium_crypto_secretbox_open')) {
        $raw   = base64_decode(substr($stored, 3));
        $nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $box   = substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plain = sodium_crypto_secretbox_open($box, $nonce, $this->crypto_key());
        return $plain === false ? '' : $plain;
      }
      if (strpos($stored, 'v2:') === 0 && function_exists('openssl_decrypt')) {
        $raw   = base64_decode(substr($stored, 3));
        $plain = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', $this->crypto_key(), OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
        return $plain === false ? '' : $plain;
      }
      return '';
    }
  }
}
