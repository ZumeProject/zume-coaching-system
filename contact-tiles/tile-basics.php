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
        $log = zume_get_user_log( $this_post['trainee_user_id'] );
        $host = zume_get_user_host( $this_post['trainee_user_id'], $log );
        $mawl = zume_get_user_mawl( $this_post['trainee_user_id'], $log );

        $h = $host['percent']['h'] ?? 0;
        $o = $host['percent']['o'] ?? 0;
        ;
        $s = $host['percent']['s'] ?? 0;
        ;
        $t = $host['percent']['t'] ?? 0;
        ;
        $m = $mawl['percent']['m'] ?? 0;
        $a = $mawl['percent']['a'] ?? 0;
        $w = $mawl['percent']['w'] ?? 0;
        $l = $mawl['percent']['l'] ?? 0;

        ?>
        <style>
            .open_host_modal, .open_host_modal progress, .open_host_modal label, .open_mawl_modal, .open_mawl_modal progress, .open_mawl_modal label {
                cursor: pointer !important;
            }
            .open_host_modal:hover, .open_mawl_modal:hover {
                background-color: #F5F5F5;
            }
            progress {
                vertical-align: middle;
                width: 90%;
            }
            .zume_button_green {
                background-color: #4CAF50 !important;
                color: white !important;
            }
        </style>
        <div class="cell small-12 medium-4">
            <div>Email : <?php echo $profile['email'] ?></div>
            <div>Phone : <?php echo $profile['phone'] ?></div>
            <div>Language : <?php echo $profile['language']['name'] ?? '' ?></div>
            <hr>
            <div class="open_host_modal" data-open="open_host_modal">
                <div><label for="heard">H : <progress id="heard" max="100" value="<?php echo $h ?>"><?php echo $h ?>%</progress></label></div>
                <div><label for="obeyed">O : <progress id="obeyed" max="100" value="<?php echo $o ?>"><?php echo $o ?>%</progress></label></div>
                <div><label for="shared">S : <progress id="shared" max="100" value="<?php echo $s ?>"><?php echo $s ?>%</progress></label></div>
                <div><label for="trained">T : <progress id="trained" max="100" value="<?php echo $t ?>"><?php echo $t ?>%</progress></label></div>
            </div>
            <hr>
            <div class="open_mawl_modal" data-open="open_mawl_modal">
                <div><label for="modeling">M : <progress id="modeling" max="100" value="<?php echo $m ?>"><?php echo $m ?>%</progress></label></div>
                <div><label for="assisting">A : <progress id="assisting" max="100" value="<?php echo $a ?>"><?php echo $a ?>%</progress></label></div>
                <div><label for="watching">W : <progress id="watching" max="100" value="<?php echo $w ?>"><?php echo $w ?>%</progress></label></div>
                <div><label for="launching">L : <progress id="launching" max="100" value="<?php echo $l ?>"><?php echo $l ?>%</progress></label></div>
            </div>
            <hr>
            <div>Location : <?php echo $profile['location']['label'] ?? '' ?></div>
            <div id="zume_map" style="width:100%; height: 300px;"><span class="zume_map loading-spinner active"></span></div>
            <script>
                jQuery(document).ready(function() {
                    let lat = <?php echo $profile['location']['lat'] ?? 0 ?>;
                    let lng = <?php echo $profile['location']['lng'] ?? 0 ?>;
                    let level = '<?php echo $profile['location']['level'] ?? 'admin0' ?>';

                    let zoom = 6
                    if ( 'admin0' === level ){
                        zoom = 3
                    } else if ( 'admin1' === level ) {
                        zoom = 4
                    } else if ( 'admin2' === level ) {
                        zoom = 5
                    }

                    window.mapboxgl.accessToken = window.dtMapbox.map_key;
                    var map = new window.mapboxgl.Map({
                        container: 'zume_map',
                        style: 'mapbox://styles/mapbox/streets-v11',
                        center: [lng, lat],
                        minZoom: 1,
                        zoom: zoom
                    });

                    var marker = new window.mapboxgl.Marker()
                        .setLngLat([lng, lat])
                        .addTo(map);

                    jQuery('.zume_map.loading-spinner').removeClass('active');
                })
            </script>

            <?php $this->_modal_host( $this_post, $log ) ?>
            <?php $this->_modal_mawl( $this_post, $log ) ?>
        </div>
        <?php
    }

    private function _modal_host( $this_post, $activity ) {
        $training_items = zume_training_items();
        $completed = [];
        foreach ( $activity as $item ) {
            if ( $item['type'] == 'training' ) {
                $completed[$item['log_key']] = $item['log_key'];
            }
        }
        ?>
        <div class="reveal" id="open_host_modal" data-v-offset="0" data-reveal>
            <h1>HOST Report for <?php echo $this_post['title'] ?></h1>
            <hr>
            <div class="grid-x">
                <?php
                foreach ( $training_items as $element ) {
                    $url = '';
                    ?>
                    <div class="cell small-8">
                        <a data-value="<?php echo esc_url( $url ); ?>" class="coaching-checklist-modal-open" target="_blank"><?php echo esc_html( $element['title'] ); ?></a>
                    </div>
                    <div class="cell small-4" style="text-align:right;">
                        <div class="small button-group" style="display: inline-block; margin-bottom: 5px;">
                            <?php foreach ( $element['host'] as $option_value ) : ?>
                                <button id="<?php echo esc_html( $option_value['key'] ) ?>" type="button" data-type="<?php echo esc_html( $option_value['type'] ); ?>" data-subtype="<?php echo esc_html( $option_value['subtype'] ) ?>"
                                        class="<?php echo in_array( $option_value['key'], $completed ) ? 'zume_button_green' : ''; ?> empty-select-button <?php echo esc_html( $option_value['type'] ); ?>_<?php echo esc_html( $option_value['subtype'] ) ?> select-button button <?php echo esc_html( $option_value['type'] ); ?>" style="padding:5px">
                                    <?php echo esc_html( $option_value['short_label'] ) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            <button class="close-button" data-close aria-label="Close modal" type="button">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <script>
            jQuery(document).ready(function(){
                jQuery('.select-button.button.training').on('click', function( event ){
                    let type = event.target.dataset.type
                    let subtype = event.target.dataset.subtype

                    if ( jQuery(this).hasClass('zume_button_green') ) {
                        jQuery(this).removeClass('zume_button_green')
                        makeRequest('DELETE', 'host', { type: type, subtype: subtype, user_id: <?php echo $this_post['trainee_user_id'] ?> }, 'zume_system/v1' ).done( function( data ) {
                            console.log(data)

                        })
                    } else {
                        jQuery(this).addClass('zume_button_green')
                        makeRequest('POST', 'log', { type: type, subtype: subtype, user_id: <?php echo $this_post['trainee_user_id'] ?>  }, 'zume_system/v1' ).done( function( data ) {
                            console.log(data)
                        })
                    }
                })
            })
        </script>
        <?php
    }
    private function _modal_mawl( $this_post, $activity ) {
        $training_items = zume_training_items();
        $completed = [];
        foreach ( $activity as $item ) {
            if ( $item['type'] == 'coaching' ) {
                $completed[$item['log_key']] = $item['log_key'];
            }
        }
        ?>
        <div class="reveal" id="open_mawl_modal" data-v-offset="0" data-reveal>
            <h1>MAWL Progress for <?php echo $this_post['title'] ?></h1>
            <hr>
            <div class="grid-x">
                <?php
                foreach ( $training_items as $element ) {
                    if ( empty( $element['mawl'] ) ) {
                        continue;
                    }
                    $url = '';
                    ?>
                    <div class="cell small-8">
                        <a data-value="<?php echo esc_url( $url ); ?>" class="coaching-checklist-modal-open" target="_blank"><?php echo esc_html( $element['title'] ); ?></a>
                    </div>
                    <div class="cell small-4" style="text-align:right;">
                        <div class="small button-group" style="display: inline-block; margin-bottom: 5px;">
                            <?php foreach ( $element['mawl'] as $option_value ) : ?>
                                <button id="<?php echo esc_html( $option_value['key'] ) ?>" type="button" data-type="<?php echo esc_html( $option_value['type'] ); ?>" data-subtype="<?php echo esc_html( $option_value['subtype'] ) ?>"
                                        class="<?php echo in_array( $option_value['key'], $completed ) ? 'zume_button_green' : ''; ?> empty-select-button <?php echo esc_html( $option_value['type'] ); ?>_<?php echo esc_html( $option_value['subtype'] ) ?> select-button button <?php echo esc_html( $option_value['type'] ); ?>" style="padding:5px">
                                    <?php echo esc_html( $option_value['short_label'] ) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            <button class="close-button" data-close aria-label="Close modal" type="button">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <script>
            jQuery(document).ready(function(){
                jQuery('.select-button.button.coaching').on('click', function( event ){
                    let type = event.target.dataset.type
                    let subtype = event.target.dataset.subtype

                    if ( jQuery(this).hasClass('zume_button_green') ) {
                        jQuery(this).removeClass('zume_button_green')
                        makeRequest('DELETE', 'mawl', { type: type, subtype: subtype, user_id: <?php echo $this_post['trainee_user_id'] ?> }, 'zume_funnel/v1' ).done( function( data ) {
                            console.log(data)

                        })
                    } else {
                        jQuery(this).addClass('zume_button_green')
                        makeRequest('POST', 'mawl', { type: type, subtype: subtype, user_id: <?php echo $this_post['trainee_user_id'] ?>  }, 'zume_funnel/v1' ).done( function( data ) {
                            console.log(data)
                        })
                    }
                })
            })
        </script>
        <?php
    }
}
