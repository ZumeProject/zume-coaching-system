<?php
if ( !defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

class Zume_Funnel_Active extends Zume_Funnel_Chart_Base
{
    //slug and title of the top menu folder
    public $base_slug = 'active'; // lowercase
    public $slug = ''; // lowercase
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
        $this->base_title = __( 'Active Training', 'zume_funnels' );

        $url_path = dt_get_url_path( true );
        if ( "zume-funnel/$this->base_slug" === $url_path ) {
            add_action( 'wp_enqueue_scripts', [ $this, 'base_scripts' ], 99 );
            add_action( 'wp_head',[ $this, 'wp_head' ], 1000);
        }
    }

    public function wp_head() {
        $this->js_api();
        ?>
        <script src="https://cdn.amcharts.com/lib/5/index.js"></script>
        <script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
        <script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
        <style>
            #chartdiv {
                width: 100%;
                height: 800px;
            }
        </style>
        <script>
            jQuery(document).ready(function(){
                "use strict";
                let chart = jQuery('#chart')
                chart.empty().html(`
                        <div id="zume-funnel">
                            <div class="grid-x">
                                <div class="cell small-6"><h1>Active Training</h1></div>
                                <div class="cell small-6">
                                </div>
                            </div>
                            <hr>
                            <div class="grid-x grid-margin-x grid-margin-y">
                                 <div class="cell"><h2>Cumulative</h2></div>
                            </div>
                            <div class="grid-x">
                                <div class="cell total_active_training_trainee"><span class="loading-spinner active"></span></div>
                            </div>
                            <div class="grid-x grid-margin-x grid-margin-y">
                                 <div class="cell medium-6 no_coach"><span class="loading-spinner active"></span></div>
                                 <div class="cell medium-6 no_friends"><span class="loading-spinner active"></span></div>
                                 <div class="cell medium-6 no_updated_profiles"><span class="loading-spinner active"></span></div>
                            </div>
                            <hr>
                            <div class="grid-x">
                                <div class="cell center"><h1 id="range-title">Last 30 Days</h1></div>
                                <div class="cell small-6">
                                    <h2>Time Range</h2>
                                </div>
                                <div class="cell small-6" style="float: right;">
                                     <span>
                                        <select id="range-filter" class="z-range-filter">
                                            <option value="30">Last 30 days</option>
                                            <option value="7">Last 7 days</option>
                                            <option value="90">Last 90 days</option>
                                            <option value="365">Last 1 Year</option>
                                        </select>
                                    </span>
                                    <span class="loading-spinner active float-spinner"></span>
                                </div>
                            </div>
                            <div class="grid-x grid-margin-x grid-margin-y">
                                 <div class="cell medium-12 in_and_out"><span class="loading-spinner active"></span></div>
                            </div>
                            <div class="grid-x grid-margin-x grid-margin-y">
                                 <div class="cell medium-6 total_checkins"><span class="loading-spinner active"></span></div>
                                 <div class="cell medium-6 3_month_plans"><span class="loading-spinner active"></span></div>
                            </div>

                            <hr>
                            <h2>Completions Per Training Element</h2>
                            <div id="chartdiv"></div>
                        </div>
                    `)
                // totals
                window.spin_add()
                makeRequest('GET', 'total', { stage: "active_training_trainee", key: "total_active_training_trainee" }, window.site_info.rest_root ).done( function( data ) {
                    data.link = ''
                    data.label = 'Active Training Trainees'
                    data.description = 'People who are actively working a training plan or have only partially completed the training.'
                    jQuery('.'+data.key).html(window.template_hero_map_only(data))
                    window.click_listener( data )
                    window.spin_remove()
                })
                window.spin_add()
                makeRequest('GET', 'total', { stage: "active_training_trainee", key: "no_coach" }, window.site_info.rest_root ).done( function( data ) {
                    data.valence = 'valence-grey'
                    data.label = 'Has No Coach'
                    data.description = 'Description'
                    jQuery('.'+data.key).html(window.template_single_list(data))
                    window.click_listener( data )
                    window.spin_remove()
                })
                window.spin_add()
                makeRequest('GET', 'total', { stage: "active_training_trainee", key: "no_friends" }, window.site_info.rest_root ).done( function( data ) {
                    data.valence = 'valence-grey'
                    data.label = 'Has No Friends'
                    data.description = 'Description'
                    jQuery('.'+data.key).html(window.template_single_list(data))
                    window.click_listener( data )
                    window.spin_remove()
                })
                window.spin_add()
                makeRequest('GET', 'total', { stage: "active_training_trainee", key: "no_updated_profiles" }, window.site_info.rest_root ).done( function( data ) {
                    data.valence = 'valence-grey'
                    data.label = 'Has Not Updated Profile'
                    data.description = 'Description'
                    jQuery('.'+data.key).html(window.template_single_list(data))
                    window.click_listener( data )
                    window.spin_remove()
                })

                // range
                window.path_load = ( range ) => {
                    jQuery('.loading-spinner').addClass('active')

                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "active_training_trainee", key: "in_and_out", range: range }, window.site_info.rest_root ).done( function( data ) {
                        data.label = 'Active Training Flow'
                        jQuery('.'+data.key).html( window.template_in_out( data ) )
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "active_training_trainee", key: "total_checkins", range: range }, window.site_info.rest_root ).done( function( data ) {
                        data.label = 'Total Checkins'
                        jQuery('.'+data.key).html(window.template_single_map(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })
                    window.spin_add()
                    makeRequest('GET', 'total', { stage: "active_training_trainee", key: "3_month_plans", range: range }, window.site_info.rest_root ).done( function( data ) {
                        data.label = 'Post Training Plans'
                        data.description = 'Description'
                        jQuery('.'+data.key).html(window.template_single_map(data))
                        window.click_listener( data )
                        window.spin_remove()
                    })

                    makeRequest('GET', 'training_elements', { range: range }, window.site_info.rest_root ).done( function( data ) {
                        am5.ready(function() {
                            console.log(data)

                            am5.array.each(am5.registry.rootElements, function(root) {
                                if (root.dom.id == "chartdiv") {
                                    root.dispose();
                                }
                            });

                            if ( typeof root === 'undefined' ) {
                                var root = am5.Root.new("chartdiv");
                                root.setThemes([
                                    am5themes_Animated.new(root)
                                ]);

                                var chart = root.container.children.push(am5xy.XYChart.new(root, {
                                    panX: true,
                                    panY: true,
                                    wheelX: "panX",
                                    wheelY: "zoomX",
                                    pinchZoomX: true
                                }));

                                var cursor = chart.set("cursor", am5xy.XYCursor.new(root, {}));
                                cursor.lineY.set("visible", false);
                                var xRenderer = am5xy.AxisRendererX.new(root, { minGridDistance: 30 });
                                xRenderer.labels.template.setAll({
                                    rotation: -90,
                                    centerY: am5.p50,
                                    centerX: am5.p100,
                                    paddingRight: 15
                                });

                                xRenderer.grid.template.setAll({
                                    location: 1
                                })

                                var xAxis = chart.xAxes.push(am5xy.CategoryAxis.new(root, {
                                    maxDeviation: 0.3,
                                    categoryField: "label",
                                    renderer: xRenderer,
                                    tooltip: am5.Tooltip.new(root, {})
                                }));

                                var yAxis = chart.yAxes.push(am5xy.ValueAxis.new(root, {
                                    maxDeviation: 0.3,
                                    renderer: am5xy.AxisRendererY.new(root, {
                                        strokeOpacity: 0.1
                                    })
                                }));

                                var series = chart.series.push(am5xy.ColumnSeries.new(root, {
                                    name: "Series 1",
                                    xAxis: xAxis,
                                    yAxis: yAxis,
                                    valueYField: "value",
                                    sequencedInterpolation: true,
                                    categoryXField: "label",
                                    tooltip: am5.Tooltip.new(root, {
                                        labelText: "{valueY}"
                                    })
                                }));

                                series.columns.template.setAll({ cornerRadiusTL: 5, cornerRadiusTR: 5, strokeOpacity: 0 });

                                xAxis.data.setAll(data);
                                series.data.setAll(data);

                                series.appear(1000);
                                chart.appear(1000, 100);
                            }
                        })
                    })

                }
                window.setup_filter()

                window.click_listener = ( data ) => {
                    window.load_list(data)
                    window.load_map(data)
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

}
new Zume_Funnel_Active();
