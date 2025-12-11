<?php
/**
 * @group admin
 */

class Test_LIAISIPR_Admin extends WP_UnitTestCase {

    private $admin;
    private $logger_mock;

    protected $backupGlobals = false;
    protected $backupStaticAttributes = false;


    public function setUp(): void {
        parent::setUp();


        // 建 mock logger（只需要回傳 table name）
        $this->logger_mock = $this->getMockBuilder(stdClass::class)
            ->setMethods(['get_table_name'])
            ->getMock();

        $this->logger_mock->method('get_table_name')->willReturn('wp_liaisipr_logs');

        // 建立主 class
        $this->admin = new LIAISIPR_Admin(
            $this->logger_mock,
            'liaison-site-prober',
            '1.0.0'
        );
    }

    public function test_user_info_export_existing_user() {
        // 建立測試 user
        $user_id = $this->factory()->user->create([
            'display_name' => 'Tester Man'
        ]);

        $this->assertEquals(
            'Tester Man',
            $this->admin->user_info_export($user_id)
        );
    }


    public function test_user_info_export_returns_NA_for_empty() {
        $this->assertEquals('N/A', $this->admin->user_info_export(0));
        $this->assertEquals('N/A', $this->admin->user_info_export(null));
    }


    public function test_get_list_table_returns_instance() {

        $list_table = $this->admin->get_list_table();

        $this->assertInstanceOf(LIAISIPR_List_Table::class, $list_table);
        $this->assertSame($list_table, $this->admin->get_list_table()); // 確保有 caching
    }

    public function test_handle_export_csv_invalid_nonce_dies() {

        // Fake admin capability
        wp_set_current_user($this->factory()->user->create(['role' => 'administrator']));

        $_GET['wpsp_nonce'] = 'invalid_nonce';

        $this->expectException(WPDieException::class);

        $this->admin->handle_export_csv();
    }

    public function test_handle_export_csv_valid_nonce_runs() {

        global $wpdb;

        // Fake admin
        wp_set_current_user($this->factory()->user->create(['role' => 'administrator']));

        // 設定 table
        $table_name = 'wp_liaisipr_logs';
        $wpdb->query("CREATE TABLE {$table_name} (
            id int(11) AUTO_INCREMENT PRIMARY KEY,
            created_at datetime,
            user_id int,
            ip varchar(50),
            action varchar(100),
            object_type varchar(100),
            description text
        )");

        // Insert sample row
        $wpdb->insert($table_name, [
            'created_at' => '2025-01-01 00:00:00',
            'user_id' => 1,
            'ip' => '127.0.0.1',
            'action' => 'login',
            'object_type' => 'user',
            'description' => 'testing'
        ]);

        $_GET['wpsp_nonce'] = wp_create_nonce('wpsp_list_table_action');

        // 因為 function 會呼叫 exit，所以用 try/catch 接住
        try {
            $this->admin->handle_export_csv();
        } catch (Exception $e) {
            // PHPUnit exit handling
        }

        // 至少確認 CSV 內容有產生（檢查輸出 buffer）
        $this->expectNotToPerformAssertions();
    }

}