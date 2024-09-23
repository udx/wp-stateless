<?php
/**
 * Migrations status class
 *
 * @since 4.0.0
 */

namespace wpCloud\StatelessMedia\Status;

use wpCloud\StatelessMedia\Singleton;
use wpCloud\StatelessMedia\Helper;
use wpCloud\StatelessMedia\Migrator;

class Migrations {
  use Singleton;

  /**
   * Migrations data
   * 
   * @var array
   */
  private $migrations = [];

  /**
   * Primary migration id (that need to run first)
   * 
   * @var string
   */
  private $primary_migration_id = '';

  /**
   * Migration id that is currently in progress (running or paused)
   * 
   * @var string
   */
  private $running_migration_id = '';

  protected function __construct() {
    $this->_init_hooks();
  }

  private function _init_hooks() {
    add_action('wp_stateless_status_tab_content', [$this, 'tab_content'], 50);
    add_filter('wp_stateless_batch_state', [$this, 'migrations_state'], 20, 2);
  }

  /**
   * Detects the oldest migration that needs to be finished before the next one can run
   */
  private function _set_primary_migration_id() {
    $this->primary_migration_id = '';

    $migrations = array_reverse($this->migrations, true);

    foreach ($migrations as $id => $migration) {
      if ( in_array($migration['status'], [Migrator::STATUS_PENDING, Migrator::STATUS_RUNNING, Migrator::STATUS_FAILED]) ) {
        $this->primary_migration_id = $id;
        break;
      }
    }
  }

  /**
   * Returns the id of the migration that is currently running (if any)
   * 
   * @param array $state
   * @return string|bool|null
   */
  private function _set_running_migration_id($state) {
    $this->running_migration_id = '';

    if ( !empty($state) ) {
      if ( isset($state['is_migration']) && $state['is_migration'] ) {
        $this->running_migration_id = $state['id']; // migration is running
        
        return;
      }
    }
  }

  /**
   * Generate the message for the migration, depending on the current state
   * 
   * @param string $id
   * @param array $migration
   * @return string
   */
  private function _get_migration_message($id, $migration) {
    $message = $migration['message'] ?? '';

    switch ($migration['status']) {
      case Migrator::STATUS_FINISHED:
        $format = get_option('date_format') . ' ' . get_option( 'time_format' );
        $date = wp_date($format, $migration['finished']);
        $message = sprintf( __('Finished at %s', ud_get_stateless_media()->domain), $date );
        break;
      case Migrator::STATUS_SKIPPED:
        $message = !empty($message) ? $message : __('Not required', ud_get_stateless_media()->domain);
        break;
      case Migrator::STATUS_FAILED:
        $message = sprintf( __('Failed to finish: %s', ud_get_stateless_media()->domain), $message );
        break;
      case Migrator::STATUS_PENDING:
        if ( $id == $this->primary_migration_id ) {
          $message = sprintf( __('Ready to run', ud_get_stateless_media()->domain) );
        } else {
          $message = sprintf( 
            __('Requires <strong>%s</strong> to finish', ud_get_stateless_media()->domain), 
            $this->migrations[$this->primary_migration_id]['description'] 
          );
        }
        break;
      case Migrator::STATUS_RUNNING:
        $message = __('In progress...', ud_get_stateless_media()->domain);
        break;
    }

    return $message;
  }

  /**
   * Generate the UI message for the migration, depending on the current state
   * 
   * @param string $id
   * @param array $migration
   * @return string
   */
  private function _get_migration_ui_message($status, $state) {
    $message = '';
    $counter = false;

    if ( isset( $state['id'] ) && isset( $state['queue'] ) && is_array( $state['queue'] ) && count( $state['queue'] ) > 1 ) {
      $counter = sprintf( '%d/%d', array_search($state['id'], $state['queue']) + 1, count($state['queue']) );
    }

    switch ($status) {
      case Migrator::STATUS_RUNNING:
        $message = $counter 
          ? sprintf( __('In progress %s...', ud_get_stateless_media()->domain), $counter) 
          : __('In progress...', ud_get_stateless_media()->domain);
        break;
      case Migrator::STATUS_PAUSED:
        $message = $counter 
          ? sprintf( __('Paused %s...', ud_get_stateless_media()->domain), $counter) 
          : __('Paused...', ud_get_stateless_media()->domain);
        break;
    }

    return $message;
  }

  /**
   * Returns the status of the migration
   * 
   * @param string $status
   * @return string
   */
  public static function get_status_text($status) {
    switch ($status) {
      case Migrator::STATUS_PENDING:
        return __('Pending', ud_get_stateless_media()->domain);
      case Migrator::STATUS_RUNNING:
        return __('Running', ud_get_stateless_media()->domain);
      case Migrator::STATUS_SKIPPED:
        return __('Skipped', ud_get_stateless_media()->domain);
      case Migrator::STATUS_FINISHED:
        return __('Finished', ud_get_stateless_media()->domain);
      case Migrator::STATUS_FAILED:
        return __('Failed', ud_get_stateless_media()->domain);
    }
  }

  /**
   * Get migrations state for frontend display
   * 
   * @return array
   */
  private function _get_migrations_state($state = null) {
    $migrations = [];
    $this->migrations = apply_filters('wp_stateless_get_migrations', []);

    if ( !is_array($this->migrations) ) {
      $this->migrations = [];
    }
  
    $defaults = [
      'description' => '',
      'status'      => '',
      'status_text' => '',
      'message'     => '',
      'ui_message'  => '',
      'can_start'   => false,
      'can_pause'   => false,
      'can_resume'  => false,
    ];

    $this->_set_primary_migration_id();
    $batch_state = $state ? $state : apply_filters('wp_stateless_batch_state', [], []);
    $this->_set_running_migration_id($batch_state);

    foreach ($this->migrations as $id => $migration) {
      $status = $migration['status'];
      $can_start = $this->primary_migration_id == $id;
      $can_pause = false;
      $can_resume = false;

      if ( $this->running_migration_id == $id ) {
        $can_start = false;

        if ( $batch_state['is_paused'] ) {
          $status = Migrator::STATUS_PAUSED;
          $can_resume = true;
        } else {
          $status = Migrator::STATUS_RUNNING;
          $can_pause = true;
        }
      }

      $classes = [
        $status,
        $can_start ? 'can-start' : '',
        $can_pause ? 'can-pause' : '',
        $can_resume ? 'can-resume' : '',
      ];

      $data = wp_parse_args([
        'description' => $migration['description'],
        'status'      => $status,
        'classes'     => implode( ' ', array_filter($classes) ),
        'status_text' => self::get_status_text($status),
        'message'     => $this->_get_migration_message($id, $migration),
        'ui_message'  => $this->_get_migration_ui_message($status, $batch_state),
        'can_start'   => $can_start,
        'can_pause'   => $can_pause,
        'can_resume'  => $can_resume,
        ], $defaults);

      $migrations[$id] = $data;
    }

    return $migrations;
  }

  /**
   * Outputs 'Data Updates' section on the Status tab on the Settings page.
   */
  public function tab_content() {
    if ( is_network_admin() ) {
      return;
    }

    $migrations = $this->_get_migrations_state();
    $migration_ids = [];

    if ( !empty($migrations) ) {
      foreach ( $migrations as $migration_id => $migration ) {
        if ( $migration['status'] != Migrator::STATUS_FINISHED && $migration['status'] != Migrator::STATUS_SKIPPED ) {
          $migration_ids[] = $migration_id;
        }
      }
    }

    if ( !empty($migration_ids) ) {
      $migration_ids = array_reverse($migration_ids);
      $migration_id = $migration_ids[0];
      $migration = $migrations[ $migration_id ];

      include ud_get_stateless_media()->path('static/views/status-sections/migrations.php', 'dir');
    }
  }

  /**
   * Get migration state
   * 
   * @param array $state
   * @return array
   */
  public function migrations_state($state, $params) {
    $is_running = $state['is_running'] ?? false;
    $is_migration = $state['is_migration'] ?? false;
    $force_migrations = $params['force_migrations'] ?? false;

    if ( !$is_running && !$is_migration && !$force_migrations ) {
      return $state;
    }

    $state['migrations'] = $this->_get_migrations_state($state);
    $state['migrations_notify'] = get_option(Migrator::MIGRATIONS_NOTIFY_KEY, false);

    return $state;
  }
}
 