<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Funnel_Registrant extends Zume_Funnel_Chart_Base
{
    //slug and title of the top menu folder
    public $base_slug = 'registrants'; // lowercase
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
        $this->base_title = __( 'Registrant', 'zume_funnels' );

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
                                <div class="cell small-6"><h1>Registrant</h1></div>
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
                            <div class="grid-x">
                                <div class="cell total_registrants"><span class="loading-spinner active"></span></div>
                            </div>
                            <div class="grid-x grid-margin-x grid-margin-y">
                                 <div class="cell medium-6 locations"><span class="loading-spinner active"></span></div>
                                <div class="cell medium-6 languages"><span class="loading-spinner active"></span></div>
                            </div>
                            <hr>
                            <div class="grid-x grid-margin-x grid-margin-y">
                                 <div class="cell medium-6 has_no_coach"><span class="loading-spinner active"></span></div>
                                 <div class="cell medium-6 not_set_name"><span class="loading-spinner active"></span></div>
                                 <div class="cell medium-6 not_set_location"><span class="loading-spinner active"></span></div>
                                 <div class="cell medium-6 not_set_phone"><span class="loading-spinner active"></span></div>
                                 <div class="cell medium-6 has_not_completed_profile"><span class="loading-spinner active"></span></div>
                                 <div class="cell medium-6 coach_requests"><span class="loading-spinner active"></span></div>
                            </div>
                            <div class="grid-x grid-margin-x grid-margin-y">
                                 <div class="cell medium-12 in_and_out"><span class="loading-spinner active"></span></div>
                            </div>
                        </div>
                `)

                // ranges
                window.path_load = ( range ) => {
                    jQuery('.loading-spinner').addClass('active')

                    // totals
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "registrant", key: "locations", range: range  }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single_map(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "registrant", key: "languages", range: range  }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single_list(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })

                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "registrant", key: "total_registrants", range: range  }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_trio(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "registrant", key: "has_no_coach", range: range  }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single_list(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "registrant", key: "has_not_completed_profile", range: range  }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single_list(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "registrant", key: "not_set_phone", range: range  }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single_list(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "registrant", key: "not_set_name", range: range  }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single_list(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "registrant", key: "not_set_location", range: range  }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single_list(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "registrant", key: "coach_requests", range: range  }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single_list(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    // positive
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "registrant", key: "in_and_out", range: range }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html( window.template_in_out( data ) )
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
new Zume_Funnel_Registrant();
