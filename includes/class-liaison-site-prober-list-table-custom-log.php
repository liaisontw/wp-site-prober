<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );


class LIAISIPR_List_Table_Custom_Log extends WP_List_Table {

    public $plugins = array();
    public $log_severity = array();
    public $table_name = '';        
	public $table_name_session = '';  
	public $plugin_select = '';
	public $_items_per_page = 20; 
	public $_total_items = 0; 

    public function __construct( $args = array() ) {
        global $wpdb;

		parent::__construct(
			array(
				'singular'  => esc_html__( 'custom_log', 'liaison-site-prober' ),
				'plural'    => esc_html__( 'custom_logs', 'liaison-site-prober' ),
			)
		);

		$this->table_name = $args['table_name'] ?? $wpdb->wpsp_custom_log;
		$this->table_name_session = $args['table_name_session'] ?? $wpdb->wpsp_custom_log_session;
    }

    private function get_filtered_link( $name = '', $value = '' ) {
		$base_page_url = menu_page_url( 'wpsp_site_prober_log_list', false );

		if ( empty( $name ) ) {
			return $base_page_url;
		}

		return add_query_arg( $name, $value, $base_page_url );
	}

    // public function column_log_id( $item ) {
    //     return esc_html( $item['log_id'] ); 
	// }

    public function column_message( $item ) {
		if ( 1 == $item['session_type'] ) {
			$session_url = esc_url( admin_url( 'admin.php?page=wpsp_site_prober_log_list&tab=custom&session-select=' . intval( $item['id'] ) ) );
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
            'message'     => __( 'Message', 'liaison-site-prober' ),
            'plugin'      => __( 'Plugin', 'liaison-site-prober' ),
            'severity'    => __( 'Severity', 'liaison-site-prober' ),
            'created_at'  => __( 'Time', 'liaison-site-prober' ),
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
    
    public function extra_tablenav_header() {
		/**
		 * Filter list of record actions
		 *
		 * @return array Array items should represent action_id => 'Action Title'
		 */
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
		/**
		 * Filter list of record actions
		 *
		 * @return array Array items should represent action_id => 'Action Title'
		 */
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

	/* build log <select> HTML (used in AJAX or page render) */
	
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

    public function delete_all_items_custom_log() {
		global $wpdb;
		$table = sanitize_key( $this->table_name );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name sanitized above.
		$wpdb->query( "TRUNCATE TABLE {$table}" );

		$table_session = sanitize_key( $this->table_name_session );
		$wpdb->query( "TRUNCATE TABLE {$table_session}" );
	}
    

	public function maybe_handle_clear_action() {
		if ( empty( $_POST['clearLogsCustomLog'] ) ) {
			return;
		}

		check_admin_referer( 'wpsp_delete_custom_log', 'wpsp_nonce_delete_custom_log' );
		$this->delete_all_items_custom_log();
	}

	public function get_orderby() {
		$allowed = [ 'created_at', 'plugin', 'severity' ];
		$orderby = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'created_at';

		return in_array( $orderby, $allowed, true ) ? $orderby : 'created_at';
	}

	public function get_order() {
		return ( isset( $_GET['order'] ) && 'asc' === strtolower( $_GET['order'] ) )
			? 'asc'
			: 'desc';
	}

	public function build_query_args() {
		global $wpdb;

		$where = ' WHERE 1 = 1';

		if ( ! empty( $_REQUEST['severityshow'] ) ) {
			$where .= $wpdb->prepare(
				' AND severity = %s',
				sanitize_text_field( wp_unslash( $_REQUEST['severityshow'] ) )
			);
		}

		if ( ! empty( $_REQUEST['pluginshow'] ) ) {
			$where .= $wpdb->prepare(
				' AND plugin_name = %s',
				sanitize_text_field( wp_unslash( $_REQUEST['pluginshow'] ) )
			);
		}

		if ( ! empty( $_REQUEST['s_custom_log'] ) ) {
			$like = '%' . $wpdb->esc_like(
				sanitize_text_field( wp_unslash( $_REQUEST['s_custom_log'] ) )
			) . '%';

			$where .= $wpdb->prepare(
				' AND (plugin_name LIKE %s OR message LIKE %s)',
				$like,
				$like
			);
		}

		return [
			'where'   => $where,
			'orderby' => $this->get_orderby(),
			'order'   => $this->get_order(),
			'offset'  => ( $this->get_pagenum() - 1 ) * $this->_items_per_page,
			'limit'   => $this->_items_per_page,
		];
	}

	public function get_items_with_cache( $args ) {
		global $wpdb;

		$cache_key   = 'site_prober_logs_page_custom_log';
		$cache_group = 'liaison-site-prober';

		$cached = wp_cache_get( $cache_key, $cache_group );
		if ( is_array( $cached )
			&& isset( $cached['items'], $cached['total_items'] )
		) {
			return $cached;
		}

		$table         = sanitize_key( $this->table_name );
		$table_session = sanitize_key( $this->table_name_session );

		$total_items = 0;
		$session = '';
		if ( ! empty( $_REQUEST['session-select'] ) ) {
			$args['where'] .= $wpdb->prepare( " AND `session_id` = %d", $_POST['session-select'] );
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
					FROM {$table_session} {$args['where']} 
					UNION
					";

			$args['where'] .= ' AND ( session_id = 0 OR session_id IS NULL )';
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is sanitized above.
			//Since pagiation gets LIMIT $items_per_page=20 from DB table.
			//$total_items must get whole table count directly.
			//$total_items = count( $this->items ); gets only $items_per_page.
			$total_items += $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$args['where']}" );
		}

		//$total_items  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_session}" );
		//$total_items += (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$args['where']}" );

		$sql = $wpdb->prepare(
			"SELECT id, plugin_name, message, severity, created_at, session_type
			FROM {$table}
			{$args['where']}
			ORDER BY {$args['orderby']} {$args['order']}
			LIMIT %d, %d",
			$args['offset'],
			$args['limit']
		);

		if ($session != '') {
			$sql = $session . $sql;	
		}
		
		$items = $wpdb->get_results( $sql, ARRAY_A );

		$result = [
			'items'       => $items,
			'total_items' => (int) $total_items ?: count( $items ),
		];

		wp_cache_set( $cache_key, $result, $cache_group, 5 * MINUTE_IN_SECONDS );

		return $result;
	}

	public function setup_table_headers() {
		$this->_column_headers = [
			$this->get_columns(),
			$this->get_hidden_columns(),
			$this->get_sortable_columns(),
		];
	}

	public function setup_pagination( $total_items ) {
		$this->set_pagination_args( [
			'total_items' => $total_items,
			'per_page'    => $this->_items_per_page,
			'total_pages' => ceil( $total_items / $this->_items_per_page ),
		] );
	}

	
	public function prepare_items( $plugin_select = '' ) {
		$this->plugin_select = $plugin_select;

		$this->maybe_handle_clear_action();

		$query_args = $this->build_query_args();

		$results = $this->get_items_with_cache( $query_args );

		$this->items = $results['items'];
		$total_items = $results['total_items'];

		$this->setup_table_headers();
		$this->setup_pagination( $total_items );
	}
	
}