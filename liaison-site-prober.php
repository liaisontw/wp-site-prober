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
 * @package           liaison-site-prober
 *
 * @wordpress-plugin
 * Plugin Name:       Liaison Site Prober
 * Plugin URI:        https://github.com/liaisontw/wp-site-prober
 * Description:       Simple activity / audit logger for WordPress. Creates activity table, hooks common events, admin UI and CSV export.
 * Version:           1.0.0
 * Author:            liason
 * Author URI:        https://github.com/liaisontw/
 * License: 		  GPLv3 or later  
 * License URI: 	  https://www.gnu.org/licenses/gpl-3.0.html  
 * Text Domain:       liaison-site-prober
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
define( 'LIAISIPR_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-liaison-site-prober-activator.php
 */
function liaisonsp_activate_wp_site_prober() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-liaison-site-prober-activator.php';
	LIAISIPR_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-liaison-site-prober-deactivator.php
 */
function liaisonsp_deactivate_wp_site_prober() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-liaison-site-prober-deactivator.php';
	LIAISIPR_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'liaisonsp_activate_wp_site_prober' );
register_deactivation_hook( __FILE__, 'liaisonsp_deactivate_wp_site_prober' );


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-liaison-site-prober.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function liaisonsp_run_wp_site_prober() {

	$plugin = new LIAISIPR();
	$plugin->run();

}
liaisonsp_run_wp_site_prober();

