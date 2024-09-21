<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Query_Funnel {

    public static $query_for_user_stage = "SELECT r.user_id, r.post_id, r.post_id as contact_id, r.type, r.subtype, MAX(r.value) as stage, MAX(r.id) as rid, MAX(r.timestamp) as timestamp FROM zume_dt_reports r
                                                  WHERE r.type = 'system' and r.subtype = 'current_level'
                                                  GROUP BY r.user_id, r.post_id, r.type, r.subtype";

    public static function query_for_user_stage( $stage, $range, $trend = false ) {

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

        $sql = "SELECT
                    p.post_title as name,
                    sub.user_id,
                    sub.post_id,
                    pm.meta_value as coaching_contact_id,
                    slgm.lng,
                    slgm.lat,
                    slgm.level,
                    slgm.label,
                    slgm.grid_id,
                    pm1.meta_value as user_email,
                    pm2.meta_value as user_phone,
					sub.timestamp
                FROM (
                    SELECT rr.user_id, rr.post_id, rr.type, rr.subtype, MAX(rr.value) as stage, MAX(rr.id) as rid, MAX(rr.timestamp) as timestamp FROM zume_dt_reports rr
                    WHERE rr.type = 'system' and rr.subtype = 'current_level'
                    GROUP BY rr.user_id, rr.post_id, rr.type, rr.subtype
                ) as sub
                LEFT JOIN zume_dt_location_grid_meta slgm ON slgm.post_id=sub.post_id
                LEFT JOIN zume_postmeta pm ON pm.post_id=sub.post_id AND pm.meta_key = 'coaching_contact_id'
                LEFT JOIN zume_posts p ON p.ID=sub.post_id
                LEFT JOIN zume_postmeta pm1 ON pm1.post_id=sub.post_id AND pm1.meta_key = 'user_email'
                LEFT JOIN zume_postmeta pm2 ON pm2.post_id=sub.post_id AND pm2.meta_key = 'user_phone'
                WHERE sub.stage = $stage
                AND sub.timestamp > $begin
                AND sub.timestamp < $end
                ";

        return $sql;
    }

    public static function query_for_user_stages( array $stages, $range, $trend = false ) {

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

        $sql = "SELECT p.post_title as name, sub.user_id, sub.post_id, pm.meta_value as coaching_contact_id,  slgm.lng, slgm.lat, slgm.level, slgm.label, slgm.grid_id
                    p.post_title as name,
                    sub.user_id,
                    sub.post_id,
                    pm.meta_value as coaching_contact_id,
                    sub.stage,
                    slgm.lng,
                    slgm.lat,
                    slgm.level,
                    slgm.label,
                    slgm.grid_id,
                    pm1.meta_value as user_email,
                    pm2.meta_value as user_phone,
					sub.timestamp
                FROM (
                    SELECT rr.user_id, rr.post_id, rr.type, rr.subtype, MAX(rr.value) as stage, MAX(rr.id) as rid, MAX(rr.timestamp) as timestamp FROM zume_dt_reports rr
                    WHERE rr.type = 'system' and rr.subtype = 'current_level'
                    GROUP BY rr.user_id, rr.post_id, rr.type, rr.subtype
                ) as sub
                LEFT JOIN zume_dt_location_grid_meta slgm ON slgm.post_id=sub.post_id
                LEFT JOIN zume_postmeta pm ON pm.post_id=sub.post_id AND pm.meta_key = 'coaching_contact_id'
                LEFT JOIN zume_posts p ON p.ID=sub.post_id
                LEFT JOIN zume_postmeta pm1 ON pm1.post_id=sub.post_id AND pm1.meta_key = 'user_email'
                LEFT JOIN zume_postmeta pm2 ON pm2.post_id=sub.post_id AND pm2.meta_key = 'user_phone'
                WHERE sub.stage = ( $stages_list )
                AND sub.timestamp > $begin
                AND sub.timestamp < $end
                ";

        return $sql;
    }

    public static function stage_total( $stage, $range, $trend = false ) {
        global $wpdb;
        $query_for_user_stage = self::query_for_user_stage( $stage, $range, $trend );

        $sql = "SELECT COUNT(tb.user_id)
                FROM
                (
                   $query_for_user_stage
                ) as tb
                ;";

        $result = $wpdb->get_var( $sql );

        if ( empty( $result ) ) {
            return 0;
        }

        return (float) $result;
    }

    public static function stage_total_list( $stage, $range, $trend = false ) {
        global $wpdb;
        $query_for_user_stage = self::query_for_user_stage( $stage, $range, $trend );

        $sql = "SELECT *
                FROM
                (
                   $query_for_user_stage
                ) as tb
                ORDER BY tb.name
                ;";

        $list = $wpdb->get_results( $sql, ARRAY_A );

        if ( empty( $list ) ) {
            return [];
        }

        return $list;
    }

    public static function stage_by_location( array $stages, $range, $trend = false ) {
        global $wpdb;
        $query_for_user_stage = self::query_for_user_stages( $stages, $range, $trend );

        $results = $wpdb->get_results( $query_for_user_stage, ARRAY_A );

        if ( empty( $results ) ) {
            return [];
        }

        return $results;
    }

    public static function stage_by_boundary( array $stages, $range, float $north, float $south, float $east, float $west, $trend = false ) {
        global $wpdb;
        $query_for_user_stage = self::query_for_user_stages( $stages, $range, $trend );

        $sql = "SELECT *
            FROM
            (
              $query_for_user_stage
            ) as tb
            WHERE tb.lat > $south
            AND tb.lat < $north
            AND tb.lng > $west
            AND tb.lng < $east
            ;";
        $results = $wpdb->get_results($sql, ARRAY_A );

        if ( empty( $results ) ) {
            return [];
        }

        return $results;
    }

    /**
     * Returns the total number of practitioners in the system.
     * @return int
     */
    public static function query_total_practitioners( $stages = [ 3,4,5,6 ], $range = -1, $trend = false ): int {
        global $wpdb;
        $query_for_user_stage = self::query_for_user_stages( $stages, $range, $trend );

        $sql = "SELECT COUNT(tb.user_id)
                FROM
                (
                   $query_for_user_stage
                ) as tb
                ";

        $result = $wpdb->get_var($sql);

        if ( empty( $result ) ) {
            return 0;
        }

        return (float) $result;
    }

    public static function has_coach( $stage, $range = -1, $trend = false, $negative = false ) {
        $list = self::has_coach_list( $stage, $range, $trend, $negative );
        if( empty( $list ) ) {
            return 0;
        } else {
            return count( $list );
        }
    }

    public static function has_coach_list( $stage, $range = -1, $trend = false, $negative = false ) {
        global $wpdb;
        $query_for_user_stage = self::query_for_user_stage( $stage, $range, $trend );

        $list = $wpdb->get_results( $query_for_user_stage, ARRAY_A );

        $data_list = [
            'negative' => [],
            'positive' => [],
        ];
        foreach( $list as $row ) {
            if ( $row['coaching_contact_id'] ) {
                $data_list['positive'][] = $row;
            } else {
                $data_list['negative'][] = $row;
            }
        }

        if ( $negative ) {
            if ( empty( $data_list['negative'] ) ) {
                return [];
            } else {
                return $data_list['negative'];
            }
        } else {
            if ( empty( $data_list['positive'] ) ) {
                return [];
            } else {
                return $data_list['positive'];
            }
        }
    }

    public static function checkins( $stage, $range, $trend = false, $negative = false ) {
        $list = self::checkins_list( $stage, $range, $trend, $negative );
        if( empty( $list ) ) {
            return 0;
        } else {
            return count( $list );
        }
    }

    public static function checkins_list( $stage, $range, $trend = false, $negative = false ) {
        global $wpdb;
        $query_for_user_stage = self::query_for_user_stage( $stage, $range, $trend );

        $sql = "
            SELECT COUNT(*)
            FROM
               (
                  $query_for_user_stage
                ) as tb
            LEFT JOIN zume_dt_reports r ON r.user_id=tb.user_id AND r.type = 'training' AND r.subtype LIKE 'set_%'
            ";

        $list = $wpdb->get_results( $sql, ARRAY_A );

        $data_list = [
            'negative' => [],
            'positive' => [],
        ];
        foreach( $list as $row ) {
            if ( $row['id'] ) {
                $data_list['positive'][] = $row;
            } else {
                $data_list['negative'][] = $row;
            }
        }

        if ( $negative ) {
            if ( empty( $data_list['negative'] ) ) {
                return [];
            } else {
                return $data_list['negative'];
            }
        } else {
            if ( empty( $data_list['positive'] ) ) {
                return [];
            } else {
                return $data_list['positive'];
            }
        }
    }



    public static function query_stage_by_type_and_subtype( $stage, $range, $type, $subtype, $trend = false, $negative = false ) {
        $list = self::query_stage_by_type_and_subtype_list( $stage, $range, $type, $subtype, $trend, $negative );
        if( empty( $list ) ) {
            return 0;
        } else {
            return count( $list );
        }
    }

    public static function query_stage_by_type_and_subtype_list( $stage, $range, $type, $subtype, $trend = false, $negative = false ) {
        global $wpdb;
        $query_for_user_stage = self::query_for_user_stage( $stage, $range, $trend );

        $sql = "
            SELECT DISTINCT *
            FROM
               (
                  $query_for_user_stage
                ) as tb
            LEFT JOIN zume_dt_reports r ON r.user_id=tb.user_id AND r.type = '$type' AND r.subtype LIKE '$subtype'
            ";
//        dt_write_log($sql);
        $list = $wpdb->get_results( $sql, ARRAY_A );

        $data_list = [
            'negative' => [],
            'positive' => [],
        ];
        foreach( $list as $row ) {
            if ( $row['id'] ) {
                $data_list['positive'][] = $row;
            } else {
                $data_list['negative'][] = $row;
            }
        }

        if ( $negative ) {
            if ( empty( $data_list['negative'] ) ) {
                return [];
            } else {
                return $data_list['negative'];
            }
        } else {
            if ( empty( $data_list['positive'] ) ) {
                return [];
            } else {
                return $data_list['positive'];
            }
        }
    }

    public static function flow( $stage, $flow, $range = -1 ) {
        // flow = in, idle, out
        global $wpdb;
        $query_for_user_stage = self::$query_for_user_stage;

        if ( $range < 1 ) {
            $timestamp = 0;
        } else {
            $timestamp = strtotime( '-'. $range . ' days' );
        }

        if ( $flow === 'idle') {
            $sql = "
            SELECT COUNT(*)
            FROM
               (
                  $query_for_user_stage
                ) as tb
            WHERE tb.stage = $stage AND tb.timestamp < $timestamp;
            ";
        }
        else if ( $flow === 'in') {
            $sql = "
            SELECT COUNT(*)
            FROM
               (
                  $query_for_user_stage
                ) as tb
            WHERE tb.stage = $stage AND tb.timestamp > $timestamp;
            ";
        }
        else if ( $flow === 'out') {
            $next_stage = $stage++;
            $sql = "
            SELECT COUNT(*)
            FROM
               (
                  $query_for_user_stage
                ) as tb
            WHERE tb.stage = $next_stage AND tb.timestamp < $timestamp;
            ";
        }

        $count = $wpdb->get_var( $sql );

        if ( $count < 1 ) {
            return 0;
        }

        return (float) $count;
    }
}
