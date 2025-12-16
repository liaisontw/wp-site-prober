<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );


class LIAISIPR_List_Table_Implicit_Backup extends WP_List_Table {

	function __construct( /*$items*/$args = array() ) {
		//$this->items       = isset( $items['entries'] ) ? $items['entries'] : array();
		//$this->total_items = isset( $items['count'] ) ? intval( $items['count'] ) : 0;

		parent::__construct(
			array(
				'singular'  => esc_html__( 'custom_log_implicit', 'liaison-site-prober' ),
				'plural'    => esc_html__( 'custom_logs_implicit', 'liaison-site-prober' ),
				//'ajax'      => false
			)
		);
	}

	public function column_id( $item ) {
		return esc_html( $item['id'] );
	}

	public function column_message( $item ) {
		if ( 1 == $item['session_type'] ) {
			$session_url = esc_url( admin_url( 'admin.php?page=wpsp_site_prober_log_list&tab=implicit&session-select=' . intval( $item['id'] ) ) );
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
            'message'     => esc_html__( 'Message', 'liaison-site-prober' ),
            'plugin'      => esc_html__( 'Plugin', 'liaison-site-prober' ),
            'severity'    => esc_html__( 'Severity', 'liaison-site-prober' ),
            'created_at'  => esc_html__( 'Time', 'liaison-site-prober' ),
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

	private function table_data() {
		$data = array();
		if ( ! empty( $this->items ) ) {
			foreach ( $this->items as $item ) {
				$data[] = array(
					'id'           => isset( $item->the_ID ) ? $item->the_ID : '',
					'log_severity' => isset( $item->severity ) ? $item->severity : '',
					'log_msg'      => isset( $item->message ) ? $item->message : '',
					'log_date'     => isset( $item->the_date ) ? $item->the_date : '',
					'log_plugin'   => isset( $item->log_plugin ) ? $item->log_plugin : '',
					'session'      => isset( $item->session ) ? $item->session : 0
				);
			}
		}
		return $data;
	}

	public function prepare_items() {
		$data = $this->table_data();
		$this->items = $data;

		$this->_column_headers = 
            array( $this->get_columns(), 
                   $this->get_hidden_columns( ), 
                   $this->get_sortable_columns() );
				   
		$items_per_page = 20; 
		$total_items = 0; 		
        $this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page' => $items_per_page,
			'total_pages' => ceil( $total_items / $items_per_page ),
		) );   
	}
}
