<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.


class Zume_Goals_Public_Facts extends Zume_Goals_Chart_Base
{
    //slug and title of the top menu folder
    public $base_slug = 'public_facts'; // lowercase
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
        $this->base_title = __( 'Facts', 'zume_goals' );

        $url_path = dt_get_url_path( true );
        if ( "zume-goals/$this->base_slug" === $url_path ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'base_scripts' ], 99 );
            add_action( 'wp_head', [ $this, 'wp_head' ], 1000 );
        }
    }

    public function wp_head() {
        $this->js_api();
        ?>
        <script>
            window.site_url = '<?php echo site_url() ?>' + '/wp-json/zume_funnel/v1/'
            jQuery(document).ready(function(){
                "use strict";

                let chart = jQuery('#chart')
                chart.empty().html(`
                        <div id="zume-goals">
                            <div class="grid-x">
                                <div class="cell small-6"><h1>Facts for Public Promotion</h1></div>
                                <div class="cell small-6 right">General statistics that are valuable for partners and Zume supporters</div>
                            </div>
                            <hr>
                            <div class="grid-x grid-padding-x">
                                <div class="cell small-6 ">
                                    <h3>General Facts</h3><hr>
                                    <ol class="all_time_stats"><span class="loading-spinner active"></span></ol>
                                </div>
                                <div class="cell small-6" style="border-left: 1px solid lightgrey;">
                                    <h3>Time Based Facts</h3><hr>
                                    <div class="grid-x">
                                        <div class="cell">
                                            <div class="grid-x">
                                                <div class="cell auto">
                                                    <select id="range-filter">
                                                        <option value="30">In the last 30 days</option>
                                                        <option value="90">In the last 90 days</option>
                                                        <option value="365">In the last year</option>
                                                        <option value="365">Since the beginning of the year</option>
                                                    </select>
                                                </div>
                                                <div class="cell small-2" >
                                                    <span class="loading-spinner active" style=" margin:5px 10px;"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="cell">
                                            <ul class="range_stats"><span class="loading-spinner active"></span></ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `)

                // totals
                window.spin_add()
                makeRequest('GET', 'total', { stage: "general", key: "all_time_stats" }, window.site_info.rest_root ).done( function( data ) {
                    jQuery('.'+data.key).empty()
                    data.list =  [
                        `There are ${window.randNumber()} registered users in the Zúme system.`,
                        `The Zume project has been running for ${window.randNumber()} days.`
                    ]
                    jQuery.each( data.list, function( i, v ) {
                        jQuery('.'+data.key).append( `<li>${v}</li>` )
                    })
                    window.spin_remove()
                })

                window.path_load = ( range ) => {

                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "general", key: "range_stats", range: range }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.'+data.key).empty()
                        let pre_statement = jQuery('#range-filter :selected').text()
                        data.list =  [
                            `${pre_statement}, there have been ${window.randNumber()} registered users in the Zúme system.`,
                            `${pre_statement}, ${window.randNumber()} people have visited the Zume.Training site.`
                        ]
                        jQuery.each( data.list, function( i, v ) {
                            jQuery('.'+data.key).append( `<li>${v}</li>` )
                        })
                        window.spin_remove()
                    })

                }
                window.setup_filter()

                window.click_listener = ( data ) => {
                    window.load_list(data)
                    window.load_map(data)
                }

                window.randNumber = () => {
                    let number = Math.floor((Math.random() * 10000) + 100)
                    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                }
            })
        </script>
        <?php
    }
}
new Zume_Goals_Public_Facts();
