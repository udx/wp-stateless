<?php
/**
 * Plugin Name: Advanced Custom Fields: Image Crop Add-on
 * Plugin URI: https://wordpress.org/plugins/acf-image-crop-add-on/
 *
 * Compatibility Description: 
 *
 */

namespace wpCloud\StatelessMedia {

    if(!class_exists('wpCloud\StatelessMedia\CompatibilityAcfImageCrop')) {
        
        class CompatibilityAcfImageCrop extends ICompatibility {
            const ID = 'acf-image-crop';
            const TITLE = 'Acf Image Crop';
            const DESCRIPTION = 'ACF image crop addons compatibility';
            
            public function __construct(){
                $modules = get_option('stateless-modules', array());
                
                Module::register_module(self::ID, self::TITLE, self::DESCRIPTION, in_array(self::ID, $modules));

                if (in_array(self::ID, $modules)) {
                    add_action('sm::module::init', array($this, 'module_init'));
                }
            }

            public function module_init($sm){
                if ($sm['mode'] === 'stateless') {
                    /**
                     * ACF image crop addons compatibility.
                     * We hook into image crops admin_ajax crop request and alter
                     * wp_upload_dir() using upload_dir filter.
                     * Then we remove the filter once the plugin get the GCS image link.
                     *
                     */
                    add_action( 'wp_ajax_acf_image_crop_perform_crop', array( $this, 'acf_image_crop_perform_crop' ), 1 );
                    
                    /*
                    * In stateless mode no local copy of images is available.
                    * So we need to filter full image path before generate_cropped_image() function uses to 
                    * get image editor using wp_get_image_editor.
                    * We will hook into acf-image-crop/full_image_path filter and return GCS link if available.
                    * 
                    */
                    add_action( 'acf-image-crop/full_image_path', array( $this, 'acf_image_crop_full_image_path' ), 10, 3 );

                }
            }

            /**
             * Alter wp_upload_dir() using upload_dir filter.
             * Then we remove the filter once the plugin get the GCS image link.
             *
             */
            public function acf_image_crop_perform_crop(){
                add_filter('upload_dir', array( $this, 'upload_dir') );
                // Removing upload_dir filter.
                add_filter('acf-image-crop/filename_postfix', array( $this, 'remove_filter_upload_dir') );
            }

            /**
             * Remove upload_dir filter as it's work is done.
             * Used acf-image-crop/filename_postfix as intermediate/temporary hook.
             * We need to remove the upload_dir filter before that function tries to
             * insert attachment to media library. Unless media library would get confused.
             *
             */
            public function remove_filter_upload_dir($postfix=''){
                remove_filter('upload_dir', array( $this, 'upload_dir') );
                return $postfix;
            }

            /*
            * Only for stateless mode.
            * Filter image link generate_cropped_image() uses to get image editor.
            * As no local copy of the image is available we need to filter the image path.
            *
            * @param $full_image_path: Expected local image path.
            * @param $id: Image/attachment ID
            * @param $meta_data: Image/attachment meta data.
            *
            * @return GCS link if it has gs_link in meta data.
            */
            public function acf_image_crop_full_image_path( $full_image_path, $id, $meta_data ){
                if(!empty($meta_data['gs_link']))
                $full_image_path = $meta_data['gs_link'];
                return $full_image_path;
            }
        }

    }

}
