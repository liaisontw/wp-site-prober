<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );


class wp_site_prober_List_Table extends WP_List_Table {

    public function __construct( $args = array() ) {
        $this->total_items = 20;

		parent::__construct(
			array(
				'singular'  => esc_html__( 'activity', 'wp-site-prober' ),
				'plural'    => esc_html__( 'activities', 'wp-site-prober' ),
			)
		);
    }

    /*
    public function prepare_items() {
		die( 'function WP_List_Table::prepare_items() must be overridden in a subclass.' );
	}
    public function get_columns() {
		die( 'function WP_List_Table::get_columns() must be overridden in a subclass.' );
	}    
    public function ajax_user_can() {
		die( 'function WP_List_Table::ajax_user_can() must be overridden in a subclass.' );
	}

    */

    private function get_filtered_link( $name = '', $value = '' ) {
		$base_page_url = menu_page_url( 'wp-site-prober', false );

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
    public function column_user_id( $item ) {
        return $this->user_info( $item['user_id'] ); 
		//return esc_html( $item['user_id'] );
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
            'created_at'  => __( 'Time', 'wp-site-prober' ),
            'user_id'     => __( 'User Info', 'wp-site-prober' ),
            'action'      => __( 'Action', 'wp-site-prober' ),
            'object_type' => __( 'Object', 'wp-site-prober' ),
            'description' => __( 'Description', 'wp-site-prober' ),
            'ip'          => __( 'IP', 'wp-site-prober' ),
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
    
    public function extra_tablenav_footer() {
		/**
		 * Filter list of record actions
		 *
		 * @return array Array items should represent action_id => 'Action Title'
		 */
		$actions = apply_filters( 'wpsp_record_actions', array() );
		?>
			<?php if ( count( $actions ) > 1 ) : ?>
			<div class="alignleft actions recordactions">
				<select name="wpsp-record-action">
					<option value=""><?php echo esc_attr__( 'Export File Format', 'wp-site-prober' ); ?></option>
					<?php foreach ( $actions as $action_key => $action_title ) : ?>
					<option value="<?php echo esc_attr( $action_key ); ?>"><?php echo esc_html( $action_title ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<?php else :
				$action_title = reset( $actions );
				$action_key = key( $actions );
			?>
			<input type="hidden" name="wpsp-record-action" value="<?php echo esc_attr( $action_key ); ?>">
			<?php endif; ?>

			<button type="submit" name="wpsp-record-actions-submit" id="record-actions-submit" class="button button-primary" value="1">
				<?php
				// Is result filtering enabled?
				if ( array_key_exists( 'wpsp-filter', $_GET ) ) {
					echo sprintf( esc_html__( 'Export filtered records as %s', 'wp-site-prober' ), $action_title );
				} else {
					echo sprintf( esc_html__( 'Export as %s', 'wp-site-prober' ), $action_title );
				}
				?>
			</button>

			<?php wp_nonce_field( 'wpsp_actions_nonce', 'wpsp_actions_nonce' ); ?>
		<?php
	}

	public function extra_tablenav( $which ) {
		global $wpdb;

		if ( 'bottom' === $which ) {
			//$this->extra_tablenav_footer();
            return;
		}

		if ( 'top' === $which ) {
            $this->extra_tablenav_footer();
        }

        if ( 'top' !== $which ) {
			return;
        }

		echo '<div class="alignleft actions">';

		$users = $wpdb->get_results(
			'SELECT DISTINCT `user_id` FROM `' . $wpdb->activity_log . '`
				WHERE 1 = 1
				' . $this->_get_where_by_role() . '
				GROUP BY `user_id`
				ORDER BY `user_id`
				LIMIT 100
			;'
		);

		$this->data_types = $wpdb->get_col(
			'SELECT DISTINCT `object_type` FROM `' . $wpdb->activity_log . '`
				WHERE 1 = 1
				' . $this->_get_where_by_role() . '
				GROUP BY `object_type`
				ORDER BY `object_type`
			;'
		);

		// Make sure we get items for filter.
		if ( $users || $this->data_types ) {
			if ( ! isset( $_REQUEST['dateshow'] ) )
				$_REQUEST['dateshow'] = '';

			$date_options = array(
				'' => __( 'All Time', 'wp-site-prober' ),
				'today' => __( 'Today', 'wp-site-prober' ),
				'yesterday' => __( 'Yesterday', 'wp-site-prober' ),
				'week' => __( 'Week', 'wp-site-prober' ),
				'month' => __( 'Month', 'wp-site-prober' ),
			);
			echo '<select name="dateshow" id="hs-filter-date">';
			foreach ( $date_options as $key => $value )
				printf( '<option value="%s"%s>%s</option>', $key, selected( $_REQUEST['dateshow'], $key, false ), $value );
			echo '</select>';

			submit_button( __( 'Filter', 'wp-site-prober' ), 'button', 'wpsp-filter', false, array( 'id' => 'activity-query-submit' ) );
		}

		if ( $users ) {
			if ( ! isset( $_REQUEST['capshow'] ) )
				$_REQUEST['capshow'] = '';

			$output = array();
			foreach ( $this->_get_allow_caps() as $cap ) {
				$output[ $cap ] = __( ucwords( $cap ), 'wp-site-prober' );
			}

			if ( ! empty( $output ) ) {
				echo '<select name="capshow" id="hs-filter-capshow">';
				printf( '<option value="">%s</option>', __( 'All Roles', 'wp-site-prober' ) );
				foreach ( $output as $key => $value ) {
					printf( '<option value="%s"%s>%s</option>', $key, selected( $_REQUEST['capshow'], $key, false ), $value );
				}
				echo '</select>';
			}

			if ( ! isset( $_REQUEST['usershow'] ) )
				$_REQUEST['usershow'] = '';

			$output = array();
			foreach ( $users as $_user ) {
				if ( 0 === (int) $_user->user_id ) {
					$output[0] = __( 'N/A', 'wp-site-prober' );
					continue;
				}

				$user = get_user_by( 'id', $_user->user_id );
				if ( $user )
					$output[ $user->ID ] = $user->user_nicename;
			}

			if ( ! empty( $output ) ) {
				echo '<select name="usershow" id="hs-filter-usershow">';
				printf( '<option value="">%s</option>', __( 'All Users', 'wp-site-prober' ) );
				foreach ( $output as $key => $value ) {
					printf( '<option value="%s"%s>%s</option>', $key, selected( $_REQUEST['usershow'], $key, false ), $value );
				}
				echo '</select>';
			}
		}

		if ( $this->data_types ) {
			if ( ! isset( $_REQUEST['typeshow'] ) ) {
				$_REQUEST['typeshow'] = '';
			}

			$output = array();
			foreach ( $this->data_types as $object_type ) {
				$output[] = sprintf(
					'<option value="%s"%s>%s</option>',
					$object_type,
					selected( $_REQUEST['typeshow'], $object_type, false ),
					__( $object_type, 'wp-site-prober' )
				);
			}

			echo '<select name="typeshow" id="hs-filter-typeshow">';
			printf( '<option value="">%s</option>', __( 'All Topics', 'wp-site-prober' ) );
			echo implode( '', $output );
			echo '</select>';
		}

		$actions = $wpdb->get_results(
			'SELECT DISTINCT `action` FROM  `' . $wpdb->activity_log . '`
				WHERE 1 = 1
				' . $this->_get_where_by_role() . '
				GROUP BY `action`
				ORDER BY `action`
			;'
		);

		if ( $actions ) {
			if ( ! isset( $_REQUEST['showaction'] ) )
				$_REQUEST['showaction'] = '';

			$output = array();
			foreach ( $actions as $action )
				$output[] = sprintf( '<option value="%s"%s>%s</option>', $action->action, selected( $_REQUEST['showaction'], $action->action, false ), $this->get_action_label( $action->action ) );

			echo '<select name="showaction" id="hs-filter-showaction">';
			printf( '<option value="">%s</option>', __( 'All Actions', 'wp-site-prober' ) );
			echo implode( '', $output );
			echo '</select>';
		}

		$filters = array(
			'dateshow',
			'capshow',
			'usershow',
			'typeshow',
			'showaction',
		);

		foreach ( $filters as $filter ) {
			if ( ! empty( $_REQUEST[ $filter ] ) ) {
				echo '<a href="' . $this->get_filtered_link() . '" id="wpsp-reset-filter"><span class="dashicons dashicons-dismiss"></span>' . __( 'Reset Filters', 'wp-site-prober' ) . '</a>';
				break;
			}
		}

		echo '</div>';
	}
    public function prepare_items() {
		global $wpdb;

        $items_per_page = 20;
        $this->set_pagination_args(
			array(
				'total_items' => $this->total_items,
				'per_page'    => $items_per_page
			)
		);
        $this->_column_headers = 
            array( $this->get_columns(), 
                   $this->get_hidden_columns( ), 
                   $this->get_sortable_columns() );

        $table = $wpdb->wpsp_activity;
        $where = '';
        $this->items = $wpdb->get_results( 
            "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT 200", ARRAY_A );
        
        /*
        $this->_column_headers = 
            array( $this->get_columns(), 
                   get_hidden_columns( $this->screen ), 
                   $this->get_sortable_columns() );
        $this->items = $wpdb->get_results( $wpdb->prepare(
			'SELECT * FROM `' . $wpdb->activity_log . '`
				' . $where . '
					' . $this->_get_where_by_role() . '
					ORDER BY ' . $items_orderby . ' ' . $items_order . '
					LIMIT %d, %d;',
			$offset,
			$items_per_page
		) );
         */

        /*
		$this->set_pagination_args(
			array(
				'total_items' => $this->total_items,
				'per_page'    => 20
			)
		);

		$this->_column_headers = 
            array( $this->get_columns(), 
                $this->get_hidden_columns(), 
                $this->get_sortable_columns();
		$this->items = $this->table_data();
        */

    }

}