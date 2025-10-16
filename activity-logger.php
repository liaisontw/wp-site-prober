<?php
/**
 * Plugin Name: Activity Logger
 * Description: Simple activity / audit logger for WordPress (example). Creates activity table, hooks common events, admin UI and CSV export.
 * Version: 1.0.0
 * Author: Generated Example
 * Text Domain: activity-logger
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-activity-logger.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-activity-logger-admin.php';

function activity_logger() {
	static $instance = null;
	if ( null === $instance ) {
		$instance = new Activity_Logger();
	}
	return $instance;
}

// bootstrap
add_action( 'plugins_loaded', 'activity_logger' );

// helper global func
function activity_logger_log( $action, $object_type = '', $object_id = null, $description = '' ) {
	$logger = activity_logger();
	$logger->log( $action, $object_type, $object_id, $description );
}
