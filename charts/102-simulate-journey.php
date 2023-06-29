<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Coaching_Test_Journey extends Zume_Coaching_Chart_Base
{
    //slug and title of the top menu folder
    public $base_slug = 'test_journey'; // lowercase
    public $slug = '';
    public $title;
    public $base_title;
    public $js_object_name = 'wp_js_object'; // This object will be loaded into the metrics.js file by the wp_localize_script.
    public $js_file_name = '/dt-metrics/groups/overview.js'; // should be full file name plus extension
    public $permissions = [ 'dt_all_access_contacts', 'view_project_metrics' ];

    public function __construct() {
        parent::__construct();
        if ( !$this->has_permission() ){
            return;
        }
        $this->base_title = __( 'simulate journey', 'zume_coaching' );

        $url_path = dt_get_url_path( true );
        if ( "zume-coaching/$this->base_slug" === $url_path ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'scripts' ], 99 );
            add_action( 'wp_head',[ $this, 'wp_head' ], 1000);
        }
    }

    public function base_menu( $content ) {
        $content .= '<li class=""><a href="'.site_url('/zume-coaching/'.$this->base_slug).'" id="'.$this->base_slug.'-menu">' .  $this->base_title . '</a></li>';
        return $content;
    }
    public function wp_head() {
        $this->js_api();
        ?>
        <style>
            .left-border {
                border-left: 1px solid #ddd;
            }
            .alt-color {
                background-color: #02b4e9;
            }
            .button.done {
                background-color: #39ea29;
            }
            .button.done:hover {
                background-color: #25951b;
            }
        </style>

        <script>
            window.site_url = '<?php echo site_url() ?>' + '/wp-json/zume_coaching/v1/'
            window.user_id = '<?php echo get_current_user_id() ?>'

            //  0 = Anonymous
            //  1 = Registrant
            //  2 = Active Training
            //  3 = Post-Training
            //  4 = S1 (Partial)
            //  5 = S2 (Complete)
            //  6 = S3 (Multiplying)


            jQuery(document).ready(function(){
                "use strict";
                let chart = jQuery('#chart')
                let user_list = `<?php echo $this->user_list() ?>`

                chart.empty().html(`
                        <div class="grid-x">
                            <div class="cell medium-3">
                                <h2>Log a User Journey</h2>
                            </div>
                            <div class="cell medium-9">
                                <select id="user_id" style=" width:150px;">
                                    <option value="${window.user_id}">Me</option>
                                   ${user_list}
                                </select>
                                <select id="days_ago" style=" width:150px;">
                                    <option value="0">today</option>
                                    <option value="3">3 Days Ago</option>
                                    <option value="7">7 Days Ago</option>
                                    <option value="10">10 Days Ago</option>
                                    <option value="14">14 Days Ago</option>
                                    <option value="18">18 Days Ago</option>
                                    <option value="24">24 Days Ago</option>
                                    <option value="30">30 Days Ago</option>
                                    <option value="60">60 Days Ago</option>
                                    <option value="90">90 Days Ago</option>
                                    <option value="100">100 Days Ago</option>
                                </select>
                                <span class="loading-spinner active"></span>
                            </div>
                            <div class="cell">
                                <hr>
                            </div>
                        </div>


                        <div class="grid-x grid-padding-x">

                            <div class="cell small-5">
                                <h2>KEY STEPS</h2>
                            </div>
                            <div class="cell small-5">
                                <h2>CTAs</h2>
                            </div>
                            <div class="cell small-2">

                            </div>
                            <div class="cell">
                                <hr>
                            </div>


                             <div class="cell">
                                <h2>Anonymous</h2>
                            </div>
                            <div class="cell small-5">
                                <button class="button large">Registered</button>
                            </div>
                            <div class="cell small-5 left-border">
                                <button class="button large alt-color">Coach Request</button>
                                <button class="button large alt-color">Joined Online Training</button>
                            </div>
                            <div class="cell small-2">

                            </div>
                             <div class="cell">
                                <hr>
                            </div>

                            <div class="cell">
                                <h2>Registrant</h2>
                            </div>
                            <div class="cell small-5">
                               <button class="button large">Made a Plan</button>
                            </div>
                            <div class="cell small-5 left-border">
                                <button class="button large alt-color">Coach Request</button>
                                <button class="button large alt-color">Set Profile</button>
                                <button class="button large alt-color">Invited Friends</button>
                                <button class="button large alt-color">Joined Online Training</button>
                            </div>
                            <div class="cell small-2">

                            </div>
                             <div class="cell">
                                <hr>
                            </div>


                            <div class="cell">
                                <h2>Active Training Trainee</h2>
                            </div>
                            <div class="cell small-5">
                               <button class="button large">Completed 25 of 32 Training Points</button>
                            </div>
                            <div class="cell small-5 left-border">
                                <button class="button large alt-color">Coach Request</button>
                                <button class="button large alt-color">Set Profile</button>
                                <button class="button large alt-color">Invited Friends</button>
                                <button class="button large alt-color">Made 3-Month Plan</button>
                            </div>
                            <div class="cell small-2">

                            </div>
                             <div class="cell">
                                <hr>
                            </div>


                            <div class="cell">
                                <h2>Post-Training Trainee</h2>
                            </div>
                            <div class="cell small-5">
                               <button class="button large">Made First Lifestyle Report</button>
                            </div>
                            <div class="cell small-5 left-border">
                                <button class="button large alt-color">Coach Request</button>
                                <button class="button large alt-color">Set Profile</button>
                                <button class="button large alt-color">Completed 3-Month Plan</button>
                            </div>
                            <div class="cell small-2">

                            </div>
                             <div class="cell">
                                <hr>
                            </div>


                            <div class="cell">
                                <h2>Stage 1 - Partial Practitioner</h2>
                            </div>
                            <div class="cell small-5">
                               <button class="button large">Completed MAWL Checklist</button>
                            </div>
                            <div class="cell small-5 left-border">
                                <button class="button large alt-color">Coach Request</button>
                                <button class="button large alt-color">Report Churches</button>
                                <button class="button large alt-color">Join Affinity Hub</button>
                            </div>
                            <div class="cell small-2">

                            </div>
                             <div class="cell">
                                <hr>
                            </div>

                            <div class="cell">
                                <h2>Stage 2 - Completed Practitioner</h2>
                            </div>
                            <div class="cell small-5">
                               <button class="button large">Seeing Generational Fruit</button>
                            </div>
                            <div class="cell small-5 left-border">
                                <button class="button large alt-color">Coach Request</button>
                                <button class="button large alt-color">Report Churches</button>
                                <button class="button large alt-color">Join Affinity Hub</button>
                            </div>
                            <div class="cell small-2">

                            </div>
                             <div class="cell">
                                <hr>
                            </div>


                            <div class="cell">
                                <h2>Stage 3 - Multiplying Practitioner</h2>
                            </div>
                            <div class="cell small-5">
                            </div>
                            <div class="cell small-5 left-border">
                                <button class="button large alt-color">Coach Request</button>
                                <button class="button large alt-color">Report Churches</button>
                                <button class="button large alt-color">Coach Others</button>
                                <button class="button large alt-color">Join Affinity Hub</button>
                                <button class="button large alt-color">Provide Hub Leadership</button>
                            </div>
                            <div class="cell small-2">

                            </div>
                             <div class="cell">
                                <hr>
                            </div>


                        </div>
                    </div>
                    `)

                jQuery('.button').on('click', function(event) {
                    event.preventDefault()
                    jQuery('.loading-spinner').addClass('active')
                    const data = {
                        "user_id": jQuery('#user_id').val(),
                        "days_ago": jQuery('#days_ago').val(),
                        "action": jQuery(this).text()
                    }
                    jQuery(this).addClass('done')
                    makePostRequest('POST', 'journey_log', data)
                        .then((response) => {
                            console.log(response)
                            jQuery('.loading-spinner').removeClass('active')
                        })
                        .catch((error) => {
                            console.log(error)
                            jQuery('.loading-spinner').removeClass('active')
                        })
                })

                jQuery('.loading-spinner').removeClass('active')

                function makePostRequest(type, url, data, base = "zume_coaching/v1/") {
                    //make sure base has a trailing slash if url does not start with one
                    if ( !base.endsWith('/') && !url.startsWith('/')){
                        base += '/'
                    }
                    const options = {
                        type: type,
                        contentType: "application/json; charset=utf-8",
                        dataType: "json",
                        url: url.startsWith("http") ? url : `${wpApiShare.root}${base}${url}`,
                        beforeSend: (xhr) => {
                            xhr.setRequestHeader("X-WP-Nonce", wpApiShare.nonce);
                        },
                    };

                    if (data && !window.lodash.isEmpty(data)) {
                        options.data = type === "GET" ? data : JSON.stringify(data);
                    }

                    return jQuery.ajax(options);
                }
            })
        </script>
        <?php
    }

    public function data() {
        return [
            'translations' => [
                'title_overview' => __( 'Project Overview', 'zume_coaching' ),
            ],
        ];
    }

    public function user_list() {
        global $wpdb;
        $users = $wpdb->get_results(
            "SELECT ID, display_name
                    FROM $wpdb->users
                    JOIN $wpdb->usermeta ON $wpdb->users.ID = $wpdb->usermeta.user_id AND $wpdb->usermeta.meta_key = 'wp_3_capabilities'
                    ", ARRAY_A
        );

        $html = '';
        foreach( $users as $user ) {
            $html .= '<option value="' . $user['ID'] . '">' . $user['display_name'] . '</option>';
        }

        return $html;

    }
}
new Zume_Coaching_Test_Journey();
