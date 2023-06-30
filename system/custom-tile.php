<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Coaching_Tile
{
    private static $_instance = null;
    public static function instance(){
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct(){
        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 10, 2 );
        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields_settings' ], 1, 2 );
        add_action( 'dt_details_additional_section', [ $this, 'dt_details_additional_section' ], 30, 2 );
    }

    public function dt_details_additional_tiles( $tiles, $post_type = '' ) {
        if ( $post_type === 'contacts' ) {
            $tiles['user_view_tile'] = [ 'label' => __( 'User View', 'zume-coaching' ) ];
            $tiles['request_reports_tile'] = [ 'label' => __( 'Request Reports', 'zume-coaching' ) ];
        }
        return $tiles;
    }
    public function dt_custom_fields_settings( array $fields, string $post_type = '' ) {

        if ( $post_type === 'contacts' ) {


        }

        return $fields;
    }
    public function dt_details_additional_section( $section, $post_type ) {

        if ( $post_type === 'contacts' && $section === 'user_view_tile' ) {

            $this_post = DT_Posts::get_post( $post_type, get_the_ID() );
            $post_type_fields = DT_Posts::get_post_field_settings( $post_type );
            ?>

            <div class="cell small-12 medium-4">
                <button class="button" data-open="modal-funnel">End to End Funnel</button>
                <button class="button" data-open="modal-reports">Open User Reports</button>
            </div>
            <div class="reveal full" id="modal-funnel" data-v-offset="0" data-reveal>
                <h1>End to End Funnel for <?php echo $this_post['title'] ?></h1>
                <p class="lead">Show funnel progress</p>
                <p>I'm a cool paragraph that lives inside of an even cooler modal. Wins!</p>
                <button class="close-button" data-close aria-label="Close modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="reveal full" id="modal-reports"  data-v-offset="0" data-reveal>
                <h1>User Reports for <?php echo $this_post['title'] ?></h1>
                <p class="lead">Show report history</p>
                <p>I'm a cool paragraph that lives inside of an even cooler modal. Wins!</p>
                <button class="close-button" data-close aria-label="Close modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

        <?php }

        if ( $post_type === 'contacts' && $section === 'request_reports_tile' ) {

            $this_post = DT_Posts::get_post( $post_type, get_the_ID() );
            $post_type_fields = DT_Posts::get_post_field_settings( $post_type );
            ?>

            <div class="cell small-12 medium-4">
                <button class="button" data-open="modal-send-reports">Send Report Request</button>
            </div>
            <div class="reveal" id="modal-send-reports" data-v-offset="0" data-reveal>
                <h1>Send Reports to <?php echo $this_post['title'] ?></h1>
                <p class="lead">Send a report request to this person.</p>
                <p>I'm a cool paragraph that lives inside of an even cooler modal. Wins!</p>
                <button class="close-button" data-close aria-label="Close modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

        <?php }
    }
}
Zume_Coaching_Tile::instance();
