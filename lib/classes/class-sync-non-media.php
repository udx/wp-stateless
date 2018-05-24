<?php
/**
 * To do: Create seperate table to keep track of files.
 * To do: check if client is connected to google before doing any action.
 * Need to improve workflow.
 * Maybe add a transient of few days to keep track of synced files.
 */

namespace wpCloud\StatelessMedia {

    if (!class_exists('wpCloud\StatelessMedia\SyncNonMedia')) {

        class SyncNonMedia {
            
            public $registered_dir = array();
            public $registered_files = array();
            
            public function __construct(){
                $this->registered_files = get_option('sm_synced_files', array());
                // Manual sync using sync tab. 
                // called from ajax action_get_non_library_files_id
                // Return files to be manualy sync from sync tab.
                add_filter( 'sm:sync::nonMediaFiles', array($this, 'sync_non_media_files') );

                // register a dir to sync from sync tab
                add_action( 'sm:sync::register_dir', array($this, 'register_dir') );
                add_action( 'sm:sync::addFile', array($this, 'add_file') );
                // Sync a file.
                add_action( 'sm:sync::syncFile', array($this, 'sync_file'), 10, 3 );
                add_action( 'sm:sync::deleteFile', array($this, 'delete_file') );
                add_action( 'sm:sync::deleteFiles', array($this, 'delete_files') );
            }

            /**
             * Register dir to be sync from Sync tab.
             * @param
             * $dir: The directory to register
             */
            public function register_dir($dir){
                if(!in_array($dir, $this->registered_dir)){
                    $this->registered_dir[] = $dir;
                }
            }

            /**
             * Add file to list of files to be sync from Sync tab.
             * Save the file path to database.
             * @param
             * $file: The file to register.
             */
            public function add_file($file){
                if(!in_array($file, $this->registered_files)){
                    $this->registered_files[] = $file;
                    update_option( 'sm_synced_files', $this->registered_files );
                }
            }

            /**
             * Sync the file to GCS.
             * @param:
             *  $name: Reletive path to upload dir.
             *  $absolutePath: Full path of the file
             *  $forced: Whether to force to move the file to GCS even it's already exists.
             * @return:
             *  $media: Media object returned from client->add_media() method.
             * @throws: Exception File not found
             */
            public function sync_file($name, $absolutePath, $forced = false){
                if(in_array($name, $this->registered_files) && !$forced){
                    return false;
                }

                // add file_path to the file list.
                $this->add_file($name);

                $file_type = wp_check_filetype($absolutePath);
                if(empty($this->client)){
                    $this->client = ud_get_stateless_media()->get_client();
                }

                if( is_wp_error( $this->client ) ) {
                    return;
                }
                
                $file_copied_from_gcs = false;
                $local_file_exists = file_exists( $absolutePath );

                if ( !$local_file_exists && ud_get_stateless_media()->get( 'sm.mode' ) !== 'stateless') {

                    // Try get it and save
                    $result_code = $this->client->get_media( $name, true, $absolutePath );

                    if ( $result_code == 200 ) {
                        $local_file_exists = true;
                        $file_copied_from_gcs = true;
                    }
                }

                if($local_file_exists && !$file_copied_from_gcs){

                    $media = $this->client->add_media( array(
                        'name' => $name,
                        'force' => ($forced == 2),
                        'absolutePath' => $absolutePath,
                        'cacheControl' => apply_filters( 'sm:item:cacheControl', 'public, max-age=36000, must-revalidate', $absolutePath),
                        'contentDisposition' => apply_filters( 'sm:item:contentDisposition', null, $absolutePath),
                        'mimeType' => $file_type['type'],
                        'metadata' => array(
                            'child-of' => dirname($name),
                            'file-hash' => md5( $name ),
                        ),
                    ));

                    // Addon can hook this function to modify database after manual sync done.
                    do_action( 'sm::synced::nonMediaFiles', $name, $absolutePath, $media); // , $media

                    // Stateless mode: we don't need the local version.
                    if(ud_get_stateless_media()->get( 'sm.mode' ) === 'stateless'){
                        unlink($absolutePath);
                    }
                    return $media;
                }

            }

            /**
             * Generate list for manual sync using sync tab. Sync all register files, dir and passed files.
             * @param:
             *  $files: Additional files to sync.
             * @return:
             *  $files: A list of registered files, dir and passed file.
             */
            public function sync_non_media_files($files){
                $upload_dir = wp_upload_dir();
                $files = array_merge($files, $this->registered_files);
                foreach ($this->registered_dir as $key => $dir) {
                    $dir = $upload_dir['basedir'] . "/" . trim($dir, '/') . "/";
                    if(is_dir($dir)){
                        $_files = $this->get_files( $dir );
                        foreach ($_files as $id => $file) {
                            if(!file_exists($file)){
                                continue;
                            }

                            $_file = str_replace(wp_normalize_path($upload_dir['basedir']), '', wp_normalize_path($file));
                            if(!in_array($_file, $files)){
                                $files[] = trim($_file, '/');
                            }
                        }
                    }
                }

                $files = array_values(array_unique($files));
                return $files;
            }
            
            /**
             * Return list of files in a dir.
             * @param:
             *  $dir: Directory path
             * @return: Lists of files in the directory and subdirectory.
             */
            function get_files($dir) {
                $return = array();
                if(is_dir($dir) && $dh = opendir($dir)) {
                    while($file = readdir($dh)){
                        if($file != '.' && $file != '..'){
                            if(is_dir($dir . $file)){
                                // since it is a directory we recursively get files.
                                $arr = $this->get_files($dir . $file . '/');
                                $return = array_merge($return, $arr);
                            }else{
                                $return[] = $dir . $file;   
                            }
                        }
                    }
                    closedir($dh);         
                }
                return $return;
            }

            /**
             * Delete a file from GCS.
             * @param:
             *  $file: File path relative to upload dir.
             * @return: Whether file removed from GCS or not.
             * @todo: Improve workflow. Currently file removing dependent on Registered files list.
             */
            public function delete_file($file, $force = true){
                try{
                    $file = trim($file, '/');
                    if(empty($this->client)){
                        $this->client = ud_get_stateless_media()->get_client();
                    }

                    if( is_wp_error( $this->client ) ) {
                        return;
                    }
                    // Removing file for GCS
                    $this->client->remove_media($file);
                    
                    if($key = array_search($file, $this->registered_files)){
                        if(isset($this->registered_files[$key])){
                            unset($this->registered_files[$key]);
                            update_option( 'sm_synced_files', $this->registered_files );
                        }
                    }
                    return true;
                }
                catch(Exception $e){
                    return false;
                }
            }

            /**
             * Remove registered files of specified dir from GCS.
             * @param:
             *  $dir: Directory path for file to be removed
             * @todo: Improve workflow. Currently file removing dependent on Registered files list.
             */
            public function delete_files($dir){
                if(empty($this->client)){
                    $this->client = ud_get_stateless_media()->get_client();
                }

                if( is_wp_error( $this->client ) ) {
                    return;
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
