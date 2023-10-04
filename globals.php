<?php
/**
 * These are global functions that are used throughout the system, and used in the coaching system. There is a copy of this file in the coaching system.
 * If changes are made here, they need copied to the coaching plugin.
 * All sql queries should not use variable table names, but should be fully qualified.
 */

/**
 * API Routes for common global functions
 */
if ( dt_is_rest() ) {
    add_action( 'rest_api_init', 'zume_global_add_api_routes' );
    add_filter( 'dt_allow_rest_access', 'zume_global_authorize_url', 10, 1 );
}
function zume_global_add_api_routes()
{
    $namespace = 'zume_system/v1';
    register_rest_route(
        $namespace, '/user_data/profile', [
            'methods' => [ 'GET', 'POST' ],
            'callback' => 'zume_get_user_profile_api',
            'permission_callback' => '__return_true',
        ]
    );
    register_rest_route(
        $namespace, '/user_data/stage', [
            'methods' => [ 'GET', 'POST' ],
            'callback' => 'zume_get_user_stage_api',
            'permission_callback' => '__return_true',
        ]
    );
    register_rest_route(
        $namespace, '/user_data/host', [
            'methods' => [ 'GET', 'POST' ],
            'callback' => 'zume_get_user_host_api',
            'permission_callback' => '__return_true',
        ]
    );
    register_rest_route(
        $namespace, '/user_data/mawl', [
            'methods' => [ 'GET', 'POST' ],
            'callback' => 'zume_get_user_mawl_api',
            'permission_callback' => '__return_true',
        ]
    );
}
function zume_global_authorize_url( $authorized )
{
    if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 'zume_system/v1' ) !== false ) {
        $authorized = true;
    }
    return $authorized;
}
function zume_get_user_profile_api( WP_REST_Request $request ) {
    $user_id = get_current_user_id();
    return zume_get_user_profile( $user_id );
}
function zume_get_user_stage_api( WP_REST_Request  $request )
{
    $user_id = get_current_user_id();
    return zume_get_user_stage( $user_id );
}
function zume_get_user_host_api( WP_REST_Request $request )
{
    $user_id = get_current_user_id();
    return zume_get_user_host( $user_id );
}
function zume_get_user_mawl_api( WP_REST_Request $request )
{
    $user_id = get_current_user_id();
    return zume_get_user_mawl( $user_id );
}

/**
 * End API Routes
 */

if ( ! function_exists( 'zume_get_user_profile' ) ) {
    function zume_get_user_profile( $user_id = null ) {
        if ( is_null( $user_id ) ) {
            $user_id = get_current_user_id();
        }

        $contact_id = zume_get_user_contact_id( $user_id );

        global $wpdb;
        $name = $wpdb->get_var( $wpdb->prepare( 'SELECT post_title FROM wp_posts WHERE ID = %d', $contact_id ) );

        $contact_meta_query = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM wp_postmeta WHERE post_id = %d', $contact_id ), ARRAY_A );
        $contact_meta = [];
        foreach ( $contact_meta_query as $value ) {
            $contact_meta[$value['meta_key']] = $value['meta_value'];
        }

        $email = $contact_meta['user_email'] ?? '';
        $phone = $contact_meta['user_phone'] ?? '';
        $timezone = $contact_meta['user_timezone'] ?? '';
        $user_friend_key = $contact_meta['user_friend_key'] ?? '';
        $user_ui_language = $contact_meta['user_ui_language'] ?? '';

        $language = zume_get_user_language( $user_id );
        $location = zume_get_user_location( $user_id );

        $coaching_contact_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT post_id
                FROM wp_3_postmeta
                WHERE meta_key = 'trainee_user_id'
                  AND meta_value = %s",
        $user_id ) );
        $coaches = $wpdb->get_results( $wpdb->prepare(
            "SELECT p.ID as contact_id, pm.meta_value as user_id, p.post_title as name
                FROM wp_3_p2p p2
                LEFT JOIN wp_3_posts p ON p2.p2p_to=p.ID
                LEFT JOIN wp_3_postmeta pm ON pm.post_id = p.ID AND pm.meta_key = 'corresponds_to_user'
                WHERE p2p_from = %d
                  AND p2p_type = 'contacts_to_contacts'",
        $coaching_contact_id ), ARRAY_A );
        if ( empty( $coaches ) ) {
            $coaches = [];
        }

        if ( $user_id == get_current_user_id() ) {
            // user is current user, build global variable
            global $zume_user_profile; // sets a global variable for user_profile
            $zume_user_profile = [
                'name' => $name,
                'user_id' => $user_id,
                'contact_id' => $contact_id,
                'coaching_contact_id' => $coaching_contact_id,
                'email' => $email,
                'phone' => $phone,
                'location' => $location,
                'language' => $language,
                'timezone' => $timezone,
                'coaches' => $coaches,
                'friend_key' => $user_friend_key,
                'ui_language' => $user_ui_language,
            ];
            return $zume_user_profile;
        } else {
            // if user is not current user, return array
            return [
                'name' => $name,
                'user_id' => $user_id,
                'contact_id' => $contact_id,
                'coaching_contact_id' => $coaching_contact_id,
                'email' => $email,
                'phone' => $phone,
                'location' => $location,
                'language' => $language,
                'timezone' => $timezone,
                'coaches' => $coaches,
                'friend_key' => $user_friend_key,
                'ui_language' => $user_ui_language,
            ];
        }
    }
}
if ( ! function_exists( 'zume_get_user_stage' ) ) {
    function zume_get_user_stage( $user_id = null, $log = null, $number_only = false ) {
        if ( is_null( $user_id ) ) {
            $user_id = get_current_user_id();
        }
        if ( is_null( $log ) ) {
            $log = zume_get_user_log( $user_id );
        }

        $funnel = zume_funnel_stages();
        $stage = $funnel[0];

        if ( empty( $log ) ) {
            return $stage;
        }

        if ( count( $log ) > 0 ) {

            $funnel_steps = [
                1 => false,
                2 => false,
                3 => false,
                4 => false,
                5 => false,
                6 => false,
            ];

            foreach ( $log as $value ) {
                if ( 'registered' == $value['subtype'] ) {
                    $funnel_steps[1] = true;
                }
                if ( 'plan_created' == $value['subtype'] ) {
                    $funnel_steps[2] = true;
                }
                if ( 'training_completed' == $value['subtype'] ) {
                    $funnel_steps[3] = true;
                }
                if ( 'first_practitioner_report' == $value['subtype'] ) {
                    $funnel_steps[4] = true;
                }
                if ( 'mawl_completed' == $value['subtype'] || 'host_completed' == $value['subtype'] ) {
                    $funnel_steps[5] = true;
                }
                if ( 'seeing_generational_fruit' == $value['subtype'] ) {
                    $funnel_steps[6] = true;
                }
            }

            if ( $funnel_steps[6] ) {
                $stage = $funnel[6];
            } else if ( $funnel_steps[5] ) {
                $stage = $funnel[5];
            } else if ( $funnel_steps[4] ) {
                $stage = $funnel[4];
            } else if ( $funnel_steps[3] ) {
                $stage = $funnel[3];
            } else if ( $funnel_steps[2] ) {
                $stage = $funnel[2];
            } else if ( $funnel_steps[1] ) {
                $stage = $funnel[1];
            } else {
                $stage = $funnel[0];
            }
        }

        if ( $number_only ) {
            return $stage['value'];
        } else {
            return $stage;
        }
    }
}
if ( ! function_exists( 'zume_get_user_language' ) ) {
    function zume_get_user_language( $user_id = null )
    {
        if ( is_null( $user_id ) ) {
            $user_id = get_current_user_id();
        }
        global $zume_languages_by_locale;
        if ( empty( $zume_languages_by_locale ) ) {
            $zume_languages_by_locale = zume_languages( 'locale' );
        }

        $locale = get_user_meta( $user_id, 'locale', true );
        if ( $user_id == get_current_user_id() && empty( $locale ) ) {
            update_user_meta( $user_id, 'locale', zume_current_language() );
            $locale = zume_current_language();
        }

        if ( ! $locale ) {
            $locale = 'en';
        }

        return isset( $zume_languages_by_locale[$locale] ) ? $zume_languages_by_locale[$locale] : $zume_languages_by_locale['en'];
    }
}
if ( ! function_exists( 'zume_get_user_location' ) ) {
    function zume_get_user_location( $user_id = null, $ip_lookup = false ) {
        if ( is_null( $user_id ) ) {
            $user_id = get_current_user_id();
        }
        global $wpdb;
        $location = $wpdb->get_row( $wpdb->prepare(
            "SELECT lng, lat, level, label, grid_id, source
                    FROM wp_postmeta pm
                    JOIN wp_dt_location_grid_meta lgm ON pm.post_id=lgm.post_id
                    WHERE pm.meta_key = 'corresponds_to_user' AND pm.meta_value = %d
                    ORDER BY grid_meta_id desc
                    LIMIT 1",
        $user_id ), ARRAY_A );

        if ( empty( $location ) && $ip_lookup ) {
            $result = DT_Ipstack_API::get_location_grid_meta_from_current_visitor();
            if ( ! empty( $result ) ) {
                $location = [
                    'lng' => $result['lng'],
                    'lat' => $result['lat'],
                    'level' => $result['level'],
                    'label' => $result['label'],
                    'grid_id' => $result['grid_id'],
                    'source' => $result['source'],
                ];
            }
        }

        if ( empty( $location ) ) {
            return false;
        }

        return [
            'lng' => $location['lng'],
            'lat' => $location['lat'],
            'level' => $location['level'],
            'label' => $location['label'],
            'grid_id' => $location['grid_id'],
            'source' => $location['source'],
        ];
    }
}
if ( ! function_exists( 'zume_get_user_timezone' ) ) {
    function zume_get_user_timezone( $user_id = null, $location = null ) {
        if ( is_null( $user_id ) ) {
            $user_id = get_current_user_id();
        }
        $contact_id = zume_get_user_contact_id( $user_id );
        $timezone = get_user_meta( $contact_id, 'user_timezone', true );

        if ( empty( $timezone ) ) {
            // query ipstack ??
            $timezone = 'UTC';
        }

        // lookup timezone details

        $timezone_details = [
            'timezone' => $timezone,
            'offset' => 0,
            'offset_string' => 'UTC',
            'offset_hours' => 0,
            'offset_minutes' => 0,
            'offset_seconds' => 0,
            'current_time' => date( 'Y-m-d H:i:s' ),
        ];

        return $timezone_details;
    }
}
if ( ! function_exists( 'zume_get_user_host' ) ) {
    function zume_get_user_host( $user_id = null, $log = null ) {
        if ( is_null( $user_id ) ) {
            $user_id = get_current_user_id();
        }
        if ( is_null( $log ) ) {
            $log = zume_get_user_log( $user_id );
        }
        $training_items = zume_training_items();
        $host = [];
        foreach ( $training_items as $item ) {
            if ( isset( $item['host'] ) ) {
                foreach ( $item['host'] as $elements ) {
                    $host[$elements['type'].'_'.$elements['subtype']] = false;
                }
            }
        }
        $keys = [];
        $h = 0;
        $o = 0;
        $s = 0;
        $t = 0;
        foreach ( $log as $row ) {
            $keys[$row['log_key']] = $row['log_key'];
        }
        foreach ( $host as $key => $value ) {
            if ( in_array( $key, $keys ) ) {
                $host[$key] = true;
                if ( str_ends_with( $key, 'heard' ) ) {
                    $h++;
                }
                else if ( str_ends_with( $key, 'obeyed' ) ) {
                    $o++;
                }
                else if ( str_ends_with( $key, 'shared' ) ) {
                    $s++;
                }
                else if ( str_ends_with( $key, 'trained' ) ) {
                    $t++;
                }
            }
        }
        return [
            'list' => $host,
            'totals' => [
                'h' => $h,
                'o' => $o,
                's' => $s,
                't' => $t,
            ],
            'percent' => [
                'h' => $h / count( $training_items ) * 100,
                'o' => $o / count( $training_items ) * 100,
                's' => $s / count( $training_items ) * 100,
                't' => $t / count( $training_items ) * 100,
            ],
        ];
    }
}
if ( ! function_exists( 'zume_get_user_mawl' ) ) {
    function zume_get_user_mawl( $user_id = null, $log = null ) {
        if ( is_null( $user_id ) ) {
            $user_id = get_current_user_id();
        }
        if ( is_null( $log ) ) {
            $log = zume_get_user_log( $user_id );
        }
        $training_items = zume_training_items();
        $mawl = [];
        foreach ( $training_items as $item ) {
            if ( isset( $item['mawl'] ) ) {
                foreach ( $item['mawl'] as $elements ) {
                    $mawl[$elements['type'].'_'.$elements['subtype']] = false;
                }
            }
        }
        $keys = [];
        $m = 0;
        $a = 0;
        $w = 0;
        $l = 0;
        foreach ( $log as $row ) {
            $keys[$row['log_key']] = $row['log_key'];
        }
        foreach ( $mawl as $key => $value ) {
            if ( in_array( $key, $keys ) ) {
                $mawl[$key] = true;
                if ( str_ends_with( $key, 'modeling' ) ) {
                    $m++;
                }
                else if ( str_ends_with( $key, 'assisting' ) ) {
                    $a++;
                }
                else if ( str_ends_with( $key, 'watching' ) ) {
                    $w++;
                }
                else if ( str_ends_with( $key, 'launching' ) ) {
                    $l++;
                }
            }
        }
        return [
            'list' => $mawl,
            'totals' => [
                'm' => $m,
                'a' => $a,
                'w' => $w,
                'l' => $l,
            ],
            'percent' => [
                'm' => $m / count( $training_items ) * 100,
                'a' => $a / count( $training_items ) * 100,
                'w' => $w / count( $training_items ) * 100,
                'l' => $l / count( $training_items ) * 100,
            ],
        ];
    }
}
if ( ! function_exists( 'zume_get_user_commitments' ) ) {
    // open, closed, all
    function zume_get_user_commitments(  $user_id = null, $status = 'open' )
    {
        if ( is_null( $user_id ) ) {
            $user_id = get_current_user_id();
        }
        global $wpdb;
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM wp_dt_post_user_meta
                    WHERE user_id = %d
                      AND category = 'custom'
                    ORDER BY date DESC",
        $user_id), ARRAY_A);

        $list = [];
        foreach ( $results as $result ) {
            $meta = maybe_unserialize( $result['meta_value'] );

            if ( 'open' === $status && isset( $meta['status'] ) ) { // status is added when closed, so if present, then it is closed
                continue;
            }

            if ( 'closed' === $status && !isset( $meta['status'] ) ) {
                continue;
            }

            $list[] = [
                'id' => $result['id'],
                'note' => $meta['note'] ?? '',
                'question' => $meta['question'] ?? '',
                'answer' => $meta['answer'] ?? '',
                'status' => isset( $meta['status'] ) ? 'closed' : 'open',
                'due_date' => $result['date'],
            ];
        }

        return $list;
    }
}
if ( ! function_exists( 'zume_get_user_plans' ) ) {
    function zume_get_user_plans( $user_id = null )
    {
        if ( is_null( $user_id ) ) {
            $user_id = get_current_user_id();
        }
        $log = zume_get_user_log( $user_id );
        $log_subtypes = array_column( $log, 'subtype' );

        global $wpdb;
        $contact_id = zume_get_user_contact_id( $user_id );
        $connected_plans = $wpdb->get_results( $wpdb->prepare(
            "SELECT p.ID as post_id, p.post_title as title, pm.meta_key, pm.meta_value
                    FROM wp_p2p p2
                    LEFT JOIN wp_posts p ON p.ID=p2.p2p_to
                    LEFT JOIN wp_postmeta pm ON pm.post_id=p2.p2p_to
                    WHERE p2.p2p_type = 'zume_plans_to_contacts'
                    AND p2.p2p_from = %d ",
            $contact_id
        ), ARRAY_A );

        $plans = [];
        if ( ! empty( $connected_plans ) ) {
            $participants = [];
            foreach ( $connected_plans as $connection ){
                if ( ! isset( $plans[$connection['post_id']] ) ) {
                    $plans[$connection['post_id']] = [];
                    $plans[$connection['post_id']]['title'] = $connection['title'];
                    $plans[$connection['post_id']]['participants'] = [];
                    $participants[] = $connection['post_id'];
                }
                if ( ((string) (int) $connection['meta_value'] === $connection['meta_value'])
                    && ($connection['meta_value'] <= PHP_INT_MAX)
                    && ($connection['meta_value'] >= ~PHP_INT_MAX)
                    && $connection['meta_key'] !== 'last_modified') {
                    $plans[$connection['post_id']][$connection['meta_key']] = [
                        'timestamp' => $connection['meta_value'],
                        'date' => date( 'Y-m-d', $connection['meta_value'] ),
                        'date_formatted' => date( 'M j, Y', $connection['meta_value'] ),
                        'completed' => in_array( $connection['meta_key'], $log_subtypes ),
                    ];
                } else {
                    $plans[$connection['post_id']][$connection['meta_key']] = $connection['meta_value'];
                }
            }
            $participants_string = implode( ',', $participants );
            $participants_result = $wpdb->get_results(
                "SELECT  p2.p2p_to as plan_id, p2.p2p_from as contact_id, pm.meta_value as user_id, p.post_title as user_name
                    FROM wp_p2p p2
            		LEFT JOIN wp_posts p ON p.ID=p2.p2p_from
					LEFT JOIN wp_postmeta pm ON p2.p2p_from=pm.post_id AND pm.meta_key = 'corresponds_to_user'
                    WHERE p2.p2p_type = 'zume_plans_to_contacts'
                    AND p2.p2p_to IN ( $participants_string ) ", ARRAY_A );

            foreach ( $participants_result as $participant ) {
                $plans[$participant['plan_id']]['participants'][] = [
                    'contact_id' => $participant['contact_id'],
                    'user_id' => $participant['user_id'],
                    'name' => $participant['user_name'],
                ];
            }
        }

        // @todo embelish the array with more info and convert the dates from unix.


        return $plans;
    }
}
if ( ! function_exists( 'zume_get_user_contact_id' ) ) {
    function zume_get_user_contact_id( $user_id ) {
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM wp_postmeta WHERE meta_key = 'corresponds_to_user' AND meta_value = %s", $user_id ) );
    }
}
if ( ! function_exists( 'zume_get_user_id_by_contact_id' ) ) {
    function zume_get_user_id_by_contact_id( $contact_id ) {
        global $wpdb;
        return $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM wp_usermeta WHERE meta_key = 'wp_corresponds_to_contact' AND meta_value = %s", $contact_id ) );
    }
}
if ( ! function_exists( 'zume_get_user_log' ) ) {
    function zume_get_user_log( $user_id ) {
        global $wpdb;
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT CONCAT( r.type, '_', r.subtype ) as log_key, r.*
                FROM wp_dt_reports r
                WHERE r.user_id = %s
                AND r.post_type = 'zume'
                ", $user_id ), ARRAY_A );

        if ( is_array( $results ) ) {
            return $results;
        } else {
            return [];
        }
    }
}
if ( ! function_exists( 'zume_languages' ) ) {
    function zume_languages( $type = 'code' ) {
        global $zume_languages_by_code, $zume_languages_by_locale;
        $zume_languages_by_code = array(
            'en' => array(
                'name' => 'English',
                'enDisplayName' => 'English',
                'code' => 'en',
                'locale' => 'en',
                'nativeName' => 'English',
                'rtl' => false,
            ),
            'ar' => array(
                'name' => 'Arabic',
                'enDisplayName' => 'Arabic',
                'code' => 'ar',
                'locale' => 'ar_LB',
                'nativeName' => 'العربية',
                'rtl' => true,
            ),
            'ar_jo' => array(
                'name' => 'Arabic (JO)',
                'enDisplayName' => 'Arabic (JO)',
                'code' => 'ar_jo',
                'locale' => 'ar_JO',
                'nativeName' => 'العربية - الأردن',
                'rtl' => true,
            ),
            'asl' => array(
                'name' => 'American Sign Language',
                'enDisplayName' => 'American Sign Language',
                'code' => 'asl',
                'locale' => 'asl',
                'nativeName' => 'Sign Language',
                'rtl' => false,
            ),
            'bho' => array(
                'name' => 'Bhojpuri',
                'enDisplayName' => 'Bhojpuri',
                'code' => 'bho',
                'locale' => 'bho',
                'nativeName' => 'भोजपुरी',
                'rtl' => false,
            ),
            'bn' => array(
                'name' => 'Bengali (India)',
                'enDisplayName' => 'Bengali (India)',
                'code' => 'bn',
                'locale' => 'bn_IN',
                'nativeName' => 'বাংলা',
                'rtl' => false,
            ),
            'bs' => array(
                'name' => 'Bosnian',
                'enDisplayName' => 'Bosnian',
                'code' => 'bs',
                'locale' => 'bs_BA',
                'nativeName' => 'Bosanski',
                'rtl' => false,
            ),
            'zhhk' => array(
                'name' => 'Cantonese (Traditional)',
                'enDisplayName' => 'Cantonese (Traditional)',
                'code' => 'zhhk',
                'locale' => 'zh_HK',
                'nativeName' => '粵語 (繁體)',
                'rtl' => false,
            ),
            'zhcn' => array(
                'name' => 'Chinese (Simplified)',
                'enDisplayName' => 'Chinese (Simplified)',
                'code' => 'zhcn',
                'locale' => 'zh_CN',
                'nativeName' => '国语（简体)',
                'rtl' => false,
            ),
            'zhtw' => array(
                'name' => 'Chinese (Traditional)',
                'enDisplayName' => 'Chinese (Traditional)',
                'code' => 'zhtw',
                'locale' => 'zh_TW',
                'nativeName' => '國語（繁體)',
                'rtl' => false,
            ),
            'hr' => array(
                'name' => 'Croatian',
                'enDisplayName' => 'Croatian',
                'code' => 'hr',
                'locale' => 'hr',
                'nativeName' => 'Hrvatski',
                'rtl' => false,
            ),
            'fa' => array(
                'name' => 'Farsi/Persian',
                'enDisplayName' => 'Farsi/Persian',
                'code' => 'fa',
                'locale' => 'fa_IR',
                'nativeName' => 'فارسی',
                'rtl' => true,
            ),
            'fr' => array(
                'name' => 'French',
                'enDisplayName' => 'French',
                'code' => 'fr',
                'locale' => 'fr_FR',
                'nativeName' => 'Français',
                'rtl' => false,
            ),
            'de' => array(
                'name' => 'German',
                'enDisplayName' => 'German',
                'code' => 'de',
                'locale' => 'de_DE',
                'nativeName' => 'Deutsch',
                'rtl' => false,
            ),
            'gu' => array(
                'namne' => 'Gujarati',
                'enDisplayName' => 'Gujarati',
                'code' => 'gu',
                'locale' => 'gu',
                'nativeName' => 'ગુજરાતી',
                'rtl' => false,
            ),
            'ha' => array(
                'name' => 'Hausa',
                'enDisplayName' => 'Hausa',
                'code' => 'ha',
                'locale' => 'ha_NG',
                'nativeName' => 'Hausa',
                'rtl' => false,
            ),
            'hi' => array(
                'name' => 'Hindi',
                'enDisplayName' => 'Hindi',
                'code' => 'hi',
                'locale' => 'hi_IN',
                'nativeName' => 'हिन्दी',
                'rtl' => false,
            ),
            'id' => array(
                'name' => 'Indonesian',
                'enDisplayName' => 'Indonesian',
                'code' => 'id',
                'locale' => 'id_ID',
                'nativeName' => 'Bahasa Indonesia',
                'rtl' => false,
            ),
            'it' => array(
                'name' => 'Italian',
                'enDisplayName' => 'Italian',
                'code' => 'it',
                'locale' => 'it_IT',
                'nativeName' => 'Italiano',
                'rtl' => false,
            ),
            'kn' => array(
                'name' => 'Kannada',
                'enDisplayName' => 'Kannada',
                'code' => 'kn',
                'locale' => 'kn',
                'nativeName' => 'ಕನ್ನಡ',
                'rtl' => false,
            ),
            'ko' => array(
                'name' => 'Korean',
                'enDisplayName' => 'Korean',
                'code' => 'ko',
                'locale' => 'ko_KR',
                'nativeName' => '한국어',
                'rtl' => false,
            ),
            'ku' => array(
                'name' => 'Kurdish',
                'enDisplayName' => 'Kurdish',
                'code' => 'ku',
                'locale' => 'ku',
                'nativeName' => 'کوردی',
                'rtl' => true,
            ),
            'lo' => array(
                'name' => 'Lao',
                'enDisplayName' => 'Lao',
                'code' => 'lo',
                'locale' => 'lo',
                'nativeName' => 'ພາສາລາວ',
                'rtl' => false,
            ),
            'mai' => array(
                'name' => 'Maithili',
                'enDisplayName' => 'Maithili',
                'code' => 'mai',
                'locale' => 'mai',
                'nativeName' => '𑒧𑒻𑒟𑒱𑒪𑒲',
                'rtl' => false,
            ),
            'mr' => array(
                'name' => 'Marathi',
                'enDisplayName' => 'Marathi',
                'code' => 'mr',
                'locale' => 'mr',
                'nativeName' => 'मराठी',
                'rtl' => false,
            ),
            'ml' => array(
                'name' => 'Malayalam',
                'enDisplayName' => 'Malayalam',
                'code' => 'ml',
                'locale' => 'ml',
                'nativeName' => 'മലയാളം',
                'rtl' => false,
            ),
            'ne' => array(
                'name' => 'Nepali',
                'enDisplayName' => 'Nepali',
                'code' => 'ne',
                'locale' => 'ne_NP',
                'nativeName' => 'नेपाली',
                'rtl' => false,
            ),
            'or' => array(
                'name' => 'Oriya',
                'enDisplayName' => 'Oriya',
                'code' => 'or',
                'locale' => 'or_IN',
                'nativeName' => 'ଓଡ଼ିଆ',
                'rtl' => false,
            ),
            'pa' => array(
                'name' => 'Punjabi',
                'enDisplayName' => 'Punjabi',
                'code' => 'pa',
                'locale' => 'pa_IN',
                'nativeName' => 'ਪੰਜਾਬੀ',
                'rtl' => false,
            ),
            'pt' => array(
                'name' => 'Portuguese',
                'enDisplayName' => 'Portuguese',
                'code' => 'pt',
                'locale' => 'pt_PT',
                'nativeName' => 'Português',
                'rtl' => false,
            ),
            'ru' => array(
                'name' => 'Russian',
                'enDisplayName' => 'Russian',
                'code' => 'ru',
                'locale' => 'ru_RU',
                'nativeName' => 'Русский',
                'rtl' => false,
            ),
            'ro' => array(
                'name' => 'Romanian',
                'enDisplayName' => 'Romanian',
                'code' => 'ro',
                'locale' => 'ro_RO',
                'nativeName' => 'Română',
                'rtl' => false,
            ),
            'sl' => array(
                'name' => 'Slovenian',
                'enDisplayName' => 'Slovenian',
                'code' => 'sl',
                'locale' => 'sl_Sl',
                'nativeName' => 'Slovenščina',
                'rtl' => false,
            ),
            'es' => array(
                'name' => 'Spanish',
                'enDisplayName' => 'Spanish',
                'code' => 'es',
                'locale' => 'es',
                'nativeName' => 'Español',
                'rtl' => false,
            ),
            'so' => array(
                'name' => 'Somali',
                'enDisplayName' => 'Somali',
                'code' => 'so',
                'locale' => 'so',
                'nativeName' => 'Soomaali',
                'rtl' => false,
            ),
            'swa' => array(
                'name' => 'Swahili',
                'enDisplayName' => 'Swahili',
                'code' => 'swa',
                'locale' => 'swa',
                'nativeName' => 'Kiswahili',
                'rtl' => false,
            ),
            'ta' => array(
                'name' => 'Tamil',
                'enDisplayName' => 'Tamil',
                'code' => 'ta',
                'locale' => 'ta_IN',
                'nativeName' => 'தமிழ்',
                'rtl' => false,
            ),
            'te' => array(
                'name' => 'Telugu',
                'enDisplayName' => 'Telugu',
                'code' => 'te',
                'locale' => 'te',
                'nativeName' => 'తెలుగు',
                'rtl' => false,
            ),
            'th' => array(
                'name' => 'Thai',
                'enDisplayName' => 'Thai',
                'code' => 'th',
                'locale' => 'th',
                'nativeName' => 'ไทย',
                'rtl' => false,
            ),
            'tr' => array(
                'name' => 'Turkish',
                'enDisplayName' => 'Turkish',
                'code' => 'tr',
                'locale' => 'tr_TR',
                'nativeName' => 'Türkçe',
                'rtl' => false,
            ),
            'ur' => array(
                'name' => 'Urdu',
                'enDisplayName' => 'Urdu',
                'code' => 'ur',
                'locale' => 'ur',
                'nativeName' => 'اردو',
                'rtl' => true,
            ),
            'vi' => array(
                'name' => 'Vietnamese',
                'enDisplayName' => 'Vietnamese',
                'code' => 'vi',
                'locale' => 'vi',
                'nativeName' => 'Tiếng Việt',
                'rtl' => false,
            ),
            'yo' => array(
                'name' => 'Yoruba',
                'enDisplayName' => 'Yoruba',
                'code' => 'yo',
                'locale' => 'yo',
                'nativeName' => 'Yorùbá',
                'rtl' => false,
            ),
        );
        foreach ( $zume_languages_by_code as $lang ) {
            $zume_languages_by_locale[$lang['locale']] = $lang;
        }

        if ( $type === 'code' ) {
            return $zume_languages_by_code;
        } else {
            return $zume_languages_by_locale;
        }
    }
    zume_languages();
}
if ( ! function_exists( 'zume_language_codes' ) ) {
    function zume_language_codes() {
        global $zume_languages_by_code;
        return array_keys( $zume_languages_by_code );
    }
}
if ( ! function_exists( 'zume_get_language_locale' ) ) {
    function zume_get_language_locale( $code ) {
        global $zume_languages_by_code;
        if ( isset( $zume_languages_by_code[$code]['locale'] ) ) {
            return $zume_languages_by_code[$code]['locale'];
        } else {
            return 'en';
        }
    }
}
if ( ! function_exists( 'zume_training_items' ) ) {
    function zume_training_items(): array {

        $training_items = [
            '1' => [
                'key' => '01',
                'title' => 'God Uses Ordinary People',
                'description' => "You'll see how God uses ordinary people doing simple things to make a big impact.",
                'type' => 'concept',
                'host' => true,
                'mawl' => false,
            ],
            '2' => [
                'key' => '02',
                'title' => 'Simple Definition of Disciple and Church',
                'description' => 'Discover the essence of being a disciple, making a disciple, and what is the church.',
                'type' => 'concept',
                'host' => true,
                'mawl' => false,
            ],
            '3' => [
                'key' => '03',
                'title' => 'Spiritual Breathing is Hearing and Obeying God',
                'description' => 'Being a disciple means we hear from God and we obey God.',
                'type' => 'concept',
                'host' => true,
                'mawl' => false,
            ],
            '4' => [
                'key' => '04',
                'title' => 'SOAPS Bible Reading',
                'description' => 'A tool for daily Bible study that helps you understand, obey, and share God’s Word.',
                'type' => 'tool',
                'host' => true,
                'mawl' => true,
            ],
            '5' => [
                'key' => '05',
                'title' => 'Accountability Groups',
                'description' => 'A tool for two or three people of the same gender to meet weekly and encourage each other in areas that are going well and reveal areas that need correction.',
                'type' => 'tool',
                'host' => true,
                'mawl' => true,
            ],
            '6' => [
                'key' => '06',
                'title' => 'Consumer vs Producer Lifestyle',
                'description' => "You'll discover the four main ways God makes everyday followers more like Jesus.",
                'type' => 'concept',
                'host' => true,
                'mawl' => false,
            ],
            '7' => [
                'key' => '07',
                'title' => 'How to Spend an Hour in Prayer',
                'description' => 'See how easy it is to spend an hour in prayer.',
                'type' => 'tool',
                'host' => true,
                'mawl' => true,
            ],
            '8' => [
                'key' => '08',
                'title' => 'Relational Stewardship – List of 100',
                'description' => 'A tool designed to help you be a good steward of your relationships.',
                'type' => 'tool',
                'host' => true,
                'mawl' => true,
            ],
            '9' => [
                'key' => '09',
                'title' => 'The Kingdom Economy',
                'description' => "Learn how God's economy is different from the world's. God invests more in those who are faithful with what they've already been given.",
                'type' => 'concept',
                'host' => true,
                'mawl' => false,
            ],
            '10' => [
                'key' => '10',
                'title' => 'The Gospel and How to Share It',
                'description' => 'Learn a way to share God’s Good News from the beginning of humanity all the way to the end of this age.',
                'type' => 'tool',
                'host' => true,
                'mawl' => true,
            ],
            '11' => [
                'key' => '11',
                'title' => 'Baptism and How To Do It',
                'description' => 'Jesus said, “Go and make disciples of all nations, BAPTIZING them in the name of the Father and of the Son and of the Holy Spirit…” Learn how to put this into practice.',
                'type' => 'tool',
                'host' => true,
                'mawl' => true,
            ],
            '12' => [
                'key' => '12',
                'title' => 'Prepare Your 3-Minute Testimony',
                'description' => 'Learn how to share your testimony in three minutes by sharing how Jesus has impacted your life.',
                'type' => 'tool',
                'host' => true,
                'mawl' => true,
            ],
            '13' => [
                'key' => '13',
                'title' => 'Vision Casting the Greatest Blessing',
                'description' => 'Learn a simple pattern of making not just one follower of Jesus but entire spiritual families who multiply for generations to come.',
                'type' => 'tool',
                'host' => true,
                'mawl' => true,
            ],
            '14' => [
                'key' => '14',
                'title' => 'Duckling Discipleship – Leading Immediately',
                'description' => 'Learn what ducklings have to do with disciple-making.',
                'type' => 'concept',
                'host' => true,
                'mawl' => false,
            ],
            '15' => [
                'key' => '15',
                'title' => 'Eyes to See Where the Kingdom Isn’t',
                'description' => 'Begin to see where God’s Kingdom isn’t. These are usually the places where God wants to work the most.',
                'type' => 'concept',
                'host' => true,
                'mawl' => false,
            ],
            '16' => [
                'key' => '16',
                'title' => 'The Lord’s Supper and How To Lead It',
                'description' => 'It’s a simple way to celebrate our intimate connection and ongoing relationship with Jesus. Learn a simple way to celebrate.',
                'type' => 'tool',
                'host' => true,
                'mawl' => true,
            ],
            '17' => [
                'key' => '17',
                'title' => 'Prayer Walking and How To Do It',
                'description' => "It’s a simple way to obey God’s command to pray for others. And it's just what it sounds like — praying to God while walking around!",
                'type' => 'tool',
                'host' => true,
                'mawl' => true,
            ],
            '18' => [
                'key' => '18',
                'title' => 'A Person of Peace and How To Find One',
                'description' => "Learn who a person of peace might be and how to know when you've found one.",
                'type' => 'concept',
                'host' => true,
                'mawl' => false,
            ],
            '19' => [
                'key' => '19',
                'title' => 'The BLESS Prayer Pattern',
                'description' => 'Practice a simple mnemonic to remind you of ways to pray for others.',
                'type' => 'tool',
                'host' => true,
                'mawl' => true,
            ],
            '20' => [
                'key' => '20',
                'title' => 'Faithfulness is Better Than Knowledge',
                'description' => "It's important what disciples know — but it's much more important what they DO with what they know.",
                'type' => 'concept',
                'host' => true,
                'mawl' => false,
            ],
            '21' => [
                'key' => '21',
                'title' => '3/3 Group Meeting Pattern',
                'description' => "A 3/3 Group is a way for followers of Jesus to meet, pray, learn, grow, fellowship and practice obeying and sharing what they've learned. In this way, a 3/3 Group is not just a small group but a Simple Church.",
                'type' => 'tool',
                'host' => true,
                'mawl' => true,
            ],
            '22' => [
                'key' => '22',
                'title' => 'Training Cycle for Maturing Disciples',
                'description' => 'Learn the training cycle and consider how it applies to disciple making.',
                'type' => 'tool',
                'host' => true,
                'mawl' => true,
            ],
            '23' => [
                'key' => '23',
                'title' => 'Leadership Cells',
                'description' => 'A Leadership Cell is a way someone who feels called to lead can develop their leadership by practicing serving.',
                'type' => 'concept',
                'host' => true,
                'mawl' => false,
            ],
            '24' => [
                'key' => '24',
                'title' => 'Expect Non-Sequential Growth',
                'description' => "See how disciple making doesn't have to be linear. Multiple things can happen at the same time.",
                'type' => 'concept',
                'host' => true,
                'mawl' => false,
            ],
            '25' => [
                'key' => '25',
                'title' => 'Pace of Multiplication Matters',
                'description' => 'Multiplying matters and multiplying quickly matters even more. See why pace matters.',
                'type' => 'concept',
                'host' => true,
                'mawl' => false,
            ],
            '26' => [
                'key' => '26',
                'title' => 'Always Part of Two Churches',
                'description' => "Learn how to obey Jesus' commands by going AND staying.",
                'type' => 'concept',
                'host' => true,
                'mawl' => true,
            ],
            '27' => [
                'key' => '27',
                'title' => 'Three-Month Plan',
                'description' => 'Create and share your plan for how you will implement the Zúme tools over the next three months.',
                'type' => 'tool',
                'host' => true,
                'mawl' => false,
            ],
            '28' => [
                'key' => '28',
                'title' => 'Coaching Checklist',
                'description' => 'A powerful tool you can use to quickly assess your own strengths and vulnerabilities when it comes to making disciples who multiply.',
                'type' => 'tool',
                'host' => true,
                'mawl' => false,
            ],
            '29' => [
                'key' => '29',
                'title' => 'Leadership in Networks',
                'description' => 'Learn how multiplying churches stay connected and live life together as an extended, spiritual family.',
                'type' => 'concept',
                'host' => true,
                'mawl' => false,
            ],
            '30' => [
                'key' => '30',
                'title' => 'Peer Mentoring Groups',
                'description' => 'This is a group that consists of people who are leading and starting 3/3 Groups. It also follows a 3/3 format and is a powerful way to assess the spiritual health of God’s work in your area.',
                'type' => 'concept',
                'host' => true,
                'mawl' => false,
            ],
            '31' => [
                'key' => '31',
                'title' => 'Four Fields Tool',
                'description' => 'The four fields diagnostic chart is a simple tool to be used by a leadership cell to reflect on the status of current efforts and the kingdom activity around them.',
                'type' => 'tool',
                'host' => true,
                'mawl' => true,
            ],
            '32' => [
                'key' => '32',
                'title' => 'Generational Mapping',
                'description' => 'Generation mapping is another simple tool to help leaders in a movement understand the growth around them.',
                'type' => 'tool',
                'host' => true,
                'mawl' => true,
            ],
        ];

        $list = [];
        foreach ( $training_items as $training_item ) {
            $index = $training_item['key'];
            $list[] = [
                'key' => $index,
                'type' => $training_item['type'],
                'title' => $training_item['title'],
                'description' => $training_item['description'],
                'host' => $training_item['host'] ? [
                    [
                        'label' => 'Heard',
                        'short_label' => 'H',
                        'type' => 'training',
                        'subtype' => $index.'_heard',
                        'key' => 'training_'.$index.'_heard',
                    ],
                    [
                        'label' => 'Obeyed',
                        'short_label' => 'O',
                        'type' => 'training',
                        'subtype' => $index.'_obeyed',
                        'key' => 'training_'.$index.'_obeyed',
                    ],
                    [
                        'label' => 'Shared',
                        'short_label' => 'S',
                        'type' => 'training',
                        'subtype' => $index.'_shared',
                        'key' => 'training_'.$index.'_shared',
                    ],
                    [
                        'label' => 'Trained',
                        'short_label' => 'T',
                        'type' => 'training',
                        'subtype' => $index.'_trained',
                        'key' => 'training_'.$index.'_trained',
                    ],
                ] : [],
                'mawl' => $training_item['mawl'] ? [
                    [
                        'label' => 'Modeling',
                        'short_label' => 'M',
                        'type' => 'coaching',
                        'subtype' => $index.'_modeling',
                        'key' => 'coaching_'.$index.'_modeling',
                    ],
                    [
                        'label' => 'Assisting',
                        'short_label' => 'A',
                        'type' => 'coaching',
                        'subtype' => $index.'_assisting',
                        'key' => 'coaching_'.$index.'_assisting',
                    ],
                    [
                        'label' => 'Watching',
                        'short_label' => 'W',
                        'type' => 'coaching',
                        'subtype' => $index.'_watching',
                        'key' => 'coaching_'.$index.'_watching',
                    ],
                    [
                        'label' => 'Launching',
                        'short_label' => 'L',
                        'type' => 'coaching',
                        'subtype' => $index.'_launching',
                        'key' => 'coaching_'.$index.'_launching',
                    ],
                ] : [],
            ];
        }

        return $list;
    }
}
if ( ! function_exists( 'zume_funnel_stages' ) ) {
    function zume_funnel_stages(): array {
        return [
            0 => [
                'key' => 'anonymous',
                'value' => 0,
                'label' => 'Anonymous',
                'label_full' => 'Anonymous',
                'description' => 'Anonymous visitors to the website.',
                'description_full' => 'Anonymous visitors to the website.',
                'characteristics' => [
                    'Anonymous website visitor',
                ],
                'priority_next_step' => 'Register',
                'next_steps' => [
                    'Register for a user account',
                    'Join an online training',
                    'Get a coach',
                ],
            ],
            1 => [
                'key' => 'registrant',
                'value' => 1,
                'label' => 'Registrant',
                'label_full' => 'Registrant',
                'description' => 'Trainee who has registered for the training.',
                'description_full' => 'Trainee who has registered for the training.',
                'characteristics' => [
                    'Has registered for a user account',
                ],
                'priority_next_step' => 'Make a training plan',
                'next_steps' => [
                    'Make a training plan',
                    'Invite friends',
                ],
            ],
            2 => [
                'key' => 'active_training_trainee',
                'value' => 2,
                'label' => 'Active Training',
                'label_full' => 'Active Training Trainee',
                'description' => 'Trainee who is in active training.',
                'description_full' => 'An active trainee is someone who has made a training plan. They are actively working through the training.',
                'characteristics' => [
                    'Has made a training plan',
                ],
                'priority_next_step' => 'Complete training',
                'next_steps' => [
                    'Complete training',
                    'Create post training plan',
                ],
            ],
            3 => [
                'key' => 'post_training_trainee',
                'value' => 3,
                'label' => 'Post-Training',
                'label_full' => 'Post-Training Trainee',
                'description' => 'Trainee who has completed training.',
                'description_full' => 'Trainee who has completed training.',
                'characteristics' => [
                    'Has completed training',
                ],
                'priority_next_step' => 'Make first practitioner report',
                'next_steps' => [
                    'Make first practitioner report',
                    'Complete post training plan',
                    'Establish ongoing coaching relationship',
                ],
            ],
            4 => [
                'key' => 'partial_practitioner',
                'value' => 4,
                'label' => 'Partial Practitioner',
                'label_full' => '(S1) Partial Practitioner',
                'description' => 'Practitioner still coaching through MAWL checklist.',
                'description_full' => 'Practitioner still coaching through MAWL checklist.',
                'characteristics' => [
                    'Has made first practitioner report',
                    'Working on HOST/MAWL checklist, but not complete',
                    'Consistent effort, inconsistent fruit',
                    'Not multiplying',
                ],
                'priority_next_step' => '',
                'next_steps' => [
                    'Full skills competence',
                    'Continued reporting',
                    'Connect with S1 and S2 practitioners',
                ],
            ],
            5 => [
                'key' => 'full_practitioner',
                'value' => 5,
                'label' => 'Practitioner',
                'label_full' => '(S2) Practitioner',
                'description' => 'Practitioner who has completed the MAWL checklist, but is not multiplying.',
                'description_full' => 'Practitioner who has completed the MAWL checklist, but is not multiplying.',
                'characteristics' => [
                    'Has completed HOST/MAWL checklist',
                    'Consistent effort, inconsistent fruit',
                    'Inconsistent 1st generation fruit',
                    'Not multiplying',
                ],
                'priority_next_step' => 'Consistent 2,3,4 generation fruit',
                'next_steps' => [
                    'Consistent 2,3,4 disciple generation fruit',
                    'Consistent 2,3,4 group generation fruit',
                    'Peer coaching with S2 and S3 practitioners',
                ],
            ],
            6 => [
                'key' => 'multiplying_practitioner',
                'value' => 6,
                'label' => 'Multiplying Practitioner',
                'label_full' => '(S3) Multiplying Practitioner',
                'description' => 'Practitioner who is seeing generational fruit.',
                'description_full' => 'Practitioner who is seeing generational fruit.',
                'characteristics' => [
                    '2,3,4 generations of disciples',
                    '2,3,4 generations of churches',
                ],
                'priority_next_step' => 'Downstream coaching for consistent generations',
                'next_steps' => [
                    'Downstream coaching for consistent generations',
                ],
            ],
        ];
    }
}
if ( ! function_exists( 'zume_mirror_url' ) ) {
    function zume_mirror_url() {
        return 'https://storage.googleapis.com/zume-file-mirror/';
    }
}
if ( ! function_exists( 'zume_current_language' ) ) {
    function zume_current_language() {
        if ( function_exists( 'pll_the_languages' ) ) {
            $current_language = pll_current_language();
            return ( ! empty( $current_language ) ) ? $current_language : 'en';
        }

        $locale = get_locale();

        if ( empty( $local ) || 'en_US' === $locale ) {
            return 'en';
        }

        return $locale;
    }
}
if ( ! function_exists( 'zume_format_int' ) ) {
    function zume_format_int( $int )
    {
        $int = (float) $int;
        return number_format( $int, 0, '.', ',' );
    }
}
if ( ! function_exists( 'zume_get_valence' ) ) {
    function zume_get_valence( float $value, float $compare, $negative_stat = false )
    {
        $percent = zume_get_percent( $value, $compare );

        if ( $negative_stat ) {
            if ( $percent > 20 ) {
                $valence = 'valence-darkred';
            } else if ( $percent > 10 ) {
                $valence = 'valence-red';
            } else if ( $percent < -10 ) {
                $valence = 'valence-green';
            } else if ( $percent < -20 ) {
                $valence = 'valence-darkgreen';
            } else {
                $valence = 'valence-grey';
            }
        } elseif ( $percent > 20 ) {
                $valence = 'valence-darkgreen';
        } else if ( $percent > 10 ) {
            $valence = 'valence-green';
        } else if ( $percent < -10 ) {
            $valence = 'valence-red';
        } else if ( $percent < -20 ) {
            $valence = 'valence-darkred';
        } else {
            $valence = 'valence-grey';
        }

        return $valence;
    }
}
if ( ! function_exists( 'zume_get_percent' ) ) {
    function zume_get_percent( float $value, float $compare )
    {
        $percent = ( $value / $compare ) * 100;
        if ( $percent > 100 ) {
            $percent = round( $percent - 100, 1 );
        } else if ( $percent < 100 ) {
            $percent = round( ( 100 - $percent ), 1 ) * -1;
        } else {
            $percent = 0;
        }
        return $percent;
    }
}
if ( ! function_exists( 'zume_get_timezones' ) ) {
    function zume_get_timezones( string $key = null ) : array {
        $timezones = [
            "Africa/Abidjan" => [
                "timezone" => "Africa/Abidjan",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Africa/Accra" => [
                "timezone" => "Africa/Accra",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Africa/Addis_Ababa" => [
                "timezone" => "Africa/Addis_Ababa",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "Africa/Algiers" => [
                "timezone" => "Africa/Algiers",
                "gmt_offset" => "3600",
                "dst_offset" => "3600",
            ],
            "Africa/Asmara" => [
                "timezone" => "Africa/Asmara",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "Africa/Asmera" => [
                "timezone" => "Africa/Asmera",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "Africa/Bamako" => [
                "timezone" => "Africa/Bamako",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Africa/Bangui" => [
                "timezone" => "Africa/Bangui",
                "gmt_offset" => "3600",
                "dst_offset" => "3600",
            ],
            "Africa/Banjul" => [
                "timezone" => "Africa/Banjul",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Africa/Bissau" => [
                "timezone" => "Africa/Bissau",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Africa/Blantyre" => [
                "timezone" => "Africa/Blantyre",
                "gmt_offset" => "7200",
                "dst_offset" => "7200",
            ],
            "Africa/Brazzaville" => [
                "timezone" => "Africa/Brazzaville",
                "gmt_offset" => "3600",
                "dst_offset" => "3600",
            ],
            "Africa/Bujumbura" => [
                "timezone" => "Africa/Bujumbura",
                "gmt_offset" => "7200",
                "dst_offset" => "7200",
            ],
            "Africa/Cairo" => [
                "timezone" => "Africa/Cairo",
                "gmt_offset" => "7200",
                "dst_offset" => "7200",
            ],
            "Africa/Casablanca" => [
                "timezone" => "Africa/Casablanca",
                "gmt_offset" => "0",
                "dst_offset" => "3600",
            ],
            "Africa/Ceuta" => [
                "timezone" => "Africa/Ceuta",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Africa/Conakry" => [
                "timezone" => "Africa/Conakry",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Africa/Dakar" => [
                "timezone" => "Africa/Dakar",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Africa/Dar_es_Salaam" => [
                "timezone" => "Africa/Dar_es_Salaam",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "Africa/Djibouti" => [
                "timezone" => "Africa/Djibouti",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "Africa/Douala" => [
                "timezone" => "Africa/Douala",
                "gmt_offset" => "3600",
                "dst_offset" => "3600",
            ],
            "Africa/El_Aaiun" => [
                "timezone" => "Africa/El_Aaiun",
                "gmt_offset" => "0",
                "dst_offset" => "3600",
            ],
            "Africa/Freetown" => [
                "timezone" => "Africa/Freetown",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Africa/Gaborone" => [
                "timezone" => "Africa/Gaborone",
                "gmt_offset" => "7200",
                "dst_offset" => "7200",
            ],
            "Africa/Harare" => [
                "timezone" => "Africa/Harare",
                "gmt_offset" => "7200",
                "dst_offset" => "7200",
            ],
            "Africa/Johannesburg" => [
                "timezone" => "Africa/Johannesburg",
                "gmt_offset" => "7200",
                "dst_offset" => "7200",
            ],
            "Africa/Juba" => [
                "timezone" => "Africa/Juba",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "Africa/Kampala" => [
                "timezone" => "Africa/Kampala",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "Africa/Khartoum" => [
                "timezone" => "Africa/Khartoum",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "Africa/Kigali" => [
                "timezone" => "Africa/Kigali",
                "gmt_offset" => "7200",
                "dst_offset" => "7200",
            ],
            "Africa/Kinshasa" => [
                "timezone" => "Africa/Kinshasa",
                "gmt_offset" => "3600",
                "dst_offset" => "3600",
            ],
            "Africa/Lagos" => [
                "timezone" => "Africa/Lagos",
                "gmt_offset" => "3600",
                "dst_offset" => "3600",
            ],
            "Africa/Libreville" => [
                "timezone" => "Africa/Libreville",
                "gmt_offset" => "3600",
                "dst_offset" => "3600",
            ],
            "Africa/Lome" => [
                "timezone" => "Africa/Lome",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Africa/Luanda" => [
                "timezone" => "Africa/Luanda",
                "gmt_offset" => "3600",
                "dst_offset" => "3600",
            ],
            "Africa/Lubumbashi" => [
                "timezone" => "Africa/Lubumbashi",
                "gmt_offset" => "7200",
                "dst_offset" => "7200",
            ],
            "Africa/Lusaka" => [
                "timezone" => "Africa/Lusaka",
                "gmt_offset" => "7200",
                "dst_offset" => "7200",
            ],
            "Africa/Malabo" => [
                "timezone" => "Africa/Malabo",
                "gmt_offset" => "3600",
                "dst_offset" => "3600",
            ],
            "Africa/Maputo" => [
                "timezone" => "Africa/Maputo",
                "gmt_offset" => "7200",
                "dst_offset" => "7200",
            ],
            "Africa/Maseru" => [
                "timezone" => "Africa/Maseru",
                "gmt_offset" => "7200",
                "dst_offset" => "7200",
            ],
            "Africa/Mbabane" => [
                "timezone" => "Africa/Mbabane",
                "gmt_offset" => "7200",
                "dst_offset" => "7200",
            ],
            "Africa/Mogadishu" => [
                "timezone" => "Africa/Mogadishu",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "Africa/Monrovia" => [
                "timezone" => "Africa/Monrovia",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Africa/Nairobi" => [
                "timezone" => "Africa/Nairobi",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "Africa/Ndjamena" => [
                "timezone" => "Africa/Ndjamena",
                "gmt_offset" => "3600",
                "dst_offset" => "3600",
            ],
            "Africa/Niamey" => [
                "timezone" => "Africa/Niamey",
                "gmt_offset" => "3600",
                "dst_offset" => "3600",
            ],
            "Africa/Nouakchott" => [
                "timezone" => "Africa/Nouakchott",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Africa/Ouagadougou" => [
                "timezone" => "Africa/Ouagadougou",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Africa/Porto-Novo" => [
                "timezone" => "Africa/Porto-Novo",
                "gmt_offset" => "3600",
                "dst_offset" => "3600",
            ],
            "Africa/Sao_Tome" => [
                "timezone" => "Africa/Sao_Tome",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Africa/Timbuktu" => [
                "timezone" => "Africa/Timbuktu",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Africa/Tripoli" => [
                "timezone" => "Africa/Tripoli",
                "gmt_offset" => "7200",
                "dst_offset" => "7200",
            ],
            "Africa/Tunis" => [
                "timezone" => "Africa/Tunis",
                "gmt_offset" => "3600",
                "dst_offset" => "3600",
            ],
            "Africa/Windhoek" => [
                "timezone" => "Africa/Windhoek",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "America/Adak" => [
                "timezone" => "America/Adak",
                "gmt_offset" => "-36000",
                "dst_offset" => "-32400",
            ],
            "America/Anchorage" => [
                "timezone" => "America/Anchorage",
                "gmt_offset" => "-32400",
                "dst_offset" => "-28800",
            ],
            "America/Anguilla" => [
                "timezone" => "America/Anguilla",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/Antigua" => [
                "timezone" => "America/Antigua",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/Araguaina" => [
                "timezone" => "America/Araguaina",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Argentina/Buenos_Aires" => [
                "timezone" => "America/Argentina/Buenos_Aires",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Argentina/Catamarca" => [
                "timezone" => "America/Argentina/Catamarca",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Argentina/ComodRivadavia" => [
                "timezone" => "America/Argentina/ComodRivadavia",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Argentina/Cordoba" => [
                "timezone" => "America/Argentina/Cordoba",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Argentina/Jujuy" => [
                "timezone" => "America/Argentina/Jujuy",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Argentina/La_Rioja" => [
                "timezone" => "America/Argentina/La_Rioja",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Argentina/Mendoza" => [
                "timezone" => "America/Argentina/Mendoza",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Argentina/Rio_Gallegos" => [
                "timezone" => "America/Argentina/Rio_Gallegos",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Argentina/Salta" => [
                "timezone" => "America/Argentina/Salta",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Argentina/San_Juan" => [
                "timezone" => "America/Argentina/San_Juan",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Argentina/San_Luis" => [
                "timezone" => "America/Argentina/San_Luis",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Argentina/Tucuman" => [
                "timezone" => "America/Argentina/Tucuman",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Argentina/Ushuaia" => [
                "timezone" => "America/Argentina/Ushuaia",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Aruba" => [
                "timezone" => "America/Aruba",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/Asuncion" => [
                "timezone" => "America/Asuncion",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Atikokan" => [
                "timezone" => "America/Atikokan",
                "gmt_offset" => "-18000",
                "dst_offset" => "-18000",
            ],
            "America/Atka" => [
                "timezone" => "America/Atka",
                "gmt_offset" => "-36000",
                "dst_offset" => "-32400",
            ],
            "America/Bahia" => [
                "timezone" => "America/Bahia",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Bahia_Banderas" => [
                "timezone" => "America/Bahia_Banderas",
                "gmt_offset" => "-21600",
                "dst_offset" => "-18000",
            ],
            "America/Barbados" => [
                "timezone" => "America/Barbados",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/Belem" => [
                "timezone" => "America/Belem",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Belize" => [
                "timezone" => "America/Belize",
                "gmt_offset" => "-21600",
                "dst_offset" => "-21600",
            ],
            "America/Blanc-Sablon" => [
                "timezone" => "America/Blanc-Sablon",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/Boa_Vista" => [
                "timezone" => "America/Boa_Vista",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/Bogota" => [
                "timezone" => "America/Bogota",
                "gmt_offset" => "-18000",
                "dst_offset" => "-18000",
            ],
            "America/Boise" => [
                "timezone" => "America/Boise",
                "gmt_offset" => "-25200",
                "dst_offset" => "-21600",
            ],
            "America/Buenos_Aires" => [
                "timezone" => "America/Buenos_Aires",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Cambridge_Bay" => [
                "timezone" => "America/Cambridge_Bay",
                "gmt_offset" => "-25200",
                "dst_offset" => "-21600",
            ],
            "America/Campo_Grande" => [
                "timezone" => "America/Campo_Grande",
                "gmt_offset" => "-14400",
                "dst_offset" => "-10800",
            ],
            "America/Cancun" => [
                "timezone" => "America/Cancun",
                "gmt_offset" => "-18000",
                "dst_offset" => "-18000",
            ],
            "America/Caracas" => [
                "timezone" => "America/Caracas",
                "gmt_offset" => "-16200",
                "dst_offset" => "-16200",
            ],
            "America/Catamarca" => [
                "timezone" => "America/Catamarca",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Cayenne" => [
                "timezone" => "America/Cayenne",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Cayman" => [
                "timezone" => "America/Cayman",
                "gmt_offset" => "-18000",
                "dst_offset" => "-18000",
            ],
            "America/Chicago" => [
                "timezone" => "America/Chicago",
                "gmt_offset" => "-21600",
                "dst_offset" => "-18000",
            ],
            "America/Chihuahua" => [
                "timezone" => "America/Chihuahua",
                "gmt_offset" => "-25200",
                "dst_offset" => "-21600",
            ],
            "America/Coral_Harbour" => [
                "timezone" => "America/Coral_Harbour",
                "gmt_offset" => "-18000",
                "dst_offset" => "-18000",
            ],
            "America/Cordoba" => [
                "timezone" => "America/Cordoba",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Costa_Rica" => [
                "timezone" => "America/Costa_Rica",
                "gmt_offset" => "-21600",
                "dst_offset" => "-21600",
            ],
            "America/Creston" => [
                "timezone" => "America/Creston",
                "gmt_offset" => "-25200",
                "dst_offset" => "-25200",
            ],
            "America/Cuiaba" => [
                "timezone" => "America/Cuiaba",
                "gmt_offset" => "-14400",
                "dst_offset" => "-10800",
            ],
            "America/Curacao" => [
                "timezone" => "America/Curacao",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/Danmarkshavn" => [
                "timezone" => "America/Danmarkshavn",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "America/Dawson" => [
                "timezone" => "America/Dawson",
                "gmt_offset" => "-28800",
                "dst_offset" => "-25200",
            ],
            "America/Dawson_Creek" => [
                "timezone" => "America/Dawson_Creek",
                "gmt_offset" => "-25200",
                "dst_offset" => "-25200",
            ],
            "America/Denver" => [
                "timezone" => "America/Denver",
                "gmt_offset" => "-25200",
                "dst_offset" => "-21600",
            ],
            "America/Detroit" => [
                "timezone" => "America/Detroit",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "America/Dominica" => [
                "timezone" => "America/Dominica",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/Edmonton" => [
                "timezone" => "America/Edmonton",
                "gmt_offset" => "-25200",
                "dst_offset" => "-21600",
            ],
            "America/Eirunepe" => [
                "timezone" => "America/Eirunepe",
                "gmt_offset" => "-18000",
                "dst_offset" => "-18000",
            ],
            "America/El_Salvador" => [
                "timezone" => "America/El_Salvador",
                "gmt_offset" => "-21600",
                "dst_offset" => "-21600",
            ],
            "America/Ensenada" => [
                "timezone" => "America/Ensenada",
                "gmt_offset" => "-28800",
                "dst_offset" => "-25200",
            ],
            "America/Fort_Nelson" => [
                "timezone" => "America/Fort_Nelson",
                "gmt_offset" => "-25200",
                "dst_offset" => "-25200",
            ],
            "America/Fort_Wayne" => [
                "timezone" => "America/Fort_Wayne",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "America/Fortaleza" => [
                "timezone" => "America/Fortaleza",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Glace_Bay" => [
                "timezone" => "America/Glace_Bay",
                "gmt_offset" => "-14400",
                "dst_offset" => "-10800",
            ],
            "America/Godthab" => [
                "timezone" => "America/Godthab",
                "gmt_offset" => "-10800",
                "dst_offset" => "-7200",
            ],
            "America/Goose_Bay" => [
                "timezone" => "America/Goose_Bay",
                "gmt_offset" => "-14400",
                "dst_offset" => "-10800",
            ],
            "America/Grand_Turk" => [
                "timezone" => "America/Grand_Turk",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/Grenada" => [
                "timezone" => "America/Grenada",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/Guadeloupe" => [
                "timezone" => "America/Guadeloupe",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/Guatemala" => [
                "timezone" => "America/Guatemala",
                "gmt_offset" => "-21600",
                "dst_offset" => "-21600",
            ],
            "America/Guayaquil" => [
                "timezone" => "America/Guayaquil",
                "gmt_offset" => "-18000",
                "dst_offset" => "-18000",
            ],
            "America/Guyana" => [
                "timezone" => "America/Guyana",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/Halifax" => [
                "timezone" => "America/Halifax",
                "gmt_offset" => "-14400",
                "dst_offset" => "-10800",
            ],
            "America/Havana" => [
                "timezone" => "America/Havana",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "America/Hermosillo" => [
                "timezone" => "America/Hermosillo",
                "gmt_offset" => "-25200",
                "dst_offset" => "-25200",
            ],
            "America/Indiana/Indianapolis" => [
                "timezone" => "America/Indiana/Indianapolis",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "America/Indiana/Knox" => [
                "timezone" => "America/Indiana/Knox",
                "gmt_offset" => "-21600",
                "dst_offset" => "-18000",
            ],
            "America/Indiana/Marengo" => [
                "timezone" => "America/Indiana/Marengo",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "America/Indiana/Petersburg" => [
                "timezone" => "America/Indiana/Petersburg",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "America/Indiana/Tell_City" => [
                "timezone" => "America/Indiana/Tell_City",
                "gmt_offset" => "-21600",
                "dst_offset" => "-18000",
            ],
            "America/Indiana/Vevay" => [
                "timezone" => "America/Indiana/Vevay",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "America/Indiana/Vincennes" => [
                "timezone" => "America/Indiana/Vincennes",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "America/Indiana/Winamac" => [
                "timezone" => "America/Indiana/Winamac",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "America/Indianapolis" => [
                "timezone" => "America/Indianapolis",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "America/Inuvik" => [
                "timezone" => "America/Inuvik",
                "gmt_offset" => "-25200",
                "dst_offset" => "-21600",
            ],
            "America/Iqaluit" => [
                "timezone" => "America/Iqaluit",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "America/Jamaica" => [
                "timezone" => "America/Jamaica",
                "gmt_offset" => "-18000",
                "dst_offset" => "-18000",
            ],
            "America/Jujuy" => [
                "timezone" => "America/Jujuy",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Juneau" => [
                "timezone" => "America/Juneau",
                "gmt_offset" => "-32400",
                "dst_offset" => "-28800",
            ],
            "America/Kentucky/Louisville" => [
                "timezone" => "America/Kentucky/Louisville",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "America/Kentucky/Monticello" => [
                "timezone" => "America/Kentucky/Monticello",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "America/Knox_IN" => [
                "timezone" => "America/Knox_IN",
                "gmt_offset" => "-21600",
                "dst_offset" => "-18000",
            ],
            "America/Kralendijk" => [
                "timezone" => "America/Kralendijk",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/La_Paz" => [
                "timezone" => "America/La_Paz",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/Lima" => [
                "timezone" => "America/Lima",
                "gmt_offset" => "-18000",
                "dst_offset" => "-18000",
            ],
            "America/Los_Angeles" => [
                "timezone" => "America/Los_Angeles",
                "gmt_offset" => "-28800",
                "dst_offset" => "-25200",
            ],
            "America/Louisville" => [
                "timezone" => "America/Louisville",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "America/Lower_Princes" => [
                "timezone" => "America/Lower_Princes",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/Maceio" => [
                "timezone" => "America/Maceio",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Managua" => [
                "timezone" => "America/Managua",
                "gmt_offset" => "-21600",
                "dst_offset" => "-21600",
            ],
            "America/Manaus" => [
                "timezone" => "America/Manaus",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/Marigot" => [
                "timezone" => "America/Marigot",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/Martinique" => [
                "timezone" => "America/Martinique",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/Matamoros" => [
                "timezone" => "America/Matamoros",
                "gmt_offset" => "-21600",
                "dst_offset" => "-18000",
            ],
            "America/Mazatlan" => [
                "timezone" => "America/Mazatlan",
                "gmt_offset" => "-25200",
                "dst_offset" => "-21600",
            ],
            "America/Mendoza" => [
                "timezone" => "America/Mendoza",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Menominee" => [
                "timezone" => "America/Menominee",
                "gmt_offset" => "-21600",
                "dst_offset" => "-18000",
            ],
            "America/Merida" => [
                "timezone" => "America/Merida",
                "gmt_offset" => "-21600",
                "dst_offset" => "-18000",
            ],
            "America/Metlakatla" => [
                "timezone" => "America/Metlakatla",
                "gmt_offset" => "-28800",
                "dst_offset" => "-28800",
            ],
            "America/Mexico_City" => [
                "timezone" => "America/Mexico_City",
                "gmt_offset" => "-21600",
                "dst_offset" => "-18000",
            ],
            "America/Miquelon" => [
                "timezone" => "America/Miquelon",
                "gmt_offset" => "-10800",
                "dst_offset" => "-7200",
            ],
            "America/Moncton" => [
                "timezone" => "America/Moncton",
                "gmt_offset" => "-14400",
                "dst_offset" => "-10800",
            ],
            "America/Monterrey" => [
                "timezone" => "America/Monterrey",
                "gmt_offset" => "-21600",
                "dst_offset" => "-18000",
            ],
            "America/Montevideo" => [
                "timezone" => "America/Montevideo",
                "gmt_offset" => "-10800",
                "dst_offset" => "-7200",
            ],
            "America/Montreal" => [
                "timezone" => "America/Montreal",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "America/Montserrat" => [
                "timezone" => "America/Montserrat",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/Nassau" => [
                "timezone" => "America/Nassau",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "America/New_York" => [
                "timezone" => "America/New_York",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "America/Nipigon" => [
                "timezone" => "America/Nipigon",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "America/Nome" => [
                "timezone" => "America/Nome",
                "gmt_offset" => "-32400",
                "dst_offset" => "-28800",
            ],
            "America/Noronha" => [
                "timezone" => "America/Noronha",
                "gmt_offset" => "-7200",
                "dst_offset" => "-7200",
            ],
            "America/North_Dakota/Beulah" => [
                "timezone" => "America/North_Dakota/Beulah",
                "gmt_offset" => "-21600",
                "dst_offset" => "-18000",
            ],
            "America/North_Dakota/Center" => [
                "timezone" => "America/North_Dakota/Center",
                "gmt_offset" => "-21600",
                "dst_offset" => "-18000",
            ],
            "America/North_Dakota/New_Salem" => [
                "timezone" => "America/North_Dakota/New_Salem",
                "gmt_offset" => "-21600",
                "dst_offset" => "-18000",
            ],
            "America/Ojinaga" => [
                "timezone" => "America/Ojinaga",
                "gmt_offset" => "-25200",
                "dst_offset" => "-21600",
            ],
            "America/Panama" => [
                "timezone" => "America/Panama",
                "gmt_offset" => "-18000",
                "dst_offset" => "-18000",
            ],
            "America/Pangnirtung" => [
                "timezone" => "America/Pangnirtung",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "America/Paramaribo" => [
                "timezone" => "America/Paramaribo",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Phoenix" => [
                "timezone" => "America/Phoenix",
                "gmt_offset" => "-25200",
                "dst_offset" => "-25200",
            ],
            "America/Port-au-Prince" => [
                "timezone" => "America/Port-au-Prince",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "America/Port_of_Spain" => [
                "timezone" => "America/Port_of_Spain",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/Porto_Acre" => [
                "timezone" => "America/Porto_Acre",
                "gmt_offset" => "-18000",
                "dst_offset" => "-18000",
            ],
            "America/Porto_Velho" => [
                "timezone" => "America/Porto_Velho",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/Puerto_Rico" => [
                "timezone" => "America/Puerto_Rico",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/Rainy_River" => [
                "timezone" => "America/Rainy_River",
                "gmt_offset" => "-21600",
                "dst_offset" => "-18000",
            ],
            "America/Rankin_Inlet" => [
                "timezone" => "America/Rankin_Inlet",
                "gmt_offset" => "-21600",
                "dst_offset" => "-18000",
            ],
            "America/Recife" => [
                "timezone" => "America/Recife",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Regina" => [
                "timezone" => "America/Regina",
                "gmt_offset" => "-21600",
                "dst_offset" => "-21600",
            ],
            "America/Resolute" => [
                "timezone" => "America/Resolute",
                "gmt_offset" => "-21600",
                "dst_offset" => "-18000",
            ],
            "America/Rio_Branco" => [
                "timezone" => "America/Rio_Branco",
                "gmt_offset" => "-18000",
                "dst_offset" => "-18000",
            ],
            "America/Rosario" => [
                "timezone" => "America/Rosario",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Santa_Isabel" => [
                "timezone" => "America/Santa_Isabel",
                "gmt_offset" => "-28800",
                "dst_offset" => "-25200",
            ],
            "America/Santarem" => [
                "timezone" => "America/Santarem",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Santiago" => [
                "timezone" => "America/Santiago",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "America/Santo_Domingo" => [
                "timezone" => "America/Santo_Domingo",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/Sao_Paulo" => [
                "timezone" => "America/Sao_Paulo",
                "gmt_offset" => "-10800",
                "dst_offset" => "-7200",
            ],
            "America/Scoresbysund" => [
                "timezone" => "America/Scoresbysund",
                "gmt_offset" => "-3600",
                "dst_offset" => "0",
            ],
            "America/Shiprock" => [
                "timezone" => "America/Shiprock",
                "gmt_offset" => "-25200",
                "dst_offset" => "-21600",
            ],
            "America/Sitka" => [
                "timezone" => "America/Sitka",
                "gmt_offset" => "-32400",
                "dst_offset" => "-28800",
            ],
            "America/St_Barthelemy" => [
                "timezone" => "America/St_Barthelemy",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/St_Johns" => [
                "timezone" => "America/St_Johns",
                "gmt_offset" => "-12600",
                "dst_offset" => "-9000",
            ],
            "America/St_Kitts" => [
                "timezone" => "America/St_Kitts",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/St_Lucia" => [
                "timezone" => "America/St_Lucia",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/St_Thomas" => [
                "timezone" => "America/St_Thomas",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/St_Vincent" => [
                "timezone" => "America/St_Vincent",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/Swift_Current" => [
                "timezone" => "America/Swift_Current",
                "gmt_offset" => "-21600",
                "dst_offset" => "-21600",
            ],
            "America/Tegucigalpa" => [
                "timezone" => "America/Tegucigalpa",
                "gmt_offset" => "-21600",
                "dst_offset" => "-21600",
            ],
            "America/Thule" => [
                "timezone" => "America/Thule",
                "gmt_offset" => "-14400",
                "dst_offset" => "-10800",
            ],
            "America/Thunder_Bay" => [
                "timezone" => "America/Thunder_Bay",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "America/Tijuana" => [
                "timezone" => "America/Tijuana",
                "gmt_offset" => "-28800",
                "dst_offset" => "-25200",
            ],
            "America/Toronto" => [
                "timezone" => "America/Toronto",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "America/Tortola" => [
                "timezone" => "America/Tortola",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/Vancouver" => [
                "timezone" => "America/Vancouver",
                "gmt_offset" => "-28800",
                "dst_offset" => "-25200",
            ],
            "America/Virgin" => [
                "timezone" => "America/Virgin",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "America/Whitehorse" => [
                "timezone" => "America/Whitehorse",
                "gmt_offset" => "-28800",
                "dst_offset" => "-25200",
            ],
            "America/Winnipeg" => [
                "timezone" => "America/Winnipeg",
                "gmt_offset" => "-21600",
                "dst_offset" => "-18000",
            ],
            "America/Yakutat" => [
                "timezone" => "America/Yakutat",
                "gmt_offset" => "-32400",
                "dst_offset" => "-28800",
            ],
            "America/Yellowknife" => [
                "timezone" => "America/Yellowknife",
                "gmt_offset" => "-25200",
                "dst_offset" => "-21600",
            ],
            "Antarctica/Casey" => [
                "timezone" => "Antarctica/Casey",
                "gmt_offset" => "28800",
                "dst_offset" => "28800",
            ],
            "Antarctica/Davis" => [
                "timezone" => "Antarctica/Davis",
                "gmt_offset" => "25200",
                "dst_offset" => "25200",
            ],
            "Antarctica/DumontDUrville" => [
                "timezone" => "Antarctica/DumontDUrville",
                "gmt_offset" => "36000",
                "dst_offset" => "36000",
            ],
            "Antarctica/Macquarie" => [
                "timezone" => "Antarctica/Macquarie",
                "gmt_offset" => "39600",
                "dst_offset" => "39600",
            ],
            "Antarctica/Mawson" => [
                "timezone" => "Antarctica/Mawson",
                "gmt_offset" => "18000",
                "dst_offset" => "18000",
            ],
            "Antarctica/McMurdo" => [
                "timezone" => "Antarctica/McMurdo",
                "gmt_offset" => "43200",
                "dst_offset" => "46800",
            ],
            "Antarctica/Palmer" => [
                "timezone" => "Antarctica/Palmer",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "Antarctica/Rothera" => [
                "timezone" => "Antarctica/Rothera",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "Antarctica/South_Pole" => [
                "timezone" => "Antarctica/South_Pole",
                "gmt_offset" => "43200",
                "dst_offset" => "46800",
            ],
            "Antarctica/Syowa" => [
                "timezone" => "Antarctica/Syowa",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "Antarctica/Troll" => [
                "timezone" => "Antarctica/Troll",
                "gmt_offset" => "0",
                "dst_offset" => "7200",
            ],
            "Antarctica/Vostok" => [
                "timezone" => "Antarctica/Vostok",
                "gmt_offset" => "21600",
                "dst_offset" => "21600",
            ],
            "Arctic/Longyearbyen" => [
                "timezone" => "Arctic/Longyearbyen",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Asia/Aden" => [
                "timezone" => "Asia/Aden",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "Asia/Almaty" => [
                "timezone" => "Asia/Almaty",
                "gmt_offset" => "21600",
                "dst_offset" => "21600",
            ],
            "Asia/Amman" => [
                "timezone" => "Asia/Amman",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "Asia/Anadyr" => [
                "timezone" => "Asia/Anadyr",
                "gmt_offset" => "43200",
                "dst_offset" => "43200",
            ],
            "Asia/Aqtau" => [
                "timezone" => "Asia/Aqtau",
                "gmt_offset" => "18000",
                "dst_offset" => "18000",
            ],
            "Asia/Aqtobe" => [
                "timezone" => "Asia/Aqtobe",
                "gmt_offset" => "18000",
                "dst_offset" => "18000",
            ],
            "Asia/Ashgabat" => [
                "timezone" => "Asia/Ashgabat",
                "gmt_offset" => "18000",
                "dst_offset" => "18000",
            ],
            "Asia/Ashkhabad" => [
                "timezone" => "Asia/Ashkhabad",
                "gmt_offset" => "18000",
                "dst_offset" => "18000",
            ],
            "Asia/Baghdad" => [
                "timezone" => "Asia/Baghdad",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "Asia/Bahrain" => [
                "timezone" => "Asia/Bahrain",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "Asia/Baku" => [
                "timezone" => "Asia/Baku",
                "gmt_offset" => "14400",
                "dst_offset" => "18000",
            ],
            "Asia/Bangkok" => [
                "timezone" => "Asia/Bangkok",
                "gmt_offset" => "25200",
                "dst_offset" => "25200",
            ],
            "Asia/Beirut" => [
                "timezone" => "Asia/Beirut",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "Asia/Bishkek" => [
                "timezone" => "Asia/Bishkek",
                "gmt_offset" => "21600",
                "dst_offset" => "21600",
            ],
            "Asia/Brunei" => [
                "timezone" => "Asia/Brunei",
                "gmt_offset" => "28800",
                "dst_offset" => "28800",
            ],
            "Asia/Calcutta" => [
                "timezone" => "Asia/Calcutta",
                "gmt_offset" => "19800",
                "dst_offset" => "19800",
            ],
            "Asia/Chita" => [
                "timezone" => "Asia/Chita",
                "gmt_offset" => "28800",
                "dst_offset" => "28800",
            ],
            "Asia/Choibalsan" => [
                "timezone" => "Asia/Choibalsan",
                "gmt_offset" => "28800",
                "dst_offset" => "32400",
            ],
            "Asia/Chongqing" => [
                "timezone" => "Asia/Chongqing",
                "gmt_offset" => "28800",
                "dst_offset" => "28800",
            ],
            "Asia/Chungking" => [
                "timezone" => "Asia/Chungking",
                "gmt_offset" => "28800",
                "dst_offset" => "28800",
            ],
            "Asia/Colombo" => [
                "timezone" => "Asia/Colombo",
                "gmt_offset" => "19800",
                "dst_offset" => "19800",
            ],
            "Asia/Dacca" => [
                "timezone" => "Asia/Dacca",
                "gmt_offset" => "21600",
                "dst_offset" => "21600",
            ],
            "Asia/Damascus" => [
                "timezone" => "Asia/Damascus",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "Asia/Dhaka" => [
                "timezone" => "Asia/Dhaka",
                "gmt_offset" => "21600",
                "dst_offset" => "21600",
            ],
            "Asia/Dili" => [
                "timezone" => "Asia/Dili",
                "gmt_offset" => "32400",
                "dst_offset" => "32400",
            ],
            "Asia/Dubai" => [
                "timezone" => "Asia/Dubai",
                "gmt_offset" => "14400",
                "dst_offset" => "14400",
            ],
            "Asia/Dushanbe" => [
                "timezone" => "Asia/Dushanbe",
                "gmt_offset" => "18000",
                "dst_offset" => "18000",
            ],
            "Asia/Gaza" => [
                "timezone" => "Asia/Gaza",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "Asia/Harbin" => [
                "timezone" => "Asia/Harbin",
                "gmt_offset" => "28800",
                "dst_offset" => "28800",
            ],
            "Asia/Hebron" => [
                "timezone" => "Asia/Hebron",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "Asia/Ho_Chi_Minh" => [
                "timezone" => "Asia/Ho_Chi_Minh",
                "gmt_offset" => "25200",
                "dst_offset" => "25200",
            ],
            "Asia/Hong_Kong" => [
                "timezone" => "Asia/Hong_Kong",
                "gmt_offset" => "28800",
                "dst_offset" => "28800",
            ],
            "Asia/Hovd" => [
                "timezone" => "Asia/Hovd",
                "gmt_offset" => "25200",
                "dst_offset" => "28800",
            ],
            "Asia/Irkutsk" => [
                "timezone" => "Asia/Irkutsk",
                "gmt_offset" => "28800",
                "dst_offset" => "28800",
            ],
            "Asia/Istanbul" => [
                "timezone" => "Asia/Istanbul",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "Asia/Jakarta" => [
                "timezone" => "Asia/Jakarta",
                "gmt_offset" => "25200",
                "dst_offset" => "25200",
            ],
            "Asia/Jayapura" => [
                "timezone" => "Asia/Jayapura",
                "gmt_offset" => "32400",
                "dst_offset" => "32400",
            ],
            "Asia/Jerusalem" => [
                "timezone" => "Asia/Jerusalem",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "Asia/Kabul" => [
                "timezone" => "Asia/Kabul",
                "gmt_offset" => "16200",
                "dst_offset" => "16200",
            ],
            "Asia/Kamchatka" => [
                "timezone" => "Asia/Kamchatka",
                "gmt_offset" => "43200",
                "dst_offset" => "43200",
            ],
            "Asia/Karachi" => [
                "timezone" => "Asia/Karachi",
                "gmt_offset" => "18000",
                "dst_offset" => "18000",
            ],
            "Asia/Kashgar" => [
                "timezone" => "Asia/Kashgar",
                "gmt_offset" => "21600",
                "dst_offset" => "21600",
            ],
            "Asia/Kathmandu" => [
                "timezone" => "Asia/Kathmandu",
                "gmt_offset" => "20700",
                "dst_offset" => "20700",
            ],
            "Asia/Katmandu" => [
                "timezone" => "Asia/Katmandu",
                "gmt_offset" => "20700",
                "dst_offset" => "20700",
            ],
            "Asia/Khandyga" => [
                "timezone" => "Asia/Khandyga",
                "gmt_offset" => "32400",
                "dst_offset" => "32400",
            ],
            "Asia/Kolkata" => [
                "timezone" => "Asia/Kolkata",
                "gmt_offset" => "19800",
                "dst_offset" => "19800",
            ],
            "Asia/Krasnoyarsk" => [
                "timezone" => "Asia/Krasnoyarsk",
                "gmt_offset" => "25200",
                "dst_offset" => "25200",
            ],
            "Asia/Kuala_Lumpur" => [
                "timezone" => "Asia/Kuala_Lumpur",
                "gmt_offset" => "28800",
                "dst_offset" => "28800",
            ],
            "Asia/Kuching" => [
                "timezone" => "Asia/Kuching",
                "gmt_offset" => "28800",
                "dst_offset" => "28800",
            ],
            "Asia/Kuwait" => [
                "timezone" => "Asia/Kuwait",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "Asia/Macao" => [
                "timezone" => "Asia/Macao",
                "gmt_offset" => "28800",
                "dst_offset" => "28800",
            ],
            "Asia/Macau" => [
                "timezone" => "Asia/Macau",
                "gmt_offset" => "28800",
                "dst_offset" => "28800",
            ],
            "Asia/Magadan" => [
                "timezone" => "Asia/Magadan",
                "gmt_offset" => "36000",
                "dst_offset" => "36000",
            ],
            "Asia/Makassar" => [
                "timezone" => "Asia/Makassar",
                "gmt_offset" => "28800",
                "dst_offset" => "28800",
            ],
            "Asia/Manila" => [
                "timezone" => "Asia/Manila",
                "gmt_offset" => "28800",
                "dst_offset" => "28800",
            ],
            "Asia/Muscat" => [
                "timezone" => "Asia/Muscat",
                "gmt_offset" => "14400",
                "dst_offset" => "14400",
            ],
            "Asia/Nicosia" => [
                "timezone" => "Asia/Nicosia",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "Asia/Novokuznetsk" => [
                "timezone" => "Asia/Novokuznetsk",
                "gmt_offset" => "25200",
                "dst_offset" => "25200",
            ],
            "Asia/Novosibirsk" => [
                "timezone" => "Asia/Novosibirsk",
                "gmt_offset" => "21600",
                "dst_offset" => "21600",
            ],
            "Asia/Omsk" => [
                "timezone" => "Asia/Omsk",
                "gmt_offset" => "21600",
                "dst_offset" => "21600",
            ],
            "Asia/Oral" => [
                "timezone" => "Asia/Oral",
                "gmt_offset" => "18000",
                "dst_offset" => "18000",
            ],
            "Asia/Phnom_Penh" => [
                "timezone" => "Asia/Phnom_Penh",
                "gmt_offset" => "25200",
                "dst_offset" => "25200",
            ],
            "Asia/Pontianak" => [
                "timezone" => "Asia/Pontianak",
                "gmt_offset" => "25200",
                "dst_offset" => "25200",
            ],
            "Asia/Pyongyang" => [
                "timezone" => "Asia/Pyongyang",
                "gmt_offset" => "30600",
                "dst_offset" => "30600",
            ],
            "Asia/Qatar" => [
                "timezone" => "Asia/Qatar",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "Asia/Qyzylorda" => [
                "timezone" => "Asia/Qyzylorda",
                "gmt_offset" => "21600",
                "dst_offset" => "21600",
            ],
            "Asia/Rangoon" => [
                "timezone" => "Asia/Rangoon",
                "gmt_offset" => "23400",
                "dst_offset" => "23400",
            ],
            "Asia/Riyadh" => [
                "timezone" => "Asia/Riyadh",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "Asia/Riyadh87" => [
                "timezone" => "Asia/Riyadh87",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "Asia/Riyadh88" => [
                "timezone" => "Asia/Riyadh88",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "Asia/Riyadh89" => [
                "timezone" => "Asia/Riyadh89",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "Asia/Saigon" => [
                "timezone" => "Asia/Saigon",
                "gmt_offset" => "25200",
                "dst_offset" => "25200",
            ],
            "Asia/Sakhalin" => [
                "timezone" => "Asia/Sakhalin",
                "gmt_offset" => "36000",
                "dst_offset" => "36000",
            ],
            "Asia/Samarkand" => [
                "timezone" => "Asia/Samarkand",
                "gmt_offset" => "18000",
                "dst_offset" => "18000",
            ],
            "Asia/Seoul" => [
                "timezone" => "Asia/Seoul",
                "gmt_offset" => "32400",
                "dst_offset" => "32400",
            ],
            "Asia/Shanghai" => [
                "timezone" => "Asia/Shanghai",
                "gmt_offset" => "28800",
                "dst_offset" => "28800",
            ],
            "Asia/Singapore" => [
                "timezone" => "Asia/Singapore",
                "gmt_offset" => "28800",
                "dst_offset" => "28800",
            ],
            "Asia/Srednekolymsk" => [
                "timezone" => "Asia/Srednekolymsk",
                "gmt_offset" => "39600",
                "dst_offset" => "39600",
            ],
            "Asia/Taipei" => [
                "timezone" => "Asia/Taipei",
                "gmt_offset" => "28800",
                "dst_offset" => "28800",
            ],
            "Asia/Tashkent" => [
                "timezone" => "Asia/Tashkent",
                "gmt_offset" => "18000",
                "dst_offset" => "18000",
            ],
            "Asia/Tbilisi" => [
                "timezone" => "Asia/Tbilisi",
                "gmt_offset" => "14400",
                "dst_offset" => "14400",
            ],
            "Asia/Tehran" => [
                "timezone" => "Asia/Tehran",
                "gmt_offset" => "12600",
                "dst_offset" => "16200",
            ],
            "Asia/Tel_Aviv" => [
                "timezone" => "Asia/Tel_Aviv",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "Asia/Thimbu" => [
                "timezone" => "Asia/Thimbu",
                "gmt_offset" => "21600",
                "dst_offset" => "21600",
            ],
            "Asia/Thimphu" => [
                "timezone" => "Asia/Thimphu",
                "gmt_offset" => "21600",
                "dst_offset" => "21600",
            ],
            "Asia/Tokyo" => [
                "timezone" => "Asia/Tokyo",
                "gmt_offset" => "32400",
                "dst_offset" => "32400",
            ],
            "Asia/Ujung_Pandang" => [
                "timezone" => "Asia/Ujung_Pandang",
                "gmt_offset" => "28800",
                "dst_offset" => "28800",
            ],
            "Asia/Ulaanbaatar" => [
                "timezone" => "Asia/Ulaanbaatar",
                "gmt_offset" => "28800",
                "dst_offset" => "32400",
            ],
            "Asia/Ulan_Bator" => [
                "timezone" => "Asia/Ulan_Bator",
                "gmt_offset" => "28800",
                "dst_offset" => "32400",
            ],
            "Asia/Urumqi" => [
                "timezone" => "Asia/Urumqi",
                "gmt_offset" => "21600",
                "dst_offset" => "21600",
            ],
            "Asia/Vientiane" => [
                "timezone" => "Asia/Vientiane",
                "gmt_offset" => "25200",
                "dst_offset" => "25200",
            ],
            "Asia/Vladivostok" => [
                "timezone" => "Asia/Vladivostok",
                "gmt_offset" => "36000",
                "dst_offset" => "36000",
            ],
            "Asia/Yakutsk" => [
                "timezone" => "Asia/Yakutsk",
                "gmt_offset" => "32400",
                "dst_offset" => "32400",
            ],
            "Asia/Yekaterinburg" => [
                "timezone" => "Asia/Yekaterinburg",
                "gmt_offset" => "18000",
                "dst_offset" => "18000",
            ],
            "Asia/Yerevan" => [
                "timezone" => "Asia/Yerevan",
                "gmt_offset" => "14400",
                "dst_offset" => "14400",
            ],
            "Atlantic/Azores" => [
                "timezone" => "Atlantic/Azores",
                "gmt_offset" => "-3600",
                "dst_offset" => "0",
            ],
            "Atlantic/Bermuda" => [
                "timezone" => "Atlantic/Bermuda",
                "gmt_offset" => "-14400",
                "dst_offset" => "-10800",
            ],
            "Atlantic/Canary" => [
                "timezone" => "Atlantic/Canary",
                "gmt_offset" => "0",
                "dst_offset" => "3600",
            ],
            "Atlantic/Cape_Verde" => [
                "timezone" => "Atlantic/Cape_Verde",
                "gmt_offset" => "-3600",
                "dst_offset" => "-3600",
            ],
            "Atlantic/Faeroe" => [
                "timezone" => "Atlantic/Faeroe",
                "gmt_offset" => "0",
                "dst_offset" => "3600",
            ],
            "Atlantic/Faroe" => [
                "timezone" => "Atlantic/Faroe",
                "gmt_offset" => "0",
                "dst_offset" => "3600",
            ],
            "Atlantic/Jan_Mayen" => [
                "timezone" => "Atlantic/Jan_Mayen",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Atlantic/Madeira" => [
                "timezone" => "Atlantic/Madeira",
                "gmt_offset" => "0",
                "dst_offset" => "3600",
            ],
            "Atlantic/Reykjavik" => [
                "timezone" => "Atlantic/Reykjavik",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Atlantic/South_Georgia" => [
                "timezone" => "Atlantic/South_Georgia",
                "gmt_offset" => "-7200",
                "dst_offset" => "-7200",
            ],
            "Atlantic/St_Helena" => [
                "timezone" => "Atlantic/St_Helena",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Atlantic/Stanley" => [
                "timezone" => "Atlantic/Stanley",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "Australia/ACT" => [
                "timezone" => "Australia/ACT",
                "gmt_offset" => "36000",
                "dst_offset" => "39600",
            ],
            "Australia/Adelaide" => [
                "timezone" => "Australia/Adelaide",
                "gmt_offset" => "34200",
                "dst_offset" => "37800",
            ],
            "Australia/Brisbane" => [
                "timezone" => "Australia/Brisbane",
                "gmt_offset" => "36000",
                "dst_offset" => "36000",
            ],
            "Australia/Broken_Hill" => [
                "timezone" => "Australia/Broken_Hill",
                "gmt_offset" => "34200",
                "dst_offset" => "37800",
            ],
            "Australia/Canberra" => [
                "timezone" => "Australia/Canberra",
                "gmt_offset" => "36000",
                "dst_offset" => "39600",
            ],
            "Australia/Currie" => [
                "timezone" => "Australia/Currie",
                "gmt_offset" => "36000",
                "dst_offset" => "39600",
            ],
            "Australia/Darwin" => [
                "timezone" => "Australia/Darwin",
                "gmt_offset" => "34200",
                "dst_offset" => "34200",
            ],
            "Australia/Eucla" => [
                "timezone" => "Australia/Eucla",
                "gmt_offset" => "31500",
                "dst_offset" => "31500",
            ],
            "Australia/Hobart" => [
                "timezone" => "Australia/Hobart",
                "gmt_offset" => "36000",
                "dst_offset" => "39600",
            ],
            "Australia/LHI" => [
                "timezone" => "Australia/LHI",
                "gmt_offset" => "37800",
                "dst_offset" => "39600",
            ],
            "Australia/Lindeman" => [
                "timezone" => "Australia/Lindeman",
                "gmt_offset" => "36000",
                "dst_offset" => "36000",
            ],
            "Australia/Lord_Howe" => [
                "timezone" => "Australia/Lord_Howe",
                "gmt_offset" => "37800",
                "dst_offset" => "39600",
            ],
            "Australia/Melbourne" => [
                "timezone" => "Australia/Melbourne",
                "gmt_offset" => "36000",
                "dst_offset" => "39600",
            ],
            "Australia/NSW" => [
                "timezone" => "Australia/NSW",
                "gmt_offset" => "36000",
                "dst_offset" => "39600",
            ],
            "Australia/North" => [
                "timezone" => "Australia/North",
                "gmt_offset" => "34200",
                "dst_offset" => "34200",
            ],
            "Australia/Perth" => [
                "timezone" => "Australia/Perth",
                "gmt_offset" => "28800",
                "dst_offset" => "28800",
            ],
            "Australia/Queensland" => [
                "timezone" => "Australia/Queensland",
                "gmt_offset" => "36000",
                "dst_offset" => "36000",
            ],
            "Australia/South" => [
                "timezone" => "Australia/South",
                "gmt_offset" => "34200",
                "dst_offset" => "37800",
            ],
            "Australia/Sydney" => [
                "timezone" => "Australia/Sydney",
                "gmt_offset" => "36000",
                "dst_offset" => "39600",
            ],
            "Australia/Tasmania" => [
                "timezone" => "Australia/Tasmania",
                "gmt_offset" => "36000",
                "dst_offset" => "39600",
            ],
            "Australia/Victoria" => [
                "timezone" => "Australia/Victoria",
                "gmt_offset" => "36000",
                "dst_offset" => "39600",
            ],
            "Australia/West" => [
                "timezone" => "Australia/West",
                "gmt_offset" => "28800",
                "dst_offset" => "28800",
            ],
            "Australia/Yancowinna" => [
                "timezone" => "Australia/Yancowinna",
                "gmt_offset" => "34200",
                "dst_offset" => "37800",
            ],
            "Brazil/Acre" => [
                "timezone" => "Brazil/Acre",
                "gmt_offset" => "-18000",
                "dst_offset" => "-18000",
            ],
            "Brazil/DeNoronha" => [
                "timezone" => "Brazil/DeNoronha",
                "gmt_offset" => "-7200",
                "dst_offset" => "-7200",
            ],
            "Brazil/East" => [
                "timezone" => "Brazil/East",
                "gmt_offset" => "-10800",
                "dst_offset" => "-7200",
            ],
            "Brazil/West" => [
                "timezone" => "Brazil/West",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "CET" => [
                "timezone" => "CET",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "CST6CDT" => [
                "timezone" => "CST6CDT",
                "gmt_offset" => "-21600",
                "dst_offset" => "-18000",
            ],
            "Canada/Atlantic" => [
                "timezone" => "Canada/Atlantic",
                "gmt_offset" => "-14400",
                "dst_offset" => "-10800",
            ],
            "Canada/Central" => [
                "timezone" => "Canada/Central",
                "gmt_offset" => "-21600",
                "dst_offset" => "-18000",
            ],
            "Canada/East-Saskatchewan" => [
                "timezone" => "Canada/East-Saskatchewan",
                "gmt_offset" => "-21600",
                "dst_offset" => "-21600",
            ],
            "Canada/Eastern" => [
                "timezone" => "Canada/Eastern",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "Canada/Mountain" => [
                "timezone" => "Canada/Mountain",
                "gmt_offset" => "-25200",
                "dst_offset" => "-21600",
            ],
            "Canada/Newfoundland" => [
                "timezone" => "Canada/Newfoundland",
                "gmt_offset" => "-12600",
                "dst_offset" => "-9000",
            ],
            "Canada/Pacific" => [
                "timezone" => "Canada/Pacific",
                "gmt_offset" => "-28800",
                "dst_offset" => "-25200",
            ],
            "Canada/Saskatchewan" => [
                "timezone" => "Canada/Saskatchewan",
                "gmt_offset" => "-21600",
                "dst_offset" => "-21600",
            ],
            "Canada/Yukon" => [
                "timezone" => "Canada/Yukon",
                "gmt_offset" => "-28800",
                "dst_offset" => "-25200",
            ],
            "Chile/Continental" => [
                "timezone" => "Chile/Continental",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "Chile/EasterIsland" => [
                "timezone" => "Chile/EasterIsland",
                "gmt_offset" => "-18000",
                "dst_offset" => "-18000",
            ],
            "Cuba" => [
                "timezone" => "Cuba",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "EET" => [
                "timezone" => "EET",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "EST" => [
                "timezone" => "EST",
                "gmt_offset" => "-18000",
                "dst_offset" => "-18000",
            ],
            "EST5EDT" => [
                "timezone" => "EST5EDT",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "Egypt" => [
                "timezone" => "Egypt",
                "gmt_offset" => "7200",
                "dst_offset" => "7200",
            ],
            "Eire" => [
                "timezone" => "Eire",
                "gmt_offset" => "0",
                "dst_offset" => "3600",
            ],
            "Etc/GMT" => [
                "timezone" => "Etc/GMT",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Etc/GMT+0" => [
                "timezone" => "Etc/GMT+0",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Etc/GMT+1" => [
                "timezone" => "Etc/GMT+1",
                "gmt_offset" => "-3600",
                "dst_offset" => "-3600",
            ],
            "Etc/GMT+10" => [
                "timezone" => "Etc/GMT+10",
                "gmt_offset" => "-36000",
                "dst_offset" => "-36000",
            ],
            "Etc/GMT+11" => [
                "timezone" => "Etc/GMT+11",
                "gmt_offset" => "-39600",
                "dst_offset" => "-39600",
            ],
            "Etc/GMT+12" => [
                "timezone" => "Etc/GMT+12",
                "gmt_offset" => "-43200",
                "dst_offset" => "-43200",
            ],
            "Etc/GMT+2" => [
                "timezone" => "Etc/GMT+2",
                "gmt_offset" => "-7200",
                "dst_offset" => "-7200",
            ],
            "Etc/GMT+3" => [
                "timezone" => "Etc/GMT+3",
                "gmt_offset" => "-10800",
                "dst_offset" => "-10800",
            ],
            "Etc/GMT+4" => [
                "timezone" => "Etc/GMT+4",
                "gmt_offset" => "-14400",
                "dst_offset" => "-14400",
            ],
            "Etc/GMT+5" => [
                "timezone" => "Etc/GMT+5",
                "gmt_offset" => "-18000",
                "dst_offset" => "-18000",
            ],
            "Etc/GMT+6" => [
                "timezone" => "Etc/GMT+6",
                "gmt_offset" => "-21600",
                "dst_offset" => "-21600",
            ],
            "Etc/GMT+7" => [
                "timezone" => "Etc/GMT+7",
                "gmt_offset" => "-25200",
                "dst_offset" => "-25200",
            ],
            "Etc/GMT+8" => [
                "timezone" => "Etc/GMT+8",
                "gmt_offset" => "-28800",
                "dst_offset" => "-28800",
            ],
            "Etc/GMT+9" => [
                "timezone" => "Etc/GMT+9",
                "gmt_offset" => "-32400",
                "dst_offset" => "-32400",
            ],
            "Etc/GMT-0" => [
                "timezone" => "Etc/GMT-0",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Etc/GMT-1" => [
                "timezone" => "Etc/GMT-1",
                "gmt_offset" => "3600",
                "dst_offset" => "3600",
            ],
            "Etc/GMT-10" => [
                "timezone" => "Etc/GMT-10",
                "gmt_offset" => "36000",
                "dst_offset" => "36000",
            ],
            "Etc/GMT-11" => [
                "timezone" => "Etc/GMT-11",
                "gmt_offset" => "39600",
                "dst_offset" => "39600",
            ],
            "Etc/GMT-12" => [
                "timezone" => "Etc/GMT-12",
                "gmt_offset" => "43200",
                "dst_offset" => "43200",
            ],
            "Etc/GMT-13" => [
                "timezone" => "Etc/GMT-13",
                "gmt_offset" => "46800",
                "dst_offset" => "46800",
            ],
            "Etc/GMT-14" => [
                "timezone" => "Etc/GMT-14",
                "gmt_offset" => "50400",
                "dst_offset" => "50400",
            ],
            "Etc/GMT-2" => [
                "timezone" => "Etc/GMT-2",
                "gmt_offset" => "7200",
                "dst_offset" => "7200",
            ],
            "Etc/GMT-3" => [
                "timezone" => "Etc/GMT-3",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "Etc/GMT-4" => [
                "timezone" => "Etc/GMT-4",
                "gmt_offset" => "14400",
                "dst_offset" => "14400",
            ],
            "Etc/GMT-5" => [
                "timezone" => "Etc/GMT-5",
                "gmt_offset" => "18000",
                "dst_offset" => "18000",
            ],
            "Etc/GMT-6" => [
                "timezone" => "Etc/GMT-6",
                "gmt_offset" => "21600",
                "dst_offset" => "21600",
            ],
            "Etc/GMT-7" => [
                "timezone" => "Etc/GMT-7",
                "gmt_offset" => "25200",
                "dst_offset" => "25200",
            ],
            "Etc/GMT-8" => [
                "timezone" => "Etc/GMT-8",
                "gmt_offset" => "28800",
                "dst_offset" => "28800",
            ],
            "Etc/GMT-9" => [
                "timezone" => "Etc/GMT-9",
                "gmt_offset" => "32400",
                "dst_offset" => "32400",
            ],
            "Etc/GMT0" => [
                "timezone" => "Etc/GMT0",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Etc/Greenwich" => [
                "timezone" => "Etc/Greenwich",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Etc/UCT" => [
                "timezone" => "Etc/UCT",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Etc/UTC" => [
                "timezone" => "Etc/UTC",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Etc/Universal" => [
                "timezone" => "Etc/Universal",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Etc/Zulu" => [
                "timezone" => "Etc/Zulu",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Europe/Amsterdam" => [
                "timezone" => "Europe/Amsterdam",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Andorra" => [
                "timezone" => "Europe/Andorra",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Athens" => [
                "timezone" => "Europe/Athens",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "Europe/Belfast" => [
                "timezone" => "Europe/Belfast",
                "gmt_offset" => "0",
                "dst_offset" => "3600",
            ],
            "Europe/Belgrade" => [
                "timezone" => "Europe/Belgrade",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Berlin" => [
                "timezone" => "Europe/Berlin",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Bratislava" => [
                "timezone" => "Europe/Bratislava",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Brussels" => [
                "timezone" => "Europe/Brussels",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Bucharest" => [
                "timezone" => "Europe/Bucharest",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "Europe/Budapest" => [
                "timezone" => "Europe/Budapest",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Busingen" => [
                "timezone" => "Europe/Busingen",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Chisinau" => [
                "timezone" => "Europe/Chisinau",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "Europe/Copenhagen" => [
                "timezone" => "Europe/Copenhagen",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Dublin" => [
                "timezone" => "Europe/Dublin",
                "gmt_offset" => "0",
                "dst_offset" => "3600",
            ],
            "Europe/Gibraltar" => [
                "timezone" => "Europe/Gibraltar",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Guernsey" => [
                "timezone" => "Europe/Guernsey",
                "gmt_offset" => "0",
                "dst_offset" => "3600",
            ],
            "Europe/Helsinki" => [
                "timezone" => "Europe/Helsinki",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "Europe/Isle_of_Man" => [
                "timezone" => "Europe/Isle_of_Man",
                "gmt_offset" => "0",
                "dst_offset" => "3600",
            ],
            "Europe/Istanbul" => [
                "timezone" => "Europe/Istanbul",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "Europe/Jersey" => [
                "timezone" => "Europe/Jersey",
                "gmt_offset" => "0",
                "dst_offset" => "3600",
            ],
            "Europe/Kaliningrad" => [
                "timezone" => "Europe/Kaliningrad",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "Europe/Kiev" => [
                "timezone" => "Europe/Kiev",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "Europe/Lisbon" => [
                "timezone" => "Europe/Lisbon",
                "gmt_offset" => "0",
                "dst_offset" => "3600",
            ],
            "Europe/Ljubljana" => [
                "timezone" => "Europe/Ljubljana",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/London" => [
                "timezone" => "Europe/London",
                "gmt_offset" => "0",
                "dst_offset" => "3600",
            ],
            "Europe/Luxembourg" => [
                "timezone" => "Europe/Luxembourg",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Madrid" => [
                "timezone" => "Europe/Madrid",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Malta" => [
                "timezone" => "Europe/Malta",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Mariehamn" => [
                "timezone" => "Europe/Mariehamn",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "Europe/Minsk" => [
                "timezone" => "Europe/Minsk",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "Europe/Monaco" => [
                "timezone" => "Europe/Monaco",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Moscow" => [
                "timezone" => "Europe/Moscow",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "Europe/Nicosia" => [
                "timezone" => "Europe/Nicosia",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "Europe/Oslo" => [
                "timezone" => "Europe/Oslo",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Paris" => [
                "timezone" => "Europe/Paris",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Podgorica" => [
                "timezone" => "Europe/Podgorica",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Prague" => [
                "timezone" => "Europe/Prague",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Riga" => [
                "timezone" => "Europe/Riga",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "Europe/Rome" => [
                "timezone" => "Europe/Rome",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Samara" => [
                "timezone" => "Europe/Samara",
                "gmt_offset" => "14400",
                "dst_offset" => "14400",
            ],
            "Europe/San_Marino" => [
                "timezone" => "Europe/San_Marino",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Sarajevo" => [
                "timezone" => "Europe/Sarajevo",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Simferopol" => [
                "timezone" => "Europe/Simferopol",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "Europe/Skopje" => [
                "timezone" => "Europe/Skopje",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Sofia" => [
                "timezone" => "Europe/Sofia",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "Europe/Stockholm" => [
                "timezone" => "Europe/Stockholm",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Tallinn" => [
                "timezone" => "Europe/Tallinn",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "Europe/Tirane" => [
                "timezone" => "Europe/Tirane",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Tiraspol" => [
                "timezone" => "Europe/Tiraspol",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "Europe/Uzhgorod" => [
                "timezone" => "Europe/Uzhgorod",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "Europe/Vaduz" => [
                "timezone" => "Europe/Vaduz",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Vatican" => [
                "timezone" => "Europe/Vatican",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Vienna" => [
                "timezone" => "Europe/Vienna",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Vilnius" => [
                "timezone" => "Europe/Vilnius",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "Europe/Volgograd" => [
                "timezone" => "Europe/Volgograd",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "Europe/Warsaw" => [
                "timezone" => "Europe/Warsaw",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Zagreb" => [
                "timezone" => "Europe/Zagreb",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Europe/Zaporozhye" => [
                "timezone" => "Europe/Zaporozhye",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "Europe/Zurich" => [
                "timezone" => "Europe/Zurich",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Factory" => [
                "timezone" => "Factory",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "GB" => [
                "timezone" => "GB",
                "gmt_offset" => "0",
                "dst_offset" => "3600",
            ],
            "GB-Eire" => [
                "timezone" => "GB-Eire",
                "gmt_offset" => "0",
                "dst_offset" => "3600",
            ],
            "GMT" => [
                "timezone" => "GMT",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "GMT+0" => [
                "timezone" => "GMT+0",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "GMT-0" => [
                "timezone" => "GMT-0",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "GMT0" => [
                "timezone" => "GMT0",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Greenwich" => [
                "timezone" => "Greenwich",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "HST" => [
                "timezone" => "HST",
                "gmt_offset" => "-36000",
                "dst_offset" => "-36000",
            ],
            "Hongkong" => [
                "timezone" => "Hongkong",
                "gmt_offset" => "28800",
                "dst_offset" => "28800",
            ],
            "Iceland" => [
                "timezone" => "Iceland",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Indian/Antananarivo" => [
                "timezone" => "Indian/Antananarivo",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "Indian/Chagos" => [
                "timezone" => "Indian/Chagos",
                "gmt_offset" => "21600",
                "dst_offset" => "21600",
            ],
            "Indian/Christmas" => [
                "timezone" => "Indian/Christmas",
                "gmt_offset" => "25200",
                "dst_offset" => "25200",
            ],
            "Indian/Cocos" => [
                "timezone" => "Indian/Cocos",
                "gmt_offset" => "23400",
                "dst_offset" => "23400",
            ],
            "Indian/Comoro" => [
                "timezone" => "Indian/Comoro",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "Indian/Kerguelen" => [
                "timezone" => "Indian/Kerguelen",
                "gmt_offset" => "18000",
                "dst_offset" => "18000",
            ],
            "Indian/Mahe" => [
                "timezone" => "Indian/Mahe",
                "gmt_offset" => "14400",
                "dst_offset" => "14400",
            ],
            "Indian/Maldives" => [
                "timezone" => "Indian/Maldives",
                "gmt_offset" => "18000",
                "dst_offset" => "18000",
            ],
            "Indian/Mauritius" => [
                "timezone" => "Indian/Mauritius",
                "gmt_offset" => "14400",
                "dst_offset" => "14400",
            ],
            "Indian/Mayotte" => [
                "timezone" => "Indian/Mayotte",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "Indian/Reunion" => [
                "timezone" => "Indian/Reunion",
                "gmt_offset" => "14400",
                "dst_offset" => "14400",
            ],
            "Iran" => [
                "timezone" => "Iran",
                "gmt_offset" => "12600",
                "dst_offset" => "16200",
            ],
            "Israel" => [
                "timezone" => "Israel",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "Jamaica" => [
                "timezone" => "Jamaica",
                "gmt_offset" => "-18000",
                "dst_offset" => "-18000",
            ],
            "Japan" => [
                "timezone" => "Japan",
                "gmt_offset" => "32400",
                "dst_offset" => "32400",
            ],
            "Kwajalein" => [
                "timezone" => "Kwajalein",
                "gmt_offset" => "43200",
                "dst_offset" => "43200",
            ],
            "Libya" => [
                "timezone" => "Libya",
                "gmt_offset" => "7200",
                "dst_offset" => "7200",
            ],
            "MET" => [
                "timezone" => "MET",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "MST" => [
                "timezone" => "MST",
                "gmt_offset" => "-25200",
                "dst_offset" => "-25200",
            ],
            "MST7MDT" => [
                "timezone" => "MST7MDT",
                "gmt_offset" => "-25200",
                "dst_offset" => "-21600",
            ],
            "Mexico/BajaNorte" => [
                "timezone" => "Mexico/BajaNorte",
                "gmt_offset" => "-28800",
                "dst_offset" => "-25200",
            ],
            "Mexico/BajaSur" => [
                "timezone" => "Mexico/BajaSur",
                "gmt_offset" => "-25200",
                "dst_offset" => "-21600",
            ],
            "Mexico/General" => [
                "timezone" => "Mexico/General",
                "gmt_offset" => "-21600",
                "dst_offset" => "-18000",
            ],
            "Mideast/Riyadh87" => [
                "timezone" => "Mideast/Riyadh87",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Mideast/Riyadh88" => [
                "timezone" => "Mideast/Riyadh88",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Mideast/Riyadh89" => [
                "timezone" => "Mideast/Riyadh89",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "NZ" => [
                "timezone" => "NZ",
                "gmt_offset" => "43200",
                "dst_offset" => "46800",
            ],
            "NZ-CHAT" => [
                "timezone" => "NZ-CHAT",
                "gmt_offset" => "45900",
                "dst_offset" => "49500",
            ],
            "Navajo" => [
                "timezone" => "Navajo",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "PRC" => [
                "timezone" => "PRC",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "PST8PDT" => [
                "timezone" => "PST8PDT",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Pacific/Apia" => [
                "timezone" => "Pacific/Apia",
                "gmt_offset" => "46800",
                "dst_offset" => "50400",
            ],
            "Pacific/Auckland" => [
                "timezone" => "Pacific/Auckland",
                "gmt_offset" => "43200",
                "dst_offset" => "46800",
            ],
            "Pacific/Bougainville" => [
                "timezone" => "Pacific/Bougainville",
                "gmt_offset" => "39600",
                "dst_offset" => "39600",
            ],
            "Pacific/Chatham" => [
                "timezone" => "Pacific/Chatham",
                "gmt_offset" => "45900",
                "dst_offset" => "49500",
            ],
            "Pacific/Chuuk" => [
                "timezone" => "Pacific/Chuuk",
                "gmt_offset" => "36000",
                "dst_offset" => "36000",
            ],
            "Pacific/Easter" => [
                "timezone" => "Pacific/Easter",
                "gmt_offset" => "-18000",
                "dst_offset" => "-18000",
            ],
            "Pacific/Efate" => [
                "timezone" => "Pacific/Efate",
                "gmt_offset" => "39600",
                "dst_offset" => "39600",
            ],
            "Pacific/Enderbury" => [
                "timezone" => "Pacific/Enderbury",
                "gmt_offset" => "46800",
                "dst_offset" => "46800",
            ],
            "Pacific/Fakaofo" => [
                "timezone" => "Pacific/Fakaofo",
                "gmt_offset" => "46800",
                "dst_offset" => "46800",
            ],
            "Pacific/Fiji" => [
                "timezone" => "Pacific/Fiji",
                "gmt_offset" => "43200",
                "dst_offset" => "46800",
            ],
            "Pacific/Funafuti" => [
                "timezone" => "Pacific/Funafuti",
                "gmt_offset" => "43200",
                "dst_offset" => "43200",
            ],
            "Pacific/Galapagos" => [
                "timezone" => "Pacific/Galapagos",
                "gmt_offset" => "-21600",
                "dst_offset" => "-21600",
            ],
            "Pacific/Gambier" => [
                "timezone" => "Pacific/Gambier",
                "gmt_offset" => "-32400",
                "dst_offset" => "-32400",
            ],
            "Pacific/Guadalcanal" => [
                "timezone" => "Pacific/Guadalcanal",
                "gmt_offset" => "39600",
                "dst_offset" => "39600",
            ],
            "Pacific/Guam" => [
                "timezone" => "Pacific/Guam",
                "gmt_offset" => "36000",
                "dst_offset" => "36000",
            ],
            "Pacific/Honolulu" => [
                "timezone" => "Pacific/Honolulu",
                "gmt_offset" => "-36000",
                "dst_offset" => "-36000",
            ],
            "Pacific/Johnston" => [
                "timezone" => "Pacific/Johnston",
                "gmt_offset" => "-36000",
                "dst_offset" => "-36000",
            ],
            "Pacific/Kiritimati" => [
                "timezone" => "Pacific/Kiritimati",
                "gmt_offset" => "50400",
                "dst_offset" => "50400",
            ],
            "Pacific/Kosrae" => [
                "timezone" => "Pacific/Kosrae",
                "gmt_offset" => "39600",
                "dst_offset" => "39600",
            ],
            "Pacific/Kwajalein" => [
                "timezone" => "Pacific/Kwajalein",
                "gmt_offset" => "43200",
                "dst_offset" => "43200",
            ],
            "Pacific/Majuro" => [
                "timezone" => "Pacific/Majuro",
                "gmt_offset" => "43200",
                "dst_offset" => "43200",
            ],
            "Pacific/Marquesas" => [
                "timezone" => "Pacific/Marquesas",
                "gmt_offset" => "-34200",
                "dst_offset" => "-34200",
            ],
            "Pacific/Midway" => [
                "timezone" => "Pacific/Midway",
                "gmt_offset" => "-39600",
                "dst_offset" => "-39600",
            ],
            "Pacific/Nauru" => [
                "timezone" => "Pacific/Nauru",
                "gmt_offset" => "43200",
                "dst_offset" => "43200",
            ],
            "Pacific/Niue" => [
                "timezone" => "Pacific/Niue",
                "gmt_offset" => "-39600",
                "dst_offset" => "-39600",
            ],
            "Pacific/Norfolk" => [
                "timezone" => "Pacific/Norfolk",
                "gmt_offset" => "39600",
                "dst_offset" => "39600",
            ],
            "Pacific/Noumea" => [
                "timezone" => "Pacific/Noumea",
                "gmt_offset" => "39600",
                "dst_offset" => "39600",
            ],
            "Pacific/Pago_Pago" => [
                "timezone" => "Pacific/Pago_Pago",
                "gmt_offset" => "-39600",
                "dst_offset" => "-39600",
            ],
            "Pacific/Palau" => [
                "timezone" => "Pacific/Palau",
                "gmt_offset" => "32400",
                "dst_offset" => "32400",
            ],
            "Pacific/Pitcairn" => [
                "timezone" => "Pacific/Pitcairn",
                "gmt_offset" => "-28800",
                "dst_offset" => "-28800",
            ],
            "Pacific/Ponape" => [
                "timezone" => "Pacific/Ponape",
                "gmt_offset" => "39600",
                "dst_offset" => "39600",
            ],
            "Pacific/Port_Moresby" => [
                "timezone" => "Pacific/Port_Moresby",
                "gmt_offset" => "36000",
                "dst_offset" => "36000",
            ],
            "Pacific/Rarotonga" => [
                "timezone" => "Pacific/Rarotonga",
                "gmt_offset" => "-36000",
                "dst_offset" => "-36000",
            ],
            "Pacific/Saipan" => [
                "timezone" => "Pacific/Saipan",
                "gmt_offset" => "36000",
                "dst_offset" => "36000",
            ],
            "Pacific/Samoa" => [
                "timezone" => "Pacific/Samoa",
                "gmt_offset" => "-39600",
                "dst_offset" => "-39600",
            ],
            "Pacific/Tahiti" => [
                "timezone" => "Pacific/Tahiti",
                "gmt_offset" => "-36000",
                "dst_offset" => "-36000",
            ],
            "Pacific/Tarawa" => [
                "timezone" => "Pacific/Tarawa",
                "gmt_offset" => "43200",
                "dst_offset" => "43200",
            ],
            "Pacific/Tongatapu" => [
                "timezone" => "Pacific/Tongatapu",
                "gmt_offset" => "46800",
                "dst_offset" => "46800",
            ],
            "Pacific/Truk" => [
                "timezone" => "Pacific/Truk",
                "gmt_offset" => "36000",
                "dst_offset" => "36000",
            ],
            "Pacific/Wake" => [
                "timezone" => "Pacific/Wake",
                "gmt_offset" => "43200",
                "dst_offset" => "43200",
            ],
            "Pacific/Wallis" => [
                "timezone" => "Pacific/Wallis",
                "gmt_offset" => "43200",
                "dst_offset" => "43200",
            ],
            "Pacific/Yap" => [
                "timezone" => "Pacific/Yap",
                "gmt_offset" => "36000",
                "dst_offset" => "36000",
            ],
            "Poland" => [
                "timezone" => "Poland",
                "gmt_offset" => "3600",
                "dst_offset" => "7200",
            ],
            "Portugal" => [
                "timezone" => "Portugal",
                "gmt_offset" => "0",
                "dst_offset" => "3600",
            ],
            "ROC" => [
                "timezone" => "ROC",
                "gmt_offset" => "28800",
                "dst_offset" => "28800",
            ],
            "ROK" => [
                "timezone" => "ROK",
                "gmt_offset" => "32400",
                "dst_offset" => "32400",
            ],
            "Singapore" => [
                "timezone" => "Singapore",
                "gmt_offset" => "28800",
                "dst_offset" => "28800",
            ],
            "Turkey" => [
                "timezone" => "Turkey",
                "gmt_offset" => "7200",
                "dst_offset" => "10800",
            ],
            "UCT" => [
                "timezone" => "UCT",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "US/Alaska" => [
                "timezone" => "US/Alaska",
                "gmt_offset" => "-32400",
                "dst_offset" => "-28800",
            ],
            "US/Aleutian" => [
                "timezone" => "US/Aleutian",
                "gmt_offset" => "-36000",
                "dst_offset" => "-32400",
            ],
            "US/Arizona" => [
                "timezone" => "US/Arizona",
                "gmt_offset" => "-25200",
                "dst_offset" => "-25200",
            ],
            "US/Central" => [
                "timezone" => "US/Central",
                "gmt_offset" => "-21600",
                "dst_offset" => "-18000",
            ],
            "US/East-Indiana" => [
                "timezone" => "US/East-Indiana",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "US/Eastern" => [
                "timezone" => "US/Eastern",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "US/Hawaii" => [
                "timezone" => "US/Hawaii",
                "gmt_offset" => "-36000",
                "dst_offset" => "-36000",
            ],
            "US/Indiana-Starke" => [
                "timezone" => "US/Indiana-Starke",
                "gmt_offset" => "-21600",
                "dst_offset" => "-18000",
            ],
            "US/Michigan" => [
                "timezone" => "US/Michigan",
                "gmt_offset" => "-18000",
                "dst_offset" => "-14400",
            ],
            "US/Mountain" => [
                "timezone" => "US/Mountain",
                "gmt_offset" => "-25200",
                "dst_offset" => "-21600",
            ],
            "US/Pacific" => [
                "timezone" => "US/Pacific",
                "gmt_offset" => "-28800",
                "dst_offset" => "-25200",
            ],
            "US/Pacific-New" => [
                "timezone" => "US/Pacific-New",
                "gmt_offset" => "-28800",
                "dst_offset" => "-25200",
            ],
            "US/Samoa" => [
                "timezone" => "US/Samoa",
                "gmt_offset" => "-39600",
                "dst_offset" => "-39600",
            ],
            "UTC" => [
                "timezone" => "UTC",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "Universal" => [
                "timezone" => "Universal",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
            "W-SU" => [
                "timezone" => "W-SU",
                "gmt_offset" => "10800",
                "dst_offset" => "10800",
            ],
            "WET" => [
                "timezone" => "WET",
                "gmt_offset" => "0",
                "dst_offset" => "3600",
            ],
            "Zulu" => [
                "timezone" => "Zulu",
                "gmt_offset" => "0",
                "dst_offset" => "0",
            ],
        ];

        if ( ! is_null($key) && isset( $timezones[$key] ) ) {
            $date = new DateTime("now", new DateTimeZone( $key ) );
            $timezones[$key]['current_time'] = $date->format('Y-m-d H:i:s');
            return $timezones[$key];
        }

        return $timezones;
    }
}


// must be last for initialization
zume_get_user_profile();
