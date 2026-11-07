<?php

namespace wpCloud\StatelessMedia;

if (!class_exists('wpCloud\StatelessMedia\Plugin_Scaffold')) {
  class Plugin_Scaffold {
    public $name = false;
    public $slug = false;
    public $domain = false;
    public $root_path = false;
    public $root_url = false;
    public $uservoice_url = false;
    public $support_url = false;
    protected $_properties = array();

    protected function __construct($args = array()) {
      $extra = array();
      foreach ($args as $key => $value) {
        if (property_exists($this, $key)) {
          $property = new \ReflectionProperty($this, $key);
          if (!$property->isStatic()) {
            if ($key === 'root_path') $this->root_path = trailingslashit(trim($value));
            elseif ($key === 'name') {
              $this->slug = sanitize_key(trim($value));
              $this->name = trim($value);
            } else $this->{$key} = is_string($value) ? trim($value) : $value;
            continue;
          }
        }
        $extra[$key] = $value;
      }
      $this->args = $extra;
    }

    public function __get($key) {
      return isset($this->_properties[$key]) ? $this->_properties[$key] : null;
    }

    public function __set($key, $value) {
      $this->_properties[$key] = $value;
    }
  }
}

if (!class_exists('wpCloud\StatelessMedia\Update_Client')) {
  class Update_Client {
    private $args;
    private $slug;
    private $domain;
    private $option;

    public function __construct($args) {
      $this->args = $args;
      $this->slug = $args['slug'];
      $this->domain = $args['domain'];
      $this->option = sanitize_key('ud_ping_' . $this->slug);
      add_action('plugins_loaded', array($this, 'maybe_ping'), 11);
      add_action('admin_post_wp_stateless_dismiss_update_notice', array($this, 'dismiss_notice'));
    }

    public function maybe_ping() {
      $response = get_option($this->option);
      $cached = is_array($response) && !empty($response['time']) && time() - $response['time'] < DAY_IN_SECONDS;
      if ($cached) {
        if (empty($response['data'])) return;
        $response = $response['data'];
      } else {
        $version = $this->args['version'];
        $products = array($this->slug => array('version' => $version, 'status' => 'active', 'product_id' => $this->slug));
        $query = apply_filters('ud-api-client-ping-args', array(
          'request' => 'ping',
          'product_id' => $this->slug,
          'version' => $version,
          'detected_products' => base64_encode(wp_json_encode($products)),
          'nocache' => wp_rand(10000, 99999),
        ), $this, $products);
        $url = add_query_arg($query, 'https://www.usabilitydynamics.com/?wc-api=am-software-api');
        $request = wp_remote_get($url, array(
          'timeout' => 15,
          'redirection' => 0,
          'headers' => array(
            'x-ud-api-request' => 'ping',
            'x-ud-api-product-id' => $this->slug,
            'x-ud-api-platform' => str_ireplace(array('http://', 'https://'), '', home_url()),
          ),
        ));
        if (is_wp_error($request) || wp_remote_retrieve_response_code($request) !== 200) return;
        $response = json_decode(wp_remote_retrieve_body($request), true);
        if (!is_array($response) || !empty($response['error'])) return;
        update_option($this->option, array('time' => time(), 'data' => $response));
      }
      $response = apply_filters('ud::ping::response', $response, $this->slug, $cached);
      if (empty($response['message']) || !current_user_can('activate_plugins')) return;
      global $_ud_ping_notices;
      $_ud_ping_notices = isset($_ud_ping_notices) && is_array($_ud_ping_notices) ? $_ud_ping_notices : array();
      if (in_array($response['message'], $_ud_ping_notices, true)) return;
      $_ud_ping_notices[] = $response['message'];
      add_action('admin_notices', array($this, 'admin_notices'));
    }

    public function admin_notices() {
      global $_ud_ping_notices;
      $dismissed = get_option('dismissed_ud_notices', array());
      foreach (array_unique((array) $_ud_ping_notices) as $notice) {
        $hash = md5($notice);
        if (in_array($hash, (array) $dismissed, true)) continue;
        $message = apply_filters('ud::ping::response::admin_notice', base64_decode($notice));
        if (!$message) continue;
        $url = wp_nonce_url(admin_url('admin-post.php?action=wp_stateless_dismiss_update_notice&notice=' . $hash), 'wp_stateless_dismiss_update_notice_' . $hash);
        echo '<div class="notice notice-warning"><p>' . wp_kses_post($message) . ' <a href="' . esc_url($url) . '">' . esc_html__('Dismiss', $this->domain) . '</a></p></div>';
      }
    }

    public function dismiss_notice() {
      if (!current_user_can('activate_plugins')) wp_die(esc_html__('You are not allowed to do this action.', $this->domain));
      $hash = isset($_GET['notice']) ? sanitize_text_field(wp_unslash($_GET['notice'])) : '';
      if (!preg_match('/^[a-f0-9]{32}$/', $hash)) wp_die(esc_html__('Invalid notice.', $this->domain));
      check_admin_referer('wp_stateless_dismiss_update_notice_' . $hash);
      $dismissed = get_option('dismissed_ud_notices', array());
      $dismissed[] = $hash;
      update_option('dismissed_ud_notices', array_values(array_unique((array) $dismissed)));
      wp_safe_redirect(admin_url('index.php'));
      exit;
    }
  }
}

if (!class_exists('wpCloud\StatelessMedia\Plugin_Base')) {
  class Plugin_Base extends Plugin_Scaffold {
    public static $version = '1.0.0';
    public $type = 'plugin';
    public $schema = null;
    public $schema_path = null;
    public $errors = false;
    public $settings = null;
    public $boot_file = false;

    protected function __construct($args) {
      parent::__construct($args);
      add_action('init', array($this, 'load_textdomain'), 1);
      add_action('plugins_loaded', array($this, 'pre_init'), 100);
      add_action('plugins_loaded', array($this, 'define_update_client'), 11);
      $this->maybe_run_upgrade_process();
    }

    public function pre_init() {
      if (!$this->has_errors()) $this->init();
    }

    public function init() {}

    public function load_textdomain() {
      load_plugin_textdomain($this->domain, false, dirname(plugin_basename($this->boot_file)) . '/static/languages/');
    }

    public function path($short_path, $type = 'url') {
      if ($type === 'dir') return $this->root_path . ltrim($short_path, '/\\');
      if ($type === 'url') return $this->root_url . ltrim($short_path, '/\\');
      return false;
    }

    public function has_errors() {
      return $this->errors && $this->errors->has_errors();
    }

    public function set($key = null, $value = null) {
      return is_object($this->settings) ? $this->settings->set($key, $value) : false;
    }

    public function get($key = null, $default = null) {
      return is_object($this->settings) ? $this->settings->get($key, $default) : $default;
    }

    public function get_schema($key = '') {
      if ($this->schema === null && !empty($this->schema_path) && is_file($this->schema_path)) {
        $schema = json_decode(file_get_contents($this->schema_path), true);
        $this->schema = class_exists('UDX\\Utility') ? \UDX\Utility::l10n_localize((array) $schema, (array) $this->get_localization()) : (array) $schema;
      }
      if (!is_array($this->schema)) return false;
      if ($key === '') return $this->schema;
      $current = $this->schema;
      foreach (explode('.', $key) as $part) {
        if (!is_array($current) || !array_key_exists($part, $current)) return false;
        $current = $current[$part];
      }
      return $current;
    }

    public function get_localization() {
      return array();
    }

    public function define_update_client() {
      if ($this->has_errors() || !$this->get_schema('extra.schemas.licenses.client')) return false;
      new Update_Client(array_merge($this->args, array(
        'type' => $this->type,
        'name' => $this->name,
        'slug' => $this->slug,
        'domain' => $this->domain,
      )));
      return true;
    }

    protected function maybe_run_upgrade_process() {
      $version = get_option($this->slug . '-current-version', 0);
      $this->old_version = $version;
      if (!$version) add_action('plugins_loaded', array($this, '_run_install_process'), 0);
      elseif (version_compare($version, $this->args['version']) === -1) add_action('plugins_loaded', array($this, '_run_upgrade_process'), 0);
      add_action('plugins_loaded', array($this, 'save_version_no'), 100);
    }

    public function save_version_no() {
      update_option($this->slug . '-current-version', $this->args['version']);
    }

    public function _run_install_process() {
      $this->run_install_process();
    }

    public function _run_upgrade_process() {
      $this->run_upgrade_process();
    }

    public function run_install_process() {}

    public function run_upgrade_process() {}

    public static function get_instance($args = array()) {
      $class = get_called_class();
      if (null === $class::$instance) {
        $trace = debug_backtrace();
        if (!empty($trace[0]['file']) && is_file($trace[0]['file'])) {
          $metadata = get_file_data($trace[0]['file'], array(
            'name' => 'Plugin Name',
            'version' => 'Version',
            'domain' => 'Text Domain',
            'uservoice_url' => 'UserVoice',
            'support_url' => 'Support',
          ), 'plugin');
          $args = array_merge((array) $metadata, (array) $args, array(
            'root_path' => dirname($trace[0]['file']),
            'root_url' => plugin_dir_url($trace[0]['file']),
            'schema_path' => dirname($trace[0]['file']) . '/composer.json',
            'boot_file' => $trace[0]['file'],
          ));
          $class::$instance = new $class($args);
          register_activation_hook($trace[0]['file'], array($class::$instance, '_activate'));
          register_deactivation_hook($trace[0]['file'], array($class::$instance, '_deactivate'));
        } else {
          $class::$instance = new $class($args);
        }
      }
      return $class::$instance;
    }

    public function _activate() {
      $this->activate();
    }

    public function _deactivate() {
      $this->deactivate();
    }

    public function activate() {}

    public function deactivate() {}

    public function ud_bootstrap_dismiss_notice() {
      check_ajax_referer('ud_bootstrap_dismiss_notice', 'nonce');
      $response = array('success' => '0', 'error' => __('There was an error in request.', $this->domain));
      if (!current_user_can('manage_options')) $response['error'] = __('You are not allowed to do this action.', $this->domain);
      $key = isset($_POST['key']) ? sanitize_key($_POST['key']) : '';
      if (strpos($key, 'dismiss_') !== 0 || substr($key, -7) !== '_notice') $response['error'] = __('Invalid key', $this->domain);
      if (empty($_POST['slug']) || empty($_POST['type']) || empty($_POST['version'])) $response['error'] = __('Invalid values', $this->domain);
      if ($response['error'] === __('There was an error in request.', $this->domain)) {
        update_option($key, array(
          'slug' => sanitize_key($_POST['slug']),
          'type' => sanitize_key($_POST['type']),
          'version' => sanitize_text_field($_POST['version']),
        ));
        $response['success'] = '1';
      }
      wp_send_json($response);
    }
  }
}
