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

        $profile = zume_get_user_profile( $this_post['trainee_user_id'] );
        $activity = zume_user_log( $trainee_user_id );
        if ( empty( $activity ) ) {
           $activity = [];
        }

        ?>
        <div class="cell small-12 medium-4">
            <button class="button expanded" id="open_localized_vision" data-open="modal_localized_vision">Localized Vision</button>
            <button class="button expanded" data-open="modal_reports">Practitioner Reports</button>
            <button class="button expanded" data-open="modal_genmap">Current Genmap</button>
        </div>
        <?php

        // Modals
        self::_modal_localized_vision( $this_post, $activity, $profile );
        self::_modal_reports( $this_post, $activity );
        self::_modal_genmap( $this_post, $activity );

    }
    private function _modal_localized_vision( $this_post, $activity, $profile ) {

        ?>
        <div class="reveal full" id="modal_localized_vision" data-v-offset="0" data-reveal>
            <h1>Localized Vision for <?php echo $this_post['title'] ?></h1>
            <hr>
            <div class="grid-x">
                <div class="cell medium-6">
                    <div id="vision_map map-wrapper">
                        <div id='vision_map'><span class="localized_vision loading-spinner active"></span></div>
                    </div>
                </div>
                <div class="cell medium-6">
                    <span class="localized_vision loading-spinner active"></span>
                </div>
            </div>
            <button class="close-button" data-close aria-label="Close modal" type="button">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <script>
            jQuery(document).ready(function(){
                let vision_height = window.innerHeight - 125;
                jQuery('#vision_map').css('height', vision_height + 'px').css('width', '100%' );

                jQuery('#open_localized_vision').on('click', function() {

                    let lat = <?php echo $profile['location']['lat'] ?? 0 ?>;
                    let lng = <?php echo $profile['location']['lng'] ?? 0 ?>;
                    let level = '<?php echo $profile['location']['level'] ?? 'admin0' ?>';
                    console.log('lat', lat, 'lng', lng, 'level', level);

                    let zoom = 6
                    if ( 'admin0' === level ){
                        zoom = 3
                    } else if ( 'admin1' === level ) {
                        zoom = 4
                    } else if ( 'admin2' === level ) {
                        zoom = 5
                    }

                    window.mapboxgl.accessToken = window.dtMapbox.map_key;
                    var vision_map = new window.mapboxgl.Map({
                        container: 'vision_map',
                        style: 'mapbox://styles/mapbox/streets-v11',
                        center: [lng, lat],
                        minZoom: 1,
                        zoom: zoom
                    });

                    vision_map.on( 'load', (event) => {
                        vision_map.resize();

                        var vision_marker = new window.mapboxgl.Marker()
                            .setLngLat([lng, lat])
                            .addTo(vision_map);
                    })


                    jQuery('.localized_vision.loading-spinner').removeClass('active');

                    console.log('clicked')
                })
            })
        </script>
        <?php
    }
    private function _modal_reports( $this_post, $activity ) {
        ?>
        <div class="reveal" id="modal_reports" data-v-offset="0" data-reveal>
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
    private function _modal_genmap( $this_post, $profile ) {
        Zume_User_Genmap::instance()->modal( $this_post, $this_post['trainee_user_id'] );
    }
}

class Zume_Tile_System_API {
    public $permissions = ['access_contacts'];
    public $namespace = 'zume_simulator/v1';
    private static $_instance = null;
    public static function instance(){
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct(){
        if ( dt_is_rest() ) {
            add_action( 'rest_api_init', [ $this, 'add_api_routes' ] );
            add_filter( 'dt_allow_rest_access', [ $this, 'authorize_url' ], 10, 1 );
        }
    }
    public function add_api_routes() {
        $namespace = $this->namespace;

        register_rest_route(
            $namespace, '/geojson/localized_vision', [
                'methods'  => [ 'GET', 'POST' ],
                'callback' => [ $this, 'geojson_localized_vision' ],
                'permission_callback' => function () {
                    return dt_has_permissions($this->permissions);
                }
            ]
        );
    }
    public function geojson_localized_vision( $request ) {
        // get the user_id and the grid_id and confirm the locations within the flat grid
        // take the boundaries of the flat grid id and expand them out 111km in each direction
        // then use those new boundaries to get all boundaries within that new wider boundary of the flat grid
        // then reduced the list to the flat grid_id list

        // get boundaries for
        // get user id
        // get location & grid_id
        // get vision counts and saturation data for grid_id
        // get near neighbors for polygons/neighbor data
        $data = dt_recursive_sanitize_array( $request->get_params() );
        $geojson = [
            'type' => 'FeatureCollection',
            'features' => []
        ];

        if( ! isset( $data['user_id'] ) ) {
            return $geojson;
        }

        $user_id = $data['user_id'];


        $geojson['features'][] = [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [ $lng, $lat ]
            ],
            'properties' => [
                'title' => 'Localized Vision',
                'description' => 'This is the localized vision for this location',
                'marker-symbol' => 'marker'
            ]
        ];
        return $geojson;
    }
    public function authorize_url( $authorized ){
        if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), $this->namespace  ) !== false ) {
            $authorized = true;
        }
        return $authorized;
    }

}
Zume_Tile_System_API::instance();

class Zume_User_Genmap {
    private static $_instance = null;
    public static function instance(){
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function modal( $this_post, $user_id ) {
        ?>
        <div class="reveal full" id="modal_genmap" data-v-offset="0" data-reveal>
            <h1>Current Genmap for <?php echo $this_post['title'] ?></h1>
            <hr>
            <div class="grid-x grid-padding-x">
                <div class="cell medium-9">
                    <div id="genmap" style="width: 100%; border: 1px solid lightgrey; overflow:scroll;"></div>
                </div>
                <div class="cell medium-3">
                    <div id="genmap-details"></div>
                </div>
            </div>
            <button class="close-button" data-close aria-label="Close modal" type="button">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <script>
            jQuery(document).ready(function(){
                window.group_tree = [<?php echo json_encode( $this->tree( $user_id ) ) ?>][0]
                console.log(window.group_tree)

                let container = jQuery('#genmap')
                container.empty()

                var nodeTemplate = function(data) {
                    return `
                    <div class="title" data-item-id="${data.id}">${data.name}</div>
                    <div class="content">${data.content}</div>
                  `;
                };

                container.orgchart({
                    'data': window.group_tree,
                    'nodeContent': 'content',
                    'direction': 'l2r',
                    'nodeTemplate': nodeTemplate,
                });

                let container_height = window.innerHeight - 200 // because it is rotated
                container.height(container_height)

                container.off('click', '.node' )
                container.on('click', '.node', function () {
                    let node = jQuery(this)
                    let node_id = node.attr('id')
                    open_modal_details(node_id, post_type)
                })
            })
        </script>
        <?php
    }
    public function tree( $user_id ) {
        $query = $this->get_query( $user_id );
        return $this->get_genmap( $query  );
    }
    public function get_query( $user_id ) {
        global $wpdb;
        $key = 'user-'.$user_id;
        $query = $wpdb->get_results( $wpdb->prepare ( "
                    SELECT
                      a.ID         as id,
                      0            as parent_id,
                      a.post_title as name
                    FROM wp_posts as a
					LEFT JOIN wp_postmeta pm ON pm.post_id=a.ID AND pm.meta_key = 'assigned_to' AND pm.meta_value = %s
                    WHERE a.post_type = 'groups'
                    AND a.ID NOT IN (
                      SELECT DISTINCT (p2p_from)
                      FROM wp_p2p
                      WHERE p2p_type = 'groups_to_groups'
                      GROUP BY p2p_from
                    )
                    AND a.ID IN (
                      SELECT DISTINCT (p2p_to)
                      FROM wp_p2p
                      WHERE p2p_type = 'groups_to_groups'
                      GROUP BY p2p_to
                    )
					AND pm.meta_value IS NOT NULL
                    UNION
                    SELECT
                      p.p2p_from  as id,
                      p.p2p_to    as parent_id,
                      (SELECT sub.post_title FROM wp_posts as sub WHERE sub.ID = p.p2p_from ) as name
                    FROM wp_p2p as p
					LEFT JOIN wp_postmeta pm2 ON pm2.post_id=p.p2p_from AND pm2.meta_key = 'assigned_to' AND pm2.meta_value = %s
                    WHERE p.p2p_type = 'groups_to_groups'
					AND pm2.meta_value IS NOT NULL;
                ", $key, $key ), ARRAY_A );

        return $query;
    }

    public function get_genmap( $query ) {

        if ( is_wp_error( $query ) ){
            return $this->_circular_structure_error( $query );
        }
        if ( empty( $query ) ) {
            return $this->_no_results();
        }
        $menu_data = $this->prepare_menu_array( $query );
        return $this->build_array( 0, $menu_data, 0 );
    }
    public function prepare_menu_array( $query ) {
        // prepare special array with parent-child relations
        $menu_data = array(
            'items' => array(),
            'parents' => array()
        );

        foreach ( $query as $menu_item )
        {
            $menu_data['items'][$menu_item['id']] = $menu_item;
            $menu_data['parents'][$menu_item['parent_id']][] = $menu_item['id'];
        }
        return $menu_data;
    }
    public function build_array( $parent_id, $menu_data, $gen ) {
        $children = [];
        if ( isset( $menu_data['parents'][$parent_id] ) )
        {
            $next_gen = $gen + 1;
            foreach ( $menu_data['parents'][$parent_id] as $item_id )
            {
                $children[] = $this->build_array( $item_id, $menu_data, $next_gen );
            }
        }
        $array = [
            'id' => $parent_id,
            'name' => $menu_data['items'][ $parent_id ]['name'] ?? 'SYSTEM' ,
            'content' => 'Gen ' . $gen ,
            'children' => $children,
        ];
        return $array;
    }
    public function _no_results() {
        return '<p>'. esc_attr__( 'No Results', 'disciple_tools' ) .'</p>';
    }
    public function _circular_structure_error( $wp_error ) {
        $link = false;
        $data = $wp_error->get_error_data();

        if ( isset( $data['record'] ) ){
            $link = "<a target='_blank' href=" . get_permalink( $data['record'] ) . '>Open record</a>';
        }
        return '<p>' . esc_html( $wp_error->get_error_message() ) . ' ' . $link . '</p>';
    }
}
Zume_User_Genmap::instance();
