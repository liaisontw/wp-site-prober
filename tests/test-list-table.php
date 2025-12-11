<?php
// function menu_page_url( $menu_slug, $echo = true ) {
//     return 'admin.php?page=' . $menu_slug;
// }


/**
 * @group listtable
 */
class Test_LIAISIPR_List_Table extends WP_UnitTestCase {

    private $table;
    private $wpdb;

    public function setUp(): void {
        parent::setUp();

        global $wpdb;
        $this->wpdb  = $wpdb;
        $this->table = $wpdb->prefix . 'liaisipr_test_logs';

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

        $wpdb->insert( $this->table, [
            'created_at'  => '2025-01-01 12:00:00',
            'user_id'     => 1,
            'ip'          => '127.0.0.1',
            'action'      => 'login',
            'object_type' => 'user',
            'description' => 'User logged in'
        ]);

        wp_cache_flush();
    }

    public function tearDown(): void {
        $this->wpdb->query("DROP TABLE IF EXISTS {$this->table}");
        parent::tearDown();
    }

    public function test_prepare_items_loads_rows() {

        require_once __DIR__ . '/../includes/class-liaison-site-prober-list-table.php';

        // 建立 instance
        $list_table = new LIAISIPR_List_Table([
            'table_name' => $this->table
        ]);

        $list_table->table_name = $this->table; // override 真正 table name

        $cache_key   = 'site_prober_logs_page_';
		$cache_group = 'liaison-site-prober';
        wp_cache_delete( $cache_key, $cache_group );

        $list_table->prepare_items();

        $this->assertNotEmpty( $list_table->items );
        $this->assertEquals( 'login', $list_table->items[0]['action'] );
    }


    /*
    public function test_get_filtered_link() {

        require_once __DIR__ . '/../includes/class-liaison-site-prober-list-table.php';

        // 建立 instance
        $list_table = new LIAISIPR_List_Table([
            'table_name' => $this->table
        ]);
       
        //do_action('admin_menu');
        // 模擬 admin page URL
        $_GET['page'] = 'wpsp_site_prober_log_list';

        $url = $list_table->get_filtered_link('usershow', 5);
        //$url = 'page=wpsp_site_prober_log_list&usershow=5';

        //$this->assertStringContainsString('usershow=5', $url);
        $this->assertStringContainsString('page=wpsp_site_prober_log_list', $url);
    }
    */


    public function test_search_box_outputs_html() {

        require_once __DIR__ . '/../includes/class-liaison-site-prober-list-table.php';

        // 建立 instance
        $list_table = new LIAISIPR_List_Table([
            'table_name' => $this->table
        ]);

        ob_start();
        $list_table->search_box('Search', 'wpsp-search');
        $output = ob_get_clean();

        $this->assertStringContainsString('type="search"', $output);
        $this->assertStringContainsString('name="s"', $output);
    }
}
