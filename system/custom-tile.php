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
        add_filter( 'dt_details_additional_tiles', [ $this, 'dt_details_additional_tiles' ], 99, 2 );
        add_filter( 'dt_custom_fields_settings', [ $this, 'dt_custom_fields_settings' ], 1, 2 );
        add_action( 'dt_details_additional_section', [ $this, 'dt_details_additional_section' ], 30, 2 );
    }

    public function dt_details_additional_tiles( $tiles, $post_type = '' ) {
        if ( $post_type === 'contacts' ) {
            $tiles['followup'] = [ 'label' => __( 'Progress', 'zume-coaching' ) ];
            $tiles['faith'] = [ 'label' => __( 'Activity Profile', 'zume-coaching' ) ];
            $tiles['communication'] = [ 'label' => __( 'Communication Tools', 'zume-coaching' ) ];
        }
        return $tiles;
    }
    public function dt_custom_fields_settings( array $fields, string $post_type = '' ) {

        if ( $post_type === 'contacts' ) {


        }

        return $fields;
    }
    public function dt_details_additional_section( $section, $post_type ) {

        if ( $post_type === 'contacts' && $section === 'followup' ) {

            $this_post = DT_Posts::get_post( $post_type, get_the_ID() );
            $post_type_fields = DT_Posts::get_post_field_settings( $post_type );
            ?>

            <div class="cell small-12 medium-4">
                <button class="button expanded">Registration</button>
                <button class="button expanded hollow">Active Training</button>
                <button class="button expanded hollow">Post-Training</button>
                <button class="button expanded hollow">Stage 1 Partial Practitioner</button>
                <button class="button expanded hollow">Stage 2 Completed Practitioner</button>
                <button class="button expanded hollow">Stage 3 Multiplying Practitioner</button>
            </div>


        <?php }

        if ( $post_type === 'contacts' && $section === 'faith' ) {

            $this_post = DT_Posts::get_post( $post_type, get_the_ID() );
            $post_type_fields = DT_Posts::get_post_field_settings( $post_type );
            ?>

            <div class="cell small-12 medium-4">
                <button class="button" data-open="modal_checklist">Training Checklist</button>
                <button class="button" data-open="modal_history">Activity History</button>
                <button class="button" data-open="modal_reports">Reports History</button>
                <button class="button" data-open="modal_genmap">Current Genmap</button>
            </div>
            <div class="reveal large" id="modal_checklist" data-v-offset="0" data-reveal>
                <h1>Training Checklist for <?php echo $this_post['title'] ?></h1>
                <hr>
                <div style="height: 800px"></div>
                <button class="close-button" data-close aria-label="Close modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="reveal large" id="modal_history" data-v-offset="0" data-reveal>
                <h1>Activity History for <?php echo $this_post['title'] ?></h1>
                <hr>
                <div style="height: 800px"></div>
                <button class="close-button" data-close aria-label="Close modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="reveal large" id="modal_reports" data-v-offset="0" data-reveal>
                <h1>Report History for <?php echo $this_post['title'] ?></h1>
                <hr>
                <div style="height: 800px"></div>
                <button class="close-button" data-close aria-label="Close modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="reveal full" id="modal_genmap" data-v-offset="0" data-reveal>
                <h1>Genmap for <?php echo $this_post['title'] ?></h1>
                <hr>
                <div style="height: 800px"></div>
                <button class="close-button" data-close aria-label="Close modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

        <?php }

        if ( $post_type === 'contacts' && $section === 'communication' ) {

            $this_post = DT_Posts::get_post( $post_type, get_the_ID() );
            $post_type_fields = DT_Posts::get_post_field_settings( $post_type );
            ?>

            <div class="cell small-12 medium-4">
                <button class="button" data-open="modal-send-reports">Request Profile Update</button>
                <button class="button" data-open="modal-send-reports">Request Checklist Review</button>
                <button class="button" data-open="modal-send-reports">Request Activity Report</button>
                <button class="button" data-open="modal-send-reports">Request Church Report</button>
            </div>
            <div class="reveal large" id="modal-send-reports" data-v-offset="0" data-reveal>
                <h1>Send Reports to <?php echo $this_post['title'] ?></h1>
                <hr>
                <p class="lead">Send a report request to this person.</p>
                <button class="close-button" data-close aria-label="Close modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

        <?php }
    }
}
Zume_Coaching_Tile::instance();
