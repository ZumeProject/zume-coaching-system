<?php

add_filter( 'dt_nav', function ( $menu ) {
    if ( isset( $menu['admin']['site']['icon'] ) ) {
        $menu['admin']['site']['icon'] = plugin_dir_url( __FILE__ ) . 'coaching-logo.png';
    }
    //    if ( isset( $menu['main']['metrics'] ) ) {
    //        unset( $menu['main']['metrics'] );
    //    }

    if ( isset( $menu['main']['groups'] ) ) {
        unset( $menu['main']['groups'] );
    }

    // Hide "New Contact" and "New Group" links from the menu
    if ( isset( $menu['admin']['add_new']['submenu'] ) ) {
        // Hide New Contact (index 0)
        if ( isset( $menu['admin']['add_new']['submenu'][0] ) ) {
            $menu['admin']['add_new']['submenu'][0]['hidden'] = 1;
        }
        
        // Hide New Group (index 1)
        if ( isset( $menu['admin']['add_new']['submenu'][1] ) ) {
            $menu['admin']['add_new']['submenu'][1]['hidden'] = 1;
        }
        
        // Add New Trainee submenu item
        $new_trainee = array(
            'label' => 'New Trainee',
            'link' => 'https://zume.training/add/trainee',
            'icon' => 'https://zume.training/coaching/wp-content/themes/disciple-tools-theme/dt-assets/images/circle-add-green.svg',
            'hidden' => ''
        );
        
        // Add the new trainee item to the submenu
        $menu['admin']['add_new']['submenu'][] = $new_trainee;
    }

    return $menu;
}, 1, 99 );


add_filter( 'dt_multisite_dropdown_sites', function( $sites ) {

    $new_site_list = [];
    if ( isset( $sites[1] ) ) {
        $dashboard = new stdClass();
        $dashboard->blogname = 'Training';
        $dashboard->siteurl = 'https://zume.training/dashboard/';
        $new_site_list[] = $dashboard;
    }
    if ( isset( $sites[3] ) ) {
        $coaching = new stdClass();
        $coaching->blogname = 'Coaching';
        $coaching->siteurl = 'https://zume.training/coaching/';
        $new_site_list[] = $coaching;
    }
    if ( isset( $sites[12] ) ) {
        $vision = new stdClass();
        $vision->blogname = 'Vision';
        $vision->siteurl = 'https://zume.vision/';
        $new_site_list[] = $vision;
    }

    if ( user_can( get_current_user_id(), 'manage_options' ) ) {

        $boundary = new stdClass();
        $boundary->blogname = '______________';
        $boundary->siteurl = '#';
        $new_site_list[] = $boundary;

        $network_admin = new stdClass();
        $network_admin->blogname = 'Network Admin';
        $network_admin->siteurl = 'https://zume.training/wp-admin/network/';
        $new_site_list[] = $network_admin;

        $training_admin_contacts = new stdClass();
        $training_admin_contacts->blogname = 'Contacts';
        $training_admin_contacts->siteurl = 'https://zume.training/contacts/';
        $new_site_list[] = $training_admin_contacts;

        $training_admin_plans = new stdClass();
        $training_admin_plans->blogname = 'Plans';
        $training_admin_plans->siteurl = 'https://zume.training/zume_plans/';
        $new_site_list[] = $training_admin_plans;

    }

    return $new_site_list;
}, 1, 10 );
