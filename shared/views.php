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
}
