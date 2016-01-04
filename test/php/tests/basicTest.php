<?php
/**
 * Just make sure the test framework is working
 *
 * @class BasicTest
 */
class BasicTest extends WP_UnitTestCase {

  var $val;

  /**
   * WP Test Framework Constructor
   */
  function setUp() {
	  parent::setUp();
    $this->val = true;
  }
  
  /**
   * WP Test Framework Destructor
   */
  function tearDown() {
	  parent::tearDown();
    $this->val = false;
  }
  
  /**
   * Just make sure the test framework is working
   *
   * @group basic
   */
  function testTrue() {
    $this->assertTrue( $this->val );
  }
  
  /**
   * First test for a lame bug in PHPUnit that broke the $GLOBALS reference
   *
   * @group basic
   */   
  function testGlobals() {
    global $test_foo;
    $test_foo = array( 'foo', 'bar', 'baz' );

    function test_globals_foo() {
      unset( $GLOBALS[ 'test_foo' ][1] );
    }

    test_globals_foo();

    $this->assertEquals( $test_foo, array( 0 => 'foo', 2 => 'baz' ) );
    $this->assertEquals( $test_foo, $GLOBALS[ 'test_foo' ] );
  }

  /**
   * Second test for a lame bug in PHPUnit that broke the $GLOBALS reference
   *
   * @group basic
   */   
  function testGlobalsBar() {
    global $test_bar;
    $test_bar = array( 'a', 'b', 'c' );
    $this->assertEquals( $test_bar, $GLOBALS[ 'test_bar' ] );
  }
  
}
