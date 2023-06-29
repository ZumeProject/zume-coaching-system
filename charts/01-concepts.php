<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.


class Zume_Coaching_Path_Goals extends Zume_Coaching_Chart_Base
{
    //slug and title of the top menu folder
    public $base_slug = ''; // lowercase
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
        $this->base_title = __( 'concepts', 'zume_coaching' );

        $url_path = dt_get_url_path( true );
        if ( "zume-coaching" === $url_path ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ], 99 );
            add_action( 'wp_head',[ $this, 'wp_head' ], 1000);
        }
    }

    public function base_menu( $content ) {
        $content .= '<li>ZÚME SIMULATOR</li>';
        $content .= '<li><hr></li>';
        $content .= '<li>This plugin is for development only. It is a collection of tools to simulate and test the zume training system</li>';
        $content .= '<li><hr></li>';
        $content .= '<li><a href="'.site_url('/zume-coaching/'.$this->base_slug).'" id="'.$this->base_slug.'-menu">' .  $this->base_title . '</a></li>';
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
                        <div id="zume-coaching-path">
                            <div class="grid-x">
                                <div class="cell"><h1>Concepts and Visualization of Zúme System</h1></div>
                            </div>
                            <hr>
                            <span class="loading-spinner active"></span>
                            <div class="grid-x">
                                <div class="cell small-12">
                                    <h1 style="background-color: lightgrey; padding: 1em;"><strong>FUNNEL RELATIONSHIPS</strong></h1>
                                    <img src="<?php  echo plugin_dir_url(__DIR__) . 'images/funnel-relationships.png' ?>" />
                                </div>
                                <div class="cell"><hr></div>
                                <div class="cell small-12">
                                    <h1 style="background-color: lightgrey; padding: 1em;"><strong>STAGE SUMMARIES</strong></h1>
                                    <div class="grid-x grid-padding-x grid-margin-x grid-margin-y">
                                        <div class="cell small-4 center tile">
                                            <img src="<?php  echo plugin_dir_url(__DIR__) . 'images/stage-anonymous.png' ?>" />
                                        </div>
                                        <div class="cell small-4 center tile">
                                            <img src="<?php  echo plugin_dir_url(__DIR__) . 'images/stage-registrant.png' ?>" />
                                        </div>
                                        <div class="cell small-4 center tile">
                                            <img src="<?php  echo plugin_dir_url(__DIR__) . 'images/stage-active.png' ?>" />
                                        </div>
                                        <div class="cell small-4 center tile">
                                            <img src="<?php  echo plugin_dir_url(__DIR__) . 'images/stage-post.png' ?>" />
                                        </div>
                                        <div class="cell small-4 center tile">
                                            <img src="<?php  echo plugin_dir_url(__DIR__) . 'images/stage-s1.png' ?>" />
                                        </div>
                                        <div class="cell small-4 center tile">
                                            <img src="<?php  echo plugin_dir_url(__DIR__) . 'images/stage-s2.png' ?>" />
                                        </div>
                                        <div class="cell small-4 center tile">
                                            <img src="<?php  echo plugin_dir_url(__DIR__) . 'images/stage-s3.png' ?>" />
                                        </div>
                                    </div>
                                    <style>.cell.tile { border: 1px solid grey; border-radius: 10px; padding: 1em; }</style>
                                </div>
                                <div class="cell"><hr></div>
                                <div class="cell small-12">
                                    <h1 style="background-color: lightgrey; padding: 1em;"><strong>DIFFICULTY OVER TIME</strong></h1>
                                    <img src="<?php  echo plugin_dir_url(__DIR__) . 'images/difficulty-vs-time.png' ?>" />
                                </div>
                                <div class="cell"><hr></div>
                                <div class="cell">
                                    <h1 style="background-color: lightgrey; padding: 1em;"><strong>VALENCE</strong></h1>
                                    <div class="grid-x" style="color:white;text-align:center;font-size:2.5em;">
                                        <div class="cell small-2 valence-darkred">-20%</div>
                                        <div class="cell small-2 valence-red">-10%</div>
                                        <div class="cell small-4 valence-grey">On Track</div>
                                        <div class="cell small-2 valence-green">+10%</div>
                                        <div class="cell small-2 valence-darkgreen">+20%</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `)

                jQuery('.loading-spinner').delay(3000).removeClass('active')
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
new Zume_Coaching_Path_Goals();
