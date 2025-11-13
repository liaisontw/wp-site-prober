<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class wp_site_prober_Admin {

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
	protected $wpsp_list_table = null;
	public function __construct( $logger, $plugin_name, $version ) {
		$this->logger = $logger;
		$this->plugin_name = $plugin_name;
        $this->version = $version;
        add_action('admin_menu', array($this, 'admin_menu'));

		// handle csv export
		add_action( 'admin_post_WP_Site_Prober_export_csv', [ $this, 'handle_export_csv' ] );
	}

	    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in hidden_Stuff_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The hidden_Stuff_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wp-site-prober-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in hidden_Stuff_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The hidden_Stuff_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wp-site-prober-admin.js', array( 'jquery' ), $this->version, false);
    }

    /**
     * hidden_stuff_menu_settings function.
     * Add a menu item
     * @access public
     * @return void
     */

	public function admin_menu() {
		add_menu_page(
			'WP Site Prober',
			'WP Site Prober',
			'manage_options',
			'wp-site-prober',
			array(&$this, 'render_page_list_table'),
			'dashicons-video-alt2',
			80
		);
	}	

	public function user_info_export( $user_id ) {
		$msg = '';

		if ( ! empty( $user_id ) && 0 !== (int) $user_id ) {
			$user = get_user_by( 'id', $user_id );
			if ( $user instanceof WP_User && 0 !== $user->ID ) {
				$msg = $user->display_name;		
			}
		} else {
			$msg = 'N/A';
		}

		return $msg;
	}

	public function get_list_table() {
		if ( is_null( $this->wpsp_list_table ) ) {
			$this->wpsp_list_table = new wp_site_prober_List_Table( );
		}

		return $this->wpsp_list_table;
	}
	public function render_page_list_table() {
		$this->get_list_table()->prepare_items();
	?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WP Site Prober', 'wp-site-prober' ); ?></h1>

			<form id="activity-filter" method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
				<?php $this->get_list_table()->display(); ?>
			</form>

		</div>
	<?php

	}



	/**
	 * Export current query (no filters) as CSV
	 */
	public function handle_export_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Permission denied', 'wp-site-prober' ) );
		}
		global $wpdb;
		$table = $this->logger->get_table_name();
		$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC", ARRAY_A );

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=activity-log-' . date( 'Y-m-d' ) . '.csv' );

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, [ 'id', 'created_at', 'user_id', 'action', 'object_type', 'description', 'ip' ] );

		foreach ( $rows as $r ) {
			fputcsv( $out, [
				$r['id'],
				$r['created_at'],
				$this->user_info_export( $r['user_id'] ),
				$r['ip'],
				$r['action'],
				$r['object_type'],
				$r['description'],
			] );
		}
		fclose( $out );
		exit;
	}
}
