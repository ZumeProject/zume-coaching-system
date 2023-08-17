<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Zume_Tile_System extends Zume_Coaching_Tile {
    private static $_instance = null;
    public static function instance(){
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct(){
        parent::__construct();
    }

    public function get( $post_id, $post_type ) {

        $this_post = DT_Posts::get_post( $post_type, $post_id );
        if ( !isset( $this_post['trainee_user_id'] ) ) {
            ?>No Training ID Found<?php
            return;
        } else {
            $trainee_user_id = $this_post['trainee_user_id'];
        }

        if ( !isset( $this_post['trainee_contact_id'] ) ) {
            ?>No Contact ID Found<?php
            return;
        } else {
            $trainee_contact_id = $this_post['trainee_contact_id'];
        }

        $activity = zume_user_log( $trainee_user_id );
        if ( empty( $activity ) ) {
           $activity = [];
        }

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

        <?php

        // Modals
        self::_modal_user_overview( $this_post, $activity );
        self::_modal_activity( $this_post, $activity );
        self::_modal_checklists( $this_post, $activity );
        self::_modal_reports( $this_post, $activity );

    }

    private function _modal_user_overview( $this_post, $activity ) {
        ?>
        <div class="reveal full" id="modal_user_overview" data-v-offset="0" data-reveal>
            <h1>User Overview for <?php echo $this_post['title'] ?></h1>
            <hr>
            <div class="grid-x grid-padding-x">
                <div class="cell medium-4">
                    <h2>Details</h2>
                </div>
                <div class="cell medium-4">
                    <h2>Activity</h2>
                    <?php $this->print_activity_list( $activity) ?>
                </div>
                <div class="cell medium-4">
                    <h2>Location</h2>
                    <div id="zume_map" style="height: 400px;border:1px solid lightgrey;"></div>
                </div>
            </div>
            <button class="close-button" data-close aria-label="Close modal" type="button">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php
    }
    private function _modal_activity( $this_post, $activity ) {
        ?>
        <div class="reveal" id="modal_activity" data-v-offset="0" data-reveal>
            <h1>Activity History for <?php echo $this_post['title'] ?></h1>
            <hr>
                <?php $this->print_activity_list( $activity) ?>
            <button class="close-button" data-close aria-label="Close modal" type="button">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php
    }
    private function _modal_checklists( $this_post, $activity ) {
        ?>
        <div class="reveal full" id="modal_checklists" data-v-offset="0" data-reveal>
            <h1>Checklists for <?php echo $this_post['title'] ?></h1>
            <hr>
            <div style="height: 800px"></div>
            <button class="close-button" data-close aria-label="Close modal" type="button">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php
    }
    private function _modal_reports( $this_post, $activity ) {
        ?>
        <div class="reveal full" id="modal_reports" data-v-offset="0" data-reveal>
            <h1>Practitioner Reports for <?php echo $this_post['title'] ?></h1>
            <hr>
            <div>
                <?php
                if ( ! empty( $activity ) ) {
                    foreach( $activity as $row ) {
                        if ( 'reports' === $row['type'] ) {
                            echo date( 'M d, Y h:i a', $row['time_end'] ) ?> | <strong><?php echo $row['log_key'] ?></strong><br><?php
                        }
                    }
                }
                ?>
            </div>
            <button class="close-button" data-close aria-label="Close modal" type="button">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php
    }
    private function _modal_genmap( $this_post, $activity ) {
        ?>
        <div class="reveal full" id="modal_genmap" data-v-offset="0" data-reveal>
            <h1>Current Genmap for <?php echo $this_post['title'] ?></h1>
            <hr>
            <div style="height: 800px"></div>
            <button class="close-button" data-close aria-label="Close modal" type="button">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php
    }

}
