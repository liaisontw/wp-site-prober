<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LIAISIPR_Utility' ) )
	require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-liaison-site-prober-utility.php';

class LIAISIPR_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;


	protected $logger;
	protected $table;
	protected $wpsp_list_table = null;
	
	protected $table_custom_log;
	protected $table_custom_log_session;
	protected $wpsp_custom_log = null;
	protected $wpsp_log_implicit = null;

	protected $wpsp_utility = null;

	//protected static $session_id_in_use = null;

	public function __construct( $logger, $plugin_name, $version ) {
		$this->logger = $logger;
		$this->plugin_name = $plugin_name;
        $this->version = $version;
		$this->table = $this->logger->get_table_name();
		$this->wpsp_utility = new LIAISIPR_Utility( $logger );

        add_action( 'admin_menu', array($this, 'admin_menu'));
		add_action( 'wp_ajax_plugin_select',     array( $this, 'ajax_plugin_select' ) );

		add_action( 'custom_log_add'  , array( $this->wpsp_utility, 'add_custom_log' ), 10, 4 );	
		add_action( 'custom_log_clean', array( $this, 'clean_custom_logs' ) );
		add_action( 'custom_log_session_begin', array( $this->wpsp_utility, 'begin_session' ), 10, 4 );
		add_action( 'custom_log_session_end', array( $this->wpsp_utility, 'end_session' ) );

		add_action( 'wpsp_implicit_log_add'  , array( $this->wpsp_utility, 'add_log_implicit' ), 10, 4 );	
		add_action( 'wpsp_implicit_log_clean', array( $this->wpsp_utility, 'clean_logs_implicit' ) );
		add_action( 'wpsp_implicit_log_session_begin', array( $this->wpsp_utility, 'begin_session_implicit' ), 10, 4 );
		add_action( 'wpsp_implicit_log_session_end', array( $this->wpsp_utility, 'end_session_implicit' ) );
		
		// handle csv export
		add_action( 'admin_post_WP_Site_Prober_export_csv', [ $this->wpsp_utility, 'handle_export_csv' ] );
		// handle csv export: custom log
		add_action( 'admin_post_WP_Site_Prober_export_csv_custom_log', [ $this->wpsp_utility, 'handle_export_csv_custom_log' ] );
		// handle csv export: implicit log
		add_action( 'admin_post_WP_Site_Prober_export_csv_implicit_log', [ $this->wpsp_utility, 'handle_export_csv_implicit_log' ] );

		// Generate custom log for testing
		add_action( 'admin_post_WP_Custom_Log_custom_log_generate', [ $this->wpsp_utility, 'handle_custom_log_generate' ] );
		add_action( 'admin_post_WP_Custom_Log_session_generate', [ $this->wpsp_utility, 'handle_session_generate' ] );
		// Generate implicit log for testing
		add_action( 'admin_post_WP_Implicit_Log_log_generate', [ $this->wpsp_utility, 'handle_implicit_log_generate' ] );
		add_action( 'admin_post_WP_Implicit_Log_session_generate', [ $this->wpsp_utility, 'handle_implicit_session_generate' ] );
	}

	    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in hidden_Stuff_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/liaison-site-prober-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in hidden_Stuff_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The hidden_Stuff_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/liaison-site-prober-admin.js', array( 'jquery' ), $this->version, false);
    }

    /**
     * hidden_stuff_menu_settings function.
     * Add a menu item
     * @access public
     * @return void
     */

	public function admin_menu() {
		add_menu_page(
			'Site Prober',
			'Site Prober',
			'update_core',
			'wpsp_site_prober_log_list',
			array($this, 'render_page_tabs'),
			//array($this, 'render_page_list_table'),
			'dashicons-video-alt2',
			80
		);
	}	

	function ajax_plugin_select() {
		$plugin_select = isset( $_POST['plugin_select'] ) ? $_POST['plugin_select'] : '';
		error_log( sprintf('plugin_select : %s', $plugin_select) );		
		$this->get_list_table_custom_log()->log_plugin_select( sanitize_text_field( $plugin_select ) );
		exit;
	}

	public function render_page_tabs() {
		$active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'log';
		require_once( trailingslashit( dirname( __FILE__ ) ) . 'partials/liaison-site-prober-admin-display.php' );	
	}

	public function user_info_export( $user_id ) {
		$msg = '';

		if ( ! empty( $user_id ) && 0 !== (int) $user_id ) {
			$user = get_user_by( 'id', $user_id );
			if ( $user instanceof WP_User && 0 !== $user->ID ) {
				$msg = $user->display_name;		
			}
		} else {
			$msg = 'N/A';
		}

		return $msg;
	}

	public function get_list_table() {
		if ( is_null( $this->wpsp_list_table ) ) {
			$this->wpsp_list_table = new LIAISIPR_List_Table( );
		}

		return $this->wpsp_list_table;
	}

	public function get_list_table_custom_log() {
		if ( is_null( $this->wpsp_custom_log ) ) {
			$this->wpsp_custom_log = new LIAISIPR_List_Table_Custom_Log( );
		}

		return $this->wpsp_custom_log;
	}

	public function get_list_table_log_implicit() {
		if ( is_null( $this->wpsp_log_implicit ) ) {
			$this->wpsp_log_implicit = new LIAISIPR_List_Table_Log_Implicit( );
		}

		return $this->wpsp_log_implicit;
	}


	protected function ensure_access_and_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		if ( isset( $_GET['page'] ) && 'wpsp_site_prober_log_list' !== $_GET['page'] ) {
			//$this->redirect_back();
			wp_safe_redirect( menu_page_url( 'wpsp_site_prober_log_list', false ) );
			exit;
		}

		return true;
	}

	protected function render_list_table_page( array $args ) {
		if ( ! $this->ensure_access_and_page() ) {
			return;
		}

		$list_table   = $args['list_table'];
		$title        = $args['title'];
		$plugin_select = $args['plugin_select'] ?? null;

		if ( null !== $plugin_select ) {
			$list_table->prepare_items( $plugin_select );
		} else {
			$list_table->prepare_items();
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( $title ); ?></h1>

			<?php $list_table->render_log_clear_button(); ?>

			<form method="get">
				<input type="hidden" name="page" value="wpsp_site_prober_log_list">
				<input type="hidden" name="tab" value="<?php echo esc_attr( $_GET['tab'] ?? 'log' ); ?>">

				<?php $list_table->display(); ?>
			</form>
		</div>
		<?php
	}

	public function render_page_list_table() {
		$this->render_list_table_page( [
			'title'      => __( 'Actions', 'liaison-site-prober' ),
			'list_table' => $this->get_list_table(),
		] );
	}

	public function render_page_list_table_custom_log() {
		$plugin_select = $_POST['plugin_select'] ?? '';

		$this->render_list_table_page( [
			'title'         => __( 'Custom Log', 'liaison-site-prober' ),
			'list_table'    => $this->get_list_table_custom_log(),
			'plugin_select' => $plugin_select,
		] );
	}

	public function render_page_list_table_log_implicit() {
		$plugin_select = $_POST['plugin_select'] ?? '';

		$this->render_list_table_page( [
			'title'         => __( 'Custom Log Implicit', 'liaison-site-prober' ),
			'list_table'    => $this->get_list_table_log_implicit(),
			'plugin_select' => $plugin_select,
		] );
	}

	//add_action( 'custom_log_clean', array( $this->wpsp_utility, 'clean_custom_logs' ) );
    public function clean_custom_logs( $arg ) {
        $this->get_list_table_custom_log()->delete_all_items_custom_log();
    }
}


