<?php

// Instantiate and load Settings.
$settings  = new Settings(array(
  "store" => "options",
  "key" => "settings_test",
  "format" => "object"
));

$settings->set( 'make', 'Chevy' );
$settings->set( 'model', 'Tahoe' );
$settings->set( 'features', array(
  'ac',
  'stuff'
));

$settings->set( 'features', array(
  'dvd',
  'sunroof'
));

$settings->set( 'options', array(
  "rims" => '24',
  "towing" => true,
  "onstar" => 'active'
));

$settings->set( 'options', array(
  "gps" => 'standard'
));

//echo '<br />get all: <pre>' . print_r( $settings->get(), true ) . '</pre>';
echo '<br />get make: ' . print_r( $settings->get( 'make' ), true );
echo '<br />get options.gps: ' . print_r( $settings->get( 'options.gps' ), true );
echo '<br />get features: ' . print_r( $settings->get( 'features' ), true );
die( '<pre>get all' . print_r( $settings->get(), true ) . '</pre>' );
