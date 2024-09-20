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
