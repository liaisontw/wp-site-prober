<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class LIAISIPR_List_Table_Implicit_Backup extends WP_List_Table {

	function __construct( /*$items*/$args = array() ) {
		//$this->items       = isset( $items['entries'] ) ? $items['entries'] : array();
		//$this->total_items = isset( $items['count'] ) ? intval( $items['count'] ) : 0;

		parent::__construct(
			array(
				'singular'  => esc_html__( 'custom_log_implicit', 'liaison-site-prober' ),
				'plural'    => esc_html__( 'custom_logs_implicit', 'liaison-site-prober' ),
				//'ajax'      => false
			)
		);
	}

	public function column_id( $item ) {
		return esc_html( $item['id'] );
	}

	public function column_message( $item ) {
		if ( 1 == $item['session_type'] ) {
			$session_url = esc_url( admin_url( 'admin.php?page=wpsp_site_prober_log_list&tab=implicit&session-select=' . intval( $item['id'] ) ) );
			$message = "<a href='{$session_url}' class='thickbox'>" . esc_html( $item['message'] ) . "</a>";
		} else {
			$message = esc_html( $item['message'] );
		}
		return $message;
	}

    public function column_plugin( $item ) {
        return esc_html( $item['plugin_name'] );
    }

    public function column_severity( $item ) {
		return esc_html( $item['severity'] );
	}

    public function column_created_at( $item ) {
        return esc_html( $item['created_at'] );
    }

    public function get_columns() {
        $columns = array(
            //'log_id'      => __( 'Log Id', 'liaison-site-prober' ),
            'message'     => esc_html__( 'Message', 'liaison-site-prober' ),
            'plugin'      => esc_html__( 'Plugin', 'liaison-site-prober' ),
            'severity'    => esc_html__( 'Severity', 'liaison-site-prober' ),
            'created_at'  => esc_html__( 'Time', 'liaison-site-prober' ),
        );

		return $columns;
	}

	public function get_hidden_columns() {
		return array();
	}

	public function get_sortable_columns() {
		return array(
			//'log_id'     => array( 'log_id', false ),
			'plugin'     => array( 'plugin', false ),
            'severity'   => array( 'severity', false ),
            'created_at' => array( 'created_at', false ),
		);
	}	

	private function table_data() {
		$data = array();
		if ( ! empty( $this->items ) ) {
			foreach ( $this->items as $item ) {
				$data[] = array(
					'id'           => isset( $item->the_ID ) ? $item->the_ID : '',
					'log_severity' => isset( $item->severity ) ? $item->severity : '',
					'log_msg'      => isset( $item->message ) ? $item->message : '',
					'log_date'     => isset( $item->the_date ) ? $item->the_date : '',
					'log_plugin'   => isset( $item->log_plugin ) ? $item->log_plugin : '',
					'session'      => isset( $item->session ) ? $item->session : 0
				);
			}
		}
		return $data;
	}

	public function prepare_items() {
		$data = $this->table_data();
		$this->items = $data;

		$this->_column_headers = 
            array( $this->get_columns(), 
                   $this->get_hidden_columns( ), 
                   $this->get_sortable_columns() );
				   
		$items_per_page = 20; 
		$total_items = 0; 		
        $this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page' => $items_per_page,
			'total_pages' => ceil( $total_items / $items_per_page ),
		) );   
	}

	public function handle_export_csv_backup( ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_GET['wpsp_nonce'] ) ||
         	! wp_verify_nonce( sanitize_key( $_GET['wpsp_nonce'] ), 'wpsp_list_table_action' ) ) {
        		wp_die( esc_html__( 'Invalid request.', 'liaison-site-prober' ) );
    	}
		global $wpdb;
		$this->table = $this->logger->get_table_name();
		$table = sanitize_key( $this->table );
		$cache_key   = 'site_prober_logs_page_';
		$cache_group = 'liaison-site-prober';

		// 嘗試從快取抓資料
		$results = wp_cache_get( $cache_key, $cache_group );

		if ( false === $results ) {
			// Safe direct database access (custom table, prepared query)
			$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC",
				ARRAY_A
			);

			wp_cache_set( $cache_key, $rows, $cache_group, 5 * MINUTE_IN_SECONDS );
		}

		// 初始化 WP_Filesystem
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		global $wp_filesystem;
		WP_Filesystem();

		// 暫存檔路徑
		$upload_dir = wp_upload_dir();
		$tmp_file   = trailingslashit( $upload_dir['basedir'] ) . 'liaison-site-prober-export.csv';

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
		header( 'Content-Disposition: attachment; filename=liaison-site-prober-export-' . gmdate( 'Y-m-d' ) . '.csv' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		// 用 WP_Filesystem 安全讀出內容
		echo wp_kses_post( $wp_filesystem->get_contents( $tmp_file ) );

		$wp_filesystem->delete( $tmp_file );
		exit;
	}

	public function handle_export_csv_custom_log_backup( ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_GET['wpsp_nonce_custom_log'] ) ||
         	! wp_verify_nonce( sanitize_key( $_GET['wpsp_nonce_custom_log'] ), 'wpsp_export_custom_log' ) ) {
        		wp_die( esc_html__( 'Invalid request.', 'liaison-site-prober' ) );
    	}
		global $wpdb;
		$this->table = $this->logger->get_table_name_custom_log();
		$table = sanitize_key( $this->table );
		$cache_key   = 'site_prober_logs_page_custom_log';
		$cache_group = 'liaison-site-prober';

		// 嘗試從快取抓資料
		$results = wp_cache_get( $cache_key, $cache_group );

		if ( false === $results ) {
			// Safe direct database access (custom table, prepared query)
			$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC",
				ARRAY_A
			);

			wp_cache_set( $cache_key, $rows, $cache_group, 5 * MINUTE_IN_SECONDS );
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
		header( 'Content-Disposition: attachment; filename=liaison-site-prober-custom-log-export-' . gmdate( 'Y-m-d' ) . '.csv' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		// 用 WP_Filesystem 安全讀出內容
		echo wp_kses_post( $wp_filesystem->get_contents( $tmp_file ) );

		$wp_filesystem->delete( $tmp_file );
		exit;
	}
		/*
	public function prepare_items( $plugin_select = '' ) {
		global $wpdb;

        $items_per_page = $this->_items_per_page; 
		$total_items = $this->_total_items; 
		$this->plugin_select = $plugin_select;      
        
        $clear  = isset( $_POST['clearLogsCustomLog'] ) ? sanitize_text_field( wp_unslash( $_POST['clearLogsCustomLog'] ) ) : '';
		if ( $clear ){
			error_log(  'clearLogsCustomLog');
			check_admin_referer( 'wpsp_delete_custom_log', 'wpsp_nonce_delete_custom_log' );
			$this->delete_all_items_custom_log();
		}
        
        $where = ' WHERE 1 = 1';
	
		if ( ! empty( $_REQUEST['severityshow'] ) ) {
			$where .= $wpdb->prepare( ' AND `severity` = %d', 
			sanitize_text_field( wp_unslash( $_REQUEST['severityshow'] ) ) );
		}

        if ( isset( $_REQUEST['pluginshow'] ) && '' !== $_REQUEST['pluginshow'] ) {
			$where .= $wpdb->prepare( 
				' AND `plugin_name` = %s', 
				(int) $_REQUEST['pluginshow'] 
			);
		}

		$search = isset( $_REQUEST['s_custom_log'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s_custom_log'] ) ) : '';
		if ( $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
            $where .= $wpdb->prepare( ' AND (`plugin_name` LIKE %s OR `message` LIKE %s )', $like, $like);
		}
		
		$cache_key   = 'site_prober_logs_page_custom_log';
		$cache_group = 'liaison-site-prober';

		// 嘗試從快取抓資料
		$results = wp_cache_get( $cache_key, $cache_group );

		if ( false === $results ) {
			// Safe direct database access (custom table, prepared query)
			$table = sanitize_key( $this->table_name );
			$table_session = sanitize_key( $this->table_name_session );

            $orderby = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'created_at';
			$order   = isset( $_GET['order'] ) && in_array( strtolower($_GET['order']), ['asc','desc'], true )
				? strtolower($_GET['order'])
				: 'desc';

			$allowed_orderby = ['created_at', 'log_id', 'plugin', 'severity'];
			if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
				$orderby = 'created_at';
			}
					
			$session = '';
			if ( ! empty( $_REQUEST['session-select'] ) ) {
				$where .= $wpdb->prepare( " AND `session_id` = %d", $_POST['session-select'] );
			} else {
				$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_session}" );
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name sanitized and validated above.
				$session = "SELECT 
							id AS id,
							plugin_name AS plugin_name,
							message AS message,
							severity AS severity,
							created_at AS created_at,
							session_type AS session_type
						FROM {$table_session} {$where} 
						UNION
						";
				//$this->items = $wpdb->get_results( $session, 'ARRAY_A' );
				$where .= ' AND ( session_id = 0 OR session_id IS NULL )';

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is sanitized above.
				//Since pagiation gets LIMIT $items_per_page=20 from DB table.
				//$total_items must get whole table count directly.
				//$total_items = count( $this->items ); gets only $items_per_page.
				$total_items += $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where}" );
			}			

			$offset = ( $this->get_pagenum() - 1 ) * $items_per_page;
			$sql = $wpdb->prepare(
					"SELECT
						id AS id,
						plugin_name AS plugin_name,
						message AS message,
						severity AS severity,
						created_at AS created_at,
						session_type AS session_type
					FROM {$table} {$where} 
					ORDER BY {$orderby} {$order}
					LIMIT %d, %d",
					$offset,
					$items_per_page
			);

			if ($session != '') {
				$sql = $session . $sql;	
			}
			$this->items = $wpdb->get_results( $sql, 'ARRAY_A' );
			if ($total_items == 0) {
				$total_items = count( $this->items );
			}
			
			wp_cache_set( $cache_key, $this->items, $cache_group, 5 * MINUTE_IN_SECONDS );
		}            
        
        $this->_column_headers = 
            array( $this->get_columns(), 
                   $this->get_hidden_columns( ), 
                   $this->get_sortable_columns() );

        $this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page' => $items_per_page,
			'total_pages' => ceil( $total_items / $items_per_page ),
		) );   
    }
	*/

	/*
	protected function redirect_back() {
		wp_safe_redirect( menu_page_url( 'wpsp_site_prober_log_list', false ) );
		exit;
	}
		
	public function render_page_list_table() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		if ( isset( $_GET['page'] ) && 'wpsp_site_prober_log_list' !== $_GET['page'] ) {
			$this->redirect_back();
		} 

		$this->get_list_table()->prepare_items();
	?>
		<div class="wrap">
			<h1>
				<?php 
					esc_html_e( 'Actions', 'liaison-site-prober' ); 
				?>
			</h1>
			
			<form id="activity-filter" method="get">
				<input type="hidden" name="page" value="wpsp_site_prober_log_list">
				<input type="hidden" name="tab" value="
					<?php 
				  		echo esc_attr( $_GET['tab'] ?? 'log' ); 
					?>" 
				/>
				<?php $this->get_list_table()->display(); ?>
			</form>

		</div>
	<?php

	}

	public function render_page_list_table_custom_log() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		if ( isset( $_GET['page'] ) && 'wpsp_site_prober_log_list' !== $_GET['page'] ) {
			$this->redirect_back();
		} 

		$plugin_select = isset( $_POST['plugin_select'] ) ? $_POST['plugin_select'] : '';

		$this->get_list_table_custom_log()->prepare_items( $plugin_select );
	?>
		<div class="wrap">
			<h1>
				<?php 
					esc_html_e( 'Custom Log', 'liaison-site-prober' ); 
				?>
			</h1>
			
			<form id="custom-log-filter" method="get">
				<input type="hidden" name="page" value="wpsp_site_prober_log_list">
				<input type="hidden" name="tab" value="
					<?php 
						echo esc_attr( $_GET['tab'] ?? 'log' ); 
					?>" 
				/>
				<?php $this->get_list_table_custom_log()->display(); ?>
			</form>

		</div>
	<?php

	}

	public function render_page_list_table_log_implicit() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		if ( isset( $_GET['page'] ) && 'wpsp_site_prober_log_list' !== $_GET['page'] ) {
			$this->redirect_back();
		} 

		$plugin_select = isset( $_POST['plugin_select'] ) ? $_POST['plugin_select'] : '';

		$this->get_list_table_log_implicit()->prepare_items( $plugin_select );
	?>
		<div class="wrap">
			<h1>
				<?php 
					esc_html_e( 'Custom Log Implicit', 'liaison-site-prober' ); 
				?>
			</h1>
			
			<form id="custom-log-filter" method="get">
				<input type="hidden" name="page" value="wpsp_site_prober_log_list">
				<input type="hidden" name="tab" value="
					<?php 
						echo esc_attr( $_GET['tab'] ?? 'log' ); 
					?>" 
				/>
				<?php $this->get_list_table_log_implicit()->display(); ?>
			</form>

		</div>
	<?php

	}
	*/
	/*
    public function extra_tablenav_header() {
		 // @return array Array items should represent action_id => 'Action Title'
		 
	    ?>
			<form method="get">
				<input type="hidden" name="page" value="wpsp_site_prober_log_list" />
				<?php 
					// 產生帶 nonce 的 URL
					$export_url = wp_nonce_url(
						add_query_arg(
							array(
								'action' => 'WP_Site_Prober_export_csv_custom_log',
								'tab'    => $_GET['tab'] ?? 'log'
							),
							admin_url('admin-post.php')
						),
						'wpsp_export_custom_log',
						'wpsp_nonce_custom_log'
					);
				?>
				<a class="button" href="<?php echo esc_url( $export_url ); ?>">
					<?php esc_html_e( 'Export CSV (Custom Log)', 'liaison-site-prober' ); ?>
				</a>
			</form>
		<?php
	}

    public function extra_tablenav_footer() {
		 // @return array Array items should represent action_id => 'Action Title'
		 
			$generate_url = add_query_arg(
				[
					'action' => 'WP_Custom_Log_custom_log_generate',
					'tab'    => 'custom',
				],
				admin_url('admin-post.php')
			);

			$session_url = add_query_arg(
				[
					'action' => 'WP_Custom_Log_session_generate',
					'tab'    => 'custom',
				],
				admin_url('admin-post.php')
			);
	    ?>
			<br class="clear" />
			<div class="alignright actions">
				<a class="button" href="<?php echo esc_url( $generate_url ); ?>">
					<?php esc_html_e( 'Custom Log Generate', 'liaison-site-prober' ); ?>
				</a>
			
				<a class="button" href="<?php echo esc_url( $session_url ); ?>">
					<?php esc_html_e( 'Session Generate', 'liaison-site-prober' ); ?>
				</a>
			</div>
			<br class="clear" />
			<form method="get">
				<input type="hidden" name="page" value="wpsp_site_prober_log_list" />
				<?php 
					// 產生帶 nonce 的 URL
					$export_url = wp_nonce_url(
						add_query_arg(
							array(
								'action' => 'WP_Site_Prober_export_csv_custom_log',
								'tab'    => $_GET['tab'] ?? 'log'
							),
							admin_url('admin-post.php')
						),
						'wpsp_export_custom_log',
						'wpsp_nonce_custom_log'
					);
				?>
				<a class="button" href="<?php echo esc_url( $export_url ); ?>">
					<?php esc_html_e( 'Export CSV (Custom Log)', 'liaison-site-prober' ); ?>
				</a>
			</form>
            <form id="wpsp-form-delete" method="post" action="">
                <input type="hidden" id="clearLogsCustomLog" name="clearLogsCustomLog" value="Yes">
				<?php wp_nonce_field( 'wpsp_delete_custom_log', 'wpsp_nonce_delete_custom_log' ); ?>

                <div class="alignleft actions">
                    <?php submit_button( __( 'Clear Custom Logs', 'liaison-site-prober' ), '', 'clear_action', false ); ?>
                </div>
			</form>
			
		<?php
	}

	// build log <select> HTML (used in AJAX or page render) 
	
	public function log_plugin_select( $plugin_select ) {
		global $wpdb;
		if ( '' === $plugin_select ) {
			return false;
		}
		$where = $wpdb->prepare( 
				' WHERE `plugin_name` = %s', 
				$plugin_select 
			);

		$cache_key   = 'ajax_custom_log';
		$cache_group = 'liaison-site-prober';
		// 嘗試從快取抓資料
		$results = wp_cache_get( $cache_key, $cache_group );
		if ( false === $results ) {
			// Safe direct database access (custom table, prepared query)
			$table = sanitize_key( $this->table_name );		
			$sql = "SELECT message AS message
					FROM {$table} {$where} ";				
			$logs = $wpdb->get_results( $sql, 'ARRAY_A' );
			wp_cache_set( $cache_key, $logs, $cache_group, 5 * MINUTE_IN_SECONDS );
		} else {
			$logs = $results;
		}

		if ( false !== $logs ) {
		?>
			<select id="log-select" name="log-select">
			<option value=""><?php echo esc_html__( 'All Logs', 'liaison-site-prober' ) ?></option>
			<?php 
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fully escaped when building each option
				$log_output = array();	
				foreach ( $logs as $_log ) {
					$log_output[] = sprintf(
						'<option value="%s"%s>%s</option>',
						esc_html( $_log['message'] ), // escape attribute
						selected( $_log['message'], true, false ),
						esc_html( $_log['message'] )  // escape display text
					);
				}
				echo implode( '', $log_output );
			?>
			</select>
		<?php 
		}
	}

	public function extra_tablenav( $which ) {
		global $wpdb;

		if ( 'bottom' === $which ) {
			$this->extra_tablenav_footer();
            return;
		}

		if ( 'top' !== $which ) {
			return;
        }
        
        if ( 'top' === $which ) {
            //$this->extra_tablenav_header();
        }

		echo '<div class="alignleft actions">';

		$cache_key   = 'site_prober_logs_page_custom_log';
		$cache_group = 'liaison-site-prober';

		// 嘗試從快取抓資料
		$results = wp_cache_get( $cache_key, $cache_group );

		if ( isset($results['plugins']) || isset($results['log_severity']) ) {		
			// cache hit — 必須把 cache 裡的資料還原到物件屬性
			$this->plugins      = $results['plugins'];
			$this->log_severity = $results['log_severity'];
		} else {
			// Safe direct database access (custom table, prepared query)
			$table = esc_sql( $this->table_name );

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is sanitized above.
			$this->plugins = $wpdb->get_col(
				"SELECT DISTINCT plugin_name 
				FROM `{$table}`
				GROUP BY plugin_name
				ORDER BY plugin_name DESC
				LIMIT 200;"
			);

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is sanitized above.
			$this->log_severity = $wpdb->get_col(
				"SELECT DISTINCT severity 
				FROM `{$table}`
				GROUP BY severity
				ORDER BY severity DESC
                LIMIT 200;"
			);

			// 正確存入 cache
			$results = [
				'plugins'      => $this->plugins,
				'log_severity' => $this->log_severity,
			];

			wp_cache_set( $cache_key, $results, $cache_group, 5 * MINUTE_IN_SECONDS );
		} 

		// Make sure we get items for filter.
		if ( $this->plugins || $this->log_severity ) {
			submit_button( __( 'Filter', 'liaison-site-prober' ), 'button', 'wpsp-filter', false, array() );
		}

		if ( $this->plugins ) {
			if ( ! isset( $_REQUEST['pluginshow'] ) )
				$_REQUEST['pluginshow'] = '';

			$selected_value = isset( $_REQUEST['pluginshow'] )
				? sanitize_text_field( wp_unslash( $_REQUEST['pluginshow'] ) )
				: '';

			?>
				<label for="pluginshow">
				</label>
				<select name="pluginshow" id="pluginshow">
				<option value=""><?php echo esc_html( 'All Plugins', 'liaison-site-prober' ); ?></option>
				<?php 
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fully escaped when building each option
					$name_output = array();
			
					foreach ( $this->plugins as $_plugin ) {
						$name_output[] = sprintf(
							'<option value="%s"%s>%s</option>',
							esc_html( $_plugin ), // escape attribute
							selected( $_plugin, $selected_value, false ),
							esc_html( $_plugin )  // escape display text
						);
					}
					echo implode( '', $name_output );
				?>
				</select>

				<span id="log-select">
				<?php
					// AJAX will rerender，call here first time
					$this->log_plugin_select( $this->plugin_select );
				?>
				</span>
			<?php
		}

		if ( $this->log_severity ) {
			if ( ! isset( $_REQUEST['severityshow'] ) ) {
				$_REQUEST['severityshow'] = '';
			}

			$output = array();
			$selected_value = isset( $_REQUEST['severityshow'] )
				? sanitize_text_field( wp_unslash( $_REQUEST['severityshow'] ) )
				: '';

			foreach ( $this->log_severity as $severity ) {
				$output[] = sprintf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $severity ), // escape attribute
					selected( $selected_value, $severity, false ),
					esc_html( $severity )  // escape display text
				);
			}

			?>
				<select name="severityshow" id="hs-filter-severityshow">
					<option value=""><?php echo esc_html( 'All Severity', 'liaison-site-prober' ); ?></option>
					<?php 
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fully escaped when building each option
					echo implode( '', $output );
					?>
				</select>
			<?php
		}

		$filters = array(
			'pluginshow',
            'severityshow',
		);

		foreach ( $filters as $filter ) {
			if ( ! empty( $_REQUEST[ $filter ] ) ) {
			?>
				<a href="<?php echo esc_html( $this->get_filtered_link() ); ?>" id="wpsp-reset-filter">
					<span class="dashicons dashicons-dismiss"></span> . 
					<?php echo esc_html( __( 'Reset Filters', 'liaison-site-prober' ) ); ?>
				</a>
			<?php

				break;
			}
		}

		echo '</div>';
	}

    public function search_box( $text, $input_id ) {
		$search_data = isset( $_REQUEST['s_custom_log'] ) ? sanitize_text_field( wp_unslash($_REQUEST['s_custom_log'] ) ) : '';

		$input_id = $input_id . '-search-input-custom-log';
		?>
		<p class="search-box">
            <label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>"><?php echo esc_attr($text); ?>:</label>
			<input type="search" id="<?php echo esc_attr($input_id); ?>" name="s_custom_log" value="<?php echo esc_attr( $search_data ); ?>" placeholder="<?php esc_attr_e( 'Search plugins, messages', 'liaison-site-prober' ); ?>"/>
			<?php submit_button( $text, 'button', false, false, array('id' => 'search-submit') ); ?>
		</p>
	<?php
	}

	public function display_tablenav( $which ) {
		if ( 'top' == $which ) {
			$this->search_box( __( 'Search', 'liaison-site-prober' ), 'wpsp-search' );
		}
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">
			<?php
			    $this->extra_tablenav( $which );
			    $this->pagination( $which );
			?>
			<br class="clear" />
		</div>
		<?php
	}

	*/


}
