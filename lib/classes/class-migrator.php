<?php
/**
 * Migrations manager
 *
 * @since 4.0.0
 */

namespace wpCloud\StatelessMedia;

use wpCloud\StatelessMedia\Batch\BatchTaskManager;

class Migrator {
  use Singleton;

  const MIGRATIONS_KEY = 'sm_migrations';
  const MIGRATIONS_NOTIFY_KEY = 'sm_migrations_notify';
  const MIGRATIONS_NOTIFY_DISMISSED_KEY = 'dismissed_notice_migrations-finished';

  const NOTIFY_REQUIRE = 'require';
  const NOTIFY_FINISHED = 'finished';

  const STATUS_PENDING = 'pending';
  const STATUS_RUNNING = 'running';
  const STATUS_PAUSED = 'paused';
  const STATUS_SKIPPED = 'skipped';
  const STATUS_FINISHED = 'finished';
  const STATUS_FAILED = 'failed';

  /**
   * Path to migrations directory
   * 
   * @var string
   */
  private $path;

  protected function __construct() {
    $this->path = ud_get_stateless_media()->path('static/migrations', 'dir');

    $this->_init_hooks();
  }

  /**
   * Initializes the needed hooks
   */
  private function _init_hooks() {
    add_action( 'init', [$this, 'show_messages'] );
    add_action( 'wp_stateless_batch_task_started', [$this, 'migration_started'], 10, 2 );
    add_action( 'wp_stateless_batch_task_failed', [$this, 'migration_failed'], 10, 3 );
    add_action( 'wp_stateless_batch_task_finished', [$this, 'migration_finished'], 10, 2 );
    add_filter( 'wp_stateless_batch_action_start', [$this, 'start_migration'], 10, 2);
    add_action( 'wp_stateless_notice_dismissed', [$this, 'notice_dismissed'], 10, 1 );
    add_filter( 'wp_stateless_get_migrations', [$this, 'get_migrations']);
  }
  
  /**
   * Get migration ID from file name
   * 
   * @param string $file
   * @return string
   */
  private function _file_to_id($file) {
    return pathinfo($file, PATHINFO_FILENAME);
  }

  /**
   * Get migration ID from class name
   * 
   * @param string $class
   * @return string
   */
  private function _class_to_id($class) {
    return str_replace('Migration_', '', $class);
  }

  /**
   * Get migration class name from ID
   * 
   * @param string $id
   * @return string
   */
  private function _id_to_class($id) {
    return "\Migration_$id";
  }

  /**
   * Get migration file name from ID
   * 
   * @param string $id
   * @return string
   */
  private function _id_to_file($id) {
    return "$this->path/$id.php";
  }

  /**
   * Compares the list of files in the migrations directory with the list of finished migrations
   * Finds the oldest migration that has not been run yet
   * 
   * @return array
   */
  private function _get_migration_ids() {
    if ( !is_dir($this->path) ) {
      return [];
    }

    $ids = [];
    $files = scandir($this->path, SCANDIR_SORT_ASCENDING);

    foreach ($files as $file) {
      $extension = pathinfo($file, PATHINFO_EXTENSION);

      if ( $extension !== 'php' ) {
        continue;
      }

      $ids[] = $this->_file_to_id($file);
    }

    return $ids;
  }

  /**
   * Returns the migration object
   * 
   * @param string $id
   * @return wpCloud\StatelessMedia\Batch\Migration
   * @throws \Exception
   */
  private function _get_object($id) {
    $class = $this->_id_to_class($id);

    if ( !class_exists($class) ) {
      require_once $this->_id_to_file($id);
    }

    $object = new $class();

    if ( !is_a($object, '\wpCloud\StatelessMedia\Batch\Migration') ) {
      throw new \Exception("$class is not a valid migration");
    }

    $object->init_state();

    return $object;
  }

  /**
   * Checks if any migrations required and sets or removes global flag
   * 
   * @param array $migrations|null  
   */
  private function _check_required_migrations($migrations = null) {
    if ( empty($migrations) ) {
      $migrations = apply_filters('wp_stateless_get_migrations', []);
    }

    $require_migrations = false;

    foreach ($migrations as $id => $migration) {
      if ( !in_array( $migration['status'], [self::STATUS_FINISHED, self::STATUS_SKIPPED] ) ) {
        $require_migrations = true;
        break;
      }
    }

    if ( $require_migrations ) {
      update_option(self::MIGRATIONS_NOTIFY_KEY, self::NOTIFY_REQUIRE);
      delete_option(self::MIGRATIONS_NOTIFY_DISMISSED_KEY);
    } else {
      $notify = get_option(self::MIGRATIONS_NOTIFY_KEY, false);

      empty($notify) ? delete_option(self::MIGRATIONS_NOTIFY_KEY) : update_option(self::MIGRATIONS_NOTIFY_KEY, self::NOTIFY_FINISHED);
    }
  }

  /**
   * Dismisses the migration notice
   * 
   * @param string $option_name
   */
  public function notice_dismissed($option_name) {
    delete_option(self::MIGRATIONS_NOTIFY_KEY);
  }

  /**
   * Generates an updated list of migrations.
   * Checks which migrations should run.
   * Sets global options to display the requirement to run migrations.
   * 
   * Is called by Bootstrap object during version upgrade on 'plugins_loaded' hook.
   */
  public function migrate() {
    // Rebuild the migrations list and state according to the new version
    $ids = $this->_get_migration_ids();

    $migrations = apply_filters('wp_stateless_get_migrations', []);
    $existing = array_keys($migrations);

    foreach ($ids as $id) {
      if ( in_array($id, $existing) ) {
        continue;
      }

      try {
        $object = $this->_get_object($id);
        $skip = !$object->should_run();

        $migrations[$id] = [
          'description' => $object->get_description(),
          'started'     => '',
          'finished'    => '',
          'status'      => $object->should_run() ? self::STATUS_PENDING : self::STATUS_SKIPPED,
          'message'     => '',
        ];

      } catch (\Throwable $e) {
        Helper::log("Unable to initialize migration $id: " . $e->getMessage());
      }
    }

    krsort($migrations);

    update_option(self::MIGRATIONS_KEY, $migrations);

    // Check if we need to run any migrations
    $this->_check_required_migrations($migrations);
  }

  /**
   * Outputs the message that migrations are required
   */
  public function show_messages() {
    if ( is_network_admin() ) {
      return;
    }

    $is_running = BatchTaskManager::instance()->is_processing() || BatchTaskManager::instance()->is_paused();
    $notify = get_option(self::MIGRATIONS_NOTIFY_KEY, false);

    if ( $notify ) {
      ud_get_stateless_media()->errors->add([
        'title' => __('WP-Stateless: Data Optimization Required', ud_get_stateless_media()->domain),
        'message' => __('WP-Stateless has been updated! Your WP-Stateless data must now be optimized. <strong>Please backup your database before proceeding with the optimization.</strong>', ud_get_stateless_media()->domain),
        'button' => __('Optimize Data', ud_get_stateless_media()->domain),
        'button_link' => admin_url('upload.php?page=stateless-settings&tab=stless_status_tab#migration-action'),
        'key' => 'migrations-required',
        'dismiss' => false,
        'classes' => ($notify == self::NOTIFY_REQUIRE) && !$is_running ? '' : 'hidden',
      ], 'warning');

      ud_get_stateless_media()->errors->add([
        'title' => __('WP-Stateless: Data Optimization in Progress', ud_get_stateless_media()->domain),
        'message' => __('A background process is optimizing your WP-Stateless data. <strong>Please do not upload, change, or delete your media while this update is underway.</strong>', ud_get_stateless_media()->domain),
        'button' => __('View Progress', ud_get_stateless_media()->domain),
        'button_link' => admin_url('upload.php?page=stateless-settings&tab=stless_status_tab#migration-action'),
        'key' => 'migrations-running',
        'dismiss' => false,
        'classes' => $is_running ? '' : 'hidden',
        'capability' => 'upload_files',
        'button_capability' => 'manage_options',
      ], 'warning');

      ud_get_stateless_media()->errors->add([
        'title' => __('WP-Stateless: Data Optimization Complete', ud_get_stateless_media()->domain),
        'message' => __('Your WP-Stateless data has been optimized. You can now continue using your media as usual.', ud_get_stateless_media()->domain),
        'key' => 'migrations-finished',
        'classes' => ($notify == self::NOTIFY_FINISHED) && !$is_running ? '' : 'hidden',
      ], 'warning');
    }
  }

  /**
   * Mark migration as started
   *
   * @param string $class
   * @param string $file
   */
  public function migration_started($class, $file) {
    $migrations = apply_filters('wp_stateless_get_migrations', []);
    $id = $this->_file_to_id($file);

    if ( array_key_exists($id, $migrations) ) {
      $migrations[$id]['status'] = self::STATUS_RUNNING;
      $migrations[$id]['started'] = time();
      $migrations[$id]['finished'] = '';

      update_option(self::MIGRATIONS_KEY, $migrations);
    }
  }

  /**
   * Mark migration as failed and check other migrations
   *
   * @param string $class
   * @param string $file
   * @param string $message 
   */
  public function migration_failed($class, $file, $message) {
    $migrations = apply_filters('wp_stateless_get_migrations', []);
    $id = $this->_file_to_id($file);

    if ( array_key_exists($id, $migrations) ) {
      $migrations[$id]['status'] = self::STATUS_FAILED;
      $migrations[$id]['message'] = $message;

      update_option(self::MIGRATIONS_KEY, $migrations);
      $this->_check_required_migrations($migrations);
    }
  }

  /**
   * Mark migration as completed and check other migrations
   *
   * @param string $class
   */
  public function migration_finished($class, $state) {
    $migrations = apply_filters('wp_stateless_get_migrations', []);
    $id = $this->_class_to_id($class);

    if ( array_key_exists($id, $migrations) ) {
      $migrations[$id]['status'] = self::STATUS_FINISHED;
      $migrations[$id]['finished'] = time();

      update_option(self::MIGRATIONS_KEY, $migrations);
      $this->_check_required_migrations($migrations);
    }

    // When started from the UI, run next migration if needed
    if ( !empty($state['queue']) && is_array($state['queue']) ) {
      $index = array_search($id, $state['queue']);
      $next_index = false;

      if ( $index !== false && isset($state['queue'][$index + 1]) ) {
        $next_index = $state['queue'][$index + 1];
      }

      if ( $next_index === false ) {
        return;
      }

      $params = [
        'is_migration' => true,
        'id' => $next_index,
        'email' => $state['email'],
        'queue' => implode(':', $state['queue']),
        'action' => 'start',
      ];

      apply_filters("wp_stateless_batch_action_start", [], $params);
    }
  }

  /**
   * Run migration
   * 
   * @param array $state
   * @param array $params
   * @return array
   * @throws \Exception
   */
  public function start_migration($state, $params) {
    // Possibly not migration action
    if ( empty($params['is_migration']) || empty($params['id']) || !$params['is_migration'] ) {
      return $state;
    }
    
    $id = $params['id'];
    $migrations = apply_filters('wp_stateless_get_migrations', []);

    // Unknown migration?
    if ( !array_key_exists($id, $migrations) ) {
      return $state;
    }

    $class = $this->_id_to_class($id);
    $file = $this->_id_to_file($id);
    
    // Still possibly not migration action
    if ( !file_exists($file) ) {
      return $state;
    }

    if ( $migrations[$id]['status'] !== self::STATUS_PENDING && !isset($params['force']) ) {
      Helper::log("Migration $id is already started or finished. Status: " . $migrations[$id]['status']);

      return $state;
    }

    if ( BatchTaskManager::instance()->is_running() ) {
      Helper::log('Another batch task is already running');

      return $state;
    }

    $email = $params['email'] ?? '';
    $queue = isset($params['queue']) ? explode(':', $params['queue']) : [];

    BatchTaskManager::instance()->start_task($class, $file, $email, $queue);

    return apply_filters('wp_stateless_batch_state', $state, []);
  }

  /**
   * Get the list of migrations
   * 
   * @param array $migrations
   * @return array
   */
  public function get_migrations($migrations) {
    // We need to omit the cache and get the data directly from the db
    global $wpdb;

    $sql = $wpdb->prepare("SELECT option_value FROM $wpdb->options WHERE option_name = '%s' LIMIT 1", self::MIGRATIONS_KEY);
    $migrations = $wpdb->get_var($sql);

    return empty($migrations) ? [] : maybe_unserialize($migrations);
  }
}
