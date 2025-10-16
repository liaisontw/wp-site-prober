<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Activity_Logger {

	public $version = '1.0.0';
	public $table_name;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'activity_log';

		register_activation_hook( plugin_dir_path( __FILE__ ) . '../activity-logger.php', [ $this, 'install' ] );

		// register hooks to capture actions
		add_action( 'wp_login', [ $this, 'on_wp_login' ], 10, 2 );
		add_action( 'wp_logout', [ $this, 'on_wp_logout' ] );
		add_action( 'wp_login_failed', [ $this, 'on_login_failed' ] );
		add_action( 'save_post', [ $this, 'on_save_post' ], 10, 3 );
		add_action( 'delete_post', [ $this, 'on_delete_post' ], 10, 1 );
		add_action( 'switch_theme', [ $this, 'on_switch_theme' ], 10, 2 );
		add_action( 'activated_plugin', [ $this, 'on_plugin_activated' ], 10, 2 );
		add_action( 'deactivated_plugin', [ $this, 'on_plugin_deactivated' ], 10, 2 );
		add_action( 'profile_update', [ $this, 'on_profile_update' ], 10, 2 );

		// instantiate admin UI
		new Activity_Logger_Admin( $this );
	}

	/**
	 * Create DB table using dbDelta
	 */
	public function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->table_name} (
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

	/**
	 * Generic logger function
	 */
	public function log( $action, $object_type = '', $object_id = null, $description = '' ) {
		global $wpdb;
		$user_id = null;
		$user = wp_get_current_user();
		if ( $user && $user->ID ) {
			$user_id = $user->ID;
		}
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		$wpdb->insert(
			$this->table_name,
			[
				'user_id'     => $user_id,
				'action'      => sanitize_text_field( $action ),
				'object_type' => sanitize_text_field( $object_type ),
				'object_id'   => $object_id ? intval( $object_id ) : null,
				'description' => wp_kses_post( $description ),
				'ip'          => $ip,
				'user_agent'  => $ua,
			],
			[ '%d', '%s', '%s', '%d', '%s', '%s', '%s' ]
		);
	}

	/* --- hooked handlers --- */
	public function on_wp_login( $user_login, $user ) {
		$this->log( 'user_login', 'user', $user->ID, sprintf( 'User %s logged in', $user_login ) );
	}

	public function on_wp_logout() {
		$user = wp_get_current_user();
		$this->log( 'user_logout', 'user', $user->ID, sprintf( 'User %s logged out', $user->user_login ) );
	}

	public function on_login_failed( $username ) {
		$this->log( 'login_failed', 'user', null, sprintf( 'Failed login attempt for username %s', $username ) );
	}

	public function on_save_post( $post_id, $post, $update ) {
		$action = $update ? 'update_post' : 'create_post';
		$this->log( $action, 'post', $post_id, sprintf( 'Post %d title: %s', $post_id, $post->post_title ) );
	}

	public function on_delete_post( $post_id ) {
		$this->log( 'delete_post', 'post', $post_id, sprintf( 'Post %d deleted', $post_id ) );
	}

	public function on_switch_theme( $new_name, $new_theme ) {
		$this->log( 'switch_theme', 'theme', null, sprintf( 'Switched to theme %s', $new_name ) );
	}

	public function on_plugin_activated( $plugin, $network_wide ) {
		$this->log( 'plugin_activated', 'plugin', null, sprintf( 'Activated plugin %s', $plugin ) );
	}

	public function on_plugin_deactivated( $plugin, $network_wide ) {
		$this->log( 'plugin_deactivated', 'plugin', null, sprintf( 'Deactivated plugin %s', $plugin ) );
	}

	public function on_profile_update( $user_id, $old_user_data ) {
		$this->log( 'profile_updated', 'user', $user_id, sprintf( 'Profile updated for user id %d', $user_id ) );
	}

	/* helpers for admin class */
	public function get_table_name() {
		return $this->table_name;
	}
}
