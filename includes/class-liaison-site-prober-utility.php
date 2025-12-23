<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LIAISIPR_Utility {

    protected $logger;
    protected static $session_id_in_use = null;
    const TAXONOMY = LIAISIP_TAXONOMY;
	const CPT      = LIAISIP_CPT;

	public function __construct( $logger ) 
    {
		$this->logger = $logger;
	}

	/**
	 * 將陣列轉成 CSV 一行（處理引號、逗號等）
	 */
	private function array_to_csv_line( $fields, $delimiter = ',', $enclosure = '"' ) {
		$escaped = [];
		foreach ( $fields as $field ) {
			$escaped[] = $field ? $enclosure . str_replace( $enclosure, $enclosure . $enclosure, $field ) . $enclosure : '';
		}
		return implode( $delimiter, $escaped ) . "\n";
	}
	
	private function export_csv_generic( array $args ) {
		if ( ! current_user_can( $args['capability'] ) ) {
			return;
		}

		if (
			! isset( $_GET[ $args['nonce_key'] ] ) ||
			! wp_verify_nonce(
				sanitize_key( $_GET[ $args['nonce_key'] ] ),
				$args['nonce_action']
			)
		) {
			wp_die( esc_html__( 'Invalid request.', 'liaison-site-prober' ) );
		}

		global $wpdb;

		$table       = sanitize_key( $args['table'] );
		$cache_key   = $args['cache_key'];
		$cache_group = 'liaison-site-prober';

		$rows = wp_cache_get( $cache_key, $cache_group );

		if ( false === $rows ) {
            if ( 'implicit_logs' === $table ) {
                $comment_where = "comment_approved = 'log-catcher' OR comment_approved = 'wp-logger' ";
                $rows = $wpdb->get_results(
                    "SELECT
                        comment_ID AS id,
                        comment_author AS plugin_name,
                        comment_content AS message,
                        user_ID AS severity,
                        comment_date AS created_at,
                        0 AS session_type
			        FROM {$wpdb->comments}
			        WHERE {$comment_where}"
                    ,ARRAY_A
		        );
            } else {
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $rows = $wpdb->get_results(
                    "SELECT * FROM {$table} ORDER BY created_at DESC",
                    ARRAY_A
                );
                wp_cache_set( $cache_key, $rows, $cache_group, 5 * MINUTE_IN_SECONDS );
            }
        }

		// Init filesystem
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		global $wp_filesystem;
		WP_Filesystem();

		$upload_dir = wp_upload_dir();
		$tmp_file   = trailingslashit( $upload_dir['basedir'] ) . $args['filename'] . '.csv';

		// CSV build
		$csv_lines   = [];
		$csv_lines[] = $args['headers'];

		foreach ( $rows as $row ) {
			$csv_lines[] = call_user_func( $args['row_mapper'], $row );
		}

		$csv_content = '';
		foreach ( $csv_lines as $line ) {
			$csv_content .= $this->array_to_csv_line( $line );
		}

		$wp_filesystem->put_contents( $tmp_file, $csv_content, FS_CHMOD_FILE );

		header( 'Content-Type: text/csv; charset=utf-8' );
		header(
			'Content-Disposition: attachment; filename=' .
			$args['filename'] . '-' . gmdate( 'Y-m-d' ) . '.csv'
		);
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $wp_filesystem->get_contents( $tmp_file );

		$wp_filesystem->delete( $tmp_file );
		exit;
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

    public function handle_export_csv() {
		$this->export_csv_generic( [
			'capability' => 'manage_options',
			'nonce_key'  => 'wpsp_nonce',
			'nonce_action' => 'wpsp_list_table_action',
			'table'      => $this->logger->get_table_name(),
			'cache_key'  => 'site_prober_logs_page_',
			'filename'   => 'liaison-site-prober-export',
			'headers'    => [ 'id', 'created_at', 'user_id', 'ip', 'action', 'object_type', 'description' ],
			'row_mapper' => function( $r ) {
				return [
					$r['id'],
					$r['created_at'],
					$this->user_info_export( $r['user_id'] ),
					$r['ip'],
					$r['action'],
					$r['object_type'],
					$r['description'],
				];
			},
		] );
	}

	public function handle_export_csv_custom_log() {
		$this->export_csv_generic( [
			'capability' => 'manage_options',
			'nonce_key'  => 'wpsp_nonce_custom_log',
			'nonce_action' => 'wpsp_export_custom_log',
			'table'      => $this->logger->get_table_name_custom_log(),
			'cache_key'  => 'site_prober_logs_page_custom_log',
			'filename'   => 'liaison-site-prober-custom-log-export',
			'headers'    => [ 'id', 'log_id', 'plugin_name', 'message', 'severity', 'session_type', 'session_id', 'created_at' ],
			'row_mapper' => function( $r ) {
				return [
					$r['id'],
					$r['log_id'],
					$r['plugin_name'],
					$r['message'],
					$r['severity'],
					$r['session_type'],
					$r['session_id'],
					$r['created_at'],
				];
			},
		] );
	}

    //add_action( 'admin_post_WP_Site_Prober_export_csv_implicit_log', [ $this->wpsp_utility, 'handle_export_csv_implicit_log' ] );
    public function handle_export_csv_implicit_log() {
		$this->export_csv_generic( [
			'capability' => 'manage_options',
			'nonce_key'  => 'wpsp_nonce_implicit_log',
			'nonce_action' => 'wpsp_export_implicit_log',
			'table'      => 'implicit_logs',
			'cache_key'  => 'site_prober_logs_page_log_implicit',
			'filename'   => 'liaison-site-prober-implicit-log-export',
			'headers'    => [ 'id', 'plugin_name', 'message', 'severity', 'session_type', 'created_at' ],
			'row_mapper' => function( $r ) {
				return [
					$r['id'],
					//$r['log_id'],
					$r['plugin_name'],
					$r['message'],
					$r['severity'],
					$r['session_type'],
					//$r['session_id'],
					$r['created_at'],
				];
			},
		] );
	}


	public function add_custom_log( $plugin_name, $log, $message, $severity = 1 ) {
		global $wpdb;
		$session_id = self::$session_id_in_use;
		$table = $this->get_custom_log_table();

		$wpdb->insert(
			$table,
			[		
				'log_id'       => null,
				'plugin_name'  => sanitize_text_field( (string) $plugin_name ),
				'message'      => sanitize_text_field( $message ),
				'severity'     => intval( $severity ),
				//'session_type' => false,
				'session_id'   => intval( $session_id ),
			],
			[ '%d', '%s', '%s', '%d', '%d' ]
		);

	}

    private function build_slug( $slug, $prefix = '' ) {
        $slug = sanitize_title( $slug );
        $prefix = sanitize_title( $prefix );
        
        return $prefix ? "{$prefix}-{$slug}" : "msg_category-{$slug}";
    }

    private function ensure_plugin_term( $plugin_name ) {
        $slug = $this->build_slug( $plugin_name );

        if ( ! term_exists( $slug, self::TAXONOMY ) ) {
            $result = wp_insert_term(
                $plugin_name,
                self::TAXONOMY,
                [ 'slug' => $slug ]
            );
            if ( is_wp_error( $result ) ) {
                error_log( sprintf( 'insert term wp_error: %s', $result->get_error_message()) );
                return false;
            }
        }
        return $slug;
    }

    
    private function find_category_post( $plugin_name, $category ) {
        $query = new WP_Query([
            'post_type' => self::CPT,
            'name'      => $this->build_slug( $category, $plugin_name ),
            'tax_query' => [
                [
                    'taxonomy' => self::TAXONOMY,
                    'field'    => 'slug',
                    'terms'    => $this->build_slug( $plugin_name ),
                ],
            ],
        ]);

        return $query->have_posts()
            ? $query->posts[0]->ID
            : false;
    }

    private function create_category_post( $plugin_name, $category, $session_title = '', $severity = 0  ) {
        $term_slug = $this->ensure_plugin_term( $plugin_name );
        if ( ! $term_slug ) return false;

        // Step 1: Determine parent log ID
        $parent_id = false;
        if ( '' === $session_title ) {
            // Creating a parent log
            $parent_id = 0;
        } else {
            // Creating a session child → need parent
            $parent_id = $this->find_category_post( $plugin_name, $category );
            if ( false == $parent_id ) {
                // Create parent explicitly (only once)
                $parent_id = $this->create_category_post( $plugin_name, $category, '', 0 );
            }
        }

        // Step 2: Build post data
        $args = [
            'post_type'      => self::CPT,
            'post_status'    => 'publish',
            //'post_name'      => $this->prefix_slug( $category, $plugin_name ),
            'post_name'      => $this->build_slug( $category, $plugin_name ),
            'post_title'     => $category,
        ];

        // Apply session-specific fields
        if ( '' !== $session_title ) {
            $args['post_parent']  = $parent_id;
            $args['post_title']   = $session_title;
            $args['post_excerpt'] = $plugin_name;
            $args['menu_order']   = $severity;
        }

        $post_id = wp_insert_post( $args );

        if ( $post_id ) {
            $result = wp_set_post_terms( $post_id, $term_slug, self::TAXONOMY );
            return is_array( $result ) ? $post_id : false;
		} else {
            return false;
        }
    }


    private function ensure_category_post( $plugin_name, $category ) {
        $post_id = $this->find_category_post( $plugin_name, $category );
        return $post_id ?: $this->create_category_post( $plugin_name, $category );
    }

    //do_action( 'wpsp_implicit_log_add', 'liaison-site-prober', 'msg_category', 'session test !!!!!', 5 );
    public function add_log_implicit( $plugin_name, $category, $message, $severity = 1 ) {
        global $wpdb;

        $plugin_name = sanitize_text_field( $plugin_name );
        $category    = sanitize_text_field( $category );
        $message     = sanitize_text_field( $message );
        $severity    = intval( $severity );

        // 1. 決定寫在哪個 post
        if ( self::$session_id_in_use ) {
            $post_id = self::$session_id_in_use;
        } else {
            $post_id = $this->ensure_category_post( $plugin_name, $category );
            if ( ! $post_id ) {
                return false;
            } else {
                error_log( sprintf( 'add_log_implicit: $post_id is: %d', $post_id) );
            }
        }

        // 2. 建 comment （真正的 log）
        $comment = [
            'comment_post_ID'      => $post_id,
            'comment_content'      => wp_kses_post( $message ),
            'comment_author'       => $plugin_name,
            'comment_approved'     => self::CPT,
            'comment_author_IP'    => '',
			'comment_author_url'   => '',
			'comment_author_email' => '',
            'user_id'              => $severity,
        ];

        if ( self::$session_id_in_use ) {
            $comment['comment_parent'] = 1; // session child
        }

        $comment_id = wp_insert_comment( wp_filter_comment( $comment ) );

        // 3. 如果沒有 session → 套用保留數量限制（避免爆 table）
        if ( ! self::$session_id_in_use ) {
            $this->limit_plugin_logs( $plugin_name, $category, $post_id );
        }

        return (bool)$comment_id;
    }

    // limit plugin logs with filter wpsp_implicit_log_limit_{plugin_name} 
	private function limit_plugin_logs( $plugin_name, $log_name, $log_id ) {
		global $wpdb;

		$limit = apply_filters( 'wpsp_implicit_log_limit_' . $plugin_name, 20, $log_name );

		$comments = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM $wpdb->comments WHERE comment_approved = %s AND comment_author = %s AND comment_post_ID = %d ORDER BY comment_date ASC",
				self::CPT,
				$plugin_name,
				$log_id
			)
		);

		$count = $wpdb->num_rows;

		if ( $count > $limit ) {
			$diff = $count - $limit;
			for ( $i = 0; $i < $diff; $i++ ) {
				wp_delete_comment( $comments[ $i ]->comment_ID, true );
			}
		}
	}

	public function get_custom_log_table() {
		return sanitize_key( $this->logger->get_table_name_custom_log() );
	}
	public function get_custom_log_session_table() {
		return sanitize_key( $this->logger->get_table_name_custom_log_session() );
	}

	function begin_session( $plugin_name, $message, $session_title, $severity = 0 ) {
		global $wpdb;
		
		$table_session = $this->get_custom_log_session_table();

		$result = $wpdb->insert(
			$table_session,
			[		
				'plugin_name'  => sanitize_text_field( (string) $plugin_name ),
				'message'      => sanitize_text_field( $message ),
				'severity'     => intval( $severity ),
			],
			[ '%s', '%s', '%d']
		);

		if ( $result !== false ) {
			$last_inserted_id = $wpdb->insert_id;
			$session_id = $last_inserted_id;
			self::$session_id_in_use = $session_id;
		} else {
			error_log( 'Error inserting data.');
		}
		
		return true;
	}

	function end_session( ...$args ) {
		self::$session_id_in_use = null;
		return true;
	}

    public function handle_custom_log_generate() {
		$appends = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '!', '@', '#'];
		
		// 從 option 讀取
		$x = intval( get_option('liaison_custom_log_x', 0) );
		$append_now = $appends[$x];
		// 計算下一個值
        ( ($x + 1) >= count($appends) ) ? $x = 0 : $x++;
		// 寫回 option
		update_option('liaison_custom_log_x', $x);
		do_action( 'custom_log_add', 'liaison-site-prober', 'message-'.$append_now, 'step-'.$append_now, 2 );
		add_action('shutdown', function () {
			wp_safe_redirect(
				add_query_arg(
					[
						'page' => 'wpsp_site_prober_log_list',
						'tab'  => 'custom',
					],
					admin_url('admin.php')
				)
			);
			exit;	
		} );
	}
	public function handle_session_generate() {
		do_action( 'custom_log_session_begin', 'liaison-site-prober', 'message-session-begin', 'session-begin !', 0 );
			do_action( 'custom_log_add', 'liaison-site-prober', 'message-in-session', 'step-in-session', 4 );
		do_action( 'custom_log_session_end' );
		add_action('shutdown', function () {
			wp_safe_redirect(
				add_query_arg(
					[
						'page' => 'wpsp_site_prober_log_list',
						'tab'  => 'custom',
					],
					admin_url('admin.php')
				)
			);
			exit;	
		} );
	}

    // Generate implicit log for testing
	//add_action( 'admin_post_WP_Implicit_Log_log_generate', [ $this->wpsp_utility, 'handle_implicit_log_generate' ] );
	//add_action( 'admin_post_WP_Implicit_Log_session_generate', [ $this->wpsp_utility, 'handle_implicit_session_generate' ] );
    //add_action( 'wpsp_implicit_log_add'
    public function handle_implicit_log_generate() {
		$appends = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '!', '@', '#'];
		
		// 從 option 讀取
		$x = intval( get_option('liaison_custom_log_x', 0) );
		$append_now = $appends[$x];
		// 計算下一個值
        ( ($x + 1) >= count($appends) ) ? $x = 0 : $x++;
		// 寫回 option
		update_option('liaison_custom_log_x', $x);
		//do_action( 'wpsp_implicit_log_add', 'liaison-site-prober', 'message-'.$append_now, 'step-'.$append_now, 2 );
        do_action( 'wpsp_implicit_log_add', 'plugin-'.$append_now, 'message-'.$append_now, 'step-'.$append_now, 2); 
		
		add_action('shutdown', function () {
			wp_safe_redirect(
				add_query_arg(
					[
						'page' => 'wpsp_site_prober_log_list',
						'tab'  => 'implicit',
					],
					admin_url('admin.php')
				)
			);
			exit;	
		} );
	}
}


