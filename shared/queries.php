<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Queries {

    public static function list( $params ) {
        global $wpdb;

        $list = $wpdb->get_results( $wpdb->prepare(
            "
                    SELECT ID, display_name, user_registered
                    FROM $wpdb->users
                    ORDER BY user_registered DESC
                    LIMIT 100
                    ", ARRAY_A ) );

        return $list;
    }

    public static function stage_totals() {
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

        if ( empty( $results ) ) {
            return [];
        }

        return $results;
    }

    /**
     * Training subtype counts for all *heard* reports.
     *
     * subtype
     * value count
     * @return array
     */
    public static function training_subtype_counts() {
        global $wpdb;

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT subtype, COUNT(*) as value
            FROM wp_dt_reports
            WHERE type = 'training' AND subtype LIKE '%heard'
            GROUP BY subtype
            " ), ARRAY_A );

        if ( empty( $results ) || is_wp_error( $results ) ) {
            return [];
        }

        return $results;
    }

    /**
     * Returns the total number of churches in the system.
     * @return int
     */
    public static function query_total_churches() : int {
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

    /**
     * Returns the total number of practitioners in the system.
     * @return int
     */
    public static function query_total_practitioners() : int {
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
}