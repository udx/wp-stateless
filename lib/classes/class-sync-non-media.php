<?php
/**
 * To do: check if client is connected to google before doing any action.
 */

namespace wpCloud\StatelessMedia {

    if (!class_exists('wpCloud\StatelessMedia\SyncNonMedia')) {

        class SyncNonMedia {
            
            public $registered_dir = array();
            public $registered_files = array();
            
            public function __construct(){
                $this->registered_files = get_option('sm_synced_files', array());
                add_filter( 'sm:sync::nonMediaFiles', array($this, 'sync_non_media_files') );
                add_action( 'sm:sync::addFile', array($this, 'add_file') );
                add_action( 'sm:sync::syncFile', array($this, 'sync_file'), 10, 2 );
                add_action( 'sm:sync::deleteFile', array($this, 'delete_file') );
                add_action( 'sm:sync::deleteFiles', array($this, 'delete_files') );
            }

            public function register_dir($dir){
                if(!in_array($dir, $this->registered_dir)){
                    $this->registered_dir[] = $dir;
                }
            }

            /**
             * Add file to list of files to be sync.
             */
            public function add_file($file){
                if(!in_array($file, $this->registered_files)){
                    $this->registered_files[] = $file;
                    update_option( 'sm_synced_files', $this->registered_files );
                }
            }

            /**
             * Instant sync
             */
            public function sync_file($name, $absolutePath, $forced = false){
                if(in_array($name, $this->registered_files) && !$forced){
                    return false;
                }

                $this->add_file($name);
                $file_type = wp_check_filetype($absolutePath);
                if(empty($this->client)){
                    $this->client = ud_get_stateless_media()->get_client();
                }

                $media = $this->client->add_media( array(
                    'name' => $name,
                    'absolutePath' => $absolutePath,
                    'cacheControl' => apply_filters( 'sm:item:cacheControl', 'public, max-age=36000, must-revalidate', $absolutePath),
                    'contentDisposition' => apply_filters( 'sm:item:contentDisposition', null, $absolutePath),
                    'mimeType' => $file_type['type'],
                    'metadata' => array(
                        'child-of' => dirname($name),
                        'file-hash' => md5( $name ),
                    ),
                ));

                if(ud_get_stateless_media()->get( 'sm.mode' ) === 'stateless'){
                    unlink($absolutePath);
                }

                return $media;
            }

            /**
             * Manual sync using sync tab.
             */
            public function sync_non_media_files($files){
                $upload_dir = wp_upload_dir();
                $files = array_merge($files, $this->registered_files);
                foreach ($this->registered_dir as $key => $dir) {
                    $dir = $upload_dir['basedir'] . "/" . trim($dir, '/') . "/";
                    if(is_dir($dir)){
                        $files = glob( $upload_dir['basedir'] . $dir . "*" );
                        foreach ($files as $id => $file) {
                            $_file = str_replace(wp_normalize_path($upload_dir['basedir']), '', wp_normalize_path($file));
                            if(!in_array($_file, $files)){
                                $files[] = $_file;
                            }
                        }
                    }
                }
                return $files;
            }

            public function delete_file($file){
                $file = trim($file, '/');
                if(in_array($file, $this->registered_files)){
                    if(empty($this->client)){
                        $this->client = ud_get_stateless_media()->get_client();
                    }
                    
                    // Removing file for GCS
                    $this->client->remove_media($file);
                    
                    $key = array_search($file, $this->registered_files);
                    unset($this->registered_files[$key]);
                    update_option( 'sm_synced_files', $this->registered_files );
                    return true;
                }
                return false;
            }

            public function delete_files($dir){
                if(empty($this->client)){
                    $this->client = ud_get_stateless_media()->get_client();
                }

                foreach ($this->registered_files as $key => $file) {
                    if(strpos($file, $dir) !== false){
                        $this->client->remove_media($file);
                        unset($this->registered_files[$key]);
                    }
                }
                
                update_option( 'sm_synced_files', $this->registered_files );
                
                return true;
            }
        }
    }
}
