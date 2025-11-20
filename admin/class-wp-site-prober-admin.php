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
	protected $table;
	protected $wpsp_list_table = null;

	//protected $logger_custom_log;
	protected $table_custom_log;
	protected $wpsp_custom_log = null;
	public function __construct( $logger, $plugin_name, $version ) {
		$this->logger = $logger;
		$this->plugin_name = $plugin_name;
        $this->version = $version;
		$this->table = $this->logger->get_table_name();
		$this->table_custom_log = $this->logger->get_table_name_custom_log();
        add_action('admin_menu', array($this, 'admin_menu'));
		add_action('custom_log_add'  , array( $this, 'add_custom_log' ), 10, 4 );

		// handle csv export
		add_action( 'admin_post_WP_Site_Prober_export_csv', [ $this, 'handle_export_csv' ] );

		// handle csv export: custom log
		add_action( 'admin_post_WP_Custom_Log_export_csv_custom_log', [ $this, 'handle_export_csv_custom_log' ] );

		// Generate custom log for testing
		add_action( 'admin_post_WP_Custom_Log_custom_log_generate', [ $this, 'handle_custom_log_generate' ] );
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
			'Site Prober',
			'Site Prober',
			'manage_options',
			'wpsp-site-prober',
			array($this, 'render_page_tabs'),
			'dashicons-video-alt2',
			80
		);
	}	

	public function render_page_tabs() {
		require_once( trailingslashit( dirname( __FILE__ ) ) . 'partials/wp-site-prober-admin-display.php' );
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

	public function get_list_table_custom_log() {
		if ( is_null( $this->wpsp_custom_log ) ) {
			$this->wpsp_custom_log = new wp_site_prober_List_Table_Custom_Log( );
		}

		return $this->wpsp_custom_log;
	}

	protected function redirect_back() {
		wp_safe_redirect( menu_page_url( 'wpsp-site-prober', false ) );
		exit;
	}
	public function render_page_list_table() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		if ( isset( $_GET['page'] ) && 'wpsp-site-prober' !== $_GET['page'] ) {
			$this->redirect_back();
		} 

		$this->get_list_table()->prepare_items();
	?>
		<div class="wrap">
			<h1>
				<?php 
					esc_html_e( 'Actions', 'wpsp-site-prober' ); 
				?>
			</h1>
			
			<form id="activity-filter" method="get">
				<input type="hidden" name="page" value="Yes" />
				<?php $this->get_list_table()->display(); ?>
			</form>

		</div>
	<?php

	}

	public function render_page_list_table_custom_log() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		if ( isset( $_GET['page'] ) && 'wpsp-site-prober' !== $_GET['page'] ) {
			$this->redirect_back();
		} 

		$this->get_list_table_custom_log()->prepare_items();
	?>
		<div class="wrap">
			<h1>
				<?php 
					esc_html_e( 'Custom Log', 'wpsp-site-prober' ); 
				?>
			</h1>
			
			<form id="custom-log-filter" method="get">
				<input type="hidden" name="page" value="Yes" />
				<?php $this->get_list_table_custom_log()->display(); ?>
			</form>

		</div>
	<?php

	}

	/**
	 * 將陣列轉成 CSV 一行（處理引號、逗號等）
	 */
	private function array_to_csv_line( $fields, $delimiter = ',', $enclosure = '"' ) {
		$escaped = [];
		foreach ( $fields as $field ) {
			$escaped[] = $enclosure . str_replace( $enclosure, $enclosure . $enclosure, $field ) . $enclosure;
		}
		return implode( $delimiter, $escaped ) . "\n";
	}
	public function handle_export_csv( ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_GET['wpsp_nonce'] ) ||
         	! wp_verify_nonce( sanitize_key( $_GET['wpsp_nonce'] ), 'wpsp_list_table_action' ) ) {
        		wp_die( esc_html__( 'Invalid request.', 'wpsp-site-prober' ) );
    	}
		global $wpdb;
		$this->table = $this->logger->get_table_name();
		$table = sanitize_key( $this->table );
		$cache_key   = 'site_prober_logs_page_';
		$cache_group = 'wp-site-prober';

		// 嘗試從快取抓資料
		$results = wp_cache_get( $cache_key, $cache_group );

		if ( false === $results ) {
			// Safe direct database access (custom table, prepared query)
			$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC",
				ARRAY_A
			);

			wp_cache_set( $cache_key, $results, $cache_group, 5 * MINUTE_IN_SECONDS );
		}

		// 初始化 WP_Filesystem
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		global $wp_filesystem;
		WP_Filesystem();

		// 暫存檔路徑
		$upload_dir = wp_upload_dir();
		$tmp_file   = trailingslashit( $upload_dir['basedir'] ) . 'wp-site-prober-export.csv';

		// 建立 CSV 內容
		$csv_lines = [];
		$csv_lines[] = [ 'id', 'created_at', 'user_id', 'ip', 'action', 'object_type', 'description' ];

		foreach ( $rows as $r ) {
			$csv_lines[] = [
				$r['id'],
				$r['created_at'],
				$this->user_info_export( $r['user_id'] ),
				$r['ip'],
				$r['action'],
				$r['object_type'],
				$r['description'],
			];
		}
		
		// 將陣列轉為 CSV 格式字串
		$csv_content = '';
		foreach ( $csv_lines as $line ) {
			$csv_content .= $this->array_to_csv_line( $line );
		}

		$wp_filesystem->put_contents( $tmp_file, $csv_content, FS_CHMOD_FILE );

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=wp-site-prober-export-' . gmdate( 'Y-m-d' ) . '.csv' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// 直接輸出檔案
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		//readfile( $tmp_file );
		//wp_kses_post()
		// 用 WP_Filesystem 安全讀出內容
		echo wp_kses_post( $wp_filesystem->get_contents( $tmp_file ) );

		$wp_filesystem->delete( $tmp_file );
		exit;
	}

	public function handle_export_csv_custom_log( ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_GET['wpsp_nonce_custom_log'] ) ||
         	! wp_verify_nonce( sanitize_key( $_GET['wpsp_nonce_custom_log'] ), 'wpsp_list_table_action' ) ) {
        		wp_die( esc_html__( 'Invalid request.', 'wpsp-site-prober' ) );
    	}
		global $wpdb;
		$this->table = $this->logger->get_table_name_custom_log();
		$table = sanitize_key( $this->table );
		$cache_key   = 'site_prober_logs_page_custom_log';
		$cache_group = 'wp-site-prober';

		// 嘗試從快取抓資料
		$results = wp_cache_get( $cache_key, $cache_group );

		if ( false === $results ) {
			// Safe direct database access (custom table, prepared query)
			$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC",
				ARRAY_A
			);

			wp_cache_set( $cache_key, $results, $cache_group, 5 * MINUTE_IN_SECONDS );
		}

		// 初始化 WP_Filesystem
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		global $wp_filesystem;
		WP_Filesystem();

		// 暫存檔路徑
		$upload_dir = wp_upload_dir();
		$tmp_file   = trailingslashit( $upload_dir['basedir'] ) . 'wp-custom-log-export.csv';

		// 建立 CSV 內容
		$csv_lines = [];
		$csv_lines[] = [ 'id', 'log_id', 'plugin_name', 'message', 'severity', 'session_type', 'session_id', 'created_at' ];

		foreach ( $rows as $r ) {
			$csv_lines[] = [
				$r['id'],
				$r['log_id'],
				$r['plugin_name'],
				$r['message'],				
				$r['severity'],				
				$r['session_type'],				
				$r['session_id'],				
				$r['created_at'],
			];
		}
		
		// 將陣列轉為 CSV 格式字串
		$csv_content = '';
		foreach ( $csv_lines as $line ) {
			$csv_content .= $this->array_to_csv_line( $line );
		}

		$wp_filesystem->put_contents( $tmp_file, $csv_content, FS_CHMOD_FILE );

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=wp-site-prober-export-' . gmdate( 'Y-m-d' ) . '.csv' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// 直接輸出檔案
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		//readfile( $tmp_file );
		//wp_kses_post()
		// 用 WP_Filesystem 安全讀出內容
		echo wp_kses_post( $wp_filesystem->get_contents( $tmp_file ) );

		$wp_filesystem->delete( $tmp_file );
		exit;
	}

	public function handle_custom_log_generate() {
		error_log(  'custom_log_generate');
		do_action( 'custom_log_add', 'wpsp-site-prober-custom-log', 'message-', 'step-', 2 );
		//$this->redirect_back();
		//exit;
	}

	public function add_custom_log( $plugin_name, $log, $message, $severity = 1 ) {

	}

	/*
	public function add_log_entry( $plugin_name, $log, $message, $severity = 1 ) {
		$plugin_name = sanitize_text_field( (string) $plugin_name );
		$log         = sanitize_text_field( (string) $log );
		$message     = (string) $message;
		$severity    = intval( $severity );

		if ( self::$session_post ) {
			$post_id = self::$session_post;
		} else {
			$post_id = $this->check_existing_log( $plugin_name, $log );
			if ( false == $post_id ) {
				$post_id = $this->create_post_with_terms( $plugin_name, $log );
				if ( false == $post_id ) {
					return false;
				}
			}
		}

		$comment_data = array(
			'comment_post_ID'      => $post_id,
			'comment_content'      => wp_kses_post( $message ),
			'comment_author'       => $plugin_name,
			'comment_approved'     => self::CPT,
			'comment_author_IP'    => '',
			'comment_author_url'   => '',
			'comment_author_email' => '',
			'user_id'              => $severity,
		);

		if ( self::$session_post ) {
			$comment_data['comment_parent'] = 1;
		}

		$comment_id = wp_insert_comment( wp_filter_comment( $comment_data ) );

		if ( ! self::$session_post ) {
			$this->limit_plugin_logs( $plugin_name, $log, $post_id );
		}

		return (bool) $comment_id;
	}
	*/
}


