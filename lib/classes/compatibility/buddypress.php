<?php
/**
 * Plugin Name: BuddyPress
 * Plugin URI: https://wordpress.org/plugins/buddypress/
 *
 * Compatibility Description: Ensures compatibility with BuddyPress.
 *
 */

namespace wpCloud\StatelessMedia {

    if(!class_exists('wpCloud\StatelessMedia\BuddyPress')) {
        
        class BuddyPress extends ICompatibility {
            protected $id = 'buddypress';
            protected $title = 'BuddyPress';
            protected $constant = 'WP_STATELESS_COMPATIBILITY_BUDDYPRESS';
            protected $description = 'Ensures compatibility with BuddyPress.';
            protected $plugin_file = ['buddypress/bp-loader.php'];

            public function module_init($sm){
                add_action('xprofile_avatar_uploaded', array($this, 'avatar_uploaded'), 10, 3);
                add_action('groups_avatar_uploaded', array($this, 'avatar_uploaded'), 10, 3);
                
                add_filter('bp_core_fetch_avatar', array($this, 'bp_core_fetch_avatar'), 10, 3);
                add_filter('bp_core_fetch_avatar_url', array($this, 'bp_core_fetch_avatar_url'), 10, 3);
                add_filter('bp_core_pre_delete_existing_avatar', array($this, 'delete_existing_avatar'), 10, 2);
                add_filter('bp_attachments_pre_get_attachment', array($this, 'bp_attachments_pre_get_attachment'), 10, 2);

            }

            /**
             * Sync avatar.
             */
            public function avatar_uploaded($item_id, $type, $r){
                $full_avatar = bp_core_fetch_avatar( array(
                    'object'  => $r['object'],
                    'item_id' => $r['item_id'],
                    'html'    => false,
                    'type'    => 'full',
                ) );
                $thumb_avatar = bp_core_fetch_avatar( array(
                    'object'  => $r['object'],
                    'item_id' => $r['item_id'],
                    'html'    => false,
                    'type'    => 'thumb',
                ) );

                $wp_uploads_dir = wp_get_upload_dir();


                $full_avatar_path = $wp_uploads_dir['basedir'] . '/' . apply_filters( 'wp_stateless_file_name', $full_avatar, false);
                $full_avatar = apply_filters( 'wp_stateless_file_name', $full_avatar);

                $thumb_avatar_path = $wp_uploads_dir['basedir'] . '/' . apply_filters( 'wp_stateless_file_name', $thumb_avatar, false);
                $thumb_avatar = apply_filters( 'wp_stateless_file_name', $thumb_avatar);

                do_action( 'sm:sync::syncFile', $full_avatar, $full_avatar_path, true, array('stateless' => false));
                do_action( 'sm:sync::syncFile', $thumb_avatar, $thumb_avatar_path, true, array('stateless' => false));

            }

            /**
             * Convert image url in image html to GCS URL.
             *
             * @param [type] $image_html html code for image.
             * @return void
             */
            public function bp_core_fetch_avatar($image_html){
                try {
                    preg_match("/src=(?:'|\")(.*?)(?:'|\")/", $image_html, $image_url);
                    if(!empty($image_url[1])){
                        $gs_image_url = $this->bp_core_fetch_avatar_url($image_url[1]);
                        $image_html = str_replace($image_url[1], $gs_image_url, $image_html);
                    }
                } catch (\Throwable $th) {
                    //throw $th;
                }
                return $image_html;
            }

            /**
             * Sync then return GCS url.
             *
             * @param [type] $url image url.
             * @return void
             */
            public function bp_core_fetch_avatar_url($url){
                $wp_uploads_dir = wp_get_upload_dir();
                $name = apply_filters( 'wp_stateless_file_name', $url);
                $full_avatar_path = $wp_uploads_dir['basedir'] . '/' . $name;

                
                $root_dir = ud_get_stateless_media()->get( 'sm.root_dir' );
                $root_dir = trim( $root_dir, '/ ' ); // Remove any forward slash and empty space.
                // Making sure that we only modify url for uploads dir.
                // @todo support photo in plugins directory.


                if(strpos($name, plugins_url()) === 0){
                    $name = str_replace(plugins_url() . '/', '', $name);
                    $name = apply_filters( 'wp_stateless_file_name', $name);
                    $full_avatar_path = WP_PLUGIN_DIR . '/' . $name;
                }
                
                if(strpos($name, "$root_dir/http") !== 0 && strpos($name, "http") !== 0 && $root_dir !== $name){
                    do_action( 'sm:sync::syncFile', $name, $full_avatar_path, false, array('stateless' => false));
                    $url = ud_get_stateless_media()->get_gs_host() . '/' . $name;
                }
                return $url;
            }

            /**
             * Deleting avatar from GCS.
             */
            public function delete_existing_avatar($return, $args){
                if(empty($args['object']) && empty($args['item_id'])){
                    return $return;
                }

                $full_avatar = bp_core_fetch_avatar( array(
                    'object'  => $args['object'],
                    'item_id' => $args['item_id'],
                    'html'    => false,
                    'type'    => 'full',
                ) );
                $thumb_avatar = bp_core_fetch_avatar( array(
                    'object'  => $args['object'],
                    'item_id' => $args['item_id'],
                    'html'    => false,
                    'type'    => 'thumb',
                ) );

                do_action( 'sm:sync::deleteFile', apply_filters( 'wp_stateless_file_name', $full_avatar));
                do_action( 'sm:sync::deleteFile', apply_filters( 'wp_stateless_file_name', $thumb_avatar));

                if(ud_get_stateless_media()->get( 'sm.mode' ) === 'stateless'){
                    $return = false;
                }

                return $return;
            }

            /**
             * Sync and return GCS url for group images.
             * 
             * Used as CSS background-image.
             *
             * @param [type] $return
             * @param [type] $r
             * @return void
             */
            public function bp_attachments_pre_get_attachment($return, $r){
                // Return if this is a recursive call.
                if(!empty($r['recursive'])){
                    return $return;
                }

                try {
                    $debug_backtrace = \debug_backtrace(false);

                    // Making sure we only return GCS link if the type is url.
                    if(!empty($debug_backtrace[3]['args'][0]) && $debug_backtrace[3]['args'][0] == 'url'){
                        $r['recursive'] = true;

                        $url = bp_attachments_get_attachment('url', $r);
                        $name = apply_filters( 'wp_stateless_file_name', $url);

                        $root_dir = ud_get_stateless_media()->get( 'sm.root_dir' );
                        $root_dir = trim( $root_dir, '/ ' ); // Remove any forward slash and empty space.

                        if(!empty($name) && $root_dir . "/" != $name){
                            $full_path = bp_attachments_get_attachment(false, $r);
                            do_action( 'sm:sync::syncFile', $name, $full_path, false, array('stateless' => false));
                            $return = ud_get_stateless_media()->get_gs_host() . '/' . $name;
                        }

                    }
                } catch (\Throwable $th) {
                    //throw $th;
                }


                return $return;
            }

        }

    }

}
