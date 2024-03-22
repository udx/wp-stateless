<?php
/**
 * Admin Notices Handler
 *
 * @namespace wpCloud\StatelessMedia
 *
 * This file can be used to bootstrap any of the UD plugins, it essentially requires that you have
 * a core file which will be called after 'plugins_loaded'. In addition, if the core class has
 * 'activate' and 'deactivate' functions, then those will be called automatically by this class.
 */

namespace wpCloud\StatelessMedia {
  
  if( !class_exists( 'wpCloud\StatelessMedia\Errors' ) ) {
    
    /**
     * 
     * @author: peshkov@UD
     */
    class Errors extends \UsabilityDynamics\WP\Scaffold {
    
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
       * Notices
       *
       * @used admin_notices
       * @public
       * @property $messages
       * @type array
       */
      private $notices = array();
      
      /**
       * Action Links in Footer
       *
       * @used admin_notices
       * @public
       * @property $messages
       * @type array
       */
      private $action_links = array(
        'errors' => null,
        'notices' => null,
      );

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
        add_action( 'wp_ajax_stateless_notice_dismiss', array( $this, 'dismiss_notices' ) );
        add_action( 'wp_ajax_stateless_enable_notice_button_action', array( $this, 'stateless_enable_notice_button_action' ) );
      }
      
      /**
       * Prevents adding duplicating messages
       * Some triggers may happen few times during site load (like 'switch_blog' hook) 
       * adding the same message several times is not necessary
       *
       * @param string $collection
       * @param string $message
       */
      private function add_message( &$collection, $message ) {
        $exiting_keys = array_column($collection, 'key');

        if ( in_array( $message['key'], $exiting_keys ) ) {
          return;
        }

        $collection[] = $message;
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
            $this->add_message($this->errors, $message);
            break;
          case 'message':
          case 'warning':
          case 'notice':
            if(!is_array($message)){
              $message = array( 
                'title' => sprintf( __( '%s has the following notice:', $this->domain ), esc_html($this->name) ),
                'message' => $message,
                'button' => null,
              );
            }

            if(empty($message['key'])){
              $message['key'] = md5( $message['title'] );
            }
            $this->add_message($this->notices, $message);
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
          case 'warning':
          case 'notice':
            $this->action_links[ 'notices' ][] = $link;
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

        wp_enqueue_style("stateless-error-style", ud_get_stateless_media()->path('static/styles/error-notice.css'));
        //enqueue dismiss js for ajax requests
        $script_path = \UsabilityDynamics\WP\Utility::path( 'static/scripts/ud-dismiss.js', 'url' );
        wp_enqueue_script( "sateless-error-notice-js", ud_get_stateless_media()->path( 'static/scripts/error-notice.js', 'url' ), array( 'jquery' ) );
        wp_enqueue_script( "ud-dismiss", $script_path, array( 'jquery' ) );
        wp_localize_script( "ud-dismiss", "_ud_vars", array(
            "ajaxurl" => admin_url( 'admin-ajax.php' ),
        ));
        wp_localize_script( "sateless-error-notice-js", "stateless_error_notice_vars", array(
          "dismiss_nonce" => wp_create_nonce( 'stateless_notice_dismiss' ),
          "enable_action_nonce" => wp_create_nonce( 'stateless_enable_notice_button_action' ),
        ));

        //** Don't show the message if the user has no enough permissions. */
        if ( ! function_exists( 'wp_get_current_user' ) ) {
          require_once( ABSPATH . 'wp-includes/pluggable.php' );
        }
        
        $default_show = true;

        if (
          empty( $this->args['type'] ) ||
          ( $this->args['type'] == 'plugin' && !current_user_can( 'activate_plugins' ) ) ||
          ( $this->args['type'] == 'theme' && !current_user_can( 'switch_themes' ) )
        ) {
          $default_show = false;
        }

        //** Don't show the message if on a multisite and the user isn't a super user. */
        if ( is_multisite() && ! is_super_admin() ) {
          $default_show = false;
        }

        $errors = apply_filters( 'ud:errors:admin_notices', $this->errors, $this->args );
        $notices = apply_filters( 'stateless:notices:admin_notices', $this->notices, $this->args );

        //** Errors Block */
        if( $default_show && !empty( $errors ) && is_array( $errors ) ) {
          $message = '<ul style="none;"><li>' . implode( '</li><li>', $errors ) . '</li></ul>';
          $data = array(
            'title' => sprintf( __( '%s is not active due to following errors:', $this->domain ), esc_html($this->name) ),
            'class' => 'error',
            'message' => $message,
            'action_links' => !empty($this->action_links[ 'errors' ])?$this->action_links[ 'errors' ]:null,
          );
          
          include ud_get_stateless_media()->path( '/static/views/error-notice.php', 'dir' );
        }

        $has_notice = false;
        //** Determine if warning has been dismissed */
        if ( ! empty( $notices ) && is_array( $notices ) ) {
          //** Warnings Block */
          foreach ($notices as $notice ) {
            if ( get_option( 'dismissed_notice_' . $notice['key'] )){
              continue;
            }

            // Check additional capabilities
            $capability = isset( $notice['capability'] ) && !empty( $notice['capability'] ) ? $notice['capability'] : null;

            if ( ( !$default_show && empty($capability) ) || ( !empty($capability) && !current_user_can($capability) ) ) {
              continue;
            }

            // Additional HTML classes
            $classes = [];

            if ( isset($notice['classes']) ) {
              $classes = $notice['classes'];

              if ( !is_array($classes) ) {
                $classes = [$classes];
              }
            }

            $classes[] = 'notice';

            $data = wp_parse_args($notice, array(
              'title' => '',
              'class' => implode(' ', $classes),
              'message' => '',
              'button' => '',
              'button_link' => '#',
              'key' => '',
              'action_links' => $this->action_links[ 'notices' ],
              'dismiss' => true,
            ));
            
            $button_capability = isset( $notice['button_capability'] ) && !empty( $notice['button_capability'] ) ? $notice['button_capability'] : null;
            if ( !empty($button_capability) && !current_user_can($button_capability) ) {
              $data['button'] = '';
            }

            include ud_get_stateless_media()->path( '/static/views/error-notice.php', 'dir' );
            
            $has_notice = true;
          }
        }

      }

      /**
       * dismiss the notice ajax callback
       * @throws \Exception
       */
      public function dismiss_notices() {
        check_ajax_referer('stateless_notice_dismiss');

        if ( !is_user_logged_in() ) {
          wp_send_json_error( array( 'error' => __( 'You are not allowed to do this action.', $this->domain ) ) );
        }

        $response = array(
          'success' => '0',
          'error' => __( 'There was an error in request.', $this->domain ),
        );

        $error = false;

        $option_key = isset($_POST['key']) ? sanitize_key($_POST['key']) : '';

        if ( strpos($option_key, 'dismissed_') !== 0 ) {
          $response['error'] = __( 'Invalid key', $this->domain );
          $error = true;
        }

        if ( !$error && update_option( $option_key, time() ) ) {
          do_action('wp_stateless_notice_dismissed', $option_key);

          $response['success'] = '1';
          $response['error'] = null;
        }

        wp_send_json( $response );
      }

      /**
       * Action for the stateless_enable_notice_button_action ajax callback
       * @throws \Exception
       */
      public function stateless_enable_notice_button_action(){
        check_ajax_referer('stateless_enable_notice_button_action');

        if ( !is_user_logged_in() ) {
          wp_send_json_error( array( 'error' => __( 'You are not allowed to do this action.', $this->domain ) ) );
        }

        $response = array(
          'success' => '1',
        );
        
        $error = false;

        if( empty($_POST['key']) ) {
          $response['success'] = '0';
          $response['error'] = __( 'Invalid key', $this->domain );
        }
        else{
          $option_key = sanitize_key($_POST['key']);
          $compatibility = Module::get_module($option_key);
          if(!empty($compatibility['self']) && is_callable(array($compatibility['self'], 'enable_compatibility'))){
            $response['success'] = $compatibility['self']->enable_compatibility();
          }
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
