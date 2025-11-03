<?php

class WP_Site_Prober {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      wp_site_prober_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	protected $table_name;
	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'WP_SITE_PROBER_VERSION' ) ) {
			$this->version = WP_SITE_PROBER_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'wp-site-prober';

		$this->load_dependencies();
		//$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

		global $wpdb;
		$this->table_name = $wpdb->prefix . 'site_prober';
		//register_activation_hook( plugin_dir_path( __FILE__ ) . '../wp-site-prober.php', [ $this, 'install' ] );

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


	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - wp_site_prober_Loader. Orchestrates the hooks of the plugin.
	 * - wp_site_prober_i18n. Defines internationalization functionality.
	 * - wp_site_prober_Admin. Defines all hooks for the admin area.
	 * - wp_site_prober_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-site-prober-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-site-prober-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-site-prober-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wp-site-prober-public.php';

		$this->loader = new wp_site_prober_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the wp_site_prober_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		//$plugin_i18n = new wp_site_prober_i18n();
		//$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
			/*
			* load_plugin_textdomain() has been discouraged 
			* since WordPress version 4.6. 
			* When your plugin is hosted on WordPress.org, 
			* you no longer need to manually include this 
			* function call for translations under your plugin slug. 
			* WordPress will automatically load the translations 
			* for you as needed.
			*/

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new wp_site_prober_Admin( $this, $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new wp_site_prober_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'init'              , $plugin_public, 'init_wp_site_prober' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}


	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    wp_site_prober_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
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
