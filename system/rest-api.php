<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Coaching_Endpoints
{
    public $namespace = 'zume_coaching/v1';
    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    public function __construct() {
        if ( dt_is_rest() ) {
            add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
//            add_filter( 'dt_allow_rest_access', [ $this, 'authorize_url' ], 10, 1 );
        }
    }
    public function add_api_routes() {
        $namespace = $this->namespace;

        register_rest_route(
            $namespace, '/activity', [
                'methods'  => [ 'GET', 'POST' ],
                'callback' => [ $this, 'activity_routing' ],
                'permission_callback' => '__return_true'
            ]
        );

    }

    public function activity_routing( WP_REST_Request $request ) {
        $params =  dt_recursive_sanitize_array( $request->get_params() );
        if ( ! isset( $params['action'] ) ) {
            return new WP_Error( 'no_stage', __( 'No stage key provided.', 'zume' ), array( 'status' => 400 ) );
        }

        switch( $params['action'] ) {

            default:
                return $this->general( $params );
        }
    }

    public function general( $params ) {
        return $params;
    }
}
Zume_Coaching_Endpoints::instance();
