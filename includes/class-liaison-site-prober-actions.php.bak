<?php

class liaison_site_prober_Actions {

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
		//add_action( 'export_wp', [ $this, 'wpsp_export_csv' ] );

		add_action( 'activated_plugin', [ $this, 'wpsp_plugin_activated' ], 10, 2 );
		add_action( 'deactivated_plugin', [ $this, 'wpsp_plugin_deactivated' ], 10, 2 );
		add_action( 'delete_plugin', [ $this, 'wpsp_plugin_delete' ]);

		add_action( 'wp_login', [ $this, 'wpsp_wp_login' ], 10, 2 );
		add_action( 'clear_auth_cookie', [ $this, 'wpsp_wp_logout' ] );
		add_action( 'wp_login_failed', [ $this, 'wpsp_login_failed' ] );
		add_action( 'profile_update', [ $this, 'wpsp_profile_update' ], 10, 2 );
		add_action( 'user_register', [ $this, 'wpsp_user_register' ] );
		add_action( 'delete_user', [ $this, 'wpsp_delete_user' ] );

		add_action( 'save_post', [ $this, 'wpsp_save_post' ], 10, 3 );
		add_action( 'delete_post', [ $this, 'wpsp_delete_post' ], 10, 1 );
		add_action( 'switch_theme', [ $this, 'wpsp_switch_theme' ], 10, 2 );
		
		add_action( 'created_term', [ $this, 'wpsp_modified_term' ], 10, 3 );
		add_action( 'edited_term', [ $this, 'wpsp_modified_term' ], 10, 3 );
		add_action( 'delete_term', [ $this, 'wpsp_modified_term' ], 10, 4 );

		add_action( 'wp_insert_comment', [ $this, 'wpsp_comment_handler' ], 10, 2 );
		add_action( 'edit_comment', [ $this, 'wpsp_comment_handler' ] );
		add_action( 'trash_comment', [ $this, 'wpsp_comment_handler' ] );
		add_action( 'untrash_comment', [ $this, 'wpsp_comment_handler' ] );
		add_action( 'spam_comment', [ $this, 'wpsp_comment_handler' ] );
		add_action( 'unspam_comment', [ $this, 'wpsp_comment_handler' ] );
		add_action( 'delete_comment', [ $this, 'wpsp_comment_handler' ] );
    }

    /**
	 * Generic logger function
	 */
	public function log( $action, $object_type = '', $object_id = null, $description = '' ) {
		global $wpdb;
		$user_id = null;
		if ( 'user' == $object_type ) {
			$user_id = $object_id;
		} else {
			$user = wp_get_current_user();
			if ( $user && $user->ID ) {
				$user_id = $user->ID;
			} 
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
	public function wpsp_export_csv( $args ) {
		$this->log( 'downloaded', 'export', null, 'CSV downloaded' );
	}

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

	public function wpsp_plugin_delete( $plugin ) {
		$plugin_name = $this->get_plugin_name( $plugin );
		$this->log( 'plugin_delete', 'plugin', null, sprintf( 'Deleted plugin : %s', $plugin_name ) );
	}
	
	public function wpsp_wp_login( $user_login, $user ) {
		$this->log( 'user_login', 'user', $user->ID, sprintf( 'Logged in User: %s ', $user_login ) );
	}

	public function wpsp_wp_logout() {
		$user = wp_get_current_user();
		$this->log( 'user_logout', 'user', $user->ID, sprintf( 'Logged out User: %s ', $user->user_nicename ) );
	}

	public function wpsp_login_failed( $username ) {
		$this->log( 'login_failed', 'user', null, sprintf( 'Login Failed username: %s', $username ) );
	}

	public function wpsp_profile_update( $user_id, $old_user_data ) {
		$this->log( 'profile_updated', 'user', $user_id, sprintf( 'Profile updated for user id %d', $user_id ) );
	}

	public function wpsp_user_register( $user_id ) {
		$user = get_user_by( 'id', $user_id );

		$this->log( 'user_registered', 'user', $user_id, sprintf( 'User registered for user name %s', $user->user_nicename ) );
	}
	public function wpsp_delete_user( $user_id ) {
		$user = get_user_by( 'id', $user_id );

		$this->log( 'user_deleted', 'user', $user_id, sprintf( 'User deleted for user name %s', $user->user_nicename ) );
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

	public function wpsp_modified_term( $term_id, $tt_id, $taxonomy, $deleted_term = null ) {
		
		if ( 'delete_term' === current_filter() )
			$term = $deleted_term;
		else
			$term = get_term( $term_id, $taxonomy );

		if ( $term && ! is_wp_error( $term ) ) {
			if ( 'edited_term' === current_filter() ) {
				$action = 'updated';
			} elseif ( 'delete_term' === current_filter() ) {
				$action  = 'deleted';
				$term_id = '';
			} else {
				$action = 'created';
			}

			$this->log( $action, 'taxonomies', $term_id, sprintf( 'Taxonomy %s was %s', $term->name, $action ) );
		}
	}	
	public function wpsp_comment_handler( $comment_ID, $comment = null ) {
		if ( is_null( $comment ) )
			$comment = get_comment( $comment_ID );
		
		$action = 'created';
		switch ( current_filter() ) {
			case 'wp_insert_comment' :
				$action = 1 === (int) $comment->comment_approved ? 'approved' : 'pending';
				break;
			
			case 'edit_comment' :
				$action = 'updated';
				break;

			case 'delete_comment' :
				$action = 'deleted';
				break;
			
			case 'trash_comment' :
				$action = 'trashed';
				break;
			
			case 'untrash_comment' :
				$action = 'untrashed';
				break;
			
			case 'spam_comment' :
				$action = 'spammed';
				break;
			
			case 'unspam_comment' :
				$action = 'unspammed';
				break;
		}

		$post_title = esc_html( get_the_title( $comment->comment_post_ID ) );
		$this->log( $action, 'comments', $term_id, sprintf( 'Comment of " %s " was %s', $post_title, $action ) );
	}

}