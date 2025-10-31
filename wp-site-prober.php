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

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WP_SITE_PROBER_VERSION', '1.0.0' );

//require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-site-prober.php';
//require_once plugin_dir_path( __FILE__ ) . 'admin/class-wp-site-prober-admin.php';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wp-site-prober-activator.php
 */
function activate_wp_site_prober() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-site-prober-activator.php';
	wp_site_prober_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wp-site-prober-deactivator.php
 */
function deactivate_wp_site_prober() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-site-prober-deactivator.php';
	wp_site_prober_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wp_site_prober' );
register_deactivation_hook( __FILE__, 'deactivate_wp_site_prober' );


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wp-site-prober.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wp_site_prober() {

	$plugin = new WP_Site_Prober();
	$plugin->run();

}
run_wp_site_prober();

/*
function wp_site_prober() {
	static $instance = null;
	if ( null === $instance ) {
		$instance = new WP_Site_Prober();
	}
	return $instance;
}

// bootstrap
add_action( 'plugins_loaded', 'wp_site_prober' );

// helper global func
function wp_site_prober_log( $action, $object_type = '', $object_id = null, $description = '' ) {
	$logger = WP_Site_Prober();
	$logger->log( $action, $object_type, $object_id, $description );
}
*/
