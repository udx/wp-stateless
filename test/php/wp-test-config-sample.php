<?php
/**
 * The current file can be used to run PHPUnit tests on local machine.
 *
 */


/** 
 * SET ABSPATH TO YOUR WORPDRESS ROOT DIRECTORY
 * Note,
 * you can use already existing wordpress core in 
 * /vendor/automattic/wordpress/
 */
define( 'ABSPATH', '' );

/** 
 * SET DATABASE CREDENTIALS 
 */
define( 'DB_NAME', '' );
define( 'DB_USER', '' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

/** 
 * SET DOMAIN AND EMAIL. 
 * IT WILL BE USED FOR SETTING YOUR SITE_URL AND HOME_URL
 */
// Example value: http://example.com
define( 'WP_TESTS_DOMAIN', '' );
// Example value: test@example.com
define( 'WP_TESTS_EMAIL', '' );

/** THAT'S ALL. DO NOT MODIFY CODE BELOW. */

define( 'WP_TESTS_TITLE', 'PHPUnit Test Blog' );
define( 'WP_TESTS_NETWORK_TITLE', 'PHPUnit Test Network' );
define( 'WP_TESTS_SUBDOMAIN_INSTALL', true );
$base = '/';

define( 'WPLANG', '' );
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_DISPLAY', true );

/* Cron tries to make an HTTP request to the blog, which always fails, because tests are run in CLI mode only */
define( 'DISABLE_WP_CRON', true );

define( 'WP_ALLOW_MULTISITE', false );
if ( WP_ALLOW_MULTISITE ) {
	define( 'WP_TESTS_BLOGS', 'first,second,third,fourth' );
}
if ( WP_ALLOW_MULTISITE && !defined('WP_INSTALLING') ) {
	define( 'SUBDOMAIN_INSTALL', WP_TESTS_SUBDOMAIN_INSTALL );
	define( 'MULTISITE', true );
	define( 'DOMAIN_CURRENT_SITE', WP_TESTS_DOMAIN );
	define( 'PATH_CURRENT_SITE', '/' );
	define( 'SITE_ID_CURRENT_SITE', 1);
	define( 'BLOG_ID_CURRENT_SITE', 1);
	//define( 'SUNRISE', TRUE );
}

$table_prefix  = 'test_';

define( 'WP_PHP_BINARY', 'php' );