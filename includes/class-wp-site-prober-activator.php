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
		self::_create_tables();
	}

	/**
	 * Create DB table using dbDelta
	 */

	protected static function _create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// set up DB name
		$table_name = $wpdb->wpsp_activity;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) DEFAULT NULL,
			action varchar(191) NOT NULL,
			object_type varchar(100) DEFAULT NULL,
			object_id bigint(20) DEFAULT NULL,
			description text DEFAULT NULL,
			ip varchar(45) DEFAULT NULL,
			user_agent text DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";

		dbDelta( $sql );
	}

	protected static function _create_tables_custom_log() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// set up DB name
		$table_name = $wpdb->wpsp_custom_log;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			log_id bigint(20) DEFAULT NULL,
			plugin_name varchar(191) NOT NULL,
			message text DEFAULT NULL,
			severity bigint(20) DEFAULT NULL,
			session_type varchar(45) DEFAULT NULL,
			session_id bigint(20) DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id)
		) $charset_collate;";

		dbDelta( $sql );
	}

}
