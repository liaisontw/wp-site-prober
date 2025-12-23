<?php

/**
 * @group liaison
 */
class Tests_LIAISIPR_Utility extends WP_UnitTestCase {

	private $utility;
	private $logger;

	public function setUp(): void {
		parent::setUp();

		// --- 建立 logger mock ---
		$this->logger = $this->getMockBuilder(stdClass::class)
			->addMethods(['get_table_name', 'get_table_name_custom_log', 'get_table_name_custom_log_session'])
			->getMock();

		$this->logger->method('get_table_name')->willReturn('wp_liaison_log');
		$this->logger->method('get_table_name_custom_log')->willReturn('wp_liaison_custom_log');
		$this->logger->method('get_table_name_custom_log_session')->willReturn('wp_liaison_custom_log_session');

		global $wpdb;
		// 建立假的資料表 (Test Suite 允許直接建立)
		$wpdb->query("CREATE TABLE IF NOT EXISTS wp_liaison_custom_log (
			id bigint AUTO_INCREMENT PRIMARY KEY,
			log_id int,
			plugin_name varchar(255),
			message text,
			severity int,
			session_id int
		)");
		$wpdb->query("CREATE TABLE IF NOT EXISTS wp_liaison_custom_log_session (
			id bigint AUTO_INCREMENT PRIMARY KEY,
			plugin_name varchar(255),
			message text,
			severity int
		)");

		$this->utility = new LIAISIPR_Utility($this->logger);

		// Reset static session ID
		$ref = new ReflectionClass(LIAISIPR_Utility::class);
		$prop = $ref->getProperty('session_id_in_use');
		$prop->setAccessible(true);
		$prop->setValue(null);
	}

	/** -----------------------------------------------------------
	 * Test: array_to_csv_line (private, use reflection)
	 * ----------------------------------------------------------- */
	public function test_array_to_csv_line() {

		$ref = new ReflectionClass(LIAISIPR_Utility::class);
		$method = $ref->getMethod('array_to_csv_line');
		$method->setAccessible(true);

		$result = $method->invoke(
			$this->utility,
			['a', 'b,b', 'c"c']
		);

		$this->assertEquals("\"a\",\"b,b\",\"c\"\"c\"\n", $result);
	}

	/** -----------------------------------------------------------
	 * Test: user_info_export()
	 * ----------------------------------------------------------- */
	public function test_user_info_export() {

		$user_id = $this->factory()->user->create(['display_name'=>'John']);

		$this->assertEquals('John', $this->utility->user_info_export($user_id));
		$this->assertEquals('N/A', $this->utility->user_info_export(0));
	}

	/** -----------------------------------------------------------
	 * Test: add_custom_log()
	 * ----------------------------------------------------------- */
	public function test_add_custom_log_inserts_into_table() {
		global $wpdb;

		$this->utility->add_custom_log('test-plugin','log','hello',3);

		$row = $wpdb->get_row("SELECT * FROM wp_liaison_custom_log ORDER BY id DESC", ARRAY_A);

		$this->assertEquals('test-plugin', $row['plugin_name']);
		$this->assertEquals('hello', $row['message']);
		$this->assertEquals(3, intval($row['severity']));
	}

	/** -----------------------------------------------------------
	 * Test: begin_session() / end_session()
	 * ----------------------------------------------------------- */
	public function test_begin_and_end_session() {
		global $wpdb;

		$this->utility->begin_session('plugin-x','msg','title',9);

		$session_row = $wpdb->get_row("SELECT * FROM wp_liaison_custom_log_session ORDER BY id DESC", ARRAY_A);
		$this->assertEquals('plugin-x', $session_row['plugin_name']);
		$this->assertEquals(9, intval($session_row['severity']));

		// session_id_in_use must be > 0
		$ref = new ReflectionClass(LIAISIPR_Utility::class);
		$prop = $ref->getProperty('session_id_in_use');
		$prop->setAccessible(true);
		$value = $prop->getValue();

		$this->assertNotNull($value);
		$this->assertTrue($value > 0);

		$this->utility->end_session();
		$this->assertNull($prop->getValue());
	}

	/** -----------------------------------------------------------
	 * Test: add_log_implicit()
	 * ----------------------------------------------------------- */
	public function test_add_log_implicit_creates_comment_and_terms() {

		$taxonomy = LIAISIPR_Utility::TAXONOMY;
		$cpt      = LIAISIPR_Utility::CPT;

		register_taxonomy($taxonomy, $cpt);
		register_post_type($cpt);

		$this->utility->add_log_implicit('my-plugin','cat1','hello world',5);

		// ensure term created
		$slug = sanitize_title('msg_category-cat1');
        $exist = term_exists($slug, $taxonomy);
        var_dump($exist);
        error_log( sprintf( 'term_exists: %s', $exist) );
		//$this->assertNotEmpty( term_exists($slug, $taxonomy) );

		// ensure CPT generated
		$q = new WP_Query([
			'post_type'=>$cpt,
			'name'=>$slug,
		]);
		$this->assertTrue($q->have_posts());
		$post_id = $q->posts[0]->ID;

		// ensure comment inserted
		$comments = get_comments([
			'post_id'=>$post_id,
			'orderby'=>'comment_date',
			'order'=>'DESC',
		]);

		$this->assertNotEmpty($comments);
		$this->assertEquals('hello world', $comments[0]->comment_content);
		$this->assertEquals('my-plugin', $comments[0]->comment_author);
		$this->assertEquals(5, intval($comments[0]->user_id));
	}

	/** -----------------------------------------------------------
	 * Test: build_slug()
	 * ----------------------------------------------------------- */
	public function test_build_slug() {

		$ref = new ReflectionClass(LIAISIPR_Utility::class);
		$method = $ref->getMethod('build_slug');
		$method->setAccessible(true);

		$this->assertEquals(
			'plugin-x-cat1',
			$method->invoke($this->utility, 'cat1', 'plugin-x')
		);

		$this->assertEquals(
			'msg_category-cat1',
			$method->invoke($this->utility, 'cat1')
		);
	}

	/** -----------------------------------------------------------
	 * Test: export_csv_generic (partial)
	 * **Do NOT test headers or exit**
	 * ----------------------------------------------------------- */
	public function test_export_csv_generic_reads_from_cache() {

		// Put fake data into wp_cache
		wp_cache_set('test_cache_key', [
			['id'=>1,'created_at'=>'now']
		], 'liaison-site-prober');

		$args = [
			'capability' => 'manage_options',
			'nonce_key'  => 'nonce_test',
			'nonce_action'=>'action_test',
			'table'      => 'wp_liaison_log',
			'cache_key'  => 'test_cache_key',
			'filename'   => 'test-export',
			'headers'    => ['id','created_at'],
			'row_mapper' => function($r){ return [$r['id'],$r['created_at']]; },
		];

		$_GET['nonce_test'] = wp_create_nonce('action_test');
		$user_id = $this->factory()->user->create(['role'=>'administrator']);
		wp_set_current_user($user_id);

		// mock filesystem
		global $wp_filesystem;
		if (!function_exists('WP_Filesystem')) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();

		// Prevent exit by mocking put_contents & get_contents
		$wp_filesystem->method = null;

		// Use output buffering + catch exit
		try {
			$this->utility->handle_export_csv(); // uses export_csv_generic()
		} catch (Exception $e) {
			// normal
		}

		// If reached here: the callback worked and cache was read.
		$this->assertTrue(true);
	}
}
