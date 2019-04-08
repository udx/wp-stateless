<?php
/**
 * Plugin Name: Gravity Forms
 * Plugin URI: https://www.gravityforms.com/
 *
 * Compatibility Description: Enables support for these Gravity Forms features:
 * file upload field, post image field, custom file upload field type.
 *
 */

namespace wpCloud\StatelessMedia {

    if(!class_exists('wpCloud\StatelessMedia\GravityForm')) {

        class GravityForm extends ICompatibility {
            protected $id = 'gravity-form';
            protected $title = 'Gravity Forms';
            protected $constant = 'WP_STATELESS_COMPATIBILITY_GF';
            protected $description = 'Enables support for these Gravity Forms features: file upload field, post image field, custom file upload field type.';
            protected $plugin_file = 'gravityforms/gravityforms.php';
            protected $plugin_version;
            protected $non_library_sync = true;

            public function module_init($sm){
            	if ( class_exists('GFForms') ) {
            		$this->plugin_version = \GFForms::$version;
            	}
                do_action('sm:sync::register_dir', '/gravity_forms/');
                add_filter( 'gform_save_field_value', array($this, 'gform_save_field_value'), 10, 5 );
                add_action( 'sm::synced::nonMediaFiles', array($this, 'modify_db'), 10, 3);

                add_action( 'gform_file_path_pre_delete_file', array($this, 'gform_file_path_pre_delete_file'), 10, 2);
            }

            /**
             * On gform save field value sync file to GCS and alter the file url to GCS link.
             *
             * @param $value
             * @param $lead
             * @param $field
             * @param $form
             * @param $input_id
             */
            public function gform_save_field_value( $value, $lead, $field, $form, $input_id ) {
                if(empty($value)) return $value;

            	if ( empty($this->plugin_version) && class_exists('GFForms') ) {
            		$this->plugin_version = \GFForms::$version;
                }
                
                $type = \GFFormsModel::get_input_type($field);
                if($type == 'fileupload'){
                    $dir = wp_upload_dir();

					if ( $field->multipleFiles ) {
						$value = json_decode( $value );
                    }
                    else{
                        $value = array($value);
                    }

                    foreach($value as $k => $v){
                        if(empty($v)) continue;
                        $position = strpos($v, 'gravity_forms/');

                        if( $position !== false ){
                            $name = substr($v, $position);
                            $absolutePath = $dir['basedir'] . '/' .  $name;
                            $name = apply_filters( 'wp_stateless_file_name', $name);
                            // doing sync
                            do_action( 'sm:sync::syncFile', $name, $absolutePath);
                            $value[$k] = ud_get_stateless_media()->get_gs_host() . '/' . $name;
                            // Todo add filter.
                        }
                    }

					if ( $field->multipleFiles ) {
						$value = json_encode( $value );
                    }
                    else{
                        $value = array_pop($value);
                    }
                }
                else if($type == 'post_image'){
                    add_action( 'gform_after_create_post', function($post_id, $lead, $form) use ($value, $field){
                        global $wpdb;
                        $dir = wp_upload_dir();
                        $lead_detail_id         = $lead['id'];
                        $gf_upload_root        = \GFFormsModel::get_upload_root();
                        $gf_upload_url_root    = \GFFormsModel::get_upload_url_root();
                        $lead_detail_table      = \GFFormsModel::get_lead_details_table_name();

                        $position = strpos($value, 'gravity_forms/');
                        $_name = substr($value, $position); // gravity_forms/
                        $arr_name = explode('|:|', $_name);
                        $name = rgar( $arr_name, 0 ); // Removed |:| from end of the url.

                        // doing sync
                        $absolutePath = $dir['basedir'] . '/' .  $name;
                        $name = apply_filters( 'wp_stateless_file_name', $name);
                        do_action( 'sm:sync::syncFile', $name, $absolutePath);

                        $value = ud_get_stateless_media()->get_gs_host() . '/' . $_name;
                        // Todo add filter.
                        if(version_compare($this->plugin_version, '2.3', '<')){ // older version
                            $result = $wpdb->update( $lead_detail_table, array( 'value' => $value ), array( 'lead_id' => $lead_detail_id, 'form_id' => $form['id'], 'field_number' => $field['id'], ), array( '%s' ), array( '%d' ) );
                        }
                        else{ // New version
                            $result = $wpdb->update( \GFFormsModel::get_entry_meta_table_name(), array( 'meta_value' => $value ), array( 'entry_id' => $lead_detail_id, 'form_id' => $form['id'], 'meta_key' => $field['id'], ), array( '%s' ), array( '%d' ) );
                        }
                    }, 10, 3);
                }
                return $value;
            }

            /**
             * Modify value in database after sync from Sync tab.
             *
             */
            public function modify_db( $file_path, $fullsizepath, $media ){
                global $wpdb;
                $wpdb->hide_errors();
                $position = strpos($file_path, 'gravity_forms/');
                $is_index = strpos($file_path, 'index.html');
                $is_htaccess = strpos($file_path, '.htaccess');
                $root_dir = ud_get_stateless_media()->get( 'sm.root_dir' );
                
            	if ( empty($this->plugin_version) && class_exists('GFForms') ) {
            		$this->plugin_version = \GFForms::$version;
                }

                $gf_val_column = 'meta_value';
                $gf_table = \GFFormsModel::get_entry_meta_table_name();
                if(version_compare($this->plugin_version, '2.3', '<')){
                    $gf_val_column = 'value';
                }

                if( $position !== false && !$is_index ){
                    $dir = wp_upload_dir();
                    $file_path = trim($file_path, '/');
                    //EDIT: Use base file name since the URL in the DB could be encoded with in an array
                    $file_single = basename($file_path);

                    // Todo add filter.

                    // We need to get results from db because of post image field have extra data at the end of url.
                    // Also url could be array and json encoded.
                    // Unless we would loss those data.
                    // xyz.jpg|:|tile|:|description|:|
                    $query = sprintf(
                        "
                        SELECT id, {$gf_val_column} AS value FROM {$gf_table}
                        WHERE {$gf_val_column} like '%s';
                        "
                        , '%' . $file_single . '%'
                    );
                    $results = $wpdb->get_results( $query );
                    $this->throw_db_error();

                    foreach ($results as $result) {
                        $position = false;
						//EDIT: Check if value is json encoded, if so, cycle through array and replace URLs.
                        $value = json_decode($result->value);

						if (json_last_error() === 0) {
							foreach( $value  as $k => $v ){
								 $position = strpos($v, $dir['baseurl']);
								 if($position !== false){
								  	$value[$k] = str_replace($dir['baseurl'], ud_get_stateless_media()->get_gs_host() . '/' . $root_dir, $v );
								 }
							}

							$result->value = json_encode($value);
                        }
                        else{
							$position = strpos($result->value, $dir['baseurl']);
							$result->value = str_replace($dir['baseurl'], ud_get_stateless_media()->get_gs_host() . '/' . $root_dir, $result->value);
						}

                        if($position !== false){
                            $query = sprintf(
                                "
                                UPDATE {$gf_table}
                                SET {$gf_val_column} = '%s'
                                WHERE id = %d
                                "
                                , $result->value, $result->id
                            );
                            $entries = $wpdb->get_results( $query );
                            $this->throw_db_error();
                        }

                    }
                }
            }

            /**
             * Throw db error from last db query.
             * We need to throw db error instead of just printing,
             * so that we can catch them in ajax request.
             */
            function throw_db_error(){

                global $wpdb;
                $wpdb->show_errors();

                if($wpdb->last_error !== '' && wp_doing_ajax()) :
                    ob_start();
                    $wpdb->print_error();
                    $error = ob_get_clean();
                    if($error){
                        throw new \Exception( $error );
                    }
                endif;

            }

            /**
             * Delete file from GCS
             */
            public function gform_file_path_pre_delete_file( $file_path, $url ){
                $file_path = wp_normalize_path($file_path);
                $gs_host = wp_normalize_path( ud_get_stateless_media()->get_gs_host() );
                $dir = wp_upload_dir();
                $is_stateless = strpos($file_path, $gs_host);

                // If the url is a GCS link then remove it from GCS.
                if($is_stateless !== false){
                    $gs_name = substr($file_path, strpos($file_path, '/gravity_forms/'));
                    $file_path = $dir['basedir'] . $gs_name;
                    $gs_name = apply_filters( 'wp_stateless_file_name', $gs_name);

                    $client = ud_get_stateless_media()->get_client();
                    if( !is_wp_error( $client ) ) {
                        $client->remove_media( trim($gs_name, '/') );
                    }
                }

		        return $file_path;
            }
        }

    }

}
