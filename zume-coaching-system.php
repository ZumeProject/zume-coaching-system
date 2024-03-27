<?php
/**
 * Plugin Name: Zúme - Coaching System
 * Plugin URI: https://github.com/ZumeProject/zume-coaching
 * Description: Zúme - Coaching enables remote coaching of users in another system.
 * Text Domain: zume-coaching
 * Domain Path: /languages
 * Version:  0.1
 * Author URI: https://github.com/DiscipleTools
 * GitHub Plugin URI: https://github.com/ZumeProject/zume-coaching
 * Requires at least: 4.7.0
 * (Requires 4.7+ because of the integration of the REST API at 4.7 and the security requirements of this milestone version.)
 * Tested up to: 5.6
 *
 * @package Disciple_Tools
 * @link    https://github.com/DiscipleTools
 * @license GPL-2.0 or later
 *          https://www.gnu.org/licenses/gpl-2.0.html
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! defined( 'ZUME_TRAINING_URL' ) ) {
    define( 'ZUME_TRAINING_URL', 'https://zume5.training/' );
}
if ( ! defined( 'ZUME_COACHING_URL' ) ) {
    define( 'ZUME_COACHING_URL', 'https://zume5.training/coaching/' );
}

/**
 * Gets the instance of the `Zume_Coaching` class.
 *
 * @since  0.1
 * @access public
 * @return object|bool
 */
function zume_coaching() {
    $zume_coaching_required_dt_theme_version = '1.19';
    $wp_theme = wp_get_theme();
    $version = $wp_theme->version;

    if ( phpversion() < '8.0' ) {
        add_action( 'admin_notices', 'zume_coaching_hook_admin_notice' );
        add_action( 'wp_ajax_dismissed_notice_handler', 'dt_hook_ajax_notice_handler' );
        return false;
    }

    /*
     * Check if the Disciple.Tools theme is loaded and is the latest required version
     */
    $is_theme_dt = class_exists( 'Disciple_Tools' );
    if ( $is_theme_dt && version_compare( $version, $zume_coaching_required_dt_theme_version, '<' ) ) {
        add_action( 'admin_notices', 'zume_coaching_hook_admin_notice' );
        add_action( 'wp_ajax_dismissed_notice_handler', 'dt_hook_ajax_notice_handler' );
        return false;
    }
    if ( !$is_theme_dt ){
        return false;
    }
    /**
     * Load useful function from the theme
     */
    if ( !defined( 'DT_FUNCTIONS_READY' ) ){
        require_once get_template_directory() . '/dt-core/global-functions.php';
    }

    return Zume_Coaching::instance();

}
add_action( 'after_setup_theme', 'zume_coaching', 20 );

//register the D.T Plugin
add_filter( 'dt_plugins', function ( $plugins ){
    $plugin_data = get_file_data( __FILE__, [ 'Version' => 'Version', 'Plugin Name' => 'Zume Coaching' ], false );
    $plugins['zume-coaching'] = [
        'plugin_url' => trailingslashit( plugin_dir_url( __FILE__ ) ),
        'version' => $plugin_data['Version'] ?? null,
        'name' => $plugin_data['Plugin Name'] ?? null,
    ];
    return $plugins;
});

class Zume_Coaching {
    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct() {
        $this->define_constants();
        /* setup query access to zume.training site reports table */
        global $wpdb;
        $wpdb->zume_reports = 'wp_dt_reports';
        $wpdb->zume_activity = 'wp_dt_activity_log';
        $wpdb->dt_movement_log = 'wp_3_dt_movement_log'; // @remove temp support for legacy movement log
        /* end custom table setup */

        require_once( 'globals.php' );
        require_once( 'contact-tiles/loader.php' );
        require_once( 'appearance/loader.php' );
        require_once( 'shared/loader.php' );

        require_once( 'funnel-charts/loader.php' );
        require_once( 'goals-charts/loader.php' );
        require_once( 'magic-maps/cluster-1-last100.php' );
        require_once( 'magic-maps/heatmap.php' );
        require_once( 'magic-maps/map-2-network-activities.php' );
        require_once( 'magic-maps/map-3-trainees.php' );
        require_once( 'magic-maps/map-4-practitioners.php' );
        require_once( 'magic-maps/map-5-churches.php' );

        $this->i18n();

        if ( is_admin() ) {
            add_filter( 'plugin_row_meta', [ $this, 'plugin_description_links' ], 10, 4 );
        }

    }
    public function plugin_description_links( $links_array, $plugin_file_name, $plugin_data, $status ) {
        if ( strpos( $plugin_file_name, basename( __FILE__ ) ) ) {
            $links_array[] = '<a href="https://disciple.tools">Disciple.Tools Community</a>';
        }
        return $links_array;
    }
    public function i18n() {
        $domain = 'zume-coaching';
        load_plugin_textdomain( $domain, false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ). 'languages' );
    }
    public function __toString() {
        return 'zume-coaching';
    }
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }
    public function __call( $method = '', $args = array() ) {
        _doing_it_wrong( 'zume_coaching::' . esc_html( $method ), 'Method does not exist.', '0.1' );
        unset( $method, $args );
        return null;
    }
    public function define_constants() {
        if ( !defined( 'ZUME_LANGUAGE_COOKIE' ) ) {
            define( 'ZUME_LANGUAGE_COOKIE', 'zume_language' );
        }
        if ( !defined( 'ZUME_EMAIL_HEADER' ) ) {
            define( 'ZUME_EMAIL_HEADER', 'X-Zume-Email-System' );
        }
    }
}
