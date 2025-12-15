<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );


class LIAISIPR_List_Table_Implicit extends WP_List_Table {

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

	public function column_log_msg( $item ) {
		if ( 1 == $item['session'] ) {
			$session_url = esc_url( admin_url( 'admin.php?page=logger_catcher_log_list&session-select=' . intval( $item['id'] ) ) );
			$message = "<a href='{$session_url}' class='thickbox'>" . esc_html( $item['log_msg'] ) . "</a>";
		} else {
			$message = esc_html( $item['log_msg'] );
		}
		return $message;
	}

	public function column_log_date( $item ) {
		return esc_html( $item['log_date'] );
	}

	public function column_log_plugin( $item ) {
		return esc_html( $item['log_plugin'] );
	}

	public function column_log_severity( $item ) {
		return esc_html( $item['log_severity'] );
	}

	public function get_columns() {
		$columns = array(
			'log_msg'      => esc_html__( 'Log Message', 'log-catcher' ),
			'log_severity' => esc_html__( 'Severity', 'log-catcher' ),
			'log_plugin'   => esc_html__( 'Plugin', 'log-catcher' ),
			'log_date'     => esc_html__( 'Date', 'log-catcher' ),
		);
		return $columns;
	}

	public function get_hidden_columns() {
		return array();
	}

	public function get_sortable_columns() {
		return array(
			'log_severity' => array( 'log_severity', false ),
			'log_plugin'   => array( 'log_plugin', false ),
			'log_date'     => array( 'log_date', false ),
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
		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$data = $this->table_data();

		$this->set_pagination_args(
			array(
				'total_items' => $this->total_items,
				'per_page'    => 20
			)
		);

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items = $data;
	}
}
