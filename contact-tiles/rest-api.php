<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Training_API
{
    public $namespace = 'zume_training/v1';
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
        if ( dt_is_rest()) {
            add_action('rest_api_init', [$this, 'add_api_routes']);
            add_filter('dt_allow_rest_access', [$this, 'authorize_url'], 10, 1);
        }
    }
    public function authorize_url( $authorized ){
        if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), $this->namespace  ) !== false ) {
            $authorized = true;
        }
        return $authorized;
    }
    public function add_api_routes()
    {
        $arg_schemas = [
            'post_type' => [
                'description' => 'The post type',
                'type' => 'string',
                'required' => true,
                'validate_callback' => [ $this, 'prefix_validate_args' ]
            ],
        ];
        register_rest_route(
            $this->namespace, '/(?P<post_type>\w+)/(?P<id>\d+)', [
                'methods' => ['GET', 'POST'],
                'callback' => [$this, 'get_post'],
                'args' => [
                    'post_type' => $arg_schemas['post_type'],
                ],
                'permission_callback' => '__return_true'
            ]
        );
    }
    public function get_post( WP_REST_Request $request ){
        $params = dt_recursive_sanitize_array($request->get_params());

        global $wpdb;
        $post_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM wp_posts WHERE ID = %d", $params['id'] ), ARRAY_A );
        $postmeta_rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM wp_postmeta WHERE post_id = %d", $params['id'] ), ARRAY_A );
        $connections = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM wp_p2p WHERE p2p_from = %d OR p2p_to = %d", $params['id'], $params['id'] ), ARRAY_A );

        $fields = [
            'ID' => 0,
            'location_grid' => [],
            'location_grid_meta' => [],
        ];
        foreach( $post_row as $key => $value ) {
            $fields[ $key ] = $value;
        }
        foreach( $postmeta_rows as $row ) {
            if ( 'location_grid' === $row['meta_key'] ) {
                $fields['location_grid'][] = $wpdb->get_row("SELECT * FROM wp_dt_location_grid WHERE grid_id = {$row['meta_value']}", ARRAY_A);
            } else if ( 'location_grid_meta' === $row['meta_key'] ) {
                $fields['location_grid_meta'][] = $wpdb->get_row("SELECT * FROM wp_dt_location_grid_meta WHERE grid_meta_id = {$row['meta_value']}", ARRAY_A);
            }
            else {
                $fields[ $row['meta_key'] ] = $row['meta_value'];
            }
        }
        $fields['connections'] = $connections;
//dt_write_log( $fields);

        return $fields;
    }
    public function prefix_validate_args( $value, $request, $param ){
        $attributes = $request->get_attributes();

        if ( isset( $attributes['args'][ $param ] ) ) {
            $argument = $attributes['args'][ $param ];
            // Check to make sure our argument is a string.
            if ( 'string' === $argument['type'] && ! is_string( $value ) ) {
                return new WP_Error( 'rest_invalid_param', sprintf( '%1$s is not of type %2$s', $param, 'string' ), array( 'status' => 400 ) );
            }
            if ( 'integer' === $argument['type'] && ! is_numeric( $value ) ) {
                return new WP_Error( 'rest_invalid_param', sprintf( '%1$s is not of type %2$s', $param, 'integer' ), array( 'status' => 400 ) );
            }
            if ( $param === 'post_type' ){
                $post_types = DT_Posts::get_post_types();
                // Support advanced search all post type option
                if ( ( $value !== 'all' ) && ! in_array( $value, $post_types ) ) {
                    return new WP_Error( 'rest_invalid_param', sprintf( '%1$s is not a valid post type', $value ), array( 'status' => 400 ) );
                }
            }
        } else {
            // This code won't execute because we have specified this argument as required.
            // If we reused this validation callback and did not have required args then this would fire.
            return new WP_Error( 'rest_invalid_param', sprintf( '%s was not registered as a request argument.', $param ), array( 'status' => 400 ) );
        }

        // If we got this far then the data is valid.
        return true;
    }

}
Zume_Training_API::instance();
