<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Funnel_Anonymous extends Zume_Funnel_Chart_Base
{
    //slug and title of the top menu folder
    public $base_slug = 'anonymous'; // lowercase
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
        $this->base_title = __( 'Anonymous', 'zume_funnels' );

        $url_path = dt_get_url_path( true );
        if ( "zume-funnel/$this->base_slug" === $url_path ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'base_scripts' ], 99 );
            add_action( 'wp_head',[ $this, 'wp_head' ], 1000);
        }
    }

    public function base_menu( $content ) {
        $content .= '<li><hr></li>';
        $content .= '<li>FUNNEL</li>';
        $content .= '<li><a href="'.site_url('/zume-funnel/'.$this->base_slug).'" id="'.$this->base_slug.'-menu">' .  $this->base_title . '</a></li>';
        return $content;
    }

    public function wp_head() {
        $this->js_api();
        ?>
        <script>
            jQuery(document).ready(function(){
                "use strict";

                let chart = jQuery('#chart')
                chart.empty().html(`
                        <div id="zume-funnel">
                            <div class="grid-x">
                                <div class="cell small-6"><h1>Anonymous Candidates</h1></div>
                                <div class="cell small-6 right">Open to: group training, a change in obedience, & multiplicative practices</div>
                            </div>
                            <hr>
                            <div class="grid-x">
                                <div class="cell">
                                    <h2>Cumulative</h2>
                                </div>
                            </div>
                            <div class="grid-x">
                                <div class="cell medium-3 hero"><span class="loading-spinner active"></span></div>
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
                                 <div class="cell medium-12 registrations"><span class="loading-spinner active"></span></div>
                            </div>
                            <div class="grid-x grid-margin-x grid-margin-y">
                                 <div class="cell medium-6 coach_requests"><span class="loading-spinner active"></span></div>
                                 <div class="cell medium-6 joined_online_training"><span class="loading-spinner active"></span></div>
                            </div>
                            <div class="grid-x grid-margin-x grid-margin-y">
                                 <div class="cell medium-6 visitors"><span class="loading-spinner active"></span></div>
                            </div>
                        </div>
                    `)


                window.spin_add()
                makeRequest('GET', 'total', { stage: "anonymous", key: "total_registrations" }, window.site_info.rest_root ).done( function( data ) {
                    jQuery('.hero').html( window.template_single( data ) )
                    window.click_listener( data )
                    window.spin_remove()
                })

                window.path_load = ( range ) => {

                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "anonymous", key: "registrations", range: range }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.registrations').html( window.template_trio( data ) )
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "anonymous", key: "coach_requests", range: range }, window.site_info.rest_root ).done( function( data ) {
                        data.valence = "valence-grey"
                        jQuery('.coach_requests').html( window.template_single( data ) )
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "anonymous", key: "joined_online_training", range: range }, window.site_info.rest_root ).done( function( data ) {
                        data.valence = "valence-grey"
                        jQuery('.joined_online_training').html( window.template_single( data ) )
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "anonymous", key: "visitors", range: range }, window.site_info.rest_root ).done( function( data ) {
                        data.valence = "valence-grey"
                        jQuery('.visitors').html( window.template_single( data ) )
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

    public function data() {
        return [
            'translations' => [
                'title_overview' => __( 'Project Overview', 'zume_funnels' ),
            ],
        ];
    }
}
new Zume_Funnel_Anonymous();
