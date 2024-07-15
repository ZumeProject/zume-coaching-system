<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Zume_Tile_Communication  {
    private static $_instance = null;
    public static function instance(){
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct(){
    }
    public function get( $post_id, $post_type ) {
        $this_post = DT_Posts::get_post( $post_type, $post_id );
        if ( !isset( $this_post['trainee_user_id'] ) ) {
            ?>No Training ID Found<?php
            return;
        }
        ?>

        <div class="cell small-12 medium-4">
            <button class="button" data-open="modal-send-reports" disabled>Request Profile Update</button>
            <button class="button" data-open="modal-send-reports" disabled>Request Checklist Review</button>
            <button class="button" data-open="modal-send-reports" disabled>Request Activity Report</button>
            <button class="button" data-open="modal-send-reports" disabled>Request Church Report</button>
        </div>
        <div class="reveal large" id="modal-send-reports" data-v-offset="0" data-reveal>
            <h1>Send Reports to <?php echo $this_post['title'] ?></h1>
            <hr>
            <p class="lead">Send a report request to this person.</p>
            <button class="close-button" data-close aria-label="Close modal" type="button">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>

        <?php
    }
}

