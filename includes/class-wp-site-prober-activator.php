<?php

/**
 * Fired during plugin activation
 *
 * @link       https://github.com/liaisontw
 * @since      1.0.0
 *
 * @package    wp_site_prober
 * @subpackage wp_site_prober/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    wp_site_prober
 * @subpackage wp_site_prober/includes
 * @author     liason <liaison.tw@gmail.com>
 */
class wp_site_prober_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		add_option( 'wp_site_prober_active', 'yes' );
		add_option( 'wp_site_prober_template_text', 'Read More' );
		add_option( 'wp_site_prober_template_padding', '..' );
	}

}
