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
			//array(&$this, 'render_page'),
			//array(&$this, 'render_page_list_table'),
			array(&$this, 'render_page_mixed'),
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

	

	private function get_filtered_link( $name = '', $value = '' ) {
		$base_page_url = menu_page_url( 'wp-site-prober', false );

		if ( empty( $name ) ) {
			return $base_page_url;
		}

		return add_query_arg( $name, $value, $base_page_url );
	}

	public function user_info( $user_id ) {
		global $wp_roles;

		$msg = '';
		
		if ( ! empty( $user_id ) && 0 !== (int) $user_id ) {
			$user = get_user_by( 'id', $user_id );
			if ( $user instanceof WP_User && 0 !== $user->ID ) {
				$msg = sprintf(
					'<a href="%s">%s <span class="wpsp-author-name">%s</span></a><br /><small>%s</small>',
					$this->get_filtered_link( 'usershow', $user->ID ),
					get_avatar( $user->ID, 40 ),
					$user->display_name,
					isset( $user->roles[0] ) && isset( $wp_roles->role_names[ $user->roles[0] ] ) ? $wp_roles->role_names[ $user->roles[0] ] : __( 'Unknown', 'wp-site-prober' )
				);		
			}
		} else {
			$msg =  sprintf(
				'<span class="wpsp-author-name">%s</span>',
				__( 'N/A', 'wp-site-prober' )
			);
		}
		
		return $msg;
	}

	public function delete_all_items() {
		global $wpdb;
		$table = $this->logger->get_table_name();

		//$wpdb->query( 'TRUNCATE `' . $wpdb->activity_log . '`' );
		$wpdb->query( "TRUNCATE {$table}" );
	}

	public function render_page_mixed() {
		$this->render_page_list_table();
		$this->render_page();
	}

	public function get_list_table() {
		if ( is_null( $this->wpsp_list_table ) ) {
			//$this->_list_table = new AAL_Activity_Log_List_Table( array( 'screen' => $this->_screens['main'] ) );
			$this->wpsp_list_table = new wp_site_prober_List_Table( );
			
			//do_action( 'aal_admin_page_load', $this->_list_table );
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


	public function render_page() {
		global $wpdb;

		$table = $this->logger->get_table_name();

		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$clear  = isset( $_POST['clearLogs'] ) ? sanitize_text_field( wp_unslash( $_POST['clearLogs'] ) ) : '';
		if ($clear)	{
			error_log(  'clearLogs');
			$this->delete_all_items();
		}

		$where = '';
		if ( $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$where = $wpdb->prepare( " WHERE action LIKE %s OR description LIKE %s OR ip LIKE %s ", $like, $like, $like );
		}

		$rows = $wpdb->get_results( "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT 200", ARRAY_A );

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'WP Site Prober', 'wp-site-prober' ); ?></h1>

			<form id="wpsp-form-delete" method="post" action="">
				<div class="tablenav top">
					<br class="clear" />
				    <input type="hidden" id="clearLogs" name="clearLogs" value="Yes">
					<div class="alignleft actions">
						<?php submit_button( __( 'Clear Logs', 'wp-site-prober' ), '', 'clear_action', false ); ?>
					</div>

					<br class="clear" />
				</div>
			</form>
			
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
						<th><?php esc_html_e( 'User Info', 'wp-site-prober' ); ?></th>
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
								<td><?php 
									/*echo esc_html( $r['user_id'] ); */
									echo $this->user_info( $r['user_id'] ); 
								?></td>
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
