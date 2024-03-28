<?php
/**
 * Migrations status class
 *
 * @since 4.0.0
 */

namespace wpCloud\StatelessMedia\Status;

use wpCloud\StatelessMedia\Singleton;
use wpCloud\StatelessMedia\Helper;

class Info {
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
    add_action('wp_stateless_status_tab_content', [$this, 'tab_content'], 10);
  }

  /**
   * Get the total attachments count
   * 
   * @return int
   */
  private function _get_total_attachments() {
    global $wpdb;

    try {
      $query = "SELECT COUNT(*) " .
        "FROM $wpdb->posts " . 
        "WHERE post_type = 'attachment' AND post_status != 'trash'";

      return $wpdb->get_var($query);
    } catch (\Throwable $e) {
      return 0;
    }

    return wp_count_attachments();
  }

  /**
   * Outputs 'Health Status' tab content on the settings page.
   */
  public function tab_content() {    
    $rows = [
      [
        'label' => __('Total Attachments in Wordpress', ud_get_stateless_media()->domain),
        'value' => $this->_get_total_attachments(),
      ],
      [
        'label' => __('Total Attachments in WP-Stateless', ud_get_stateless_media()->domain),
        'value' => ud_stateless_db()->get_total_files(),
      ],
    ];

    $rows = Helper::array_of_objects($rows);

    include ud_get_stateless_media()->path('static/views/status-sections/info.php', 'dir');
  }
}
