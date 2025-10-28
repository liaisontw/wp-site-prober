<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/liaisontw/wp-site-prober
 * @since             1.0.0
 * @package           wp-site-prober
 *
 * @wordpress-plugin
 * Plugin Name:       WP Site Prober
 * Plugin URI:        https://github.com/liaisontw/wp-site-prober
 * Description:       Simple activity / audit logger for WordPress. Creates activity table, hooks common events, admin UI and CSV export.
 * Version:           1.0.0
 * Author:            liason
 * Author URI:        https://github.com/liaisontw/
 * License: 		  GPLv3 or later  
 * License URI: 	  https://www.gnu.org/licenses/gpl-3.0.html  
 * Text Domain:       wp-site-prober
 * Domain Path:       /languages
 */


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-site-prober.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/class-wp-site-prober-admin.php';

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
