<?php
/**
 * Plugin Name: Easy Digital Downloads
 * Plugin URI: https://wordpress.org/plugins/easy-digital-downloads/
 *
 * Compatibility Description: 
 *
 */

namespace wpCloud\StatelessMedia {

    if(!class_exists('wpCloud\StatelessMedia\GravityForm')) {
        
        class GravityForm extends ICompatibility {
            protected $id = 'gravity-form';
            protected $title = 'Gravity Form File Upload';
            protected $constant = 'WP_STATELESS_COMPATIBILITY_GF';
            protected $description = 'Ensures compatibility with Gravity Form File Upload field.';

            public function module_init($sm){
                // add_filter( 'gform_upload_path', array($this, 'gform_upload_path'), 10, 2 );
                do_action('sm:sync::register_dir', '/gravity_forms/');
                add_filter( 'gform_save_field_value', array($this, 'gform_save_field_value'), 10, 5 );
                add_action( 'sm::synced::nonMediaFiles', array($this, 'modify_db'), 10, 3);
            }
            
            
            /**
             * 
             *
             * @param $value
             * @param $lead
             * @param $field
             * @param $form
             * @param $input_id
             */
            public function gform_save_field_value( $value, $lead, $field, $form, $input_id ) {
                $type = \GFFormsModel::get_input_type($field);
                if($type == 'fileupload'){
                    $dir = wp_upload_dir();
                    $position = strpos($value, 'gravity_forms/');

                    if( $position !== false ){
                        $name = substr($value, $position);
                        $absolutePath = $dir['basedir'] . '/' .  $name;
                        do_action( 'sm:sync::syncFile', $name, $absolutePath);
                        $value = ud_get_stateless_media()->get_gs_host() . '/' . $name;
                    }
                }
                return $value;
            }

            public function modify_db( $file_path, $fullsizepath, $media ){
                global $wpdb;
                $position = strpos($file_path, 'gravity_forms/');
                $is_index = strpos($file_path, 'index.html');

                if( $position !== false && !$is_index ){
                    $file_path = trim($file_path, '/');
                    
                    $_file_path = ud_get_stateless_media()->get_gs_host() . '/' . $file_path;
                    $query = sprintf(
                        "
                        UPDATE {$wpdb->prefix}rg_lead_detail
                        SET value = '%s'
                        WHERE value like '%s'
                        "
                        , $_file_path, '%' . $file_path
                    );
                    $entries = $wpdb->get_results( $query );
                }
            }
        }

    }

}
