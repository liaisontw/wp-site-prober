<?php

if ( ! class_exists( 'LIAISIPR_Activator' ) )
	require_once dirname( __DIR__ ) . 'includes/class-liaison-site-prober-activator.php';

/**
 * REST API Permission Test: CVE-2026-3569 防禦測試
 */
class Liaison_Site_Prober_REST_Test extends WP_UnitTestCase {

    protected $server;
    protected $admin_id;

    public function setUp(): void {
        parent::setUp();

        LIAISIPR_Activator::activate();
        // 初始化 REST Server
        global $wp_rest_server;
        $this->server = $wp_rest_server = new WP_REST_Server();
        do_action( 'rest_api_init' );

        // 建立一個管理員帳號供測試使用
        $this->admin_id = $this->factory->user->create( array(
            'role' => 'administrator',
        ) );
    }

    /**
     * 測試 1：未登入使用者不應具備存取權限 (關鍵安全測試)
     */
    public function test_get_logs_without_permission() {
        // 確保目前是未登入狀態
        wp_set_current_user( 0 );

        $request = new WP_REST_Request( 'GET', '/site-prober/v1/logs' );
        $response = $this->server->dispatch( $request );

        // 預期狀態碼應為 401 (Unauthorized) 或 403 (Forbidden)
        $this->assertEquals( 401, $response->get_status(), '安全性漏洞：未登入使用者不應存取日誌 API' );
    }

    /**
     * 測試 2：具備管理權限的使用者應可正常讀取
     */
    public function test_get_logs_with_admin_permission() {
        // 模擬管理員登入
        wp_set_current_user( $this->admin_id );

        $request = new WP_REST_Request( 'GET', '/site-prober/v1/logs' );
        $response = $this->server->dispatch( $request );

        // 預期狀態碼應為 200 (OK)
        $this->assertEquals( 200, $response->get_status() );
    }

    public function tearDown(): void {
        parent::tearDown();
        global $wp_rest_server;
        $wp_rest_server = null;
    }
}

