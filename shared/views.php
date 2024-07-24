<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Views {

    public static function training_elements( $params ) {
        $items = zume_training_items();

        $results = Zume_Queries::training_subtype_counts();

        foreach ( $results as $index => $value ) {
            $results[$index]['value'] = (int) $value['value'];
            $key = intval( substr( $value['subtype'], 0, 2 ) );
            $results[$index]['label'] = $items[$key]['title'] ?? $value['subtype'];
        }

        return $results;
    }

    public static function stage_totals( $stage = null ) {
        $results = Zume_Queries::stage_totals();

        $totals = [
            '1' => 0,
            '2' => 0,
            '3' => 0,
            '4' => 0,
            '5' => 0,
            '6' => 0,
        ];

        foreach ( $results as $result ) {
            $totals[$result['stage']] = (int) $result['total'];
        }

        if ( is_null( $stage ) ) {
            return $totals;
        }

        if ( 1 == $stage ) {
            return (int) $totals['1'] ?? 0;
        } else if ( 2 == $stage ) {
            return (int) $totals['2'] ?? 0;
        } else if ( 3 == $stage ) {
            return (int) $totals['3'] ?? 0;
        } else if ( 4 == $stage ) {
            return (int) $totals['4'] ?? 0;
        } else if ( 5 == $stage ) {
            return (int) $totals['5'] ?? 0;
        } else if ( 6 == $stage ) {
            return (int) $totals['6'] ?? 0;
        }

        return $totals;
    }


    // @remove after development
//    public static function sample( $params ) {
//        $negative_stat = false;
//        if ( isset( $params['negative_stat'] ) && $params['negative_stat'] ) {
//            $negative_stat = $params['negative_stat'];
//        }
//
//        $value = rand(100, 1000);
//        $goal = rand(500, 700);
//        $trend = rand(500, 700);
//        return [
//            'key' => 'sample',
//            'label' => 'Sample',
//            'link' => 'sample',
//            'description' => 'Sample description.',
//            'value' => self::format_int( $value ),
//            'valence' => self::get_valence( $value, $goal, $negative_stat ),
//            'goal' => $goal,
//            'goal_valence' => self::get_valence( $value, $goal, $negative_stat ),
//            'goal_percent' => self::get_percent( $value, $goal ),
//            'trend' => $trend,
//            'trend_valence' => self::get_valence( $value, $trend, $negative_stat ),
//            'trend_percent' => self::get_percent( $value, $trend ),
//            'negative_stat' => $negative_stat,
//        ];
//    }
}
