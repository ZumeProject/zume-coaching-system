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
                                <div class="cell">
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
                makeRequest('GET', 'total', { stage: "general", key: "stats_list" }, window.site_info.rest_root ).done( function( data ) {
                    jQuery('.'+data.key).empty()
                    data.list = [
                        {
                            'label': 'Number of Languages Available',
                            'value': window.randNumber()
                        },
                        {
                            'label': 'Number of Native Speakers with Access',
                            'value': window.randNumber()
                        },
                        {
                            'label': 'Number of People with Some Training',
                            'value': window.randNumber()
                        },
                        {
                            'label': 'Locations with Zero Activity',
                            'value': window.randNumber()
                        },
                        {
                            'label': 'Active Coaches',
                            'value': window.randNumber()
                        },
                    ]
                    jQuery.each( data.list, function( i, v ) {
                        jQuery('.'+data.key).append( `<tr><td>${v.label}</td><td>${v.value}</td></tr>` )
                    })
                    window.spin_remove()
                })

                window.path_load = ( range ) => {} /* not used */
                window.setup_filter()

                window.click_listener = ( data ) => {
                    window.load_list(data)
                    window.load_map(data)
                }

                $(document).foundation();

                window.randNumber = () => {
                    let number = Math.floor((Math.random() * 10000) + 100)
                    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
                }
            })
        </script>
        <?php
    }
}
new Zume_Goals_Public_Stats();
