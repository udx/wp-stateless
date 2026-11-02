<?php
/**
 * Google Connect
 *
 * Connects the site to the administrator's own Google Cloud account using the
 * OAuth 2.0 flow for installed applications (public client + PKCE + loopback
 * redirect), the same flow the gcloud CLI uses on a headless machine. Once
 * connected the plugin provisions everything in the user's account from
 * wp-admin: project, bucket, service account, bucket-scoped role and key.
 *
 * Nothing is hosted by UDX. The only UDX artifact is the OAuth client identity.
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
    const REDIRECT_URI    = 'https://stateless.ci/connect/google/callback';
    const LOOPBACK_URI    = 'http://127.0.0.1:19190/';
    const BROKER          = 'https://stateless.ci';
    const SCOPES          = 'openid email https://www.googleapis.com/auth/cloud-platform';
    const AUTH_ENDPOINT   = 'https://accounts.google.com/o/oauth2/v2/auth';
    const TOKEN_ENDPOINT  = 'https://oauth2.googleapis.com/token';
    const REVOKE_ENDPOINT = 'https://oauth2.googleapis.com/revoke';
    const NONCE           = 'sm_google_connect';

    /** @var string|null */
    private $page_hook = null;

    protected function __construct() {
      add_action('admin_menu', array($this, 'admin_menu'), 20);
      add_action('admin_notices', array($this, 'admin_notice'));
      add_action('admin_post_sm_gc_client', array($this, 'handle_client'));
      add_action('admin_post_sm_gc_finish', array($this, 'handle_finish'));
      add_action('admin_post_sm_gc_provision', array($this, 'handle_provision'));
      add_action('admin_post_sm_gc_disconnect', array($this, 'handle_disconnect'));
    }

    /* ------------------------------------------------------------------ */
    /* Admin UI                                                             */
    /* ------------------------------------------------------------------ */

    public function admin_menu() {
      $this->page_hook = add_media_page(
        __('Stateless Connect', ud_get_stateless_media()->domain),
        __('Stateless Connect', ud_get_stateless_media()->domain),
        'manage_options',
        self::PAGE,
        array($this, 'render_page')
      );
      // Runs before any output, so a return from the broker can finish and redirect.
      add_action('load-' . $this->page_hook, array($this, 'maybe_finish_from_return'));
    }

    public function page_url($args = array()) {
      return add_query_arg(array_merge(array('page' => self::PAGE), $args), admin_url('upload.php'));
    }

    public function admin_notice() {
      if (!current_user_can('manage_options')) return;
      $screen = function_exists('get_current_screen') ? get_current_screen() : null;
      if ($screen && strpos($screen->id, self::PAGE) !== false) return;
      if (ud_get_stateless_media()->get('sm.key_json')) return;
      // Only where an admin is dealing with media or plugins, not on every screen.
      $relevant = $screen && (in_array($screen->base, array('upload', 'media', 'plugins', 'dashboard'), true) || strpos($screen->id, 'stateless') !== false);
      if (!$relevant) return;
      printf(
        '<div class="notice notice-info"><p><strong>%s</strong> %s <a class="button button-primary" style="margin-left:8px" href="%s">%s</a></p></div>',
        esc_html__('WP-Stateless is not connected to Google Cloud yet.', ud_get_stateless_media()->domain),
        esc_html__('Sign in with your Google account and the plugin will create the bucket for you.', ud_get_stateless_media()->domain),
        esc_url($this->page_url()),
        esc_html__('Connect Google Cloud', ud_get_stateless_media()->domain)
      );
    }

    public function render_page() {
      if (!current_user_can('manage_options')) wp_die(__('Insufficient permissions.'));

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
        $res = $this->api('GET', 'https://cloudbilling.googleapis.com/v1/billingAccounts');
        if (!is_wp_error($res) && !empty($res['billingAccounts'])) {
          foreach ($res['billingAccounts'] as $b) {
            if (!empty($b['open'])) $billing[] = $b;
          }
        }
      }

      $host = parse_url(home_url(), PHP_URL_HOST);
      $suggested_bucket = 'wp-stateless-' . preg_replace('/[^a-z0-9-]/', '-', strtolower($host)) . '-' . substr(md5(wp_salt() . $host), 0, 4);
      $suggested_bucket = substr(trim($suggested_bucket, '-'), 0, 63);

      $settings = (object) ud_get_stateless_media()->get('sm');

      include ud_get_stateless_media()->path('static/views/connect.php', 'dir');
    }

    /* ------------------------------------------------------------------ */
    /* Client configuration (installed-app OAuth client, public by design)  */
    /* ------------------------------------------------------------------ */

    public function get_client() {
      if (defined('WP_STATELESS_GOOGLE_CLIENT_ID') && defined('WP_STATELESS_GOOGLE_CLIENT_SECRET')) {
        return array(
          'id'           => WP_STATELESS_GOOGLE_CLIENT_ID,
          'secret'       => WP_STATELESS_GOOGLE_CLIENT_SECRET,
          'redirect_uri' => defined('WP_STATELESS_GOOGLE_REDIRECT_URI') ? WP_STATELESS_GOOGLE_REDIRECT_URI : self::REDIRECT_URI,
          'source'       => 'constant',
        );
      }
      $opt = get_option(self::CLIENT_OPTION);
      if (!empty($opt['id']) && !empty($opt['secret'])) {
        return array(
          'id'           => $opt['id'],
          'secret'       => $opt['secret'],
          // Clients saved before the hosted page existed were Desktop clients on the loopback address.
          'redirect_uri' => !empty($opt['redirect_uri']) ? $opt['redirect_uri'] : self::LOOPBACK_URI,
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
      $uri    = isset($_POST['redirect_uri']) ? trim(esc_url_raw(wp_unslash($_POST['redirect_uri']))) : '';
      if (!$id || !$secret) {
        $this->fail(__('Both the client ID and client secret are required.', ud_get_stateless_media()->domain));
      }
      if (!$uri) $uri = self::REDIRECT_URI;
      // Google only accepts https redirect URIs on web clients and loopback http on desktop clients.
      if (strpos($uri, 'https://') !== 0 && self::is_hosted_redirect($uri)) {
        $this->fail(__('The redirect URI must start with https:// or be the loopback address http://127.0.0.1:19190/.', ud_get_stateless_media()->domain));
      }
      update_option(self::CLIENT_OPTION, array('id' => $id, 'secret' => $secret, 'redirect_uri' => $uri), false);
      $this->done(array('sm_gc' => 'client-saved'));
    }

    /* ------------------------------------------------------------------ */
    /* OAuth: authorization URL, code exchange, refresh, revoke             */
    /* ------------------------------------------------------------------ */

    private function build_auth_url($client, $uid) {
      $pkce = $this->state_get('pkce', $uid);
      if (empty($pkce['verifier']) || empty($pkce['state'])) {
        $pkce = array(
          'verifier' => rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '='),
          'state'    => rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '='),
          'rt'       => rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '='),
        );
        $this->state_set('pkce', $pkce, 20 * MINUTE_IN_SECONDS, $uid);
      }
      $challenge = rtrim(strtr(base64_encode(hash('sha256', $pkce['verifier'], true)), '+/', '-_'), '=');

      // Hosted flow: tell the broker where to send the browser afterwards so the admin never copies a code.
      // The return address carries a token only this attempt knows, so a crafted link cannot trigger the
      // finish step. Registered once per attempt and refreshed before the broker's record expires.
      if ($client['source'] === 'broker' && !empty($pkce['rt']) && strpos(admin_url(), 'https://') === 0) {
        if (empty($pkce['registered']) || $pkce['registered'] < time() - 15 * MINUTE_IN_SECONDS) {
          $r = $this->broker_request('/connect/google/start', array(
            'state'      => $pkce['state'],
            'return_url' => admin_url('upload.php?page=' . self::PAGE . '&sm_gc_rt=' . rawurlencode($pkce['rt'])),
          ));
          if (!is_wp_error($r) && !empty($r['ok'])) {
            $pkce['registered'] = time();
            $this->state_set('pkce', $pkce, 20 * MINUTE_IN_SECONDS, $uid);
          }
        }
      }

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
      if (empty($_GET['sm_gc_pair']) || empty($_GET['sm_gc_rt']) || !current_user_can('manage_options') || $this->get_connection()) return;
      $pair = sanitize_text_field(wp_unslash($_GET['sm_gc_pair']));
      $rt   = (string) wp_unslash($_GET['sm_gc_rt']);
      $pkce = $this->state_get('pkce');
      if (empty($pkce['rt']) || !hash_equals($pkce['rt'], $rt)) return; // not this attempt's return; show the page as usual
      $this->finish($pair);
    }

    private function finish($pasted) {
      $client = $this->get_client();
      if (!$client) $this->fail(__('OAuth client is not configured.', ud_get_stateless_media()->domain));

      $uid  = get_current_user_id();
      $pkce = $this->state_get('pkce', $uid);
      if (empty($pkce['verifier'])) {
        $this->fail(__('The sign-in attempt expired. Start again.', ud_get_stateless_media()->domain));
      }

      $params = $this->parse_redirect($pasted);

      if (!empty($params['pair'])) {
        if (!self::is_hosted_redirect($client['redirect_uri'])) {
          $this->fail(__('Pairing codes come from the hosted sign-in page. With the loopback address, paste the full address Google sent you to instead.', ud_get_stateless_media()->domain));
        }
        $r = $this->broker_request('/connect/google/redeem', array('pair' => $params['pair'], 'state' => $pkce['state']));
        if (is_wp_error($r)) $this->fail($r->get_error_message());
        if (empty($r['code'])) {
          $this->fail(__('That code was not recognized or has expired. Click Sign in with Google again and type the new code.', ud_get_stateless_media()->domain));
        }
        $params = array('code' => $r['code'], 'state' => $pkce['state']);
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

      $email = '';
      $info  = wp_remote_get('https://openidconnect.googleapis.com/v1/userinfo', array(
        'headers' => array('Authorization' => 'Bearer ' . $token['access_token']),
        'timeout' => 20,
      ));
      if (!is_wp_error($info)) {
        $body  = json_decode(wp_remote_retrieve_body($info), true);
        $email = isset($body['email']) ? $body['email'] : '';
      }

      $connection = array(
        'email'         => $email,
        'client_id'     => $client['id'],
        'refresh_token' => $this->encrypt($token['refresh_token']),
        'access_token'  => $this->encrypt($token['access_token']),
        'expires'       => time() + intval($token['expires_in']) - 60,
        'scope'         => isset($token['scope']) ? $token['scope'] : '',
        'connected_at'  => time(),
        'connected_by'  => $uid,
      );
      update_option(self::OPTION, $connection, false);
      $this->state_delete('pkce', $uid);
      $this->done(array('sm_gc' => 'connected'));
    }

    public function handle_disconnect() {
      $this->guard();
      $connection = $this->get_connection();
      if ($connection) {
        $refresh = $this->decrypt($connection['refresh_token']);
        if ($refresh) {
          wp_remote_post(self::REVOKE_ENDPOINT, array('body' => array('token' => $refresh), 'timeout' => 20));
        }
      }
      delete_option(self::OPTION);
      $this->done(array('sm_gc' => 'disconnected'));
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
      $rec = get_user_meta($uid, 'sm_gc_' . $key, true);
      if (!is_array($rec) || !isset($rec['exp'], $rec['v'])) return false;
      if ($rec['exp'] < time()) {
        delete_user_meta($uid, 'sm_gc_' . $key);
        return false;
      }
      return $rec['v'];
    }

    private function state_set($key, $value, $ttl, $uid = null) {
      $uid = $uid ? $uid : get_current_user_id();
      update_user_meta($uid, 'sm_gc_' . $key, array('v' => $value, 'exp' => time() + $ttl));
    }

    private function state_delete($key, $uid = null) {
      $uid = $uid ? $uid : get_current_user_id();
      delete_user_meta($uid, 'sm_gc_' . $key);
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
      if (!empty($client['source']) && $client['source'] === 'broker') {
        // The broker adds the client secret and relays Google's reply without keeping it.
        $body = $this->broker_request('/connect/google/token', $fields);
        if (is_wp_error($body)) return $body;
        $code = $body['_status'];
        unset($body['_status']);
      } else {
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
      $c = get_option(self::OPTION);
      return (!empty($c['refresh_token'])) ? $c : null;
    }

    private function access_token() {
      $connection = $this->get_connection();
      if (!$connection) return new WP_Error('not_connected', __('Not connected to Google.', ud_get_stateless_media()->domain));
      if (!empty($connection['access_token']) && $connection['expires'] > time()) {
        $plain = $this->decrypt($connection['access_token']);
        if ($plain !== '') return $plain;
      }
      $client = $this->get_client();
      if (!$client) return new WP_Error('no_client', __('OAuth client is not configured.', ud_get_stateless_media()->domain));
      $token = $this->token_request(array(
        'grant_type'    => 'refresh_token',
        'refresh_token' => $this->decrypt($connection['refresh_token']),
      ), $client);
      if (is_wp_error($token)) return $token;
      $connection['access_token'] = $this->encrypt($token['access_token']);
      $connection['expires']      = time() + intval($token['expires_in']) - 60;
      update_option(self::OPTION, $connection, false);
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
      @set_time_limit(300);

      $log = array();
      $uid = get_current_user_id();

      $project_choice = isset($_POST['project']) ? sanitize_text_field($_POST['project']) : '';
      $new_project    = isset($_POST['new_project_name']) ? sanitize_text_field($_POST['new_project_name']) : '';
      $billing_choice = isset($_POST['billing_account']) ? sanitize_text_field($_POST['billing_account']) : '';
      $bucket_choice  = isset($_POST['bucket_choice']) ? sanitize_text_field($_POST['bucket_choice']) : 'new';
      $bucket         = isset($_POST['bucket_name']) ? strtolower(trim(sanitize_text_field($_POST['bucket_name']))) : '';
      $location       = isset($_POST['location']) ? sanitize_text_field($_POST['location']) : 'US';
      $mode           = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'cdn';
      if (!in_array($mode, array('backup', 'cdn', 'ephemeral', 'stateless'), true)) $mode = 'cdn';

      if (!preg_match('/^[a-z0-9][a-z0-9._-]{1,61}[a-z0-9]$/', $bucket)) {
        $this->fail(__('Bucket names use 3 to 63 lowercase letters, numbers, dashes or dots.', ud_get_stateless_media()->domain));
      }

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
        } else {
          $project_id = $project_choice;
          if (!preg_match('/^[a-z][a-z0-9-]{4,29}$/', $project_id)) throw new Exception(__('Pick a project.', ud_get_stateless_media()->domain));
          $log[] = sprintf('Using project %s', $project_id);
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
        } else {
          $r = $this->api('GET', 'https://storage.googleapis.com/storage/v1/b/' . rawurlencode($bucket));
          $this->check($r, 'Find bucket');
          $log[] = sprintf('Using existing bucket %s', $bucket);
        }

        /* 4. Service account */
        $account_id = 'wp-stateless-' . substr(md5(home_url()), 0, 8);
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
          $found    = false;
          foreach ($bindings as &$b) {
            if ($b['role'] === 'roles/storage.admin') {
              if (!in_array($member, $b['members'], true)) $b['members'][] = $member;
              $found = true;
            }
          }
          unset($b);
          if (!$found) $bindings[] = array('role' => 'roles/storage.admin', 'members' => array($member));
          $r = $this->api('PUT', 'https://storage.googleapis.com/storage/v1/b/' . rawurlencode($bucket) . '/iam', array(
            'version'  => isset($policy['version']) ? $policy['version'] : 1,
            'bindings' => $bindings,
            'etag'     => isset($policy['etag']) ? $policy['etag'] : null,
          ));
          if (is_wp_error($r) && $attempt < 6 && in_array($r->get_error_code(), array('google_400', 'google_404'), true)) {
            sleep(4); // new service account not yet visible to IAM
            continue;
          }
          $this->check($r, 'Grant bucket access');
          break;
        } while (true);
        $log[] = 'Service account granted Storage Admin on this bucket only';

        /* 6. Key */
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

        /* 7. Save plugin settings */
        update_option('sm_bucket', $bucket);
        update_option('sm_key_json', $key_json);
        update_option('sm_mode', $mode);
        delete_transient('sm::is_connected_to_gs');
        $connection = $this->get_connection();
        $connection['project']  = $project_id;
        $connection['bucket']   = $bucket;
        $connection['sa_email'] = $sa_email;
        update_option(self::OPTION, $connection, false);
        $log[] = sprintf('Plugin configured: mode %s, bucket %s', $mode, $bucket);

        /* 8. Verify with the new key itself (IAM can lag a few seconds) */
        $verified = $this->verify_key($key_json, $bucket, 10);
        $log[] = $verified === true
          ? 'Verified: the site reached the bucket with its own credentials'
          : 'Saved, but the first connection check did not pass yet: ' . $verified . ' (IAM changes can take a minute to apply; reload Stateless Settings in a moment)';

        $this->state_set('log', $log, 10 * MINUTE_IN_SECONDS, $uid);
        $this->done(array('sm_gc' => 'provisioned'));
      } catch (Exception $e) {
        $log[] = 'Stopped: ' . $e->getMessage();
        $this->state_set('log', $log, 10 * MINUTE_IN_SECONDS, $uid);
        $this->fail($e->getMessage());
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
      if (!current_user_can('manage_options')) wp_die(__('Insufficient permissions.'));
      check_admin_referer(self::NONCE);
    }

    private function done($args) {
      wp_safe_redirect($this->page_url($args));
      exit;
    }

    private function fail($message) {
      $this->state_set('error', wp_strip_all_tags((string) $message), 10 * MINUTE_IN_SECONDS);
      $this->done(array('sm_gc' => 'error'));
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
