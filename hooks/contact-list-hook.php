<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.


add_action( 'dt_post_list_filters_sidebar', function( $post_type ) {

    if ( 'contacts' === $post_type && false ) {
        ?>
        <br>
        <div class="bordered-box">
            <div class="section-header">
                Zúme Tasks
                <button class="float-right" data-open="export_help_text">
                    <img class="help-icon"  style="padding-left: 5px;" src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/help.svg' ) ?>" alt="help"/>
                </button>
                <button class="section-chevron chevron_down">
                    <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_down.svg' ) ?>"/>
                </button>
                <button class="section-chevron chevron_up">
                    <img src="<?php echo esc_html( get_template_directory_uri() . '/dt-assets/images/chevron_up.svg' ) ?>"/>
                </button>
            </div>
            <div class="section-body" style="padding-top:1em;">
                Add a New Trainee to the System
            </div>
        </div>
        
        <?php
       
    }

}, 10, 1 );