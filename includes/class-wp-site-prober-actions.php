<?php

class wp_site_prober_Actions {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

	protected $logger;

	protected $table_name;
	protected $dir;

    public function __construct( $logger, $plugin_name, $version ) {
		$this->logger = $logger;
        $this->plugin_name = $plugin_name;
        $this->version = $version;
		$this->table_name = $this->logger->get_table_name();
		$this->dir = $this->logger->get_plugin_dir();

        // register hooks to capture actions
		add_action( 'activated_plugin', [ $this, 'wpsp_plugin_activated' ], 10, 2 );
		add_action( 'deactivated_plugin', [ $this, 'wpsp_plugin_deactivated' ], 10, 2 );

		add_action( 'wp_login', [ $this, 'wpsp_wp_login' ], 10, 2 );
		add_action( 'wp_logout', [ $this, 'wpsp_wp_logout' ] );
		add_action( 'wp_login_failed', [ $this, 'wpsp_login_failed' ] );
		add_action( 'save_post', [ $this, 'wpsp_save_post' ], 10, 3 );
		add_action( 'delete_post', [ $this, 'wpsp_delete_post' ], 10, 1 );
		add_action( 'switch_theme', [ $this, 'wpsp_switch_theme' ], 10, 2 );
		
		add_action( 'profile_update', [ $this, 'wpsp_profile_update' ], 10, 2 );
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
	private function get_plugin_name( $plugin ) {
		$plugin_name = '';
		if ( false !== strpos( $plugin, '/' ) ) {
			$plugin_dir  = explode( '/', $plugin );
			$plugin_data = array_values( get_plugins( '/' . $plugin_dir[0] ) );
			$plugin_data = array_shift( $plugin_data );
			/*
			ob_start(); // Start output buffering
			var_dump( $plugin_data ); // Dump the variable
			$dump_output = ob_get_clean(); // Get the buffered output and clear the buffer
			error_log(  'Plugin data: ' . $dump_output );
			*/
			$plugin_name = $plugin_data['Name'];

			if ( ! empty( $plugin_data['Version'] ) ) {
				$plugin_version = $plugin_data['Version'];
			}
		}
		
		return $plugin_name;
	}
	public function wpsp_plugin_activated( $plugin, $network_wide ) {
		$plugin_name = $this->get_plugin_name( $plugin );
		$this->log( 'plugin_activated', 'plugin', null, sprintf( 'Activated plugin : %s', $plugin_name ) );
	}

	public function wpsp_plugin_deactivated( $plugin, $network_wide ) {
		$plugin_name = $this->get_plugin_name( $plugin );
		$this->log( 'plugin_deactivated', 'plugin', null, sprintf( 'Deactivated plugin : %s', $plugin_name ) );
	}
	
	public function wpsp_wp_login( $user_login, $user ) {
		$this->log( 'user_login', 'user', $user->ID, sprintf( 'User %s logged in', $user_login ) );
	}

	public function wpsp_wp_logout() {
		$user = wp_get_current_user();
		$this->log( 'user_logout', 'user', $user->ID, sprintf( 'User %s logged out', $user->user_login ) );
	}

	public function wpsp_login_failed( $username ) {
		$this->log( 'login_failed', 'user', null, sprintf( 'Failed login attempt for username %s', $username ) );
	}

	public function wpsp_save_post( $post_id, $post, $update ) {
		$action = $update ? 'update_post' : 'create_post';
		$this->log( $action, 'post', $post_id, sprintf( 'Post %d title: %s', $post_id, $post->post_title ) );
	}

	public function wpsp_delete_post( $post_id ) {
		$this->log( 'delete_post', 'post', $post_id, sprintf( 'Post %d deleted', $post_id ) );
	}

	public function wpsp_switch_theme( $new_name, $new_theme ) {
		$this->log( 'switch_theme', 'theme', null, sprintf( 'Switched to theme %s', $new_name ) );
	}

	

	public function wpsp_profile_update( $user_id, $old_user_data ) {
		$this->log( 'profile_updated', 'user', $user_id, sprintf( 'Profile updated for user id %d', $user_id ) );
	}

}