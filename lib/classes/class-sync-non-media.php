<?php
/**
 * Need to improve workflow.
 * Maybe add a transient of few days to keep track of synced files.
 */

namespace wpCloud\StatelessMedia {

    if (!class_exists('wpCloud\StatelessMedia\SyncNonMedia')) {

        class SyncNonMedia {
            
            private $registered_dir = array();
            const table = 'sm_sync';
            public $table_name;
            
            public function __construct(){
                global $wpdb;
                $this->table_name = $wpdb->prefix . self::table;
                ud_get_stateless_media()->create_db();

                // Manual sync using sync tab. 
                // called from ajax action_get_non_library_files_id
                // Return files to be manually sync from sync tab.
                add_filter( 'sm:sync::nonMediaFiles', array($this, 'sync_non_media_files') );
                add_filter( 'sm:sync::queue_is_exists', array($this, 'queue_is_exists'), 10, 2 );

                // register a dir to sync from sync tab
                add_action( 'sm:sync::register_dir', array($this, 'register_dir') );
                add_action( 'sm:sync::addFile', array($this, 'add_file') );
                // Sync a file.
                add_action( 'sm:sync::syncFile', array($this, 'sync_file'), 10, 4 );
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
                $this->queue_add_file($file);
            }

            /**
             * Sync the file to GCS.
             * @param:
             *  $name: Relative path to upload dir.
             *  $absolutePath: Full path of the file
             *  $forced: Type: bool/2; Whether to force to move the file to GCS even it's already exists.
             *           true: Check whether it's already synced or not in database.
             *           2 (int): Force to overwrite on GCS
             * 
             * @return:
             *  $media: Media object returned from client->add_media() method.
             * @throws: Exception File not found
             */
            public function sync_file($name, $absolutePath, $forced = false, $args = array()){
                $args = wp_parse_args($args, array(
                    'stateless' => true, // whether to delete local file in stateless mode.
                    'download'  => false, // whether to delete local file in stateless mode.
                ));
                
                if($this->queue_is_exists($name, 'synced') && !$forced){
                    return false;
                }

                $file_type = wp_check_filetype($absolutePath);
                if(empty($this->client)){
                    $this->client = ud_get_stateless_media()->get_client();
                }

                if( is_wp_error( $this->client ) ) {
                    return;
                }
                
                $file_copied_from_gcs = false;
                $local_file_exists = file_exists( $absolutePath );

                do_action( 'sm::pre::sync::nonMediaFiles', $name, $absolutePath); // , $media

                if ( !$local_file_exists && ( $args['download'] || ud_get_stateless_media()->get( 'sm.mode' ) !== 'stateless' ) ) {

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
                        'cacheControl' => apply_filters( 'sm:item:cacheControl', 'public, max-age=36000, must-revalidate', $absolutePath), //@todo use cacheControl from settings page.
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
                    if($args['stateless'] == true && ud_get_stateless_media()->get( 'sm.mode' ) === 'stateless'){
                        unlink($absolutePath);
                    }
                    // add file_path to the file list.
                    $this->queue_add_file($name, 'synced');
                    return $media;
                }

            }

            /**
             * Generate list for manual sync using sync tab. Sync all register files, dir and passed files.
             * @param array $files - Additional files to sync.
             * @return array
             */
            public function sync_non_media_files($files = array()){
                $upload_dir = wp_upload_dir();
                $files = array_merge($files, $this->queue_get_all());
                foreach ($this->registered_dir as $key => $dir) {
                    $dir = $upload_dir['basedir'] . "/" . trim($dir, '/') . "/";
                    if(is_dir($dir)){
                        // Getting all the files from dir recursively.
                        $_files = $this->get_files( $dir );
                        // validating and adding to the $files array.
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

                // $files = array_values(array_unique($files));
                return $files;
            }

            /**
             * Return list of files in a dir.
             * @param string $dir: Directory path
             * @return array - Lists of files in the directory and subdirectory.
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
             *
             * @param $file
             * @param bool $force
             * @return bool
             */
            public function delete_file($file, $force = true){
                try{
                    $file = trim($file, '/');
                    if(empty($this->client)){
                        $this->client = ud_get_stateless_media()->get_client();
                    }

                    if( is_wp_error( $this->client ) ) {
                        return false;
                    }
                    // Removing file for GCS
                    $this->client->remove_media($file);
                    $this->queue_remove_file($file);
                    return true;
                }
                catch(\Exception $e){
                    return false;
                }
            }

            /**
             * Remove registered files of specified dir from GCS.
             *
             * @param $dir
             * @return bool|void
             */
            public function delete_files($dir){
                if(empty($this->client)){
                    $this->client = ud_get_stateless_media()->get_client();
                }

                if( is_wp_error( $this->client ) ) {
                    return;
                }

                // Removing the files one by one.
                foreach ($this->queue_get_all($dir) as $key => $file) {
                    if(strpos($file, $dir) !== false){
                        $this->client->remove_media($file);
                        $this->queue_remove_file($file);
                    }
                }
                
                return true;
            }

            /***
             * Return all the files from the database.
             * @return array of files.
             */
            public function queue_get_all($prefix = ''){
                global $wpdb;
                if($prefix){
                    $files = $wpdb->get_col( $wpdb->prepare("SELECT file FROM $this->table_name WHERE file like '%s'", $wpdb->esc_like($prefix) . '%' ) );
                }
                else{
                    $files = $wpdb->get_col( "SELECT file FROM $this->table_name" );
                }
                if(!empty($files) && is_array($files))
                    return $files;
                return array();
            }

            /***
             * Checks whether a file is exist in database.
             * @params 
             *      $file: Path of file relative to upload dir.
             *      $status: optional. queued|synced
             * @return non boolean true. number of item found in db.
             */
            public function queue_is_exists($file, $status = ''){
                global $wpdb;
                if(empty($status)){
                    return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name} WHERE file = '%s';", $file));
                }
                else{
                    return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_name} WHERE file = '%s' AND status = '%s';", $file, $status));
                }
            }

            /***
             * Add file to the database.
             * @params 
             *      $file: Path of file relative to upload dir.
             *      $status: optional. queued|synced
             * @return boolean
             */
            public function queue_add_file($file, $status = 'queued'){
                global $wpdb;
                if($this->queue_is_exists($file)){
                    return $wpdb->update( $this->table_name, array( 'file' => $file, 'status' => $status ), array('file' => $file), array( '%s', '%s' ), array( '%s' ) ); 
                }
                else{
                    return $wpdb->insert( $this->table_name, array( 'file' => $file, 'status' => $status ), array( '%s', '%s' ) ); 
                }
                return false;
            }

            /***
             * Deletes a entry from database.
             * @params 
             *      $file: Path of file relative to upload dir.
             * @return boolean
             */
            public function queue_remove_file($file){
                global $wpdb;
                return $wpdb->delete( $this->table_name, array( 'file' => $file), array( '%s' ) ); 
            }
        }
    }
}
