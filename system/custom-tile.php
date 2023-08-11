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
            $tiles['followup'] = [ 'label' => __( 'Funnel Stage', 'zume-coaching' ) ];
            $tiles['faith'] = [ 'label' => __( 'Zúme System', 'zume-coaching' ) ];
            $tiles['communication'] = [ 'label' => __( 'Communication', 'zume-coaching' ) ];
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
            if ( !isset( $this_post['trainee_user_id'] ) ) {
                ?>No Training ID Found<?php
                return;
            }
            $funnel_stages = zume_funnel_stages();
            $funnel_number = zume_get_stage( $this_post['trainee_user_id'], NULL, true );
            ?>
            <div class="cell small-12 medium-4">
                <?php
                    foreach( $funnel_stages as $stage ) {
                        $stage_class = 'hollow';
                        if ( $stage['stage'] <= $funnel_number ) {
                            $stage_class = 'success';
                        }
                        ?>
                        <button class="button expanded <?php echo $stage_class ?>"><?php echo $stage['label'] ?></button>
                        <?php
                    }
                ?>
            </div>
        <?php }

        if ( $post_type === 'contacts' && $section === 'faith' ) {

            $this_post = DT_Posts::get_post( $post_type, get_the_ID() );
            if ( !isset( $this_post['trainee_user_id'] ) ) {
                ?>No Training ID Found<?php
                return;
            }
            $activity = zume_user_log( $this_post['trainee_user_id'] );

            ?>
            <div class="cell small-12 medium-4">
                <button class="button expanded" data-open="modal_user_overview">User Overview</button>
            </div>
            <hr>
            <h4>Training</h4>
            <div class="cell small-12 medium-4">
                <button class="button expanded" data-open="modal_activity">Activity History</button>
                <button class="button expanded" data-open="modal_checklists">Checklists</button>
            </div>
            <hr>
            <h4>Practitioner</h4>
            <div class="cell small-12 medium-4">
                <button class="button expanded" data-open="modal_reports">Practitioner Reports</button>
                <button class="button expanded" data-open="modal_genmap">Current Genmap</button>
            </div>


            <div class="reveal full" id="modal_user_overview" data-v-offset="0" data-reveal>
                <h1>User Overview for <?php echo $this_post['title'] ?></h1>
                <hr>
                <div style="height: 800px"></div>
                <button class="close-button" data-close aria-label="Close modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="reveal full" id="modal_activity" data-v-offset="0" data-reveal>
                <h1>Activity History for <?php echo $this_post['title'] ?></h1>
                <hr>
                <div>
                    <?php
                    if ( ! empty( $activity ) ) {
                        foreach( $activity as $row ) {
                            echo date( 'M d, Y h:i:s a', $row['time_end'] ) ?> | <strong><?php echo $row['log_key'] ?></strong><br><?php
                        }
                    }
                    ?>
                </div>
                <button class="close-button" data-close aria-label="Close modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="reveal full" id="modal_checklists" data-v-offset="0" data-reveal>
                <h1>Checklists for <?php echo $this_post['title'] ?></h1>
                <hr>
                <div style="height: 800px"></div>
                <button class="close-button" data-close aria-label="Close modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="reveal full" id="modal_reports" data-v-offset="0" data-reveal>
                <h1>Practitioner Reports for <?php echo $this_post['title'] ?></h1>
                <hr>
                <div>
                    <?php
                    if ( ! empty( $activity ) ) {
                        foreach( $activity as $row ) {
                            if ( 'reports' === $row['type'] ) {
                                echo date( 'M d, Y h:i:s a', $row['time_end'] ) ?> | <strong><?php echo $row['log_key'] ?></strong><br><?php
                            }
                        }
                    }
                    ?>
                </div>
                <button class="close-button" data-close aria-label="Close modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="reveal full" id="modal_genmap" data-v-offset="0" data-reveal>
                <h1>Current Genmap for <?php echo $this_post['title'] ?></h1>
                <hr>
                <div style="height: 800px"></div>
                <button class="close-button" data-close aria-label="Close modal" type="button">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>


        <?php }

        if ( $post_type === 'contacts' && $section === 'communication' ) {

            $this_post = DT_Posts::get_post( $post_type, get_the_ID() );
            if ( !isset( $this_post['trainee_user_id'] ) ) {
                ?>No Training ID Found<?php
                return;
            }
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
