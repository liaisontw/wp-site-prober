<?php

/**
 * @group custom-log
 */
class Tests_LIAISIPR_List_Table_Custom_Log extends WP_UnitTestCase {

	protected $table;
	protected $wpdb;

	public function setUp(): void {
		parent::setUp();
		global $wpdb;
		$this->wpdb = $wpdb;

		// 建立假 table（WP test suite 使用 $wpdb->prefix = wp_）
		$wpdb->query("
			CREATE TABLE {$wpdb->prefix}custom_log (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				log_id bigint(20) unsigned NULL,
				plugin_name varchar(255),
				message longtext,
				severity int,
				session_type int,
				session_id int,
				created_at datetime DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (id)
			) ENGINE=InnoDB;
		");

		$wpdb->query("
			CREATE TABLE {$wpdb->prefix}custom_log_session (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				plugin_name varchar(255),
				message longtext,
				severity int,
				created_at datetime DEFAULT CURRENT_TIMESTAMP,
				session_type int,
				PRIMARY KEY (id)
			) ENGINE=InnoDB;
		");

		$this->table = new LIAISIPR_List_Table_Custom_Log([
			'table_name'         => $wpdb->prefix . 'custom_log',
			'table_name_session' => $wpdb->prefix . 'custom_log_session',
		]);
	}

	public function tearDown(): void {
		global $wpdb;
		$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}custom_log");
		$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}custom_log_session");
		parent::tearDown();
	}

	/** -------------------------------------------------------------
	 *  TEST 1: get_columns()
	 * --------------------------------------------------------------*/
	public function test_get_columns() {
		$columns = $this->table->get_columns();

		$this->assertArrayHasKey('message', $columns);
		$this->assertArrayHasKey('plugin', $columns);
		$this->assertArrayHasKey('severity', $columns);
		$this->assertArrayHasKey('created_at', $columns);
	}

	/** -------------------------------------------------------------
	 *  TEST 2: get_sortable_columns()
	 * --------------------------------------------------------------*/
	public function test_get_sortable_columns() {
		$sortable = $this->table->get_sortable_columns();

		$this->assertArrayHasKey('plugin', $sortable);
		$this->assertArrayHasKey('severity', $sortable);
		$this->assertArrayHasKey('created_at', $sortable);
	}

	/** -------------------------------------------------------------
	 *  TEST 3: build_query_args() (simulate GET/REQUEST)
	 * --------------------------------------------------------------*/
	public function test_build_query_args_applies_filters() {

		$_REQUEST['pluginshow']   = 'test-plugin';
		$_REQUEST['severityshow'] = '3';
		$_REQUEST['s_custom_log'] = 'hello';

		$args = $this->table->build_query_args();

		$this->assertStringContainsString("plugin_name =", $args['where']);
		$this->assertStringContainsString("severity =", $args['where']);
		$this->assertStringContainsString("LIKE", $args['where']);

		unset($_REQUEST);
	}

	/** -------------------------------------------------------------
	 *  TEST 4: Caching + get_items_with_cache()
	 * --------------------------------------------------------------*/
	public function test_get_items_with_cache() {

		// 插入假資料
		$this->wpdb->insert(
			$this->wpdb->prefix . 'custom_log',
			[
				'plugin_name'  => 'p1',
				'message'      => 'm1',
				'severity'     => 1,
				'session_type' => 0
			]
		);

		$args = [
			'where'   => 'WHERE 1=1',
			'orderby' => 'created_at',
			'order'   => 'DESC',
			'offset'  => 0,
			'limit'   => 20
		];

		// 第一次會執行 DB
		$result1 = $this->table->get_items_with_cache($args);
		$this->assertNotEmpty($result1['items']);

		// 放入 cache 後，mock 破壞 DB，看是否仍不受影響
		$this->wpdb->query("TRUNCATE TABLE {$this->wpdb->prefix}custom_log");

		// 第二次調用 → 從 cache 回傳（不會空）
		$result2 = $this->table->get_items_with_cache($args);

		$this->assertEquals($result1['total_items'], $result2['total_items']);
		$this->assertNotEmpty($result2['items']);
	}

	/** -------------------------------------------------------------
	 *  TEST 5: prepare_items() end-to-end
	 * --------------------------------------------------------------*/
	public function test_prepare_items() {

		// 插入 3 筆資料
		for ($i=1; $i<=3; $i++) {
			$this->wpdb->insert(
				$this->wpdb->prefix . 'custom_log',
				[
					'plugin_name'  => 'plugin-' . $i,
					'message'      => 'hello ' . $i,
					'severity'     => $i,
					'session_type' => 0
				]
			);
		}

		$this->table->prepare_items();

		$this->assertNotEmpty($this->table->items);
		$this->assertEquals(3, $this->table->get_pagination_arg('total_items'));
	}

	/** -------------------------------------------------------------
	 *  TEST 6: URL builders contain nonce and actions
	 * --------------------------------------------------------------*/
	public function test_get_export_url() {
		$_GET['tab'] = 'custom';

		$url = $this->invokePrivate($this->table, 'get_export_url');

		$this->assertStringContainsString('admin-post.php', $url);
		$this->assertStringContainsString('wpsp_nonce_custom_log=', $url);
	}

	/** -------------------------------------------------------------
	 * Helper: 呼叫 private/protected 方法
	 * --------------------------------------------------------------*/
	protected function invokePrivate($obj, $method, array $args = []) {
		$r = new ReflectionMethod($obj, $method);
		$r->setAccessible(true);
		return $r->invokeArgs($obj, $args);
	}
}
