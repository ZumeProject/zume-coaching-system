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
        $funnel_number = zume_get_user_stage( $this_post['trainee_user_id'], null, true );
        ?>
        <div class="cell small-12 medium-4">
            <?php
            foreach ( $funnel_stages as $stage ) {
                if ( 'anonymous' === $stage['key'] ) {
                    continue;
                }
                $stage_class = 'hollow';
                if ( $stage['value'] <= $funnel_number ) {
                    $stage_class = 'success';
                }
                ?>
                <button style=" margin-bottom:5px;" class="button expanded <?php echo $stage_class ?>" data-open="<?php echo $stage['key'] ?>" ><?php echo $stage['label'] ?></button>
                <div class="reveal" id="<?php echo $stage['key'] ?>" data-v-offset="0" data-reveal>
                    <h1><?php echo $stage['label'] ?></h1>
                    <hr>
                    <div class="grid-x grid-padding-x">
                        <div class="cell">
                            <h3>Description</h3>
                            <p><?php echo $stage['description_full'] ?></p>
                        </div>

                        <div class="cell">
                            <hr>
                            <h3>Characteristics</h3>
                            <?php
                            if ( ! empty( $stage['characteristics'] ) ) {
                                echo '<ul>';
                                foreach ( $stage['characteristics'] as $item ) {
                                    echo '<li>'.$item.'</li>';
                                }
                                echo '</ul>';
                            }
                            ?>
                        </div>

                        <div class="cell">
                            <hr>
                            <h3>Next Steps</h3>
                            <?php
                            if ( ! empty( $stage['next_steps'] ) ) {
                                echo '<ul>';
                                foreach ( $stage['next_steps'] as $item ) {
                                    echo '<li>'.$item.'</li>';
                                }
                                echo '</ul>';
                            }
                            ?>
                        </div>
                    </div>
                    <button class="close-button" data-close aria-label="Close modal" type="button">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php
            }
            ?>
        </div>

        <?php
    }
}
