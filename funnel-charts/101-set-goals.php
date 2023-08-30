<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Funnel_Set_Goals extends Zume_Funnel_Chart_Base
{
    //slug and title of the top menu folder
    public $base_slug = 'reporting_goals'; // lowercase
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
        $this->base_title = __( 'Manage Goals', 'zume_funnels' );

        $url_path = dt_get_url_path( true );
        if ( "zume-funnel/$this->base_slug" === $url_path ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'base_scripts' ], 99 );
            add_action( 'wp_head',[ $this, 'wp_head' ], 1000);
        }
    }

    public function base_menu( $content ) {
        if ( user_can( get_current_user_id(), 'manage_options' ) ) {
            $content .= '<li class=""><hr>ADMIN</li>';
            $content .= '<li class=""><a href="'.site_url('/zume-funnel/'.$this->base_slug).'" id="'.$this->base_slug.'-menu">' .  $this->base_title . '</a></li>';
        }
        return $content;
    }
    public function wp_head() {
        $this->js_api();
        ?>
        <script>
            window.site_url = '<?php echo site_url() ?>' + '/wp-json/zume_funnel/v1/'
            window.user_id = '<?php echo get_current_user_id() ?>'

            //  0 = Anonymous
            //  1 = Registrant
            //  2 = Active Training
            //  3 = Post-Training
            //  4 = S1 (Partial)
            //  5 = S2 (Complete)
            //  6 = S3 (Multiplying)

            let data = [



                // Anonymous
                {
                    "value": 0,
                    "subtype": "got_a_coach",
                    "description": "Get a Coach Form (from an anonymous anonymous)"
                },
                {
                    "value": 0,
                    "subtype": "online_training",
                    "description": "Join Online Training (from an anonymous anonymous)"
                },
                {
                    "value": 0,
                    "subtype": "registration",
                    "description": "New Registration"
                },
                {
                    "value": 0,
                    "subtype": "studying",
                    "description": "Studying a concept in Zúme independently on a pieces page."
                },



                // Registrant
                {
                    "value": 1,
                    "subtype": "made_a_plan",
                    "description": "Made a plan to start a training"
                },
                {
                    "value": 1,
                    "subtype": "got_a_coach",
                    "description": "Get a Coach Form (from a pre-training person)"
                },
                {
                    "value": 1,
                    "subtype": "updated_location",
                    "description": "Updated location in user profile"
                },
                {
                    "value": 1,
                    "subtype": "invited_friends",
                    "description": "Invited friends to join Zúme"
                },



                // Active Training
                {
                    "value": 2,
                    "subtype": "got_a_coach",
                    "description": "Get a Coach Form (from a pre-training person)"
                },
                {
                    "value": 2,
                    "subtype": "updated_location",
                    "description": "Updated location in user profile"
                },
                {
                    "value": 2,
                    "subtype": "invited_friends",
                    "description": "Invited friends to join Zúme"
                },
                {
                    "value": 2,
                    "subtype": "check_in",
                    "description": "Check-in to a training."
                },
                {
                    "value": 2,
                    "subtype": "training_item_01",
                    "description": "God Uses Ordinary People"
                },
                {
                    "value": 2,
                    "subtype": "training_item_02",
                    "description": "Simple Definition of Disciple and Church"
                },
                {
                    "value": 2,
                    "subtype": "training_item_03",
                    "description": "Spiritual Breathing is Hearing and Obeying God"
                },
                {
                    "value": 2,
                    "subtype": "training_item_04",
                    "description": "SOAPS Bible Reading"
                },
                {
                    "value": 2,
                    "subtype": "training_item_05",
                    "description": "Accountability Groups"
                },
                {
                    "value": 2,
                    "subtype": "training_item_06",
                    "description": "Consumer vs Producer Lifestyle"
                },
                {
                    "value": 2,
                    "subtype": "training_item_07",
                    "description": "How to Spend an Hour in Prayer"
                },
                {
                    "value": 2,
                    "subtype": "training_item_08",
                    "description": "Relational Stewardship – List of 100"
                },
                {
                    "value": 2,
                    "subtype": "training_item_09",
                    "description": "The Kingdom Economy"
                },
                {
                    "value": 2,
                    "subtype": "training_item_10",
                    "description": "The Gospel and How to Share It"
                },
                {
                    "value": 2,
                    "subtype": "training_item_11",
                    "description": "Baptism and How To Do It"
                },
                {
                    "value": 2,
                    "subtype": "training_item_12",
                    "description": "Prepare Your 3-Minute Testimony"
                },
                {
                    "value": 2,
                    "subtype": "training_item_13",
                    "description": "Vision Casting the Greatest Blessing"
                },
                {
                    "value": 2,
                    "subtype": "training_item_14",
                    "description": "Duckling Discipleship – Leading Immediately"
                },
                {
                    "value": 2,
                    "subtype": "training_item_15",
                    "description": "Eyes to See Where the Kingdom Isn’t"
                },
                {
                    "value": 2,
                    "subtype": "training_item_16",
                    "description": "The Lord’s Supper and How To Lead It"
                },
                {
                    "value": 2,
                    "subtype": "training_item_17",
                    "description": "Prayer Walking and How To Do It"
                },
                {
                    "value": 2,
                    "subtype": "training_item_18",
                    "description": "A Person of Peace and How To Find One"
                },
                {
                    "value": 2,
                    "subtype": "training_item_19",
                    "description": "The BLESS Prayer Pattern"
                },
                {
                    "value": 2,
                    "subtype": "training_item_20",
                    "description": "Faithfulness is Better Than Knowledge"
                },
                {
                    "value": 2,
                    "subtype": "training_item_21",
                    "description": "3/3 Group Meeting Pattern"
                },
                {
                    "value": 2,
                    "subtype": "training_item_22",
                    "description": "Training Cycle for Maturing Disciples"
                },
                {
                    "value": 2,
                    "subtype": "training_item_23",
                    "description": "Leadership Cells"
                },
                {
                    "value": 2,
                    "subtype": "training_item_24",
                    "description": "Expect Non-Sequential Growth"
                },
                {
                    "value": 2,
                    "subtype": "training_item_25",
                    "description": "Pace of Multiplication Matters"
                },
                {
                    "value": 2,
                    "subtype": "training_item_26",
                    "description": "Always Part of Two Churches"
                },
                {
                    "value": 2,
                    "subtype": "training_item_27",
                    "description": "Coaching Checklist"
                },
                {
                    "value": 2,
                    "subtype": "training_item_28",
                    "description": "Leadership in Networks"
                },
                {
                    "value": 2,
                    "subtype": "training_item_29",
                    "description": "Peer Mentoring Groups"
                },
                {
                    "value": 2,
                    "subtype": "training_item_30",
                    "description": "Four Fields Tool"
                },
                {
                    "value": 2,
                    "subtype": "training_item_31",
                    "description": "Generational Mapping"
                },



                // Post-Training
                {
                    "value": 3,
                    "subtype": "three_month_plan",
                    "description": "Three-Month Plan"
                },
                {
                    "value": 3,
                    "subtype": "new_report",
                    "description": "New Report"
                },
                {
                    "value": 3,
                    "subtype": "got_a_coach",
                    "description": "Get a Coach Form (from a pre-training person)"
                },



                // S1 (Partial)
                {
                    "value": 4,
                    "subtype": "new_report",
                    "description": "New Report"
                },
                {
                    "value": 4,
                    "subtype": "got_a_coach",
                    "description": "Get a Coach Form (from a pre-training person)"
                },



                // S2 (Complete)
                {
                    "value": 5,
                    "subtype": "new_report",
                    "description": "New Report"
                },
                {
                    "value": 5,
                    "subtype": "got_a_coach",
                    "description": "Get a Coach Form (from a pre-training person)"
                },



                // S3 (Multiplying)
                {
                    "value": 6,
                    "subtype": "new_report",
                    "description": "New Report"
                },
                {
                    "value": 6,
                    "subtype": "got_a_coach",
                    "description": "Get a Coach Form (from a pre-training person)"
                },

            ]

            jQuery(document).ready(function(){
                "use strict";
                let chart = jQuery('#chart')

                chart.empty().html(`
                        <div id="zume-funnel">

                            <span class="loading-spinner active"></span>
                            <table class="hover" id="datatable">
                                <thead>
                                    <tr>
                                        <th style="min-width:150px;">Value/Stage</th>
                                        <th>Subtype</th>
                                        <th>Description</th>
                                        <th>Goal</th>
                                        <th>
                                            <select id="days_ago" style="float:left; width:60%;">
                                                <option value="0">today</option>
                                                <option value="3">3 Days Ago</option>
                                                <option value="7">7 Days Ago</option>
                                                <option value="10">10 Days Ago</option>
                                                <option value="14">14 Days Ago</option>
                                                <option value="18">18 Days Ago</option>
                                                <option value="24">24 Days Ago</option>
                                                <option value="30">30 Days Ago</option>
                                            </select>
                                            <select id="user_id" style="float:left; width:35%;">
                                                <option value="${window.user_id}">Me</option>
                                               <?php echo $this->user_list() ?>
                                            </select>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="zume-report-types"></tbody>
                            </table>
                            <div class="grid-x"><div class="cell small-6">
                                <u>Report Table Columns</u><br>
                                <strong>'id' bigint(22) unsigned NOT NULL AUTO_INCREMENT,</strong><br>
                                <strong>'user_id' bigint(22) DEFAULT NULL,</strong><br>
                                'parent_id' bigint(22) DEFAULT NULL,<br>
                                'post_id' bigint(22) DEFAULT NULL,<br>
                                'post_type' varchar(20) COLLATE DEFAULT NULL,<br>
                                <strong>'type' varchar(100) COLLATE NOT NULL,</strong><br>
                                <strong>'subtype' varchar(100) COLLATE DEFAULT NULL,</strong><br>
                                'payload' longtext COLLATE,<br>
                                <strong>'value' bigint(22) NOT NULL DEFAULT '0',</strong><br>
                                <strong>'lng' float DEFAULT NULL,</strong><br>
                                <strong>'lat' float DEFAULT NULL,</strong><br>
                                <strong>'level' varchar(100) COLLATE DEFAULT NULL,</strong><br>
                                <strong>'label' varchar(255) COLLATE DEFAULT NULL,</strong><br>
                                <strong>'grid_id' bigint(22) DEFAULT NULL,</strong><br>
                                'time_begin' int(11) DEFAULT NULL,<br>
                                <strong>'time_end' int(11) DEFAULT NULL,</strong><br>
                                <strong>'timestamp' int(11) NOT NULL,</strong><br>
                                <strong>'hash' varchar(65) COLLATE DEFAULT NULL,</strong><br>
                            </div>
                            <div class="cell small-6">
                                0 = Anonymous<br>
                                1 = Registrant<br>
                                2 = Active Training<br>
                                3 = Post-Training<br>
                                4 = S1 (Partial)<br>
                                5 = S2 (Complete)<br>
                                6 = S3 (Multiplying)<br>
                            </div>
                            </div>

                        </div>
                    `)
                window.stage = {
                    0: 'Anonymous',
                    1: 'Registrant',
                    2: 'Active Training',
                    3: 'Post-Training',
                    4: 'S1 (Partial)',
                    5: 'S2 (Complete)',
                    6: 'S3 (Multiplying)',
                }
                jQuery.each( data, function( key, value ) {
                    jQuery('.zume-report-types').append(`
                            <tr>
                                <td>${'(' +value.value + ') ' + window.stage[value.value] }</td>
                                <td style="font-weight:bold">${value.subtype}</td>
                                <td>${value.description}</td>
                                <td><input type="text" /></td>
                                <td><button class="button small add" data-type="zume" data-subtype="${value.subtype}" data-value="${value.value}" disabled>+</button></td>
                           </tr>
                        `)
                })

                let table = new DataTable('#datatable', {
                    "paging":   false,
                    "ordering": true,
                    "info":     false,
                    "searching": true,
                    "columnDefs": [
                        { "width": "10%", "targets": 0 },
                        { "width": "10%", "targets": 1 },
                        { "width": "40%", "targets": 2 },
                        { "width": "20%", "targets": 3 },
                        { "width": "20%", "targets": 4 },
                    ]
                });

                makeRequest('GET', 'location', {  }, window.site_info.rest_root ).done( function( data ) {
                    // console.log(data)
                    window.user_location = data

                    let buttons = jQuery('.button.add')
                    buttons.on('click', function(){
                        let subtype = jQuery(this).data('subtype')
                        let value = jQuery(this).data('value')
                        let days_ago = jQuery('#days_ago').val()
                        let user_id = jQuery('#user_id').val()

                        let log_data = {
                            type: 'zume',
                            subtype: subtype,
                            value: value,
                            grid_id: data.grid_id,
                            label: data.label,
                            lat: data.lat,
                            lng: data.lng,
                            level: data.level,
                            user_id: user_id,
                            days_ago: days_ago,
                        }
                        console.log(log_data)
                        makePostRequest( 'POST', 'log', log_data ).then( (response) => {
                            console.log(response)

                        })
                    })

                    buttons.removeAttr('disabled')
                    jQuery('.loading-spinner').removeClass('active')
                })

                function makePostRequest(type, url, data, base = "zume_funnel/v1/") {
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
                'title_overview' => __( 'Project Overview', 'zume_funnels' ),
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
new Zume_Funnel_Set_Goals();
