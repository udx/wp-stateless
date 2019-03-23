<?php
/**
 * Usability Dynamics Dashboard.
 *
 * @namespace UsabilityDynamics
 */
namespace UsabilityDynamics\WP {

  if( !class_exists( 'UsabilityDynamics\WP\Dashboard' ) ) {

    /**
     * UD Plugins/Themes dashboard
     * 
     * @author korotkov@ud
     */
    class Dashboard extends Scaffold {
      
      /**
       * Singleton instance
       * 
       * @var type 
       */
      static $instance = null;
      
      /**
       * Dashboard page slug
       * 
       * @var string 
       */
      public $page_slug = 'ud-splash';
      
      /**
       * Need splash key
       * 
       * @var string 
       */
      public $need_splash_key = 'ud_need_splash';
      
      /**
       * Transient key
       * 
       * @var string 
       */
      public $transient_key = 'ud_splash_dashboard';
      
      /**
       * Singleton
       * 
       * @return object
       */
      static function get_instance() {
        return self::$instance ? self::$instance : self::$instance = new Dashboard();
      }
      
      /**
       * Maybe redirect to UD Splash Page
       * 
       * @author korotkov@ud
       */
      public function maybe_ud_splash_page() {
        //** If there is something to show */
        if ( get_transient( $this->need_splash_key ) && ( !isset( $_REQUEST['page'] ) || $_REQUEST['page'] !== $this->page_slug ) ) {
          //** Do not redirect anymore */
          delete_transient( $this->need_splash_key );
          //** Redirect to UD splash page */
          wp_redirect( admin_url( 'index.php?page='.$this->page_slug ) );
          exit;
        }
      }
      
      /**
       * Register fake page
       * 
       * @return null
       */
      public function add_ud_splash_page() {
        if ( empty( $_GET['page'] ) ) {
          return;
        }
        if ( $_GET['page'] == $this->page_slug ) {
          add_dashboard_page( __( 'Welcome to Usability Dynamics, Inc.', $this->domain ), __( 'Welcome', $this->domain ), 'manage_options', $this->page_slug, array( $this, 'ud_splash_page' ) );
        }
      }
      
      /**
       * Render UD dashboard page
       * 
       * @author korotkov@ud
       */
      public function ud_splash_page() {
        //** Try to get information to show */
        $updates = get_transient( $this->transient_key );
        ?>
        <div class="wrap about-wrap">
          <h1><?php _e( 'Usability Dynamics, Inc.', $this->domain ) ?></h1>
          <div class="about-text"><?php _e( 'Thank you for using our products.', $this->domain ) ?></div>
          <div class="wp-badge ud-badge"></div>
          <?php
            //** If user visited this page directly */
            if ( !$updates ) { 
              echo '<h2>Keep it up! There are no notices for you in the moment.</h2>'; 
              
            //** in other case - show corresponding predefined template if it exists*/
            } else {
              foreach( $updates as $key => $page_data ) {
                update_option( $key . '-splash-version', $page_data['version'] );
                if ( file_exists( $page_data['content'] ) ) {
                  include $page_data['content'];
                }
              }
            }
            
            
          ?>
          
          <div class="return-to-dashboard">
            <a href="<?php echo esc_url( self_admin_url() ); ?>"><?php is_blog_admin() ? _e( 'Go to Dashboard &rarr; Home' ) : _e( 'Go to Dashboard' ); ?></a>
          </div>
          
        </div>
        <?php
        delete_transient( $this->need_splash_key );
      }
      
    }
    
  }
}
