<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Funnel_Events extends Zume_Funnel_Chart_Base
{
    //slug and title of the top menu folder
    public $base_slug = 'timerange'; // lowercase
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
        $this->base_title = __( 'Time Range', 'zume_funnels' );

        $url_path = dt_get_url_path( true );
        if ( "zume-funnel/$this->base_slug" === $url_path ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'base_scripts' ], 99 );
            add_action( 'wp_head', [ $this, 'wp_head' ], 1000 );
        }
    }

    public function base_menu( $content ) {
        $content .= '<li><hr></li>';
        $content .= '<li>FACTS</li>';
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
                                <div class="cell small-6"><h1>System</h1></div>
                                <div class="cell small-6 right">Has registered. Needs to plan a training and invite others.</div>
                            </div>
                            <hr>
                            <div class="grid-x">
                                <div class="cell center"><h1 id="range-title">Last 30 Days</h1></div>
                                <div class="cell small-6">
                                    <h2>Time Range</h2>
                                </div>
                                <div class="cell small-6" style="float: right;">
                                     <span>
                                        <select id="range-filter" class="z-range-filter">
                                            <?php
                                            if ( isset( $_GET['range'] ) ) {
                                            $range = sanitize_text_field( $_GET['range'] );
                                            ?><option value="<?php echo $range ?>"><?php echo $range ?> days</option><?php
                                            }
                                            ?>
                                            <option value="90">Last 90 days</option>
                                            <option value="30">Last 30 days</option>
                                            <option value="7">Last 7 days</option>
                                            <option value="365">Last 1 Year</option>
                                            <option value="<?php echo date( 'z' ); ?>">Since year start</option>
                                            <option value="-1">All Time</option>
                                        </select>
                                    </span>
                                    <span class="loading-spinner active float-spinner"></span>
                                </div>
                            </div>
                            <div class="grid-x grid-margin-x grid-margin-y">
                                <div class="cell medium-6 registrations"><span class="loading-spinner active"></span></div>
                                <div class="cell medium-6 coach_requests"><span class="loading-spinner active"></span></div>
                                <div class="cell medium-6 locations"><span class="loading-spinner active"></span></div>
                                <div class="cell medium-6 languages"><span class="loading-spinner active"></span></div>
                                <div class="cell medium-6 downloads"><span class="loading-spinner active"></span></div>
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
                window.path_load = ( range ) => {
                    jQuery('.loading-spinner').addClass('active')

                    // totals
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "time_range", key: "locations", range: range  }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "time_range", key: "languages", range: range  }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "time_range", key: "registrations", range: range }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "time_range", key: "coach_requests", range: range  }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "time_range", key: "downloads", range: range  }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })

                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "time_range", key: "set_a_01", range: range  }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "time_range", key: "set_a_02", range: range  }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "time_range", key: "set_a_03", range: range  }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "time_range", key: "set_a_04", range: range  }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "time_range", key: "set_a_05", range: range  }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "time_range", key: "set_a_06", range: range  }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "time_range", key: "set_a_07", range: range  }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "time_range", key: "set_a_08", range: range }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "time_range", key: "set_a_09", range: range  }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "time_range", key: "set_a_10", range: range  }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single(data))
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
new Zume_Funnel_Events();
