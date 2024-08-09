<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.


class Zume_Goals_Maps_Trainees extends Zume_Goals_Chart_Base
{

    //slug and title of the top menu folder
    public $base_slug = 'heatmap_trainees'; // lowercase
    public $base_title;
    public $title;
    public $slug = 'map-trainees'; // lowercase
    public $js_object_name = 'wp_js_object'; // This object will be loaded into the metrics.js file by the wp_localize_script.
    public $js_file_name = '/dt-metrics/combined/locations-list.js'; // should be full file name plus extension
    public $permissions = [ 'dt_all_access_contacts', 'view_project_metrics' ];
    public $namespace = 'zume_goals/v1';

    public function __construct() {
        parent::__construct();
        if ( !$this->has_permission() ){
            return;
        }
        $this->base_title = __( 'Trainees', 'zume_goals' );

        $url_path = dt_get_url_path( true );
        if ( "zume-goals/$this->base_slug" === $url_path ) {
            add_action( 'wp_head', [ $this, 'wp_head' ], 1000 );
        }
    }

    public function base_menu( $content ) {
        $content .= '<li><hr></li>';
        $content .= '<li>MAPS</li>';
        $content .= '<li><a href="'.site_url( '/zume-goals/'.$this->base_slug ).'" id="'.$this->base_slug.'-menu">' .  $this->base_title . '</a></li>';
        return $content;
    }

    public function wp_head() {
        $this->js_api();
        $url = 'https://zume.training/zume_app/' . $this->base_slug . '/';
        ?>
        <script>
            jQuery(document).ready(function(){
                "use strict";

                let url = '<?php echo esc_url( $url ); ?>'
                let chart = jQuery('#chart')
                let height = jQuery(window).height() - 200

                chart.empty().html(`
                        <div id="zume-goals">
                            <div class="grid-x">
                                <div class="cell small-6"><h1>Trainees Heatmap</h1></div>
                                <div class="cell small-6"></div>
                            </div>
                            <hr>
                            <div class="grid-x grid-padding-x">
                                <div class="cell"><iframe src="${url}" style="width: 100%; height: ${height}px; border: 0;"></iframe></div>
                            </div>
                        </div>
                    `)

                jQuery('.loading-spinner').removeClass('active')
            })

        </script>
        <?php
    }
}
new Zume_Goals_Maps_Trainees();

class Zume_Goals_Maps_Practitioners extends Zume_Goals_Chart_Base
{

    //slug and title of the top menu folder
    public $base_slug = 'heatmap_practitioners'; // lowercase
    public $base_title;
    public $title;
    public $slug = 'map-trainees'; // lowercase
    public $js_object_name = 'wp_js_object'; // This object will be loaded into the metrics.js file by the wp_localize_script.
    public $js_file_name = '/dt-metrics/combined/locations-list.js'; // should be full file name plus extension
    public $permissions = [ 'dt_all_access_contacts', 'view_project_metrics' ];
    public $namespace = 'zume_goals/v1';

    public function __construct() {
        parent::__construct();
        if ( !$this->has_permission() ){
            return;
        }
        $this->base_title = __( 'Practitioners', 'zume_goals' );

        $url_path = dt_get_url_path( true );
        if ( "zume-goals/$this->base_slug" === $url_path ) {
            add_action( 'wp_head', [ $this, 'wp_head' ], 1000 );
        }
    }

    public function wp_head() {
        $this->js_api();
        $url = 'https://zume.training/zume_app/' . $this->base_slug . '/';
        ?>
        <script>
            jQuery(document).ready(function(){
                "use strict";

                let url = '<?php echo esc_url( $url ); ?>'
                let chart = jQuery('#chart')
                let height = jQuery(window).height() - 200

                chart.empty().html(`
                        <div id="zume-goals">
                            <div class="grid-x">
                                <div class="cell small-6"><h1>Practitioner Heatmap</h1></div>
                                <div class="cell small-6"></div>
                            </div>
                            <hr>
                            <div class="grid-x grid-padding-x">
                                <div class="cell"><iframe src="${url}" style="width: 100%; height: ${height}px; border: 0;"></iframe></div>
                            </div>
                        </div>
                    `)

                jQuery('.loading-spinner').removeClass('active')
            })

        </script>
        <?php
    }
}
new Zume_Goals_Maps_Practitioners();

class Zume_Goals_Maps_Churches extends Zume_Goals_Chart_Base
{

    //slug and title of the top menu folder
    public $base_slug = 'heatmap_churches'; // lowercase
    public $base_title;
    public $title;
    public $slug = 'map-trainees'; // lowercase
    public $js_object_name = 'wp_js_object'; // This object will be loaded into the metrics.js file by the wp_localize_script.
    public $js_file_name = '/dt-metrics/combined/locations-list.js'; // should be full file name plus extension
    public $permissions = [ 'dt_all_access_contacts', 'view_project_metrics' ];
    public $namespace = 'zume_goals/v1';

    public function __construct() {
        parent::__construct();
        if ( !$this->has_permission() ){
            return;
        }
        $this->base_title = __( 'Churches', 'zume_goals' );

        $url_path = dt_get_url_path( true );
        if ( "zume-goals/$this->base_slug" === $url_path ) {
            add_action( 'wp_head', [ $this, 'wp_head' ], 1000 );
        }
    }

    public function wp_head() {
        $this->js_api();
        $url = 'https://zume.training/zume_app/' . $this->base_slug . '/';
        ?>
        <script>
            jQuery(document).ready(function(){
                "use strict";

                let url = '<?php echo esc_url( $url ); ?>'
                let chart = jQuery('#chart')
                let height = jQuery(window).height() - 200

                chart.empty().html(`
                        <div id="zume-goals">
                            <div class="grid-x">
                                <div class="cell small-6"><h1>Churches Heatmap</h1></div>
                                <div class="cell small-6"></div>
                            </div>
                            <hr>
                            <div class="grid-x grid-padding-x">
                                <div class="cell"><iframe src="${url}" style="width: 100%; height: ${height}px; border: 0;"></iframe></div>
                            </div>
                        </div>
                    `)

                jQuery('.loading-spinner').removeClass('active')
            })

        </script>
        <?php
    }
}
new Zume_Goals_Maps_Churches();
