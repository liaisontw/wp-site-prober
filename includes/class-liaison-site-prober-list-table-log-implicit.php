<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'LIAISIPR_List_Table_Custom_Log' ) )
	require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-liaison-site-prober-list-table-custom-log.php';

class LIAISIPR_List_Table_Log_Implicit extends LIAISIPR_List_Table_Custom_Log {
	
	public function build_query_args() {
		global $wpdb;

		$where = [];

		if ( ! empty( $_REQUEST['severityshow'] ) ) {
			$where[] = $wpdb->prepare(
				'severity = %s',
				sanitize_text_field( wp_unslash( $_REQUEST['severityshow'] ) )
			);
		}

		if ( ! empty( $_REQUEST['pluginshow'] ) ) {
			$where[] = $wpdb->prepare(
				'plugin_name = %s',
				sanitize_text_field( wp_unslash( $_REQUEST['pluginshow'] ) )
			);
		}

		if ( ! empty( $_REQUEST['s_custom_log'] ) ) {
			$like = '%' . $wpdb->esc_like(
				sanitize_text_field( wp_unslash( $_REQUEST['s_custom_log'] ) )
			) . '%';

			$where[] = $wpdb->prepare(
				'(plugin_name LIKE %s OR message LIKE %s)',
				$like,
				$like
			);
		}

		return [
			'where'   => $where, // array of conditions
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

		//$where_sql = 'WHERE 1=1';
		$where_sql = '';
		if ( ! empty( $args['where'] ) ) {
			//$where_sql .= ' AND ' . implode( ' AND ', $args['where'] );
			$where_sql = $args['where'];
		}
		
		$total_items = 0;
		$session_sql = '';
		if ( ! empty( $_REQUEST['session-select'] ) ) {
			$where_sql .= $wpdb->prepare( " AND `session_id` = %d", $_POST['session-select'] );
		} 
		else 
		{
			$session_sql = "SELECT
					ID AS id,
					post_excerpt AS plugin_name,
					post_title AS message,
					menu_order AS severity,
					post_date AS created_at,
					1 AS session_type
				FROM {$wpdb->posts}
				{$where_sql}
			";		
	
			$total_items = $wpdb->get_var( 
				"SELECT COUNT(*) FROM {$wpdb->posts}{$where_sql}" );
		}

		$total_items += $wpdb->get_var( 
				"SELECT COUNT(*) FROM {$wpdb->comments}{$where_sql}" );

		$logs_sql ="
			SELECT
				comment_ID AS id,
				comment_author AS plugin_name,
				comment_content AS message,
				user_ID AS severity,
				comment_date AS created_at,
				0 AS session_type
			FROM {$wpdb->comments}
			{$where_sql}
		";

		$sql = $wpdb->prepare(
			"
			({$session_sql})
			UNION ALL
			({$logs_sql})
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
