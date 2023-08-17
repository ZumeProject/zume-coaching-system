<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Zume_Tile_Funnel {
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
                <button style="cursor: default;" class="button expanded <?php echo $stage_class ?>"><?php echo $stage['label'] ?></button>
                <?php
            }
            ?>
        </div>
        <?php
    }
}
