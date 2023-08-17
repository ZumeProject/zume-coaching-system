<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Zume_Tile_Basics {
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
        ?>
        <div class="cell small-12 medium-4">
            <p>Email : <?php echo $profile['email'] ?></p>
            <p>Phone : <?php echo $profile['phone'] ?></p>
            <p>Language : <?php echo $profile['locale'] ?></p>
            <p>Location : <?php echo $profile['location']['label'] ?></p>
            <p>H : <progress id="file" max="100" value="70">70%</progress></p>
            <p>O : <progress id="file" max="100" value="70">70%</progress></p>
            <p>S : <progress id="file" max="100" value="70">70%</progress></p>
            <p>T : <progress id="file" max="100" value="70">70%</progress></p>
            <p>M : <progress id="file" max="100" value="70">70%</progress></p>
            <p>A : <progress id="file" max="100" value="70">70%</progress></p>
            <p>W : <progress id="file" max="100" value="70">70%</progress></p>
            <p>L : <progress id="file" max="100" value="70">70%</progress></p>
        </div>
        <?php
    }
}
