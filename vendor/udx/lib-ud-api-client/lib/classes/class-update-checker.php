<?php
/**
 * Update Checker
 *
 * @namespace UsabilityDynamics
 *
 */
namespace UsabilityDynamics\UD_API {

  if( !class_exists( 'UsabilityDynamics\UD_API\Update_Checker' ) ) {

    /**
     * 
     * @author: peshkov@UD
     */
    class Update_Checker {
    
      /**
       *
       */
      public static $version = '1.0.0';
      
      /**
       * URL to access the Update API Manager.
       */
      private $upgrade_url;
      
      /**
       * Changelog
       */
      private $changelog;
      
      /**
       * Instance type ( plugin or theme )
       */
      private $type;
      
      /**
       * same as plugin slug. if a theme use a theme name like 'twentyeleven'
       */
      private $name;
      
      /**
       * Path to plugin/theme file
       */
      private $file; 
      
      /**
       * Software Title
       */
      private $product_id;
      
      /**
       * API License Key
       */
      private $api_key;
      
      /**
       * License Email
       */
      private $activation_email; 
      
      /**
       * URL to renew a license
       */
      private $renew_license_url;
      
      /**
       * Instance ID (unique to each blog activation)
       */
      private $instance;
      
      /**
       * blog domain name
       */
      private $blog;
      
      /**
       *
       */
      private $software_version;
      
      /**
       * 'theme' or 'plugin'
       */
      private $plugin_or_theme;
      
      /**
       * localization for translation
       */
      private $text_domain;
      
      /**
       * Used to send any extra information.
       */
      private $extra;
      
      /**
       * Errors
       */
      public $errors;
      
      /**
       * Error handler.
       *
       */
      public $errors_callback;

      /**
       * Constructor.
       *
       * @access public
       * @since  1.0.0
       * @return void
       */
      public function __construct( $args, $errors_callback = false ) {
        //** API data */
        $this->upgrade_url 			  = isset( $args[ 'upgrade_url' ] ) ? $args[ 'upgrade_url' ] : false;
        $this->type 			        = isset( $args[ 'type' ] ) ? $args[ 'type' ] : false;
        $this->name 			        = isset( $args[ 'name' ] ) ? $args[ 'name' ] : false;
        $this->file 			        = isset( $args[ 'file' ] ) ? $args[ 'file' ] : false;
        $this->product_id 			  = isset( $args[ 'product_id' ] ) ? $args[ 'product_id' ] : false;
        $this->api_key 				    = isset( $args[ 'api_key' ] ) ? $args[ 'api_key' ] : false;
        $this->activation_email   = isset( $args[ 'activation_email' ] ) ? $args[ 'activation_email' ] : false;
        $this->renew_license_url 	= isset( $args[ 'renew_license_url' ] ) ? $args[ 'renew_license_url' ] : false;
        $this->instance 			    = isset( $args[ 'instance' ] ) ? $args[ 'instance' ] : false;
        $this->software_version 	= isset( $args[ 'software_version' ] ) ? $args[ 'software_version' ] : false;
        $this->text_domain 			  = isset( $args[ 'text_domain' ] ) ? $args[ 'text_domain' ] : false;
        $this->extra 				      = isset( $args[ 'extra' ] ) ? $args[ 'extra' ] : false;
        $this->changelog 				  = isset( $args[ 'changelog' ] ) ? $args[ 'changelog' ] : false;
        
        /**
         * Some web hosts have security policies that block the : (colon) and // (slashes) in http://,
         * so only the host portion of the URL can be sent. For example the host portion might be
         * www.example.com or example.com. http://www.example.com includes the scheme http,
         * and the host www.example.com.
         * Sending only the host also eliminates issues when a client site changes from http to https,
         * but their activation still uses the original scheme.
         * To send only the host, use a line like the one below:
         */
        $this->blog = str_ireplace( array( 'http://', 'https://' ), '', home_url() );
        
        $this->errors_callback = $errors_callback;
        
        /**
         * More info:
         * function set_site_transient moved from wp-includes/functions.php
         * to wp-includes/option.php in WordPress 3.4
         *
         * set_site_transient() contains the pre_set_site_transient_{$transient} filter
         * {$transient} is either update_plugins or update_themes
         *
         * Transient data for plugins and themes exist in the Options table:
         * _site_transient_update_themes
         * _site_transient_update_plugins
         */
         
        /**
         * Plugin Updates
         */
        if( $this->type == 'plugin' ) {
          //** Check For Plugin Updates */
          add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'update_check' ) );
          //** Check For Plugin/Theme Information to display on the update details page */
          add_filter( 'plugins_api', array( $this, 'request' ), 10, 3 );
        }
        /**
         * Theme Updates
         */
        elseif ( $this->type == 'theme' ) {
          add_filter( 'pre_set_site_transient_update_themes', array( $this, 'update_check' ) );
        }

        add_action( 'wp_ajax_ud_api_dismiss', array( $this, 'dismiss_notices' ) );
        
      }

      /**
       * Upgrade API URL
       *
       */
      private function create_upgrade_api_url( $args ) {
        $upgrade_url = add_query_arg( 'wc-api', 'upgrade-api', $this->upgrade_url );
        return $upgrade_url . '&' . http_build_query( $args );
      }

      /**
       * Check for updates against the remote server.
       *
       * @access public
       * @since  1.0.0
       * @param  object $transient
       * @return object $transient
       */
      public function update_check( $transient ) {
        
        //** Check if the transient contains the 'checked' information */
        //** If no, just return its value without hacking it */
        if ( empty( $transient->checked ) ) {
          return $transient;
        }
        
        $args = array(
          'request' => 'pluginupdatecheck',
          'plugin_name' => $this->name,
          //'version' => $transient->checked[$this->name],
          'version' => $this->software_version,
          'product_id' => $this->product_id,
          'api_key' => $this->api_key,
          'activation_email' => $this->activation_email,
          'instance' => $this->instance,
          'domain' => $this->blog,
          'software_version' => $this->software_version,
          'extra' => $this->extra,
        );

        //** Check for a plugin update */
        $response = $this->plugin_information( $args );
        //** Displays an admin error message in the WordPress dashboard */
        $this->check_response_for_errors( $response );

        //** Set version variables */
        if ( isset( $response ) && is_object( $response ) && $response !== false ) {
          //** New plugin version from the API */
          $new_ver = (string)$response->new_version;
          //** Current installed plugin version */
          $curr_ver = (string)$this->software_version;
          //$curr_ver = (string)$transient->checked[$this->name];
        }

        //** If there is a new version, modify the transient to reflect an update is available */
        if ( isset( $new_ver ) && isset( $curr_ver ) ) {
          if ( $response !== false && version_compare( $new_ver, $curr_ver, '>' ) ) {
            if( $this->type == 'plugin' ) {
              if( isset( $response->slug ) ) {
                $response->slug = sanitize_title( $response->slug );  
              }
              $transient->response[$this->file] = $response;
            } else {
              $theme = basename( dirname( $this->file ) );
              $response = (array)$response;
              if( empty( $response[ 'url' ] ) ) { 
                $response[ 'url' ] = !empty( $this->changelog ) ? $this->changelog : 'https://www.usabilitydynamics.com';
              }
              $transient->response[$theme] = (array)$response;
            }
            
          }
        }
        
        //echo "<pre>"; print_r( $this ); echo "</pre>"; die();
        //echo "<pre>"; print_r( $transient ); echo "</pre>"; die();

        return $transient;
      }

      /**
       * Sends and receives data to and from the server API
       *
       * @access public
       * @since  1.0.0
       * @return object $response
       */
      public function plugin_information( $args ) {
        $target_url = $this->create_upgrade_api_url( $args );

        //** Check licenses keys once per one hour! */
        $response = get_transient( md5( $target_url ) );
        if ( false === $response || empty( $response ) ) {
          //** Add nocache hack. We must be sure we do not get CACHE result. peshkov@UD */
          $_target_url = $target_url . '&' . http_build_query( array( 'nocache' => rand( 10000, 99999 ) ) );
          $request = wp_remote_get( $_target_url, array( 'decompress' => false, 'sslverify' => false ) );
          if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
            return false;
          }
          $response = unserialize( wp_remote_retrieve_body( $request ) );

          if( is_object( $response ) ) {
            set_transient( md5( $target_url ), json_encode($response), HOUR_IN_SECONDS );
          }
        } else {
          $r = json_decode( $response, true );
          $response = new \stdClass();
          foreach( $r as $k => $v ){
            $response->{$k} = $v;
          }
        }

        //echo "<pre>"; print_r( $response ); echo "</pre>";
        if ( is_object( $response ) ) {
          return $response;
        } else {
          return false;
        }
      }

      /**
       * Generic request helper.
       *
       * @access public
       * @since  1.0.0
       * @param  array $args
       * @return object $response or boolean false
       */
      public function request( $false, $action, $args ) {
      
        //** Check if this plugins API is about this plugin */
        if ( isset( $args->slug ) ) {
          //** Check if this plugins API is about this plugin */
          if ( sanitize_key( $args->slug ) != sanitize_key( $this->name ) ) {
            return $false;
          }
        } else {
          return $false;
        }

        $args = array(
          'request' => 'plugininformation',
          'plugin_name' =>	$this->name,
          //'version' =>	$version->checked[$this->name],
          'version' =>	$this->software_version,
          'product_id' =>	$this->product_id,
          'api_key' =>	$this->api_key,
          'activation_email' =>	$this->activation_email,
          'instance' =>	$this->instance,
          'domain' =>	$this->blog,
          'software_version' => $this->software_version,
          'extra' => $this->extra,
        );

        $response = $this->plugin_information( $args );

        //** If everything is okay return the $response */
        if ( isset( $response ) && is_object( $response ) && $response !== false ) {
          return $response;
        }
      }

      /**
       * Displays an admin error message in the WordPress dashboard
       * @param  array $response
       * @return string
       */
      public function check_response_for_errors( $response ) {

        $this->errors = array();
      
        if ( ! empty( $response ) ) {

          $plugins = get_plugins();
          $name = isset( $plugins[$this->name] ) ? $plugins[$this->name]['Name'] : $this->name;
          
          if ( isset( $response->errors['no_key'] ) && $response->errors['no_key'] == 'no_key' && isset( $response->errors['no_subscription'] ) && $response->errors['no_subscription'] == 'no_subscription' ) {

            $no_key_dismissed = get_option( 'dismissed_error_' .  sanitize_key( $name ) . '_no_key', '' );
            $show_no_key_error = $this->check_dismiss_time( $no_key_dismissed );
            if( $show_no_key_error ) {
                $this->errors[] = sprintf( __( 'A license key for %s could not be found. Maybe you forgot to enter a license key when setting up %s, or the key was deactivated in your account. You can reactivate or purchase a license key from your account <a href="%s" target="_blank">Licences</a> | <a class="dismiss-error dismiss" data-key="dismissed_error_%s_no_key" href="#">dismiss</a>.', $this->text_domain ), $name, $name, $this->renew_license_url, sanitize_key( $name ) );
            }

            $no_subscription_dismissed = get_option( 'dismissed_error_' .  sanitize_key( $name ) . '_no_subscription', '' );
            $show_no_subscription_error = $this->check_dismiss_time( $no_subscription_dismissed );
            if( $show_no_subscription_error ) {
                $this->errors[] = sprintf( __( 'A subscription for %s could not be found. You can purchase a subscription from your account <a href="%s" target="_blank">dashboard</a> | <a class="dismiss-error dismiss" data-key="dismissed_error_%s_no_subscription" href="#">dismiss</a>.', $this->text_domain ), $name, $this->renew_license_url, sanitize_key( $name ) );
            }

          } else if ( isset( $response->errors['exp_license'] ) && $response->errors['exp_license'] == 'exp_license' ) {

            $exp_license_dismissed = get_option( 'dismissed_error_' .  sanitize_key( $name ) . '_exp_license', '' );
            $show_exp_license_error = $this->check_dismiss_time( $exp_license_dismissed );
            if( $show_exp_license_error ) {
                $this->errors[] = sprintf( __( 'The license key for %s has expired. You can reactivate or get a license key from your account <a href="%s" target="_blank">dashboard</a> | <a class="dismiss-error dismiss" data-key="dismissed_error_%s_exp_license" href="#">dismiss</a>.', $this->text_domain ), $name, $this->renew_license_url, sanitize_key( $name ) );
            }

          }  else if ( isset( $response->errors['hold_subscription'] ) && $response->errors['hold_subscription'] == 'hold_subscription' ) {

            $hold_subscription_dismissed = get_option( 'dismissed_error_' .  sanitize_key( $name ) . '_hold_subscription', '' );
            $show_hold_subscription_error = $this->check_dismiss_time( $hold_subscription_dismissed );
            if( $show_hold_subscription_error ) {
                $this->errors[] = sprintf( __( 'The subscription for %s is on-hold. You can reactivate the subscription from your account <a href="%s" target="_blank">dashboard</a> | <a class="dismiss-error dismiss" data-key="dismissed_error_%s_hold_subscription" href="#">dismiss</a>.', $this->text_domain ), $name, $this->renew_license_url, sanitize_key( $name ) );
            }

          } else if ( isset( $response->errors['cancelled_subscription'] ) && $response->errors['cancelled_subscription'] == 'cancelled_subscription' ) {

            $cancelled_subscription_dismissed = get_option( 'dismissed_error_' .  sanitize_key( $name ) . '_cancelled_subscription', '' );
            $show_cancelled_subscription_error = $this->check_dismiss_time( $cancelled_subscription_dismissed );
            if( $show_cancelled_subscription_error ) {
                $this->errors[] = sprintf( __( 'The subscription for %s has been cancelled. You can renew the subscription from your account <a href="%s" target="_blank">dashboard</a>. A new license key will be emailed to you after your order has been completed. <a class="dismiss-error dismiss" data-key="dismissed_error_%s_cancelled_subscription" href="#">dismiss</a>.', $this->text_domain ), $name, $this->renew_license_url, sanitize_key( $name ) );
            }

          } else if ( isset( $response->errors['exp_subscription'] ) && $response->errors['exp_subscription'] == 'exp_subscription' ) {

            $exp_subscription_dismissed = get_option( 'dismissed_error_' .  sanitize_key( $name ) . '_exp_subscription', '' );
            $show_exp_subscription_error = $this->check_dismiss_time( $exp_subscription_dismissed );
            if( $show_exp_subscription_error ) {
                $this->errors[] = sprintf( __( 'The subscription for %s has expired. You can reactivate the subscription from your account <a href="%s" target="_blank">dashboard</a> | <a class="dismiss-error dismiss" data-key="dismissed_error_%s_exp_subscription" href="#">dismiss</a>.', $this->text_domain ), $name, $this->renew_license_url, sanitize_key( $name ) ) ;
            }

          } else if ( isset( $response->errors['suspended_subscription'] ) && $response->errors['suspended_subscription'] == 'suspended_subscription' ) {

            $suspended_subscription_dismissed = get_option( 'dismissed_error_' .  sanitize_key( $name ) . '_suspended_subscription', '' );
            $show_suspended_subscription_error = $this->check_dismiss_time( $suspended_subscription_dismissed );
            if( $show_suspended_subscription_error ) {
                $this->errors[] = sprintf( __( 'The subscription for %s has been suspended. You can reactivate the subscription from your account <a href="%s" target="_blank">dashboard</a> | <a class="dismiss-error dismiss" data-key="dismissed_error_%s_suspended_subscription" href="#">dismiss</a>.', $this->text_domain ), $name, $this->renew_license_url, sanitize_key( $name ) ) ;
            }

          } else if ( isset( $response->errors['pending_subscription'] ) && $response->errors['pending_subscription'] == 'pending_subscription' ) {

            $pending_subscription_dismissed = get_option( 'dismissed_error_' .  sanitize_key( $name ) . '_pending_subscription', '' );
            $show_pending_subscription_error = $this->check_dismiss_time( $pending_subscription_dismissed );
            if( $show_pending_subscription_error ) {
                $this->errors[] = sprintf( __( 'The subscription for %s is still pending. You can check on the status of the subscription from your account <a href="%s" target="_blank">dashboard</a> | <a class="dismiss-error dismiss" data-key="dismissed_error_%s_pending_subscription" href="#">dismiss</a>.', $this->text_domain ), $name, $this->renew_license_url, sanitize_key( $name ) ) ;
            }

          } else if ( isset( $response->errors['trash_subscription'] ) && $response->errors['trash_subscription'] == 'trash_subscription' ) {

            $trash_subscription_dismissed = get_option( 'dismissed_error_' .  sanitize_key( $name ) . '_trash_subscription', '' );
            $show_trash_subscription_error = $this->check_dismiss_time( $trash_subscription_dismissed );
            if( $show_trash_subscription_error ) {
                $this->errors[] = sprintf( __( 'The subscription for %s has been placed in the trash and will be deleted soon. You can get a new subscription from your account <a href="%s" target="_blank">dashboard</a> | <a class="dismiss-error dismiss" data-key="dismissed_error_%s_trash_subscription" href="#">dismiss</a>.', $this->text_domain ), $name, $this->renew_license_url, sanitize_key( $name ) ) ;
            }

          } else if ( isset( $response->errors['no_subscription'] ) && $response->errors['no_subscription'] == 'no_subscription' ) {

            $no_subscription_dismissed = get_option( 'dismissed_error_' .  sanitize_key( $name ) . '_no_subscription', '' );
            $show_no_subscription_error = $this->check_dismiss_time( $no_subscription_dismissed );
            if( $show_no_subscription_error ) {
                $this->errors[] = sprintf( __( 'A subscription for %s could not be found. You can get a subscription from your account <a href="%s" target="_blank">dashboard</a> | <a class="dismiss-error dismiss" data-key="dismissed_error_%s_no_subscription" href="#">dismiss</a>.', $this->text_domain ), $name, $this->renew_license_url, sanitize_key( $name ) );
            }

          } else if ( isset( $response->errors['no_activation'] ) && $response->errors['no_activation'] == 'no_activation' ) {

            $no_activation_dismissed = get_option( 'dismissed_error_' .  sanitize_key( $name ) . '_no_activation', '' );
            $show_no_activation_error = $this->check_dismiss_time( $no_activation_dismissed );
            if( $show_no_activation_error ) {
                $this->errors[] = sprintf( __( '%s has not been activated. Go to the settings page and enter the license key and license email to activate %s. <a class="dismiss-error dismiss" data-key="dismissed_error_%s_no_activation" href="#">dismiss</a>.', $this->text_domain ), $name, $name, sanitize_key( $name ) ) ;
            }

          } else if ( isset( $response->errors['no_key'] ) && $response->errors['no_key'] == 'no_key' ) {

            $no_key_dismissed = get_option( 'dismissed_error_' .  sanitize_key( $name ) . '_no_key', '' );
            $show_no_key_error = $this->check_dismiss_time( $no_key_dismissed );
            if( $show_no_key_error ) {
                $this->errors[] = sprintf( __( 'A license key for %s could not be found. Maybe you forgot to enter a license key when setting up %s, or the key was deactivated in your account. You can reactivate or get a license key from your account <a href="%s" target="_blank">Licences</a> | <a class="dismiss-error dismiss" data-key="dismissed_error_%s_no_key" href="#">dismiss</a>.', $this->text_domain ), $name, $name, $this->renew_license_url, sanitize_key( $name ) );
            }

          } else if ( isset( $response->errors['download_revoked'] ) && $response->errors['download_revoked'] == 'download_revoked' ) {

            $download_revoked_dismissed = get_option( 'dismissed_error_' .  sanitize_key( $name ) . '_download_revoked', '' );
            $show_download_revoked_error = $this->check_dismiss_time( $download_revoked_dismissed );
            if( $show_download_revoked_error ) {
                $this->errors[] = sprintf( __( 'Download permission for %s has been revoked possibly due to a license key or subscription expiring. You can reactivate or get a license key from your account <a href="%s" target="_blank">dashboard</a> | <a class="dismiss-error dismiss" data-key="dismissed_error_%s_download_revoked" href="#">dismiss</a>.', $this->text_domain ), $name, $this->renew_license_url, sanitize_key( $name ) ) ;
            }

          } else if ( isset( $response->errors['switched_subscription'] ) && $response->errors['switched_subscription'] == 'switched_subscription' ) {

            $switched_subscription_dismissed = get_option( 'dismissed_error_' .  sanitize_key( $name ) . '_switched_subscription', '' );
            $show_switched_subscription_error = $this->check_dismiss_time( $switched_subscription_dismissed );
            if( $show_switched_subscription_error ) {
                $this->errors[] = sprintf( __( 'You changed the subscription for %s, so you will need to enter your new API License Key in the settings page. The License Key should have arrived in your email inbox, if not you can get it by logging into your account <a href="%s" target="_blank">dashboard</a> | <a class="dismiss-error dismiss" data-key="dismissed_error_%s_switched_subscription" href="#">dismiss</a>.', $this->text_domain ), $name, $this->renew_license_url, sanitize_key( $name ) ) ;
            }

          }

        }
        
        if( !empty( $this->errors ) ) {
          add_action('admin_notices', array( $this, 'print_errors') );
        }

      }
      
      /**
       * Maybe print admin notices
       */
      public function print_errors() {
        if( !empty( $this->errors ) && is_array( $this->errors ) ) {
          foreach( $this->errors as $error ) {
            echo '<div id="message" class="error"><p>' . $error . '</p></div>';
          }
          $this->print_scripts();
        }
      }

      /**
       * print script for ajax
       */
      public function print_scripts() {
            ob_start();
            ?>
            <script type="text/javascript">
                jQuery( document ).ready( function () {

                    jQuery( '.error' ).on( 'click', '.dismiss', function(e){
                        e.preventDefault();

                        var _this = jQuery( this );

                        var data = {
                            action: 'ud_api_dismiss',
                            key: _this.data('key'),
                        }

                        jQuery.post( "<?php echo admin_url( 'admin-ajax.php' ); ?>", data, function ( result_data ) {
                            if( result_data.success == '1' ) {
                                _this.closest('.error').remove();
                            } else if ( result_data.success == '0' ) {
                                console.error(result_data.error);
                            }
                        }, "json" );

                    });

                } );
            </script>
            <?php
            echo ob_get_clean();
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

        /**
         * dismiss the notice ajax callback
         * @throws \Exception
         */
        public function dismiss_notices(){
          $response = array(
              'success' => '0',
              'error' => __( 'There was an error in request.', $this->text_domain ),
          );
          $error = false;

          if( empty($_POST['key']) ) {
            $response['error'] = __( 'Invalid key', $this->text_domain );
            $error = true;
          }

          if ( ! $error && update_option( ( $_POST['key'] ), time() ) ) {
            $response['success'] = '1';
          }

          wp_send_json( $response );
        }

      
    }
  
  }
  
}
