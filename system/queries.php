<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Coaching_Queries
{
   public static function get_activity( $user_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare (
            "SELECT * FROM (
                        SELECT r.type as type, r.subtype as subtype, r.time_end as time, 'report' as source
                        FROM $wpdb->zume_reports r
                        WHERE user_id = %s
                        UNION ALL
                        SELECT a.action as type, a.object_type as subtype, a.hist_time as time, 'activity' as source
                        FROM $wpdb->zume_activity a
                        WHERE user_id = %s
                    ) as t ORDER BY time DESC
                    ", $user_id, $user_id ), ARRAY_A );
   }
}

