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
	public function __construct( $logger, $plugin_name, $version ) {
		$this->logger = $logger;
		$this->plugin_name = $plugin_name;
        $this->version = $version;
        add_action('admin_menu', array($this, 'admin_menu'));
		//add_action( 'admin_menu', [ $this, 'admin_menu' ] );

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
			array(&$this, 'render_page'),
			'dashicons-list-view',
			80
		);

	}

	/*
	public function enqueue_assets( $hook ) {
		if ( $hook !== 'toplevel_page_wp-site-prober' ) {
			return;
		}
		wp_enqueue_style( 'wp-site-prober-admin', plugin_dir_url( __FILE__ ) . '../css/admin.css' );
		wp_enqueue_script( 'wp-site-prober-admin', plugin_dir_url( __FILE__ ) . '../js/admin.js', [ 'jquery' ], false, true );
		wp_localize_script( 'wp-site-prober-admin', 'SiteProber', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		] );
	}
		*/

	public function render_page() {
		global $wpdb;

		$table = $this->logger->get_table_name();

		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

		$where = '';
		if ( $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$where = $wpdb->prepare( " WHERE action LIKE %s OR description LIKE %s OR ip LIKE %s ", $like, $like, $like );
		}

		$rows = $wpdb->get_results( "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT 200", ARRAY_A );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WP Site Prober', 'wp-site-prober' ); ?></h1>

			<form method="get">
				<input type="hidden" name="page" value="wp-site-prober" />
				<p class="search-box">
					<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search actions, descriptions, IP', 'wp-site-prober' ); ?>" />
					<button class="button"><?php esc_html_e( 'Search', 'wp-site-prober' ); ?></button>
					<a class="button" href="<?php echo esc_url( admin_url( 'admin-post.php?action=WP_Site_Prober_export_csv' ) ); ?>"><?php esc_html_e( 'Export CSV', 'wp-site-prober' ); ?></a>
				</p>
			</form>

			<table class="widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Time', 'wp-site-prober' ); ?></th>
						<th><?php esc_html_e( 'User ID', 'wp-site-prober' ); ?></th>
						<th><?php esc_html_e( 'Action', 'wp-site-prober' ); ?></th>
						<th><?php esc_html_e( 'Object', 'wp-site-prober' ); ?></th>
						<th><?php esc_html_e( 'Description', 'wp-site-prober' ); ?></th>
						<th><?php esc_html_e( 'IP', 'wp-site-prober' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $rows ) ) : ?>
						<?php foreach ( $rows as $r ) : ?>
							<tr>
								<td><?php echo esc_html( $r['created_at'] ); ?></td>
								<td><?php echo esc_html( $r['user_id'] ); ?></td>
								<td><?php echo esc_html( $r['action'] ); ?></td>
								<td><?php echo esc_html( $r['object_type'] . ' ' . $r['object_id'] ); ?></td>
								<td><?php echo esc_html( $r['description'] ); ?></td>
								<td><?php echo esc_html( $r['ip'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr><td colspan="6"><?php esc_html_e( 'No activity found', 'wp-site-prober' ); ?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>
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
		fputcsv( $out, [ 'id', 'created_at', 'user_id', 'action', 'object_type', 'object_id', 'description', 'ip' ] );

		foreach ( $rows as $r ) {
			fputcsv( $out, [
				$r['id'],
				$r['created_at'],
				$r['user_id'],
				$r['action'],
				$r['object_type'],
				$r['object_id'],
				$r['description'],
				$r['ip'],
			] );
		}
		fclose( $out );
		exit;
	}
}
