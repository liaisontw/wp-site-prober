<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'LIAISIPR_List_Table_Custom_Log' ) )
	require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-liaison-site-prober-list-table-custom-log.php';

class LIAISIPR_List_Table_Log_Implicit extends LIAISIPR_List_Table_Custom_Log {
	const TAXONOMY = LIAISIP_TAXONOMY;
	const CPT      = LIAISIP_CPT;

	public function column_message( $item ) {
		if ( 1 == $item['session_type'] ) {
			$session_url = esc_url( admin_url( 'admin.php?page=wpsp_site_prober_log_list&tab=implicit&session-select=' . intval( $item['id'] ) ) );
			$message = "<a href='{$session_url}' class='thickbox'>" . esc_html( $item['message'] ) . "</a>";
		} else {
			$message = esc_html( $item['message'] );
		}
		return $message;
    }

	protected function get_log_generate_url() {
		return wp_nonce_url(
			add_query_arg(
				[
					'action' => 'WP_Implicit_Log_log_generate',
					//'tab'    => $_GET['tab'] ?? 'log',
					'tab'    => 'implicit',
				],
				admin_url('admin-post.php')
			),
			'wpsp_log_generate_implicit_log',
			'wpsp_nonce_implicit_log'
		);
	}

	protected function render_log_generate_button() {
		printf(
			'<a class="button" href="%s">%s</a>',
			esc_url($this->get_log_generate_url()),
			esc_html__('Implicit Log Generate', 'liaison-site-prober')
		);
	}

	protected function get_session_generate_url() {
		return wp_nonce_url(
			add_query_arg(
				[
					'action' => 'WP_Implicit_Log_session_generate',
					'tab'    => 'implicit',
				],
				admin_url('admin-post.php')
			),
			'wpsp_session_generate_implicit_log',
			'wpsp_nonce_implicit_log'
		);
	}

	protected function render_session_generate_button() {
		printf(
			'<a class="button" href="%s">%s</a> <br class="clear" />',
			esc_url($this->get_session_generate_url()),
			esc_html__('Implicit Session Generate', 'liaison-site-prober')
		);
	}

	protected function get_export_url() {
		return wp_nonce_url(
			add_query_arg(
				[
					'action' => 'WP_Site_Prober_export_csv_implicit_log',
					'tab'    => 'implicit',
				],
				admin_url('admin-post.php')
			),
			'wpsp_export_implicit_log',
			'wpsp_nonce_implicit_log'
		);
	}

	protected function render_export_button() {
		printf(
			'<a class="button" href="%s">%s</a> <br class="clear" />',
			esc_url($this->get_export_url()),
			esc_html__('Export CSV (Implicit Log)', 'liaison-site-prober')
		);
	}

	public function render_log_clear_button2() {
	    ?>
			<br class="clear" />
            <form id="wpsp-form-delete-implicit" method="post" action="">
                <input type="hidden" id="clearLogsImplicitLog" name="clearLogsImplicitLog" value="Yes">
				<?php wp_nonce_field( 'wpsp_delete_implicit_log', 'wpsp_nonce_delete_implicit_log' ); ?>

                <div class="alignleft actions">
                    <?php submit_button( __( 'Clear Implicit Logs', 'liaison-site-prober' ), '', 'clear_action', false ); ?>
                </div>
			</form>
			
		<?php
	}
	public function extra_tablenav_footer() {
	    
	}


	public function search_box( $text, $input_id ) {
		$search_data = isset( $_REQUEST['s_implicit_log'] ) ? sanitize_text_field( wp_unslash($_REQUEST['s_implicit_log'] ) ) : '';

		$input_id = $input_id . '-search-input-implicit-log';
		?>
			<p class="search-box">
				<label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>"><?php echo esc_attr($text); ?>:</label>
				<input type="search" id="<?php echo esc_attr($input_id); ?>" name="s_implicit_log" value="<?php echo esc_attr( $search_data ); ?>" placeholder="<?php esc_attr_e( 'Search plugins, messages', 'liaison-site-prober' ); ?>"/>
				<?php submit_button( $text, 'button', false, false, array('id' => 'search-submit') ); ?>
			</p>
		<?php
	}

	public function render_plugin_filter($plugins, $selected) {
		echo '<select name="implicit_plugin_filter" id="implicit_plugin_filter">';
		echo '<option value="">' . esc_html__('All Plugins', 'liaison-site-prober') . '</option>';
		foreach ($plugins as $p) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr($p),
				selected($selected, $p, false),
				esc_html($p)
			);
		}
		echo '</select>';
	}

	public function delete_all_items_custom_log() {
		if ( empty($_REQUEST['plugindelete']) ) {
			return;
		}
		$plugin_to_delete = sanitize_text_field( $_REQUEST['plugindelete'] );
		do_action( 'wpsp_implicit_log_clean', $plugin_to_delete );
	}

	public function maybe_handle_clear_action() {
		if ( empty( $_POST['clearLogsImplicitLog'] ) ) {
			return;
		}

		check_admin_referer( 'wpsp_delete_implicit_log', 'wpsp_nonce_delete_implicit_log' );
		$this->delete_all_items_custom_log();
	}

	public function render_log_clear_button() {
	?>
		<div class="alignleft actions"> 
			<label for="plugin-select">
				<?php esc_html_e( 'Log Clear for Plugin:', 'liaison-site-prober' ); ?>
			</label>
			<br class="clear" />
			<form id="wpsp-form-delete" method="post" action="">
                <input type="hidden" id="clearLogsImplicitLog" name="clearLogsImplicitLog" value="Yes">
				<?php wp_nonce_field( 'wpsp_delete_implicit_log', 'wpsp_nonce_delete_implicit_log' ); ?>
				<?php
					$filters_delete = $this->load_filter_options();
					$selected_plugin  = $_REQUEST['plugindelete']   ?? '';
					if ($filters_delete['plugins']) {
						$this->render_plugin_filter_delete($filters_delete['plugins'], $selected_plugin);
					}
				?>
				<?php submit_button( __( 'Clear', 'liaison-site-prober' ), '', 'clear_action', false ); ?>
			</form>
		</div>
	<?php
	}

	public function load_filter_options() {		
		global $wpdb;
		return $this->get_cached_list('implicit_log_filter_options', function() use ($wpdb) {
			//global $wpdb;
			$comment_where = "comment_approved = '" . esc_sql( self::CPT ) . "'";

			return [
				'plugins' => $wpdb->get_col(
					"SELECT DISTINCT 
						comment_author AS plugin_name
					FROM `{$wpdb->comments}` 
					WHERE {$comment_where} 
					ORDER BY plugin_name 
					DESC LIMIT 200"
				),
				'severity' => $wpdb->get_col(
					"SELECT DISTINCT 
						user_ID AS severity
					FROM `{$wpdb->comments}` 
					WHERE {$comment_where} 
					ORDER BY severity
					DESC LIMIT 200"
				),
			];
		});
		
	}

	public function build_slug( $slug, $prefix = '' ) {
        $slug = sanitize_title( $slug );
        $prefix = sanitize_title( $prefix );
        
        return $prefix ? "{$prefix}-{$slug}" : "msg_category-{$slug}";
    }

	public function build_query_args() {
		global $wpdb;

		$where = [];
		//$post_where    = "post_type = '" . esc_sql( LIAISIP_CPT ) . "' AND post_parent != 0";
		//$comment_where = "comment_approved = '" . esc_sql( LIAISIP_CPT ) . "'";
		$post_where    = "post_type = '" . esc_sql( self::CPT ) . "' AND post_parent != 0";
		$comment_where = "comment_approved = '" . esc_sql( self::CPT ) . "'";

		if ( ! empty( $_REQUEST['severityshow'] ) ) {
			// $where[] = $wpdb->prepare(
			// 	'severity = %s',
			// 	sanitize_text_field( wp_unslash( $_REQUEST['severityshow'] ) )
			// );
		}

		$join = '';
		if ( ! empty( $_REQUEST['pluginshow']) ) {
			$selected_plugin  = sanitize_text_field( wp_unslash( $_REQUEST['pluginshow'] ))  ?? '';
			$term = get_term_by( 'slug', $this->build_slug( $selected_plugin ), self::TAXONOMY );
			if ( $term ) {
				//$post_where .= $wpdb->prepare( " AND (wp_term_relationships.term_taxonomy_id IN (%d))", intval( $term->term_id ) );
				$comment_where .= $wpdb->prepare( " AND comment_author = %s", $selected_plugin );
				//$join = 'INNER JOIN ' . $wpdb->term_relationships . ' ON ' . $wpdb->posts . '.ID = ' . $wpdb->term_relationships . '.object_id ';
			}
		}

		if ( ! empty( $_REQUEST['s_implicit_log'] ) ) {
			$like = '%' . $wpdb->esc_like(
				sanitize_text_field( wp_unslash( $_REQUEST['s_implicit_log'] ) )
			) . '%';

			$post_where .= $wpdb->prepare( " 
				AND ( {$wpdb->posts}.post_title LIKE %s )", $like );
			$comment_where .= $wpdb->prepare( " 
			    AND ( {$wpdb->comments}.comment_content LIKE %s 
				OR {$wpdb->comments}.comment_author LIKE %s )"
				, $like, $like );
		}


		return [
			'where'   => $where, // array of conditions
			'join'    => $join,
			'post_where'    => $post_where,
			'comment_where' => $comment_where,
			'orderby' => $this->get_orderby(),
			'order'   => $this->get_order(),
			'offset'  => ( $this->get_pagenum() - 1 ) * $this->_items_per_page,
			'limit'   => $this->_items_per_page,
		];
	}
	
	public function get_items_with_cache( $args ) {
		global $wpdb;

		$cache_key   = 'site_prober_logs_page_log_implicit';
		$cache_group = 'liaison-site-prober';

		$cached = wp_cache_get( $cache_key, $cache_group );
		if ( is_array( $cached )
			&& isset( $cached['items'], $cached['total_items'] )
		) {
			return $cached;
		}

		$post_where    = $args['post_where'];
		$comment_where = $args['comment_where'];	
		$total_items = 0;
		$session_sql = '';

		if ( ! empty( $_REQUEST['session-select'] ) ) {
			$comment_where .= $wpdb->prepare( " AND comment_post_ID = %d AND comment_parent = 1", intval( $_REQUEST['session-select'] ) );
		} 
		else 
		{
			$comment_where .= ' AND comment_parent = 0';
			
			$session_sql = "(
				SELECT
					ID AS id,
					post_excerpt AS plugin_name,
					post_title AS message,
					menu_order AS severity,
					post_date AS created_at,
					1 AS session_type
				FROM {$wpdb->posts}
				WHERE {$post_where}
			) UNION ALL ";		
	
			$total_items = $wpdb->get_var( 
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE {$post_where}" );
		}

		$total_items += $wpdb->get_var( 
				"SELECT COUNT(*) FROM {$wpdb->comments} WHERE {$comment_where}" );

		//var_dump($comment_where);

		$logs_sql ="(
			SELECT
				comment_ID AS id,
				comment_author AS plugin_name,
				comment_content AS message,
				user_ID AS severity,
				comment_date AS created_at,
				0 AS session_type
			FROM {$wpdb->comments}
			WHERE {$comment_where}
		)";

		// ****Combine $session_sql & $logs_sql
		
		$sql = $wpdb->prepare(
			"
			{$session_sql}
			{$logs_sql}
			ORDER BY {$args['orderby']} {$args['order']}
			LIMIT %d, %d
			",
			$args['offset'],
			$args['limit']
		);
		
		$items = $wpdb->get_results( $sql, ARRAY_A );
		$result = [
			'items'       => $items,
			'total_items' => (int) $total_items ?: count( $items ),
		];

		wp_cache_set( $cache_key, $result, $cache_group, 5 * MINUTE_IN_SECONDS );

		return $result;
	}

	/*
	public function extra_tablenav($which) {
		if ($which === 'bottom') {
			echo '<div class="alignleft actions">';
			$this->render_export_button();
			$this->render_log_generate_button();
			$this->render_session_generate_button();
			$this->extra_tablenav_footer();
			echo '</div>';
			return;
		}

		if ($which !== 'top') return;

		if ($which === 'top') {
			//$this->search_box(__('Search', 'liaison-site-prober'), 'wpsp-search');
		}

		$filters = $this->load_filter_options();

		$selected_plugin  = $_REQUEST['pluginshow']   ?? '';
		$selected_sev     = $_REQUEST['severityshow'] ?? '';

		echo '<label for="plugin-filter">';
		echo esc_html_e( 'Plugin Display Filter:', 'liaison-site-prober' );
		echo '</label>';

		if ($filters['plugins']) {
			$this->render_plugin_filter($filters['plugins'], $selected_plugin);
		}

		if ($filters['severity']) {
			$this->render_severity_filter($filters['severity'], $selected_sev);
		}

		submit_button(__('Filter', 'liaison-site-prober'), 'button', '', false);

		echo '<span id="log-select">';
		// AJAX will rerender，call here first time
		$this->log_plugin_select( $this->plugin_select );
		echo '</span>';
	}
		*/

	
	public function log_plugin_select( $plugin_select ) {
		global $wpdb;

		error_log( sprintf('implicit_plugin_filter : %s', $plugin_select) );		
		return false;
		if ( '' === $plugin_select ) {
			return false;
		}

		$messages = '';
		/*
		$where = $wpdb->prepare( 
				' WHERE `plugin_name` = %s', 
				$plugin_select 
			);

		//error_log( sprintf('where : %s', $where) );		
		$cache_key   = 'ajax_custom_log';
		$cache_group = 'liaison-site-prober';
		// 嘗試從快取抓資料
		$results = false;
		$results = wp_cache_get( $cache_key, $cache_group );
		
		if ( is_array( $results ) && isset( $results )
		) {
			$messages = $results;
		} else {
			// Safe direct database access (custom table, prepared query)
			$table = sanitize_key( $this->table_name );		
			$sql = "SELECT message AS message
					FROM {$table} {$where} ";				
			$messages = $wpdb->get_results( $sql, 'ARRAY_A' );
			wp_cache_set( $cache_key, $messages, $cache_group, 5 * MINUTE_IN_SECONDS );
		}

		foreach ( $messages as $message ) {
			foreach ( $message as $key => $_message ) {
				//error_log( sprintf('$key : %s,$_messageg : %s', $key, $_message) );		
			}
		}

		*/
		if ( false !== $messages ) {
		?>
			<select id="log-select" name="log-select">
			<option value=""><?php echo esc_html__( 'All Messages', 'liaison-site-prober' ) ?></option>
			<?php 
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fully escaped when building each option
				$message_output = array();	
				foreach ( $messages as $_message ) {
					$message_output[] = sprintf(
						'<option value="%s"%s>%s</option>',
						esc_html( $_message['message'] ), // escape attribute
						selected( $_message['message'], true, false ),
						esc_html( $_message['message'] )  // escape display text
					);
				}
				echo implode( '', $message_output );
			?>
			</select>
		<?php 
		}
	}

}
