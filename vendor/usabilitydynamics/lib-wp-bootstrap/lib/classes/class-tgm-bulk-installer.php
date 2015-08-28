<?php
/**
 * Plugin installation and activation for WordPress themes.
 *
 * @package   TGM-Plugin-Activation
 * @version   2.4.0
 * @author    Thomas Griffin <thomasgriffinmedia.com>
 * @author    Gary Jones <gamajo.com>
 * @copyright Copyright (c) 2012, Thomas Griffin
 * @license   http://opensource.org/licenses/gpl-2.0.php GPL v2 or later
 * @link      https://github.com/thomasgriffin/TGM-Plugin-Activation
 */

/*
    Copyright 2014 Thomas Griffin (thomasgriffinmedia.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * The WP_Upgrader file isn't always available. If it isn't available,
 * we load it here.
 *
 * We check to make sure no action or activation keys are set so that WordPress
 * doesn't try to re-include the class when processing upgrades or installs outside
 * of the class.
 *
 * @since 2.2.0
 */
 
namespace UsabilityDynamics\WP {
  
  if ( ! class_exists( 'WP_Upgrader' ) ) {   
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
  }

  if ( ! class_exists( 'UsabilityDynamics\WP\TGM_Bulk_Installer' ) ) {
      /**
       * Installer class to handle bulk plugin installations.
       *
       * Extends WP_Upgrader and customizes to suit the installation of multiple
       * plugins.
       *
       * @since 2.2.0
       *
       * @package TGM-Plugin-Activation
       * @author  Thomas Griffin <thomasgriffinmedia.com>
       * @author  Gary Jones <gamajo.com>
       */
      class TGM_Bulk_Installer extends \WP_Upgrader {

          /**
           * Holds result of bulk plugin installation.
           *
           * @since 2.2.0
           *
           * @var string
           */
          public $result;

          /**
           * Flag to check if bulk installation is occurring or not.
           *
           * @since 2.2.0
           *
           * @var boolean
           */
          public $bulk = false;

          /**
           * Processes the bulk installation of plugins.
           *
           * @since 2.2.0
           *
           * @param array $packages The plugin sources needed for installation.
           * @return string|boolean Install confirmation messages on success, false on failure.
           */
          public function bulk_install( $packages ) {

              // Pass installer skin object and set bulk property to true.
              $this->init();
              $this->bulk = true;

              // Set install strings and automatic activation strings (if config option is set to true).
              $this->install_strings();
              if ( TGM_Plugin_Activation::$instance->is_automatic ) {
                  $this->activate_strings();
              }

              // Run the header string to notify user that the process has begun.
              $this->skin->header();

              // Connect to the Filesystem.
              $res = $this->fs_connect( array( WP_CONTENT_DIR, WP_PLUGIN_DIR ) );
              if ( ! $res ) {
                  $this->skin->footer();
                  return false;
              }

              // Set the bulk header and prepare results array.
              $this->skin->bulk_header();
              $results = array();

              // Get the total number of packages being processed and iterate as each package is successfully installed.
              $this->update_count   = count( $packages );
              $this->update_current = 0;

              // Loop through each plugin and process the installation.
              foreach ( $packages as $plugin ) {
                  $this->update_current++; // Increment counter.

                  // Do the plugin install.
                  $result = $this->run(
                      array(
                          'package'           => $plugin, // The plugin source.
                          'destination'       => WP_PLUGIN_DIR, // The destination dir.
                          'clear_destination' => false, // Do we want to clear the destination or not?
                          'clear_working'     => true, // Remove original install file.
                          'is_multi'          => true, // Are we processing multiple installs?
                          'hook_extra'        => array( 'plugin' => $plugin, ), // Pass plugin source as extra data.
                      )
                  );

                  // Store installation results in result property.
                  $results[$plugin] = $this->result;

                  // Prevent credentials auth screen from displaying multiple times.
                  if ( false === $result ) {
                      break;
                  }
              }

              // Pass footer skin strings.
              $this->skin->bulk_footer();
              $this->skin->footer();

              // Return our results.
              return $results;

          }

          /**
           * Performs the actual installation of each plugin.
           *
           * This method also activates the plugin in the automatic flag has been
           * set to true for the TGMPA class.
           *
           * @since 2.2.0
           *
           * @param array $options The installation cofig options
           * @return null/array Return early if error, array of installation data on success
           */
          public function run( $options ) {

              // Default config options.
              $defaults = array(
                  'package'           => '',
                  'destination'       => '',
                  'clear_destination' => false,
                  'clear_working'     => true,
                  'is_multi'          => false,
                  'hook_extra'        => array(),
              );

              // Parse default options with config options from $this->bulk_upgrade and extract them.
              $options = wp_parse_args( $options, $defaults );
              extract( $options );

              // Connect to the Filesystem.
              $res = $this->fs_connect( array( WP_CONTENT_DIR, $destination ) );
              if ( ! $res ) {
                  return false;
              }

              // Return early if there is an error connecting to the Filesystem.
              if ( is_wp_error( $res ) ) {
                  $this->skin->error( $res );
                  return $res;
              }

              // Call $this->header separately if running multiple times.
              if ( ! $is_multi )
                  $this->skin->header();

              // Set strings before the package is installed.
              $this->skin->before();

              // Download the package (this just returns the filename of the file if the package is a local file).
              $download = $this->download_package( $package );
              if ( is_wp_error( $download ) ) {
                  $this->skin->error( $download );
                  $this->skin->after();
                  return $download;
              }

              // Don't accidentally delete a local file.
              $delete_package = ( $download != $package );

              // Unzip file into a temporary working directory.
              $working_dir = $this->unpack_package( $download, $delete_package );
              if ( is_wp_error( $working_dir ) ) {
                  $this->skin->error( $working_dir );
                  $this->skin->after();
                  return $working_dir;
              }

              // Install the package into the working directory with all passed config options.
              $result = $this->install_package(
                  array(
                      'source'            => $working_dir,
                      'destination'       => $destination,
                      'clear_destination' => $clear_destination,
                      'clear_working'     => $clear_working,
                      'hook_extra'        => $hook_extra,
                  )
              );

              // Pass the result of the installation.
              $this->skin->set_result( $result );

              // Set correct strings based on results.
              if ( is_wp_error( $result ) ) {
                  $this->skin->error( $result );
                  $this->skin->feedback( 'process_failed' );
              }
              // The plugin install is successful.
              else {
                  $this->skin->feedback( 'process_success' );
              }

              // Only process the activation of installed plugins if the automatic flag is set to true.
              if ( TGM_Plugin_Activation::$instance->is_automatic ) {
                  // Flush plugins cache so we can make sure that the installed plugins list is always up to date.
                  wp_cache_flush();

                  // Get the installed plugin file and activate it.
                  $plugin_info = $this->plugin_info( $package );
                  $activate    = activate_plugin( $plugin_info );

                  // Re-populate the file path now that the plugin has been installed and activated.
                  TGM_Plugin_Activation::$instance->populate_file_path();

                  // Set correct strings based on results.
                  if ( is_wp_error( $activate ) ) {
                      $this->skin->error( $activate );
                      $this->skin->feedback( 'activation_failed' );
                  }
                  // The plugin activation is successful.
                  else {
                      $this->skin->feedback( 'activation_success' );
                  }
              }

              // Flush plugins cache so we can make sure that the installed plugins list is always up to date.
              wp_cache_flush();

              // Set install footer strings.
              $this->skin->after();
              if ( ! $is_multi ) {
                  $this->skin->footer();
              }

              return $result;

          }

          /**
           * Sets the correct install strings for the installer skin to use.
           *
           * @since 2.2.0
           */
          public function install_strings() {

              $this->strings['no_package']          = __( 'Install package not available.', 'tgmpa' );
              $this->strings['downloading_package'] = __( 'Downloading install package from <span class="code">%s</span>&#8230;', 'tgmpa' );
              $this->strings['unpack_package']      = __( 'Unpacking the package&#8230;', 'tgmpa' );
              $this->strings['installing_package']  = __( 'Installing the plugin&#8230;', 'tgmpa' );
              $this->strings['process_failed']      = __( 'Plugin install failed.', 'tgmpa' );
              $this->strings['process_success']     = __( 'Plugin installed successfully.', 'tgmpa' );

          }

          /**
           * Sets the correct activation strings for the installer skin to use.
           *
           * @since 2.2.0
           */
          public function activate_strings() {

              $this->strings['activation_failed']  = __( 'Plugin activation failed.', 'tgmpa' );
              $this->strings['activation_success'] = __( 'Plugin activated successfully.', 'tgmpa' );

          }

          /**
           * Grabs the plugin file from an installed plugin.
           *
           * @since 2.2.0
           *
           * @return string|boolean Return plugin file on success, false on failure
           */
          public function plugin_info() {

              // Return false if installation result isn't an array or the destination name isn't set.
              if ( ! is_array( $this->result ) ) {
                  return false;
              }

              if ( empty( $this->result['destination_name'] ) ) {
                  return false;
              }

              /// Get the installed plugin file or return false if it isn't set.
              $plugin = get_plugins( '/' . $this->result['destination_name'] );
              if ( empty( $plugin ) ) {
                  return false;
              }

              // Assume the requested plugin is the first in the list.
              $pluginfiles = array_keys( $plugin );

              return $this->result['destination_name'] . '/' . $pluginfiles[0];

          }

      }
  }

  if ( ! class_exists( 'UsabilityDynamics\WP\TGM_Bulk_Installer_Skin' ) ) {
      /**
       * Installer skin to set strings for the bulk plugin installations..
       *
       * Extends Bulk_Upgrader_Skin and customizes to suit the installation of multiple
       * plugins.
       *
       * @since 2.2.0
       *
       * @package TGM-Plugin-Activation
       * @author  Thomas Griffin <thomasgriffinmedia.com>
       * @author  Gary Jones <gamajo.com>
       */
      class TGM_Bulk_Installer_Skin extends \Bulk_Upgrader_Skin {

          /**
           * Holds plugin info for each individual plugin installation.
           *
           * @since 2.2.0
           *
           * @var array
           */
          public $plugin_info = array();

          /**
           * Holds names of plugins that are undergoing bulk installations.
           *
           * @since 2.2.0
           *
           * @var array
           */
          public $plugin_names = array();

          /**
           * Integer to use for iteration through each plugin installation.
           *
           * @since 2.2.0
           *
           * @var integer
           */
          public $i = 0;

          /**
           * Constructor. Parses default args with new ones and extracts them for use.
           *
           * @since 2.2.0
           *
           * @param array $args Arguments to pass for use within the class.
           */
          public function __construct( $args = array() ) {

              // Parse default and new args.
              $defaults = array( 'url' => '', 'nonce' => '', 'names' => array() );
              $args     = wp_parse_args( $args, $defaults );

              // Set plugin names to $this->plugin_names property.
              $this->plugin_names = $args['names'];

              // Extract the new args.
              parent::__construct( $args );

          }

          /**
           * Sets install skin strings for each individual plugin.
           *
           * Checks to see if the automatic activation flag is set and uses the
           * the proper strings accordingly.
           *
           * @since 2.2.0
           */
          public function add_strings() {

              // Automatic activation strings.
              if ( TGM_Plugin_Activation::$instance->is_automatic ) {
                  $this->upgrader->strings['skin_upgrade_start']        = __( 'The installation and activation process is starting. This process may take a while on some hosts, so please be patient.', 'tgmpa' );
                  $this->upgrader->strings['skin_update_successful']    = __( '%1$s installed and activated successfully.', 'tgmpa' ) . ' <a onclick="%2$s" href="#" class="hide-if-no-js"><span>' . __( 'Show Details', 'tgmpa' ) . '</span><span class="hidden">' . __( 'Hide Details', 'tgmpa' ) . '</span>.</a>';
                  $this->upgrader->strings['skin_upgrade_end']          = __( 'All installations and activations have been completed.', 'tgmpa' );
                  $this->upgrader->strings['skin_before_update_header'] = __( 'Installing and Activating Plugin %1$s (%2$d/%3$d)', 'tgmpa' );
              }
              // Default installation strings.
              else {
                  $this->upgrader->strings['skin_upgrade_start']        = __( 'The installation process is starting. This process may take a while on some hosts, so please be patient.', 'tgmpa' );
                  $this->upgrader->strings['skin_update_failed_error']  = __( 'An error occurred while installing %1$s: <strong>%2$s</strong>.', 'tgmpa' );
                  $this->upgrader->strings['skin_update_failed']        = __( 'The installation of %1$s failed.', 'tgmpa' );
                  $this->upgrader->strings['skin_update_successful']    = __( '%1$s installed successfully.', 'tgmpa' ) . ' <a onclick="%2$s" href="#" class="hide-if-no-js"><span>' . __( 'Show Details', 'tgmpa' ) . '</span><span class="hidden">' . __( 'Hide Details', 'tgmpa' ) . '</span>.</a>';
                  $this->upgrader->strings['skin_upgrade_end']          = __( 'All installations have been completed.', 'tgmpa' );
                  $this->upgrader->strings['skin_before_update_header'] = __( 'Installing Plugin %1$s (%2$d/%3$d)', 'tgmpa' );
              }

          }

          /**
           * Outputs the header strings and necessary JS before each plugin installation.
           *
           * @since 2.2.0
           */
          public function before( $title = '' ) {

              // We are currently in the plugin installation loop, so set to true.
              $this->in_loop = true;

              printf( '<h4>' . $this->upgrader->strings['skin_before_update_header'] . ' <img alt="" src="' . admin_url( 'images/wpspin_light.gif' ) . '" class="hidden waiting-' . $this->upgrader->update_current . '" style="vertical-align:middle;" /></h4>', $this->plugin_names[$this->i], $this->upgrader->update_current, $this->upgrader->update_count );
              echo '<script type="text/javascript">jQuery(\'.waiting-' . esc_js( $this->upgrader->update_current ) . '\').show();</script>';
              echo '<div class="update-messages hide-if-js" id="progress-' . esc_attr( $this->upgrader->update_current ) . '"><p>';

              // Flush header output buffer.
              $this->before_flush_output();

          }

          /**
           * Outputs the footer strings and necessary JS after each plugin installation.
           *
           * Checks for any errors and outputs them if they exist, else output
           * success strings.
           *
           * @since 2.2.0
           */
          public function after( $title = '' ) {

              // Close install strings.
              echo '</p></div>';

              // Output error strings if an error has occurred.
              if ( $this->error || ! $this->result ) {
                  if ( $this->error ) {
                      echo '<div class="error"><p>' . sprintf( $this->upgrader->strings['skin_update_failed_error'], $this->plugin_names[$this->i], $this->error ) . '</p></div>';
                  } else {
                      echo '<div class="error"><p>' . sprintf( $this->upgrader->strings['skin_update_failed'], $this->plugin_names[$this->i] ) . '</p></div>';
                  }

                  echo '<script type="text/javascript">jQuery(\'#progress-' . esc_js( $this->upgrader->update_current ) . '\').show();</script>';
              }

              // If the result is set and there are no errors, success!
              if ( ! empty( $this->result ) && ! is_wp_error( $this->result ) ) {
                  echo '<div class="updated"><p>' . sprintf( $this->upgrader->strings['skin_update_successful'], $this->plugin_names[$this->i], 'jQuery(\'#progress-' . esc_js( $this->upgrader->update_current ) . '\').toggle();jQuery(\'span\', this).toggle(); return false;' ) . '</p></div>';
                  echo '<script type="text/javascript">jQuery(\'.waiting-' . esc_js( $this->upgrader->update_current ) . '\').hide();</script>';
              }

              // Set in_loop and error to false and flush footer output buffer.
              $this->reset();
              $this->after_flush_output();

          }

          /**
           * Outputs links after bulk plugin installation is complete.
           *
           * @since 2.2.0
           */
          public function bulk_footer() {

              // Serve up the string to say installations (and possibly activations) are complete.
              parent::bulk_footer();

              // Flush plugins cache so we can make sure that the installed plugins list is always up to date.
              wp_cache_flush();

              // Display message based on if all plugins are now active or not.
              $complete = array();
              foreach ( TGM_Plugin_Activation::$instance->plugins as $plugin ) {
                  if ( ! is_plugin_active( $plugin['file_path'] ) ) {
                      echo '<p><a href="' . esc_url( add_query_arg( 'page', TGM_Plugin_Activation::$instance->menu, network_admin_url( 'themes.php' ) ) ) . '" title="' . esc_attr( TGM_Plugin_Activation::$instance->strings['return'] ) . '" target="_parent">' . TGM_Plugin_Activation::$instance->strings['return'] . '</a></p>';
                      $complete[] = $plugin;
                      break;
                  }
                  // Nothing to store.
                  else {
                      $complete[] = '';
                  }
              }

              // Filter out any empty entries.
              $complete = array_filter( $complete );

              // All plugins are active, so we display the complete string and hide the menu to protect users.
              if ( empty( $complete ) ) {
                  echo '<p>' .  sprintf( TGM_Plugin_Activation::$instance->strings['complete'], '<a href="' . network_admin_url() . '" title="' . __( 'Return to the Dashboard', 'tgmpa' ) . '">' . __( 'Return to the Dashboard', 'tgmpa' ) . '</a>' ) . '</p>';
                  echo '<style type="text/css">#adminmenu .wp-submenu li.current { display: none !important; }</style>';
              }

          }

          /**
           * Flush header output buffer.
           *
           * @since 2.2.0
           */
          public function before_flush_output() {

              wp_ob_end_flush_all();
              flush();

          }

          /**
           * Flush footer output buffer and iterate $this->i to make sure the
           * installation strings reference the correct plugin.
           *
           * @since 2.2.0
           */
          public function after_flush_output() {

              wp_ob_end_flush_all();
              flush();
              $this->i++;

          }

      }
  }

}