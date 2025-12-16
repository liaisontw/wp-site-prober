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
	

	/*
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
		*/

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

		
		$where_sql = 'WHERE 1=1';
		if ( ! empty( $args['where'] ) ) {
			$where_sql .= ' AND ' . implode( ' AND ', $args['where'] );
		}
		
		$session = '';
		if ( ! empty( $_REQUEST['session-select'] ) ) {
			$where_sql .= $wpdb->prepare( " AND `session_id` = %d", $_POST['session-select'] );
		} else {
			$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_session}" );
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name sanitized and validated above.
			/*
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
			*/

			
			$session = "SELECT
					ID AS id,
					post_excerpt AS plugin_name,
					post_title AS message,
					menu_order AS severity,
					post_date AS created_at,
					1 AS session_type
				FROM {$wpdb->posts}
				{$where_sql}
			";
			
	
			//$args['where'] .= ' AND ( session_id = 0 OR session_id IS NULL )';
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is sanitized above.
			//Since pagiation gets LIMIT $items_per_page=20 from DB table.
			//$total_items must get whole table count directly.
			//$total_items = count( $this->items ); gets only $items_per_page.
			$total_items += $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$args['where']}" );
		}

		//$total_items  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_session}" );
		//$total_items += (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$args['where']}" );

		/*
		$sql = $wpdb->prepare(
			"SELECT id, plugin_name, message, severity, created_at, session_type
			FROM {$table}
			{$args['where']}
			ORDER BY {$args['orderby']} {$args['order']}
			LIMIT %d, %d",
			$args['offset'],
			$args['limit']
		);
		*/

		$comments_sql ="
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
			({$session})
			UNION ALL
			({$comments_sql})
			ORDER BY {$args['orderby']} {$args['order']}
			LIMIT %d, %d
			",
			$args['offset'],
			$args['limit']
		);

		/*
		$sql =  $wpdb->prepare(
				"SELECT
				comment_ID AS id,
				comment_author AS plugin_name,
				comment_content AS message,
				user_ID AS severity,
				comment_date AS created_at,
				0 AS session_type
			FROM
				{$wpdb->comments}
			WHERE
				{$where_sql}
			ORDER BY
			ORDER BY {$args['orderby']} {$args['order']}
			LIMIT %d, %d",
			$args['offset'],
			$args['limit']
			);

		if ($session != '') {
			$sql = $session . $sql;	
		}
			*/
		
		$items = $wpdb->get_results( $sql, ARRAY_A );

		$result = [
			'items'       => $items,
			'total_items' => (int) $total_items ?: count( $items ),
		];

		wp_cache_set( $cache_key, $result, $cache_group, 5 * MINUTE_IN_SECONDS );

		return $result;
	}

}
