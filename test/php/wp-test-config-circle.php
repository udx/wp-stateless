<?php

/**  */
define( 'ABSPATH', dirname( dirname( dirname( __FILE__ ) ) ) . '/vendor/usabilitydynamics/wordpress/' );

/** */
define( 'DB_NAME', 'circle_test' );
define( 'DB_USER', 'ubuntu' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', 'localhost' );
define( 'DB_CHARSET', 'utf8' );
define( 'DB_COLLATE', '' );

define( 'WPLANG', '' );
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_DISPLAY', true );

define( 'WP_TESTS_DOMAIN', 'http://unit-tests.ud-dev.com' );
define( 'WP_TESTS_EMAIL', 'unittest@usabilitydynamics.com' );
define( 'WP_TESTS_TITLE', 'PHPUnit Test Blog' );
define( 'WP_TESTS_NETWORK_TITLE', 'PHPUnit Test Network' );
define( 'WP_TESTS_SUBDOMAIN_INSTALL', true );
$base = '/';

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

$table_prefix  = 'wp_';

define( 'WP_PHP_BINARY', 'php' );