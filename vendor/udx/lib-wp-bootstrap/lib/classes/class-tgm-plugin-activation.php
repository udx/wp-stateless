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

namespace UsabilityDynamics\WP {

  if ( ! class_exists( 'UsabilityDynamics\WP\TGM_Plugin_Activation' ) ) {
      /**
       * Automatic plugin installation and activation library.
       *
       * Creates a way to automatically install and activate plugins from within themes.
       * The plugins can be either pre-packaged, downloaded from the WordPress
       * Plugin Repository or downloaded from a private repository.
       *
       * @since 1.0.0
       *
       * @package TGM-Plugin-Activation
       * @author  Thomas Griffin <thomasgriffinmedia.com>
       * @author  Gary Jones <gamajo.com>
       */
      class TGM_Plugin_Activation {

          /**
           * Holds a copy of itself, so it can be referenced by the class name.
           *
           * @since 1.0.0
           *
           * @var TGM_Plugin_Activation
           */
          public static $instance;

          /**
           * Holds arrays of plugin details.
           *
           * @since 1.0.0
           *
           * @var array
           */
          public $plugins = array();
          
          /**
           *
           */
          public $referrers = array();

          /**
           * Name of the querystring argument for the admin page.
           *
           * @since 1.0.0
           *
           * @var string
           */
          public $menu = 'ud-install-plugins';

          /**
           * Default absolute path to folder containing pre-packaged plugin zip files.
           *
           * @since 2.0.0
           *
           * @var string Absolute path prefix to packaged zip file location. Default is empty string.
           */
          public $default_path = '';

          /**
           * Flag to show admin notices or not.
           *
           * @since 2.1.0
           *
           * @var boolean
           */
          public $has_notices = true;

          /**
           * Flag to determine if the user can dismiss the notice nag.
           *
           * @since 2.4.0
           *
           * @var boolean
           */
          public $dismissable = true;

          /**
           * Message to be output above nag notice if dismissable is false.
           *
           * @since 2.4.0
           *
           * @var string
           */
          public $dismiss_msg = '';

          /**
           * Flag to set automatic activation of plugins. Off by default.
           *
           * @since 2.2.0
           *
           * @var boolean
           */
          public $is_automatic = false;

          /**
           * Optional message to display before the plugins table.
           *
           * @since 2.2.0
           *
           * @var string Message filtered by wp_kses_post(). Default is empty string.
           */
          public $message = '';

          /**
           * Holds configurable array of strings.
           *
           * Default values are added in the constructor.
           *
           * @since 2.0.0
           *
           * @var array
           */
          public $strings = array();

          /**
           * Error Notice types.
           *
           * @var array
           */
          public $error_types = array(
            'notice_can_install_required',
            'notice_can_activate_required',
            'notice_ask_to_update',
          );
          
          /**
           * Holds the version of WordPress.
           *
           * @since 2.4.0
           *
           * @var int
           */
          public $wp_version;

          /**
           * Adds a reference of this object to $instance, populates default strings,
           * does the tgmpa_init action hook, and hooks in the interactions to init.
           *
           * @since 1.0.0
           *
           * @see TGM_Plugin_Activation::init()
           */
          private function __construct() {
              
              $this->strings = array(
                  'page_title'                     => __( 'Install Required Plugins', 'tgmpa' ),
                  'menu_title'                     => __( 'Install Plugins', 'tgmpa' ),
                  'installing'                     => __( 'Installing Plugin: %s', 'tgmpa' ),
                  'oops'                           => __( 'Something went wrong.', 'tgmpa' ),
                  'notice_can_install_required'    => _n_noop( '%2$s requires the following plugin: %1$s.', '%2$s requires the following plugins: %1$s.' ),
                  'notice_can_install_recommended' => _n_noop( '%2$s recommends the following plugin: %1$s.', '%2$s recommends the following plugins: %1$s.' ),
                  'notice_cannot_install'          => _n_noop( 'Sorry, but you do not have the correct permissions to install the %s plugin. Contact the administrator of this site for help on getting the plugin installed.', 'Sorry, but you do not have the correct permissions to install the %s plugins. Contact the administrator of this site for help on getting the plugins installed.' ),
                  'notice_can_activate_required'   => _n_noop( 'The following required plugin is currently inactive: %1$s.', 'The following required plugins are currently inactive: %1$s.' ),
                  'notice_can_activate_recommended'=> _n_noop( 'The following recommended plugin is currently inactive: %1$s.', 'The following recommended plugins are currently inactive: %1$s.' ),
                  'notice_cannot_activate'         => _n_noop( 'Sorry, but you do not have the correct permissions to activate the %s plugin. Contact the administrator of this site for help on getting the plugin activated.', 'Sorry, but you do not have the correct permissions to activate the %s plugins. Contact the administrator of this site for help on getting the plugins activated.' ),
                  'notice_ask_to_update'           => _n_noop( 'The following plugin needs to be updated to its latest version to ensure maximum compatibility with %2$s: %1$s.', 'The following plugins need to be updated to their latest version to ensure maximum compatibility with %2$s: %1$s.' ),
                  'notice_cannot_update'           => _n_noop( 'Sorry, but you do not have the correct permissions to update the %s plugin. Contact the administrator of this site for help on getting the plugin updated.', 'Sorry, but you do not have the correct permissions to update the %s plugins. Contact the administrator of this site for help on getting the plugins updated.' ),
                  'install_link'                   => _n_noop( 'Begin installing plugin', 'Begin installing plugins' ),
                  'activate_link'                  => _n_noop( 'Begin activating plugin', 'Begin activating plugins' ),
                  'return'                         => __( 'Return to Required Plugins Installer', 'tgmpa' ),
                  'dashboard'                      => __( 'Return to the dashboard', 'tgmpa' ),
                  'plugin_activated'               => __( 'Plugin activated successfully.', 'tgmpa' ),
                  'activated_successfully'         => __( 'The following plugin was activated successfully:', 'tgmpa' ),
                  'complete'                       => __( 'All plugins installed and activated successfully. %1$s', 'tgmpa' ),
                  'dismiss'                        => __( 'Dismiss this notice', 'tgmpa' ),
              );

              // Set the current WordPress version.
              global $wp_version;
              $this->wp_version = $wp_version;

              // Announce that the class is ready, and pass the object (for advanced use).
              do_action_ref_array( 'tgmpa_init', array( $this ) );

              // When the rest of WP has loaded, kick-start the rest of the class.
              //add_action( 'plugins_loaded', array( $this, 'init' ), 0 );
              add_action( 'init', array( $this, 'init' ) );

          }

          /**
           * Initialise the interactions between this class and WordPress.
           *
           * Hooks in three new methods for the class: admin_menu, notices and styles.
           *
           * @since 2.0.0
           *
           * @see TGM_Plugin_Activation::admin_menu()
           * @see TGM_Plugin_Activation::notices()
           * @see TGM_Plugin_Activation::styles()
           */
          public function init() {

              // After this point, the plugins should be registered and the configuration set.

              // Proceed only if we have plugins to handle.
              if ( $this->plugins ) {
                  $sorted = array();

                  foreach ( $this->plugins as $plugin ) {
                      $sorted[] = $plugin['name'];
                  }

                  array_multisort( $sorted, SORT_ASC, $this->plugins );

                  add_action( 'admin_menu', array( $this, 'admin_menu' ) );
                  add_filter( 'install_plugin_complete_actions', array( $this, 'actions' ) );
                  add_action( 'switch_theme', array( $this, 'flush_plugins_cache' ) );

                  // Load admin bar in the header to remove flash when installing plugins.
                  if ( $this->is_tgmpa_page() ) {
                      remove_action( 'wp_footer', 'wp_admin_bar_render', 1000 );
                      remove_action( 'admin_footer', 'wp_admin_bar_render', 1000 );
                      add_action( 'wp_head', 'wp_admin_bar_render', 1000 );
                      add_action( 'admin_head', 'wp_admin_bar_render', 1000 );
                  }

                  if ( $this->has_notices ) {
                      add_action( 'admin_init', array( $this, 'admin_init' ), 1 );
                      add_action( 'admin_enqueue_scripts', array( $this, 'thickbox' ) );
                  }

                  // Setup the force activation hook.
                  foreach ( $this->plugins as $plugin ) {
                      if ( isset( $plugin['force_activation'] ) && true === $plugin['force_activation'] ) {
                          add_action( 'admin_init', array( $this, 'force_activation' ) );
                          break;
                      }
                  }

                  // Setup the force deactivation hook.
                  foreach ( $this->plugins as $plugin ) {
                      if ( isset( $plugin['force_deactivation'] ) && true === $plugin['force_deactivation'] ) {
                          add_action( 'switch_theme', array( $this, 'force_deactivation' ) );
                          break;
                      }
                  }
              }

          }

          /**
           * Handles calls to show plugin information via links in the notices.
           *
           * We get the links in the admin notices to point to the TGMPA page, rather
           * than the typical plugin-install.php file, so we can prepare everything
           * beforehand.
           *
           * WP doesn't make it easy to show the plugin information in the thickbox -
           * here we have to require a file that includes a function that does the
           * main work of displaying it, enqueue some styles, set up some globals and
           * finally call that function before exiting.
           *
           * Down right easy once you know how...
           *
           * @since 2.1.0
           *
           * @global string $tab Used as iframe div class names, helps with styling
           * @global string $body_id Used as the iframe body ID, helps with styling
           * @return null Returns early if not the TGMPA page.
           */
          public function admin_init() {

              if ( ! $this->is_tgmpa_page() ) {
                  return;
              }

              if ( isset( $_REQUEST['tab'] ) && 'plugin-information' == $_REQUEST['tab'] ) {
                  require_once ABSPATH . 'wp-admin/includes/plugin-install.php'; // Need for install_plugin_information().

                  wp_enqueue_style( 'plugin-install' );

                  global $tab, $body_id;
                  $body_id = $tab = 'plugin-information';

                  install_plugin_information();

                  exit;
              }

          }

          /**
           * Enqueues thickbox scripts/styles for plugin info.
           *
           * Thickbox is not automatically included on all admin pages, so we must
           * manually enqueue it for those pages.
           *
           * Thickbox is only loaded if the user has not dismissed the admin
           * notice or if there are any plugins left to install and activate.
           *
           * @since 2.1.0
           */
          public function thickbox() {
            add_thickbox();
          }

          /**
           * Adds submenu page under 'Appearance' tab.
           *
           * This method adds the submenu page letting users know that a required
           * plugin needs to be installed.
           *
           * This page disappears once the plugin has been installed and activated.
           *
           * @since 1.0.0
           *
           * @see TGM_Plugin_Activation::init()
           * @see TGM_Plugin_Activation::install_plugins_page()
           */
          public function admin_menu() {

              // Make sure privileges are correct to see the page
              if ( ! current_user_can( 'install_plugins' ) ) {
                  return;
              }

              $this->populate_file_path();

              foreach ( $this->plugins as $plugin ) {
                  if ( ! is_plugin_active( $plugin['file_path'] ) ) {
                      add_theme_page(
                          $this->strings['page_title'],          // Page title.
                          $this->strings['menu_title'],          // Menu title.
                          'edit_theme_options',                  // Capability.
                          $this->menu,                           // Menu slug.
                          array( $this, 'install_plugins_page' ) // Callback.
                      );
                  break;
                  }
              }

          }

          /**
           * Echoes plugin installation form.
           *
           * This method is the callback for the admin_menu method function.
           * This displays the admin page and form area where the user can select to install and activate the plugin.
           *
           * @since 1.0.0
           *
           * @return null Aborts early if we're processing a plugin installation action
           */
          public function install_plugins_page() {

              // Store new instance of plugin table in object.
              $plugin_table = new TGMPA_List_Table;

              // Return early if processing a plugin installation action.
              if ( isset( $_POST['action'] ) && 'tgmpa-bulk-install' == $_POST['action'] && $plugin_table->process_bulk_actions() || $this->do_plugin_install() ) {
                  return;
              }

              ?>
              <div class="tgmpa wrap">

                  <?php if ( version_compare( $this->wp_version, '3.8', '<' ) ) {
                      screen_icon( apply_filters( 'ud_default_screen_icon', 'themes' ) );
                  } ?>
                  <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
                  <?php $plugin_table->prepare_items(); ?>

                  <?php if ( isset( $this->message ) ) {
                      echo wp_kses_post( $this->message );
                  } ?>

                  <form id="tgmpa-plugins" action="" method="post">
                      <input type="hidden" name="tgmpa-page" value="<?php echo $this->menu; ?>" />
                      <?php $plugin_table->display(); ?>
                  </form>

              </div>
              <?php

          }

          /**
           * Installs a plugin or activates a plugin depending on the hover
           * link clicked by the user.
           *
           * Checks the $_GET variable to see which actions have been
           * passed and responds with the appropriate method.
           *
           * Uses WP_Filesystem to process and handle the plugin installation
           * method.
           *
           * @since 1.0.0
           *
           * @uses WP_Filesystem
           * @uses WP_Error
           * @uses WP_Upgrader
           * @uses Plugin_Upgrader
           * @uses Plugin_Installer_Skin
           *
           * @return boolean True on success, false on failure
           */
          protected function do_plugin_install() {

              // All plugin information will be stored in an array for processing.
              $plugin = array();

              // Checks for actions from hover links to process the installation.
              if ( isset( $_GET['plugin'] ) && ( isset( $_GET['tgmpa-install'] ) && 'install-plugin' == $_GET['tgmpa-install'] ) ) {
                  check_admin_referer( 'tgmpa-install' );

                  $plugin['name']   = $_GET['plugin_name']; // Plugin name.
                  $plugin['slug']   = $_GET['plugin']; // Plugin slug.
                  $plugin['source'] = $_GET['plugin_source']; // Plugin source.

                  // Pass all necessary information via URL if WP_Filesystem is needed.
                  $url = wp_nonce_url(
                      esc_url( add_query_arg(
                          array(
                              'page'          => $this->menu,
                              'plugin'        => $plugin['slug'],
                              'plugin_name'   => $plugin['name'],
                              'plugin_source' => $plugin['source'],
                              'tgmpa-install' => 'install-plugin',
                          ),
                          admin_url( 'themes.php' )
                      ) ),
                      'tgmpa-install'
                  );
                  $method = ''; // Leave blank so WP_Filesystem can populate it as necessary.
                  $fields = array( 'tgmpa-install' ); // Extra fields to pass to WP_Filesystem.

                  if ( false === ( $creds = request_filesystem_credentials( $url, $method, false, false, $fields ) ) ) {
                      return true;
                  }

                  if ( ! WP_Filesystem( $creds ) ) {
                      request_filesystem_credentials( $url, $method, true, false, $fields ); // Setup WP_Filesystem.
                      return true;
                  }

                  require_once ABSPATH . 'wp-admin/includes/plugin-install.php'; // Need for plugins_api.
                  require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php'; // Need for upgrade classes.

                  // Set plugin source to WordPress API link if available.
                  if ( isset( $plugin['source'] ) && 'repo' == $plugin['source'] ) {
                      $api = plugins_api( 'plugin_information', array( 'slug' => $plugin['slug'], 'fields' => array( 'sections' => false ) ) );

                      if ( is_wp_error( $api ) ) {
                          wp_die( $this->strings['oops'] . var_dump( $api ) );
                      }

                      if ( isset( $api->download_link ) ) {
                          $plugin['source'] = $api->download_link;
                      }
                  }

                  // Set type, based on whether the source starts with http:// or https://.
                  $type = preg_match( '|^http(s)?://|', $plugin['source'] ) ? 'web' : 'upload';

                  // Prep variables for Plugin_Installer_Skin class.
                  $title = sprintf( $this->strings['installing'], $plugin['name'] );
                  $url   = esc_url( add_query_arg( array( 'action' => 'install-plugin', 'plugin' => $plugin['slug'] ), 'update.php' ) );
                  if ( isset( $_GET['from'] ) ) {
                      $url .= esc_url( add_query_arg( 'from', urlencode( stripslashes( $_GET['from'] ) ), $url ) );
                  }

                  $nonce = 'install-plugin_' . $plugin['slug'];

                  // Prefix a default path to pre-packaged plugins.
                  $source = ( 'upload' == $type ) ? $this->default_path . $plugin['source'] : $plugin['source'];

                  // Create a new instance of Plugin_Upgrader.
                  $upgrader = new \Plugin_Upgrader( $skin = new \Plugin_Installer_Skin( compact( 'type', 'title', 'url', 'nonce', 'plugin', 'api' ) ) );

                  // Perform the action and install the plugin from the $source urldecode().
                  $upgrader->install( $source );

                  // Flush plugins cache so we can make sure that the installed plugins list is always up to date.
                  wp_cache_flush();

                  // Only activate plugins if the config option is set to true.
                  if ( $this->is_automatic ) {
                      $plugin_activate = $upgrader->plugin_info(); // Grab the plugin info from the Plugin_Upgrader method.
                      $activate        = activate_plugin( $plugin_activate ); // Activate the plugin.
                      $this->populate_file_path(); // Re-populate the file path now that the plugin has been installed and activated.

                      if ( is_wp_error( $activate ) ) {
                          echo '<div id="message" class="error"><p>' . $activate->get_error_message() . '</p></div>';
                          echo '<p><a href="' . esc_url( add_query_arg( 'page', $this->menu, admin_url( 'themes.php' ) ) ) . '" title="' . esc_attr( $this->strings['return'] ) . '" target="_parent">' . $this->strings['return'] . '</a></p>';
                          return true; // End it here if there is an error with automatic activation
                      }
                      else {
                          echo '<p>' . $this->strings['plugin_activated'] . '</p>';
                      }
                  }

                  // Display message based on if all plugins are now active or not.
                  $complete = array();
                  foreach ( $this->plugins as $plugin ) {
                      if ( ! is_plugin_active( $plugin['file_path'] ) ) {
                          echo '<p><a href="' . add_query_arg( 'page', $this->menu, admin_url( 'themes.php' ) ) . '" title="' . esc_attr( $this->strings['return'] ) . '" target="_parent">' . $this->strings['return'] . '</a></p>';
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

                  // All plugins are active, so we display the complete string and hide the plugin menu.
                  if ( empty( $complete ) ) {
                      echo '<p>' .  sprintf( $this->strings['complete'], '<a href="' . admin_url() . '" title="' . __( 'Return to the Dashboard', 'tgmpa' ) . '">' . __( 'Return to the Dashboard', 'tgmpa' ) . '</a>' ) . '</p>';
                      echo '<style type="text/css">#adminmenu .wp-submenu li.current { display: none !important; }</style>';
                  }

                  return true;
              }
              // Checks for actions from hover links to process the activation.
              elseif ( isset( $_GET['plugin'] ) && ( isset( $_GET['tgmpa-activate'] ) && 'activate-plugin' == $_GET['tgmpa-activate'] ) ) {
                  check_admin_referer( 'tgmpa-activate', 'tgmpa-activate-nonce' );

                  // Populate $plugin array with necessary information.
                  $plugin['name']   = $_GET['plugin_name'];
                  $plugin['slug']   = $_GET['plugin'];
                  $plugin['source'] = $_GET['plugin_source'];

                  $plugin_path = $this->_get_plugin_basename_from_slug($plugin['slug']); // Retrieve all plugins.
                  $activate = activate_plugin( $plugin_path ); // Activate the plugin.

                  if ( is_wp_error( $activate ) ) {
                      echo '<div id="message" class="error"><p>' . $activate->get_error_message() . '</p></div>';
                      echo '<p><a href="' . esc_url( add_query_arg( 'page', $this->menu, admin_url( 'themes.php' ) ) ) . '" title="' . esc_attr( $this->strings['return'] ) . '" target="_parent">' . $this->strings['return'] . '</a></p>';
                      return true; // End it here if there is an error with activation.
                  }
                  else {
                      // Make sure message doesn't display again if bulk activation is performed immediately after a single activation.
                      if ( ! isset( $_POST['action'] ) ) {
                          $msg = $this->strings['activated_successfully'] . ' <strong>' . $plugin['name'] . '</strong>';
                          echo '<div id="message" class="updated"><p>' . $msg . '</p></div>';
                      }
                  }
              }

              return false;

          }

          /**
           * Echoes required plugin notice.
           *
           * Outputs a message telling users that a specific plugin is required for
           * their theme. If appropriate, it includes a link to the form page where
           * users can install and activate the plugin.
           *
           * @since 1.0.0
           *
           * @global object $current_screen
           * @return null Returns early if we're on the Install page.
           */
          public function notices( $referrer = false ) {
              //** Check if get_plugins() function exists. This is required on the front end of the */
              //** site, since it is in a file that is normally only loaded in the admin. */
              if ( ! function_exists( 'get_plugins' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
              }
              
              $installed_plugins = \get_plugins(); // Retrieve a list of all the plugins
              $this->populate_file_path();

              $message             = array(); // Store the messages in an array to be outputted after plugins have looped through.
              
              $e_install_link        = false;   // Set to false, change to true in loop if conditions exist, used for action link 'install'.
              $e_install_link_count  = 0;       // Used to determine plurality of install action link text.
              $e_activate_link       = false;   // Set to false, change to true in loop if conditions exist, used for action link 'activate'.
              $e_activate_link_count = 0;       // Used to determine plurality of activate action link text.

              $m_install_link        = false;   // Set to false, change to true in loop if conditions exist, used for action link 'install'.
              $m_install_link_count  = 0;       // Used to determine plurality of install action link text.
              $m_activate_link       = false;   // Set to false, change to true in loop if conditions exist, used for action link 'activate'.
              $m_activate_link_count = 0;       // Used to determine plurality of activate action link text.
              
              foreach ( $this->referrers as $plugin ) {
                  //** We must return only notices for referrer */
                  if( empty( $referrer ) || $referrer != $plugin[ '_referrer' ] || !isset( $this->plugins[ $plugin[ 'slug' ] ] ) ) {
                    continue;
                  }
                  $plugin[ 'file_path' ] = $this->plugins[ $plugin[ 'slug' ] ][ 'file_path' ];
                  // If the plugin is installed and active, check for minimum version argument before moving forward.
                  if ( is_plugin_active( $plugin['file_path'] ) ) {
                      // A minimum version has been specified.
                      if ( isset( $plugin['version'] ) ) {
                          if ( isset( $installed_plugins[$plugin['file_path']]['Version'] ) ) {
                              // If the current version is less than the minimum required version, we display a message.
                              if ( version_compare( $installed_plugins[$plugin['file_path']]['Version'], $plugin['version'], '<' ) ) {
                                  if ( current_user_can( 'install_plugins' ) ) {
                                      $message['notice_ask_to_update'][] = $plugin;
                                  } else {
                                      $message['notice_cannot_update'][] = $plugin;
                                  }
                              }
                          }
                          // Can't find the plugin, so iterate to the next condition.
                          else {
                              continue;
                          }
                      }
                      // No minimum version specified, so iterate over the plugin.
                      else {
                          continue;
                      }
                  }

                  // Not installed.
                  if ( ! isset( $installed_plugins[$plugin['file_path']] ) ) {
                      if ( current_user_can( 'install_plugins' ) ) {
                          if ( isset( $plugin['required'] ) && $plugin['required'] ) {
                            $e_install_link = true; // We need to display the 'install' action link.
                            $e_install_link_count++; // Increment the install link count.
                            $message['notice_can_install_required'][] = $plugin;
                          }
                          // This plugin is only recommended.
                          else {
                            $m_install_link = true; // We need to display the 'install' action link.
                            $m_install_link_count++; // Increment the install link count.
                            $message['notice_can_install_recommended'][] = $plugin;
                          }
                      } elseif( !is_user_logged_in() && isset( $plugin['required'] ) && $plugin['required'] ) {
                          $message['notice_can_install_required'][] = $plugin;
                      }
                      // Need higher privileges to install the plugin.
                      else {
                          if ( isset( $plugin['required'] ) && $plugin['required'] ) {
                              $message['notice_can_install_required'][] = $plugin;
                              $message['notice_cannot_activate'][] = $plugin;
                          }
                          else {
                              $message['notice_cannot_activate'][] = $plugin;
                          }
                      }
                  }
                  // Installed but not active.
                  elseif ( is_plugin_inactive( $plugin['file_path'] ) ) {
                      if ( current_user_can( 'activate_plugins' ) ) {
                          if ( isset( $plugin['required'] ) && $plugin['required'] ) {
                            $e_activate_link = true; // We need to display the 'activate' action link.
                            $e_activate_link_count++; // Increment the activate link count.
                            $message['notice_can_activate_required'][] = $plugin;
                          }
                          // This plugin is only recommended.
                          else {
                            $m_activate_link = true; // We need to display the 'activate' action link.
                            $m_activate_link_count++; // Increment the activate link count.
                            $message['notice_can_activate_recommended'][] = $plugin;
                          }
                      } elseif( !is_user_logged_in() && isset( $plugin['required'] ) && $plugin['required'] ) {
                          $message['notice_can_install_required'][] = $plugin;
                      }
                      // Need higher privileges to activate the plugin.
                      else {
                          if ( isset( $plugin['required'] ) && $plugin['required'] ) {
                              $message['notice_can_install_required'][] = $plugin;
                              $message['notice_cannot_activate'][] = $plugin;
                          }
                          else {
                              $message['notice_cannot_activate'][] = $plugin;
                          }

                      }
                  }
              }
              
              //return $message;

              $prepared = array();
              
              // If we have notices to display, we move forward.
              if ( ! empty( $message ) ) {
                  krsort( $message ); // Sort messages.
                  
                  // Grab all plugin names.
                  foreach ( $message as $type => $plugin_groups ) {
                      $linked_plugin_groups = array();

                      // Count number of plugins in each message group to calculate singular/plural message.
                      $count = count( $plugin_groups );

                      // Loop through the plugin names to make the ones pulled from the .org repo linked.
                      foreach ( $plugin_groups as $plugin ) {
                          $plugin_group_single_name = $plugin[ 'name' ];
                          $external_url = $this->_get_plugin_data_from_name( $plugin_group_single_name, 'external_url' );
                          $source       = $this->_get_plugin_data_from_name( $plugin_group_single_name, 'source' );

                          if ( $external_url && preg_match( '|^http(s)?://|', $external_url ) ) {
                              $linked_plugin_groups[] = '<a href="' . esc_url( $external_url ) . '" title="' . $plugin_group_single_name . '" target="_blank">' . $plugin_group_single_name . '</a>';
                          }
                          elseif ( ! $source || preg_match( '|^http://wordpress.org/extend/plugins/|', $source ) ) {
                              $url = esc_url( add_query_arg(
                                  array(
                                      'tab'       => 'plugin-information',
                                      'plugin'    => $this->_get_plugin_data_from_name( $plugin_group_single_name ),
                                      'TB_iframe' => 'true',
                                      'width'     => '640',
                                      'height'    => '500',
                                  ),
                                  admin_url( 'plugin-install.php' )
                              ) );

                              $linked_plugin_groups[] = '<a href="' . esc_url( $url ) . '" class="thickbox" title="' . $plugin_group_single_name . '">' . $plugin_group_single_name . '</a>';
                          }
                          else {
                              $linked_plugin_groups[] = $plugin_group_single_name; // No hyperlink.
                          }

                          if ( isset( $linked_plugin_groups ) && (array) $linked_plugin_groups ) {
                              $plugin_groups = $linked_plugin_groups;
                          }
                      }

                      $last_plugin = array_pop( $plugin_groups ); // Pop off last name to prep for readability.
                      $imploded    = empty( $plugin_groups ) ? '<em>' . $last_plugin . '</em>' : '<em>' . ( implode( ', ', $plugin_groups ) . '</em> and <em>' . $last_plugin . '</em>' );
                      
                      $prepared['messages'][] = array(
                        'type' => ( in_array( $type, $this->error_types ) && $plugin[ 'required' ] ? 'error' : 'message' ),
                        'value' => sprintf( translate_nooped_plural( $this->strings[$type], $count, 'tgmpa' ), $imploded, $plugin[ '_referrer_name' ], $count ),
                      );
                      
                  }
                  
                  //** Setup variables to determine if action links are needed. */
                  $e_show_install_link  = $e_install_link ? '<a href="' . esc_url( add_query_arg( 'page', $this->menu, admin_url( 'themes.php' ) ) ) . '">' . translate_nooped_plural( $this->strings['install_link'], $e_install_link_count, 'tgmpa' ) . '</a>' : '';
                  $e_show_activate_link = $e_activate_link ? '<a href="' . esc_url( add_query_arg( 'page', $this->menu, admin_url( 'themes.php' ) ) ) . '">' . translate_nooped_plural( $this->strings['activate_link'], $e_activate_link_count, 'tgmpa' ) . '</a>'  : '';
                  
                  $m_show_install_link  = $m_install_link ? '<a href="' . esc_url( add_query_arg( 'page', $this->menu, admin_url( 'themes.php' ) ) ) . '">' . translate_nooped_plural( $this->strings['install_link'], $m_install_link_count, 'tgmpa' ) . '</a>' : '';
                  $m_show_activate_link = $m_activate_link ? '<a href="' . esc_url( add_query_arg( 'page', $this->menu, admin_url( 'themes.php' ) ) ) . '">' . translate_nooped_plural( $this->strings['activate_link'], $m_activate_link_count, 'tgmpa' ) . '</a>'  : '';
                  
                  //** Define all of the action links. */
                  $prepared[ 'links' ] = array(
                    'error' => array_filter( array(
                      'install'  => ( current_user_can( 'install_plugins' ) )  ? $e_show_install_link  : false,
                      'activate' => ( current_user_can( 'activate_plugins' ) ) ? $e_show_activate_link : false,
                    ) ),
                    'message' => array_filter( array(
                      'install'  => ( current_user_can( 'install_plugins' ) )  ? $m_show_install_link  : false,
                      'activate' => ( current_user_can( 'activate_plugins' ) ) ? $m_show_activate_link : false,
                    ) ),
                  );
              }
              
              return $prepared;
          }
          
          /**
           * Add individual plugin to our collection of plugins.
           *
           * If the required keys are not set or the plugin has already
           * been registered, the plugin is not added.
           *
           * @since 2.0.0
           *
           * @param array $plugin Array of plugin arguments.
           */
          public function register( $plugin ) {
            if ( ! isset( $plugin['slug'] ) || ! isset( $plugin['name'] ) ) {
              return;
            }
            if( isset( $this->plugins[ $plugin[ 'slug' ] ] ) ) {
              $_plugin = $this->plugins[ $plugin[ 'slug' ] ];
              //** Version must be set the highest to prevent issues. */
              if( !empty( $_plugin[ 'version' ] ) && !empty( $plugin[ 'version' ] ) ) {
                $version = version_compare( $_plugin[ 'version' ], $plugin[ 'version' ], '<' ) ? $plugin[ 'version' ] : $_plugin[ 'version' ];
              } else {
                $version = !empty( $plugin[ 'version' ] ) ? $plugin[ 'version' ] : ( !empty( $_plugin[ 'version' ] ) ? $_plugin[ 'version' ] : false );
              }
              if( !empty( $version ) ) {
                $this->plugins[ $plugin[ 'slug' ] ][ 'version' ] = $version;
              }
              //** Parent plugin must be set as required if any child plugin requires it. */
              $this->plugins[ $plugin[ 'slug' ] ][ 'required' ] = $_plugin[ 'required' ] == true ? $_plugin[ 'required' ] : $plugin[ 'required' ];
            } else {
              $_plugin = $plugin;
              unset( $_plugin[ '_referrer' ] );
              unset( $_plugin[ '_referrer_name' ] );
              $this->plugins[ $plugin[ 'slug' ] ] = $_plugin;
            }
            $this->referrers[] = $plugin;
          }

          /**
           * Amend default configuration settings.
           *
           * @since 2.0.0
           *
           * @param array $config Array of config options to pass as class properties.
           */
          public function config( $config ) {

              $keys = array( 'default_path', 'has_notices', 'dismissable', 'dismiss_msg', 'menu', 'is_automatic', 'message', 'strings' );

              foreach ( $keys as $key ) {
                  if ( isset( $config[$key] ) ) {
                      if ( is_array( $config[$key] ) ) {
                          foreach ( $config[$key] as $subkey => $value ) {
                              $this->{$key}[$subkey] = $value;
                          }
                      } else {
                          $this->$key = $config[$key];
                      }
                  }
              }

          }

          /**
           * Amend action link after plugin installation.
           *
           * @since 2.0.0
           *
           * @param array $install_actions Existing array of actions.
           * @return array                 Amended array of actions.
           */
          public function actions( $install_actions ) {

              // Remove action links on the TGMPA install page.
              if ( $this->is_tgmpa_page() ) {
                  return false;
              }

              return $install_actions;

          }

          /**
           * Flushes the plugins cache on theme switch to prevent stale entries
           * from remaining in the plugin table.
           *
           * @since 2.4.0
           */
          public function flush_plugins_cache() {

              wp_cache_flush();

          }

          /**
           * Set file_path key for each installed plugin.
           *
           * @since 2.1.0
           */
          public function populate_file_path() {

              // Add file_path key for all plugins.
              foreach ( $this->plugins as $plugin => $values ) {
                  $this->plugins[$plugin]['file_path'] = $this->_get_plugin_basename_from_slug( $values['slug'] );
              }

          }

          /**
           * Helper function to extract the file path of the plugin file from the
           * plugin slug, if the plugin is installed.
           *
           * @since 2.0.0
           *
           * @param string $slug Plugin slug (typically folder name) as provided by the developer.
           * @return string      Either file path for plugin if installed, or just the plugin slug.
           */
          protected function _get_plugin_basename_from_slug( $slug ) {
              $keys = array_keys( get_plugins() );
              $_keys = array();
              /** Try to get slug of activated plugin at first */
              foreach ( $keys as $key ) {
                  if ( preg_match( '|^' . $slug .'(-v?[0-9\.]+)?/|', $key )  ) {
                      if( is_plugin_active( $key ) ) {
                          return $key;
                      } else {
                          array_push( $_keys, $key );
                      }
                  }
              }
              /** Get key from any non activated but installed matched plugin */
              if( !empty( $_keys ) ) {
                return $_keys[0];
              }
              return $slug;
          }

          /**
           * Retrieve plugin data, given the plugin name.
           *
           * Loops through the registered plugins looking for $name. If it finds it,
           * it returns the $data from that plugin. Otherwise, returns false.
           *
           * @since 2.1.0
           *
           * @param string $name    Name of the plugin, as it was registered.
           * @param string $data    Optional. Array key of plugin data to return. Default is slug.
           * @return string|boolean Plugin slug if found, false otherwise.
           */
          protected function _get_plugin_data_from_name( $name, $data = 'slug' ) {

              foreach ( $this->plugins as $plugin => $values ) {
                  if ( $name == $values['name'] && isset( $values[$data] ) ) {
                      return $values[$data];
                  }
              }

              return false;

          }

          /**
           * Determine if we're on the TGMPA Install page.
           *
           * @since 2.1.0
           *
           * @return boolean True when on the TGMPA page, false otherwise.
           */
          public function is_tgmpa_page() {

              if ( isset( $_GET['page'] ) && $this->menu === $_GET['page'] ) {
                  return true;
              }

              return false;

          }

          /**
           * Forces plugin activation if the parameter 'force_activation' is
           * set to true.
           *
           * This allows theme authors to specify certain plugins that must be
           * active at all times while using the current theme.
           *
           * Please take special care when using this parameter as it has the
           * potential to be harmful if not used correctly. Setting this parameter
           * to true will not allow the specified plugin to be deactivated unless
           * the user switches themes.
           *
           * @since 2.2.0
           */
          public function force_activation() {
            // Set file_path parameter for any installed plugins.
            $this->populate_file_path();
            $installed_plugins = get_plugins();
            foreach ( $this->plugins as $plugin ) {
                // Oops, plugin isn't there so iterate to next condition.
                if ( isset( $plugin['force_activation'] ) && $plugin['force_activation'] && ! isset( $installed_plugins[$plugin['file_path']] ) ) {
                    continue;
                }
                // There we go, activate the plugin.
                elseif ( isset( $plugin['force_activation'] ) && $plugin['force_activation'] && is_plugin_inactive( $plugin['file_path'] ) ) {
                    activate_plugin( $plugin['file_path'] );
                }
            }
          }

          /**
           * Forces plugin deactivation if the parameter 'force_deactivation'
           * is set to true.
           *
           * This allows theme authors to specify certain plugins that must be
           * deactived upon switching from the current theme to another.
           *
           * Please take special care when using this parameter as it has the
           * potential to be harmful if not used correctly.
           *
           * @since 2.2.0
           */
          public function force_deactivation() {

              // Set file_path parameter for any installed plugins.
              $this->populate_file_path();

              foreach ( $this->plugins as $plugin ) {
                  // Only proceed forward if the paramter is set to true and plugin is active.
                  if ( isset( $plugin['force_deactivation'] ) && $plugin['force_deactivation'] && is_plugin_active( $plugin['file_path'] ) ) {
                      deactivate_plugins( $plugin['file_path'] );
                  }
              }

          }

          /**
           * Returns the singleton instance of the class.
           *
           * @since 2.4.0
           *
           * @return object The TGM_Plugin_Activation object.
           */
          public static function get_instance() {
            if ( ! isset( self::$instance ) && !( self::$instance instanceof TGM_Plugin_Activation ) ) {
              self::$instance = new TGM_Plugin_Activation();
            }
            return self::$instance;
          }

      }

  }

}
