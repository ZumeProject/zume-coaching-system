<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Goals_Query {

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

    public static function list( $params ) {
        global $wpdb;

        $list = $wpdb->get_results( $wpdb->prepare(
            "
                    SELECT ID, display_name, user_registered
                    FROM $wpdb->users
                    ORDER BY user_registered DESC
                    LIMIT 100
                    " ) );

        return $list;
    }

    public static function query_total_churches() {
        global $wpdb;
        $results = $wpdb->get_var(
            "SELECT count(*) as count
                    FROM wp_posts p
                    JOIN wp_postmeta pm ON pm.post_id=p.ID AND pm.meta_key = 'group_type' AND pm.meta_value = 'church'
                    JOIN wp_postmeta pm2 ON pm2.post_id=p.ID AND pm2.meta_key = 'group_status' AND pm2.meta_value = 'active'
                    WHERE post_type = 'groups';"
        );
        if ( $results ) {
            return (int) $results;
        } else {
            return 0;
        }
    }

    public static function query_total_practitioners() {
        global $wpdb;
        $results = $wpdb->get_var(
            "SELECT count(*) as practitioners
                FROM
                (
                    SELECT r.user_id, MAX(r.value) as stage FROM wp_dt_reports r
                    WHERE r.type = 'stage' and r.subtype = 'current_level' and r.value >= 4
                    GROUP BY r.user_id
                ) as tb;");

        if ( $results ) {
            return (int) $results;
        } else {
            return 0;
        }
    }



    public static function format_int( $int ) {
        return number_format( $int, 0, '.', ',' );
    }

    public static function get_valence( $value, $compare, $negative_stat = false ) {
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
    public static function get_percent( $value, $compare ) {
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

}
