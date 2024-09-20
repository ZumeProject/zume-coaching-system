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


}
