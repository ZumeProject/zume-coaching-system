<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Funnel_Cumulative_Checkins extends Zume_Funnel_Chart_Base
{
    //slug and title of the top menu folder
    public $base_slug = 'checkincummulative'; // lowercase
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
        $this->base_title = __( 'Cumulative Checkins', 'zume_funnels' );

        $url_path = dt_get_url_path( true );
        if ( "zume-funnel/$this->base_slug" === $url_path ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'base_scripts' ], 99 );
            add_action( 'wp_head', [ $this, 'wp_head' ], 1000 );
        }
    }

    public function base_menu( $content ) {
        $content .= '<li><a href="'.site_url( '/zume-funnel/'.$this->base_slug ).'" id="'.$this->base_slug.'-menu">' .  $this->base_title . '</a></li>';
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
                                <div class="cell small-6"><h1>Cumulative</h1></div>
                                <div class="cell small-6 right">
                                    <span>
                                        <input type="date" id="date-filter" class="z-range-filter" />
                                        <span class="loading-spinner active float-spinner"></span>
                                    </span>
                                </div>
                            </div>
                            <hr>
                            <div class="grid-x grid-margin-x grid-margin-y">
                                <div class="cell center"><h2>Checkins</h2></div>
                            </div>
                            <div class="grid-x grid-margin-x grid-margin-y">
                                <div class="cell medium-6 set_a_01"><span class="loading-spinner active"></span></div>
                                <div class="cell medium-6 set_a_02"><span class="loading-spinner active"></span></div>
                                <div class="cell medium-6 set_a_03"><span class="loading-spinner active"></span></div>
                                <div class="cell medium-6 set_a_04"><span class="loading-spinner active"></span></div>
                                <div class="cell medium-6 set_a_05"><span class="loading-spinner active"></span></div>
                                <div class="cell medium-6 set_a_06"><span class="loading-spinner active"></span></div>
                                <div class="cell medium-6 set_a_07"><span class="loading-spinner active"></span></div>
                                <div class="cell medium-6 set_a_08"><span class="loading-spinner active"></span></div>
                                <div class="cell medium-6 set_a_09"><span class="loading-spinner active"></span></div>
                                <div class="cell medium-6 set_a_10"><span class="loading-spinner active"></span></div>
                            </div>
                        </div>
                `)

                // ranges
                window.path_load = ( end_date ) => {
                    jQuery('.loading-spinner').addClass('active')

                    
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "cumulative", key: "set_a_01", end_date: end_date }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "cumulative", key: "set_a_02", end_date: end_date }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "cumulative", key: "set_a_03", end_date: end_date }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "cumulative", key: "set_a_04", end_date: end_date }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "cumulative", key: "set_a_05", end_date: end_date }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "cumulative", key: "set_a_06", end_date: end_date }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "cumulative", key: "set_a_07", end_date: end_date }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "cumulative", key: "set_a_08", end_date: end_date }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "cumulative", key: "set_a_09", end_date: end_date }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "cumulative", key: "set_a_10", end_date: end_date }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })

                }

                let range_filter = jQuery('#date-filter')
                window.filter = range_filter.val()
                range_filter.on('change', function(){
                    window.filter = range_filter.val()
                    window.path_load( window.filter )
                })
                window.path_load( window.filter )

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
new Zume_Funnel_Cumulative_Checkins();
