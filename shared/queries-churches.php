<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Query_Churches {

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

    public static function query_churches_cumulative( $range, $current = true ): int {
        global $wpdb;

        $begin = 1;
        $end = time();
        if ( ! $current ) {
            $end = strtotime( '-'. $range . ' days' );
        }

        $sql = "SELECT COUNT(*) as count
                FROM zume_posts p
                JOIN zume_postmeta pm2 ON pm2.post_id=p.ID AND pm2.meta_key = 'group_status' AND pm2.meta_value = 'active'
                LEFT JOIN zume_postmeta pm3 ON pm3.post_id=p.ID AND pm3.meta_key = 'church_start_date' AND pm3.meta_value != ''
                WHERE post_type = 'groups'
                    AND pm3.meta_value > $begin
                    AND pm3.meta_value < $end
        ;";

        $result = $wpdb->get_var($sql);

        if ( empty( $result ) ) {
            return 0;
        }

        return (float) $result;
    }



}
