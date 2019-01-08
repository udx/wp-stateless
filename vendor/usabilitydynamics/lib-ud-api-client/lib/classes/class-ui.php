<?php
/**
 * Screen UI
 *
 * @namespace UsabilityDynamics
 *
 */
namespace UsabilityDynamics\UD_API {

  if( !class_exists( 'UsabilityDynamics\UD_API\UI' ) ) {

    /**
     * 
     * @author: peshkov@UD
     */
    class UI extends Scaffold {
      
      /**
       * Available Screens
       */
      public $available_screens;
      
      /**
       * Token
       */
      public $token;
      
      /**
       * Constructor
       */
      public function __construct( $args ) {
        parent::__construct( $args );
        $this->token = isset( $args[ 'token' ] ) ? $args[ 'token' ] : array();
        $this->available_screens = isset( $args[ 'screens' ] ) ? $args[ 'screens' ] : array();
      }
      
      /**
       * Generate header HTML.
       * @access  public
       * @since   1.0.0
       * @return  void
       */
      public function get_header ( $token = 'ud-license-manager' ) {
        global $current_screen;
        do_action( 'ud_licenses_screen_before', $token );
        $html = '<div class="wrap ud-licenses-wrap">' . "\n";
        $html .= '<h2 class="ud-licenses-title">' . get_admin_page_title() . '</h2>' . "\n";
        $html .= '<h2 class="nav-tab-wrapper">' . "\n";
        $html .= $this->get_navigation_tabs();
        $html .= '</h2>' . "\n";
        echo $html;
        do_action( 'ud_licenses_screen_header_before_content', $token );
      }

      /**
       * Generate footer HTML.
       * @access  public
       * @since   1.0.0
       * @return  void
       */
      public function get_footer ( $token = 'ud-license-manager', $screen_icon = 'tools' ) {
        do_action( 'ud_licenses_screen_footer_after_content', $token, $screen_icon );
        $html = '</div><!--/.wrap ud-licenses-wrap-->' . "\n";
        echo $html;
        do_action( 'ud_licenses_screen_after', $token, $screen_icon );
      }

      /**
       * Generate navigation tabs HTML, based on a specific admin menu.
       * @access  public
       * @since   1.0.0
       * @return  string/WP_Error
       */
      public function get_navigation_tabs () {
        $html = '';

        $screens = !empty( $this->available_screens ) && is_array( $this->available_screens ) ? $this->available_screens : array();
        $licenses_url = get_option( $this->token . '-url', '' );
        
        $current_tab = self::get_current_screen();
        if ( 0 < count( $screens ) ) {
          foreach ( $screens as $k => $v ) {
            $class = 'nav-tab';
            if ( $current_tab == $k ) {
              $class .= ' nav-tab-active';
            }
            $url = add_query_arg( 'screen', $k, $licenses_url );
            $html .= '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $v ) . '</a>';
          }
        }
        return $html;
      }

      /**
       * Return the token for the current screen.
       * @access  public
       * @since   1.0.0
       * @return  string The token for the current screen.
       */
      public function get_current_screen () {
        $screen = 'licenses'; // Default.
        if ( isset( $_GET['screen'] ) && '' != $_GET['screen'] ) $screen = esc_attr( $_GET['screen'] );
        return $screen;
      }
      
    }
  
  }
  
}