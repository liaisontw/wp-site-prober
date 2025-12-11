<?php

/**
 * @group listtable
 */
class Test_LIAISIPR_List_Table_Additional extends WP_UnitTestCase {

    private $table;
    private $wpdb;

    public function setUp(): void {
        parent::setUp();

        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'liaisipr_test_logs2';

        $wpdb->query("
            CREATE TABLE {$this->table} (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                created_at datetime NOT NULL,
                user_id bigint(20),
                ip varchar(100),
                action varchar(255),
                object_type varchar(255),
                description text,
                PRIMARY KEY  (id)
            ) ENGINE=InnoDB;
        ");

        // Insert multiple rows for pagination & filtering tests
        for ( $i = 1; $i <= 30; $i++ ) {
            $wpdb->insert( $this->table, [
                'created_at'  => '2025-01-01 12:00:' . sprintf('%02d', $i),
                'user_id'     => ($i % 2) + 1,  // users 1 and 2
                'ip'          => '127.0.0.' . $i,
                'action'      => ($i % 2 == 0 ? 'login' : 'logout'),
                'object_type' => 'user',
                'description' => 'Test row ' . $i
            ]);
        }

        wp_cache_flush();
    }

    public function tearDown(): void {
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->table}");
        parent::tearDown();
    }

    private function get_list_table() {
        require_once __DIR__ . '/../includes/class-liaison-site-prober-list-table.php';
        $table = new LIAISIPR_List_Table();
        $table->table_name = $this->table;
        return $table;
    }

    /** -------------------------------------------
     * TEST: Column definitions
     * ------------------------------------------- */
    public function test_get_columns_structure() {

        $list_table = $this->get_list_table();
        $columns = $list_table->get_columns();

        // 核心欄位要存在
        $this->assertArrayHasKey('created_at',  $columns);
        $this->assertArrayHasKey('user_id',     $columns);
        $this->assertArrayHasKey('ip',          $columns);
        $this->assertArrayHasKey('action',      $columns);
        $this->assertArrayHasKey('object_type', $columns);
        $this->assertArrayHasKey('description', $columns);
    }

    /** -------------------------------------------
     * TEST: Sortable columns
     * ------------------------------------------- */
    public function test_sortable_columns() {

        $list_table = $this->get_list_table();
        $sortable = $list_table->get_sortable_columns();

        $this->assertArrayHasKey('created_at', $sortable);
        $this->assertArrayHasKey('user_id', $sortable);
        $this->assertArrayHasKey('ip', $sortable);
    }

    /** -------------------------------------------
     * TEST: Pagination works (20 per page)
     * ------------------------------------------- */
    public function test_pagination_works() {

        $list_table = $this->get_list_table();
        $list_table->prepare_items();

        $pagination = $list_table->get_pagination_arg('total_pages');

        // 30 rows, 20 per page → should be 2 pages
        $this->assertEquals(2, $pagination);
    }

    /** -------------------------------------------
     * TEST: Search filter applies
     * ------------------------------------------- */
    public function test_search_filter_applies() {

        $_REQUEST['s'] = 'logout';

        $list_table = $this->get_list_table();
        $list_table->prepare_items();

        foreach ( $list_table->items as $row ) {
            $this->assertStringContainsString('logout', $row['action']);
        }
    }

    /** -------------------------------------------
     * TEST: User filter applies
     * ------------------------------------------- */
    public function test_user_filter_applies() {

        $_REQUEST['usershow'] = 2;

        $list_table = $this->get_list_table();
        $list_table->prepare_items();

        foreach ( $list_table->items as $row ) {
            $this->assertEquals(2, intval($row['user_id']));
        }
    }

    /** -------------------------------------------
     * TEST: Hidden columns returns empty array
     * ------------------------------------------- */
    public function test_hidden_columns_is_empty() {

        $list_table = $this->get_list_table();
        $this->assertEquals([], $list_table->get_hidden_columns());
    }
}
