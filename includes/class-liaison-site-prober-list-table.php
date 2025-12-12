<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );


class LIAISIPR_List_Table extends WP_List_Table {

    protected $data_types = array();
    public $table_name = '';        

    public function __construct( $args = array() ) {
        global $wpdb;

		parent::__construct(
			array(
				'singular'  => esc_html__( 'activity', 'liaison-site-prober' ),
				'plural'    => esc_html__( 'activities', 'liaison-site-prober' ),
			)
		);

        //$this->table_name = $wpdb->wpsp_activity;        
		$this->table_name = $args['table_name'] ?? $wpdb->wpsp_activity;
    }

    private function get_filtered_link( $name = '', $value = '' ) {
		$base_page_url = menu_page_url( 'wpsp_site_prober_log_list', false );

		if ( empty( $name ) ) {
			return $base_page_url;
		}

		return add_query_arg( $name, $value, $base_page_url );
	}


    public function column_created_at( $item ) {
		return esc_html( $item['created_at'] );
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
					isset( $user->roles[0] ) && isset( $wp_roles->role_names[ $user->roles[0] ] ) ? $wp_roles->role_names[ $user->roles[0] ] : __( 'Unknown', 'liaison-site-prober' )
				);		
			}
		} else {
			$msg =  sprintf(
				'<span class="wpsp-author-name">%s</span>',
				__( 'N/A', 'liaison-site-prober' )
			);
		}
		
		return $msg;
	}
    public function column_user_id( $item ) {
        return $this->user_info( $item['user_id'] ); 
	}

    public function column_action( $item ) {
		return esc_html( $item['action'] );
	}

    public function column_object_type( $item ) {
		return esc_html( $item['object_type'] );
	}

    public function column_description( $item ) {
		return esc_html( $item['description'] );
	}

    public function column_ip( $item ) {
		return esc_html( $item['ip'] );
	}

    public function get_columns() {
        $columns = array(
            'created_at'  => __( 'Time', 'liaison-site-prober' ),
            'user_id'     => __( 'User Info', 'liaison-site-prober' ),
            'ip'          => __( 'IP', 'liaison-site-prober' ),
            'action'      => __( 'Action', 'liaison-site-prober' ),
            'object_type' => __( 'Object', 'liaison-site-prober' ),
            'description' => __( 'Description', 'liaison-site-prober' ),
        );

		return $columns;
	}
    

	public function get_hidden_columns() {
		return array();
	}

	public function get_sortable_columns() {
		return array(
			'created_at' => array( 'created_at', false ),
			'user_id'    => array( 'user_id', false ),
			'ip'         => array( 'ip', false ),
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
								'action' => 'WP_Site_Prober_export_csv',
								'tab'    => $_GET['tab'] ?? 'log'
							),
							admin_url('admin-post.php')
						),
						'wpsp_list_table_action',
						'wpsp_nonce'
					);

				?>
				<a class="button" href="<?php echo esc_url( $export_url ); ?>">
					<?php esc_html_e( 'Export CSV', 'liaison-site-prober' ); ?>
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
	    ?>
            <form id="wpsp-form-delete" method="post" action="">
                <input type="hidden" id="clearLogs" name="clearLogs" value="Yes">
				<?php wp_nonce_field( 'wpsp_list_table_action', 'wpsp_nonce' ); ?>
                <div class="alignleft actions">
                    <?php submit_button( __( 'Clear Logs', 'liaison-site-prober' ), '', 'clear_action', false ); ?>
                </div>
			</form>
		<?php
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
            $this->extra_tablenav_header();
        }

		echo '<div class="alignleft actions">';

		//$table = sanitize_key( $this->table_name );
		$cache_key   = 'site_prober_logs_page_';
		$cache_group = 'liaison-site-prober';

		// 嘗試從快取抓資料
		$results = wp_cache_get( $cache_key, $cache_group );

		if (    ( false !== $results) 
			 && ( isset($results['users']) && isset($results['data_types']) ) 
		   )
		{
				// cache hit — 必須把 cache 裡的資料還原到物件屬性
				$users      	  = $results['users'];
				$this->data_types = $results['data_types'];
		} else {
			// Safe direct database access (custom table, prepared query)
			$table = sanitize_key( $this->table_name );

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is sanitized above.
			$users = $wpdb->get_results(
				"SELECT DISTINCT user_id 
				FROM `{$table}`
				GROUP BY user_id
				ORDER BY user_id
				LIMIT 200;"
			);

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is sanitized above.
			$this->data_types = $wpdb->get_col(
				"SELECT DISTINCT object_type 
				FROM `{$table}`
				GROUP BY object_type
				ORDER BY object_type
				;"
			);

			$results = [
				'users' 	  => $users,
				'data_types'  => $this->data_types,
			];

			wp_cache_set( $cache_key, $results, $cache_group, 5 * MINUTE_IN_SECONDS );
		}

		// Make sure we get items for filter.
		if ( $users || $this->data_types ) {
			submit_button( esc_html( __( 'Filter', 'liaison-site-prober' ) ), 'button', 'wpsp-filter', false, array() );
		}

		if ( $users ) {
			if ( ! isset( $_REQUEST['usershow'] ) )
				$_REQUEST['usershow'] = '';

			$output = array();
			foreach ( $users as $_user ) {
				if ( 0 === (int) $_user->user_id ) {
					$output[0] = esc_html( __( 'N/A', 'liaison-site-prober' ) );
					continue;
				}

				$user = get_user_by( 'id', $_user->user_id );
				if ( $user )
					$output[ $user->ID ] = $user->user_nicename;
			}

			$selected_value = isset( $_REQUEST['usershow'] )
				? sanitize_text_field( wp_unslash( $_REQUEST['usershow'] ) )
				: '';

			$name_output = array();
			if ( ! empty( $output ) ) {
				foreach ( $output as $key => $value ) {
					$name_output[] = sprintf(
						'<option value="%s"%s>%s</option>',
						esc_attr( $key ), // escape attribute
						selected( $selected_value, $key, false ),
						esc_html( $value )  // escape display text
					);
				}
			}
			?>
				<select name="usershow" id="hs-filter-usershow">
				<option value=""><?php echo esc_html( 'All Users' ); ?></option>
				<?php 
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fully escaped when building each option
					echo implode( '', $name_output );
				?>
				</select>
			<?php
		}

		if ( $this->data_types ) {
			if ( ! isset( $_REQUEST['typeshow'] ) ) {
				$_REQUEST['typeshow'] = '';
			}

			$output = array();

			$selected_value = isset( $_REQUEST['typeshow'] )
				? sanitize_text_field( wp_unslash( $_REQUEST['typeshow'] ) )
				: '';

			foreach ( $this->data_types as $object_type ) {

				$output[] = sprintf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $object_type ), // escape attribute
					selected( $selected_value, $object_type, false ),
					esc_html( $object_type )  // escape display text
				);
			}

			?>
				<select name="typeshow" id="hs-filter-typeshow">
					<option value=""><?php echo esc_html( 'All Objects' ); ?></option>
					<?php 
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fully escaped when building each option
					echo implode( '', $output );
					?>
				</select>
			<?php

		}

		$filters = array(
			'usershow',
            'typeshow',
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
		$search_data = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash($_REQUEST['s'] ) ) : '';

		$input_id = $input_id . '-search-input';
		?>
		<p class="search-box">
            <label class="screen-reader-text" for="<?php echo esc_attr($input_id); ?>"><?php echo esc_attr($text); ?>:</label>
			<input type="search" id="<?php echo esc_attr($input_id); ?>" name="s" value="<?php echo esc_attr( $search_data ); ?>" placeholder="<?php esc_attr_e( 'Search actions, descriptions, IP', 'liaison-site-prober' ); ?>"/>
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

    public function delete_all_items() {
		global $wpdb;
		$table = sanitize_key( $this->table_name );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name sanitized above.
		$wpdb->query( "TRUNCATE TABLE {$table}" );
	}
    public function prepare_items() {
		global $wpdb;

        $items_per_page = 20;        
        
        $clear  = isset( $_POST['clearLogs'] ) ? sanitize_text_field( wp_unslash( $_POST['clearLogs'] ) ) : '';
		if ( $clear ){
			check_admin_referer( 'wpsp_list_table_action', 'wpsp_nonce' );
			$this->delete_all_items();
		}
        
        $search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		        $offset = ( $this->get_pagenum() - 1 ) * $items_per_page;
        $where = ' WHERE 1 = 1';

		if ( ! empty( $_REQUEST['typeshow'] ) ) {
			$where .= $wpdb->prepare( ' AND `object_type` = %s', 
			sanitize_text_field( wp_unslash( $_REQUEST['typeshow'] ) ) );
		}

        if ( isset( $_REQUEST['usershow'] ) && '' !== $_REQUEST['usershow'] ) {
			$where .= $wpdb->prepare( 
				' AND `user_id` = %d', 
				(int) $_REQUEST['usershow'] 
			);
		}

		if ( $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
            $where .= $wpdb->prepare( ' AND (`action` LIKE %s OR `description` LIKE %s OR `ip` LIKE %s)', $like, $like, $like );
		}
      
		// Safe direct database access (custom table, prepared query)
		$table = sanitize_key( $this->table_name );

		$orderby = isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'created_at';
		//$order picked is safe since it comes from ['asc','desc']
		$order   = isset( $_GET['order'] ) && in_array( strtolower($_GET['order']), ['asc','desc'], true )
			? strtolower( sanitize_key( $_GET['order'] ) )
			: 'desc';

		$allowed_orderby = ['created_at', 'user_id', 'ip'];
		if ( ! in_array( $orderby, $allowed_orderby, true ) ) {
			$orderby = 'created_at';
		}

		$cache_key   = 'site_prober_logs_page_';
		$cache_group = 'liaison-site-prober';

		// 嘗試從快取抓資料
		$results = wp_cache_get( $cache_key, $cache_group );

		if (   is_array( $results ) 
    		&& isset( $results['items'], $results['total_items'] )
    		&& is_array( $results['items'] ) ) 
		{
			$this->items = $results['items'];
			$total_items = $results['total_items'];	
		} else {

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is sanitized above.
			//Since pagiation gets LIMIT $items_per_page=20 from DB table.
			//$total_items must get whole table count directly.
			//$total_items = count( $this->items ); gets only $items_per_page.
			// count total items
			$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name sanitized and validated above.
			$this->items = $wpdb->get_results( 
				$wpdb->prepare(
					"SELECT * FROM {$table} {$where} 
					ORDER BY {$orderby} {$order}
					LIMIT %d, %d",
					$offset,
					$items_per_page
				),
				ARRAY_A
			);
			
			$results = [
				'items'      => $this->items,
				'total_items' => $total_items,
			];

			wp_cache_set( $cache_key, $results, $cache_group, 5 * MINUTE_IN_SECONDS );
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
}