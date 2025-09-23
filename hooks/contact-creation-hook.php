<?php

add_action( 'dt_post_created', function ( $post_type, $post_id, $fields, $args ) {
    if ( 'contacts' === $post_type ) {
        if ( !isset( $fields['overall_status'] ) ) {
            DT_Posts::update_post( $post_type, $post_id, [ 'overall_status' => 'new' ] );
        }
    }
}, 10, 4 );

add_action( 'dt_post_updated', function ( $post_type, $post_id, $fields, $args ) {
    // dt_write_log('fields in update');
    // dt_write_log($fields);
    if ( 'contacts' === $post_type ) {
        if ( isset( $fields['contact_email'] ) ) {
            global $wpdb;
            if (isset($fields['contact_email'][0]['value'])) {
                $email = $fields['contact_email'][0]['value'];
            } else if (isset($fields['contact_email'][1]['value'])) {
                $email = $fields['contact_email'][1]['value'];
            } else if (isset($fields['contact_email']['values'][0])) {
                $email = $fields['contact_email']['values'][0];
            } else {
                return;
            }
            $this_post = DT_Posts::get_post( $post_type, $post_id );
            if ( !isset( $this_post['trainee_user_id'] ) ) {
                return;
            }
            $wpdb->update( 'zume_postmeta', [ 'meta_value' => $email ], [ 'post_id' => $this_post['trainee_contact_id'], 'meta_key' => 'user_communications_email' ] );
        }
    }
    if ( 'contacts' === $post_type ) {
        if ( isset( $fields['contact_phone'] ) ) {
            global $wpdb;

            if (isset($fields['contact_phone'][0]['value'])) {
                $phone = $fields['contact_phone'][0]['value'];
            } else if (isset($fields['contact_phone'][1]['value'])) {
                $phone = $fields['contact_phone'][1]['value'];
            } else if (isset($fields['contact_phone']['values'][0] )) {
                $phone = $fields['contact_phone']['values'][0];
            } else {
                return;
            }
            
            $this_post = DT_Posts::get_post( $post_type, $post_id );
            if ( !isset( $this_post['trainee_user_id'] ) ) {
                return;
            }
            $wpdb->update( 'zume_postmeta', [ 'meta_value' =>  $phone ], [ 'post_id' => $this_post['trainee_contact_id'], 'meta_key' => 'user_phone' ] );
        }
    }
}, 10, 4 );




function zume_email_quality_check( $this_post, $profile ) { 
    // checks if email is valid
    if ( isset( $this_post['contact_email'][0]['value'] ) && $profile['communications_email'] !== $this_post['contact_email'][0]['value'] ) { // no match
        // dt_write_log('no email match');
        // update email
        $fields = [
            'contact_email' => [
                'values' => [
                    [ "key" => $this_post['contact_email'][0]['key'], "delete" => true],
                    [ 'value' => $profile['communications_email'] ]
                ],
                "force_values" => true
            ]
        ];
        DT_Posts::update_post( 'contacts', $this_post['ID'], $fields, false, false );
    }
    else if( ! isset( $this_post['contact_email'][0]['value'] ) ) { // not present
        // dt_write_log('no email');
        // add email
        $fields = [
            'contact_email' => [
                'values' => [
                    [ 'value' => $profile['communications_email'] ]
                ],
                "force_values" => true
            ]
        ];
        DT_Posts::update_post( 'contacts', $this_post['ID'], $fields, false, false );
    }
}

function zume_phone_quality_check(  $this_post, $profile ) { // checks if phone is valid
    if ( isset( $this_post['contact_phone'][0]['value'] ) && ! empty( $profile['phone'] ) && $profile['phone'] !== $this_post['contact_phone'][0]['value'] ) { // no match
        // dt_write_log('no phone match');
        // update phone
        $fields = [
            'contact_phone' => [
                'values' => [
                    ["key" => $this_post['contact_phone'][0]['key'], "delete" => true],
                    [ 'value' => $profile['phone'] ]
                ],
                "force_values" => true
            ]
        ];
        DT_Posts::update_post( 'contacts', $this_post['ID'], $fields, false, false );
    }
    else if( ! isset( $this_post['contact_phone'][0]['value'] ) && ! empty( $profile['phone'] ) ) { // not present
        // dt_write_log('no phone');
        // add phone
        $fields = [
            'contact_phone' => [
                'values' => [
                    [ 'value' => $profile['phone'] ]
                ],
                "force_values" => true
            ]
        ];
        DT_Posts::update_post( 'contacts', $this_post['ID'], $fields, false, false );
    }
}