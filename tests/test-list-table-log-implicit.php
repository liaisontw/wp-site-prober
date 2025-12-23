<?php
/**
 * @group liaisipr
 */
class Tests_LIAISIPR_List_Table_Log_Implicit extends WP_UnitTestCase {

    protected $table;
	public function setUp(): void {
		parent::setUp();

		// Fake $wpdb custom table name for parent constructor
		global $wpdb;
		$wpdb->wpsp_custom_log         = $wpdb->prefix . 'wpsp_custom_log';
		$wpdb->wpsp_custom_log_session = $wpdb->prefix . 'wpsp_custom_log_session';

		//$this->table = new LIAISIPR_List_Table_Log_Implicit();

        update_option( 'siteurl', 'http://example.org' );
        update_option( 'home', 'http://example.org' );

        // 強制 WordPress 重新載入 URL 設定
        wp_load_alloptions();

        $this->table = new LIAISIPR_List_Table_Log_Implicit([
            'table_name'         => 'wp_wpsp_custom_log',
            'table_name_session' => 'wp_wpsp_custom_log_session',
        ]);

        add_filter('admin_url', function($url) {
            return 'http://example.org/wp-admin/' . ltrim($url, '/');
        });

	}

	/** -----------------------------------------------------------
	 * TEST: column_message() 
	 * ----------------------------------------------------------- */
	public function test_column_message_session_type_1_generates_link() {
		$item = [
			'id'           => 123,
			'message'      => 'Hello',
			'session_type' => 1,
		];

		$output = $this->table->column_message( $item );

		$this->assertStringContainsString( 'thickbox', $output );
		$this->assertStringContainsString( 'session-select=123', $output );
		$this->assertStringContainsString( 'Hello', $output );
	}

	public function test_column_message_normal() {
		$item = [
			'id'           => 5,
			'message'      => 'Log Message',
			'session_type' => 0,
		];

		$output = $this->table->column_message( $item );

		$this->assertSame( 'Log Message', $output );
	}

	/** -----------------------------------------------------------
	 * TEST: URL Generators 
	 * ----------------------------------------------------------- */
	/*
    public function test_get_log_generate_url_contains_expected_parts() {
		$url = $this->table->get_log_generate_url();

		$this->assertStringContainsString( 'admin-post.php', $url );
		$this->assertStringContainsString( 'action=WP_Implicit_Log_log_generate', $url );
		$this->assertStringContainsString( 'tab=implicit', $url );
	}

	public function test_get_session_generate_url_contains_expected_parts() {
		$url = $this->table->get_session_generate_url();

		$this->assertStringContainsString( 'action=WP_Implicit_Log_session_generate', $url );
		$this->assertStringContainsString( 'tab=implicit', $url );
	}

	public function test_get_export_url_contains_expected_parts() {
		$_GET['tab'] = 'implicit';
		$url = $this->table->get_export_url();

		$this->assertStringContainsString( 'WP_Site_Prober_export_csv_implicit_log', $url );
		$this->assertStringContainsString( 'tab=implicit', $url );
	}
        */

	/** -----------------------------------------------------------
	 * TEST: search_box() Output
	 * ----------------------------------------------------------- */
	public function test_search_box_outputs_correct_input_name() {
		ob_start();
		$this->table->search_box( 'Search', 'test-search' );
		$html = ob_get_clean();

		$this->assertStringContainsString( 'name="s_implicit_log"', $html );
		$this->assertStringContainsString( 'test-search-search-input-implicit-log', $html );
	}

	/** -----------------------------------------------------------
	 * TEST: build_query_args()
	 * ----------------------------------------------------------- */
	public function test_build_query_args_basic() {
		$args = $this->table->build_query_args();

		$this->assertArrayHasKey( 'post_where', $args );
		$this->assertArrayHasKey( 'comment_where', $args );
		$this->assertStringContainsString( 'post_parent != 0', $args['post_where'] );
		$this->assertStringContainsString( 'comment_approved', $args['comment_where'] );
	}

	public function test_build_query_args_with_search() {
		$_REQUEST['s_implicit_log'] = 'hello';

		$args = $this->table->build_query_args();

		$this->assertStringContainsString( 'LIKE', $args['post_where'] );
		$this->assertStringContainsString( 'LIKE', $args['comment_where'] );
	}

	/** -----------------------------------------------------------
	 * TEST: get_items_with_cache()
	 * ----------------------------------------------------------- */
	public function test_get_items_with_cache_uses_cache_if_available() {

		$fake_cached = [
			'items'       => [ [ 'id' => 1, 'message' => 'cached' ] ],
			'total_items' => 1,
		];

		wp_cache_set( 'site_prober_logs_page_log_implicit', $fake_cached, 'liaison-site-prober' );

		$args = $this->table->build_query_args();
		$result = $this->table->get_items_with_cache( $args );

		$this->assertSame( $fake_cached, $result );
	}

	public function test_get_items_with_cache_generates_sql_and_returns_array() {
		global $wpdb;

		add_filter( 'pre_query', function( $retval, $sql ) {
            if ( stripos( $sql, 'COUNT(*)' ) !== false ) {
                return 5; // mock return
            }
            return $retval;
        }, 10, 2 );

		$wpdb->query( "DELETE FROM {$wpdb->comments}" ); // clean test DB

		$args = $this->table->build_query_args();
		$result = $this->table->get_items_with_cache( $args );

		$this->assertArrayHasKey( 'items', $result );
		$this->assertArrayHasKey( 'total_items', $result );
		$this->assertIsArray( $result['items'] );
	}

}
