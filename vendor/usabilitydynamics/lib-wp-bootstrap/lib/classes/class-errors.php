<?php
/**
 * Admin Notices Handler
 *
 * @namespace UsabilityDynamics
 *
 * This file can be used to bootstrap any of the UD plugins, it essentially requires that you have
 * a core file which will be called after 'plugins_loaded'. In addition, if the core class has
 * 'activate' and 'deactivate' functions, then those will be called automatically by this class.
 */
namespace UsabilityDynamics\WP {

  if( !class_exists( 'UsabilityDynamics\WP\Errors' ) ) {

    /**
     * 
     * @author: peshkov@UD
     */
    class Errors extends Scaffold {
    
      /**
       * Errors
       *
       * @used admin_notices
       * @public
       * @property $errors
       * @type array
       */
      private $errors = array();
      
      /**
       * Messages
       *
       * @used admin_notices
       * @public
       * @property $messages
       * @type array
       */
      private $messages = array();

      /**
       * Warnings
       *
       * @used admin_notices
       * @public
       * @property $messages
       * @type array
       */
      private $warnings = array();
      
      /**
       * Action Links in Footer
       *
       * @used admin_notices
       * @public
       * @property $messages
       * @type array
       */
      private $action_links = array();

      /**
       * Dismiss action link is available or not.
       *
       * @var bool
       */
      private $dismiss = true;
      
      /**
       *
       */
      public function __construct( $args ) {
        parent::__construct( $args );
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
        add_action( 'wp_ajax_ud_dismiss', array( $this, 'dismiss_notices' ) );
      }
      
      /**
       * Add new message for admin notices
       *
       * @param string $message
       * @param string $type Values: 'error', 'message', 'warning'
       * @author peshkov@UD
       */
      public function add( $message, $type = 'error' ) {
        switch( $type ) {
          case 'error':
            $this->errors[] = $message;
            break;
          case 'message':
            $this->messages[] = $message;
            break;
          case 'warning':
            $this->warnings[] = $message;
            break;
        }
      }
      
      /**
       * Add footer link to specific ( errors|messages|wanrnings ) block
       *
       * @author peshkov@UD
       */
      public function add_action_link( $link, $type = 'error' ) {
        switch( $type ) {
          case 'error':
            $this->action_links[ 'errors' ][] = $link;
            break;
          case 'message':
            $this->action_links[ 'messages' ][] = $link;
            break;
          case 'message':
            $this->action_links[ 'warnings' ][] = $link;
            break;
        }
      }
      
      /**
       * Determine if errors exist
       *
       * @author peshkov@UD
       */
      public function has_errors() {
        return !empty( $this->errors ) ? true : false;
      }
      
      /**
       * Renders admin notes in case there are errors or notices on bootstrap init
       *
       * @author peshkov@UD
       */
      public function admin_notices() {
        global $wp_version;

        //** Don't show the message if the user has no enough permissions. */
        if ( ! function_exists( 'wp_get_current_user' ) ) {
          require_once( ABSPATH . 'wp-includes/pluggable.php' );
        }
        
        if(
          empty( $this->args['type'] ) ||
          ( $this->args['type'] == 'plugin' && !current_user_can( 'activate_plugins' ) ) ||
          ( $this->args['type'] == 'theme' && !current_user_can( 'switch_themes' ) )
        ) {
          return;
        }

        //** Don't show the message if on a multisite and the user isn't a super user. */
        if ( is_multisite() && ! is_super_admin() ) {
          return;
        }
        //** Ignore messages on TGM Plugin Activation page */
        if( TGM_Plugin_Activation::get_instance()->is_tgmpa_page() ) {
          return;
        }
        
        $errors = apply_filters( 'ud:errors:admin_notices', $this->errors, $this->args );
        $messages = apply_filters( 'ud:messages:admin_notices', $this->messages, $this->args );
        $warnings = apply_filters( 'ud:warnings:admin_notices', $this->warnings, $this->args );
        
        if( !empty( $errors ) || !empty( $messages ) || !empty( $warnings ) ) {
          echo "<style>.ud-admin-notice a { text-decoration: underline !important; } .ud-admin-notice { display: block !important; } .ud-admin-notice.update-nag { border-color: #ffba00 !important; }</style>";
        }

        //** Errors Block */
        if( !empty( $errors ) && is_array( $errors ) ) {
          $message = '<ul style="list-style:disc inside;"><li>' . implode( '</li><li>', $errors ) . '</li></ul>';
          $message = sprintf( __( '<p><b>%s</b> is not active due to following errors:</p> %s', $this->domain ), $this->name, $message );
          if( !empty( $this->action_links[ 'errors' ] ) && is_array( $this->action_links[ 'errors' ] ) ) {
            $message .= '<p>' . implode( ' | ', $this->action_links[ 'errors' ] ) . '</p>';
          }
          echo '<div class="ud-admin-notice error fade" style="padding:11px;">' . $message . '</div>';
        }

        //** Determine if warning has been dismissed */
        $warning_dismissed = get_option( ( 'dismissed_warning_' . sanitize_key( $this->name ) ) );
        $show_warnings = $this->check_dismiss_time( $warning_dismissed );
        if ( $show_warnings && ! empty( $warnings ) && is_array( $warnings ) ) {
          //** Warnings Block */
          $message = '<ul style="list-style:disc inside;"><li>' . implode( '</li><li>', $warnings ) . '</li></ul>';
          $message = sprintf( __( '<p><b>%s</b> has the following warnings:</p> %s', $this->domain ), $this->name, $message );
          if( $this->dismiss ) {
            $this->action_links[ 'warnings' ][] = '<a class="dismiss-warning dismiss" data-key="dismissed_warning_' . sanitize_key( $this->name ).'" href="#">' . __( 'Dismiss this warning', $this->domain ) . '</a>';
          }
          if( !empty( $this->action_links[ 'warnings' ] ) && is_array( $this->action_links[ 'warnings' ] ) ) {
            $message .= '<p>' . implode( ' | ', $this->action_links[ 'warnings' ] ) . '</p>';
          }
          echo '<div class="ud-admin-notice updated update-nag fade" style="padding:11px;">' . $message . '</div>';
        }

        //** Determine if message has been dismissed */
        $message_dismissed = get_option( ( 'dismissed_notice_' . sanitize_key( $this->name ) ) );
        if ( empty( $message_dismissed ) ) {
          //** Notices Block */
          if( !empty( $messages ) && is_array( $messages ) ) {
            $message = '<ul style="list-style:disc inside;"><li>' . implode( '</li><li>', $messages ) . '</li></ul>';
            if( !empty( $errors ) ) {
              $message = sprintf( __( '<p><b>%s</b> has the following additional notices:</p> %s', $this->domain ), $this->name, $message );
            } else {
              $message = sprintf( __( '<p><b>%s</b> is active, but has the following notices:</p> %s', $this->domain ), $this->name, $message );
            }
            if( $this->dismiss ) {
              $this->action_links[ 'messages' ][] = '<a class="dismiss-notice dismiss" data-key="dismissed_notice_' . sanitize_key( $this->name ).'" href="#">' . __( 'Dismiss this notice', $this->domain ) . '</a>';
            }
            $message .= '<p>' . implode( ' | ', $this->action_links[ 'messages' ] ) . '</p>';
            echo '<div class="ud-admin-notice updated fade" style="padding:11px;">' . $message . '</div>';
          }
        }

        if ( $show_warnings || empty( $message_dismissed ) ) {
          //enqueue dismiss js for ajax requests
          $script_path = Utility::path( 'static/scripts/ud-dismiss.js', 'url' );
          wp_enqueue_script( "ud-dismiss", $script_path, array( 'jquery' ) );
          wp_localize_script( "ud-dismiss", "_ud_vars", array(
              "ajaxurl" => admin_url( 'admin-ajax.php' ),
          ) );
        }
        
      }

      /**
       * dismiss the notice ajax callback
       * @throws \Exception
       */
      public function dismiss_notices(){
        $response = array(
          'success' => '0',
          'error' => __( 'There was an error in request.', $this->domain ),
        );
        $error = false;

        if( empty($_POST['key']) ) {
          $response['error'] = __( 'Invalid key', $this->domain );
          $error = true;
        }

        if ( ! $error && update_option( ( $_POST['key'] ), time() ) ) {
          $response['success'] = '1';
        }

        wp_send_json( $response );
      }

      /**
       * Check dismiss notice timestamp if greater than 24 hrs
       *
       * @param string $time
       *
       * @return bool
       */
      public function check_dismiss_time( $time = '' ) {
        if( empty( $time ) ) {
          return true;
        }
        $current_time = time();
        $diff = $current_time - 86400;
        if ( $diff > (int)$time ) {
          return true;
        }
        return false;
      }
      
    }
  
  }
  
}
