<?php

class LIAISIPR {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      liaison_site_prober_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The actions logger that's responsible for registering hooks of all actions
	 * 
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      liaison_site_prober_Actions    $actions    Registers hooks of all actions.
	 */
	protected $actions;

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
	protected $table_name_custom_log;
	protected $table_name_custom_log_session;
	public $dir;

	const TAXONOMY = LIAISIP_TAXONOMY;
	const CPT      = LIAISIP_CPT;
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
		if ( defined( 'LIAISIPR_VERSION' ) ) {
			$this->version = LIAISIPR_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'liaison-site-prober';

		$this->load_dependencies();
		//$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

		global $wpdb;
		$wpdb->wpsp_activity = $wpdb->prefix . 'liaison_site_prober';
		$wpdb->wpsp_custom_log = $wpdb->prefix . 'liaison_site_prober_custom_log';	
		$wpdb->wpsp_custom_log_session = $wpdb->prefix . 'liaison_site_prober_custom_log_session';	
		$this->table_name = $wpdb->wpsp_activity;
		$this->table_name_custom_log = $wpdb->wpsp_custom_log;
		$this->table_name_custom_log_session = $wpdb->wpsp_custom_log_session;
		$this->dir = plugin_basename( __FILE__ );

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-liaison-site-prober-actions.php';
		$this->actions = new LIAISIPR_Actions($this, $this->get_plugin_name(), $this->get_version());
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - liaison_site_prober_Loader. Orchestrates the hooks of the plugin.
	 * - liaison_site_prober_i18n. Defines internationalization functionality.
	 * - liaison_site_prober_Admin. Defines all hooks for the admin area.
	 * - liaison_site_prober_Public. Defines all hooks for the public side of the site.
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
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-liaison-site-prober-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-liaison-site-prober-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-liaison-site-prober-list-table.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-liaison-site-prober-list-table-custom-log.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-liaison-site-prober-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-liaison-site-prober-public.php';


		$this->loader = new LIAISIPR_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the liaison_site_prober_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		//$plugin_i18n = new LIAISIPR_i18n();
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

		$plugin_admin = new LIAISIPR_Admin( $this, $this->get_plugin_name(), $this->get_version() );

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

		$plugin_public = new LIAISIPR_Public( $this->get_plugin_name(), $this->get_version() );

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
	 * @return    liaison_site_prober_Loader    Orchestrates the hooks of the plugin.
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

	/* helpers for admin class */
	public function get_table_name() {
		return $this->table_name;
	}

	public function get_table_name_custom_log() {
		return $this->table_name_custom_log;
	}

	public function get_table_name_custom_log_session() {
		return $this->table_name_custom_log_session;
	}

	public function get_plugin_dir() {
		return $this->dir;
	}
}
