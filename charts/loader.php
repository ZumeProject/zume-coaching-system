<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Coaching_Metrics_Base {

    public $base_slug = 'zume-coaching';
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
        add_filter( 'off_canvas_menu_options', [ $this, 'add_navigation_links' ], 35);

        $url_path = dt_get_url_path(true);

        // top
        if ( str_contains( $url_path, $this->base_slug ) !== false ) {
            add_filter('dt_templates_for_urls', [$this, 'dt_templates_for_urls']);
            add_filter('dt_metrics_menu', [$this, 'dt_metrics_menu'], 99);

            require_once ('abstract.php');

            require_once ('01-concepts.php');

            require_once ('100-simulate-reports.php');
            require_once ('102-simulate-journey.php');

        }
    }

    public function add_navigation_links( $tabs ) {
        if ( current_user_can( 'access_zume_coaching' ) ) {
            $tabs[] = [
                "link" => site_url( "/zume-coaching/" ), // the link where the user will be directed when they click
                "label" => __( "Simulator", "zume_coaching" )  // the label the user will see
            ];
        }
        return $tabs;
    }

    public function dt_metrics_menu( $content ) {
        return $content;
    }

    public function dt_templates_for_urls( $template_for_url ) {
        $template_for_url['zume-coaching'] = 'template-metrics.php';
        return $template_for_url;
    }
}
Zume_Coaching_Metrics_Base::instance();
