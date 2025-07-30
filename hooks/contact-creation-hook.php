<?php

add_action( 'dt_post_created', function ( $post_type, $post_id, $fields, $args ) {
    if ( 'contacts' === $post_type ) {
        if ( !isset( $fields['overall_status'] ) ) {
            DT_Posts::update_post( $post_type, $post_id, [ 'overall_status' => 'new' ] );
        }
    }
}, 10, 4 );
