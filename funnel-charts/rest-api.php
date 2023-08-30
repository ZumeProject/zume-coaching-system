<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Stats_Endpoints
{
    public $permissions = ['manage_dt'];
    public $namespace = 'zume_funnel/v1';
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
                'callback' => [ $this, 'map_switcher' ],
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
        register_rest_route(
            $namespace, '/training_elements', [
                'methods'  => [ 'GET', 'POST' ],
                'callback' => [ $this, 'training_elements' ],
                'permission_callback' => function () {
                    return $this->has_permission();
                }
            ]
        );
        register_rest_route(
            $this->namespace, '/location_funnel', [
                [
                    'methods'  => 'GET',
                    'callback' => [ $this, 'location_funnel' ],
                    'permission_callback' => function () {
                        return $this->has_permission();
                    }
                ],
            ]
        );
        register_rest_route(
            $namespace, '/simulate', [
                'methods'  => [ 'GET', 'POST' ],
                'callback' => [ $this, 'training_elements' ],
                'permission_callback' => function () {
                    return $this->has_permission();
                }
            ]
        );

        // dev
        register_rest_route(
            $namespace, '/sample', [
                'methods'  => [ 'GET', 'POST' ],
                'callback' => [ $this, 'sample' ],
                'permission_callback' => function () {
                    return $this->has_permission();
                }
            ]
        );
    }

    /**
     * @param WP_REST_Request $request
     * @return array|WP_Error
     */
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
            default:
                return $this->general( $params );
        }
    }
    public function total_anonymous( $params ) {
        $negative_stat = $params['negative_stat'];

        $label = '';
        $description = '';
        $link = '';
        $value = 0;
        $goal = 0;
        $trend = 0;
        $valence = NULL;

        switch( $params['key'] ) {

            case 'total_registrations':
                $label = 'Total Registrations';
                $description = 'Total registrations over the entire history of the project';
                $link = '';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                $valence = 'valence-grey';
                break;
            case 'visitors':
                $label = 'Visitors';
                $description = 'Visitors to some content on the website (not including bounces).';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'registrations':
                $label = 'Registrations';
                $description = 'Total registrations to the system.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'coach_requests':
                $label = 'Coach Requests';
                $description = 'Responses to the "Request a Coach" CTA';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'joined_online_training':
                $label = 'Joined Online Training';
                $description = 'People who have responded the online training CTA';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'total_anonymous':
                $label = 'Anonymous';
                $description = 'Visitors who have meaningfully engaged with the site, but have not registered.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            default:
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;

        }

        return [
            'key' => $params['key'],
            'stage' => $params['stage'],
            'label' => $label,
            'description' => $description,
            'link' => $link,
            'value' => Zume_Funnel_Query::format_int( $value ),
            'valence' => $valence ?? Zume_Funnel_Query::get_valence( $value, $goal, $negative_stat ),
            'goal' => $goal,
            'goal_valence' => Zume_Funnel_Query::get_valence( $value, $goal, $negative_stat ),
            'goal_percent' => Zume_Funnel_Query::get_percent( $value, $goal ),
            'trend' => $trend,
            'trend_valence' => Zume_Funnel_Query::get_valence( $value, $trend, $negative_stat ),
            'trend_percent' => Zume_Funnel_Query::get_percent( $value, $trend ),
            'negative_stat' => $negative_stat,
        ];

    }
    public function total_registrants( $params ) {
        $negative_stat = $params['negative_stat'];

        $label = '';
        $description = '';
        $link = '';
        $value = 0;
        $goal = 0;
        $trend = 0;
        $valence = NULL;
        $goal_valence = NULL;
        $trend_valence = NULL;

        switch( $params['key'] ) {
            case 'locations':
                $label = 'Locations';
                $description = 'Cumulative number of locations in this stage.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                $valence = 'valence-grey';
                break;
            case 'countries':
                $label = 'Countries';
                $description = 'Cumulative number of countries in this stage.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                $valence = 'valence-grey';
                break;
            case 'new_registrations':
                $label = 'New Registrations';
                $description = 'Total number of registrants in this stage.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'coach_requests':
                $label = 'Coach Requests';
                $description = 'Coach requests in this period of time';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'has_plan':
                $label = 'Has Plan';
                $description = 'Total number of registrants who have a plan.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'no_plan':
                $label = 'Has No Plan';
                $description = 'Total number of registrants who have no plan.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                $valence = NULL;
                break;
            case 'no_friends':
                $label = 'Has No Friends';
                $description = 'Total number of registrants who have not invited any friends.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'no_coach':
                $label = 'Has Not Requested a Coach';
                $description = 'Total number of registrants who have not requested a coach.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'no_updated_profile':
                $label = 'Has Not Updated Profile';
                $description = 'Total number of registrants who have not updated their profile.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;

            case 'total_registrants':
                $label = 'Registrants';
                $description = 'People who have registered but have not progressed into training.';
                $link = 'registrants';
                $value = Zume_Funnel_Query::stage_totals( 1 );
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'in_and_out':
                return [
                    'key' => $params['key'],
                    'stage' => $params['stage'],
                    'label' => 'Flow',
                    'description' => 'People moving in and out of thise stage.',
                    'link' => '',
                    'value_in' => Zume_Funnel_Query::format_int( rand(100, 1000) ),
                    'value_idle' => Zume_Funnel_Query::format_int( rand(100, 1000) ),
                    'value_out' => Zume_Funnel_Query::format_int( rand(100, 1000) ),
                ];

            default:
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
        }

        return [
            'key' => $params['key'],
            'stage' => $params['stage'],
            'label' => $label,
            'description' => $description,
            'link' => $link,
            'value' => Zume_Funnel_Query::format_int( $value ),
            'valence' => $valence ?? Zume_Funnel_Query::get_valence( $value, $goal, $negative_stat ),
            'goal' => $goal,
            'goal_valence' => $goal_valence ?? Zume_Funnel_Query::get_valence( $value, $goal, $negative_stat ),
            'goal_percent' => Zume_Funnel_Query::get_percent( $value, $goal ),
            'trend' => $trend,
            'trend_valence' => $trend_valence ?? Zume_Funnel_Query::get_valence( $value, $trend, $negative_stat ),
            'trend_percent' => Zume_Funnel_Query::get_percent( $value, $trend ),
            'negative_stat' => $negative_stat,
        ];

    }
    public function total_active_training_trainee( $params ) {
        $negative_stat = $params['negative_stat'];

        $label = '';
        $description = '';
        $link = '';
        $value = 0;
        $goal = 0;
        $trend = 0;
        $valence = NULL;
        $goal_valence = NULL;
        $trend_valence = NULL;

        switch( $params['key'] ) {
            case 'has_coach':
                $label = 'Has Coach';
                $description = 'Active trainees who have a coach.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                $valence = 'valence-grey';
                break;
            case 'inactive_trainees':
                $label = 'Inactive Trainees';
                $description = 'People who have been inactive more than 6 months.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                $valence = 'valence-grey';
                break;
            case 'new_active_trainees':
                $label = 'New Active Trainees';
                $description = 'New people who entered stage during time period.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'total_checkins':
                $label = 'Total Checkins';
                $description = 'Total number of checkins registered for training.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'has_no_coach':
                $label = 'Has No Coach';
                $description = 'People who have no coach.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'has_no_updated_profile':
                $label = 'No Updated Profile';
                $description = 'People who have not updated their profile.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                $valence = NULL;
                break;
            case 'in_and_out':
                return [
                    'key' => $params['key'],
                    'stage' => $params['stage'],
                    'label' => 'Flow',
                    'description' => 'People moving in and out of thise stage.',
                    'link' => '',
                    'value_in' => Zume_Funnel_Query::format_int( rand(100, 1000) ),
                    'value_idle' => Zume_Funnel_Query::format_int( rand(100, 1000) ),
                    'value_out' => Zume_Funnel_Query::format_int( rand(100, 1000) ),
                ];
            case 'total_active_training_trainee':
                $label = 'Active Training Trainees';
                $description = 'People who are actively working a training plan or have only partially completed the training.';
                $value = Zume_Funnel_Query::stage_totals( 2 );
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            default:
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
        }

       return [
            'key' => $params['key'],
            'stage' => $params['stage'],
            'label' => $label,
            'description' => $description,
            'link' => $link,
            'value' => Zume_Funnel_Query::format_int( $value ),
            'valence' => $valence ?? Zume_Funnel_Query::get_valence( $value, $goal, $negative_stat ),
            'goal' => $goal,
            'goal_valence' => $goal_valence ?? Zume_Funnel_Query::get_valence( $value, $goal, $negative_stat ),
            'goal_percent' => Zume_Funnel_Query::get_percent( $value, $goal ),
            'trend' => $trend,
            'trend_valence' => $trend_valence ?? Zume_Funnel_Query::get_valence( $value, $trend, $negative_stat ),
            'trend_percent' => Zume_Funnel_Query::get_percent( $value, $trend ),
            'negative_stat' => $negative_stat,
        ];

    }
    public function total_post_training_trainee( $params ) {
        $negative_stat = $params['negative_stat'];

        $label = '';
        $description = '';
        $link = '';
        $value = 0;
        $goal = 0;
        $trend = 0;
        $valence = NULL;
        $goal_valence = NULL;
        $trend_valence = NULL;

        switch( $params['key'] ) {

            case 'needs_3_month_plan':
                $label = 'Needs 3 Month Plan';
                $description = 'Needs a 3 month plan.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                $valence = 'valence-grey';
                break;
            case 'needs_coach':
                $label = 'Needs Coach';
                $description = 'Needs a coach';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                $valence = 'valence-grey';
                break;
            case 'new_trainees':
                $label = 'New Trainees';
                $description = 'New trainees entering stage in time period.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'new_3_month_plans':
                $label = 'New Post Training Plans';
                $description = 'New Post Training Plans';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'new_coaching_requests':
                $label = 'New Coaching Requests';
                $description = 'New coaching requests during the time period.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'total_post_training_trainee':
                $label = 'Post-Training Trainees';
                $description = 'People who have completed the training and are working on a post training plan.';
                $link = 'post';
                $value = Zume_Funnel_Query::stage_totals( 3 );
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'in_and_out':
                return [
                    'key' => $params['key'],
                    'stage' => $params['stage'],
                    'label' => 'Flow',
                    'description' => 'People moving in and out of thise stage.',
                    'link' => '',
                    'value_in' => Zume_Funnel_Query::format_int( rand(100, 1000) ),
                    'value_idle' => Zume_Funnel_Query::format_int( rand(100, 1000) ),
                    'value_out' => Zume_Funnel_Query::format_int( rand(100, 1000) ),
                ];
            default:
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
        }

        return [
            'key' => $params['key'],
            'stage' => $params['stage'],
            'label' => $label,
            'description' => $description,
            'link' => $link,
            'value' => Zume_Funnel_Query::format_int( $value ),
            'valence' => $valence ?? Zume_Funnel_Query::get_valence( $value, $goal, $negative_stat ),
            'goal' => $goal,
            'goal_valence' => $goal_valence ?? Zume_Funnel_Query::get_valence( $value, $goal, $negative_stat ),
            'goal_percent' => Zume_Funnel_Query::get_percent( $value, $goal ),
            'trend' => $trend,
            'trend_valence' => $trend_valence ?? Zume_Funnel_Query::get_valence( $value, $trend, $negative_stat ),
            'trend_percent' => Zume_Funnel_Query::get_percent( $value, $trend ),
            'negative_stat' => $negative_stat,
        ];

    }
    public function total_partial_practitioner( $params ) {
        $negative_stat = $params['negative_stat'];

        $label = '';
        $description = '';
        $link = '';
        $value = 0;
        $goal = 0;
        $trend = 0;
        $valence = NULL;
        $goal_valence = NULL;
        $trend_valence = NULL;

        switch( $params['key'] ) {

            case 'total_churches';
                $label = 'Churches';
                $description = 'Total number of churches reported by S1 Practitioners.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                $valence = 'valence-grey';
                break;
            case 'total_locations';
                $label = 'Locations';
                $description = 'Total number of locations reported by S1 Practitioners.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                $valence = 'valence-grey';
                break;
            case 'total_active_reporters';
                $label = 'Reporting';
                $description = 'Total number of active reporters.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                $valence = 'valence-grey';
                break;
            case 'new_practitioners';
                $label = 'New Practitioners';
                $description = 'Total number of new practitioners.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'new_reporters';
                $label = 'New Reporters';
                $description = 'Total number of new reporters.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'new_churches';
                $label = 'New Churches';
                $description = 'Total number of new churches reported by S1 Practitioners.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'new_locations';
                $label = 'New Locations';
                $description = 'Total number of new locations reported by S1 Practitioners.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'has_no_coach';
                $label = 'Has No Coach';
                $description = 'Total number of S1 Practitioners who have not yet been assigned a coach.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'has_not_reported';
                $label = 'Has Not Reported';
                $description = 'Total number of S1 Practitioners who have not yet reported.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'total_partial_practitioner':
                $label = '(S1) Partial Practitioners';
                $description = 'Learning through doing. Implementing partial checklist / 4-fields';
                $link = 'partial_practitioner_practitioners';
                $value = Zume_Funnel_Query::stage_totals( 4 );
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'in_and_out':
                return [
                    'key' => $params['key'],
                    'stage' => $params['stage'],
                    'label' => 'Flow',
                    'description' => 'People moving in and out of thise stage.',
                    'link' => '',
                    'value_in' => Zume_Funnel_Query::format_int( rand(100, 1000) ),
                    'value_idle' => Zume_Funnel_Query::format_int( rand(100, 1000) ),
                    'value_out' => Zume_Funnel_Query::format_int( rand(100, 1000) ),
                ];
            default:
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
        }

        return [
            'key' => $params['key'],
            'stage' => $params['stage'],
            'label' => $label,
            'description' => $description,
            'link' => $link,
            'value' => Zume_Funnel_Query::format_int( $value ),
            'valence' => $valence ?? Zume_Funnel_Query::get_valence( $value, $goal, $negative_stat ),
            'goal' => $goal,
            'goal_valence' => $goal_valence ?? Zume_Funnel_Query::get_valence( $value, $goal, $negative_stat ),
            'goal_percent' => Zume_Funnel_Query::get_percent( $value, $goal ),
            'trend' => $trend,
            'trend_valence' => $trend_valence ?? Zume_Funnel_Query::get_valence( $value, $trend, $negative_stat ),
            'trend_percent' => Zume_Funnel_Query::get_percent( $value, $trend ),
            'negative_stat' => $negative_stat,
        ];

    }
    public function total_full_practitioner( $params ) {
        $negative_stat = $params['negative_stat'];

        $label = '';
        $description = '';
        $link = '';
        $value = 0;
        $goal = 0;
        $trend = 0;
        $valence = NULL;
        $goal_valence = NULL;
        $trend_valence = NULL;

        switch( $params['key'] ) {

            case 'total_churches';
                $label = 'Churches';
                $description = 'Total number of churches reported by S2 Practitioners.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'total_locations';
                $label = 'Locations';
                $description = 'Total number of locations reported by S2 Practitioners.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'total_active_reporters';
                $label = 'Active Reporters';
                $description = 'Total number of active reporters.';
                $link = 'partial_practitioner_practitioners';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'new_practitioners';
                $label = 'New Practitioners';
                $description = 'Total number of new practitioners.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'new_reporters';
                $label = 'New Reporters';
                $description = 'Total number of new reporters.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'new_churches';
                $label = 'New Churches';
                $description = 'Total number of new churches reported by S2 Practitioners.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'new_locations';
                $label = 'New Locations';
                $description = 'Total number of new locations reported by S2 Practitioners.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'has_no_coach';
                $label = 'Has No Coach';
                $description = 'Total number of S2 Practitioners who have not yet been assigned a coach.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'has_not_reported';
                $label = 'Has Not Reported';
                $description = 'Total number of S2 Practitioners who have not yet reported.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'total_full_practitioner':
                $label = '(S2) Full Practitioners';
                $description = 'People who are seeking multiplicative movement and are completely skilled with the coaching checklist.';
                $link = 'full_practitioner_practitioners';
                $value = Zume_Funnel_Query::stage_totals( 5 );
                $goal = 5;
                $trend = rand(1, 10);
                break;
            case 'in_and_out':
                return [
                    'key' => $params['key'],
                    'stage' => $params['stage'],
                    'label' => 'Flow',
                    'description' => 'People moving in and out of thise stage.',
                    'link' => '',
                    'value_in' => Zume_Funnel_Query::format_int( rand(100, 1000) ),
                    'value_idle' => Zume_Funnel_Query::format_int( rand(100, 1000) ),
                    'value_out' => Zume_Funnel_Query::format_int( rand(100, 1000) ),
                ];
            default:
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
        }

        return [
            'key' => $params['key'],
            'stage' => $params['stage'],
            'label' => $label,
            'description' => $description,
            'link' => $link,
            'value' => Zume_Funnel_Query::format_int( $value ),
            'valence' => $valence ?? Zume_Funnel_Query::get_valence( $value, $goal, $negative_stat ),
            'goal' => $goal,
            'goal_valence' => $goal_valence ?? Zume_Funnel_Query::get_valence( $value, $goal, $negative_stat ),
            'goal_percent' => Zume_Funnel_Query::get_percent( $value, $goal ),
            'trend' => $trend,
            'trend_valence' => $trend_valence ?? Zume_Funnel_Query::get_valence( $value, $trend, $negative_stat ),
            'trend_percent' => Zume_Funnel_Query::get_percent( $value, $trend ),
            'negative_stat' => $negative_stat,
        ];

    }
    public function total_multiplying_practitioner( $params ) {
        $negative_stat = $params['negative_stat'];

        $label = '';
        $description = '';
        $link = '';
        $value = 0;
        $goal = 0;
        $trend = 0;
        $valence = NULL;
        $goal_valence = NULL;
        $trend_valence = NULL;

        switch( $params['key'] ) {
            case 'total_churches';
                $label = 'Churches';
                $description = 'Total number of churches reported by S2 Practitioners.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'total_locations';
                $label = 'Locations';
                $description = 'Total number of locations reported by S2 Practitioners.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'total_active_reporters';
                $label = 'Active Reporters';
                $description = 'Total number of active reporters.';
                $link = 'partial_practitioner_practitioners';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'new_practitioners';
                $label = 'New Practitioners';
                $description = 'Total number of new practitioners.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'new_reporters';
                $label = 'New Reporters';
                $description = 'Total number of new reporters.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'new_churches';
                $label = 'New Churches';
                $description = 'Total number of new churches reported by S2 Practitioners.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'new_locations';
                $label = 'New Locations';
                $description = 'Total number of new locations reported by S2 Practitioners.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'has_no_coach';
                $label = 'Has No Coach';
                $description = 'Total number of S2 Practitioners who have not yet been assigned a coach.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'has_not_reported';
                $label = 'Has Not Reported';
                $description = 'Total number of S2 Practitioners who have not yet reported.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'total_multiplying_practitioner':
                $label = '(S3) Multiplying Practitioners';
                $description = 'People who are seeking multiplicative movement and are stewarding generational fruit.';
                $link = 'multiplying_practitioner_practitioners';
                $value = Zume_Funnel_Query::stage_totals( 6 );
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'in_and_out':
                return [
                    'key' => $params['key'],
                    'stage' => $params['stage'],
                    'label' => 'Flow',
                    'description' => 'People moving in and out of thise stage.',
                    'link' => '',
                    'value_in' => Zume_Funnel_Query::format_int( rand(100, 1000) ),
                    'value_idle' => Zume_Funnel_Query::format_int( rand(100, 1000) ),
                    'value_out' => Zume_Funnel_Query::format_int( rand(100, 1000) ),
                ];
            default:
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
        }

        return [
            'key' => $params['key'],
            'stage' => $params['stage'],
            'label' => $label,
            'description' => $description,
            'link' => $link,
            'value' => Zume_Funnel_Query::format_int( $value ),
            'valence' => $valence ?? Zume_Funnel_Query::get_valence( $value, $goal, $negative_stat ),
            'goal' => $goal,
            'goal_valence' => $goal_valence ?? Zume_Funnel_Query::get_valence( $value, $goal, $negative_stat ),
            'goal_percent' => Zume_Funnel_Query::get_percent( $value, $goal ),
            'trend' => $trend,
            'trend_valence' => $trend_valence ?? Zume_Funnel_Query::get_valence( $value, $trend, $negative_stat ),
            'trend_percent' => Zume_Funnel_Query::get_percent( $value, $trend ),
            'negative_stat' => $negative_stat,
        ];

    }
    public function total_facilitator( $params ) {
        $negative_stat = $params['negative_stat'];

        $label = '';
        $description = '';
        $link = '';
        $value = 0;
        $goal = 0;
        $trend = 0;
        $valence = NULL;
        $goal_valence = NULL;
        $trend_valence = NULL;

        switch( $params['key'] ) {

            case 'new_coaching_requests';
                $label = 'New Coaching Requests';
                $description = 'Total number of new coaching requests submitted to Facilitator Coaches.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                $valence = 'valence-grey';
                break;
            case 'languages';
                $label = 'Languages';
                $description = 'Number of languages from requests';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                $valence = 'valence-grey';
                break;
            case 'locations';
                $label = 'Locations';
                $description = 'Locations from requests.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                $valence = 'valence-grey';
                break;
            default:
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
        }

        return [
            'key' => $params['key'],
            'stage' => $params['stage'],
            'label' => $label,
            'description' => $description,
            'link' => $link,
            'value' => Zume_Funnel_Query::format_int( $value ),
            'valence' => $valence ?? Zume_Funnel_Query::get_valence( $value, $goal, $negative_stat ),
            'goal' => $goal,
            'goal_valence' => $goal_valence ?? Zume_Funnel_Query::get_valence( $value, $goal, $negative_stat ),
            'goal_percent' => Zume_Funnel_Query::get_percent( $value, $goal ),
            'trend' => $trend,
            'trend_valence' => $trend_valence ?? Zume_Funnel_Query::get_valence( $value, $trend, $negative_stat ),
            'trend_percent' => Zume_Funnel_Query::get_percent( $value, $trend ),
            'negative_stat' => $negative_stat,
        ];

    }
    public function total_early( $params ) {
        $negative_stat = $params['negative_stat'];

        $label = '';
        $description = '';
        $link = '';
        $value = 0;
        $goal = 0;
        $trend = 0;
        $valence = NULL;
        $goal_valence = NULL;
        $trend_valence = NULL;


        switch( $params['key'] ) {
            case 'new_coaching_requests';
                $label = 'Languages';
                $description = '';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                $valence = 'valence-grey';
                break;
            case 'languages';
                $label = 'New Coaching Requests';
                $description = 'Number of languages from requests';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'locations';
                $label = 'Locations';
                $description = 'Locations from requests.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                $valence = 'valence-grey';
                break;
            case 'total_multiplying_practitioner';
            default:
                $label = '(S3) Multiplying Practitioners';
                $description = 'People who are seeking multiplicative movement and are stewarding generational fruit.';
                $link = 'multiplying_practitioner_practitioners';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
        }

        return [
            'key' => $params['key'],
            'stage' => $params['stage'],
            'label' => $label,
            'description' => $description,
            'link' => $link,
            'value' => Zume_Funnel_Query::format_int( $value ),
            'valence' => $valence ?? Zume_Funnel_Query::get_valence( $value, $goal, $negative_stat ),
            'goal' => $goal,
            'goal_valence' => $goal_valence ?? Zume_Funnel_Query::get_valence( $value, $goal, $negative_stat ),
            'goal_percent' => Zume_Funnel_Query::get_percent( $value, $goal ),
            'trend' => $trend,
            'trend_valence' => $trend_valence ?? Zume_Funnel_Query::get_valence( $value, $trend, $negative_stat ),
            'trend_percent' => Zume_Funnel_Query::get_percent( $value, $trend ),
            'negative_stat' => $negative_stat,
        ];


    }
    public function total_advanced( $params ) {
        $negative_stat = $params['negative_stat'];

        $label = '';
        $description = '';
        $link = '';
        $value = 0;
        $goal = 0;
        $trend = 0;
        $valence = NULL;
        $goal_valence = NULL;
        $trend_valence = NULL;

        switch( $params['key'] ) {
            case 'total_churches';
                $label = 'Total Churches';
                $description = 'Total number of churches reported by S2 Practitioners.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'total_locations';
                $label = 'Total Locations';
                $description = 'Total number of locations reported by S2 Practitioners.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'total_active_reporters';
                $label = 'Total Active Reporters';
                $description = 'Total number of active reporters.';
                $link = 'partial_practitioner_practitioners';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'new_practitioners';
                $label = 'New Practitioners';
                $description = 'Total number of new practitioners.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'new_reporters';
                $label = 'New Reporters';
                $description = 'Total number of new reporters.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'new_churches';
                $label = 'New Churches';
                $description = 'Total number of new churches reported by S2 Practitioners.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'new_locations';
                $label = 'New Locations';
                $description = 'Total number of new locations reported by S2 Practitioners.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'has_no_coach';
                $label = 'Has No Coach';
                $description = 'Total number of S2 Practitioners who have not yet been assigned a coach.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'has_not_reported';
                $label = 'Has Not Reported';
                $description = 'Total number of S2 Practitioners who have not yet reported.';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            case 'total_multiplying_practitioner':
                $label = '(S3) Multiplying Practitioners';
                $description = 'People who are seeking multiplicative movement and are stewarding generational fruit.';
                $link = 'multiplying_practitioner_practitioners';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
            default:
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
        }

        return [
            'key' => $params['key'],
            'stage' => $params['stage'],
            'label' => $label,
            'description' => $description,
            'link' => $link,
            'value' => Zume_Funnel_Query::format_int( $value ),
            'valence' => $valence ?? Zume_Funnel_Query::get_valence( $value, $goal, $negative_stat ),
            'goal' => $goal,
            'goal_valence' => $goal_valence ?? Zume_Funnel_Query::get_valence( $value, $goal, $negative_stat ),
            'goal_percent' => Zume_Funnel_Query::get_percent( $value, $goal ),
            'trend' => $trend,
            'trend_valence' => $trend_valence ?? Zume_Funnel_Query::get_valence( $value, $trend, $negative_stat ),
            'trend_percent' => Zume_Funnel_Query::get_percent( $value, $trend ),
            'negative_stat' => $negative_stat,
        ];

    }
    public function general( $params ) {
        $negative_stat = $params['negative_stat'];

        $label = '';
        $description = '';
        $link = '';
        $value = 0;
        $goal = 0;
        $trend = 0;
        $valence = NULL;
        $goal_valence = NULL;
        $trend_valence = NULL;

        switch( $params['key'] ) {

            case 'active_coaches';
                $label = 'Active Coaches';
                $description = 'Number of active coaches';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                $valence = 'valence-grey';
                break;
            case 'total_people_in_coaching';
                $label = 'People in Coaching';
                $description = 'Number of people in coaching';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                $valence = 'valence-grey';
                break;
            case 'people_in_coaching';
                $label = 'People in Coaching';
                $description = 'Number of people in coaching';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                $valence = 'valence-grey';
                break;
            case 'coaching_engagements';
                $label = 'Coaching Engagements';
                $description = 'Number of coaching engagements';
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                $valence = 'valence-grey';
                break;
            case 'in_and_out':
                return [
                    'key' => $params['key'],
                    'label' => '',
                    'description' => 'Description',
                    'link' => '',
                    'value_in' => Zume_Funnel_Query::format_int( rand(100, 1000) ),
                    'value_idle' => Zume_Funnel_Query::format_int( rand(100, 1000) ),
                    'value_out' => Zume_Funnel_Query::format_int( rand(100, 1000) ),
                ];
            default:
                $value = rand(100, 1000);
                $goal = rand(500, 700);
                $trend = rand(500, 700);
                break;
        }

        return [
            'key' => $params['key'],
            'stage' => $params['stage'],
            'label' => $label,
            'description' => $description,
            'link' => $link,
            'value' => Zume_Funnel_Query::format_int( $value ),
            'valence' => $valence ?? Zume_Funnel_Query::get_valence( $value, $goal, $negative_stat ),
            'goal' => $goal,
            'goal_valence' => $goal_valence ?? Zume_Funnel_Query::get_valence( $value, $goal, $negative_stat ),
            'goal_percent' => Zume_Funnel_Query::get_percent( $value, $goal ),
            'trend' => $trend,
            'trend_valence' => $trend_valence ?? Zume_Funnel_Query::get_valence( $value, $trend, $negative_stat ),
            'trend_percent' => Zume_Funnel_Query::get_percent( $value, $trend ),
            'negative_stat' => $negative_stat,
        ];

    }

    public function location_funnel( ) {
        $data = DT_Mapping_Module::instance()->data();
        $funnel = zume_funnel_stages();

        $data = $this->add_column(  $data, $funnel['1']['key'], $funnel['1']['label'], ['1'] );
        $data = $this->add_column(  $data, $funnel['2']['key'], $funnel['2']['label'], ['2'] );
        $data = $this->add_column(  $data, $funnel['3']['key'], $funnel['3']['label'], ['3'] );
        $data = $this->add_column(  $data, $funnel['4']['key'], $funnel['4']['label'], ['4'] );
        $data = $this->add_column(  $data, $funnel['5']['key'], $funnel['5']['label'], ['5'] );
        $data = $this->add_column(  $data, $funnel['6']['key'], $funnel['6']['label'], ['6'] );

        return $data;
    }

    public function list( WP_REST_Request $request ) {
        return Zume_Goals_Query::list( dt_recursive_sanitize_array( $request->get_params() ) );
    }



    public function map_switcher( WP_REST_Request $request ) {
        $params =  dt_recursive_sanitize_array( $request->get_params() );
        if ( ! isset( $params['stage'] ) ) {
            return new WP_Error( 'no_stage', __( 'No stage key provided.', 'zume' ), array( 'status' => 400 ) );
        }


        switch( $params['stage'] ) {
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
            default:
                return $this->general( $params );
        }
    }
    public function map( WP_REST_Request $request ) {
        $params =  dt_recursive_sanitize_array( $request->get_params() );
        dt_write_log( $params );
        $data = Zume_Funnels_Query::map( $params );

//        $features = [];
//        foreach ( $results as $result ) {
//            $features[] = array(
//                'type' => 'Feature',
//                'properties' => array(
//                    'address' => $result['address'],
//                    'post_id' => $result['post_id'],
//                    'name' => $result['name'],
//                    'post_type' => $post_type
//                ),
//                'geometry' => array(
//                    'type' => 'Point',
//                    'coordinates' => array(
//                        $result['lng'],
//                        $result['lat'],
//                        1
//                    ),
//                ),
//            );
//        }
//
//        $new_data = array(
//            'type' => 'FeatureCollection',
//            'features' => $features,
//        );
//
//        return $new_data;

        return [ "link" => '<iframe class="map-iframe" width="100%" height="2500" src="https://zume.training/coaching/zume_app/heatmap_trainees" frameborder="0" style="border:0" allowfullscreen></iframe>' ];
    }

    public function query_location_funnel( array $range ) {
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
    public function add_column(  $data, $key, $label, $stage )
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
        $results = $this->query_location_funnel( $stage);
        if (!empty($results)) {
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

    public function location( WP_REST_Request $request ) {
        return DT_Ipstack_API::get_location_grid_meta_from_current_visitor();
    }
    public function training_elements( WP_REST_Request $request ) {
        return Zume_Funnel_Query::training_elements( dt_recursive_sanitize_array( $request->get_params() ) );
    }

    // dev
    public function sample( WP_REST_Request $request ) {
        return Zume_Funnel_Query::sample( dt_recursive_sanitize_array( $request->get_params() ) );
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
Zume_Stats_Endpoints::instance();
