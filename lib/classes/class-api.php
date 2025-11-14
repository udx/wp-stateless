<?php
/**
 * API Handler
 *
 *
 *
 * @since 1.0.0
 */
namespace wpCloud\StatelessMedia {

  if( !class_exists( 'wpCloud\StatelessMedia\API' ) ) {


    final class API {

      /**
       * API Status Endpoint.
       *
       * @return array
       */
      static public function status() {

        return array(
          "ok" => true,
          "message" => "API up."
        );

      }

      /**
       * Jobs Endpoint.
       *
       * @return array
       */
      static public function jobs() {

        return array(
          "ok" => true,
          "message" => "Job endpoint up.",
          "jobs" => array()
        );

      }

      /**
       * Get settings Endpoint.
       *
       * @param $request
       * @return array
       */
      static public function getSettings( $request ) {

        if( !self::authRequest( $request ) ) {
          return array("ok" => false, "message" => __( "Auth fail." ));
        }

        $settings = apply_filters('stateless::get_settings', array());

        return array(
            "ok" => true,
            "message" => "getSettings endpoint.",
            "settings" => $settings
        );

      }

      /**
       * Get media library Endpoint.
       *
       * @param $request
       * @return array
       */
      static public function getMediaLibrary( $request ) {

        if( !self::authRequest( $request ) ) {
          return array("ok" => false, "message" => __( "Auth fail." ));
        }

        if( !self::switchBlog( $request ) ){
          return array("ok" => false, "message" => __( "Missed blog param." ));
        }

        $query_images_args = array(
            'post_type' => 'attachment',
            'post_mime_type' =>'image',
            'post_status' => 'inherit',
            'posts_per_page' => -1,
        );

        $query_images = new \WP_Query( $query_images_args );
        $media = array();
        foreach ( $query_images->posts as $image) {
          $media[] = self::mediaMapping($image);
        }

        return array(
            "ok" => true,
            "message" => "getMediaLibrary endpoint.",
            "mediaLibrary" => $media
        );

      }

      /**
       * Get media item Endpoint.
       *
       * @param $request
       * @return array
       */
      static public function getMediaItem( $request ) {

        if( !self::authRequest( $request ) ) {
          return array("ok" => false, "message" => __( "Auth fail." ));
        }

        if( !self::switchBlog( $request ) ){
          return array("ok" => false, "message" => __( "Missed blog param." ));
        }

        $attachment_id = $request->get_param('attachment_id');
        if(!$attachment_id){
          return array("ok" => false, "message" => __( "Missing attachment id." ));
        }

        $attachment = get_post($attachment_id);

        if(!$attachment){
          return array("ok" => false, "message" => __( "Wrong attachment id." ));
        }

        $item = self::mediaMapping($attachment);

        return array(
            "ok" => true,
            "message" => "getMediaItem endpoint.",
            "mediaItem" => $item
        );

      }

      /**
       * Handle Auth.
       *
       * @param $request
       * @return bool
       */
      static public function authRequest( $request = false ) {

        if( !$request ) {
          return false;
        }

        if( !$request->get_param('key') ) {
          return false;
        }

        $settings = apply_filters('stateless::get_settings', array());

        if( !$settings[ 'api_key' ] ) {
          return false;
        }

        if( $request->get_param('key') !== $settings[ 'api_key' ] ) {
          return false;
        }

        return true;


      }

      /**
       * Check blog param and switch to requested blog
       *
       * @param bool $request
       * @return bool
       */
      static public function switchBlog( $request = false ){

        if(!is_multisite()){
          return true;
        }

        if( !$request ) {
          return false;
        }

        $blog_id = $request->get_param('blog');
        if( !$blog_id ) {
          return false;
        }

        $current_blog_id = get_current_blog_id();
        if($current_blog_id == $blog_id){
          return true;
        }

        return switch_to_blog( $blog_id );
      }

      /**
       * Applying mapping for media item
       *
       * @param $image
       * @return array
       */
      static private function mediaMapping($image){

        if(!$image){
          return array();
        }

        return array(
            'attachment_id' => $image->ID,
            'date_create' => $image->post_date,
            'parent' => $image->post_parent,
            'link' => wp_get_attachment_url($image->ID),
            'title' => $image->post_title,
            'description' => $image->post_content,
            'thumbnail' => wp_get_attachment_image_src($image->ID)[0]
        );
      }

    }

  }

}
