<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LIAISIPR_Utility {

    protected $logger;
    protected static $session_id_in_use = null;

	public function __construct( $logger ) 
    {
		$this->logger = $logger;
	}

	/**
	 * 將陣列轉成 CSV 一行（處理引號、逗號等）
	 */
	private function array_to_csv_line( $fields, $delimiter = ',', $enclosure = '"' ) {
		$escaped = [];
		foreach ( $fields as $field ) {
			$escaped[] = $field ? $enclosure . str_replace( $enclosure, $enclosure . $enclosure, $field ) . $enclosure : '';
		}
		return implode( $delimiter, $escaped ) . "\n";
	}
	
	private function export_csv_generic( array $args ) {
		if ( ! current_user_can( $args['capability'] ) ) {
			return;
		}

		if (
			! isset( $_GET[ $args['nonce_key'] ] ) ||
			! wp_verify_nonce(
				sanitize_key( $_GET[ $args['nonce_key'] ] ),
				$args['nonce_action']
			)
		) {
			wp_die( esc_html__( 'Invalid request.', 'liaison-site-prober' ) );
		}

		global $wpdb;

		$table       = sanitize_key( $args['table'] );
		$cache_key   = $args['cache_key'];
		$cache_group = 'liaison-site-prober';

		$rows = wp_cache_get( $cache_key, $cache_group );

		if ( false === $rows ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$rows = $wpdb->get_results(
				"SELECT * FROM {$table} ORDER BY created_at DESC",
				ARRAY_A
			);
			wp_cache_set( $cache_key, $rows, $cache_group, 5 * MINUTE_IN_SECONDS );
		}

		// Init filesystem
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		global $wp_filesystem;
		WP_Filesystem();

		$upload_dir = wp_upload_dir();
		$tmp_file   = trailingslashit( $upload_dir['basedir'] ) . $args['filename'] . '.csv';

		// CSV build
		$csv_lines   = [];
		$csv_lines[] = $args['headers'];

		foreach ( $rows as $row ) {
			$csv_lines[] = call_user_func( $args['row_mapper'], $row );
		}

		$csv_content = '';
		foreach ( $csv_lines as $line ) {
			$csv_content .= $this->array_to_csv_line( $line );
		}

		$wp_filesystem->put_contents( $tmp_file, $csv_content, FS_CHMOD_FILE );

		header( 'Content-Type: text/csv; charset=utf-8' );
		header(
			'Content-Disposition: attachment; filename=' .
			$args['filename'] . '-' . gmdate( 'Y-m-d' ) . '.csv'
		);
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $wp_filesystem->get_contents( $tmp_file );

		$wp_filesystem->delete( $tmp_file );
		exit;
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

    public function handle_export_csv() {
		$this->export_csv_generic( [
			'capability' => 'manage_options',
			'nonce_key'  => 'wpsp_nonce',
			'nonce_action' => 'wpsp_list_table_action',
			'table'      => $this->logger->get_table_name(),
			'cache_key'  => 'site_prober_logs_page_',
			'filename'   => 'liaison-site-prober-export',
			'headers'    => [ 'id', 'created_at', 'user_id', 'ip', 'action', 'object_type', 'description' ],
			'row_mapper' => function( $r ) {
				return [
					$r['id'],
					$r['created_at'],
					$this->user_info_export( $r['user_id'] ),
					$r['ip'],
					$r['action'],
					$r['object_type'],
					$r['description'],
				];
			},
		] );
	}

	public function handle_export_csv_custom_log() {
		$this->export_csv_generic( [
			'capability' => 'manage_options',
			'nonce_key'  => 'wpsp_nonce_custom_log',
			'nonce_action' => 'wpsp_export_custom_log',
			'table'      => $this->logger->get_table_name_custom_log(),
			'cache_key'  => 'site_prober_logs_page_custom_log',
			'filename'   => 'liaison-site-prober-custom-log-export',
			'headers'    => [ 'id', 'log_id', 'plugin_name', 'message', 'severity', 'session_type', 'session_id', 'created_at' ],
			'row_mapper' => function( $r ) {
				return [
					$r['id'],
					$r['log_id'],
					$r['plugin_name'],
					$r['message'],
					$r['severity'],
					$r['session_type'],
					$r['session_id'],
					$r['created_at'],
				];
			},
		] );
	}

	public function add_custom_log( $plugin_name, $log, $message, $severity = 1 ) {
		global $wpdb;
		$session_id = self::$session_id_in_use;
		$table = $this->get_custom_log_table();
		error_log( sprintf( 'inserted data to: %s', $table) );

		$wpdb->insert(
			$table,
			[		
				'log_id'       => null,
				'plugin_name'  => sanitize_text_field( (string) $plugin_name ),
				'message'      => sanitize_text_field( $message ),
				'severity'     => intval( $severity ),
				//'session_type' => false,
				'session_id'   => intval( $session_id ),
			],
			[ '%d', '%s', '%s', '%d', '%d' ]
		);

	}

	public function get_custom_log_table() {
		return sanitize_key( $this->logger->get_table_name_custom_log() );
	}
	public function get_custom_log_session_table() {
		return sanitize_key( $this->logger->get_table_name_custom_log_session() );
	}

	function begin_session( $plugin_name, $message, $session_title, $severity = 0 ) {
		global $wpdb;
		
		error_log('begin_session');
		//do_action( 'liaison-site-prober', 'message-session-begin', 'session-begin !', 0 );
		$table_session = $this->get_custom_log_session_table();

		$result = $wpdb->insert(
			$table_session,
			[		
				'plugin_name'  => sanitize_text_field( (string) $plugin_name ),
				'message'      => sanitize_text_field( $message ),
				'severity'     => intval( $severity ),
			],
			[ '%s', '%s', '%d']
		);

		if ( $result !== false ) {
			$last_inserted_id = $wpdb->insert_id;
			error_log( sprintf( 'Successfully inserted data. Last inserted ID: %d', $last_inserted_id) );
			$session_id = $last_inserted_id;
			self::$session_id_in_use = $session_id;
		} else {
			error_log( 'Error inserting data.');
		}
		
		return true;
	}

	function end_session( ...$args ) {
		error_log('end_session');
		self::$session_id_in_use = null;
		return true;
	}

    public function handle_custom_log_generate() {
		$appends = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '!', '@', '#'];
		
		// 從 option 讀取
		$x = intval( get_option('liaison_custom_log_x', 0) );
		$append_now = $appends[$x];
		// 計算下一個值
        ( ($x + 1) >= count($appends) ) ? $x = 0 : $x++;
		// 寫回 option
		update_option('liaison_custom_log_x', $x);
		do_action( 'custom_log_add', 'liaison-site-prober', 'message-'.$append_now, 'step-'.$append_now, 2 );
		add_action('shutdown', function () {
			wp_safe_redirect(
				add_query_arg(
					[
						'page' => 'wpsp_site_prober_log_list',
						'tab'  => 'custom',
					],
					admin_url('admin.php')
				)
			);
			exit;	
		} );
	}
	public function handle_session_generate() {
		do_action( 'custom_log_session_begin', 'liaison-site-prober', 'message-session-begin', 'session-begin !', 0 );
			error_log('in_session');
			do_action( 'custom_log_add', 'liaison-site-prober', 'message-in-session', 'step-in-session', 4 );
		do_action( 'custom_log_session_end' );
		add_action('shutdown', function () {
			wp_safe_redirect(
				add_query_arg(
					[
						'page' => 'wpsp_site_prober_log_list',
						'tab'  => 'custom',
					],
					admin_url('admin.php')
				)
			);
			exit;	
		} );
	}
}


