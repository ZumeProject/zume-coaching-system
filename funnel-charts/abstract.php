<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

abstract class Zume_Funnel_Chart_Base
{

    public $core = 'zume-funnel';
    public $base_slug = 'example'; //lowercase
    public $base_title = 'Example';

    //child
    public $title = '';
    public $slug = '';
    public $js_object_name = ''; // This object will be loaded into the metrics.js file by the wp_localize_script.
    public $js_file_name = ''; // should be full file name plus extension
    public $permissions = [];

    public function __construct() {
        $this->base_slug = str_replace( ' ', '', trim( strtolower( $this->base_slug ) ) );
        $url_path = dt_get_url_path( true );

        if ( strpos( $url_path, 'zume-funnel' ) === 0 ) {
            if ( !$this->has_permission() ){
                return;
            }
            add_filter( 'dt_metrics_menu', [ $this, 'base_menu' ], 20 ); //load menu links

            if ( strpos( $url_path, "zume-funnel/$this->base_slug" ) === 0 ) {
                add_filter( 'dt_templates_for_urls', [ $this, 'base_add_url' ] ); // add custom URLs
                add_action( 'wp_enqueue_scripts', [ $this, 'base_scripts' ], 99 );
            }
        }
    }

    public function base_menu( $content ) {
        $content .= '<li class=""><a href="'.site_url( '/zume-funnel/'.$this->base_slug ).'" id="'.$this->base_slug.'-menu">' .  $this->base_title . '</a></li>';
        return $content;
    }

    public function base_add_url( $template_for_url ) {
        if ( empty( $this->base_slug ) ) {
            $template_for_url['zume-funnel'] = 'template-metrics.php';
        } else {
            $template_for_url["zume-funnel/$this->base_slug"] = 'template-metrics.php';
        }
        return $template_for_url;
    }

    public function base_scripts() {
        wp_register_script( 'amcharts-core', 'https://www.amcharts.com/lib/4/core.js', false, '4' );
        wp_register_script( 'amcharts-charts', 'https://www.amcharts.com/lib/4/charts.js', false, '4' );
        wp_register_script( 'amcharts-animated', 'https://www.amcharts.com/lib/4/themes/animated.js', [ 'amcharts-core' ], '4' );

        wp_enqueue_style( 'datatable_css', '//cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css', [], '1.13.4' );
        wp_enqueue_script( 'datatable_js', '//cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js', [ 'jquery' ], '1.13.4' );

        wp_localize_script(
            'dt_'.$this->base_slug.'_script', 'wpMetricsBase', [
                'slug' => $this->base_slug,
                'root' => esc_url_raw( rest_url() ),
                'plugin_uri' => plugin_dir_url( __DIR__ ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'current_user_login' => wp_get_current_user()->user_login,
                'current_user_id' => get_current_user_id(),
            ]
        );
    }

    public function has_permission(){
        $permissions = $this->permissions;
        $pass = count( $permissions ) === 0;
        foreach ( $this->permissions as $permission ){
            if ( current_user_can( $permission ) ){
                $pass = true;
            }
        }
        return $pass;
    }

    public function js_api() {
        ?>
        <style>
            <?php echo file_get_contents( trailingslashit( plugin_dir_path( __DIR__ ) ) . 'shared/chart-templates.css' ); ?>
        </style>
        <script>
            window.site_info = {
                'map_key': '<?php echo DT_Mapbox_API::get_key(); ?>',
                'rest_root': 'zume_funnel/v1/',
                'site_url': '<?php echo site_url(); ?>',
                'rest_url': '<?php echo esc_url_raw( rest_url() ); ?>',
                'total_url': '<?php echo esc_url_raw( rest_url() ); ?>zume_funnel/v1/total',
                'range_url': '<?php echo esc_url_raw( rest_url() ); ?>zume_funnel/v1/range',
                'map_url': '<?php echo esc_url_raw( rest_url() ); ?>zume_funnel/v1/map',
                //'list_url': '<?php //echo esc_url_raw( rest_url() ); ?>//zume_funnel/v1/list',
                'elements_url': '<?php echo esc_url_raw( rest_url() ); ?>zume_funnel/v1/training_elements',
                'plugin_uri': '<?php echo plugin_dir_url( __DIR__ ); ?>',
                'nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>',
                'current_user_login': '<?php echo wp_get_current_user()->user_login; ?>',
                'current_user_id': '<?php echo get_current_user_id(); ?>'
            };

            <?php echo file_get_contents( trailingslashit( plugin_dir_path( __DIR__ ) ) . 'shared/chart-templates.js' ); ?>
        </script>
        <?php
    }
}
