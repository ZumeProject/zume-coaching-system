<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Charts_API
{
    public $leadership_permissions = [ 'manage_dt' ];
    public $coach_permissions = [ 'access_contacts' ];
    public $namespace = 'zume_funnel/v1';
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
                    return $this->has_permission( $this->coach_permissions );
                },
            ]
        );
        register_rest_route(
            $namespace, '/location', [
                'methods'  => [ 'GET', 'POST' ],
                'callback' => [ $this, 'location' ],
                'permission_callback' => function () {
                    return $this->has_permission( $this->coach_permissions );
                },
            ]
        );
        register_rest_route(
            $namespace, '/map', [
                'methods'  => [ 'GET', 'POST' ],
                'callback' => [ $this, 'map_switcher' ],
                'permission_callback' => function () {
                    return $this->has_permission( $this->coach_permissions );
                },
            ]
        );
        register_rest_route(
            $namespace, '/map_list', [
                'methods'  => [ 'GET', 'POST' ],
                'callback' => [ $this, 'map_list_switcher' ],
                'permission_callback' => function () {
                    return $this->has_permission( $this->coach_permissions );
                },
            ]
        );
        register_rest_route(
            $namespace, '/list', [
                'methods'  => [ 'GET', 'POST' ],
                'callback' => [ $this, 'list' ],
                'permission_callback' => function () {
                    return $this->has_permission( $this->coach_permissions );
                },
            ]
        );
        register_rest_route(
            $namespace, '/training_elements', [
                'methods'  => [ 'GET', 'POST' ],
                'callback' => [ $this, 'training_elements' ],
                'permission_callback' => function () {
                    return $this->has_permission( $this->coach_permissions );
                },
            ]
        );
        register_rest_route(
            $this->namespace, '/location_funnel', [
                [
                    'methods'  => 'GET',
                    'callback' => [ $this, 'location_funnel' ],
                    'permission_callback' => function () {
                        return $this->has_permission( $this->coach_permissions );
                    },
                ],
            ]
        );
        register_rest_route(
            $this->namespace, '/location_goals', [
                [
                    'methods'  => 'GET',
                    'callback' => [ $this, 'location_goals' ],
                    'permission_callback' => function () {
                        return $this->has_permission( $this->coach_permissions );
                    },
                ],
            ]
        );
        register_rest_route(
            $this->namespace, '/mawl', [
                [
                    'methods'  => 'GET',
                    'callback' => [ $this, 'list_mawl' ],
                    'permission_callback' => function () {
                        return $this->has_permission( $this->coach_permissions );
                    },
                ],
            ]
        );
        register_rest_route(
            $this->namespace, '/mawl', [
                [
                    'methods'  => 'POST',
                    'callback' => [ $this, 'create_mawl' ],
                    'permission_callback' => function () {
                        return $this->has_permission( $this->coach_permissions );
                    },
                ],
            ]
        );
        register_rest_route(
            $this->namespace, '/mawl', [
                [
                    'methods'  => 'DELETE',
                    'callback' => [ $this, 'delete_mawl' ],
                    'permission_callback' => function () {
                        return $this->has_permission( $this->coach_permissions );
                    },
                ],
            ]
        );

//        register_rest_route(
//            $namespace, '/simulate', [
//                'methods'  => [ 'GET', 'POST' ],
//                'callback' => [ $this, 'training_elements' ],
//                'permission_callback' => function () {
//                    return $this->has_permission( $this->coach_permissions );
//                }
//            ]
//        );

        // dev
//        register_rest_route(
//            $namespace, '/sample', [
//                'methods'  => [ 'GET', 'POST' ],
//                'callback' => [ $this, 'sample' ],
//                'permission_callback' => function () {
//                    return $this->has_permission( $this->coach_permissions );
//                }
//            ]
//        );
    }
    public function has_permission( $permissions = [] ) {
        $pass = false;
        foreach ( $permissions as $permission ){
            if ( current_user_can( $permission ) ){
                $pass = true;
            }
        }
        return $pass;
    }

    /**
     * @param WP_REST_Request $request
     * @return array|WP_Error
     */
    public function total( WP_REST_Request $request ) {
        $params = dt_recursive_sanitize_array( $request->get_params() );
        if ( ! isset( $params['stage'] ) ) {
            return new WP_Error( 'no_stage', __( 'No stage key provided.', 'zume' ), array( 'status' => 400 ) );
        }

        if ( ! isset( $params['negative_stat'] ) ) {
            $params['negative_stat'] = false;
        }

        if ( ! isset( $params['range'] ) ) {
            $params['range'] = false;
        }

        switch ( $params['stage'] ) {
            case 'anonymous':
                return $this->total_anonymous( $params );
            case 'registrant':
                return $this->total_registrants( $params );
            case 'active_training_trainee':
                return $this->total_active_training_trainee( $params );
            case 'post_training_trainee':
                return $this->total_post_training_trainee( $params );
            case 'partial_practitioner':
                return $this->total_partial_practitioner( $params );
            case 'full_practitioner':
                return $this->total_full_practitioner( $params );
            case 'multiplying_practitioner':
                return $this->total_multiplying_practitioner( $params );
            case 'facilitator':
                return $this->total_facilitator( $params );
            case 'early':
                return $this->total_early( $params );
            case 'advanced':
                return $this->total_advanced( $params );
            case 'practitioners':
                return $this->total_practitioners( $params );
            case 'churches':
                return $this->total_churches( $params );
            default:
                return $this->general( $params );
        }
    }
    public function total_anonymous( $params ) {
        $negative_stat = $params['negative_stat'] ?? false;

        $label = '';
        $description = '';
        $link = '';
        $value = 0;
        $goal = 0;
        $trend = 0;
        $valence = null;

        switch ( $params['key'] ) {

            case 'total_registrations':
                $label = 'Total Registrations';
                $description = 'Total registrations over the entire history of the project';
                $link = '';
                $value = 0;
                $goal = 0;
                $trend = 0;
                $valence = 'valence-grey';
                break;
            case 'visitors':
                $label = 'Visitors';
                $description = 'Visitors to some content on the website (not including bounces).';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'registrations':
                $label = 'Registrations';
                $description = 'Total registrations to the system.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'coach_requests':
                $label = 'Coach Requests';
                $description = 'Responses to the "Request a Coach" CTA';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'joined_online_training':
                $label = 'Joined Online Training';
                $description = 'People who have responded the online training CTA';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'total_anonymous':
                $label = 'Anonymous';
                $description = 'Visitors who have meaningfully engaged with the site, but have not registered.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            default:
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;

        }

        return [
            'key' => $params['key'],
            'stage' => $params['stage'],
            'label' => $label,
            'description' => $description,
            'link' => $link,
            'value' => zume_format_int( $value ),
            'valence' => $valence ?? zume_get_valence( $value, $goal, $negative_stat ),
            'goal' => $goal,
            'goal_valence' => zume_get_valence( $value, $goal, $negative_stat ),
            'goal_percent' => zume_get_percent( $value, $goal ),
            'trend' => $trend,
            'trend_valence' => zume_get_valence( $value, $trend, $negative_stat ),
            'trend_percent' => zume_get_percent( $value, $trend ),
            'negative_stat' => $negative_stat,
        ];
    }
    public function total_registrants( $params ) {
        $negative_stat = $params['negative_stat'] ?? false;

        $label = '';
        $description = '';
        $link = '';
        $value = 0;
        $goal = 0;
        $trend = 0;
        $valence = null;
        $goal_valence = null;
        $trend_valence = null;

        switch ( $params['key'] ) {
            case 'locations':
                $label = 'Locations';
                $description = 'Cumulative number of locations in this stage.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                $valence = 'valence-grey';
                break;
            case 'countries':
                $label = 'Countries';
                $description = 'Cumulative number of countries in this stage.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                $valence = 'valence-grey';
                break;
            case 'new_registrations':
                $label = 'New Registrations';
                $description = 'Total number of registrants in this stage.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'coach_requests':

                // missing query by date range

                $label = 'Coach Requests';
                $description = 'Coach requests in this period of time';
                $value = 40;
                $goal = 30;
                $trend = 0;
                break;
            case 'has_plan':
                $label = 'Has Plan';
                $description = 'Total number of registrants who have a plan.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'no_plan':
                // lookup the query
                $label = 'Has No Plan';
                $description = 'Total number of registrants who have no plan.';
                $value = 2;
                $goal = 4;
                $trend = 4;
                $valence = null;
                break;
            case 'no_friends':
                $label = 'Has No Friends';
                $description = 'Total number of registrants who have not invited any friends.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'no_coach':
                $label = 'Has Not Requested a Coach';
                $description = 'Total number of registrants who have not requested a coach.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'no_updated_profile':
                $label = 'Has Not Updated Profile';
                $description = 'Total number of registrants who have not updated their profile.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;

            case 'total_registrants':
                $label = 'Registrants';
                $description = 'People who have registered but have not progressed into training.';
                $link = 'registrants';
                $value = Zume_Views::stage_totals( 1 );
                $goal = 0;
                $trend = 0;
                break;
            case 'in_and_out':
                return [
                    'key' => $params['key'],
                    'stage' => $params['stage'],
                    'label' => 'Flow',
                    'description' => 'People moving in and out of thise stage.',
                    'link' => '',
                    'value_in' => zume_format_int( 0 ),
                    'value_idle' => zume_format_int( 0 ),
                    'value_out' => zume_format_int( 0 ),
                ];

            default:
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
        }

        return [
            'key' => $params['key'],
            'stage' => $params['stage'],
            'label' => $label,
            'description' => $description,
            'link' => $link,
            'value' => zume_format_int( $value ),
            'valence' => $valence ?? zume_get_valence( $value, $goal, $negative_stat ),
            'goal' => $goal,
            'goal_valence' => $goal_valence ?? zume_get_valence( $value, $goal, $negative_stat ),
            'goal_percent' => zume_get_percent( $value, $goal ),
            'trend' => $trend,
            'trend_valence' => $trend_valence ?? zume_get_valence( $value, $trend, $negative_stat ),
            'trend_percent' => zume_get_percent( $value, $trend ),
            'negative_stat' => $negative_stat,
        ];
    }
    public function total_active_training_trainee( $params ) {
        $negative_stat = $params['negative_stat'] ?? false;

        $label = '';
        $description = '';
        $link = '';
        $value = 0;
        $goal = 0;
        $trend = 0;
        $valence = null;
        $goal_valence = null;
        $trend_valence = null;

        switch ( $params['key'] ) {
            case 'has_coach':
                $label = 'Has Coach';
                $description = 'Active trainees who have a coach.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                $valence = 'valence-grey';
                break;
            case 'inactive_trainees':
                $label = 'Inactive Trainees';
                $description = 'People who have been inactive more than 6 months.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                $valence = 'valence-grey';
                break;
            case 'new_active_trainees':
                $label = 'New Active Trainees';
                $description = 'New people who entered stage during time period.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'total_checkins':
                $label = 'Total Checkins';
                $description = 'Total number of checkins registered for training.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'has_no_coach':
                $label = 'Has No Coach';
                $description = 'People who have no coach.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'has_no_updated_profile':
                $label = 'No Updated Profile';
                $description = 'People who have not updated their profile.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                $valence = null;
                break;
            case 'in_and_out':
                return [
                    'key' => $params['key'],
                    'stage' => $params['stage'],
                    'label' => 'Flow',
                    'description' => 'People moving in and out of thise stage.',
                    'link' => '',
                    'value_in' => zume_format_int( 0 ),
                    'value_idle' => zume_format_int( 0 ),
                    'value_out' => zume_format_int( 0 ),
                ];
            case 'total_active_training_trainee':
                $label = 'Active Training Trainees';
                $description = 'People who are actively working a training plan or have only partially completed the training.';
                $value = Zume_Views::stage_totals( 2 );
                $goal = 0;
                $trend = 0;
                break;
            default:
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
        }

        return [
            'key' => $params['key'],
            'stage' => $params['stage'],
            'label' => $label,
            'description' => $description,
            'link' => $link,
            'value' => zume_format_int( $value ),
            'valence' => $valence ?? zume_get_valence( $value, $goal, $negative_stat ),
            'goal' => $goal,
            'goal_valence' => $goal_valence ?? zume_get_valence( $value, $goal, $negative_stat ),
            'goal_percent' => zume_get_percent( $value, $goal ),
            'trend' => $trend,
            'trend_valence' => $trend_valence ?? zume_get_valence( $value, $trend, $negative_stat ),
            'trend_percent' => zume_get_percent( $value, $trend ),
            'negative_stat' => $negative_stat,
        ];
    }
    public function total_post_training_trainee( $params ) {
        $negative_stat = $params['negative_stat'] ?? false;

        $label = '';
        $description = '';
        $link = '';
        $value = 0;
        $goal = 0;
        $trend = 0;
        $valence = null;
        $goal_valence = null;
        $trend_valence = null;

        switch ( $params['key'] ) {

            case 'needs_3_month_plan':
                $label = 'Needs 3 Month Plan';
                $description = 'Needs a 3 month plan.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                $valence = 'valence-grey';
                break;
            case 'needs_coach':
                $label = 'Needs Coach';
                $description = 'Needs a coach';
                $value = 0;
                $goal = 0;
                $trend = 0;
                $valence = 'valence-grey';
                break;
            case 'new_trainees':
                $label = 'New Trainees';
                $description = 'New trainees entering stage in time period.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'new_3_month_plans':
                $label = 'New Post Training Plans';
                $description = 'New Post Training Plans';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'new_coaching_requests':
                $label = 'New Coaching Requests';
                $description = 'New coaching requests during the time period.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'total_post_training_trainee':
                $label = 'Post-Training Trainees';
                $description = 'People who have completed the training and are working on a post training plan.';
                $link = 'post';
                $value = Zume_Views::stage_totals( 3 );
                $goal = 0;
                $trend = 0;
                break;
            case 'in_and_out':
                return [
                    'key' => $params['key'],
                    'stage' => $params['stage'],
                    'label' => 'Flow',
                    'description' => 'People moving in and out of thise stage.',
                    'link' => '',
                    'value_in' => zume_format_int( 0 ),
                    'value_idle' => zume_format_int( 0 ),
                    'value_out' => zume_format_int( 0 ),
                ];
            default:
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
        }

        return [
            'key' => $params['key'],
            'stage' => $params['stage'],
            'label' => $label,
            'description' => $description,
            'link' => $link,
            'value' => zume_format_int( $value ),
            'valence' => $valence ?? zume_get_valence( $value, $goal, $negative_stat ),
            'goal' => $goal,
            'goal_valence' => $goal_valence ?? zume_get_valence( $value, $goal, $negative_stat ),
            'goal_percent' => zume_get_percent( $value, $goal ),
            'trend' => $trend,
            'trend_valence' => $trend_valence ?? zume_get_valence( $value, $trend, $negative_stat ),
            'trend_percent' => zume_get_percent( $value, $trend ),
            'negative_stat' => $negative_stat,
        ];
    }
    public function total_partial_practitioner( $params ) {
        $negative_stat = $params['negative_stat'] ?? false;

        $label = '';
        $description = '';
        $link = '';
        $value = 0;
        $goal = 0;
        $trend = 0;
        $valence = null;
        $goal_valence = null;
        $trend_valence = null;

        switch ( $params['key'] ) {

            case 'total_churches';
                $label = 'Churches';
                $description = 'Total number of churches reported by S1 Practitioners.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                $valence = 'valence-grey';
                break;
            case 'total_locations';
                $label = 'Locations';
                $description = 'Total number of locations reported by S1 Practitioners.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                $valence = 'valence-grey';
                break;
            case 'total_active_reporters';
                $label = 'Reporting';
                $description = 'Total number of active reporters.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                $valence = 'valence-grey';
                break;
            case 'new_practitioners';
                $label = 'New Practitioners';
                $description = 'Total number of new practitioners.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'new_reporters';
                $label = 'New Reporters';
                $description = 'Total number of new reporters.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'new_churches';
                $label = 'New Churches';
                $description = 'Total number of new churches reported by S1 Practitioners.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'new_locations';
                $label = 'New Locations';
                $description = 'Total number of new locations reported by S1 Practitioners.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'has_no_coach';
                $label = 'Has No Coach';
                $description = 'Total number of S1 Practitioners who have not yet been assigned a coach.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'has_not_reported';
                $label = 'Has Not Reported';
                $description = 'Total number of S1 Practitioners who have not yet reported.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'total_partial_practitioner':
                $label = '(S1) Partial Practitioners';
                $description = 'Learning through doing. Implementing partial checklist / 4-fields';
                $link = 'partial_practitioner_practitioners';
                $value = Zume_Views::stage_totals( 4 );
                $goal = 0;
                $trend = 0;
                break;
            case 'in_and_out':
                return [
                    'key' => $params['key'],
                    'stage' => $params['stage'],
                    'label' => 'Flow',
                    'description' => 'People moving in and out of thise stage.',
                    'link' => '',
                    'value_in' => zume_format_int( 0 ),
                    'value_idle' => zume_format_int( 0 ),
                    'value_out' => zume_format_int( 0 ),
                ];
            default:
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
        }

        return [
            'key' => $params['key'],
            'stage' => $params['stage'],
            'label' => $label,
            'description' => $description,
            'link' => $link,
            'value' => zume_format_int( $value ),
            'valence' => $valence ?? zume_get_valence( $value, $goal, $negative_stat ),
            'goal' => $goal,
            'goal_valence' => $goal_valence ?? zume_get_valence( $value, $goal, $negative_stat ),
            'goal_percent' => zume_get_percent( $value, $goal ),
            'trend' => $trend,
            'trend_valence' => $trend_valence ?? zume_get_valence( $value, $trend, $negative_stat ),
            'trend_percent' => zume_get_percent( $value, $trend ),
            'negative_stat' => $negative_stat,
        ];
    }
    public function total_full_practitioner( $params ) {
        $negative_stat = $params['negative_stat'] ?? false;

        $label = '';
        $description = '';
        $link = '';
        $value = 0;
        $goal = 0;
        $trend = 0;
        $valence = null;
        $goal_valence = null;
        $trend_valence = null;

        switch ( $params['key'] ) {

            case 'total_churches';
                $label = 'Churches';
                $description = 'Total number of churches reported by S2 Practitioners.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'total_locations';
                $label = 'Locations';
                $description = 'Total number of locations reported by S2 Practitioners.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'total_active_reporters';
                $label = 'Active Reporters';
                $description = 'Total number of active reporters.';
                $link = 'partial_practitioner_practitioners';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'new_practitioners';
                $label = 'New Practitioners';
                $description = 'Total number of new practitioners.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'new_reporters';
                $label = 'New Reporters';
                $description = 'Total number of new reporters.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'new_churches';
                $label = 'New Churches';
                $description = 'Total number of new churches reported by S2 Practitioners.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'new_locations';
                $label = 'New Locations';
                $description = 'Total number of new locations reported by S2 Practitioners.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'has_no_coach';
                $label = 'Has No Coach';
                $description = 'Total number of S2 Practitioners who have not yet been assigned a coach.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'has_not_reported';
                $label = 'Has Not Reported';
                $description = 'Total number of S2 Practitioners who have not yet reported.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'total_full_practitioner':
                $label = 'Full Practitioners';
                $description = 'People who are seeking multiplicative movement and are completely skilled with the coaching checklist.';
                $link = 'full_practitioner_practitioners';
                $value = Zume_Views::stage_totals( 5 );
                $goal = 5;
                $trend = rand( 1, 10 );
                break;
            case 'in_and_out':
                return [
                    'key' => $params['key'],
                    'stage' => $params['stage'],
                    'label' => 'Flow',
                    'description' => 'People moving in and out of thise stage.',
                    'link' => '',
                    'value_in' => zume_format_int( 0 ),
                    'value_idle' => zume_format_int( 0 ),
                    'value_out' => zume_format_int( 0 ),
                ];
            default:
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
        }

        return [
            'key' => $params['key'],
            'stage' => $params['stage'],
            'label' => $label,
            'description' => $description,
            'link' => $link,
            'value' => zume_format_int( $value ),
            'valence' => $valence ?? zume_get_valence( $value, $goal, $negative_stat ),
            'goal' => $goal,
            'goal_valence' => $goal_valence ?? zume_get_valence( $value, $goal, $negative_stat ),
            'goal_percent' => zume_get_percent( $value, $goal ),
            'trend' => $trend,
            'trend_valence' => $trend_valence ?? zume_get_valence( $value, $trend, $negative_stat ),
            'trend_percent' => zume_get_percent( $value, $trend ),
            'negative_stat' => $negative_stat,
        ];
    }
    public function total_multiplying_practitioner( $params ) {
        $negative_stat = $params['negative_stat'] ?? false;

        $label = '';
        $description = '';
        $link = '';
        $value = 0;
        $goal = 0;
        $trend = 0;
        $valence = null;
        $goal_valence = null;
        $trend_valence = null;

        switch ( $params['key'] ) {
            case 'total_churches';
                $label = 'Churches';
                $description = 'Total number of churches reported by S2 Practitioners.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'total_locations';
                $label = 'Locations';
                $description = 'Total number of locations reported by S2 Practitioners.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'total_active_reporters';
                $label = 'Active Reporters';
                $description = 'Total number of active reporters.';
                $link = 'partial_practitioner_practitioners';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'new_practitioners';
                $label = 'New Practitioners';
                $description = 'Total number of new practitioners.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'new_reporters';
                $label = 'New Reporters';
                $description = 'Total number of new reporters.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'new_churches';
                $label = 'New Churches';
                $description = 'Total number of new churches reported by S2 Practitioners.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'new_locations';
                $label = 'New Locations';
                $description = 'Total number of new locations reported by S2 Practitioners.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'has_no_coach';
                $label = 'Has No Coach';
                $description = 'Total number of S2 Practitioners who have not yet been assigned a coach.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'has_not_reported';
                $label = 'Has Not Reported';
                $description = 'Total number of S2 Practitioners who have not yet reported.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'total_multiplying_practitioner':
                $label = 'Multiplying Practitioners';
                $description = 'People who are seeking multiplicative movement and are stewarding generational fruit.';
                $link = 'multiplying_practitioner_practitioners';
                $value = Zume_Views::stage_totals( 6 );
                $goal = 0;
                $trend = 0;
                break;
            case 'in_and_out':
                return [
                    'key' => $params['key'],
                    'stage' => $params['stage'],
                    'label' => 'Flow',
                    'description' => 'People moving in and out of thise stage.',
                    'link' => '',
                    'value_in' => zume_format_int( 0 ),
                    'value_idle' => zume_format_int( 0 ),
                    'value_out' => zume_format_int( 0 ),
                ];
            default:
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
        }

        return [
            'key' => $params['key'],
            'stage' => $params['stage'],
            'label' => $label,
            'description' => $description,
            'link' => $link,
            'value' => zume_format_int( $value ),
            'valence' => $valence ?? zume_get_valence( $value, $goal, $negative_stat ),
            'goal' => $goal,
            'goal_valence' => $goal_valence ?? zume_get_valence( $value, $goal, $negative_stat ),
            'goal_percent' => zume_get_percent( $value, $goal ),
            'trend' => $trend,
            'trend_valence' => $trend_valence ?? zume_get_valence( $value, $trend, $negative_stat ),
            'trend_percent' => zume_get_percent( $value, $trend ),
            'negative_stat' => $negative_stat,
        ];
    }
    public function total_facilitator( $params ) {
        $negative_stat = $params['negative_stat'] ?? false;

        $label = '';
        $description = '';
        $link = '';
        $value = 0;
        $goal = 0;
        $trend = 0;
        $valence = null;
        $goal_valence = null;
        $trend_valence = null;

        switch ( $params['key'] ) {

            case 'new_coaching_requests';
                $label = 'New Coaching Requests';
                $description = 'Total number of new coaching requests submitted to Facilitator Coaches.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                $valence = 'valence-grey';
                break;
            case 'languages';
                $label = 'Languages';
                $description = 'Number of languages from requests';
                $value = 0;
                $goal = 0;
                $trend = 0;
                $valence = 'valence-grey';
                break;
            case 'locations';
                $label = 'Locations';
                $description = 'Locations from requests.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                $valence = 'valence-grey';
                break;
            default:
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
        }

        return [
            'key' => $params['key'],
            'stage' => $params['stage'],
            'label' => $label,
            'description' => $description,
            'link' => $link,
            'value' => zume_format_int( $value ),
            'valence' => $valence ?? zume_get_valence( $value, $goal, $negative_stat ),
            'goal' => $goal,
            'goal_valence' => $goal_valence ?? zume_get_valence( $value, $goal, $negative_stat ),
            'goal_percent' => zume_get_percent( $value, $goal ),
            'trend' => $trend,
            'trend_valence' => $trend_valence ?? zume_get_valence( $value, $trend, $negative_stat ),
            'trend_percent' => zume_get_percent( $value, $trend ),
            'negative_stat' => $negative_stat,
        ];
    }
    public function total_early( $params ) {
        $negative_stat = $params['negative_stat'] ?? false;

        $label = '';
        $description = '';
        $link = '';
        $value = 0;
        $goal = 0;
        $trend = 0;
        $valence = null;
        $goal_valence = null;
        $trend_valence = null;


        switch ( $params['key'] ) {
            case 'new_coaching_requests';
                $label = 'Languages';
                $description = '';
                $value = 0;
                $goal = 0;
                $trend = 0;
                $valence = 'valence-grey';
                break;
            case 'languages';
                $label = 'New Coaching Requests';
                $description = 'Number of languages from requests';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'locations';
                $label = 'Locations';
                $description = 'Locations from requests.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                $valence = 'valence-grey';
                break;
            case 'total_multiplying_practitioner';
            default:
                $label = '(S3) Multiplying Practitioners';
                $description = 'People who are seeking multiplicative movement and are stewarding generational fruit.';
                $link = 'multiplying_practitioner_practitioners';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
        }

        return [
            'key' => $params['key'],
            'stage' => $params['stage'],
            'label' => $label,
            'description' => $description,
            'link' => $link,
            'value' => zume_format_int( $value ),
            'valence' => $valence ?? zume_get_valence( $value, $goal, $negative_stat ),
            'goal' => $goal,
            'goal_valence' => $goal_valence ?? zume_get_valence( $value, $goal, $negative_stat ),
            'goal_percent' => zume_get_percent( $value, $goal ),
            'trend' => $trend,
            'trend_valence' => $trend_valence ?? zume_get_valence( $value, $trend, $negative_stat ),
            'trend_percent' => zume_get_percent( $value, $trend ),
            'negative_stat' => $negative_stat,
        ];
    }
    public function total_advanced( $params ) {
        $negative_stat = $params['negative_stat'] ?? false;

        $label = '';
        $description = '';
        $link = '';
        $value = 0;
        $goal = 0;
        $trend = 0;
        $valence = null;
        $goal_valence = null;
        $trend_valence = null;

        switch ( $params['key'] ) {
            case 'total_churches';
                $label = 'Total Churches';
                $description = 'Total number of churches reported by S2 Practitioners.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'total_locations';
                $label = 'Total Locations';
                $description = 'Total number of locations reported by S2 Practitioners.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'total_active_reporters';
                $label = 'Total Active Reporters';
                $description = 'Total number of active reporters.';
                $link = 'partial_practitioner_practitioners';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'new_practitioners';
                $label = 'New Practitioners';
                $description = 'Total number of new practitioners.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'new_reporters';
                $label = 'New Reporters';
                $description = 'Total number of new reporters.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'new_churches';
                $label = 'New Churches';
                $description = 'Total number of new churches reported by S2 Practitioners.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'new_locations';
                $label = 'New Locations';
                $description = 'Total number of new locations reported by S2 Practitioners.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'has_no_coach';
                $label = 'Has No Coach';
                $description = 'Total number of S2 Practitioners who have not yet been assigned a coach.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'has_not_reported';
                $label = 'Has Not Reported';
                $description = 'Total number of S2 Practitioners who have not yet reported.';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            case 'total_multiplying_practitioner':
                $label = '(S3) Multiplying Practitioners';
                $description = 'People who are seeking multiplicative movement and are stewarding generational fruit.';
                $link = 'multiplying_practitioner_practitioners';
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
            default:
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
        }

        return [
            'key' => $params['key'],
            'stage' => $params['stage'],
            'label' => $label,
            'description' => $description,
            'link' => $link,
            'value' => zume_format_int( $value ),
            'valence' => $valence ?? zume_get_valence( $value, $goal, $negative_stat ),
            'goal' => $goal,
            'goal_valence' => $goal_valence ?? zume_get_valence( $value, $goal, $negative_stat ),
            'goal_percent' => zume_get_percent( $value, $goal ),
            'trend' => $trend,
            'trend_valence' => $trend_valence ?? zume_get_valence( $value, $trend, $negative_stat ),
            'trend_percent' => zume_get_percent( $value, $trend ),
            'negative_stat' => $negative_stat,
        ];
    }
    public function general( $params ) {
        $negative_stat = $params['negative_stat'] ?? false;

        $label = '';
        $description = '';
        $link = '';
        $value = 0;
        $goal = 0;
        $trend = 0;
        $valence = null;
        $goal_valence = null;
        $trend_valence = null;

        switch ( $params['key'] ) {

            case 'active_coaches';
                $label = 'Active Coaches';
                $description = 'Number of active coaches';
                $value = 0;
                $goal = 0;
                $trend = 0;
                $valence = 'valence-grey';
                break;
            case 'total_people_in_coaching';
                $label = 'People in Coaching';
                $description = 'Number of people in coaching';
                $value = 0;
                $goal = 0;
                $trend = 0;
                $valence = 'valence-grey';
                break;
            case 'people_in_coaching';
                $label = 'People in Coaching';
                $description = 'Number of people in coaching';
                $value = 0;
                $goal = 0;
                $trend = 0;
                $valence = 'valence-grey';
                break;
            case 'coaching_engagements';
                $label = 'Coaching Engagements';
                $description = 'Number of coaching engagements';
                $value = 0;
                $goal = 0;
                $trend = 0;
                $valence = 'valence-grey';
                break;
            case 'in_and_out':
                return [
                    'key' => $params['key'],
                    'label' => '',
                    'description' => 'Description',
                    'link' => '',
                    'value_in' => zume_format_int( 0 ),
                    'value_idle' => zume_format_int( 0 ),
                    'value_out' => zume_format_int( 0 ),
                ];
            default:
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;
        }

        return [
            'key' => $params['key'],
            'stage' => $params['stage'],
            'label' => $label,
            'description' => $description,
            'link' => $link,
            'value' => zume_format_int( $value ),
            'valence' => $valence ?? zume_get_valence( $value, $goal, $negative_stat ),
            'goal' => $goal,
            'goal_valence' => $goal_valence ?? zume_get_valence( $value, $goal, $negative_stat ),
            'goal_percent' => zume_get_percent( $value, $goal ),
            'trend' => $trend,
            'trend_valence' => $trend_valence ?? zume_get_valence( $value, $trend, $negative_stat ),
            'trend_percent' => zume_get_percent( $value, $trend ),
            'negative_stat' => $negative_stat,
        ];
    }
    public function total_practitioners( $params ) {
        $negative_stat = $params['negative_stat'] ?? false;

        $label = '';
        $description = '';
        $link = '';
        $value = 0;
        $goal = 0;
        $trend = 0;
        $valence = null;

        switch ( $params['key'] ) {

            case 'churches_total':
                $label = 'Total Registrations';
                $description = 'People who are seeking multiplicative movement and are stewarding generational fruit.';
                $value = Zume_Queries::query_total_churches();
                $goal = 0;
                $trend = 0;
                ;
                $valence = 'valence-grey';
                break;
            case 'practitioners_total':
                $label = 'Visitors';
                $description = 'People who are seeking multiplicative movement and are stewarding generational fruit.';
                $value = Zume_Queries::query_total_practitioners();
                $goal = 0;
                $trend = 0;
                $valence = 'valence-grey';
                break;
            default:
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;

        }

        return [
            'key' => $params['key'],
            'stage' => $params['stage'],
            'label' => $label,
            'description' => $description,
            'link' => $link,
            'value' => zume_format_int( $value ),
            'valence' => $valence ?? zume_get_valence( $value, $goal, $negative_stat ),
            'goal' => $goal,
            'goal_valence' => zume_get_valence( $value, $goal, $negative_stat ),
            'goal_percent' => zume_get_percent( $value, $goal ),
            'trend' => $trend,
            'trend_valence' => zume_get_valence( $value, $trend, $negative_stat ),
            'trend_percent' => zume_get_percent( $value, $trend ),
            'negative_stat' => $negative_stat,
        ];
    }

    public function total_churches( $params ) {
        $negative_stat = $params['negative_stat'] ?? false;

        $label = '';
        $description = '';
        $link = '';
        $value = 0;
        $goal = 0;
        $trend = 0;
        $valence = null;

        switch ( $params['key'] ) {

            case 'churches_total':
                $label = 'Total Registrations';
                $description = 'People who are seeking multiplicative movement and are stewarding generational fruit.';
                $value = Zume_Queries::query_total_churches();
                $goal = 0;
                $trend = 0;
                ;
                $valence = 'valence-grey';
                break;
            case 'practitioners_total':
                $label = 'Visitors';
                $description = 'People who are seeking multiplicative movement and are stewarding generational fruit.';
                $value = Zume_Queries::query_total_practitioners();
                $goal = 0;
                $trend = 0;
                $valence = 'valence-grey';
                break;
            default:
                $value = 0;
                $goal = 0;
                $trend = 0;
                break;

        }

        return [
            'key' => $params['key'],
            'stage' => $params['stage'],
            'label' => $label,
            'description' => $description,
            'link' => $link,
            'value' => zume_format_int( $value ),
            'valence' => $valence ?? zume_get_valence( $value, $goal, $negative_stat ),
            'goal' => $goal,
            'goal_valence' => zume_get_valence( $value, $goal, $negative_stat ),
            'goal_percent' => zume_get_percent( $value, $goal ),
            'trend' => $trend,
            'trend_valence' => zume_get_valence( $value, $trend, $negative_stat ),
            'trend_percent' => zume_get_percent( $value, $trend ),
            'negative_stat' => $negative_stat,
        ];
    }

    public function location_funnel() {
        $data = DT_Mapping_Module::instance()->data();
        $funnel = zume_funnel_stages();

        $data = $this->add_column( $data, $funnel['1']['key'], $funnel['1']['label'], [ '1' ] );
        $data = $this->add_column( $data, $funnel['2']['key'], $funnel['2']['label'], [ '2' ] );
        $data = $this->add_column( $data, $funnel['3']['key'], $funnel['3']['label'], [ '3' ] );
        $data = $this->add_column( $data, $funnel['4']['key'], $funnel['4']['label'], [ '4' ] );
        $data = $this->add_column( $data, $funnel['5']['key'], $funnel['5']['label'], [ '5' ] );
        $data = $this->add_column( $data, $funnel['6']['key'], $funnel['6']['label'], [ '6' ] );

        return $data;
    }

    public function list( WP_REST_Request $request ) {
        return Zume_Goals_Query::list( dt_recursive_sanitize_array( $request->get_params() ) );
    }


    public function map_switcher( WP_REST_Request $request ) {
        $params = dt_recursive_sanitize_array( $request->get_params() );
        if ( ! isset( $params['stage'] ) ) {
            return new WP_Error( 'no_stage', __( 'No stage key provided.', 'zume' ), array( 'status' => 400 ) );
        }

        switch ( $params['stage'] ) {
            case 'anonymous':
                return $this->map_geojson( [ 0 ] );
            case 'registrant':
                return $this->map_geojson( [ 1 ] );
            case 'active_training_trainee':
                return $this->map_geojson( [ 2 ] );
            case 'post_training_trainee':
                return $this->map_geojson( [ 3 ] );
            case 'partial_practitioner':
                return $this->map_geojson( [ 4 ] );
            case 'full_practitioner':
                return $this->map_geojson( [ 5 ] );
            case 'multiplying_practitioner':
                return $this->map_geojson( [ 6 ] );
            case 'trainees':
                return $this->map_geojson( [ 1,2,3 ] );
            case 'practitioners':
                return $this->map_geojson( [ 4,5,6 ] );
            case 'churches':
                return $this->map_churches_geojson( 6 );
//            case 'facilitator':
//                return $this->total_facilitator( $params );
//            case 'early':
//                return $this->total_early( $params );
//            case 'advanced':
//                return $this->total_advanced( $params );
            default:
                return $this->general( $params );
        }
    }

    public function map_geojson( array $stage ) {
        $results = Zume_Queries::stage_by_location( $stage );

        $features = [];
        foreach ( $results as $result ) {

            $lat = $result['lat'];
            $lng = $result['lng'];

            $features[] = array(
                'type' => 'Feature',
                'properties' => [
                    'name' => $result['name'],
                    'post_id' => $result['post_id'],
                    'post_type' => 'contacts',
                ],
                'geometry' => array(
                    'type' => 'Point',
                    'coordinates' => array(
                        (float) $lng,
                        (float) $lat,
                        1,
                    ),
                ),
            );
        }

        $new_data = array(
            'stage' => $stage,
            'type' => 'FeatureCollection',
            'features' => $features,
        );

        return $new_data;
    }

    public function map_churches_geojson() {
        global $wpdb;
        $results = Zume_Queries::churches_with_location();

        $features = [];
        foreach ( $results as $result ) {

            $lat = $result['lat'];
            $lng = $result['lng'];

            $features[] = array(
                'type' => 'Feature',
                'properties' => [
                    'name' => $result['name'],
                    'post_id' => $result['post_id'],
                    'post_type' => 'groups',
                ],
                'geometry' => array(
                    'type' => 'Point',
                    'coordinates' => array(
                        (float) $lng,
                        (float) $lat,
                        1,
                    ),
                ),
            );
        }

        $new_data = array(
            'type' => 'FeatureCollection',
            'features' => $features,
        );

        return $new_data;
    }

    public function map_list_switcher( WP_REST_Request $request ) {
        $params = dt_recursive_sanitize_array( $request->get_params() );
        if ( ! isset( $params['stage'], $params['north'], $params['south'], $params['east'], $params['west'] ) ) {
            return new WP_Error( 'no_stage', __( 'No stage key or complete boundaries provided.', 'zume' ), array( 'status' => 400 ) );
        }
        $params['north'] = (float) $params['north'];
        $params['south'] = (float) $params['south'];
        $params['east'] = (float) $params['east'];
        $params['west'] = (float) $params['west'];

        switch ( $params['stage'] ) {
            case 'anonymous':
                return Zume_Queries::stage_by_boundary( [ 0 ], $params['north'], $params['south'], $params['east'], $params['west'] );
            case 'registrant':
                return Zume_Queries::stage_by_boundary( [ 1 ], $params['north'], $params['south'], $params['east'], $params['west'] );
            case 'active_training_trainee':
                return Zume_Queries::stage_by_boundary( [ 2 ], $params['north'], $params['south'], $params['east'], $params['west'] );
            case 'post_training_trainee':
                return Zume_Queries::stage_by_boundary( [ 3 ], $params['north'], $params['south'], $params['east'], $params['west'] );
            case 'partial_practitioner':
                return Zume_Queries::stage_by_boundary( [ 4 ], $params['north'], $params['south'], $params['east'], $params['west'] );
            case 'full_practitioner':
                return Zume_Queries::stage_by_boundary( [ 5 ], $params['north'], $params['south'], $params['east'], $params['west'] );
            case 'multiplying_practitioner':
                return Zume_Queries::stage_by_boundary( [ 6 ], $params['north'], $params['south'], $params['east'], $params['west'] );
            case 'practitioners':
                return Zume_Queries::stage_by_boundary( [ 4,5,6 ], $params['north'], $params['south'], $params['east'], $params['west'] );
            case 'churches':
                return Zume_Queries::churches_by_boundary( $params['north'], $params['south'], $params['east'], $params['west'] );
//            case 'facilitator':
//                return $this->total_facilitator( $params );
//            case 'early':
//                return $this->total_early( $params );
//            case 'advanced':
//                return $this->total_advanced( $params );
            default:
                return $this->general( $params );
        }
    }

    public function query_location_funnel( array $range ) {
        global $wpdb;
        if ( count( $range ) > 1 ) {
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
						SELECT grid_id FROM zume_dt_reports WHERE user_id = r.user_id AND grid_id IS NOT NULL ORDER BY id DESC LIMIT 1
					) as grid_id FROM zume_dt_reports r
                   WHERE r.type = 'stage' AND r.subtype = 'current_level'
                   GROUP BY r.user_id
                ) as tb
                LEFT JOIN zume_dt_location_grid lg ON lg.grid_id=tb.grid_id
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
						SELECT grid_id FROM zume_dt_reports WHERE user_id = r.user_id AND grid_id IS NOT NULL ORDER BY id DESC LIMIT 1
					) as grid_id FROM zume_dt_reports r
                   WHERE r.type = 'stage' AND r.subtype = 'current_level'
                   GROUP BY r.user_id
                ) as tb
                LEFT JOIN zume_dt_location_grid lg ON lg.grid_id=tb.grid_id
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
						SELECT grid_id FROM zume_dt_reports WHERE user_id = r.user_id AND grid_id IS NOT NULL ORDER BY id DESC LIMIT 1
					) as grid_id FROM zume_dt_reports r
                   WHERE r.type = 'stage' AND r.subtype = 'current_level'
                   GROUP BY r.user_id
                ) as tb
                LEFT JOIN zume_dt_location_grid lg ON lg.grid_id=tb.grid_id
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
						SELECT grid_id FROM zume_dt_reports WHERE user_id = r.user_id AND grid_id IS NOT NULL ORDER BY id DESC LIMIT 1
					) as grid_id FROM zume_dt_reports r
                   WHERE r.type = 'stage' AND r.subtype = 'current_level'
                   GROUP BY r.user_id
                ) as tb
                LEFT JOIN zume_dt_location_grid lg ON lg.grid_id=tb.grid_id
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
    public function add_column( $data, $key, $label, $stage )
    {
        $column_labels = $data['custom_column_labels'] ?? [];
        $column_data = $data['custom_column_data'] ?? [];
        if ( empty( $column_labels ) ) {
            $next_column_number = 0;
        } else if ( count( $column_labels ) === 1 ) {
            $next_column_number = 1;
        } else {
            $next_column_number = count( $column_labels );
        }
        $column_labels[$next_column_number] = [
            'key' => $key,
            'label' => $label,
        ];
        if ( !empty( $column_data ) ) {
            foreach ( $column_data as $key => $value ) {
                $column_data[$key][$next_column_number] = 0;
            }
        }
        $results = $this->query_location_funnel( $stage );
        if ( !empty( $results ) ) {
            foreach ( $results as $result ) {
                if ( $result['count'] > 0 ) { // filter for only contact and positive counts
                    $grid_id = $result['grid_id'];

                    // test if grid_id exists, else prepare it with 0 values
                    if ( !isset( $column_data[$grid_id] ) ) {
                        $column_data[$grid_id] = [];
                        $i = 0;
                        while ( $i <= $next_column_number ) {
                            $column_data[$grid_id][$i] = 0;
                            $i++;
                        }
                    }

                    // add new record to column
                    $column_data[$grid_id][$next_column_number] = (int) $result['count'] ?? 0; // must be string
                }
            }
        }
        $data['custom_column_labels'] = $column_labels;
        $data['custom_column_data']   = $column_data;
        return $data;
    }

    public function location( WP_REST_Request $request ) {
        return DT_Ipstack_API::get_location_grid_meta_from_current_visitor();
    }
    public function training_elements( WP_REST_Request $request ) {
        return Zume_Views::training_elements( dt_recursive_sanitize_array( $request->get_params() ) );
    }

    // dev
//    public function sample( WP_REST_Request $request ) {
//        return Zume_Views::sample( dt_recursive_sanitize_array( $request->get_params() ) );
//    }

    public function location_goals() {
        $data = DT_Mapping_Module::instance()->data();

        $practitioners = $this->query_practitioner_funnel( [ '4','5','6' ] );
        $data = $this->add_goals_column( $data, 'practitioners', 'Practitioners', $practitioners );
        $data = $this->add_practitioners_goal_column( $data );

        $churches = $this->query_churches_funnel();
        $data = $this->add_goals_column( $data, 'churches', 'Churches', $churches );
        $data = $this->add_church_goal_column( $data );

        return $data;
    }
    public function query_practitioner_funnel( array $range ) {
        global $wpdb;
        if ( count( $range ) > 1 ) {
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
						SELECT grid_id FROM zume_dt_reports WHERE user_id = r.user_id AND grid_id IS NOT NULL ORDER BY id DESC LIMIT 1
					) as grid_id FROM zume_dt_reports r
                   WHERE r.type = 'stage' AND r.subtype = 'current_level'
                   GROUP BY r.user_id
                ) as tb
                LEFT JOIN zume_dt_location_grid lg ON lg.grid_id=tb.grid_id
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
						SELECT grid_id FROM zume_dt_reports WHERE user_id = r.user_id AND grid_id IS NOT NULL ORDER BY id DESC LIMIT 1
					) as grid_id FROM zume_dt_reports r
                   WHERE r.type = 'stage' AND r.subtype = 'current_level'
                   GROUP BY r.user_id
                ) as tb
                LEFT JOIN zume_dt_location_grid lg ON lg.grid_id=tb.grid_id
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
						SELECT grid_id FROM zume_dt_reports WHERE user_id = r.user_id AND grid_id IS NOT NULL ORDER BY id DESC LIMIT 1
					) as grid_id FROM zume_dt_reports r
                   WHERE r.type = 'stage' AND r.subtype = 'current_level'
                   GROUP BY r.user_id
                ) as tb
                LEFT JOIN zume_dt_location_grid lg ON lg.grid_id=tb.grid_id
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
						SELECT grid_id FROM zume_dt_reports WHERE user_id = r.user_id AND grid_id IS NOT NULL ORDER BY id DESC LIMIT 1
					) as grid_id FROM zume_dt_reports r
                   WHERE r.type = 'stage' AND r.subtype = 'current_level'
                   GROUP BY r.user_id
                ) as tb
                LEFT JOIN zume_dt_location_grid lg ON lg.grid_id=tb.grid_id
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
                FROM zume_dt_location_grid_meta lgm
                LEFT JOIN zume_postmeta pm ON pm.post_id=lgm.post_id AND pm.meta_key = 'group_type' AND pm.meta_value = 'church'
                LEFT JOIN zume_dt_location_grid lg ON lg.grid_id=lgm.grid_id
                WHERE lgm.post_type = 'groups'
            ) as t0
            GROUP BY t0.admin0_grid_id
            UNION
            SELECT t1.admin1_grid_id as grid_id, count(t1.admin1_grid_id) as count
            FROM (
                SELECT lg.admin0_grid_id, lg.admin1_grid_id, lg.admin2_grid_id, lg.admin3_grid_id
                FROM zume_dt_location_grid_meta lgm
                LEFT JOIN zume_postmeta pm ON pm.post_id=lgm.post_id AND pm.meta_key = 'group_type' AND pm.meta_value = 'church'
                LEFT JOIN zume_dt_location_grid lg ON lg.grid_id=lgm.grid_id
                WHERE lgm.post_type = 'groups'
            ) as t1
            GROUP BY t1.admin1_grid_id
            UNION
            SELECT t2.admin2_grid_id as grid_id, count(t2.admin2_grid_id) as count
            FROM (
                SELECT lg.admin0_grid_id, lg.admin1_grid_id, lg.admin2_grid_id, lg.admin3_grid_id
                FROM zume_dt_location_grid_meta lgm
                LEFT JOIN zume_postmeta pm ON pm.post_id=lgm.post_id AND pm.meta_key = 'group_type' AND pm.meta_value = 'church'
                LEFT JOIN zume_dt_location_grid lg ON lg.grid_id=lgm.grid_id
                WHERE lgm.post_type = 'groups'
            ) as t2
            GROUP BY t2.admin2_grid_id
            UNION
            SELECT t3.admin3_grid_id as grid_id, count(t3.admin3_grid_id) as count
            FROM (
                SELECT lg.admin0_grid_id, lg.admin1_grid_id, lg.admin2_grid_id, lg.admin3_grid_id
                FROM zume_dt_location_grid_meta lgm
                LEFT JOIN zume_postmeta pm ON pm.post_id=lgm.post_id AND pm.meta_key = 'group_type' AND pm.meta_value = 'church'
                LEFT JOIN zume_dt_location_grid lg ON lg.grid_id=lgm.grid_id
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
    public function add_goals_column( $data, $key, $label, $results = [] )
    {
        $column_labels = $data['custom_column_labels'] ?? [];
        $column_data = $data['custom_column_data'] ?? [];
        if ( empty( $column_labels ) ) {
            $next_column_number = 0;
        } else if ( count( $column_labels ) === 1 ) {
            $next_column_number = 1;
        } else {
            $next_column_number = count( $column_labels );
        }
        $column_labels[$next_column_number] = [
            'key' => $key,
            'label' => $label,
        ];
        if ( !empty( $column_data ) ) {
            foreach ( $column_data as $key => $value ) {
                $column_data[$key][$next_column_number] = 0;
            }
        }

        if ( !empty( $results ) ) {
            foreach ( $results as $result ) {
                if ( $result['count'] > 0 ) { // filter for only contact and positive counts
                    $grid_id = $result['grid_id'];

                    // test if grid_id exists, else prepare it with 0 values
                    if ( !isset( $column_data[$grid_id] ) ) {
                        $column_data[$grid_id] = [];
                        $i = 0;
                        while ( $i <= $next_column_number ) {
                            $column_data[$grid_id][$i] = 0;
                            $i++;
                        }
                    }

                    // add new record to column
                    $column_data[$grid_id][$next_column_number] = (int) $result['count'] ?? 0; // must be string
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
            'label' => __( 'Practitioner Goal', 'zume_funnels' ),
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
                      AND population IS NOT NULL",
        ARRAY_A );
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
                        $i++;
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
            'label' => __( 'Church Goal', 'zume_funnels' ),
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
                      AND population IS NOT NULL",
        ARRAY_A );
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
                        $i++;
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
Zume_Charts_API::instance();
