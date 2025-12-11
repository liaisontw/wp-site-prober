<?php
/**
 * @group actions
 */
class Test_LIAISIPR_Actions_Log extends WP_UnitTestCase {

    private $table;
    private $logger_stub;
    private $actions;

    public function setUp(): void {
        parent::setUp();

        global $wpdb;

        // 假 logger stub
        $this->table = $wpdb->prefix . 'liaisipr_logs_test';

        $this->logger_stub = $this->getMockBuilder(stdClass::class)
            ->addMethods(['get_table_name', 'get_plugin_dir'])
            ->getMock();

        $this->logger_stub->method('get_table_name')
            ->willReturn($this->table);

        $this->logger_stub->method('get_plugin_dir')
            ->willReturn('/tmp');

        // 建立資料表
        $wpdb->query("
            CREATE TABLE {$this->table} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20),
                action varchar(100),
                object_type varchar(100),
                object_id bigint(20),
                description text,
                ip varchar(45),
                user_agent text,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB;
        ");

        // 建立模擬登入使用者
        // Correct way: create and set current user
        $user_id = self::factory()->user->create([
            'role' => 'administrator'
        ]);
        wp_set_current_user($user_id);

        // 建立 action instance
        $this->actions = new LIAISIPR_Actions(
            $this->logger_stub,
            'liaison-site-prober',
            '1.0.0'
        );
    }

    public function tearDown(): void {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$this->table}");
        parent::tearDown();
    }


    /** @test */
    public function test_log_inserts_row_correctly() {
        global $wpdb;

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test UA';

        // 呼叫 log()
        $this->actions->log(
            'test_action',
            'test_object',
            10,
            'This is a test description'
        );

        // 查詢資料
        $row = $wpdb->get_row("SELECT * FROM {$this->table}", ARRAY_A);

        $this->assertNotEmpty($row, 'No data inserted by log()');
        $this->assertEquals('test_action', $row['action']);
        $this->assertEquals('test_object', $row['object_type']);
        $this->assertEquals(10, intval($row['object_id']));
        $this->assertEquals('This is a test description', $row['description']);
        $this->assertEquals('127.0.0.1', $row['ip']);
        $this->assertEquals('PHPUnit Test UA', $row['user_agent']);

        // user_id 應該是登入使用者
        $current_user = wp_get_current_user();
        $this->assertEquals($current_user->ID, intval($row['user_id']));
    }
}
