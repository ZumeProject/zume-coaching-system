<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.


class Zume_Funnel_Coaching_Stages extends Zume_Funnel_Chart_Base
{
    //slug and title of the top menu folder
    public $base_slug = 'coaching_engagement'; // lowercase
    public $slug = ''; // lowercase
    public $title;
    public $base_title;
    public $js_object_name = 'wp_js_object'; // This object will be loaded into the metrics.js file by the wp_localize_script.
    public $js_file_name = '/dt-metrics/groups/overview.js'; // should be full file name plus extension
    public $permissions = [ 'dt_all_access_contacts', 'view_project_metrics' ];

    public function __construct() {
        parent::__construct();
        if ( !$this->has_permission() ){
            return;
        }
        $this->base_title = __( 'Overview', 'zume_funnels' );

        $url_path = dt_get_url_path( true );
        if ( "zume-funnel/$this->base_slug" === $url_path ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'base_scripts' ], 99 );
            add_action( 'wp_head',[ $this, 'wp_head' ], 1000);
        }
    }

    public function base_menu( $content ) {
        $content .= '<li class=""><hr></li>';
        $content .= '<li class="">COACHING</li>';
        $content .= '<li class=""><a href="'.site_url('/zume-funnel/'.$this->base_slug).'" id="'.$this->base_slug.'-menu">' .  $this->base_title . '</a></li>';
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
                        <div id="zume-funnel">
                            <div class="grid-x">
                                <div class="cell small-6"><h1>Coaching Overview</h1></div>
                                <div class="cell small-6 right">Overview of the coaching engagement</div>
                            </div>
                            <hr>
                            <div class="grid-x">
                                <div class="cell small-12">
                                    <h2>Cumulative</h2>
                                </div>
                            </div>
                            <div class="grid-x grid-margin-x grid-margin-y">
                                 <div class="cell medium-6 coaches"><span class="loading-spinner active"></span></div>
                                 <div class="cell medium-6 total_people_in_coaching"><span class="loading-spinner active"></span></div>
                            </div>
                            <hr>
                            <div class="grid-x">
                                <div class="cell center"><h1 id="range-title">Last 30 Days</h1></div>
                                <div class="cell small-6">
                                    <h2>Time Range</h2>
                                </div>
                                <div class="cell small-6">
                                    <span style="float: right;">
                                        <select id="range-filter">
                                            <option value="30">Last 30 days</option>
                                            <option value="7">Last 7 days</option>
                                            <option value="90">Last 90 days</option>
                                            <option value="365">Last 1 Year</option>
                                        </select>
                                    </span>
                                    <span class="loading-spinner active" style="float: right; margin:0 10px;"></span>
                                </div>
                            </div>
                            <div class="grid-x grid-margin-x grid-margin-y">
                                 <div class="cell medium-6 active_coaches"><span class="loading-spinner active"></span></div>
                                 <div class="cell medium-6 new_coaching_requests"><span class="loading-spinner active"></span></div>
                                 <div class="cell medium-6 coaching_engagements"><span class="loading-spinner active"></span></div>
                                 <div class="cell medium-6 people_in_coaching"><span class="loading-spinner active"></span></div>
                            </div>
                        </div>
                    `)

                // totals
                window.spin_add()
                makeRequest('GET', 'total', { stage: "general", key: "coaches" }, window.site_info.rest_root ).done( function( data ) {
                    data.label = "Coaches"
                    jQuery('.'+data.key).html(window.template_single_map(data))
                    window.click_listener( data )
                    window.spin_remove()
                })

                window.spin_add()
                makeRequest('GET', 'total', { stage: "general", key: "total_people_in_coaching" }, window.site_info.rest_root ).done( function( data ) {
                    jQuery('.total_people_in_coaching').html(window.template_single_map(data))
                    window.click_listener( data )
                    window.spin_remove()
                })

                window.path_load = ( range ) => {

                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "general", key: "active_coaches", range: range }, window.site_info.rest_root ).done( function( data ) {
                        data.label = "Active Coaches"
                        jQuery('.'+data.key).html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "general", key: "new_coaching_requests", range: range }, window.site_info.rest_root ).done( function( data ) {
                        data.label = "New Coaching Requests"
                        jQuery('.new_coaching_requests').html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "general", key: "coaching_engagements", range: range }, window.site_info.rest_root ).done( function( data ) {
                        data.label = "Coach Engagements"
                        jQuery('.coaching_engagements').html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "general", key: "people_in_coaching", range: range }, window.site_info.rest_root ).done( function( data ) {
                        data.label = "People in Coaching"
                        jQuery('.people_in_coaching').html(window.template_single_map(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })

                }
                window.setup_filter()

                window.click_listener = ( data ) => {
                    window.load_list(data)
                    window.load_map(data)
                }
            })
        </script>
        <?php
    }

    public function styles() {
        ?>
        <style>
            .zume-cards {
                max-width: 700px;
            }
        </style>
        <?php
    }

}
new Zume_Funnel_Coaching_Stages();
