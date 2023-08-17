<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Coaching_Tile
{
    private static $_instance = null;
    public static function instance(){
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct(){

        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 99, 2 );
        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields_settings' ], 1, 2 );
        add_action( 'dt_details_additional_section', [ $this, 'dt_details_additional_section' ], 30, 2 );

        if ( dt_is_rest() ) {
            add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
        }

    }
    public function dt_details_additional_tiles( $tiles, $post_type = '' ) {
        if ( $post_type === 'contacts' ) {
            $tiles['basics'] = [ 'label' => __( 'Basics', 'zume-coaching' ) ]; // Funnel tile is keyed to followup (reduce tile redundancy)
            $tiles['followup'] = [ 'label' => __( 'Funnel Stage', 'zume-coaching' ) ]; // Funnel tile is keyed to followup (reduce tile redundancy)
            $tiles['faith'] = [ 'label' => __( 'Zúme System', 'zume-coaching' ) ]; // System tile is keyed to faith (reduce tile redundancy)
            $tiles['communication'] = [ 'label' => __( 'Communication', 'zume-coaching' ) ];
        }
        return $tiles;
    }
    public function dt_custom_fields_settings( array $fields, string $post_type = '' ) {
        if ( $post_type === 'contacts' ) {
            // process fields
        }
        return $fields;
    }
    public function dt_details_additional_section( $section, $post_type ) {
        // Hide Details Tile
        if ( $post_type === 'contacts' ) {
            ?>
            <style>
                #details-tile {
                    display:none;
                }
            </style>
            <?php
        }
        // Basics
        if ( $post_type === 'contacts' && $section === 'basics' ) {
            Zume_Tile_Basics::instance()->get( get_the_ID(), $post_type );
        }
        // Funnel Tile
        if ( $post_type === 'contacts' && $section === 'followup' ) {
            Zume_Tile_Funnel::instance()->get( get_the_ID(), $post_type );
        }
        // System Title
        if ( $post_type === 'contacts' && $section === 'faith' ) {
            Zume_Tile_System::instance()->get( get_the_ID(), $post_type );
        }
        // Communication Tile
        if ( $post_type === 'contacts' && $section === 'communication' ) {
            Zume_Tile_Communication::instance()->get( get_the_ID(), $post_type );
        }
    }


    public function add_api_routes() {
        $namespace = 'zume_coaching/v1';
        register_rest_route(
            $namespace, '/activity', [
                'methods'  => [ 'GET', 'POST' ],
                'callback' => [ $this, 'api_action_switch' ],
                'permission_callback' => '__return_true'
            ]
        );
    }
    public function api_action_switch( WP_REST_Request $request ) {
        $params =  dt_recursive_sanitize_array( $request->get_params() );
        if ( ! isset( $params['action'] ) ) {
            return new WP_Error( 'no_stage', __( 'No stage key provided.', 'zume' ), array( 'status' => 400 ) );
        }
        switch( $params['action'] ) {
            default:
                return $this->api_general( $params );
        }
    }
    public function api_general( $params ) {
        return $params;
    }




}
Zume_Coaching_Tile::instance();
