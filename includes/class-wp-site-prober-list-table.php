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

    public function prepare_items() {
		global $wpdb;

        $this->set_pagination_args(
			array(
				'total_items' => $this->total_items,
				'per_page'    => 20
			)
		);
        $this->_column_headers = 
            array( $this->get_columns(), 
                   get_hidden_columns( $this->screen ), 
                   $this->get_sortable_columns() );
        $this->items = $wpdb->get_results( $wpdb->prepare(
			'SELECT * FROM `' . $wpdb->wpsp_activity . '`
					ORDER BY ' . $items_orderby . ' ' . $items_order . '
					LIMIT %d, %d;',
			$offset,
			$items_per_page
		) );
        
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