<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Funnel_Post extends Zume_Funnel_Chart_Base
{
    //slug and title of the top menu folder
    public $base_slug = 'post'; // lowercase
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
        $this->base_title = __( 'Post-Training', 'zume_funnels' );

        $url_path = dt_get_url_path( true );
        if ( "zume-funnel/$this->base_slug" === $url_path ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'base_scripts' ], 99 );
            add_action( 'wp_head', [ $this, 'wp_head' ], 1000 );
        }
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
                                <div class="cell small-6"><h1>Post Training</h1></div>
                                <div class="cell small-6 right">Training completed. Working Post Training Plan. Adopting lifestyle.</div>
                            </div>
                            <hr>
                             <div class="grid-x">
                                <div class="cell">
                                    <h2>Cumulative</h2>
                                </div>
                            </div>
                             <div class="grid-x">
                                <div class="cell total_post_training_trainee"><span class="loading-spinner active"></span></div>
                            </div>
                            <div class="grid-x grid-margin-x grid-margin-y">
                                 <div class="cell medium-6 no_coach"><span class="loading-spinner active"></span></div>
                                 <div class="cell medium-6 no_updated_profiles"><span class="loading-spinner active"></span></div>
                                 <div class="cell medium-6 needs_3_month_plan"><span class="loading-spinner active"></span></div>
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
                                 <div class="cell medium-12 in_and_out"><span class="loading-spinner active"></span></div>
                            </div>
                            <div class="grid-x grid-margin-x grid-margin-y">
                                 <div class="cell medium-6 coach_requests"><span class="loading-spinner active"></span></div>
                                 <div class="cell medium-6 set_profile"><span class="loading-spinner active"></span></div>
                                 <div class="cell medium-6 completed_3_month_plans"><span class="loading-spinner active"></span></div>
                            </div>
                        </div>
                    `)

                // totals
                window.spin_add()
                makeRequest('GET', 'total', { stage: "post_training_trainee", key: "total_post_training_trainee"}, window.site_info.rest_root ).done( function( data ) {
                    data.link = ''
                    data.label = 'Post-Training Trainees'
                    jQuery('.'+data.key).html(window.template_hero_map_only(data))
                    window.click_listener( data )
                    window.spin_remove()
                })
                window.spin_add()
                makeRequest('GET', 'total', { stage: "post_training_trainee", key: "no_coach" }, window.site_info.rest_root ).done( function( data ) {
                    data.valence = 'valence-grey'
                    data.label = 'Has No Coach'
                    data.description = 'Description'
                    jQuery('.'+data.key).html(window.template_single_list(data))
                    window.click_listener( data )
                    window.spin_remove()
                })
                window.spin_add()
                makeRequest('GET', 'total', { stage: "post_training_trainee", key: "no_updated_profiles" }, window.site_info.rest_root ).done( function( data ) {
                    data.valence = 'valence-grey'
                    data.label = 'Has Not Updated Profile'
                    data.description = 'Description'
                    jQuery('.'+data.key).html(window.template_single_list(data))
                    window.click_listener( data )
                    window.spin_remove()
                })
                window.spin_add()
                makeRequest('GET', 'total', { stage: "post_training_trainee", key: "needs_3_month_plan" }, window.site_info.rest_root ).done( function( data ) {
                    data.valence = 'valence-grey'
                    data.label = 'Needs Post Training Plan'
                    data.description = 'Description'
                    jQuery('.'+data.key).html(window.template_single_list(data))
                    window.click_listener( data )
                    window.spin_remove()
                })

                // ranges
                window.path_load = ( range ) => {
                    jQuery('.loading-spinner').addClass('active')

                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "post_training_trainee", key: "in_and_out", range: range }, window.site_info.rest_root ).done( function( data ) {
                        data.label = 'Post-Training Flow'
                        data.description = 'Description'
                        jQuery('.'+data.key).html( window.template_in_out( data ) )
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "post_training_trainee", key: "coach_requests", range: range }, window.site_info.rest_root ).done( function( data ) {
                        data.label = 'Coaching Requests'
                        data.description = 'Description'
                        jQuery('.'+data.key).html(window.template_single_map(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "post_training_trainee", key: "set_profile", range: range }, window.site_info.rest_root ).done( function( data ) {
                        data.label = 'Set Profile'
                        data.description = 'Description'
                        jQuery('.'+data.key).html(window.template_single_map(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "post_training_trainee", key: "completed_3_month_plans", range: range }, window.site_info.rest_root ).done( function( data ) {
                        data.label = 'Completed Post Training Plans'
                        data.description = 'Description'
                        jQuery('.'+data.key).html(window.template_single_map(data))
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
new Zume_Funnel_Post();
