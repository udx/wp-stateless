<?php
/**
 * Plugin Name: Smush Image Compression and Optimization
 * Plugin URI: https://wordpress.org/plugins/wp-smushit/
 *
 * Compatibility Description: Ensures compatibility with WPSmush.
 *
 * @todo Compatibility for backup feature
 */

namespace wpCloud\StatelessMedia {

    if(!class_exists('wpCloud\StatelessMedia\WPSmush')) {
        
        class WPSmush extends ICompatibility {
            protected $id = 'wp-smush';
            protected $title = 'WP Smush';
            protected $constant = 'WP_STATELESS_COMPATIBILITY_WPSMUSH';
            protected $description = 'Ensures compatibility with WP Smush.';
            protected $plugin_file = ['wp-smushit/wp-smush.php', 'wp-smush-pro/wp-smush.php', 'wp-smushit-pro/wp-smush-pro.php'];

            public function module_init($sm){
                add_action('wp_smush_image_optimised', array($this, 'image_optimized'), 10, 2);
                // Check if the file not exists for the given path then download
                // Useful in Stateless mode
                add_action( 'smush_file_exists', array( $this, 'maybe_download_file' ), 10, 3 );

                // We need to remove the regular handler for sync 
                // unless in stateless mode we would remove the attachment before it's get optimized.
                remove_filter( 'wp_update_attachment_metadata', array( 'wpCloud\StatelessMedia\Utility', 'add_media' ), 999 );
                add_filter( 'wp_update_attachment_metadata', array( $this, 'add_media_wrapper' ), 999, 2 );

                add_filter('delete_attachment', array($this, 'remove_backup'));
                add_filter( 'smush_backup_exists', array( $this, 'backup_exists_on_gcs' ), 10, 3 );
                add_action( 'sm:synced::image', array( $this, 'sync_backup'), 10, 2 );
            }

            /**
             * Replacement for default wp_update_attachment_metadata filter of bootstrap class.
             * To avoid sync same image twice, once on upload and again after optimization.
             * We also avoid downloading image before optimization on stateless mode.
             */
            public function add_media_wrapper($metadata, $attachment_id) {
                if (class_exists('WP_Smush_Modules')) {
                    $auto_smush = \WP_Smush::get_instance()->core()->mod->settings->get('auto');
                } else {
                    global $wpsmush_settings;
                    $auto_smush = $wpsmush_settings->settings['auto'];
                }

                if (!$auto_smush || !wp_attachment_is_image($attachment_id) ||
                    !apply_filters('wp_smush_image', true, $attachment_id) ||
                    !(
                        ((!empty($_POST['action']) && 'upload-attachment' == $_POST['action']) || isset($_POST['post_id'])) &&
                        // And, check if Async is enabled.
                        defined('WP_SMUSH_ASYNC') && WP_SMUSH_ASYNC
                    )
                ) {
                    return ud_get_stateless_media()->add_media($metadata, $attachment_id);
                }
                return $metadata;
            }

            /**
             * Sync image after it's been optimized.
             * 
             * @param int $attachment_id attachment id
             * @param array $stats compression stats
             * 
             * @return null
             */
            public function image_optimized($attachment_id, $stats){
                // Sync the attachment to GCS
                ud_get_stateless_media()->add_media( array(), $attachment_id, true );
                
                // also sync the backup images
                $this->sync_backup($attachment_id);
            }

            /**
             * If local file don't exists then download it from GCS
             *
             * @param string $file_path Full file path
             * @param string $attachment_id
             * @param array $size_details Array of width and height for the image
             *
             * @return null
             */
            function maybe_download_file( $file_path = '', $attachment_id = '', $size_details = array() ) {
                if ( empty( $file_path ) || empty( $attachment_id ) ) {
                    return;
                }

                //Download if file not exists
                if ( !file_exists( $file_path ) ) {
                    $client = ud_get_stateless_media()->get_client();
                    $metadata = wp_get_attachment_metadata( $attachment_id );
                    if(!empty($metadata['gs_name'])){
                        $gs_name = dirname($metadata['gs_name']) . '/' . basename($file_path);
                        $client->get_media( apply_filters( 'wp_stateless_file_name', $gs_name), true, $file_path );
                        
                        // We need to remove backup from GCS if it's a restore action
                        if($this->hook_from_restore_image()){
                            $client->remove_media( apply_filters( 'wp_stateless_file_name', $gs_name) );
                        }
                    }
                }
            }

            /**
             * Remove backup when attachment is removed
             * 
             * @param $attachment_id 
             */
            function remove_backup($attachment_id){
                $upload_dir = wp_get_upload_dir();
                $metadata = wp_get_attachment_metadata( $attachment_id );
                $backup_paths = get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true );

                if(!empty($metadata['gs_name']) && !empty($backup_paths) && is_array($backup_paths)){
                    // Getting local dir path for backup image
                    $base_dir = $upload_dir['basedir'] . '/' . dirname( $metadata['file'] );
                    // Getting GCS dir name from meta data. In case Bucket Folder used.
                    $gs_dir = dirname($metadata['gs_name']);
                    foreach ($backup_paths as $key => $data) {
                        $gs_name = $gs_dir . '/' . basename($data['file']);
                        // Path of backup image
                        $backup_path = $base_dir . '/' . basename($data['file']);
                        do_action( 'sm:sync::deleteFile', apply_filters( 'wp_stateless_file_name', $gs_name), $backup_path);
                        delete_transient('sm-wp-smush-backup-exists-' . $attachment_id);
                    }
                }

            }

            /**
             * Checks if we've backup on gcs for the given attachment id and backup path
             *
             * @param string $attachment_id
             * @param string $backup_path
             *
             * @return bool
             */
            function backup_exists_on_gcs( $exists, $attachment_id = '', $backup_path = '' ) {
                if(!$exists && $attachment_id){
                    if(get_transient('sm-wp-smush-backup-exists-' . $attachment_id)){
                        return true;
                    }

                    $metadata = wp_get_attachment_metadata( $attachment_id );
                    if(!empty($metadata['gs_name'])){
                        $gs_name = dirname($metadata['gs_name']) . '/' . basename($backup_path);
                        if ( ud_get_stateless_media()->get_client()->media_exists( apply_filters( 'wp_stateless_file_name', $gs_name) ) ) {
                            set_transient( 'sm-wp-smush-backup-exists-' . $attachment_id, true, HOUR_IN_SECONDS );
                            return true;
                        }
                    }
                }

                return $exists;
            }

            /**
             * Sync backup image to GCS
             */
            public function sync_backup($attachment_id, $metadata = array()){
                $upload_dir = wp_get_upload_dir();
                if(empty($metadata) || empty($metadata['gs_name'])){
                    $metadata = wp_get_attachment_metadata( $attachment_id );
                }

                // Getting backup path from smush settings in db
                $backup_paths = get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true );
                
                if(!empty($metadata['gs_name']) && !empty($backup_paths) && is_array($backup_paths)){
                    // Getting local dir for backup image
                    $base_dir = $upload_dir['basedir'] . '/' . dirname( $metadata['file'] );
                    // Getting GCS dir name from meta data. In case Bucket Folder used.
                    $gs_dir = dirname($metadata['gs_name']);

                    foreach ($backup_paths as $key => $data) {
                        $gs_name = $gs_dir . '/' . basename($data['file']);
                        // Path of backup image
                        $backup_path = $base_dir . '/' . basename($data['file']);
                        // Sync backup image with GCS
                        do_action( 'sm:sync::syncFile', apply_filters( 'wp_stateless_file_name', $gs_name), $backup_path);
                        delete_transient('sm-wp-smush-backup-exists-' . $attachment_id);
                    }
                }
            }
            
            /**
             * Determine where we hook from
             * Is this a hook from wp smush restore image or not.
             *
             * @return bool 
             */
            private function hook_from_restore_image() {
                $call_stack = debug_backtrace();
                $class_name = class_exists( 'WpSmushBackup' ) ? 'WpSmushBackup' : 'WP_Smush_Backup';

                if ( !empty( $call_stack ) && is_array( $call_stack ) ) {
                    foreach( $call_stack as $step ) {


                        if ( $step['function'] == 'restore_image' && $step['class'] == $class_name ) {
                            return true;
                        }
                    }
                }

                return false;
            }

        }

    }

}
