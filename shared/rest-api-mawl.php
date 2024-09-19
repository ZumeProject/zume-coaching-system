<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Charts_API_MAWL
{
    public $coach_permissions = ['access_contacts'];
    public $namespace = 'zume_funnel/v1';
    private static $_instance = null;

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        if (self::dt_is_rest()) {
            add_action('rest_api_init', [$this, 'add_api_routes']);
            add_filter('dt_allow_rest_access', [$this, 'authorize_url'], 10, 1);
        }
    }

    public function add_api_routes()
    {
        register_rest_route(
            $this->namespace, '/mawl', [
                [
                    'methods' => 'GET',
                    'callback' => [$this, 'list_mawl'],
                    'permission_callback' => function () {
                        return $this->has_permission($this->coach_permissions);
                    },
                ],
            ]
        );
        register_rest_route(
            $this->namespace, '/mawl', [
                [
                    'methods' => 'POST',
                    'callback' => [$this, 'create_mawl'],
                    'permission_callback' => function () {
                        return $this->has_permission($this->coach_permissions);
                    },
                ],
            ]
        );
        register_rest_route(
            $this->namespace, '/mawl', [
                [
                    'methods' => 'DELETE',
                    'callback' => [$this, 'delete_mawl'],
                    'permission_callback' => function () {
                        return $this->has_permission($this->coach_permissions);
                    },
                ],
            ]
        );
    }

    public function has_permission($permissions = [])
    {
        $pass = false;
        foreach ($permissions as $permission) {
            if (current_user_can($permission)) {
                $pass = true;
            }
        }
        return $pass;
    }

    public function list_mawl( WP_REST_Request $request ) {
        $params = dt_recursive_sanitize_array( $request->get_params() );
        if ( ! isset( $params['user_id'] ) ) {
            return new WP_Error( __METHOD__, 'User_id required.', array( 'status' => 401 ) );
        }
        $user_id = zume_validate_user_id_request( $params['user_id'] );

        return zume_get_user_host( $user_id );
    }
    public function create_mawl( WP_REST_Request $request ) {
        $params = dt_recursive_sanitize_array( $request->get_params() );
        if ( ! isset( $params['type'], $params['subtype'], $params['user_id'] ) ) {
            return new WP_Error( __METHOD__, 'Type, subtype, and user_id required.', array( 'status' => 401 ) );
        }
        if ( 'coaching' !== $params['type'] ) {
            return new WP_Error( __METHOD__, 'Type must be coaching.', array( 'status' => 401 ) );
        }
        $user_id = zume_validate_user_id_request( $params['user_id'] );

        return zume_log_insert( $params['type'], $params['subtype'], [ 'user_id' => $user_id ] );
    }
    public function delete_mawl( WP_REST_Request $request ) {
        global $wpdb;
        $params = dt_recursive_sanitize_array( $request->get_params() );
        if ( ! isset( $params['type'], $params['subtype'], $params['user_id'] ) ) {
            return new WP_Error( __METHOD__, 'Type, subtype, and user_id required.', array( 'status' => 401 ) );
        }
        if ( 'coaching' !== $params['type'] ) {
            return new WP_Error( __METHOD__, 'Type must be coaching.', array( 'status' => 401 ) );
        }
        $user_id = zume_validate_user_id_request( $params['user_id'] );

        $fields = [
            'type' => $params['type'],
            'subtype' => $params['subtype'],
            'user_id' => $user_id,
        ];

        $delete = $wpdb->delete( 'zume_dt_reports', $fields );

        return $delete;
    }

    public function authorize_url( $authorized ){
        if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), $this->namespace ) !== false ) {
            $authorized = true;
        }
        return $authorized;
    }
    public function dt_is_rest( $namespace = null ) {
        // https://github.com/DiscipleTools/disciple-tools-theme/blob/a6024383e954cec2ac4e7a1a31fb4601c940f485/dt-core/global-functions.php#L60
        // Added here so that in non-dt sites there is no dependency.
        $prefix = rest_get_url_prefix();
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST
            || isset( $_GET['rest_route'] )
            && strpos( trim( sanitize_text_field( wp_unslash( $_GET['rest_route'] ) ), '\\/' ), $prefix, 0 ) === 0 ) {
            return true;
        }
        $rest_url    = wp_parse_url( site_url( $prefix ) );
        $current_url = wp_parse_url( add_query_arg( array() ) );
        $is_rest = strpos( $current_url['path'], $rest_url['path'], 0 ) === 0;
        if ( $namespace ){
            return $is_rest && strpos( $current_url['path'], $namespace ) != false;
        } else {
            return $is_rest;
        }
    }
}
Zume_Charts_API_MAWL::instance();
