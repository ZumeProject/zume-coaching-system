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
    public $permissions = [ 'access_contacts' ];

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
                                <div class="cell center"><h1 id="range-title">Last 30 Days</h1></div>
                                <div class="cell small-6">
                                    <h2>Time Range</h2>
                                </div>
                                <div class="cell small-6">
                                    <span style="float: right;">
                                        <select id="range-filter">
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
                                    <span class="loading-spinner active" style="float: right; margin:0 10px;"></span>
                                </div>
                            </div>
                             <div class="grid-x">
                                <div class="cell total_post_training_trainee"><span class="loading-spinner active"></span></div>
                            </div>
                            <div class="grid-x grid-margin-x grid-margin-y">
                                 <div class="cell medium-6 has_no_coach"><span class="loading-spinner active"></span></div>
                                 <div class="cell medium-6 has_not_completed_profile"><span class="loading-spinner active"></span></div>
                                 <div class="cell medium-6 needs_3_month_plan"><span class="loading-spinner active"></span></div>
                                 <div class="cell medium-6 new_coaching_requests"><span class="loading-spinner active"></span></div>
                                 <div class="cell medium-6 new_3_month_plans"><span class="loading-spinner active"></span></div>
                            </div>
                            <div class="grid-x grid-margin-x grid-margin-y">
                                 <div class="cell medium-12 in_and_out"><span class="loading-spinner active"></span></div>
                            </div>
                        </div>
                    `)

                // ranges
                window.path_load = ( range ) => {
                    jQuery('.loading-spinner').addClass('active')

                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "post_training_trainee", key: "total_post_training_trainee", range: range  }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_trio(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "post_training_trainee", key: "has_no_coach", range: range  }, window.site_info.rest_root ).done( function( data ) {
                        data.valence = 'valence-grey'
                        jQuery('.'+data.key).html(window.template_single_list(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "post_training_trainee", key: "has_not_completed_profile", range: range  }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single_list(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "post_training_trainee", key: "needs_3_month_plan", range: range  }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single_list(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })

                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "post_training_trainee", key: "new_3_month_plans", range: range }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).html(window.template_single_list(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "post_training_trainee", key: "in_and_out", range: range }, window.site_info.rest_root ).done( function( data ) {
                        data.label = 'Post-Training Flow'
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
new Zume_Funnel_Post();
