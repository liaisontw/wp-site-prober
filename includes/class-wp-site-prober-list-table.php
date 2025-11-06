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

    public function column_created_at( $item ) {
		return esc_html( $item['created_at'] );
	}

    public function column_user_id( $item ) {
		return esc_html( $item['user_id'] );
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