<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Queries {

    // this is a reusable query that gets the user_id, post_id (contact_id), stage, and report id (rid) from the reports table.
    public static $query_for_user_stage = "SELECT r.user_id, r.post_id, r.post_id as contact_id, r.type, r.subtype, MAX(r.value) as stage, MAX(r.id) as rid, MAX(r.timestamp) as timestamp FROM zume_dt_reports r
                                                  WHERE r.type = 'system' and r.subtype = 'current_level'
                                                  GROUP BY r.user_id, r.post_id, r.type, r.subtype";


    public static function stage_total( $stage, $range, $trend = false ) {
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

        $result = $wpdb->get_var(
            "SELECT COUNT(tb.user_id)
                FROM
                (
                   $query_for_user_stage
                ) as tb
                WHERE tb.timestamp > $begin
                  AND tb.timestamp < $end
                  AND tb.stage = $stage
                ;" );

        if ( empty( $result ) ) {
            return 0;
        }

        return (float) $result;
    }

    public static function stage_total_list( $stage, $range, $trend = false ) {
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

        $sql = "SELECT *
                FROM
                (
                   $query_for_user_stage
                ) as tb
                WHERE tb.timestamp > $begin
                  AND tb.timestamp < $end
                  AND tb.stage = $stage
                ;";

        dt_write_log($sql);

        $list = $wpdb->get_results( $sql, ARRAY_A );

        if ( empty( $list ) ) {
            return [];
        }

        return $list;
    }

    public static function stage_totals_by_range( $range, $trend = false ) {
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

        $results = $wpdb->get_results(
            "SELECT tb.stage, COUNT(tb.user_id) as total
                FROM
                (
                   $query_for_user_stage
                ) as tb
                WHERE tb.timestamp > $begin
                  AND tb.timestamp < $end
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

    public static function stage_by_location( array $stages, $range, $trend = false ) {
        global $wpdb;
        $query_for_user_stage = self::$query_for_user_stage;

        $range = (float) $range;

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

        if ( count( $stages ) > 1 ) {
            $stages = '(' . implode( ',', $stages ) . ')';
        } else {
            $stages = '(' . $stages[0] . ')';
        }

        $results = $wpdb->get_results(
            "SELECT p.post_title as name, tb.user_id, tb.post_id, tb.stage, lgm.label, lgm.grid_id, lgm.lng, lgm.lat, lgm.level
            FROM
            (
              $query_for_user_stage
            ) as tb
            LEFT JOIN zume_posts p ON p.ID=tb.post_id
            LEFT JOIN zume_dt_location_grid_meta lgm ON lgm.post_id=tb.post_id AND lgm.post_type='contacts'
            LEFT JOIN zume_dt_reports r1 ON r1.id=tb.rid
            WHERE tb.stage IN $stages
                  AND tb.timestamp > $begin
                  AND tb.timestamp < $end;", ARRAY_A );

//        dt_write_log($results);

        if ( empty( $results ) ) {
            return [];
        }

        return $results;
    }

    public static function stage_by_boundary( array $stages, $range, float $north, float $south, float $east, float $west, $trend = false ) {
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

        if ( count( $stages ) > 1 ) {
            $stages = '(' . implode( ',', $stages ) . ')';
        } else {
            $stages = '(' . $stages[0] . ')';
        }

        $sql = "SELECT p.post_title as name, tb.user_id, tb.post_id, tb.stage, lgm.label, lgm.grid_id, lgm.lng, lgm.lat, lgm.level
            FROM
            (
              $query_for_user_stage
            ) as tb
            LEFT JOIN zume_posts p ON p.ID=tb.post_id
            LEFT JOIN zume_dt_location_grid_meta lgm ON lgm.post_id=tb.post_id AND lgm.post_type='contacts'
            LEFT JOIN zume_dt_reports r1 ON r1.id=tb.rid
            WHERE tb.stage IN $stages
            AND lgm.lat > $south
            AND lgm.lat < $north
            AND lgm.lng > $west
            AND lgm.lng < $east
            AND tb.timestamp > $begin
            AND tb.timestamp < $end;
            ;";
        $results = $wpdb->get_results($sql, ARRAY_A );

//        dt_write_log($sql);

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
    public static function query_total_practitioners( $stages = [ 3,4,5,6 ], $range = -1, $trend = false ): int {
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

    public static function world_grid_sql(): string {
        return "SELECT
                lg1.grid_id, lg1.population, lg1.country_code, lg1.longitude, lg1.latitude,
                CONCAT_WS(', ',
                          IF(LENGTH(ga4.alt_name),ga4.alt_name,NULL),
                          IF(LENGTH(ga3.alt_name),ga3.alt_name,NULL),
                          IF(LENGTH(ga2.alt_name),ga2.alt_name,NULL),
                          IF(LENGTH(ga1.alt_name),ga1.alt_name,NULL),
                          IF(LENGTH(gc.alt_name),gc.alt_name,NULL)
                    ) as full_name
            FROM zume_dt_location_grid lg1
                     LEFT JOIN zume_dt_location_grid as gc ON lg1.admin0_grid_id=gc.grid_id
                     LEFT JOIN zume_dt_location_grid as ga1 ON lg1.admin1_grid_id=ga1.grid_id
                     LEFT JOIN zume_dt_location_grid as ga2 ON lg1.admin2_grid_id=ga2.grid_id
                     LEFT JOIN zume_dt_location_grid as ga3 ON lg1.admin3_grid_id=ga3.grid_id
                     LEFT JOIN zume_dt_location_grid as ga4 ON lg1.admin4_grid_id=ga4.grid_id
                     LEFT JOIN zume_dt_location_grid as ga5 ON lg1.admin5_grid_id=ga5.grid_id
            WHERE lg1.level = 0
              AND lg1.grid_id NOT IN ( SELECT lg11.admin0_grid_id FROM zume_dt_location_grid lg11 WHERE lg11.level = 1 AND lg11.admin0_grid_id = lg1.grid_id )
              #'China', 'India', 'France', 'Spain', 'Pakistan', 'Bangladesh'
              AND lg1.admin0_grid_id NOT IN (100050711,100219347, 100089589,100074576,100259978,100018514)
              #'Romania', 'Estonia', 'Bhutan', 'Croatia', 'Solomon Islands', 'Guyana', 'Iceland', 'Vanuatu', 'Cape Verde', 'Samoa', 'Faroe Islands', 'Norway', 'Uruguay', 'Mongolia', 'United Arab Emirates', 'Slovenia', 'Bulgaria', 'Honduras', 'Columbia', 'Namibia', 'Switzerland', 'Western Sahara'
              AND lg1.admin0_grid_id NOT IN (100314737,100083318,100041128,100133112,100341242,100132648,100222839,100379914,100055707,100379993,100130389,100255271,100363975,100248845,100001527,100342458,100024289,100132795,100054605,100253456,100342975,100074571)

              # above admin 0 (22)

            UNION ALL
            --
            # admin 1 locations that have no level 2 (768)
            --
            SELECT
                lg2.grid_id, lg2.population, lg2.country_code, lg2.longitude, lg2.latitude,
                CONCAT_WS(', ',
                          IF(LENGTH(ga4.alt_name),ga4.alt_name,NULL),
                          IF(LENGTH(ga3.alt_name),ga3.alt_name,NULL),
                          IF(LENGTH(ga2.alt_name),ga2.alt_name,NULL),
                          IF(LENGTH(ga1.alt_name),ga1.alt_name,NULL),
                          IF(LENGTH(gc.alt_name),gc.alt_name,NULL)
                    ) as full_name
            FROM zume_dt_location_grid lg2
                     LEFT JOIN zume_dt_location_grid as gc ON lg2.admin0_grid_id=gc.grid_id
                     LEFT JOIN zume_dt_location_grid as ga1 ON lg2.admin1_grid_id=ga1.grid_id
                     LEFT JOIN zume_dt_location_grid as ga2 ON lg2.admin2_grid_id=ga2.grid_id
                     LEFT JOIN zume_dt_location_grid as ga3 ON lg2.admin3_grid_id=ga3.grid_id
                     LEFT JOIN zume_dt_location_grid as ga4 ON lg2.admin4_grid_id=ga4.grid_id
                     LEFT JOIN zume_dt_location_grid as ga5 ON lg2.admin5_grid_id=ga5.grid_id
            WHERE lg2.level = 1
              AND lg2.grid_id NOT IN ( SELECT lg22.admin1_grid_id FROM zume_dt_location_grid lg22 WHERE lg22.level = 2 AND lg22.admin1_grid_id = lg2.grid_id )
              #'China', 'India', 'France', 'Spain', 'Pakistan', 'Bangladesh'
              AND lg2.admin0_grid_id NOT IN (100050711,100219347, 100089589,100074576,100259978,100018514)
              #'Romania', 'Estonia', 'Bhutan', 'Croatia', 'Solomon Islands', 'Guyana', 'Iceland', 'Vanuatu', 'Cape Verde', 'Samoa', 'Faroe Islands', 'Norway', 'Uruguay', 'Mongolia', 'United Arab Emirates', 'Slovenia', 'Bulgaria', 'Honduras', 'Columbia', 'Namibia', 'Switzerland', 'Western Sahara'
              AND lg2.admin0_grid_id NOT IN (100314737,100083318,100041128,100133112,100341242,100132648,100222839,100379914,100055707,100379993,100130389,100255271,100363975,100248845,100001527,100342458,100024289,100132795,100054605,100253456,100342975,100074571)


            UNION ALL
            --
            # admin 2 all countries (37100)
            --
            SELECT
                lg3.grid_id, lg3.population,  lg3.country_code, lg3.longitude, lg3.latitude,
                CONCAT_WS(', ',
                          IF(LENGTH(ga4.alt_name),ga4.alt_name,NULL),
                          IF(LENGTH(ga3.alt_name),ga3.alt_name,NULL),
                          IF(LENGTH(ga2.alt_name),ga2.alt_name,NULL),
                          IF(LENGTH(ga1.alt_name),ga1.alt_name,NULL),
                          IF(LENGTH(gc.alt_name),gc.alt_name,NULL)
                    ) as full_name
            FROM zume_dt_location_grid lg3
                     LEFT JOIN zume_dt_location_grid as gc ON lg3.admin0_grid_id=gc.grid_id
                     LEFT JOIN zume_dt_location_grid as ga1 ON lg3.admin1_grid_id=ga1.grid_id
                     LEFT JOIN zume_dt_location_grid as ga2 ON lg3.admin2_grid_id=ga2.grid_id
                     LEFT JOIN zume_dt_location_grid as ga3 ON lg3.admin3_grid_id=ga3.grid_id
                     LEFT JOIN zume_dt_location_grid as ga4 ON lg3.admin4_grid_id=ga4.grid_id
                     LEFT JOIN zume_dt_location_grid as ga5 ON lg3.admin5_grid_id=ga5.grid_id
            WHERE lg3.level = 2
              #'China', 'India', 'France', 'Spain', 'Pakistan', 'Bangladesh'
              AND lg3.admin0_grid_id NOT IN (100050711,100219347, 100089589,100074576,100259978,100018514)
              #'Romania', 'Estonia', 'Bhutan', 'Croatia', 'Solomon Islands', 'Guyana', 'Iceland', 'Vanuatu', 'Cape Verde', 'Samoa', 'Faroe Islands', 'Norway', 'Uruguay', 'Mongolia', 'United Arab Emirates', 'Slovenia', 'Bulgaria', 'Honduras', 'Columbia', 'Namibia', 'Switzerland', 'Western Sahara'
              AND lg3.admin0_grid_id NOT IN (100314737,100083318,100041128,100133112,100341242,100132648,100222839,100379914,100055707,100379993,100130389,100255271,100363975,100248845,100001527,100342458,100024289,100132795,100054605,100253456,100342975,100074571)

            UNION ALL
            --
            # admin 1 for little highly divided countries (352)
            --
            SELECT
                lg4.grid_id, lg4.population,  lg4.country_code, lg4.longitude, lg4.latitude,
                CONCAT_WS(', ',
                          IF(LENGTH(ga4.alt_name),ga4.alt_name,NULL),
                          IF(LENGTH(ga3.alt_name),ga3.alt_name,NULL),
                          IF(LENGTH(ga2.alt_name),ga2.alt_name,NULL),
                          IF(LENGTH(ga1.alt_name),ga1.alt_name,NULL),
                          IF(LENGTH(gc.alt_name),gc.alt_name,NULL)
                    ) as full_name
            FROM zume_dt_location_grid lg4
                     LEFT JOIN zume_dt_location_grid as gc ON lg4.admin0_grid_id=gc.grid_id
                     LEFT JOIN zume_dt_location_grid as ga1 ON lg4.admin1_grid_id=ga1.grid_id
                     LEFT JOIN zume_dt_location_grid as ga2 ON lg4.admin2_grid_id=ga2.grid_id
                     LEFT JOIN zume_dt_location_grid as ga3 ON lg4.admin3_grid_id=ga3.grid_id
                     LEFT JOIN zume_dt_location_grid as ga4 ON lg4.admin4_grid_id=ga4.grid_id
                     LEFT JOIN zume_dt_location_grid as ga5 ON lg4.admin5_grid_id=ga5.grid_id
            WHERE lg4.level = 1
              #'China', 'India', 'France', 'Spain', 'Pakistan', 'Bangladesh'
              AND lg4.admin0_grid_id NOT IN (100050711,100219347, 100089589,100074576,100259978,100018514)
              #'Romania', 'Estonia', 'Bhutan', 'Croatia', 'Solomon Islands', 'Guyana', 'Iceland', 'Vanuatu', 'Cape Verde', 'Samoa', 'Faroe Islands', 'Norway', 'Uruguay', 'Mongolia', 'United Arab Emirates', 'Slovenia', 'Bulgaria', 'Honduras', 'Columbia', 'Namibia', 'Switzerland', 'Western Sahara'
              AND lg4.admin0_grid_id IN (100314737,100083318,100041128,100133112,100341242,100132648,100222839,100379914,100055707,100379993,100130389,100255271,100363975,100248845,100001527,100342458,100024289,100132795,100054605,100253456,100342975,100074571)

            UNION ALL

            --
            # admin 3 for big countries (6153)
            --
            SELECT
                lg5.grid_id, lg5.population, lg5.country_code, lg5.longitude, lg5.latitude,
                CONCAT_WS(', ',
                          IF(LENGTH(ga4.alt_name),ga4.alt_name,NULL),
                          IF(LENGTH(ga3.alt_name),ga3.alt_name,NULL),
                          IF(LENGTH(ga2.alt_name),ga2.alt_name,NULL),
                          IF(LENGTH(ga1.alt_name),ga1.alt_name,NULL),
                          IF(LENGTH(gc.alt_name),gc.alt_name,NULL)
                    ) as full_name
            FROM zume_dt_location_grid as lg5
                     LEFT JOIN zume_dt_location_grid as gc ON lg5.admin0_grid_id=gc.grid_id
                     LEFT JOIN zume_dt_location_grid as ga1 ON lg5.admin1_grid_id=ga1.grid_id
                     LEFT JOIN zume_dt_location_grid as ga2 ON lg5.admin2_grid_id=ga2.grid_id
                     LEFT JOIN zume_dt_location_grid as ga3 ON lg5.admin3_grid_id=ga3.grid_id
                     LEFT JOIN zume_dt_location_grid as ga4 ON lg5.admin4_grid_id=ga4.grid_id
                     LEFT JOIN zume_dt_location_grid as ga5 ON lg5.admin5_grid_id=ga5.grid_id
            WHERE
                    lg5.level = 3
              #'China', 'India', 'France', 'Spain', 'Pakistan', 'Bangladesh'
              AND lg5.admin0_grid_id IN (100050711,100219347, 100089589,100074576,100259978,100018514)
              #'Romania', 'Estonia', 'Bhutan', 'Croatia', 'Solomon Islands', 'Guyana', 'Iceland', 'Vanuatu', 'Cape Verde', 'Samoa', 'Faroe Islands', 'Norway', 'Uruguay', 'Mongolia', 'United Arab Emirates', 'Slovenia', 'Bulgaria', 'Honduras', 'Columbia', 'Namibia', 'Switzerland', 'Western Sahara'
              AND lg5.admin0_grid_id NOT IN (100314737,100083318,100041128,100133112,100341242,100132648,100222839,100379914,100055707,100379993,100130389,100255271,100363975,100248845,100001527,100342458,100024289,100132795,100054605,100253456,100342975,100074571)
        ";
    }


    public static function world_grid_id_sql(): string {
        return "
            SELECT lg1.grid_id
            FROM zume_dt_location_grid lg1
                     LEFT JOIN zume_dt_location_grid as gc ON lg1.admin0_grid_id=gc.grid_id
                     LEFT JOIN zume_dt_location_grid as ga1 ON lg1.admin1_grid_id=ga1.grid_id
                     LEFT JOIN zume_dt_location_grid as ga2 ON lg1.admin2_grid_id=ga2.grid_id
                     LEFT JOIN zume_dt_location_grid as ga3 ON lg1.admin3_grid_id=ga3.grid_id
                     LEFT JOIN zume_dt_location_grid as ga4 ON lg1.admin4_grid_id=ga4.grid_id
                     LEFT JOIN zume_dt_location_grid as ga5 ON lg1.admin5_grid_id=ga5.grid_id
            WHERE lg1.level = 0
              AND lg1.grid_id NOT IN ( SELECT lg11.admin0_grid_id FROM zume_dt_location_grid lg11 WHERE lg11.level = 1 AND lg11.admin0_grid_id = lg1.grid_id )
              #'China', 'India', 'France', 'Spain', 'Pakistan', 'Bangladesh'
              AND lg1.admin0_grid_id NOT IN (100050711,100219347, 100089589,100074576,100259978,100018514)
              #'Romania', 'Estonia', 'Bhutan', 'Croatia', 'Solomon Islands', 'Guyana', 'Iceland', 'Vanuatu', 'Cape Verde', 'Samoa', 'Faroe Islands', 'Norway', 'Uruguay', 'Mongolia', 'United Arab Emirates', 'Slovenia', 'Bulgaria', 'Honduras', 'Columbia', 'Namibia', 'Switzerland', 'Western Sahara'
              AND lg1.admin0_grid_id NOT IN (100314737,100083318,100041128,100133112,100341242,100132648,100222839,100379914,100055707,100379993,100130389,100255271,100363975,100248845,100001527,100342458,100024289,100132795,100054605,100253456,100342975,100074571)

              # above admin 0 (22)

            UNION ALL
            --
            # admin 1 locations that have no level 2 (768)
            --
            SELECT lg2.grid_id
            FROM zume_dt_location_grid lg2
                     LEFT JOIN zume_dt_location_grid as gc ON lg2.admin0_grid_id=gc.grid_id
                     LEFT JOIN zume_dt_location_grid as ga1 ON lg2.admin1_grid_id=ga1.grid_id
                     LEFT JOIN zume_dt_location_grid as ga2 ON lg2.admin2_grid_id=ga2.grid_id
                     LEFT JOIN zume_dt_location_grid as ga3 ON lg2.admin3_grid_id=ga3.grid_id
                     LEFT JOIN zume_dt_location_grid as ga4 ON lg2.admin4_grid_id=ga4.grid_id
                     LEFT JOIN zume_dt_location_grid as ga5 ON lg2.admin5_grid_id=ga5.grid_id
            WHERE lg2.level = 1
              AND lg2.grid_id NOT IN ( SELECT lg22.admin1_grid_id FROM zume_dt_location_grid lg22 WHERE lg22.level = 2 AND lg22.admin1_grid_id = lg2.grid_id )
              #'China', 'India', 'France', 'Spain', 'Pakistan', 'Bangladesh'
              AND lg2.admin0_grid_id NOT IN (100050711,100219347, 100089589,100074576,100259978,100018514)
              #'Romania', 'Estonia', 'Bhutan', 'Croatia', 'Solomon Islands', 'Guyana', 'Iceland', 'Vanuatu', 'Cape Verde', 'Samoa', 'Faroe Islands', 'Norway', 'Uruguay', 'Mongolia', 'United Arab Emirates', 'Slovenia', 'Bulgaria', 'Honduras', 'Columbia', 'Namibia', 'Switzerland', 'Western Sahara'
              AND lg2.admin0_grid_id NOT IN (100314737,100083318,100041128,100133112,100341242,100132648,100222839,100379914,100055707,100379993,100130389,100255271,100363975,100248845,100001527,100342458,100024289,100132795,100054605,100253456,100342975,100074571)


            UNION ALL
            --
            # admin 2 all countries (37100)
            --
            SELECT lg3.grid_id
            FROM zume_dt_location_grid lg3
                     LEFT JOIN zume_dt_location_grid as gc ON lg3.admin0_grid_id=gc.grid_id
                     LEFT JOIN zume_dt_location_grid as ga1 ON lg3.admin1_grid_id=ga1.grid_id
                     LEFT JOIN zume_dt_location_grid as ga2 ON lg3.admin2_grid_id=ga2.grid_id
                     LEFT JOIN zume_dt_location_grid as ga3 ON lg3.admin3_grid_id=ga3.grid_id
                     LEFT JOIN zume_dt_location_grid as ga4 ON lg3.admin4_grid_id=ga4.grid_id
                     LEFT JOIN zume_dt_location_grid as ga5 ON lg3.admin5_grid_id=ga5.grid_id
            WHERE lg3.level = 2
              #'China', 'India', 'France', 'Spain', 'Pakistan', 'Bangladesh'
              AND lg3.admin0_grid_id NOT IN (100050711,100219347, 100089589,100074576,100259978,100018514)
              #'Romania', 'Estonia', 'Bhutan', 'Croatia', 'Solomon Islands', 'Guyana', 'Iceland', 'Vanuatu', 'Cape Verde', 'Samoa', 'Faroe Islands', 'Norway', 'Uruguay', 'Mongolia', 'United Arab Emirates', 'Slovenia', 'Bulgaria', 'Honduras', 'Columbia', 'Namibia', 'Switzerland', 'Western Sahara'
              AND lg3.admin0_grid_id NOT IN (100314737,100083318,100041128,100133112,100341242,100132648,100222839,100379914,100055707,100379993,100130389,100255271,100363975,100248845,100001527,100342458,100024289,100132795,100054605,100253456,100342975,100074571)

            UNION ALL
            --
            # admin 1 for little highly divided countries (352)
            --
            SELECT lg4.grid_id
            FROM zume_dt_location_grid lg4
                     LEFT JOIN zume_dt_location_grid as gc ON lg4.admin0_grid_id=gc.grid_id
                     LEFT JOIN zume_dt_location_grid as ga1 ON lg4.admin1_grid_id=ga1.grid_id
                     LEFT JOIN zume_dt_location_grid as ga2 ON lg4.admin2_grid_id=ga2.grid_id
                     LEFT JOIN zume_dt_location_grid as ga3 ON lg4.admin3_grid_id=ga3.grid_id
                     LEFT JOIN zume_dt_location_grid as ga4 ON lg4.admin4_grid_id=ga4.grid_id
                     LEFT JOIN zume_dt_location_grid as ga5 ON lg4.admin5_grid_id=ga5.grid_id
            WHERE lg4.level = 1
              #'China', 'India', 'France', 'Spain', 'Pakistan', 'Bangladesh'
              AND lg4.admin0_grid_id NOT IN (100050711,100219347, 100089589,100074576,100259978,100018514)
              #'Romania', 'Estonia', 'Bhutan', 'Croatia', 'Solomon Islands', 'Guyana', 'Iceland', 'Vanuatu', 'Cape Verde', 'Samoa', 'Faroe Islands', 'Norway', 'Uruguay', 'Mongolia', 'United Arab Emirates', 'Slovenia', 'Bulgaria', 'Honduras', 'Columbia', 'Namibia', 'Switzerland', 'Western Sahara'
              AND lg4.admin0_grid_id IN (100314737,100083318,100041128,100133112,100341242,100132648,100222839,100379914,100055707,100379993,100130389,100255271,100363975,100248845,100001527,100342458,100024289,100132795,100054605,100253456,100342975,100074571)

            UNION ALL

            --
            # admin 3 for big countries (6153)
            --
            SELECT lg5.grid_id
            FROM zume_dt_location_grid as lg5
                     LEFT JOIN zume_dt_location_grid as gc ON lg5.admin0_grid_id=gc.grid_id
                     LEFT JOIN zume_dt_location_grid as ga1 ON lg5.admin1_grid_id=ga1.grid_id
                     LEFT JOIN zume_dt_location_grid as ga2 ON lg5.admin2_grid_id=ga2.grid_id
                     LEFT JOIN zume_dt_location_grid as ga3 ON lg5.admin3_grid_id=ga3.grid_id
                     LEFT JOIN zume_dt_location_grid as ga4 ON lg5.admin4_grid_id=ga4.grid_id
                     LEFT JOIN zume_dt_location_grid as ga5 ON lg5.admin5_grid_id=ga5.grid_id
            WHERE
                    lg5.level = 3
              #'China', 'India', 'France', 'Spain', 'Pakistan', 'Bangladesh'
              AND lg5.admin0_grid_id IN (100050711,100219347, 100089589,100074576,100259978,100018514)
              #'Romania', 'Estonia', 'Bhutan', 'Croatia', 'Solomon Islands', 'Guyana', 'Iceland', 'Vanuatu', 'Cape Verde', 'Samoa', 'Faroe Islands', 'Norway', 'Uruguay', 'Mongolia', 'United Arab Emirates', 'Slovenia', 'Bulgaria', 'Honduras', 'Columbia', 'Namibia', 'Switzerland', 'Western Sahara'
              AND lg5.admin0_grid_id NOT IN (100314737,100083318,100041128,100133112,100341242,100132648,100222839,100379914,100055707,100379993,100130389,100255271,100363975,100248845,100001527,100342458,100024289,100132795,100054605,100253456,100342975,100074571)";
    }

    public static function has_coach( $stage, $range = -1, $trend = false, $negative = false ) {
        global $wpdb;
        $query_for_user_stage = self::$query_for_user_stage;

        if ( $negative ) {
            $where = "AND pm.meta_value = ''";
        } else {
            $where = "AND pm.meta_value != ''";
        }

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
            JOIN zume_postmeta pm ON pm.post_id=tb.post_id AND pm.meta_key = 'coaching_contact_id' $where
            WHERE tb.stage = $stage
                AND tb.timestamp > $begin
                AND tb.timestamp < $end;
            ";
        $count = $wpdb->get_var( $sql );

        return $count;
    }

    public static function has_coach_list( $stage, $range = -1, $trend = false, $negative = false ) {
        global $wpdb;
        $query_for_user_stage = self::$query_for_user_stage;

        if ( $negative ) {
            $where = "AND pm.meta_value = ''";
        } else {
            $where = "AND pm.meta_value != ''";
        }

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
            SELECT tb.user_id, tb.post_id, pm.meta_value as coaching_contact_id, p.post_title as name, pm1.meta_value as user_email, pm2.meta_value as user_phone
            FROM
               (
                  $query_for_user_stage
                ) as tb
            JOIN zume_posts p ON p.ID=tb.post_id
            JOIN zume_postmeta pm ON pm.post_id=tb.post_id AND pm.meta_key = 'coaching_contact_id' $where
			LEFT JOIN zume_postmeta pm1 ON pm1.post_id=tb.post_id AND pm1.meta_key = 'user_email'
			LEFT JOIN zume_postmeta pm2 ON pm2.post_id=tb.post_id AND pm2.meta_key = 'user_phone'
            WHERE tb.stage = $stage
                AND tb.timestamp > $begin
                AND tb.timestamp < $end;
            ";

//        dt_write_log($sql);
        $list = $wpdb->get_results( $sql, ARRAY_A );

        if ( empty( $list ) ) {
            return [];
        }

        return $list;
    }

    public static function checkins( $stage, $range, $trend = false, $negative = false ) {
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
            JOIN zume_dt_reports r ON r.user_id=tb.user_id AND r.type = 'training' AND r.subtype LIKE 'set_%'
            WHERE tb.stage = $stage
              AND tb.timestamp > $begin
              AND tb.timestamp < $end
            ";
        $count = $wpdb->get_var( $sql );

        if ( $negative ) {
            $stage_total = self::stage_total( $stage, $range, $trend );
            $stage_total = (int) $stage_total;
            $count = (int) $count;
            $count =  $stage_total - $count;
            if ( $count < 1 ) {
                $count = $count * -1;
            }
        }

        return (float) $count;
    }

    public static function locations( $stages = [ 1 ], $range = -1, $trend = false, $negative = false ) {
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
            SELECT COUNT( DISTINCT( r.grid_id ) ) as count
            FROM zume_dt_reports r
            JOIN
            (
                $world_grid_ids
            ) as grid_ids ON r.grid_id=grid_ids.grid_id
            WHERE
                r.value IN ( $stages_list )
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

        $stages_list = dt_array_to_sql( $stages );

        $sql = "
            SELECT language_code, count(*)
            FROM zume_dt_reports r
            WHERE r.value IN ( $stages_list )
              AND r.timestamp > $begin
              AND r.timestamp < $end
            AND r.language_code != ''
            GROUP BY language_code
            ";
        $list = $wpdb->get_results( $sql, ARRAY_A );

        if ( empty( $list ) ) {
            return 0;
        }

        $count = count($list);

        if ( $negative && $count ) {
            $count = 45 - (int) $count;
        }

        if ( $count < 1 ) {
            return 0;
        }

        return (float) $count;
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

    public static function query_stage_by_type_and_subtype( $stage, $range, $type, $subtype, $trend = false, $negative = false ) {
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
            JOIN zume_dt_reports r ON r.user_id=tb.user_id AND r.type = '$type' AND r.subtype LIKE '$subtype'
            WHERE tb.stage = $stage
               AND tb.timestamp > $begin
              AND tb.timestamp < $end
            ";
//        dt_write_log($sql);
        $count = $wpdb->get_var( $sql );

        if ( $count < 1 ) {
            return 0;
        }

        if ( $negative ) {
            $stage_total = self::stage_total( $stage, $range, $trend );
            $stage_total = (int) $stage_total;
            $count = (int) $count;
            $count =  $stage_total - $count;
            if ( $count < 1 ) {
                $count = $count * -1;
            }
        }

        return (float) $count;
    }

    public static function query_stage_by_type_and_subtype_list( $stage, $range, $type, $subtype, $trend = false, $negative = false ) {
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
            SELECT DISTINCT tb.user_id, tb.post_id, pm.meta_value as coaching_contact_id, p.post_title as name, pm1.meta_value as user_email, pm2.meta_value as user_phone, IF( r.id, true, false ) as logged
            FROM
               (
                  $query_for_user_stage
                ) as tb
            LEFT JOIN zume_dt_reports r ON r.user_id=tb.user_id AND r.type = '$type' AND r.subtype LIKE '$subtype'
			JOIN zume_posts p ON p.ID=tb.post_id
			LEFT JOIN zume_postmeta pm ON pm.post_id=tb.post_id AND pm.meta_key = 'coaching_contact_id'
			LEFT JOIN zume_postmeta pm1 ON pm1.post_id=tb.post_id AND pm1.meta_key = 'user_email'
			LEFT JOIN zume_postmeta pm2 ON pm2.post_id=tb.post_id AND pm2.meta_key = 'user_phone'
            WHERE tb.stage = $stage
               AND tb.timestamp > $begin
              AND tb.timestamp < $end
            ORDER BY p.post_title
            ";
//        dt_write_log($sql);
        $list = $wpdb->get_results( $sql, ARRAY_A );

        if ( empty( $list ) ) {
            return [];
        }

        $data_list = [
            'negative' => [],
            'positive' => [],
        ];
        foreach( $list as $row ) {
            if ( $row['logged'] ) {
                $data_list['positive'][] = $row;
            } else {
                $data_list['negative'][] = $row;
            }
        }

        if ( $negative ) {
            return $data_list['negative'];
        } else {
            return $data_list['positive'];
        }
    }

    public static function registrations( $range = -1, $trend = false ) {
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

        if ( $count < 1 ) {
            return 0;
        }

        return (float) $count;
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
