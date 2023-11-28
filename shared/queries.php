<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Queries {

//    public static function list( $params ) {
//        global $wpdb;
//
//        $list = $wpdb->get_results( $wpdb->prepare(
//            "
//                    SELECT ID, display_name, user_registered
//                    FROM $wpdb->users
//                    ORDER BY user_registered DESC
//                    LIMIT 100
//                    ", ARRAY_A ) );
//
//        return $list;
//    }

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

    public static function stage_by_location( array $range = [ 1 ] ) {
        global $wpdb;

        if( count( $range ) > 1 ) {
            $range = '(' . implode( ',', $range ) . ')';
        } else {
            $range = '(' . $range[0] . ')';
        }

        $results = $wpdb->get_results(
            "SELECT p.post_title as name, tb.user_id, tb.post_id, 'contacts' as post_type, tb.stage, lgm.label, lgm.grid_id, lgm.lng, lgm.lat, lgm.level
            FROM
            (
              SELECT r.user_id, r.post_id, MAX(r.value) as stage, MAX(r.id) as rid FROM wp_dt_reports r
              WHERE r.type = 'stage' and r.subtype = 'current_level'
              GROUP BY r.user_id, r.post_id
            ) as tb
            LEFT JOIN wp_posts p ON p.ID=tb.post_id
            LEFT JOIN wp_dt_location_grid_meta lgm ON lgm.post_id=tb.post_id AND lgm.post_type='contacts'
            WHERE tb.stage IN $range;", ARRAY_A );

        if ( empty( $results ) ) {
            return [];
        }

        return $results;
    }

    public static function churches_with_location( ) {
        global $wpdb;

        $results = $wpdb->get_results(
            "SELECT p.ID as post_id, p.post_title as name, 'groups' as post_type, lgm.grid_id, lgm.lng, lgm.lat, lgm.level, lgm.source, lgm.label
            FROM wp_posts p
            LEFT JOIN wp_postmeta pm ON pm.post_id=p.ID AND pm.meta_key = 'location_grid_meta'
            LEFT JOIN wp_dt_location_grid_meta lgm ON lgm.grid_meta_id=pm.meta_value
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
            FROM wp_posts p
            LEFT JOIN wp_postmeta pm ON pm.post_id=p.ID AND pm.meta_key = 'location_grid_meta'
            LEFT JOIN wp_dt_location_grid_meta lgm ON lgm.grid_meta_id=pm.meta_value
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

    public static function stage_by_boundary( array $range, float $north, float $south, float $east, float $west ) {
        global $wpdb;

        if( count( $range ) > 1 ) {
            $range = '(' . implode( ',', $range ) . ')';
        } else {
            $range = '(' . $range[0] . ')';
        }

        $results = $wpdb->get_results(
            "SELECT p.post_title as name, tb.user_id, tb.post_id,  'groups' as post_type, tb.stage, lgm.label, lgm.grid_id, lgm.lng, lgm.lat, lgm.level
            FROM
            (
              SELECT r.user_id, r.post_id, MAX(r.value) as stage, MAX(r.id) as rid FROM wp_dt_reports r
              WHERE r.type = 'stage' and r.subtype = 'current_level'
              GROUP BY r.user_id, r.post_id
            ) as tb
            LEFT JOIN wp_posts p ON p.ID=tb.post_id
            LEFT JOIN wp_dt_location_grid_meta lgm ON lgm.post_id=tb.post_id AND lgm.post_type='contacts'
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
                ) as tb;"
        );

        if ( $results ) {
            return (int) $results;
        } else {
            return 0;
        }
    }

    public static function query_total_has_no_friends() : int {
        global $wpdb;
        $results = $wpdb->get_var(
            "SELECT
                COUNT( DISTINCT object_id ) - ( SELECT COUNT( DISTINCT object_id )
                FROM wp_dt_activity_log
                WHERE meta_key = 'contacts_to_relation' ) AS friendless_users
            FROM wp_dt_activity_log;"
        );

        if ( $results ) {
            return (int) $results;
        } else {
            return 0;
        }
    }

    public static function query_total_has_no_coach() : int {
        global $wpdb;
        $results = $wpdb->get_var(
            "SELECT
                COUNT( DISTINCT object_id ) - ( SELECT COUNT( DISTINCT meta_value )
                FROM wp_3_postmeta
                WHERE meta_key = 'trainee_user_id' ) AS friendless_users
        FROM wp_dt_activity_log;"
        );

        if ( $results ) {
            return (int) $results;
        } else {
            return 0;
        }
    }
}