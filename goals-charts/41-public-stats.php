<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.


class Zume_Goals_Public_Stats extends Zume_Goals_Chart_Base
{
    //slug and title of the top menu folder
    public $base_slug = 'public_stats'; // lowercase
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
        $this->base_title = __( 'Stats', 'zume_goals' );

        $url_path = dt_get_url_path( true );
        if ( "zume-goals/$this->base_slug" === $url_path ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'base_scripts' ], 99 );
            add_action( 'wp_head', [ $this, 'wp_head' ], 1000 );
        }
    }

    public function base_menu( $content ) {
        $content .= '<li><hr></li>';
        $content .= '<li>MARKETING</li>';
        $content .= '<li><a href="'.site_url( '/zume-goals/'.$this->base_slug ).'" id="'.$this->base_slug.'-menu">' .  $this->base_title . '</a></li>';
        return $content;
    }

    public function wp_head() {
        $this->js_api();
        ?>
        <script>
            window.site_url = '<?php echo site_url() ?>' + '/wp-json/zume_goals/v1/'
            jQuery(document).ready(function(){
                "use strict";

                let chart = jQuery('#chart')
                chart.empty().html(`
                        <div id="zume-goals">
                            <div class="grid-x">
                                <div class="cell small-6"><h1>Stats for Public Promotion</h1></div>
                                <div class="cell small-6 right">General statistics that are valuable for partners and Zume supporters</div>
                            </div>
                            <hr>
                            <div class="grid-x grid-padding-x">
                                <div class="cell"><span class="loading-spinner active"></span>
                                    <table class="striped">
                                        <thead>
                                            <tr>
                                                <th style="width:33%">Description</th>
                                                <th>Fact</th>
                                            </tr>
                                        </thead>
                                        <tbody class="stats_list"></tbody>

                                    </table>
                                </div>
                            </div>
                        </div>
                    `)

                // totals
                window.spin_add()
                makeRequest('GET', 'stats_list', { key: "all" }, window.site_info.rest_root ).done( function( data ) {
                    jQuery.each( data, function( index, key ) {
                        makeRequest('GET', 'stats_list', { key: key }, window.site_info.rest_root ).done( function( data ) {
                            console.log(data)
                            jQuery('.stats_list').append(`<tr><td>${data.label}</td><td>${data.value}</td></tr>`)
                            window.spin_remove()
                        })
                    })
                })
            })
        </script>
        <?php
    }
}
new Zume_Goals_Public_Stats();
