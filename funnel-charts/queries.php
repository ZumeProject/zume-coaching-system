<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Funnel_Query {

    public static function sample( $params ) {
        $negative_stat = false;
        if ( isset( $params['negative_stat'] ) && $params['negative_stat'] ) {
            $negative_stat = $params['negative_stat'];
        }

        $value = rand(100, 1000);
        $goal = rand(500, 700);
        $trend = rand(500, 700);
        return [
            'key' => 'sample',
            'label' => 'Sample',
            'link' => 'sample',
            'description' => 'Sample description.',
            'value' => self::format_int( $value ),
            'valence' => self::get_valence( $value, $goal, $negative_stat ),
            'goal' => $goal,
            'goal_valence' => self::get_valence( $value, $goal, $negative_stat ),
            'goal_percent' => self::get_percent( $value, $goal ),
            'trend' => $trend,
            'trend_valence' => self::get_valence( $value, $trend, $negative_stat ),
            'trend_percent' => self::get_percent( $value, $trend ),
            'negative_stat' => $negative_stat,
        ];
    }

    public static function stage_totals( $stage = NULL ) {
        global $wpdb;

        $results = $wpdb->get_results(
        "SELECT tb.stage, count(tb.user_id) as total
                FROM
                (
                    SELECT r.user_id, MAX(r.value) as stage FROM wp_dt_reports r
                    WHERE r.type = 'stage' and r.subtype = 'current_level'
                    GROUP BY r.user_id
                ) as tb
                GROUP BY tb.stage;"
            , ARRAY_A );

        $totals = [
            '1' => 0,
            '2' => 0,
            '3' => 0,
            '4' => 0,
            '5' => 0,
            '6' => 0,
        ];
        if ( empty( $results ) ) {
            return $totals;
        }

        foreach( $results as $result ) {
            $totals[$result['stage']] = (int) $result['total'];
        }

        if ( is_null( $stage ) ) {
            return $totals;
        }

        if ( 1 == $stage ) {
            return (int) $totals['1'] ?? 0;
        } else if ( 2 == $stage ) {
            return (int) $totals['2'] ?? 0;
        } else if ( 3 == $stage ) {
            return (int) $totals['3'] ?? 0;
        } else if ( 4 == $stage ) {
            return (int) $totals['4'] ?? 0;
        } else if ( 5 == $stage ) {
            return (int) $totals['5'] ?? 0;
        } else if ( 6 == $stage ) {
            return (int) $totals['6'] ?? 0;
        }

        return $totals;
    }


    public static function training_elements( $params ) {
        global $wpdb;
        $items = zume_training_items();
//        dt_write_log( $items );

        $list = $wpdb->get_results( $wpdb->prepare(
            "SELECT subtype, COUNT(*) as value
            FROM wp_dt_reports
            WHERE type = 'training' AND subtype LIKE '%heard'
            GROUP BY subtype
            " ), ARRAY_A );

        foreach( $list as $index => $value ) {
            $list[$index]['value'] = (int) $value['value'];
            $key = intval(substr( $value['subtype'], 0, 2 ));
            $list[$index]['label'] = $items[$key]['title'] ?? $value['subtype'];
        }

        return $list;
    }

    public static function trainees_list( $params ) {
        global $wpdb;

        $list = $wpdb->get_results( $wpdb->prepare(
            "
                    SELECT ID, display_name, user_registered
                    FROM wp_users
                    ORDER BY user_registered DESC
                    LIMIT 100
                    " ) );

        return $list;
    }


    public static function format_int( $int ) {
        $int = (float) $int;
        return number_format( $int, 0, '.', ',' );
    }
    public static function get_valence( float $value, float $compare, $negative_stat = false ) {
        $percent = self::get_percent( $value, $compare );

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
        } else {
            if ( $percent > 20 ) {
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
        }

        return $valence;
    }
    public static function get_percent( float $value, float $compare ) {
        $percent =  ( $value / $compare ) * 100;
        if ( $percent > 100 ) {
            $percent = round( $percent - 100, 1 );
        } else if ( $percent < 100 ) {
            $percent = round( (100 - $percent), 1) * -1;
        } else {
            $percent = 0;
        }
        return $percent;
    }
    public static function requested_range( $params ) {

        $requested_days = 'all';
        $days = 10000;
        $compare_days = 10000;
        if ( isset( $params['filter'] ) ) {
            if ( $params['filter'] < 1 ) {
                $requested_days = 'all';
                $days = 10000;
                $compare_days = 10000;
            } else {
                $requested_days = absint( $params['filter'] );
                $days = $requested_days + 1;
                $compare_days = $requested_days * 2 + 1;
            }
        }

        $range = [
            'days' => $requested_days,
            'end' => date( 'Y-m-d', strtotime( 'yesterday' ) ),
            'end_time' => strtotime( 'yesterday' ),
            'start' => date( 'Y-m-d', strtotime( '-' . $days . ' days' ) ),
            'start_time' => strtotime( '-' . $days . ' days' ),
            'compare_end' => date( 'Y-m-d', strtotime( '-' . $days  . ' days' ) ),
            'compare_end_time' => strtotime( '-' . $days  . ' days' ),
            'compare_start' => date( 'Y-m-d', strtotime( '-' . $compare_days . ' days' ) ),
            'compare_start_time' => strtotime( '-' . $compare_days . ' days' ),
        ];
        return $range;
    }

//    public static function log( $params ) {
//
//        $time = strtotime( 'Today -'.$params['days_ago'].' days' );
//
//        $contact_id = Disciple_Tools_Users::get_contact_for_user($params['user_id']);
//
//        return dt_report_insert( [
//            'type' => 'zume',
//            'subtype' => $params['subtype'],
//            'post_id' => $contact_id,
//            'value' => $params['value'],
//            'grid_id' => $params['grid_id'],
//            'label' => $params['label'],
//            'lat' => $params['lat'],
//            'lng' => $params['lng'],
//            'level' => $params['level'],
//            'user_id' => $params['user_id'],
//            'time_end' => $time,
//            'hash' => hash('sha256', maybe_serialize($params)  . time() ),
//        ] );
//    }

    public static function set_array( $params ) {
        $array = [];
        foreach ( $params as $key => $value ) {
            $array[$key] = $value;
        }
        return $array;
    }

}
