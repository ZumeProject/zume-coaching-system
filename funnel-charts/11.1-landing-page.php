<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Funnel_Landing_Page extends Zume_Funnel_Chart_Base
{
    //slug and title of the top menu folder
    public $base_slug = 'landing-page'; // lowercase
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
        $this->base_title = __( 'Landing Page', 'zume_funnels' );

        $url_path = dt_get_url_path( true );
        if ( "zume-funnel/$this->base_slug" === $url_path ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'base_scripts' ], 99 );
            add_action( 'wp_head', [ $this, 'wp_head' ], 1000 );
        }
    }

    public function base_menu( $content ) {
        $content .= '<li><hr></li>';
        $content .= '<li>FUNNEL</li>';
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
                                <div class="cell small-6"><h1>Landing Pages</h1></div>
                                <div class="cell small-6 right">Open to: group training, a change in obedience, & multiplicative practices</div>
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
                            <div class="grid-x grid-margin-x grid-margin-y">
                                 <div class="cell medium-12 landing_page_source_users"><span class="loading-spinner active"></span></div>
                            </div>

                        </div>
                    `)

                window.path_load = ( range ) => {

                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "anonymous", key: "landing_page_source_users", range: range }, window.site_info.rest_root ).done( function( data ) {
                        jQuery('.landing_page_source_users').html( window.template_single_list( data ) )
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
new Zume_Funnel_Landing_Page();
