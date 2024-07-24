<?php

add_filter( 'dt_nav', function ( $menu ) {
    if ( isset( $menu['admin']['site']['icon'] ) ) {
        $menu['admin']['site']['icon'] = plugin_dir_url( __FILE__ ) . 'coaching-logo.png';
    }
    if ( isset( $menu['main']['metrics'] ) ) {
        unset( $menu['main']['metrics'] );
    }

    if ( isset( $menu['main']['groups'] ) ) {
        unset( $menu['main']['groups'] );
    }
    return $menu;
}, 1, 99 );
