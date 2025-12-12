<?php
/**
 * @group customlog
 */
class Test_LIAISIPR_List_Table_Custom_Log extends WP_UnitTestCase {

    private $table;
    private $table_session;
    private $wpdb;

    public function setUp(): void {
        parent::setUp();

        global $wpdb;
        $this->wpdb = $wpdb;

        $this->table         = $wpdb->prefix . 'wpsp_custom_log';
        $this->table_session = $wpdb->prefix . 'wpsp_custom_log_session';

        // ---- 建立主 table ----
        $wpdb->query("
            CREATE TABLE {$this->table} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                plugin_name varchar(255),
                message text,
                severity int,
                created_at datetime,
                session_type int,
                session_id int,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB;
        ");

        // ---- 建立 session table ----
        $wpdb->query("
            CREATE TABLE {$this->table_session} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                plugin_name varchar(255),
                message text,
                severity int,
                created_at datetime,
                session_type int,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB;
        ");

        // ---- Insert 測試資料 ----
        $wpdb->insert($this->table, [
            'plugin_name'  => 'test-plugin',
            'message'      => 'First Message',
            'severity'     => 1,
            'created_at'   => '2025-01-01 00:00:00',
            'session_type' => 0,
        ]);

        $wpdb->insert($this->table_session, [
            'plugin_name'  => 'session-plugin',
            'message'      => 'Session Message',
            'severity'     => 2,
            'created_at'   => '2025-01-01 00:00:01',
            'session_type' => 1,
        ]);

        wp_cache_flush();
    }

    public function tearDown(): void {
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->table}");
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->table_session}");
        parent::tearDown();
    }

    private function get_instance() {
        require_once __DIR__ . '/../includes/class-liaison-site-prober-list-table-custom-log.php';
        // 建立 instance
        $list_table = new LIAISIPR_List_Table_Custom_Log([
            'table_name' => $this->table,
            'table_name_session' => $this->table_session
        ]);

        // override 真正 table name
        $list_table->table_name = $this->table; 
        $list_table->table_name_session = $this->table_session;
        return $list_table;
    }

    /** ----------------------------------------------------
     *  Test: get_columns()
     * ---------------------------------------------------- */
    public function test_get_columns() {
        $table = $this->get_instance();
        $cols = $table->get_columns();

        $this->assertArrayHasKey('message', $cols);
        $this->assertArrayHasKey('plugin', $cols);
        $this->assertArrayHasKey('severity', $cols);
        $this->assertArrayHasKey('created_at', $cols);
    }

    /** ----------------------------------------------------
     *  Test: column_message()
     * ---------------------------------------------------- */
    public function test_column_message_normal_row() {
        $table = $this->get_instance();

        $item = [
            'message'      => 'Hello world',
            'session_type' => 0,
            'id'           => 1,
        ];

        $html = $table->column_message($item);

        $this->assertStringContainsString('Hello world', $html);
        $this->assertStringNotContainsString('<a', $html);
    }

    public function test_column_message_session() {
        $table = $this->get_instance();

        $item = [
            'message'      => 'Session Msg',
            'session_type' => 1,
            'id'           => 10,
        ];

        $html = $table->column_message($item);

        $this->assertStringContainsString('<a href=', $html);
        $this->assertStringContainsString('Session Msg', $html);
    }

    /** ----------------------------------------------------
     *  Test: column_plugin(), column_severity(), created_at
     * ---------------------------------------------------- */
    public function test_column_plugin() {
        $table = $this->get_instance();
        $html  = $table->column_plugin(['plugin_name' => 'abc']);
        $this->assertEquals('abc', $html);
    }

    public function test_column_severity() {
        $table = $this->get_instance();
        $html  = $table->column_severity(['severity' => '5']);
        $this->assertEquals('5', $html);
    }

    public function test_column_created_at() {
        $table = $this->get_instance();
        $html  = $table->column_created_at(['created_at' => '2025-01-01']);
        $this->assertEquals('2025-01-01', $html);
    }

    /** ----------------------------------------------------
     *  Test: search_box()
     * ---------------------------------------------------- */
    public function test_search_box_displays_html() {
        $table = $this->get_instance();
        ob_start();
        $table->search_box('Search', 'wpsp-search');
        $html = ob_get_clean();

        $this->assertStringContainsString('type="search"', $html);
        $this->assertStringContainsString('name="s_custom_log"', $html);
    }

    /** ----------------------------------------------------
     *  Test: prepare_items()
     * ---------------------------------------------------- */
    public function test_prepare_items_loads_data() {
        $table = $this->get_instance();
        $table->prepare_items();

        $this->assertNotEmpty($table->items);

        // 依照 LIMIT 20，只有 2 筆應該都讀到
        $messages = wp_list_pluck($table->items, 'message');
   
        $this->assertContains('First Message', $messages);
        $this->assertContains('Session Message', $messages);
    }

    /** ----------------------------------------------------
     *  Test: log_plugin_select()
     * ---------------------------------------------------- */
    public function test_log_plugin_select() {

        // Fake plugin name in REQUEST
        $table = $this->get_instance();

        ob_start();
        $table->log_plugin_select('test-plugin');
        $html = ob_get_clean();

        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('<option', $html);
    }

    /** ----------------------------------------------------
     *  Test: extra_tablenav() (top)
     * ---------------------------------------------------- */
    public function test_extra_tablenav_outputs_html_top() {
        $table = $this->get_instance();

        ob_start();
        $table->extra_tablenav('top');
        $html = ob_get_clean();

        $this->assertStringContainsString('alignleft actions', $html);
    }

    /** ----------------------------------------------------
     *  Test: extra_tablenav() (bottom)
     * ---------------------------------------------------- */
    public function test_extra_tablenav_outputs_html_bottom() {
        $table = $this->get_instance();

        ob_start();
        $table->extra_tablenav('bottom');
        $html = ob_get_clean();

        $this->assertStringContainsString('Custom Log Generate', $html);
        $this->assertStringContainsString('Session Generate', $html);
    }
}
