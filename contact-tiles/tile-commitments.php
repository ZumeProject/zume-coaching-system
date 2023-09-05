<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Zume_Tile_Commitments {
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
        $profile = zume_get_user_profile( $this_post['trainee_user_id'] );
        $commitments = zume_get_user_commitments( $this_post['trainee_user_id'] );
        dt_write_log( $commitments);
        ?>
        <div class="cell small-12 medium-4">
            <div class="grid-x grid-padding-x">
                <div class="cell">
                    <h3>Commitments</h3>
                    <hr>
                    <ul>
                        <?php
                        foreach( $commitments as $commitment ) {
                            echo '<li>'.$commitment['note'].'</li>';
                        }
                        ?>
                    </ul>
                </div>
            </div>

        </div>

        <?php
    }

}
