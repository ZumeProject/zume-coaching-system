<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Query_Cumulative extends Zume_Queries_Base {

    public static function locations( $stages = [ 0,1,2,3,4,5,6 ], $end_date = null, $trend = false, $negative = false ) {
        if ( is_null( $end_date ) ) {
            $end_date = time();
        }
        $list = self::locations_list_cumulative( $stages, $end_date, $trend, $negative );
        if( empty( $list ) ) {
            return 0;
        } else {
            return count( $list );
        }
    }

    public static function locations_list_cumulative( $stages = [ 0,1,2,3,4,5,6 ], $end_date = null, $trend = false, $negative = false ) {
        global $wpdb;
        $world_grid_ids = self::world_grid_id_sql();
        if ( is_null( $end_date ) ) {
            $end_date = time();
        }

        $begin = 0;
        if ( empty( $end_date ) ) {
            $end = time();
        } else {
            $end = $end_date;
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

        return $wpdb->get_results( $sql, ARRAY_A );
    }

    public static function languages( $stages = [ 1,2,3,4,5,6 ], $end_date = null, $trend = false, $negative = false ) {
        if ( is_null( $end_date ) ) {
            $end_date = time();
        }
        $language_list = self::languages_list_cumulative( $stages, $end_date, $trend, $negative );
        if( empty( $language_list ) ) {
            return 0;
        } else {
            return count( $language_list );
        }
    }

    public static function languages_list_cumulative( $stages = [ 1 ], $end_date = null, $trend = false, $negative = false ) {
        global $wpdb;
        $languages = zume_languages();
        if ( is_null( $end_date ) ) {
            $end_date = time();
        }

        $begin = 0;
        if ( empty( $end_date ) ) {
            $end = time();
        } else {
            $end = $end_date;
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
            $language_list[$lang['language_code']] = $languages[$lang['language_code']] ?? [];
            $language_list[$lang['language_code']]['activities'] = $lang['activities'];
        }

        return $language_list;
    }

    public static function has_coach( array $stage, $end_date = null, $trend = false, $negative = false ) {
        if ( is_null( $end_date ) ) {
            $end_date = time();
        }
        $list = self::has_coach_list_cumulative( $stage, $end_date, $trend, $negative );
        if( empty( $list ) ) {
            return 0;
        } else {
            return count( $list );
        }
    }

    public static function has_coach_list_cumulative( array $stage, $end_date = null, $trend = false, $negative = false ) {
        global $wpdb;
        if ( is_null( $end_date ) ) {
            $end_date = time();
        }
        $query_for_user_stage = self::query_for_user_stages( $stage, $end_date, $trend );

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

    public static function registrations( $end_date, $trend = false ) {
        global $wpdb;
        $query_for_user_stage = self::$query_for_user_stage;

        $begin = 0;
        if ( empty( $end_date ) ) {
            $end = time();
        } else {
            $end = $end_date;
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

        if ( $count < 1 ) {
            return 0;
        }

        return (float) $count;
    }

    public static function query_practitioners_cumulative( $stages, $range, $current = true ): int {
        global $wpdb;
        $query_for_user_stage = self::$query_for_user_stage;

        $begin = 0;
        $end = time();
        if ( ! $current ) {
            $end = strtotime( '-'. $range . ' days' );
        }

        if ( ! $stages ) {
            $stages = [ 3,4,5,6 ];
        }

        $stages_list = dt_array_to_sql( $stages );

        $sql = "SELECT COUNT(tb.user_id)
                FROM
                (
                   $query_for_user_stage
                ) as tb
                WHERE tb.timestamp > $begin
                  AND tb.timestamp < $end
                  AND tb.stage IN ($stages_list)
                ";

        $result = $wpdb->get_var($sql);

        if ( empty( $result ) ) {
            return 0;
        }

        return (float) $result;
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

    public static function query_stages_by_type_and_subtype( $end_date, $type, $subtype, $negative = false ) {
        $list = self::query_stages_by_type_and_subtype_list( $end_date, $type, $subtype, $negative );
        if( empty( $list ) ) {
            return 0;
        } else {
            return count( $list );
        }
    }

    public static function query_stages_by_type_and_subtype_list( $end_date, $type, $subtype, $negative = false ) {
        global $wpdb;
        $query_for_user_stage = self::query_cumulative_for_user_stages( $end_date );

        $sql = "
            SELECT DISTINCT *
            FROM
               (
                  $query_for_user_stage
                ) as tb
            LEFT JOIN zume_dt_reports r ON r.user_id=tb.user_id AND r.type = '$type' AND r.subtype LIKE '$subtype'
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

    public static function checkins( $end_date) {
        global $wpdb;
        $query_for_user_stage = self::query_cumulative_for_user_stages( $end_date );

        $sql = "
            SELECT COUNT(*)
            FROM
               (
                  $query_for_user_stage
                ) as tb
            JOIN zume_dt_reports r ON r.user_id=tb.user_id AND r.type = 'training' AND r.subtype LIKE 'set_%'
            ";
        $count = $wpdb->get_var( $sql );

        return (float) $count;
    }

    public static function downloads( $end_date) {
        global $wpdb;

        $sql = "
            SELECT COUNT(*)
            FROM `zume_dt_reports_anonymous`
            WHERE type = 'downloading'
                AND timestamp < $end_date;
            ";
        $count = $wpdb->get_var( $sql );

        return (float) $count;
    }
}
