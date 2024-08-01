<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Queries {

    // this is a reusable query that gets the user_id, post_id (contact_id), stage, and report id (rid) from the reports table.
    public static $query_for_user_stage = "SELECT r.user_id, r.post_id, r.post_id as contact_id, MAX(r.value) as stage, MAX(r.id) as rid FROM zume_dt_reports r
                                                  WHERE r.type = 'stage' and r.subtype = 'current_level'
                                                  GROUP BY r.user_id, r.post_id";


    public static function stage_totals() {
        global $wpdb;
        $query_for_user_stage = self::$query_for_user_stage;

        $results = $wpdb->get_results(
            "SELECT tb.stage, count(tb.user_id) as total
                FROM
                (
                   $query_for_user_stage
                ) as tb
                GROUP BY tb.stage;",
        ARRAY_A );

        $stages = [];

        if ( empty( $results ) ) {
            return $stages;
        }

        foreach ( $results as $result ) {
            $stages[ $result['stage'] ] = $result;
        }

        return $stages;
    }

    public static function stage_by_location( array $range = [ 1 ] ) {
        global $wpdb;
        $query_for_user_stage = self::$query_for_user_stage;

        if ( count( $range ) > 1 ) {
            $range = '(' . implode( ',', $range ) . ')';
        } else {
            $range = '(' . $range[0] . ')';
        }

        $results = $wpdb->get_results(
            "SELECT p.post_title as name, tb.user_id, tb.post_id, lgm.post_type, tb.stage, lgm.label, lgm.grid_id, lgm.lng, lgm.lat, lgm.level
            FROM
            (
              $query_for_user_stage
            ) as tb
            LEFT JOIN zume_posts p ON p.ID=tb.post_id
            LEFT JOIN zume_dt_location_grid_meta lgm ON lgm.post_id=tb.post_id AND lgm.post_type='contacts'
            WHERE tb.stage IN $range;", ARRAY_A );

        if ( empty( $results ) ) {
            return [];
        }

        return $results;
    }

    public static function stage_by_boundary( array $range, float $north, float $south, float $east, float $west ) {
        global $wpdb;
        $query_for_user_stage = self::$query_for_user_stage;

        if ( count( $range ) > 1 ) {
            $range = '(' . implode( ',', $range ) . ')';
        } else {
            $range = '(' . $range[0] . ')';
        }

        $results = $wpdb->get_results(
            "SELECT p.post_title as name, tb.user_id, tb.post_id, lgm.post_type, tb.stage, lgm.label, lgm.grid_id, lgm.lng, lgm.lat, lgm.level
            FROM
            (
              $query_for_user_stage
            ) as tb
            LEFT JOIN zume_posts p ON p.ID=tb.post_id
            LEFT JOIN zume_dt_location_grid_meta lgm ON lgm.post_id=tb.post_id AND lgm.post_type='contacts'
            WHERE tb.stage IN $range
            AND lgm.lat > $south
            AND lgm.lat < $north
            AND lgm.lng > $west
            AND lgm.lng < $east
            ;", ARRAY_A );

        if ( empty( $results ) ) {
            return [];
        }

        return $results;
    }

    public static function churches_with_location() {
        global $wpdb;

        $results = $wpdb->get_results(
            "SELECT p.ID as post_id, p.post_title as name, 'groups' as post_type, lgm.grid_id, lgm.lng, lgm.lat, lgm.level, lgm.source, lgm.label
            FROM zume_posts p
            LEFT JOIN zume_postmeta pm ON pm.post_id=p.ID AND pm.meta_key = 'location_grid_meta'
            LEFT JOIN zume_dt_location_grid_meta lgm ON lgm.grid_meta_id=pm.meta_value
            WHERE p.post_type = 'groups';", ARRAY_A );

        if ( empty( $results ) ) {
            return [];
        }

        return $results;
    }

    public static function churches_by_boundary( float $north, float $south, float $east, float $west ) {
        global $wpdb;

        $results = $wpdb->get_results(
            "SELECT p.ID, p.post_title as name, 'groups' as post_type, lgm.grid_id, lgm.lng, lgm.lat, lgm.level, lgm.source, lgm.label
            FROM zume_posts p
            LEFT JOIN zume_postmeta pm ON pm.post_id=p.ID AND pm.meta_key = 'location_grid_meta'
            LEFT JOIN zume_dt_location_grid_meta lgm ON lgm.grid_meta_id=pm.meta_value
            WHERE p.post_type = 'groups'
            AND lgm.lat > $south
            AND lgm.lat < $north
            AND lgm.lng > $west
            AND lgm.lng < $east
        ;", ARRAY_A );

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
            FROM zume_dt_reports
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
    public static function query_total_churches(): int {
        global $wpdb;
        $results = $wpdb->get_var(
            "SELECT count(*) as count
                    FROM zume_posts p
                    JOIN zume_postmeta pm ON pm.post_id=p.ID AND pm.meta_key = 'group_type' AND pm.meta_value = 'church'
                    JOIN zume_postmeta pm2 ON pm2.post_id=p.ID AND pm2.meta_key = 'group_status' AND pm2.meta_value = 'active'
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
    public static function query_total_practitioners(): int {
        global $wpdb;
        $query_for_user_stage = self::$query_for_user_stage;

        $results = $wpdb->get_var(
            "SELECT count(*) as practitioners
                FROM
                (
                    $query_for_user_stage
                ) as tb
            WHERE tb.stage >= 4;"
        );

        if ( $results ) {
            return (int) $results;
        } else {
            return 0;
        }
    }
}
