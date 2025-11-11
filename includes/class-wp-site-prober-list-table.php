<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );


class wp_site_prober_List_Table extends WP_List_Table {

    protected $data_types = array();

    public function __construct( $args = array() ) {
        $this->total_items = 10;

		parent::__construct(
			array(
				'singular'  => esc_html__( 'activity', 'wp-site-prober' ),
				'plural'    => esc_html__( 'activities', 'wp-site-prober' ),
			)
		);
    }

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
	    ?>
			<form method="get">
				<input type="hidden" name="page" value="wp-site-prober" />
				<a class="button" href="<?php echo esc_url( admin_url( 'admin-post.php?action=WP_Site_Prober_export_csv' ) ); ?>"><?php esc_html_e( 'Export CSV', 'wp-site-prober' ); ?></a>
			</form>
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
			'SELECT DISTINCT `user_id` FROM `' . $wpdb->wpsp_activity . '`
				WHERE 1 = 1
				GROUP BY `user_id`
				ORDER BY `user_id`
				LIMIT 100
			;'
		);

		$this->data_types = $wpdb->get_col(
			'SELECT DISTINCT `object_type` FROM `' . $wpdb->wpsp_activity . '`
				WHERE 1 = 1
				GROUP BY `object_type`
				ORDER BY `object_type`
			;'
		);

		// Make sure we get items for filter.
		if ( $users || $this->data_types ) {
			submit_button( __( 'Filter', 'wp-site-prober' ), 'button', 'wpsp-filter', false, array() );
		}

		if ( $users ) {
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
			printf( '<option value="">%s</option>', __( 'All Objects', 'wp-site-prober' ) );
			echo implode( '', $output );
			echo '</select>';
		}

		$filters = array(
			'usershow',
            'typeshow',
		);

		foreach ( $filters as $filter ) {
			if ( ! empty( $_REQUEST[ $filter ] ) ) {
				echo '<a href="' . $this->get_filtered_link() . '" id="wpsp-reset-filter"><span class="dashicons dashicons-dismiss"></span>' . __( 'Reset Filters', 'wp-site-prober' ) . '</a>';
				break;
			}
		}

		echo '</div>';
	}

    public function search_box( $text, $input_id ) {
		$search_data = isset( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : '';

		$input_id = $input_id . '-search-input';
		?>
		<p class="search-box">
            <label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
			<input type="search" id="<?php echo $input_id ?>" name="s" value="<?php echo esc_attr( $search_data ); ?>" placeholder="<?php esc_attr_e( 'Search actions, descriptions, IP', 'wp-site-prober' ); ?>"/>
			<?php submit_button( $text, 'button', false, false, array('id' => 'search-submit') ); ?>
		</p>
	<?php
	}

	public function display_tablenav( $which ) {
		if ( 'top' == $which ) {
			$this->search_box( __( 'Search', 'wp-site-prober' ), 'wpsp-search' );
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
    public function prepare_items() {
		global $wpdb;

        $items_per_page = 10;

        $this->_column_headers = 
            array( $this->get_columns(), 
                   $this->get_hidden_columns( ), 
                   $this->get_sortable_columns() );

        $table = $wpdb->wpsp_activity;
        
        $where = ' WHERE 1 = 1';

        
		if ( ! empty( $_REQUEST['typeshow'] ) ) {
			$where .= $wpdb->prepare( ' AND `object_type` = %s', sanitize_text_field( $_REQUEST['typeshow'] ) );
		}

        
		if ( isset( $_REQUEST['usershow'] ) && '' !== $_REQUEST['usershow'] ) {
			$where .= $wpdb->prepare( ' AND `user_id` = %d', sanitize_text_field( $_REQUEST['usershow'] ) );
		}

        //$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
        $search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
		
		if ( $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			//$where = $wpdb->prepare( " WHERE action LIKE %s OR description LIKE %s OR ip LIKE %s ", $like, $like, $like );
            $where .= $wpdb->prepare( ' AND (`action` LIKE %s OR `description` LIKE %s OR `ip` LIKE %s)', $like, $like, $like );
		}

        
        $this->items = $wpdb->get_results( 
            "SELECT * FROM {$table} {$where} ORDER BY created_at DESC LIMIT 200", ARRAY_A );
        
        $total_items = count( $this->items );
        
        $this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page' => $items_per_page,
			'total_pages' => ceil( $total_items / $items_per_page ),
		) );
        
    }

}