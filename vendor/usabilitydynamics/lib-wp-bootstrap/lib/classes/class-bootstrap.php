<?php
/**
 * Bootstrap
 *
 * @namespace UsabilityDynamics
 *
 * This file is being used to bootstrap WordPress theme.
 */
namespace UsabilityDynamics\WP {

  if( !class_exists( 'UsabilityDynamics\WP\Bootstrap' ) ) {

    /**
     * Bootstrap the theme in WordPress.
     *
     * @class Bootstrap
     * @author: peshkov@UD
     */
    class Bootstrap extends Scaffold {
    
      /**
       * Schemas
       *
       * @public
       * @property schema
       * @var array
       */
      public $schema = null;
      
      /**
       * Absolute path to schema ( composer.json )
       *
       * @public
       * @property schema_path
       * @var array
       */
      public $schema_path = null;
      
      /**
       * Admin Notices handler object
       *
       * @public
       * @property errors
       * @var object UsabilityDynamics\WP\Errors object
       */
      public $errors = false;
      
      /**
       * Settings
       *
       * @public
       * @static
       * @property $settings
       * @type \UsabilityDynamics\Settings object
       */
      public $settings = null;
      
      /**
       * Path to main plugin/theme file
       *
       * @public
       * @property boot_file
       * @var array
       */
      public $boot_file = false;
      
      /**
       * Constructor
       * Attention: MUST NOT BE CALLED DIRECTLY! USE get_instance() INSTEAD!
       *
       * @author peshkov@UD
       */
      protected function __construct( $args ) {
        parent::__construct( $args );
        //** Define our Admin Notices handler object */
        $this->errors = new Errors( array_merge( $args, array(
          'type' => $this->type
        ) ) );
        //** Determine if Composer autoloader is included and modules classes are up to date */
        $this->composer_dependencies();
        //** Determine if plugin/theme requires or recommends another plugin(s) */
        $this->plugins_dependencies();
        // Maybe run install or upgrade processes.
        $this->maybe_run_upgrade_process();
        //** Set install/upgrade pages if needed */
        $this->define_splash_pages();
        //** Maybe need to show UD splash page. Used static functions intentionaly. */
        if ( !has_action( 'admin_init', array( Dashboard::get_instance(), 'maybe_ud_splash_page' ) ) ) {
          add_action( 'admin_init', array( Dashboard::get_instance(), 'maybe_ud_splash_page' ) );
        }
        if ( !has_action( 'admin_menu', array( Dashboard::get_instance(), 'add_ud_splash_page') ) ) {
          add_action( 'admin_menu', array( Dashboard::get_instance(), 'add_ud_splash_page') );
        }
        add_action( 'wp_ajax_ud_bootstrap_dismiss_notice', array( $this, 'ud_bootstrap_dismiss_notice' ) );
      }
      
      /**
       * Initialize application.
       * Redeclare the method in final class!
       *
       * @author peshkov@UD
       */
      public function init() {}
      
      /**
       * Determine if errors exist
       * Just wrapper.
       */
      public function has_errors() {
        return $this->errors->has_errors();
      }
      
      /**
       * @param string $key
       * @param mixed $value
       *
       * @author peshkov@UD
       * @return \UsabilityDynamics\Settings
       */
      public function set( $key = null, $value = null ) {
        if( !is_object( $this->settings ) || !is_callable( array( $this->settings, 'set' ) ) ) {
          return false;
        }
        return $this->settings->set( $key, $value );
      }

      /**
       * @param string $key
       * @param mixed $default
       *
       * @author peshkov@UD
       * @return \UsabilityDynamics\type
       */
      public function get( $key = null, $default = null ) {
        if( !is_object( $this->settings ) || !is_callable( array( $this->settings, 'get' ) ) ) {
          return $default;
        }
        return $this->settings->get( $key, $default );
      }
      
      /**
       * Returns specific schema from composer.json file.
       *
       * @param string $file Path to file
       * @author peshkov@UD
       * @return mixed array or false
       */
      public function get_schema( $key = '' ) {
        if( $this->schema === null ) {
          if( !empty( $this->schema_path ) && file_exists( $this->schema_path ) ) {
            $this->schema = (array)\UsabilityDynamics\Utility::l10n_localize( json_decode( file_get_contents( $this->schema_path ), true ), (array)$this->get_localization() );
          }
        }
        //** Break if composer.json does not exist */
        if( !is_array( $this->schema ) ) {
          return false;
        }
        //** Resolve dot-notated key. */
        if( strpos( $key, '.' ) ) {
          $current = $this->schema;
          $p = strtok( $key, '.' );
          while( $p !== false ) {
            if( !isset( $current[ $p ] ) ) {
              return false;
            }
            $current = $current[ $p ];
            $p = strtok( '.' );
          }
          return $current;
        } 
        //** Get default key */
        else {
          return isset( $this->schema[ $key ] ) ? $this->schema[ $key ] : false;
        }
      }
      
      /**
       * Return localization's list.
       *
       * Example:
       * If schema contains l10n.{key} values:
       *
       * { 'config': 'l10n.hello_world' }
       *
       * the current function should return something below:
       *
       * return array(
       *   'hello_world' => __( 'Hello World', $this->domain ),
       * );
       *
       * @author peshkov@UD
       * @return array
       */
      public function get_localization() {
        return array();
      }

      /**
       * Determine if product is just installed or upgraded
       * and run install/upgrade processes
       *
       * @author peshkov@UD
       */
      protected function maybe_run_upgrade_process() {
        //** Determine what to show depending on version installed */
        $version = get_option($this->slug . '-current-version', 0);
        $this->old_version = $version;
        //** Just installed */
        if (!$version) {
          /* Run Install handlers */
          add_action( 'plugins_loaded', array( $this, '_run_install_process' ), 0 );
        }
        //** Upgraded */
        elseif (version_compare($version, $this->args['version']) == -1) {
          /* Run Upgrade handlers */
          add_action( 'plugins_loaded', array( $this, '_run_upgrade_process' ), 0 );
        }
        // Need to save current version on plugins_loaded action,
        // unless _run_install_process and _run_upgrade_process not get called.
        add_action( 'plugins_loaded', array( $this, 'save_version_no' ), 100 );
      }

      /**
       * Saving version no to database.
       * 
       */
      public function save_version_no($value=''){
        update_option( $this->slug . '-current-version', $this->args['version'] );
      }

      /**
       * Installation Handler
       * Internal method. Use run_install_process() instead
       */
      public function _run_install_process() {
        /* Delete 'Install/Upgrade' notice 'dismissed' information */
        delete_option( sanitize_key( 'dismiss_' . $this->slug . '_' . str_replace( '.', '_', $this->args['version'] ) . '_notice' ) );
        /* Delete 'Bootstrap' notice 'dismissed' information */
        delete_option( 'dismissed_notice_' . sanitize_key( $this->name ) );
        $this->run_install_process();
      }

      /**
       * Upgrade Handler
       * Internal method. Use run_upgrade_process() instead
       */
      public function _run_upgrade_process() {
        /* Delete 'Install/Upgrade' notice 'dismissed' information */
        delete_option( sanitize_key( 'dismiss_' . $this->slug . '_' . str_replace( '.', '_', $this->args['version'] ) . '_notice' ) );
        /* Delete 'Bootstrap' notice 'dismissed' information */
        delete_option( 'dismissed_notice_' . sanitize_key( $this->name ) );
        $this->run_upgrade_process();
      }

      /**
       * Run Install Process.
       *
       * Re-define the function in child.
       */
      public function run_install_process() {}

      /**
       * Run Upgrade Process.
       *
       * Re-define the function in child.
       */
      public function run_upgrade_process() {}

      /**
       * Define splash pages for plugins if needed
       * And Renders Admin Notice about installed product
       *
       * @return boolean
       * @author korotkov@UD
       * @author peshkov@UD
       */
      public function define_splash_pages() {
        //** If not defined in schemas or not determined - skip */
        if ( !$splashes = $this->get_schema( 'extra.splashes' ) ) {
          return false;
        }

        foreach( (array)$splashes as $splash => $shortpath ) {
          $path = $this->type == 'theme' ? get_template_directory() . '/' . ltrim( $shortpath, '/\\' ) : $this->path( $shortpath, 'dir' );
          if( !file_exists( $path ) ) {
            unset( $splashes[ $splash ] );
          }
        }

        //** If no splash templates files or missed 'install' splash - skip */
        if( empty( $splashes ) || !isset( $splashes[ 'install' ] ) ) {
          return false;
        }

        $page = false;

        //** Determine what to show depending on version installed */
        $version = get_option( $this->slug . '-splash-version', 0 );

        //** Just installed */
        if( !$version ) {
          $page = 'install';
        }
        //** Upgraded */
        elseif ( version_compare( $version,  $this->args['version'] ) == -1 ) {
          if( isset( $splashes[ 'upgrade' ] ) ) {
            $page = 'upgrade';
          } else {
            $page = 'install';
          }
        }
        //** In other case do not do this */
        else {

          /**
           * Maybe Render Install Notice
           * about current instance
           */

          $option = sanitize_key( 'dismiss_' . $this->slug . '_' . str_replace( '.', '_', $this->args['version'] ) . '_notice' );

          if(
            isset( $_REQUEST[ 'page' ] ) &&
            $_REQUEST[ 'page' ] == Dashboard::get_instance()->page_slug &&
            isset( $_REQUEST[ 'slug' ] ) &&
            $_REQUEST[ 'slug' ] == $this->slug
          ) {

            /** Dismiss Admin Notice */
            if( isset( $_REQUEST[ 'dismiss' ] ) ) {

              update_option( $option, array(
                'slug' => $this->slug,
                'type' => $this->type,
                'version' => $this->args['version']
              ) );

              if( !function_exists( 'wp_redirect' ) ) {
                require_once( ABSPATH . 'wp-includes/pluggable.php' );
              }

              if( !empty( $_SERVER[ 'HTTP_REFERER' ] ) ) {
                wp_redirect( $_SERVER[ 'HTTP_REFERER' ] );
              } else {
                wp_redirect( admin_url( 'plugins.php' ) );
              }
              exit;

            }

            /** Add information about product to Dashboard page */
            else {

              if( isset( $splashes[ 'upgrade' ] ) ) {
                $page = 'upgrade';
              } else {
                $page = 'install';
              }

            }

          }

          if( !get_option( $option ) ) {
            add_action( 'admin_notices',  array( $this, 'render_upgrade_notice' ), 1 );
          }

          if( empty( $page ) ) {
            return false;
          }

        }

        $content = $this->root_path . ltrim( $splashes[$page], '/\\' );

        //** Abort if no files exist */
        if ( !file_exists( $content ) ) {
          return false;
        }

        //** Push data to temp transient */
        $_current_pages_to_show = get_transient( Dashboard::get_instance()->transient_key );

        //** If empty - create */
        if ( !$_current_pages_to_show ) {
          set_transient( Dashboard::get_instance()->transient_key, array(
            $this->slug => array(
              'name' => $this->name,
              'content' => $content,
              'version' => $this->args['version']
            )
          ), 30 );
        }
        //** If not empty - update */
        else {
          $_current_pages_to_show[$this->slug] = array(
            'name' => $this->name,
            'content' => $content,
            'version' => $this->args['version']
          );
          set_transient( Dashboard::get_instance()->transient_key, $_current_pages_to_show, 30 );
        }

        set_transient( Dashboard::get_instance()->need_splash_key, Dashboard::get_instance()->transient_key, 30 );

      }

      /**
       * Renders Upgrade Notice.
       *
       */
      public function render_upgrade_notice() {
        
        if( $this->type == 'theme' ) {
          if( !current_user_can( 'switch_themes' ) ) {
            return;
          }
          $icon = file_exists( get_template_directory() . '/static/images/icon.png' ) ? get_template_directory_uri() . '/static/images/icon.png' : false;
        } else {
          if( !current_user_can( 'activate_plugins' ) ) {
            return;
          }
          $icon = file_exists( $this->path( 'static/images/icon.png', 'dir' ) ) ? $this->path( 'static/images/icon.png', 'url' ) : false;
        }

        ob_start();
        $vars = apply_filters( 'ud::bootstrap::upgrade_notice::vars', array(
          'content' => false,
          'icon' => $icon,
          'name' => $this->name,
          'type' => $this->type,
          'slug' => $this->slug,
          'version' => $this->args['version'],
          'dashboard_link' => admin_url( 'index.php?page='. Dashboard::get_instance()->page_slug . '&slug=' . $this->slug ),
          'dismiss_link' => admin_url( 'index.php?page='. Dashboard::get_instance()->page_slug . '&slug=' . $this->slug . '&dismiss=1' ),
          'home_link' => !empty( $this->schema[ 'homepage' ] ) ? $this->schema[ 'homepage' ] : false,
        ) );
        extract( $vars );
        require( dirname( dirname( __DIR__ ) ) . '/static/views/install_notice.php' );
        $content = ob_get_clean();
        echo apply_filters( 'ud::bootstrap::upgrade_notice::template', $content, $this->slug, $vars );
      }

      /**
       * Check plugins requirements
       *
       * @author peshkov@UD
       */
      public function check_plugins_requirements() {
        //** Determine if we have TGMA Plugin Activation initialized. */
        $is_tgma = $this->is_tgma;
        if( $is_tgma ) {
          $tgma = TGM_Plugin_Activation::get_instance();
          //** Maybe get TGMPA notices. */
          $notices = $tgma->notices( get_class( $this ) );
          if( !empty( $notices[ 'messages' ] ) && is_array( $notices[ 'messages' ] ) ) {
            $error_links = false;
            $message_links = false;
            foreach( $notices[ 'messages' ] as $m ) {
              if( $m[ 'type' ] == 'error' ) $error_links = true;
              elseif( $m[ 'type' ] == 'message' ) $message_links = true;
              $this->errors->add( $m[ 'value' ], $m[ 'type' ] );
            }
            //** Maybe add footer action links to errors and|or notices block. */
            if( !empty( $notices[ 'links' ] ) && is_array( $notices[ 'links' ] ) ) {
              foreach( $notices[ 'links' ] as $type => $links ) {
                foreach( $links as $link ) {
                  $this->errors->add_action_link( $link, $type );
                }
              }
            }
          }
        }
      }

      /**
       * Maybe determines if Composer autoloader is included and modules classes are up to date
       *
       * @author peshkov@UD
       */
      private function composer_dependencies() {
        $dependencies = $this->get_schema( 'extra.schemas.dependencies.modules' );
        if( !empty( $dependencies ) && is_array( $dependencies ) ) {
          foreach( $dependencies as $module => $classes ) {
            if( !empty( $classes ) && is_array( $classes ) ) {
              foreach( $classes as $class => $v ) {
                if( !class_exists( $class ) ) {
                  $this->errors->add( sprintf( __( 'Module <b>%s</b> is not installed or the version is old, class <b>%s</b> does not exist.', $this->domain ), $module, $class ) );
                  continue;
                }
                if ( '*' != trim( $v ) && ( !property_exists( $class, 'version' ) || $class::$version < $v ) ) {
                  $this->errors->add( sprintf( __( 'Module <b>%s</b> should be updated to the latest version, class <b>%s</b> must have version <b>%s</b> or higher.', $this->domain ), $module, $class, $v ) );
                }
              }
            }
          }
        }
      }
      
      /**
       * Determine if plugin/theme requires or recommends another plugin(s)
       *
       * @author peshkov@UD
       */
      private function plugins_dependencies() {
        /** 
         * Dependencies must be checked before plugins_loaded hook to prevent issues!
         * 
         * The current condition fixes incorrect behaviour on custom 'Install Plugins' page
         * after activation plugin which has own dependencies.
         * 
         * The condition belongs to WordPress 4.3 and higher.
         */
        if( did_action( 'plugins_loaded' ) && $this->type == 'plugin' ) {
          return;
        }
        $plugins = $this->get_schema( 'extra.schemas.dependencies.plugins' );
        if( !empty( $plugins ) && is_array( $plugins ) ) {
          $tgma = TGM_Plugin_Activation::get_instance();
          foreach( $plugins as $plugin ) {
            $plugin[ '_referrer' ] = get_class( $this );
            $plugin[ '_referrer_name' ] = $this->name;
            $tgma->register( $plugin );
          }
          $this->is_tgma = true;
        }
      }
      
      
      /**
       * Defines License Client if 'licenses' schema is set
       *
       * @author peshkov@UD
       */
      protected function define_license_client() {
        //** Break if we already have errors to prevent fatal ones. */
        if( $this->has_errors() ) {
          return false;
        }
        //** Be sure we have licenses scheme to continue */
        $schema = $this->get_schema( 'extra.schemas.licenses.client' );
        if( !$schema ) {
          return false;
        }
        //** Licenses Manager */
        if( !class_exists( '\UsabilityDynamics\UD_API\Bootstrap' ) ) {
          $this->errors->add( __( 'Class \UsabilityDynamics\UD_API\Bootstrap does not exist. Be sure all required plugins and (or) composer modules installed and activated.', $this->domain ) );
          return false;
        }
        $args = $this->args;
        $args = array_merge( $args, array(
          'type' => $this->type,
          'name' => $this->name,
          'slug' => $this->slug,
          'referrer_slug' => $this->slug,
          'domain' => $this->domain,
          'errors_callback' => array( $this->errors, 'add' ),
        ), $schema );
        if( empty( $args[ 'screen' ] ) ) {
          $this->errors->add( __( 'Licenses client can not be activated due to invalid \'licenses\' schema.', $this->domain ) );
        }
        $this->client = new \UsabilityDynamics\UD_API\Bootstrap( $args );
      }
      
      /**
       * Defines License Manager if 'license' schema is set
       *
       * @author peshkov@UD
       */
      public function define_license_manager() {
        //** Break if we already have errors to prevent fatal ones. */
        if( $this->has_errors() ) {
          return false;
        }
        //** Be sure we have license scheme to continue */
        $schema = $this->get_schema( 'extra.schemas.licenses.product' );
        if( !$schema ) {
          return false;
        }
        if( empty( $schema[ 'product_id' ] ) || ( empty( $schema[ 'referrer' ] ) && $this->type !== 'theme' ) ) {
          $this->errors->add( __( 'Product requires license, but product ID and (or) referrer is undefined. Please, be sure, that license schema has all required data.', $this->domain ), 'message' );
        }
        $schema = array_merge( (array)$schema, array(
          'type' => $this->type,
          'name' => $this->name,
          'boot_file' => $this->boot_file,
          'errors_callback' => array( $this->errors, 'add' )
        ) );
        //** Licenses Manager */
        if( !class_exists( '\UsabilityDynamics\UD_API\Manager' ) ) {
          //$this->errors->add( __( 'Class \UsabilityDynamics\UD_API\Manager does not exist. Be sure all required plugins installed and activated.', $this->domain ), 'message' );
          return false;
        }
        $this->license_manager = new \UsabilityDynamics\UD_API\Manager( $schema );
        return true;
      }

      public function ud_bootstrap_dismiss_notice() {
        $response = array(
            'success' => '0',
            'error' => __( 'There was an error in request.', $this->domain ),
        );
        $error = false;

        if( empty( $_POST['key'] ) ||
            empty( $_POST['slug'] ) ||
            empty( $_POST['type'] ) ||
            empty( $_POST['version'] )
        ) {
          $response['error'] = __( 'Invalid values', $this->domain );
          $error = true;
        }

        if ( ! $error && update_option( ( $_POST['key'] ), array(
                'slug' => $_POST['slug'],
                'type' => $_POST['type'],
                'version' => $_POST['version'],
            ) ) ) {
          $response['success'] = '1';
        }

        wp_send_json( $response );
      }
      
    }
  
  }
  
}
