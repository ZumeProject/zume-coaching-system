jQuery(document).ready(function(){
  let chartDiv = jQuery('#chart')

  window.activity_list = {}
  window.activity_geojson = {
    "type": "FeatureCollection",
    "features": []
  }
  window.activity_empty_geojson = {
    "type": "FeatureCollection",
    "features": []
  }


  // Add html and map
  chartDiv.empty().html(`
      <style>
          #activity-wrapper {
              height: ${window.innerHeight}px !important;
              overflow: scroll;
          }
          #activity-list{
              height: ${window.innerHeight}px !important;
              overflow: scroll;
          }
          #activity-list li {
              font-size:.8em;
              list-style-type: none;
          }
          #activity-list h2 {
              font-size:1.2em;
              font-weight:bold;
          }
          #map-wrapper {
              height: ${window.innerHeight}px !important;
          }
          #map {
              height: ${window.innerHeight}px !important;
          }
         #map-header {
              position: absolute;
              top:10px;
              left:10px;
              z-index: 20;
              background-color: white;
              padding:1em;
              opacity: 0.8;
              border-radius: 5px;
          }
          #map-spinner {
              position: absolute;
              top:50%;
              left:50%;
              z-index: 50;
          }
          @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .loading-spinner-map-center.active {
            border-radius: 50%;
            width: 24px;
            height: 24px;
            border: 0.25rem solid #919191;
            border-top-color: black;
            animation: spin 1s infinite linear;
            display: inline-block;
        }
      </style>
      <div class="grid-x">
        <div class="medium-9 cell">
            <div id="map-wrapper">
                <div id='map'></div>
                <div id="map-header"><h3>${jsObject.translation.title}</h3></div>
                <div id="map-spinner" class="loading-spinner-map-center active"></div>
            </div>
        </div>
        <div class="medium-3 cell" style="padding:.2em;">
            <div class="loading-spinner"></div>
            <div id="activity-wrapper">
                <ul id="activity-list"></ul>
            </div>
        </div>
      </div>
  `)
  let container = jQuery('#activity-list');

  mapboxgl.accessToken = jsObject.map_key;
  var map = new mapboxgl.Map({
    container: 'map',
    style: 'mapbox://styles/discipletools/cl1qp8vuf002l15ngm5a7up59',
    center: [-98, 38.88],
    minZoom: 1,
    maxZoom: 15,
    zoom: 1
  });

  // disable map rotation using right click + drag
  map.dragRotate.disable();
  map.touchZoomRotate.disableRotation();

  map.addControl(
    new MapboxGeocoder({
      accessToken: mapboxgl.accessToken,
      mapboxgl: mapboxgl
    })
  );

  map.on('load', function() {
    load_empty_cluster_map()
    load_positive_cluster_map()
  });

  map.on('zoomend', function(e){
    load_map_activity()
  })
  map.on('dragend', function(e){
    load_map_activity()
  })

  function load_positive_cluster_map() {
    map.addSource('layer-source-contacts', {
      type: 'geojson',
      data: window.activity_geojson,
      cluster: true,
      clusterMaxZoom: 20,
      clusterRadius: 50
    });
    map.addLayer({
      id: 'clusters',
      type: 'circle',
      source: 'layer-source-contacts',
      filter: ['has', 'point_count'],
      paint: {
        'circle-color': [
          'step',
          ['get', 'point_count'],
          '#00d9ff',
          20,
          '#00aeff',
          150,
          '#90C741'
        ],
        'circle-radius': [
          'step',
          ['get', 'point_count'],
          20,
          100,
          30,
          750,
          40
        ]
      }
    });
    map.addLayer({
      id: 'cluster-count-contacts',
      type: 'symbol',
      source: 'layer-source-contacts',
      filter: ['has', 'point_count'],
      layout: {
        // 'text-field': '{point_count_abbreviated}',
        'text-font': ['DIN Offc Pro Medium', 'Arial Unicode MS Bold'],
        'text-size': 12
      }
    });
    map.addLayer({
      id: 'unclustered-point-contacts',
      type: 'circle',
      source: 'layer-source-contacts',
      filter: ['!', ['has', 'point_count']],
      paint: {
        'circle-color': '#00d9ff',
        'circle-radius':20,
        'circle-stroke-width': 1,
        'circle-stroke-color': '#fff'
      }
    });
  }
  function load_empty_cluster_map() {
    map.addSource('layer-empty-item', {
      type: 'geojson',
      data: window.activity_empty_geojson,
      cluster: true,
      clusterMaxZoom: 20,
      clusterRadius: 50
    });
    map.addLayer({
      id: 'clusters-empty',
      type: 'circle',
      source: 'layer-empty-item',
      filter: ['has', 'point_count'],
      paint: {
        'circle-color': [
          'step',
          ['get', 'point_count'],
          '#a9a9a9',
          20,
          '#a9a9a9',
          150,
          '#d6d6d6'
        ],
        'circle-radius': [
          'step',
          ['get', 'point_count'],
          10,
          100,
          20,
          750,
          20
        ]
      }
    });
    map.addLayer({
      id: 'cluster-count-empty',
      type: 'symbol',
      source: 'layer-empty-item',
      filter: ['has', 'point_count'],
      layout: {
        'text-field': '',
        'text-font': ['DIN Offc Pro Medium', 'Arial Unicode MS Bold'],
        'text-size': 12
      }
    });
    map.addLayer({
      id: 'unclustered-point-empty',
      type: 'circle',
      source: 'layer-empty-item',
      filter: ['!', ['has', 'point_count']],
      paint: {
        'circle-color': '#a9a9a9',
        'circle-radius':20,
        'circle-stroke-width': 1,
        'circle-stroke-color': '#fff'
      }
    });
  }

  function load_geojson(){
    let spinner = jQuery('.loading-spinner-map-center')
    spinner.addClass('active')
    let data = get_filters()
    window.post_request('load_geojson', data )
      .done( data => {
        console.log(data)
        "use strict";
        window.activity_geojson = data

        var mapSource = map.getSource('layer-source-contacts');
        if( typeof mapSource !== 'undefined') {
          map.getSource('layer-source-contacts').setData(window.activity_geojson);
        }

        jQuery('.loading-spinner-map-center').removeClass('active')

      })
  }
  load_geojson()
  function load_empty_geojson(){
    let data = get_filters()
    window.post_request('load_empty_geojson', data )
      .done( data => {
        console.log(data)
        "use strict";
        window.activity_empty_geojson = data

        var mapSource= map.getSource('layer-empty-item');
        if( typeof mapSource !== 'undefined') {
          map.getSource('layer-empty-item').setData(window.activity_empty_geojson);
        }

      })
  }
  load_empty_geojson()

  window.activity_timer_id = '';
  function load_map_activity() {
    let spinner = jQuery('.loading-spinner')
    spinner.addClass('active')

    clear_timer()

    // set timer
    window.activity_timer_id = setTimeout(function(){
      run_activity()
    }, 700);

  }
  function clear_timer() {
    clearTimeout(window.activity_timer_id);
  }

  function run_activity() {
    container.empty()
    let spinner = jQuery('.loading-spinner')
    spinner.addClass('active')
    let data = get_filters()

    if ( data.zoom > 5 ) {
      window.post_request('activity_list', data )
        .done( data => {
          let spinner = jQuery('.loading-spinner')
          console.log(data)
          "use strict";
          window.activity_list = data
          update_activity_list()
          spinner.removeClass('active')
        })
    }
    else {
      spinner.removeClass('active')
    }
  }
  load_map_activity()

  function get_filters() {
    let current_time_zone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/Chicago'
    window.current_bounds = map.getBounds()
    return {
      bounds: { 'n_lat': window.current_bounds._ne.lat, 's_lat': window.current_bounds._sw.lat, 'e_lng': window.current_bounds._ne.lng, 'w_lng': window.current_bounds._sw.lng},
      timezone: current_time_zone,
      zoom: map.getZoom()
    }
  }


  function update_activity_list(){
    container.empty()
    let spinner = jQuery('.loading-spinner')
    container.append(`<li>Locations with Activity: (${window.activity_list.count}) | Locations without: (${window.activity_list.empty_count})</li><li><hr style="margin:0 0 1rem;"></li>`)
    jQuery.each( window.activity_list.list, function(i,v){
      if ( '' === v.note ) {
        return
      }
      container.append(`<li class=""> ${v} </li>`)
    })

    if ( ! window.activity_list.list  ) {
      container.append(`<li><strong>Results</strong> 0</li>`)
    }

    if ( window.activity_list.count > 250 ) {
      container.append(`<hr><li><strong>${window.activity_list.count - 250} Additional Records. (Zoom or Filter map to focus results)</strong></li><br><br>`)
    }

    spinner.removeClass('active')
  }

})
