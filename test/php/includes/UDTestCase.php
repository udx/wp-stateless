<?php
/**
 * 
 * @class UD_Plugin_WP_UnitTestCase
 */
class UD_Plugin_WP_UnitTestCase extends WP_UnitTestCase {

  protected $root_dir;
  
  protected $instance;

  /**
   * WP Test Framework Constructor
   */
  function setUp() {
	  parent::setUp();
    $this->root_dir = dirname( dirname( dirname( __DIR__ ) ) );
    if( file_exists( $this->root_dir . '/stateless-media.php' ) ) {
      include_once( $this->root_dir . '/stateless-media.php' );
    }
    if( !class_exists( '\wpCloud\StatelessMedia\Bootstrap' ) ) {
      $this->fail( 'Plugin is not available.' );
    }
    $this->instance = \wpCloud\StatelessMedia\Bootstrap::get_instance();
  }
  
  /**
   * WP Test Framework Destructor
   */
  function tearDown() {
	  parent::tearDown();
    $this->root_dir = NULL;
    $this->instance = NULL;
  }
  
}
