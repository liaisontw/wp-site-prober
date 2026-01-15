<?php
define('WP_RUNNING_PHPUNIT', true);

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}


if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // WPCS: XSS ok.
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */

function _manually_load_plugin() {
	require dirname( __DIR__, 1 ) . '/liaison-site-prober.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.

define( 'DB_NAME', getenv( 'DB_NAME' ) ?: 'wordpress_test' );
define( 'DB_USER', getenv( 'DB_USER' ) ?: 'root' );
define( 'DB_PASSWORD', getenv( 'DB_PASSWORD' ) ?: 'root' );
define( 'DB_HOST', getenv( 'DB_HOST' ) ?: '127.0.0.1' );

require $_tests_dir . '/includes/bootstrap.php';







