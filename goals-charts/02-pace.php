<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Funnel_Pace extends Zume_Goals_Chart_Base
{
    //slug and title of the top menu folder
    public $base_slug = 'pace'; // lowercase
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
        $this->base_title = __( 'Pace', 'zume_goals' );

        $url_path = dt_get_url_path( true );
        if ( 'zume-goals/'.$this->base_slug === $url_path ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'base_scripts' ], 99 );
            add_action( 'wp_head', [ $this, 'wp_head' ], 1000 );
        }
    }

    public function base_menu( $content ) {
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
                                <div class="cell small-6"><h1>Pace</h1></div>
                                <div class="cell small-6">
                                    <span style="float: right;">
                                        <select id="range-filter">
                                            <?php
                                                if ( isset( $_GET['range'] ) ) {
                                                    $range = sanitize_text_field( $_GET['range'] );
                                                ?>
                                                    <option value="<?php echo $range ?>"><?php echo $range ?> days</option>
                                                <?php
                                                }
                                            ?>
                                            <option value="1095">3 Years</option>
                                            <option value="365">Last 1 Year</option>
                                            <option value="<?php echo date( 'z' ); ?>">Since year start</option>
                                            <option value="90">Last 90 days</option>
                                            <option value="30">Last 30 days</option>
                                            <option value="7">Last 7 days</option>
                                        </select>
                                    </span>
                                    <span class="loading-spinner active right" style="margin:.5em 1em;"></span>
                                </div>
                            </div>
                            <div class="grid-x grid-padding-x"><div class="cell"><hr></div></div>

                            <div class="grid-x grid-padding-x"><div class="cell"><h2>Practitioners Pace <span class="loading-spinner"></span></h2></div></div>
                            <div class="grid-x grid-padding-x">
                                <div class="cell medium-3 practitioners previous_pace"></div>
                                <div class="cell medium-3 practitioners current_pace"></div>
                                <div class="cell medium-3 practitioners days_left"></div>
                                <div class="cell medium-3 practitioners goal"></div>
                            </div>
                            <div class="grid-x grid-padding-x"><div class="cell"><h2>Churches Pace <span class="loading-spinner"></span></h2></div></div>
                            <div class="grid-x grid-padding-x">
                                <div class="cell medium-3 churches previous_pace"></div>
                                <div class="cell medium-3 churches current_pace"></div>
                                <div class="cell medium-3 churches days_left"></div>
                                <div class="cell medium-3 churches goal"></div>
                            </div>
                        </div>
                    `)

                window.path_load = ( range ) => {
                    window.spin_add()
                    makeRequest('GET', 'pace', { stage: "practitioners", key: "previous_pace", range: range }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.stage+'.'+data.key).html( window.template_single( data ) )
                        window.click_listener(data)
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'pace', { stage: "practitioners", key: "current_pace", range: range }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.stage+'.'+data.key).html( window.template_single( data ) )
                        window.click_listener(data)
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'pace', { stage: "practitioners", key: "days_left", range: range }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.stage+'.'+data.key).html( window.template_pace_arrow( data ) )
                        window.click_listener(data)
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'pace', { stage: "practitioners", key: "goal", range: range }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.stage+'.'+data.key).html( window.template_single( data ) )
                        window.click_listener(data)
                        window.spin_remove()
                    })


                    window.spin_add()
                    makeRequest('GET', 'pace', { stage: "churches", key: "previous_pace", range: range }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.stage+'.'+data.key).html( window.template_single( data ) )
                        window.click_listener(data)
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'pace', { stage: "churches", key: "current_pace", range: range }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.stage+'.'+data.key).html( window.template_single( data ) )
                        window.click_listener(data)
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'pace', { stage: "churches", key: "days_left", range: range }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.stage+'.'+data.key).html( window.template_pace_arrow( data ) )
                        window.click_listener(data)
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'pace', { stage: "churches", key: "goal", range: range }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.stage+'.'+data.key).html( window.template_single( data ) )
                        window.click_listener(data)
                        window.spin_remove()
                    })
                }
                window.setup_filter()

                window.click_listener = ( data ) => {
                    window.load_list(data)
                    window.load_map(data)
                    jQuery('.z-card-main.hover.'+data.key).click(function(){
                        window.location.href = data.link
                    })
                }
            })

        </script>
        <?php
    }

    public function styles() {
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
new Zume_Funnel_Pace();
