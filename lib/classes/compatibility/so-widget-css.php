<?php
/**
 * Plugin Name: Page Builder by SiteOrigin
 * Plugin URI: https://wordpress.org/plugins/easy-digital-downloads/
 *
 * Compatibility Description: add support for siteorigin generated CSS files
 *
 */

namespace wpCloud\StatelessMedia {

    if (!class_exists('wpCloud\StatelessMedia\SOWidgetCSS')) {
        
        class SOWidgetCSS extends ICompatibility {
            protected $id = 'so-widget-css';
            protected $title = 'SiteOrigin CSS files';
            protected $constant = 'WP_STATELESS_COMPATIBILITY_SO_CSS';
            protected $description = 'Add support for siteorigin generated CSS files';
            
            public function __construct(){
                $this->init();
            }

            public function module_init($sm){
                add_filter( 'set_url_scheme', array( $this, 'set_url_scheme' ), 20, 3 );
                add_filter( 'pre_set_transient_sow:cleared', array( $this, 'clear_file_cache' ), 20, 3 );
                add_filter( 'siteorigin_widgets_sanitize_instance', array($this, 'delete_file'), 10, 3);

            }

            

            /**
             * Change Upload BaseURL when CDN Used.
             *
             * @param $data
             * @return mixed
             */
            public function set_url_scheme( $url, $scheme, $orig_scheme ) {
                $position = strpos($url, 'siteorigin-widgets/');
                if( $position !== false ){
                    $upload_data = wp_upload_dir();
                    $name = substr($url, $position);
                    $absolutePath = $upload_data['basedir'] . '/' .  $name;
                    do_action( 'sm:sync::syncFile', $name, $absolutePath);
                    $url = ud_get_stateless_media()->get_gs_host() . '/' . $name;
                }
                return $url;
            }

            /**
             * Clear all SO CSS files from GCS after expired. 7 days
             */
            public function clear_file_cache($value, $expiration, $transient){
                do_action( 'sm:sync::deleteFiles', 'siteorigin-widgets/' );
                return $value;
            }

            /**
             * Remove single file from GCS
             */
            public function delete_file($new_instance, $form_options, $so_widget){
                $new_instance = $so_widget->modify_instance($new_instance);
                $style = $so_widget->get_style_name($new_instance);
                $hash = $so_widget->get_style_hash( $new_instance );
                $name = $so_widget->id_base.'-'.$style.'-'.$hash;

                $file = '/siteorigin-widgets/' . $name . '.css';
                do_action( 'sm:sync::deleteFile', $file );
                return $new_instance;
            }

            
        }

    }

}
