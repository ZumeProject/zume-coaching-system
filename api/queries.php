<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Coaching_Query {

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

    public static function log( $params ) {

        $time = strtotime( 'Today -'.$params['days_ago'].' days' );

        $contact_id = Disciple_Tools_Users::get_contact_for_user($params['user_id']);

        return dt_report_insert( [
            'type' => 'zume',
            'subtype' => $params['subtype'],
            'post_id' => $contact_id,
            'value' => $params['value'],
            'grid_id' => $params['grid_id'],
            'label' => $params['label'],
            'lat' => $params['lat'],
            'lng' => $params['lng'],
            'level' => $params['level'],
            'user_id' => $params['user_id'],
            'time_end' => $time,
            'hash' => hash('sha256', maybe_serialize($params)  . time() ),
        ] );
    }

}
