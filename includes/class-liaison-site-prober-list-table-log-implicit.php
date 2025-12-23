<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'LIAISIPR_List_Table_Custom_Log' ) )
	require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-liaison-site-prober-list-table-custom-log.php';

#[AllowDynamicProperties]
class LIAISIPR_List_Table_Log_Implicit extends LIAISIPR_List_Table_Custom_Log {
	
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
			'<a class="button" href="%s">%s</a> <br class="clear" />',
			esc_url($this->get_log_generate_url()),
			esc_html__('Implicit Log Generate', 'liaison-site-prober')
		);
	}

	protected function get_session_generate_url() {
		return wp_nonce_url(
			add_query_arg(
				[
					'action' => 'WP_Implicit_Log_session_generate',
					//'tab'    => $_GET['tab'] ?? 'log',
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


	public function build_query_args() {
		global $wpdb;

		$where = [];
		//$post_where    = "post_type = 'log-catcher' OR post_type = 'wp-logger' OR post_type = 'liaisip-logs-cpt' AND post_parent != 0";
		//$comment_where = "comment_approved = 'log-catcher' OR comment_approved = 'wp-logger' OR comment_approved = 'liaisip-logs-cpt'";
		$post_where    = "post_type = '" . esc_sql( LIAISIP_CPT ) . "' AND post_parent != 0";
		$comment_where = "comment_approved = '" . esc_sql( LIAISIP_CPT ) . "'";

		if ( ! empty( $_REQUEST['severityshow'] ) ) {
			// $where[] = $wpdb->prepare(
			// 	'severity = %s',
			// 	sanitize_text_field( wp_unslash( $_REQUEST['severityshow'] ) )
			// );
		}

		$join = '';
		if ( ! empty( $_REQUEST['pluginshow'] ) ) {
			// $where[] = $wpdb->prepare(
			// 	'plugin_name = %s',
			// 	sanitize_text_field( wp_unslash( $_REQUEST['pluginshow'] ) )
			// );
			//$term = get_term_by( 'slug', $this->prefix_slug( sanitize_text_field( wp_unslash( $_POST['plugin-select'] ) ) ), self::TAXONOMY );
			if ( $term ) {
				$post_where .= $wpdb->prepare( " AND (wp_term_relationships.term_taxonomy_id IN (%d))", intval( $term->term_id ) );
				$comment_where .= $wpdb->prepare( " AND comment_author = %s", sanitize_text_field( wp_unslash( $_POST['plugin-select'] ) ) );
				$join = 'INNER JOIN ' . $wpdb->term_relationships . ' ON ' . $wpdb->posts . '.ID = ' . $wpdb->term_relationships . '.object_id ';
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
			/*
			$where[] = $wpdb->prepare(
				'(plugin_name LIKE %s OR message LIKE %s)',
				$like,
				$like
			);
			*/
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

}
