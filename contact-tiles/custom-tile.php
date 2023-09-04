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

        add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ], 99 );

        if ( dt_is_rest() ) {
            add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
        }

    }
    public function scripts() {
        global $post_type;
        if ( 'contacts' === $post_type ) {
            wp_enqueue_script( 'orgchart_js', 'https://cdnjs.cloudflare.com/ajax/libs/orgchart/3.7.0/js/jquery.orgchart.min.js', [
                'jquery',
            ], '3.7.0', true );
            $css_file_name = 'genmap/jquery.orgchart.custom.css';
            wp_enqueue_style( 'orgchart_css', plugin_dir_url(__FILE__) . $css_file_name, [], filemtime( plugin_dir_path(__FILE__)  . $css_file_name ) );
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
                return self::api_general( $params );
        }
    }
    public function api_general( $params ) {
        return $params;
    }

    public static function print_activity_list( $activity ) {
        $activity_by_date = self::_activity_by_date( $activity );
        if ( ! empty( $activity_by_date ) ) {
            echo '<div class="grid-x grid-padding-x">';
            $days_skipped = 0;
            foreach( $activity_by_date as $year => $days ) {
                echo '<div class="cell"><h2>' . $year  . '</h2></div>';

                foreach( $days as $day => $day_activity ) {
                    if ( empty( $day_activity ) ) {
                        $days_skipped++;
                    } else {
                        if ( $days_skipped > 0 ) {
                            echo '<div class="cell small-3"></div><div class="cell small-9" style="padding:1em;">-- ' . $days_skipped . ' days no activity --</div>';
                            $days_skipped = 0;
                        }
                        echo '<div class="cell small-3" style="text-align:right;">' . $day  . '</div><div class="cell small-9" style="border-left:1px solid lightgrey;">';
                        foreach( $day_activity as $row) {
                            echo date( 'h:i a', $row['time_end'] ) ?> - <strong><?php echo ucwords( str_replace( '_', ' ', $row['log_key'] ) ) ?></strong><br><?php
                        }
                        echo '</div>';
                    }
                }
            }
            echo '</div>';
        }
    }
    public static function _activity_by_date( $activity ) {
        if ( empty( $activity ) ) {
            return [];
        }

        $range = self::create_date_range_array( date( 'Y-m-d', $activity[0]['time_end'] ), date( 'Y-m-d', time() ) );

        $new_activity = [];
        foreach ($range as $value) {
            $year = substr($value, 0, 4);
            $day = substr($value, 5, 2) . '-' . substr($value, 8, 2);

            if ( ! isset( $new_activity[$year] ) ) {
                $new_activity[$year] = [];
            }
            $new_activity[$year][$day] = [];
        }

        foreach ( $activity as $item ) {
            $year = date( 'Y', $item['time_end'] );
            $day = date( 'm-d', $item['time_end'] );

            if( ! isset( $new_activity[$year][$day] ) ) {
                continue;
            }

            $new_activity[$year][$day][] = $item;

        }
        return $new_activity;
    }
    public static function create_date_range_array($start_date,$end_date)
    {
        $aryRange = [];

        $iDateFrom = mktime(1, 0, 0, substr($start_date, 5, 2), substr($start_date, 8, 2), substr($start_date, 0, 4));
        $iDateTo = mktime(1, 0, 0, substr($end_date, 5, 2), substr($end_date, 8, 2), substr($end_date, 0, 4));

        if ($iDateTo >= $iDateFrom) {
            array_push($aryRange, date('Y-m-d', $iDateFrom)); // first entry
            while ($iDateFrom<$iDateTo) {
                $iDateFrom += 86400; // add 24 hours
                array_push($aryRange, date('Y-m-d', $iDateFrom));
            }
        }
        return $aryRange;
    }


}
Zume_Coaching_Tile::instance();
