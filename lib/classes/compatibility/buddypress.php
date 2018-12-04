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

                add_action('bp_core_fetch_avatar', array($this, 'bp_core_fetch_avatar'), 10, 3);
                add_filter('bp_core_fetch_avatar_url', array($this, 'bp_core_fetch_avatar'), 10, 3);
                // in stateless mode
                add_filter('bp_core_pre_delete_existing_avatar', array($this, 'bp_core_fetch_avatar'), 10, 2);
                // add_action('bp_core_delete_existing_avatar', array($this, 'delete_existing_avatar'));
            }

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

                $bp_upload_path = bp_core_get_upload_dir('upload_path');
                $full_avatar = trim(str_replace( bp_core_avatar_url(), '', $full_avatar ), '/');
                $full_avatar_path = $bp_upload_path . '/' .  $full_avatar;

                $thumb_avatar = trim(str_replace( bp_core_avatar_url(), '', $thumb_avatar ), '/');
                $thumb_avatar_path = $bp_upload_path . '/' .  $thumb_avatar;

                do_action( 'sm:sync::syncFile', apply_filters( 'wp_stateless_file_name', $full_avatar), $full_avatar_path, true, array('stateless' => false));
                do_action( 'sm:sync::syncFile', apply_filters( 'wp_stateless_file_name', $thumb_avatar), $thumb_avatar_path, true, array('stateless' => false));

            }

            public function bp_core_fetch_avatar($url){
                $url = ud_get_stateless_media()->the_content_filter($url);
                // die();
                return $url;
            }

            public function delete_existing_avatar($return, $args){
                // print_r(func_get_args());
                if(empty($args['object']) && empty($args['item_id'])){
                    return $return;
                }

                $_full_avatar = bp_core_fetch_avatar( array(
                    'object'  => $args['object'],
                    'item_id' => $args['item_id'],
                    'html'    => false,
                    'type'    => 'full',
                ) );
                $_thumb_avatar = bp_core_fetch_avatar( array(
                    'object'  => $args['object'],
                    'item_id' => $args['item_id'],
                    'html'    => false,
                    'type'    => 'thumb',
                ) );

                $full_avatar = trim(str_replace( bp_core_avatar_url(), '', $_full_avatar ), '/');
                $thumb_avatar = trim(str_replace( bp_core_avatar_url(), '', $_thumb_avatar ), '/');
                if($full_avatar != $_full_avatar){
                    do_action( 'sm:sync::deleteFile', apply_filters( 'wp_stateless_file_name', $full_avatar));
                }
                if($thumb_avatar != $_thumb_avatar){
                    do_action( 'sm:sync::deleteFile', apply_filters( 'wp_stateless_file_name', $thumb_avatar));
                }

                // var_dump($_full_avatar);
                // var_dump($full_avatar);
                // var_dump($_thumb_avatar);
                // var_dump($thumb_avatar);

                return $return;
            }

        }

    }

}
