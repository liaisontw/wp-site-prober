<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LIAISIPR_REST_Controller {

    const NAMESPACE = 'site-prober/v1';

    public function register_routes() {

        register_rest_route(
            self::NAMESPACE,
            '/logs',
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'get_logs' ],
                    'permission_callback' => [ $this, 'permissions_read' ],
                    'args'                => [
                        'plugin' => [
                            'type'     => 'string',
                            'required' => false,
                        ],
                        'limit' => [
                            'type'    => 'integer',
                            'default' => 20,
                        ],
                    ],
                ],
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'create_log' ],
                    'permission_callback' => [ $this, 'permissions_write' ],
                ],
                [
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => [ $this, 'clear_logs' ],
                    'permission_callback' => [ $this, 'permissions_write' ],
                ],
            ]
        );
    }

    /* -----------------------------------------------------------------
     * Permissions
     * ----------------------------------------------------------------- */

    public function permissions_read() {
        return __return_true();
        //return current_user_can( 'read' );
    }

    public function permissions_write() {
        return current_user_can( 'manage_options' );
    }

    /* -----------------------------------------------------------------
     * Callbacks
     * ----------------------------------------------------------------- */
    /*
        $limit  = (int) $request->get_param( 'limit' );
        $plugin = sanitize_text_field( $request->get_param( 'plugin' ) );

        $where = '1=1';

        if ( $plugin ) {
            $where .= $wpdb->prepare(
                ' AND comment_author = %s',
                $plugin
            );
        }

        $sql = $wpdb->prepare(
            "
            SELECT
                comment_ID   AS id,
                comment_author AS plugin,
                comment_content AS message,
                comment_date AS created_at
            FROM {$wpdb->comments}
            WHERE {$where}
            ORDER BY comment_date DESC
            LIMIT %d
            ",
            $limit
        );
        */

    public function get_logs( WP_REST_Request $request ) {
        global $wpdb;

        $table = $wpdb->wpsp_activity;
        $sql = $wpdb->prepare(
            "
            SELECT
                id,
                created_at,
                user_id,
                ip,
                action,
                object_type,
                description
            FROM {$table} 
            "
        );

        
        $results = $wpdb->get_results( $sql, ARRAY_A );

        return rest_ensure_response( [
            'count' => count( $results ),
            'logs'  => $results,
        ] );
    }

    public function create_log( WP_REST_Request $request ) {
        global $wpdb;

        $plugin  = sanitize_text_field( $request->get_param( 'plugin' ) );
        $message = sanitize_textarea_field( $request->get_param( 'message' ) );

        if ( empty( $plugin ) || empty( $message ) ) {
            return new WP_Error(
                'invalid_data',
                __( 'Plugin and message are required.', 'liaison-site-prober' ),
                [ 'status' => 400 ]
            );
        }

        $wpdb->insert(
            $wpdb->comments,
            [
                'comment_author'  => $plugin,
                'comment_content' => $message,
                'comment_approved'=> 'site-prober',
                'comment_date'    => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s', '%s' ]
        );

        return rest_ensure_response( [
            'success' => true,
            'id'      => $wpdb->insert_id,
        ] );
    }

    public function clear_logs() {
        global $wpdb;

        $wpdb->query(
            "DELETE FROM {$wpdb->comments} WHERE comment_approved = 'site-prober'"
        );

        return rest_ensure_response( [
            'success' => true,
        ] );
    }
}
