<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Funnel_Trainee extends Zume_Funnel_Chart_Base
{
    //slug and title of the top menu folder
    public $base_slug = ''; // lowercase
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
        $this->base_title = __( 'Overview', 'zume_funnels' );

        $url_path = dt_get_url_path( true );
        if ( 'zume-funnel' === $url_path ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'base_scripts' ], 99 );
            add_action( 'wp_head', [ $this, 'wp_head' ], 1000 );
        }
    }

    public function base_menu( $content ) {
        $content .= '<li class=""><a href="'.site_url( '/zume-funnel/'.$this->base_slug ).'" id="'.$this->base_slug.'-menu">' .  $this->base_title . '</a></li>';
        return $content;
    }
    public function wp_head() {
            $this->styles();
            $this->js_api();
            $stages = zume_funnel_stages();
            $html = '';

        foreach ( $stages as $stage ) {
            if ( 'anonymous' === $stage['key'] ) {
                continue;
            }
            $html .= '<div class="cell medium-9 zume-funnel">
                                 <div class="'.$stage['key'].'"><span class="loading-spinner active"></span></div>
                            </div>
                            <div class="cell medium-3 padding-top">
                                <h3>Characteristics</h3>';
            $html .='<ul>';
            foreach ( $stage['characteristics'] as $item ) {
                $html .='<li>'.$item.'</li>';
            }
            $html .= '</ul>';

            $html .= '<h3>Next Steps</h3>';

            $html .= '<ul>';
            foreach ( $stage['next_steps'] as $item ) {
                $html .= '<li>'.$item.'</li>';
            }
            $html .= '</ul></div>';
        }


        ?>
            <script>
                jQuery(document).ready(function(){
                    "use strict";
                    let chart = jQuery('#chart')
                    let list = `<?php echo $html; ?>`;
                    chart.empty().html(`
                        <div id="zume-funnel">
                            <div class="grid-x">
                                <div class="cell small-6"><h1>Trainee / Practitioner Funnel</h1></div>
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
                                    <span class="loading-spinner active right" style="margin:.5em 1em;"></span>
                                </div>
                            </div>
                            <hr>
                            <div class="grid-x grid-padding-x">
                                <div class="cell medium-9 center">
                                     STEPS
                                </div>
                                <div class="cell medium-3"></div>
                                ${list}
                            </div>
                        </div>
                    `)

                    window.path_load = ( range ) => {
                        window.spin_add()
                        makeRequest('GET', 'total', { stage: "registrant", key: "total_registrants", range: range }, window.site_info.rest_root ).done( function( data ) {
                            data.label = "Registrant"
                            jQuery('.registrant').html(window.template_trio(data))
                            window.click_listener(data)
                            window.spin_remove()
                        })
                        window.spin_add()
                        makeRequest('GET', 'total', { stage: "active_training_trainee", key: "total_active_training_trainee", range: range }, window.site_info.rest_root ).done( function( data ) {
                            data.label = "Active Training Trainee"
                            jQuery('.active_training_trainee').html(window.template_trio(data))
                            window.click_listener(data)
                            window.spin_remove()
                        })
                        window.spin_add()
                        makeRequest('GET', 'total', { stage: "post_training_trainee", key: "total_post_training_trainee", range: range }, window.site_info.rest_root ).done( function( data ) {
                            data.label = "Post Training Trainee"
                            jQuery('.post_training_trainee').html(window.template_trio(data))
                            window.click_listener(data)
                            window.spin_remove()
                        })
                        window.spin_add()
                        makeRequest('GET', 'total', { stage: "partial_practitioner", key: "total_partial_practitioner", range: range }, window.site_info.rest_root ).done( function( data ) {
                            data.label = "(S1) Partial Practitioner"
                            jQuery('.partial_practitioner').html(window.template_trio(data))
                            window.click_listener(data)
                            window.spin_remove()
                        })
                        window.spin_add()
                        makeRequest('GET', 'total', { stage: "full_practitioner", key: "total_full_practitioner", range: range }, window.site_info.rest_root ).done( function( data ) {
                            data.label = "(S2) Full Practitioner"
                            jQuery('.full_practitioner').html(window.template_trio(data))
                            window.click_listener(data)
                            window.spin_remove()
                        })
                        window.spin_add()
                        makeRequest('GET', 'total', { stage: "multiplying_practitioner", key: "total_multiplying_practitioner", range: range }, window.site_info.rest_root ).done( function( data ) {
                            data.label = "(S3) Multiplying Practitioner"
                            jQuery('.multiplying_practitioner').html(window.template_trio(data))
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
new Zume_Funnel_Trainee();
