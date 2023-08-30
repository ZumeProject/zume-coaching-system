<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Goals_Stats_Endpoints
{
    public $namespace = 'zume_goals/v1';
    public $permissions = ['manage_dt'];
    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    public function __construct() {
        if ( $this->dt_is_rest() ) {
            add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
            add_filter( 'dt_allow_rest_access', [ $this, 'authorize_url' ], 10, 1 );
        }
    }
    public function add_api_routes() {
        $namespace = $this->namespace;

        register_rest_route(
            $namespace, '/total', [
                'methods'  => [ 'GET', 'POST' ],
                'callback' => [ $this, 'total' ],
                'permission_callback' => function () {
                    return $this->has_permission();
                }
            ]
        );
        register_rest_route(
            $this->namespace, '/location_goals', [
                [
                    'methods'  => 'GET',
                    'callback' => [ $this, 'location_goals' ],
                    'permission_callback' => function () {
                        return $this->has_permission();
                    }
                ],
            ]
        );
        register_rest_route(
            $namespace, '/location', [
                'methods'  => [ 'GET', 'POST' ],
                'callback' => [ $this, 'location' ],
                'permission_callback' => function () {
                    return $this->has_permission();
                }
            ]
        );


        register_rest_route(
            $namespace, '/map', [
                'methods'  => [ 'GET', 'POST' ],
                'callback' => [ $this, 'map' ],
                'permission_callback' => function () {
                    return $this->has_permission();
                }
            ]
        );
        register_rest_route(
            $namespace, '/list', [
                'methods'  => [ 'GET', 'POST' ],
                'callback' => [ $this, 'list' ],
                'permission_callback' => function () {
                    return $this->has_permission();
                }
            ]
        );
    }

    public function total( WP_REST_Request $request ) {
        $params =  dt_recursive_sanitize_array( $request->get_params() );
        if ( ! isset( $params['stage'] ) ) {
            return new WP_Error( 'no_stage', __( 'No stage key provided.', 'zume' ), array( 'status' => 400 ) );
        }

        if ( ! isset( $params['negative_stat'] ) ) {
            $params['negative_stat'] = false;
        }

        if ( ! isset( $params['range'] ) ) {
            $params['range'] = false;
        }

        switch( $params['stage'] ) {
            case 'anonymous':
                return $this->total_anonymous( $params );
            case 'registrants':
                return $this->total_registrants( $params );
            case 'att':
                return $this->total_att( $params );
            case 'ptt':
                return $this->total_ptt( $params );
            case 's1':
                return $this->total_s1( $params );
            case 's2':
                return $this->total_s2( $params );
            case 's3':
                return $this->total_s3( $params );
            case 'facilitator':
                return $this->total_facilitator( $params );
            case 'early':
                return $this->total_early( $params );
            case 'advanced':
                return $this->total_advanced( $params );
            default:
                return $this->general( $params );
        }
    }
    public function total_anonymous( $params ) {
        $negative_stat = $params['negative_stat'];
        $value = rand(100, 1000);
        $goal = rand(500, 700);
        $trend = rand(500, 700);

        switch( $params['key'] ) {

            case 'total_anonymous':
                return [
                    'key' => $params['key'],
                    'label' => 'Anonymous',
                    'description' => 'Sample description.',
                    'link' => '',
                    'value' => Zume_Goals_Query::format_int( $value ),
                    'valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal' => $goal,
                    'goal_valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal_percent' => Zume_Goals_Query::get_percent( $value, $goal ),
                    'trend' => $trend,
                    'trend_valence' => Zume_Goals_Query::get_valence( $value, $trend, $negative_stat ),
                    'trend_percent' => Zume_Goals_Query::get_percent( $value, $trend ),
                    'negative_stat' => $negative_stat,
                ];
            default:
                return [
                    'key' => $params['key'],
                    'label' => '',
                    'description' => '',
                    'link' => '',
                    'value' => Zume_Goals_Query::format_int( $value ),
                    'valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal' => $goal,
                    'goal_valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal_percent' => Zume_Goals_Query::get_percent( $value, $goal ),
                    'trend' => $trend,
                    'trend_valence' => Zume_Goals_Query::get_valence( $value, $trend, $negative_stat ),
                    'trend_percent' => Zume_Goals_Query::get_percent( $value, $trend ),
                    'negative_stat' => $negative_stat,
                ];
        }

    }
    public function total_registrants( $params ) {
        $negative_stat = $params['negative_stat'];
        $value = rand(100, 1000);
        $goal = rand(500, 700);
        $trend = rand(500, 700);

        switch( $params['key'] ) {
            case 'total_registrants':
                return [
                    'key' => $params['key'],
                    'label' => 'Registrants',
                    'description' => 'People who have registered but have not progressed into training.',
                    'link' => 'registrants',
                    'value' => Zume_Goals_Query::format_int( $value ),
                    'valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal' => $goal,
                    'goal_valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal_percent' => Zume_Goals_Query::get_percent( $value, $goal ),
                    'trend' => $trend,
                    'trend_valence' => Zume_Goals_Query::get_valence( $value, $trend, $negative_stat ),
                    'trend_percent' => Zume_Goals_Query::get_percent( $value, $trend ),
                    'negative_stat' => $negative_stat,
                ];
            default:
                return [
                    'key' => $params['key'],
                    'label' => '',
                    'description' => '',
                    'link' => '',
                    'value' => Zume_Goals_Query::format_int( $value ),
                    'valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal' => $goal,
                    'goal_valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal_percent' => Zume_Goals_Query::get_percent( $value, $goal ),
                    'trend' => $trend,
                    'trend_valence' => Zume_Goals_Query::get_valence( $value, $trend, $negative_stat ),
                    'trend_percent' => Zume_Goals_Query::get_percent( $value, $trend ),
                    'negative_stat' => $negative_stat,
                ];

        }

    }
    public function total_att( $params ) {
        $negative_stat = $params['negative_stat'];
        $value = rand(100, 1000);
        $goal = rand(500, 700);
        $trend = rand(500, 700);

        switch( $params['key'] ) {
            case 'total_att':
                return [
                    'key' => $params['key'],
                    'label' => 'Active Training Trainees',
                    'description' => 'People who are actively working a training plan or have only partially completed the training.',
                    'link' => 'active',
                    'value' => Zume_Goals_Query::format_int( $value ),
                    'valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal' => $goal,
                    'goal_valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal_percent' => Zume_Goals_Query::get_percent( $value, $goal ),
                    'trend' => $trend,
                    'trend_valence' => Zume_Goals_Query::get_valence( $value, $trend, $negative_stat ),
                    'trend_percent' => Zume_Goals_Query::get_percent( $value, $trend ),
                    'negative_stat' => $negative_stat,
                ];
            default:
                return [
                    'key' => $params['key'],
                    'label' => '',
                    'description' => '',
                    'link' => '',
                    'value' => Zume_Goals_Query::format_int( $value ),
                    'valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal' => $goal,
                    'goal_valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal_percent' => Zume_Goals_Query::get_percent( $value, $goal ),
                    'trend' => $trend,
                    'trend_valence' => Zume_Goals_Query::get_valence( $value, $trend, $negative_stat ),
                    'trend_percent' => Zume_Goals_Query::get_percent( $value, $trend ),
                    'negative_stat' => $negative_stat,
                ];
        }

    }
    public function total_ptt( $params ) {
        $negative_stat = $params['negative_stat'];
        $value = rand(100, 1000);
        $goal = rand(500, 700);
        $trend = rand(500, 700);

        switch( $params['key'] ) {
            case 'total_ptt':
                return [
                    'key' => $params['key'],
                    'label' => 'Post-Training Trainees',
                    'description' => 'People who have completed the training and are working on a post training plan.',
                    'link' => 'post',
                    'value' => Zume_Goals_Query::format_int( $value ),
                    'valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal' => $goal,
                    'goal_valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal_percent' => Zume_Goals_Query::get_percent( $value, $goal ),
                    'trend' => $trend,
                    'trend_valence' => Zume_Goals_Query::get_valence( $value, $trend, $negative_stat ),
                    'trend_percent' => Zume_Goals_Query::get_percent( $value, $trend ),
                    'negative_stat' => $negative_stat,
                ];
            default:
                return [
                    'key' => $params['key'],
                    'label' => '',
                    'description' => '',
                    'link' => '',
                    'value' => Zume_Goals_Query::format_int( $value ),
                    'valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal' => $goal,
                    'goal_valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal_percent' => Zume_Goals_Query::get_percent( $value, $goal ),
                    'trend' => $trend,
                    'trend_valence' => Zume_Goals_Query::get_valence( $value, $trend, $negative_stat ),
                    'trend_percent' => Zume_Goals_Query::get_percent( $value, $trend ),
                    'negative_stat' => $negative_stat,
                ];
        }

    }
    public function total_s1( $params ) {
        $negative_stat = $params['negative_stat'];
        $value = rand(100, 1000);
        $goal = rand(500, 700);
        $trend = rand(500, 700);

        switch( $params['key'] ) {
            case 'total_s1':
                return [
                    'key' => $params['key'],
                    'label' => '(S1) Partial Practitioners',
                    'description' => 'Learning through doing. Implementing partial checklist / 4-fields',
                    'link' => 's1_practitioners',
                    'value' => Zume_Goals_Query::format_int( $value ),
                    'valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal' => $goal,
                    'goal_valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal_percent' => Zume_Goals_Query::get_percent( $value, $goal ),
                    'trend' => $trend,
                    'trend_valence' => Zume_Goals_Query::get_valence( $value, $trend, $negative_stat ),
                    'trend_percent' => Zume_Goals_Query::get_percent( $value, $trend ),
                    'negative_stat' => $negative_stat,
                ];
            default:
                return [
                    'key' => $params['key'],
                    'label' => '',
                    'description' => '',
                    'link' => '',
                    'value' => Zume_Goals_Query::format_int( $value ),
                    'valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal' => $goal,
                    'goal_valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal_percent' => Zume_Goals_Query::get_percent( $value, $goal ),
                    'trend' => $trend,
                    'trend_valence' => Zume_Goals_Query::get_valence( $value, $trend, $negative_stat ),
                    'trend_percent' => Zume_Goals_Query::get_percent( $value, $trend ),
                    'negative_stat' => $negative_stat,
                ];
        }

    }
    public function total_s2( $params ) {
        $negative_stat = $params['negative_stat'];
        $value = rand(100, 1000);
        $goal = rand(500, 700);
        $trend = rand(500, 700);

        switch( $params['key'] ) {
            case 'total_s2':
                return [
                    'key' => $params['key'],
                    'label' => '(S2) Completed Practitioners',
                    'description' => 'People who are seeking multiplicative movement and are completely skilled with the coaching checklist.',
                    'link' => 's2_practitioners',
                    'value' => Zume_Goals_Query::format_int( $value ),
                    'valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal' => $goal,
                    'goal_valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal_percent' => Zume_Goals_Query::get_percent( $value, $goal ),
                    'trend' => $trend,
                    'trend_valence' => Zume_Goals_Query::get_valence( $value, $trend, $negative_stat ),
                    'trend_percent' => Zume_Goals_Query::get_percent( $value, $trend ),
                    'negative_stat' => $negative_stat,
                ];
            default:
                return [
                    'key' => $params['key'],
                    'label' => '',
                    'description' => '',
                    'link' => '',
                    'value' => Zume_Goals_Query::format_int( $value ),
                    'valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal' => $goal,
                    'goal_valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal_percent' => Zume_Goals_Query::get_percent( $value, $goal ),
                    'trend' => $trend,
                    'trend_valence' => Zume_Goals_Query::get_valence( $value, $trend, $negative_stat ),
                    'trend_percent' => Zume_Goals_Query::get_percent( $value, $trend ),
                    'negative_stat' => $negative_stat,
                ];
        }

    }
    public function total_s3( $params ) {
        $negative_stat = $params['negative_stat'];
        $value = rand(100, 1000);
        $goal = rand(500, 700);
        $trend = rand(500, 700);

        switch( $params['key'] ) {
            case 'total_s3':
                return [
                    'key' => $params['key'],
                    'label' => '(S3) Multiplying Practitioners',
                    'description' => 'People who are seeking multiplicative movement and are stewarding generational fruit.',
                    'link' => 's3_practitioners',
                    'value' => Zume_Goals_Query::format_int( $value ),
                    'valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal' => $goal,
                    'goal_valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal_percent' => Zume_Goals_Query::get_percent( $value, $goal ),
                    'trend' => $trend,
                    'trend_valence' => Zume_Goals_Query::get_valence( $value, $trend, $negative_stat ),
                    'trend_percent' => Zume_Goals_Query::get_percent( $value, $trend ),
                    'negative_stat' => $negative_stat,
                ];
            default:
                return [
                    'key' => $params['key'],
                    'label' => '',
                    'description' => '',
                    'link' => '',
                    'value' => Zume_Goals_Query::format_int( $value ),
                    'valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal' => $goal,
                    'goal_valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal_percent' => Zume_Goals_Query::get_percent( $value, $goal ),
                    'trend' => $trend,
                    'trend_valence' => Zume_Goals_Query::get_valence( $value, $trend, $negative_stat ),
                    'trend_percent' => Zume_Goals_Query::get_percent( $value, $trend ),
                    'negative_stat' => $negative_stat,
                ];
        }

    }
    public function general( $params ) {
        $negative_stat = $params['negative_stat'];
        $value = rand(100, 1000);
        $goal = rand(500, 700);
        $trend = rand(500, 700);

        switch( $params['key'] ) {

            case 'practitioners_total':
                $value = Zume_Goals_Query::query_total_practitioners();
                $goal = rand(500, 700);
                $trend = 20;

                return [
                    'key' => $params['key'],
                    'label' => 'Practitioners',
                    'description' => 'People who are seeking multiplicative movement and are stewarding generational fruit.',
                    'link' => 'practitioners_total',
                    'value' => Zume_Goals_Query::format_int( $value ),
                    'valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal' => $goal,
                    'goal_valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal_percent' => Zume_Goals_Query::get_percent( $value, $goal ),
                    'trend' => $trend,
                    'trend_valence' => Zume_Goals_Query::get_valence( $value, $trend, $negative_stat ),
                    'trend_percent' => Zume_Goals_Query::get_percent( $value, $trend ),
                    'negative_stat' => $negative_stat,
                ];
            case 'churches_total':
                $value = Zume_Goals_Query::query_total_churches();
                $goal = rand(500, 700);
                $trend = 20;

                return [
                    'key' => $params['key'],
                    'label' => 'Churches',
                    'description' => 'People who are seeking multiplicative movement and are stewarding generational fruit.',
                    'link' => 'churches_total',
                    'value' => Zume_Goals_Query::format_int( $value ),
                    'valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal' => $goal,
                    'goal_valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal_percent' => Zume_Goals_Query::get_percent( $value, $goal ),
                    'trend' => $trend,
                    'trend_valence' => Zume_Goals_Query::get_valence( $value, $trend, $negative_stat ),
                    'trend_percent' => Zume_Goals_Query::get_percent( $value, $trend ),
                    'negative_stat' => $negative_stat,
                ];
            default:
                return [
                    'key' => $params['key'],
                    'label' => '',
                    'description' => '',
                    'link' => '',
                    'value' => Zume_Goals_Query::format_int( $value ),
                    'valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal' => $goal,
                    'goal_valence' => Zume_Goals_Query::get_valence( $value, $goal, $negative_stat ),
                    'goal_percent' => Zume_Goals_Query::get_percent( $value, $goal ),
                    'trend' => $trend,
                    'trend_valence' => Zume_Goals_Query::get_valence( $value, $trend, $negative_stat ),
                    'trend_percent' => Zume_Goals_Query::get_percent( $value, $trend ),
                    'negative_stat' => $negative_stat,
                ];
        }

    }

    public function list( WP_REST_Request $request ) {
        return Zume_Goals_Query::list( dt_recursive_sanitize_array( $request->get_params() ) );
    }
    public function map( WP_REST_Request $request ) {
        $params =  dt_recursive_sanitize_array( $request->get_params() );
        return [ "link" => '<iframe class="map-iframe" width="100%" height="2500" src="https://zume.training/coaching/zume_app/heatmap_trainees" frameborder="0" style="border:0" allowfullscreen></iframe>' ];
    }



    public function location_goals() {
        $data = DT_Mapping_Module::instance()->data();

        $practitioners = $this->query_practitioner_funnel( ['4','5','6'] );
        $data = $this->add_column(  $data, 'practitioners', 'Practitioners', $practitioners );
        $data = $this->add_practitioners_goal_column( $data );

        $churches = $this->query_churches_funnel();
        $data = $this->add_column(  $data, 'churches', 'Churches', $churches );
        $data = $this->add_church_goal_column( $data );

        return $data;
    }
    public function query_practitioner_funnel( array $range ) {
        global $wpdb;
        if( count( $range ) > 1 ) {
            $range = '(' . implode( ',', $range ) . ')';
        } else {
            $range = '(' . $range[0] . ')';
        }

        //phpcs:disable
        $results = $wpdb->get_results( "
            SELECT t0.admin0_grid_id as grid_id, count(t0.admin0_grid_id) as count
            FROM (
                SELECT tb.grid_id, lg.admin0_grid_id, lg.admin1_grid_id, lg.admin2_grid_id, lg.admin3_grid_id
                FROM
                (
                   SELECT r.user_id, MAX(r.value) as stage, (
						SELECT grid_id FROM wp_dt_reports WHERE user_id = r.user_id AND grid_id IS NOT NULL ORDER BY id DESC LIMIT 1
					) as grid_id FROM wp_dt_reports r
                   WHERE r.type = 'stage' AND r.subtype = 'current_level'
                   GROUP BY r.user_id
                ) as tb
                LEFT JOIN wp_dt_location_grid lg ON lg.grid_id=tb.grid_id
                WHERE tb.stage IN $range
            ) as t0
            GROUP BY t0.admin0_grid_id
            UNION
            SELECT t1.admin1_grid_id as grid_id, count(t1.admin1_grid_id) as count
            FROM (
               SELECT tb.grid_id, lg.admin0_grid_id, lg.admin1_grid_id, lg.admin2_grid_id, lg.admin3_grid_id
                FROM
                (
                   SELECT r.user_id, MAX(r.value) as stage, (
						SELECT grid_id FROM wp_dt_reports WHERE user_id = r.user_id AND grid_id IS NOT NULL ORDER BY id DESC LIMIT 1
					) as grid_id FROM wp_dt_reports r
                   WHERE r.type = 'stage' AND r.subtype = 'current_level'
                   GROUP BY r.user_id
                ) as tb
                LEFT JOIN wp_dt_location_grid lg ON lg.grid_id=tb.grid_id
                WHERE tb.stage IN $range
            ) as t1
            GROUP BY t1.admin1_grid_id
            UNION
            SELECT  t2.admin2_grid_id as grid_id, count(t2.admin2_grid_id) as count
            FROM (
               SELECT tb.grid_id, lg.admin0_grid_id, lg.admin1_grid_id, lg.admin2_grid_id, lg.admin3_grid_id
                FROM
                (
                   SELECT r.user_id, MAX(r.value) as stage, (
						SELECT grid_id FROM wp_dt_reports WHERE user_id = r.user_id AND grid_id IS NOT NULL ORDER BY id DESC LIMIT 1
					) as grid_id FROM wp_dt_reports r
                   WHERE r.type = 'stage' AND r.subtype = 'current_level'
                   GROUP BY r.user_id
                ) as tb
                LEFT JOIN wp_dt_location_grid lg ON lg.grid_id=tb.grid_id
                WHERE tb.stage IN $range
            ) as t2
            GROUP BY t2.admin2_grid_id
            UNION
            SELECT t3.admin3_grid_id as grid_id, count(t3.admin3_grid_id) as count
            FROM (
                SELECT tb.grid_id, lg.admin0_grid_id, lg.admin1_grid_id, lg.admin2_grid_id, lg.admin3_grid_id
                FROM
                (
                   SELECT r.user_id, MAX(r.value) as stage, (
						SELECT grid_id FROM wp_dt_reports WHERE user_id = r.user_id AND grid_id IS NOT NULL ORDER BY id DESC LIMIT 1
					) as grid_id FROM wp_dt_reports r
                   WHERE r.type = 'stage' AND r.subtype = 'current_level'
                   GROUP BY r.user_id
                ) as tb
                LEFT JOIN wp_dt_location_grid lg ON lg.grid_id=tb.grid_id
                WHERE tb.stage IN $range
            ) as t3
            GROUP BY t3.admin3_grid_id;
            ", ARRAY_A );
        //phpcs:enable

        $list = [];
        if ( is_array( $results ) ) {
            foreach ( $results as $result ) {
                if ( empty( $result['grid_id'] ) ) {
                    continue;
                }
                $list[$result['grid_id']] = $result;
            }
        }

        return $list;
    }
    public function query_churches_funnel() {
        global $wpdb;

        //phpcs:disable
        $results = $wpdb->get_results( "
            SELECT t0.admin0_grid_id as grid_id, count(t0.admin0_grid_id) as count
            FROM (
                SELECT lg.admin0_grid_id, lg.admin1_grid_id, lg.admin2_grid_id, lg.admin3_grid_id
                FROM wp_dt_location_grid_meta lgm
                LEFT JOIN wp_postmeta pm ON pm.post_id=lgm.post_id AND pm.meta_key = 'group_type' AND pm.meta_value = 'church'
                LEFT JOIN wp_dt_location_grid lg ON lg.grid_id=lgm.grid_id
                WHERE lgm.post_type = 'groups'
            ) as t0
            GROUP BY t0.admin0_grid_id
            UNION
            SELECT t1.admin1_grid_id as grid_id, count(t1.admin1_grid_id) as count
            FROM (
                SELECT lg.admin0_grid_id, lg.admin1_grid_id, lg.admin2_grid_id, lg.admin3_grid_id
                FROM wp_dt_location_grid_meta lgm
                LEFT JOIN wp_postmeta pm ON pm.post_id=lgm.post_id AND pm.meta_key = 'group_type' AND pm.meta_value = 'church'
                LEFT JOIN wp_dt_location_grid lg ON lg.grid_id=lgm.grid_id
                WHERE lgm.post_type = 'groups'
            ) as t1
            GROUP BY t1.admin1_grid_id
            UNION
            SELECT t2.admin2_grid_id as grid_id, count(t2.admin2_grid_id) as count
            FROM (
                SELECT lg.admin0_grid_id, lg.admin1_grid_id, lg.admin2_grid_id, lg.admin3_grid_id
                FROM wp_dt_location_grid_meta lgm
                LEFT JOIN wp_postmeta pm ON pm.post_id=lgm.post_id AND pm.meta_key = 'group_type' AND pm.meta_value = 'church'
                LEFT JOIN wp_dt_location_grid lg ON lg.grid_id=lgm.grid_id
                WHERE lgm.post_type = 'groups'
            ) as t2
            GROUP BY t2.admin2_grid_id
            UNION
            SELECT t3.admin3_grid_id as grid_id, count(t3.admin3_grid_id) as count
            FROM (
                SELECT lg.admin0_grid_id, lg.admin1_grid_id, lg.admin2_grid_id, lg.admin3_grid_id
                FROM wp_dt_location_grid_meta lgm
                LEFT JOIN wp_postmeta pm ON pm.post_id=lgm.post_id AND pm.meta_key = 'group_type' AND pm.meta_value = 'church'
                LEFT JOIN wp_dt_location_grid lg ON lg.grid_id=lgm.grid_id
                WHERE lgm.post_type = 'groups'
            ) as t3
            GROUP BY t3.admin3_grid_id;
            ", ARRAY_A );
        //phpcs:enable

        $list = [];
        if ( is_array( $results ) ) {
            foreach ( $results as $result ) {
                if ( empty( $result['grid_id'] ) ) {
                    continue;
                }
                $list[$result['grid_id']] = $result;
            }
        }

        return $list;
    }
    public function add_column(  $data, $key, $label, $results = [] )
    {
        $column_labels = $data['custom_column_labels'] ?? [];
        $column_data = $data['custom_column_data'] ?? [];
        if (empty($column_labels)) {
            $next_column_number = 0;
        } else if (count($column_labels) === 1) {
            $next_column_number = 1;
        } else {
            $next_column_number = count($column_labels);
        }
        $column_labels[$next_column_number] = [
            'key' => $key,
            'label' => $label
        ];
        if (!empty($column_data)) {
            foreach ($column_data as $key => $value) {
                $column_data[$key][$next_column_number] = 0;
            }
        }

        if ( !empty($results) ) {
            foreach ($results as $result) {
                if ($result['count'] > 0) { // filter for only contact and positive counts
                    $grid_id = $result['grid_id'];

                    // test if grid_id exists, else prepare it with 0 values
                    if (!isset($column_data[$grid_id])) {
                        $column_data[$grid_id] = [];
                        $i = 0;
                        while ($i <= $next_column_number) {
                            $column_data[$grid_id][$i] = 0;
                            $i++;
                        }
                    }

                    // add new record to column
                    $column_data[$grid_id][$next_column_number] = (int)$result['count'] ?? 0; // must be string
                }
            }
        }
        $data['custom_column_labels'] = $column_labels;
        $data['custom_column_data']   = $column_data;
        return $data;
    }

    public function add_practitioners_goal_column( $data ) {
        global $wpdb;
        $column_labels = $data['custom_column_labels'] ?? [];
        $column_data   = $data['custom_column_data'] ?? [];
        if ( empty( $column_labels ) ) {
            $next_column_number = 0;
        } else if ( count( $column_labels ) === 1 ) {
            $next_column_number = 1;
        } else {
            $next_column_number = count( $column_labels );
        }
        $column_labels[ $next_column_number ] = [
            'key'   => 'practitioner_goal',
            'label' => __( 'Practitioner Goal', 'zume_funnels' )
        ];
        if ( ! empty( $column_data ) ) {
            foreach ( $column_data as $key => $value ) {
                $column_data[$key][$next_column_number] = 0;
            }
        }
        $results = $wpdb->get_results(
            "SELECT grid_id, population, country_code, 1 as count
                    FROM {$wpdb->prefix}dt_location_grid
                    WHERE population != '0'
                      AND population IS NOT NULL"
            , ARRAY_A );
        if ( ! empty( $results ) ) {
            foreach ( $results as $result ) {
                $grid_id = $result['grid_id'];
                if ( $result['country_code'] === 'US' ) {
                    $result['count'] = round( intval( $result['population'] ) / 5000 );
                } else {
                    $result['count'] = round( intval( $result['population'] ) / 50000 );
                }

                if ( ! isset( $column_data[ $grid_id ] ) ) {
                    $column_data[ $grid_id ] = [];
                    $i = 0;
                    while ( $i <= $next_column_number ) {
                        $column_data[$grid_id][$i] = 0;
                        $i ++;
                    }
                }

                if ( $result['count'] == 0 ) {
                    $result['count'] = 1;
                }

                $column_data[$grid_id][$next_column_number] = number_format( $result['count'] ); // must be string
            }
        }
        $data['custom_column_labels'] = $column_labels;
        $data['custom_column_data']   = $column_data;
        return $data;
    }
    public function add_church_goal_column( $data ) {
        global $wpdb;
        $column_labels = $data['custom_column_labels'] ?? [];
        $column_data   = $data['custom_column_data'] ?? [];
        if ( empty( $column_labels ) ) {
            $next_column_number = 0;
        } else if ( count( $column_labels ) === 1 ) {
            $next_column_number = 1;
        } else {
            $next_column_number = count( $column_labels );
        }
        $column_labels[ $next_column_number ] = [
            'key'   => 'church_goal',
            'label' => __( 'Church Goal', 'zume_funnels' )
        ];
        if ( ! empty( $column_data ) ) {
            foreach ( $column_data as $key => $value ) {
                $column_data[$key][$next_column_number] = 0;
            }
        }
        $results = $wpdb->get_results(
            "SELECT grid_id, population, country_code, 1 as count
                    FROM {$wpdb->prefix}dt_location_grid
                    WHERE population != '0'
                      AND population IS NOT NULL"
            , ARRAY_A );
        if ( ! empty( $results ) ) {
            foreach ( $results as $result ) {
                $grid_id = $result['grid_id'];
                if ( $result['country_code'] === 'US' ) {
                    $result['count'] = round( intval( $result['population'] ) / 2500 );
                } else {
                    $result['count'] = round( intval( $result['population'] ) / 25000 );
                }

                if ( ! isset( $column_data[ $grid_id ] ) ) {
                    $column_data[ $grid_id ] = [];
                    $i = 0;
                    while ( $i <= $next_column_number ) {
                        $column_data[$grid_id][$i] = 0;
                        $i ++;
                    }
                }

                if ( $result['count'] == 0 ) {
                    $result['count'] = 1;
                }

                $column_data[$grid_id][$next_column_number] = number_format( $result['count'] ); // must be string
            }
        }
        $data['custom_column_labels'] = $column_labels;
        $data['custom_column_data']   = $column_data;
        return $data;
    }

    public function has_permission(){
        $pass = false;
        foreach ( $this->permissions as $permission ){
            if ( current_user_can( $permission ) ){
                $pass = true;
            }
        }
        return $pass;
    }
    public function authorize_url( $authorized ){
        if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), $this->namespace  ) !== false ) {
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
Zume_Goals_Stats_Endpoints::instance();
