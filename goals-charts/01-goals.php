<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.


class Zume_Goals_Goals extends Zume_Goals_Chart_Base
{
    //slug and title of the top menu folder
    public $base_slug = ''; // lowercase
    public $slug = ''; // lowercase
    public $title;
    public $base_title;
    public $js_object_name = 'wp_js_object'; // This object will be loaded into the metrics.js file by the wp_localize_script.
    public $js_file_name = '/dt-metrics/groups/overview.js'; // should be full file name plus extension
    public $permissions = [ 'access_contacts' ];

    public function __construct() {
        parent::__construct();
        if ( !$this->has_permission() ){
            return;
        }
        $this->base_title = __( 'Top Goals', 'zume_goals' );

        $url_path = dt_get_url_path( true );
        if ( 'zume-goals' === $url_path ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'base_scripts' ], 99 );
            add_action( 'wp_head', [ $this, 'wp_head' ], 1000 );
        }
    }

    public function base_menu( $content ) {
        $content .= '<li>GOALS</li>';
        $content .= '<li><a href="'.site_url( '/zume-goals/'.$this->base_slug ).'" id="'.$this->base_slug.'-menu">' .  $this->base_title . '</a></li>';
        return $content;
    }

    public function wp_head() {
        $this->styles();
        $this->js_api();
        ?>
        <script>
            jQuery(document).ready(function(){
                "use strict";

                let chart = jQuery('#chart')
                chart.empty().html(`
                        <div id="zume-goals">
                            <div class="grid-x">
                                <div class="cell small-6"><h1>Zúme Top Goals</h1></div>
                                <div class="cell small-6">

                                </div>
                            </div>
                            <hr>
                            <div class="grid-x grid-padding-x">
                                <div class="cell medium-3">
                                    <h2>Vision</h2>
                                    <p><strong>Zúme's vision</strong> is to saturation the world with multiplying disciples in our generation. As a measurement, our goal is to catalyze 1 trained practitioner and 2 multiplying simple churches for every 5,000 people in the USA and 50,000 people globally</p>
                                    <p><strong>Zúme Training</strong> is an on-line and in-life learning experience designed for small groups who follow Jesus to learn how to obey His Great Commission and make disciples who multiply.</p>
                                    <p><strong>Zúme Community</strong> is a community of practice for those who what to see disciple making movements.</p>
                                    <h2>Top Metrics</h2>
                                    <p>These metrics ( practitioners and churches) represent the highest level milestones for accomplishing Zúme's vision. </p>
                                </div>
                                <div class="cell medium-9">
                                     <div class="grid-x zume-goals">
                                        <div class="cell"><div class="practitioners_total zume-goals"><span class="loading-spinner active"></span></div></div>
                                        <div class="cell"><div class="churches_total zume-goals"><span class="loading-spinner active"></span></div></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `)

                window.path_load = ( range ) => {

                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "practitioners", key: "practitioners_total", range: range }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_hero_map_only(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "churches", key: "churches_total", range: range }, window.site_info.rest_root ).done( function( data ) {
                        data.label = 'Churches'
                        data.link = 'heatmap_churches'
                        data.valence = 'valence-grey'
                        data.description = 'These are the total number of churches reported by all the practitioners of all stages in the Zúme journey.'
                        jQuery('.'+data.key).html(window.template_hero_map_only(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })

                }
                window.setup_filter()

                window.click_listener = ( data ) => {
                    window.load_list(data)
                    window.load_map(data)
                }

                jQuery('.loading-spinner').removeClass('active')
            })

        </script>
        <?php
    }

    public function styles() {
        /* required for side menu*/
        ?>
        <style>
            .side-menu-item-highlight {
                font-weight: 300;
            }
            #-menu {
                font-weight: 700;
            }
            .zume-cards {
                max-width: 700px;
            }
        </style>
        <?php
    }
}
new Zume_Goals_Goals();
