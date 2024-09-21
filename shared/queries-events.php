<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Query_Events {

    /**
     * Training subtype counts for all *heard* reports.
     *
     * subtype
     * value count
     * @return array
     */
    public static function training_subtype_counts(  ) {
        global $wpdb;

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT subtype, COUNT(*) as value
            FROM zume_dt_reports
            WHERE type = 'training' AND subtype LIKE '%heard'
            GROUP BY subtype
            " ), ARRAY_A );

        if ( empty( $results ) || is_wp_error( $results ) ) {
            return [];
        }

        return $results;
    }

    public static function locations( $range = -1, $trend = false, $negative = false ) {
        global $wpdb;
        $world_grid_ids = self::world_grid_id_sql();

        $end = time();
        if ( $range < 1 ) {
            $begin = 0;
        } else {
            $begin = strtotime( '-'. $range . ' days' );
            if ( $trend ) {
                $end = $begin;
                $begin = strtotime( '-'. ( $range * 2 ) . ' days' );
            }
        }

        $sql = "
            SELECT COUNT( DISTINCT( r.grid_id ) ) as count
            FROM zume_dt_reports r
            JOIN
            (
                $world_grid_ids
            ) as grid_ids ON r.grid_id=grid_ids.grid_id
            WHERE
              AND r.timestamp > $begin
              AND r.timestamp < $end
            ";
        $count = $wpdb->get_var( $sql );

        if ( $negative && $count ) {
            $count = 44395 - (int) $count;
        }

        if ( $count < 1 ) {
            return 0;
        }

        return (float) $count;
    }

    public static function languages( $stages = [ 1 ], $range = -1, $trend = false, $negative = false ) {
        $language_list = self::languages_list( $stages, $range, $trend, $negative );
        if( empty( $language_list ) ) {
            return 0;
        } else {
            return count( $language_list );
        }
    }

    public static function languages_list( $stages = [ 1 ], $range = -1, $trend = false, $negative = false ) {
        global $wpdb;
        $languages = zume_languages();

        $end = time();
        if ( $range < 1 ) {
            $begin = 0;
        } else {
            $begin = strtotime( '-'. $range . ' days' );
            if ( $trend ) {
                $end = $begin;
                $begin = strtotime( '-'. ( $range * 2 ) . ' days' );
            }
        }

        $stages_list = dt_array_to_sql( $stages );

        $sql = "
            SELECT language_code, count(*) as activities
            FROM zume_dt_reports r
            WHERE r.value IN ( $stages_list )
              AND r.timestamp > $begin
              AND r.timestamp < $end
            AND r.language_code != ''
            GROUP BY language_code
            ORDER BY activities DESC
            ";
        $list = $wpdb->get_results( $sql, ARRAY_A );

        if ( empty( $list ) ) {
            return [];
        }

        $language_list = [];
        foreach( $list as $lang ) {
            $language_list[$lang['language_code']] = $languages[$lang['language_code']];
            $language_list[$lang['language_code']]['activities'] = $lang['activities'];
        }

        return $language_list;
    }


}
