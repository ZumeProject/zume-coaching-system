<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Query_Time_Range extends Zume_Queries_Base {

    public static function locations( $stages = [ 0,1,2,3,4,5,6 ], $range = -1, $trend = false, $negative = false ) {
        $list = self::locations_list( $stages, $range, $trend, $negative );
        if( empty( $list ) ) {
            return 0;
        } else {
            return count( $list );
        }
    }

    public static function locations_list( $stages = [ 0,1,2,3,4,5,6 ], $range = -1, $trend = false, $negative = false ) {
        // object cache
        $request_key = hash( 'md5', serialize( __METHOD__ . serialize( $stages ) . $range . $trend . $negative ) );
        $cached = wp_cache_get( $request_key, 'zume' );
        if ( $cached ) {
            dt_write_log( __METHOD__ . ' cache hit' );
            return $cached;
        }

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

        $stages_list = dt_array_to_sql( $stages );

        $sql = "
            SELECT DISTINCT r.grid_id, r.label
            FROM zume_dt_reports r
            JOIN
            (
                $world_grid_ids
            ) as grid_ids ON r.grid_id=grid_ids.grid_id
            WHERE r.value IN ( $stages_list )
              AND r.timestamp > $begin
              AND r.timestamp < $end
            ";
        $list = $wpdb->get_results( $sql, ARRAY_A );

        if ( empty( $list ) ) {
            $list = [];
        }

        wp_cache_set( $request_key, $list, 'zume' );

        return $list;
    }

    public static function languages( $stages = [ 1,2,3,4,5,6 ], $range = -1, $trend = false, $negative = false ) {
        $language_list = self::languages_list( $stages, $range, $trend, $negative );
        if( empty( $language_list ) ) {
            return 0;
        } else {
            return count( $language_list );
        }
    }

    public static function languages_list( $stages = [ 1 ], $range = -1, $trend = false, $negative = false ) {
        // object cache
        $request_key = hash( 'md5', serialize( __METHOD__ . serialize( $stages ) . $range . $trend . $negative ) );
        $cached = wp_cache_get( $request_key, 'zume' );
        if ( $cached ) {
            dt_write_log( __METHOD__ . ' cache hit' );
            return $cached;
        }

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
            $list = [];
        }

        $language_list = [];
        foreach( $list as $lang ) {
            $language_list[$lang['language_code']] = $languages[$lang['language_code']] ?? [];
            $language_list[$lang['language_code']]['activities'] = $lang['activities'];
        }

        wp_cache_set( $request_key, $language_list, 'zume' );

        return $language_list;
    }

    public static function has_coach( array $stage, $range = -1, $trend = false, $negative = false ) {
        $list = self::has_coach_list( $stage, $range, $trend, $negative );
        if( empty( $list ) ) {
            return 0;
        } else {
            return count( $list );
        }
    }

    public static function has_coach_list( array $stage, $range = -1, $trend = false, $negative = false ) {
        global $wpdb;
        $query_for_user_stage = self::query_for_user_stages( $stage, $range, $trend );

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

    public static function registrations(  $range = -1, $trend = false ) {
        // object cache
        $request_key = hash( 'md5', serialize( __METHOD__ . $range . $trend ) );
        $cached = wp_cache_get( $request_key, 'zume' );
        if ( $cached ) {
            dt_write_log( __METHOD__ . ' cache hit' );
            return $cached;
        }

        global $wpdb;
        $query_for_user_stage = self::$query_for_user_stage;

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
            SELECT COUNT(*)
            FROM
               (
                  $query_for_user_stage
                ) as tb
            JOIN zume_dt_reports r ON r.user_id=tb.user_id AND r.type = 'training' AND r.subtype = 'registered'
            WHERE tb.timestamp > $begin AND tb.timestamp < $end;
            ";
        $count = $wpdb->get_var( $sql );

        if ( empty( $count ) ) {
            $count = 0;
        } else {
            $count = (int) $count;
        }

        wp_cache_set( $request_key, $count, 'zume' );

        return $count;
    }
    public static function downloads( $range = -1, $trend = false ) {
        // object cache
        $request_key = hash( 'md5', serialize( __METHOD__ . $range . $trend ) );
        $cached = wp_cache_get( $request_key, 'zume' );
        if ( $cached ) {
            dt_write_log( __METHOD__ . ' cache hit' );
            return $cached;
        }

        global $wpdb;

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
            SELECT COUNT(*)
            FROM `zume_dt_reports_anonymous`
            WHERE type = 'downloading'
                AND timestamp > $begin
                AND timestamp < $end;
            ";
//        dt_write_log($sql);
        $count = $wpdb->get_var( $sql );

        if ( empty( $count ) ) {
            $count = 0;
        } else {
            $count = (int) $count;
        }

        wp_cache_set( $request_key, $count, 'zume' );

        return $count;
    }


}
