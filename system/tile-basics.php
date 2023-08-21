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
        $host = zume_get_user_host( $this_post['trainee_user_id'] );
        $mawl = zume_get_user_mawl( $this_post['trainee_user_id'] );
        dt_write_log($host);

        $h = $host['percent']['h'] ?? 0;
        $o = $host['percent']['o'] ?? 0;;
        $s = $host['percent']['s'] ?? 0;;
        $t = $host['percent']['t'] ?? 0;;
        $m = $mawl['percent']['m'] ?? 0;
        $a = $mawl['percent']['a'] ?? 0;
        $w = $mawl['percent']['w'] ?? 0;
        $l = $mawl['percent']['l'] ?? 0;
        ?>
        <div class="cell small-12 medium-4">
            <div>Email : <?php echo $profile['email'] ?></div>
            <div>Phone : <?php echo $profile['phone'] ?></div>
            <div>Language : <?php echo $profile['language']['name'] ?? '' ?></div>
            <div>Location : <?php echo $profile['location']['label'] ?? '' ?></div>
            <hr>
            <div>H : <progress id="file" max="100" value="<?php echo $h ?>"><?php echo $h ?>%</progress></div>
            <div>O : <progress id="file" max="100" value="<?php echo $o ?>"><?php echo $o ?>%</progress></div>
            <div>S : <progress id="file" max="100" value="<?php echo $s ?>"><?php echo $s ?>%</progress></div>
            <div>T : <progress id="file" max="100" value="<?php echo $t ?>"><?php echo $t ?>%</progress></div>
            <hr>
            <div>M : <progress id="file" max="100" value="<?php echo $m ?>"><?php echo $m ?>%</progress></div>
            <div>A : <progress id="file" max="100" value="<?php echo $a ?>"><?php echo $a ?>%</progress></div>
            <div>W : <progress id="file" max="100" value="<?php echo $w ?>"><?php echo $w ?>%</progress></div>
            <div>L : <progress id="file" max="100" value="<?php echo $l ?>"><?php echo $l ?>%</progress></div>
        </div>
        <?php
    }
}
