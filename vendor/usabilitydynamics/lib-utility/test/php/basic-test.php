<?php
/**
 *
 * Class BasicUtilityTest
 */
class BasicUtilityTest extends PHPUnit_Framework_TestCase {

  /**
   * Test Object Extending with Defaults
   *
   */
  public function testDefaults() {

    $myConfiguration = array(
      "someOther" => 10
    );

    $defaultsSettings = array(
      "someDefault" => 7
    );

    $finalConfiguration = UsabilityDynamics\Utility::defaults( $myConfiguration, $defaultsSettings );

    $this->assertEquals( 7,   $finalConfiguration->someDefault );
    $this->assertEquals( 10,  $finalConfiguration->someOther );

  }

  /**
   * Test Utility::findUp();
   *
   */
  public function testFindUp() {

    // Traverse upward directory tree until fixtures/sample.json is found.
    $sampleJSON = UsabilityDynamics\Utility::findUp( 'fixtures/sample.json' );

    // Traverse upward directory tree until fixtures/sample.xml is found.
    $sampleXML  = UsabilityDynamics\Utility::findUp( 'fixtures/sample.xml' );

    // Traverse upward directory tree until fixtures/sample.php is found.
    UsabilityDynamics\Utility::findUp( 'fixtures/sample.php' );

    // JSON Formatted properly.
    $this->assertEquals( 1.1,   $sampleJSON->anagrafica->{"@version"} );

    // XML Formatted properly.
    $this->assertEquals( 1.1,   (string) $sampleXML->version );

    // Custom class loaded.
    $this->assertEquals( true,  class_exists( 'MySampleClass' ) );

    // Custom class method is accessible.
    $this->assertEquals( true,  method_exists( 'MySampleClass', 'test_method' ) );

  }

}
