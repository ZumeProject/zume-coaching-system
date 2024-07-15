<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Zume_Tile_System  {
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
        } else {
            $trainee_user_id = $this_post['trainee_user_id'];
        }

        $profile = zume_get_user_profile( $trainee_user_id );
        $log = zume_get_user_log( $trainee_user_id );
        $active_commitments = zume_get_user_commitments( $trainee_user_id );
        $completed_commitments = zume_get_user_commitments( $trainee_user_id, 'closed' );

        $profile['commitments'] = 0;
        $profile['reports'] = 0;
        $profile['churches'] = 0;
        $profile['activities'] = 0;
        $reports = [];
        foreach( $log as $item ) {
            if ( $item['type'] == 'reports' && $item['subtype'] == 'new_church' ) {
                $profile['churches']++;
            }
            if ( 'reports' === $item['type'] ) {
                $reports[] = $item;
                $profile['reports']++;
            }
            $profile['activities']++;
        }
        if ( count($active_commitments) > 0 ) {
            $profile['commitments'] = count($active_commitments);
        }

        ?>
        <div class="cell small-12 medium-4">
            <button class="button expanded" data-open="modal_activity">User Activities <?php echo !empty( $profile['activities'] ) ? '('. $profile['activities'] . ')': ''; ?></button>
            <button class="button expanded" id="open_commitments">Commitments <?php echo !empty( $profile['commitments'] ) ? '('. $profile['commitments'] . ')': ''; ?></button>
            <button class="button expanded" data-open="modal_reports" disabled>Reports <?php echo !empty( $profile['reports'] ) ? '('. $profile['reports'] . ')': ''; ?></button>
            <button class="button expanded" data-open="modal_genmap" disabled>Church GenMap <?php echo !empty( $profile['churches'] ) ? '('. $profile['churches'] . ')': ''; ?></button>
            <button class="button expanded" data-open="modal_localized_vision" disabled>Localized Vision</button>
        </div>
        <?php

        // Modals
        self::_modal_localized_vision( $profile );
        self::_modal_reports( $profile, $reports );
        self::_modal_commitments( $profile, $active_commitments, $completed_commitments );
        self::_modal_genmap( $profile, $trainee_user_id );
        self::_modal_activity( $profile, $log );

    }
    private function _modal_localized_vision( $profile ) {
        ?>
        <div class="reveal full" id="modal_localized_vision" data-v-offset="0" data-reveal>
            <h1>Localized Vision for <?php echo $profile['name'] ?></h1>
            <hr>
            <iframe src="<?php echo ZUME_TRAINING_URL ?>zume_app/local_vision/?grid_id=<?php echo $profile['location']['grid_id'] ?>" id="local_vision_window" style="border:none;" width="100%" height="600px"></iframe>

            <button class="close-button" data-close aria-label="Close modal" type="button">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <script>
            jQuery(document).ready(function(){
                let vision_height = window.innerHeight - 125;
                jQuery('#local_vision_window').css('height', vision_height + 'px').css('width', '100%' );
            })
        </script>
        <?php
    }
    private function _modal_reports( $profile, $reports ) {
        ?>
        <div class="reveal" id="modal_reports" data-v-offset="0" data-reveal>
            <h1>Practitioner Reports for <?php echo $profile['name'] ?></h1>
            <hr>
            <div>
                <?php Zume_Coaching_Tile::print_activity_list( $reports ) ?>
            </div>
            <button class="close-button" data-close aria-label="Close modal" type="button">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php
    }
    private function _modal_commitments( $profile, $active_commitments, $completed_commitments ) {
        ?>
        <div class="reveal large" id="modal_commitments" data-v-offset="0" data-reveal>
            <h1>Commitments for <?php echo $profile['name'] ?></h1>
            <hr>
            <div class="grid-x grid-padding-x">
                <div class="cell medium-8">
                    <div class="grid-x grid-padding-x" >
                        <div class="cell" style="background: lightgrey; padding: 1em;"><h2>Active</h2></div>
                        <div class="cell"><br></div>
                    </div>
                    <div class="grid-x grid-padding-x active-commitments"></div>
                    <div class="grid-x grid-padding-x" >
                        <div class="cell" style="background: lightgrey; padding: 1em;"><h2>Completed</h2></div>
                        <div class="cell"><br></div>
                    </div>
                    <div class="grid-x grid-padding-x completed-commitments"></div>
                </div>
                <div class="cell medium-4">
                    <h3>Add New</h3>
                    <p > You can add commitments here and it will show up on <?php echo $profile['name'] ?>'s list.</p>
                    <textarea id="commitment-note" placeholder="Enter a new commitment"></textarea>
                    <button class="button commitment-add">Add Commitment</button> <span class="commitment-indicator loading-spinner"></span>
                </div>
            </div>
            <button class="close-button" data-close aria-label="Close modal" type="button">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <script>
            jQuery(document).ready(function(){
                jQuery('#open_commitments').on('click', function(){
                    window.commitments();
                })
                jQuery('.commitment-add').on('click', function(){
                    window.commitments_add();
                })

                window.commitments = () => {
                    console.log('cta_commitments')

                    makeRequest('GET', 'commitments', { user_id: window.trainee_profile.user_id, status: 'all' }, 'zume_system/v1').done(function (data) {
                        let active_commitments = jQuery('.active-commitments')
                        let completed_commitments = jQuery('.completed-commitments')
                        active_commitments.empty()
                        completed_commitments.empty()
                        if (data) {
                            jQuery.each(data, function (i, v) {
                                if ( v.question !== '' && v.answer !== '' && v.status === 'open') {
                                    active_commitments.append(`<div class="cell medium-9"><strong>Question:</strong> ${v.question}<br><strong>Answer:</strong> ${v.answer}</br><strong>Due Date:</strong> ${v.due_date}<br><strong>Status:</strong> ${v.status}</div><div class="cell medium-3"> <button class="button complete-commitment" value="${v.id}">Complete</button> <button class="button delete-commitment" value="${v.id}">Delete</button></div><div class="cell"><hr></div>`)
                                }
                                else if ( v.note !== '' && v.status === 'open') {
                                    active_commitments.append(`<div class="cell medium-9"><strong>Note:</strong> ${v.note}</br><strong>Due Date:</strong> ${v.due_date}<br><strong>Status:</strong> ${v.status}</div><div class="cell medium-3"><button class="button complete-commitment" value="${v.id}">Complete</button> <button class="button delete-commitment" value="${v.id}">Delete</button></div><div class="cell"><hr></div>`)
                                }
                                if ( v.question !== '' && v.answer !== '' && v.status === 'closed') {
                                    completed_commitments.append(`<div class="cell medium-9"><strong>Question:</strong> ${v.question}<br><strong>Answer:</strong> ${v.answer}</br><strong>Due Date:</strong> ${v.due_date}<br><strong>Status:</strong> ${v.status}</div><div class="cell medium-3"></div><div class="cell"><hr></div>`)
                                }
                                else if ( v.note !== '' && v.status === 'closed') {
                                    completed_commitments.append(`<div class="cell medium-9"><strong>Note:</strong> ${v.note}</br><strong>Due Date:</strong> ${v.due_date}<br><strong>Status:</strong> ${v.status}</div><div class="cell medium-3"></div><div class="cell"><hr></div>`)
                                }
                            })
                        }

                        jQuery('.complete-commitment').on('click', function () {
                            let id = jQuery(this).val()
                            let data = {
                                id: id,
                                user_id: window.trainee_profile.user_id
                            }
                            makeRequest('PUT', 'commitment', data, 'zume_system/v1').done(function (data) {
                                window.commitments()
                            })
                        })

                        jQuery('.delete-commitment').on('click', function () {
                            let id = jQuery(this).val()
                            let data = {
                                id: id,
                                user_id: window.trainee_profile.user_id
                            }
                            makeRequest('DELETE', 'commitment', data, 'zume_system/v1').done(function (data) {
                                window.commitments()
                            })
                        })

                    })

                    jQuery('#modal_commitments').foundation('open')
                }
                window.commitments_add = () => {
                    jQuery('.commitment-indicator.loading-spinner').addClass('active')
                    let note = jQuery('#commitment-note').val()
                    let data = {
                        note: note,
                        user_id: window.trainee_profile.user_id
                    }
                    makeRequest('POST', 'commitment', data, 'zume_system/v1').done(function (data) {
                        jQuery('#commitment-note').val('')
                        jQuery('.commitment-indicator.loading-spinner').removeClass('active')
                        window.commitments()
                    })
                }
            })
        </script>
        <?php
    }
    private function _modal_genmap( $profile, $user_id ) {
        Zume_User_Genmap::instance()->modal( $profile, $user_id);
    }
    private function _modal_activity( $profile, $log ) {
        ?>
        <div class="reveal" id="modal_activity" data-v-offset="0" data-reveal>
            <h1>Activity History for <?php echo $profile['name'] ?></h1>
            <hr>
            <?php Zume_Coaching_Tile::print_activity_list( $log) ?>
            <button class="close-button" data-close aria-label="Close modal" type="button">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php
    }

}
