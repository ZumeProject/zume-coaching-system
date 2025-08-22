<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Funnel_Base {

    public $base_slug = 'zume-funnel';
    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {

        add_filter( 'desktop_navbar_menu_options', [ $this, 'add_navigation_links' ], 35 );
        add_filter( 'off_canvas_menu_options', [ $this, 'add_navigation_links' ], 35 );

        // top
        $url_path = dt_get_url_path( true );
        if ( str_contains( $url_path, $this->base_slug ) !== false ) {
            add_filter( 'dt_templates_for_urls', [ $this, 'dt_templates_for_urls' ] );
            add_filter( 'dt_metrics_menu', [ $this, 'dt_metrics_menu' ], 99 );

            require_once( 'abstract.php' );

            // overview
            require_once( '01-trainee-overview.php' );
            require_once( '02-in-and-out.php' );
            require_once( '03-location-funnel.php' );

            // funnel
//            require_once( '11-anonymous.php' );
            require_once( '12-registrants.php' );
            require_once( '13-active-training.php' );
            require_once( '14-post-training.php' );
            require_once( '15-partial-practitioner.php' );
            require_once( '16-full-practitioner.php' );
            require_once( '17-multiplying-practitioner.php' );

            require_once( '19-time-range.php' );
            require_once( '20-time-range-checkins.php' );
            require_once( '21-cumulative.php' );
            require_once( '22-cumulative-checkins.php' );

            // coaching
            if ( user_can( get_current_user_id(), 'manage_options' ) ) :
//                require_once( '30-overview.php' );
//                require_once( '31-facilitator.php' );
//                require_once( '32-early.php' );
//                require_once( '33-advanced.php' );
            endif;

            require_once( '50-concepts.php' );

            require_once( '101-set-goals.php' );

        }
    }

    public function add_navigation_links( $tabs ) {
        if ( current_user_can( 'access_contacts' ) ) {
            $tabs[] = [
                'link' => site_url( '/zume-funnel/' ), // the link where the user will be directed when they click
                'label' => __( 'Funnel', 'zume_funnels' ),  // the label the user will see
            ];
        }
        return $tabs;
    }

    public function dt_metrics_menu( $content ) {
        return $content;
    }

    public function dt_templates_for_urls( $template_for_url ) {
        $template_for_url['zume-funnel'] = 'template-metrics.php';
        return $template_for_url;
    }
}
Zume_Funnel_Base::instance();
