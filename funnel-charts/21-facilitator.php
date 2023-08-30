<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.


class Zume_Funnel_Coaching_Facilitator extends Zume_Funnel_Chart_Base
{
    //slug and title of the top menu folder
    public $base_slug = 'coaching_facilitator'; // lowercase
    public $slug = '';
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
        $this->base_title = __( 'Facilitator', 'zume_funnels' );

        $url_path = dt_get_url_path( true );
        if ( "zume-funnel/$this->base_slug" === $url_path ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'base_scripts' ], 99 );
            add_action( 'wp_head',[ $this, 'wp_head' ], 1000);
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
                                <div class="cell small-6"><h1>Facilitator Coaches</h1></div>
                                <div class="cell small-6 right">Coaching activity during the Registration and Active Training Stages</div>
                            </div>
                            <hr>
                            <div class="grid-x">
                                <div class="cell small-12">
                                    <h2>Cumulative</h2>
                                </div>
                            </div>
                            <div class="grid-x grid-margin-x grid-margin-y">
                                 <div class="cell medium-6 total_registrants"><span class="loading-spinner active"></span></div>
                                 <div class="cell medium-6 total_att"><span class="loading-spinner active"></span></div>
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
                                 <div class="cell medium-6 new_coaching_requests"><span class="loading-spinner active"></span></div>
                                 <div class="cell medium-6 languages"><span class="loading-spinner active"></span></div>
                            </div>
                        </div>
                    `)

                window.spin_add()
                makeRequest('GET', 'total', { stage: "registrants", key: "total_registrants" }, window.site_info.rest_root ).done( function( data ) {
                    data.label = 'Current Registrants'
                    data.valence = 'valence-grey'
                    jQuery('.total_registrants').html(window.template_single(data))
                    window.spin_remove()
                })
                window.spin_add()
                makeRequest('GET', 'total', { stage: "att", key: "total_att" }, window.site_info.rest_root ).done( function( data ) {
                    data.label = 'Current Active Trainees'
                    data.valence = 'valence-grey'
                    jQuery('.total_att').html(window.template_single(data))
                    window.spin_remove()
                })

                window.path_load = ( range ) => {

                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "facilitator", key: "new_coaching_requests", range: range }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.new_coaching_requests').html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "facilitator", key: "languages", range: range }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.languages').html(window.template_single(data))
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
new Zume_Funnel_Coaching_Facilitator();
