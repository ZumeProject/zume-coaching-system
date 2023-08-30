jQuery(document).ready(function(){
  var isMobile = false; //initiate as false
  // device detection
  if(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|ipad|iris|kindle|Android|Silk|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i.test(navigator.userAgent)
    || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(navigator.userAgent.substr(0,4))) {
    isMobile = true;
  }
  let chartDiv = jQuery('#chart')

  window.activity_list = {}
  window.activity_geojson = {
    "type": "FeatureCollection",
    "features": []
  }

  // Add html and map
  let map_height = window.innerHeight
  let mobile_show = 'inherit'
  if ( isMobile && window.innerWidth < 640 ) {
    map_height = window.innerHeight / 2
    mobile_show = 'none'
  }
  chartDiv.empty().html(`
      <style>
          #activity-wrapper {
              height: ${window.innerHeight - 200}px !important;
              overflow: scroll;
          }
          #activity-list{
              height: ${window.innerHeight - 200}px !important;
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
              height: ${map_height}px !important;
          }
          #map {
              height: ${map_height}px !important;
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
           .mapboxgl-ctrl-geocoder.mapboxgl-ctrl {
                display: ${mobile_show};
           }
      </style>
      <div class="grid-x">
        <div class="medium-9 cell">
            <div id="map-wrapper">
                <div id='map'></div>
                <div id="map-header"><h3>Last 100 Hours of Movement Activity</h3>Countries: <span id="country_count">0</span> | Languages: <span id="languages_count">0</span></div>
            </div>
        </div>
        <div class="medium-3 cell">
            <div class="grid-x grid-padding-x" style="margin-top:10px;">
                <div class="cell">
                    <div>
                        <select name="type" id="type-dropdown" class="input-filter">
                            <option value="none">All Types</option>
                        </select>
                    </div>
                    <div>
                        <select name="country" id="country-dropdown" class="input-filter">
                            <option value="none">All Countries</option>
                        </select>
                    </div>
                    <div>
                        <select name="language" id="language-dropdown" class="input-filter">
                            <option value="none">All Languages</option>
                        </select>
                    </div>
                    <div id="stats-list"></div>
                </div>
                <div class="cell"><div class="loading-spinner active"></div></div>
            </div>
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
    initialize_cluster_map()
  });

  map.on('zoomend', function(e){
    load_map_activity()
  })
  map.on('dragend', function(e){
    load_map_activity()
  })

  function initialize_cluster_map() {
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
        'text-field': '{point_count_abbreviated}',
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
        'circle-radius':12,
        'circle-stroke-width': 1,
        'circle-stroke-color': '#fff'
      }
    });
  }

  function load_geojson(){
    let data = get_filters()
    window.post_request('load_geojson', data )
      .done( data => {
        console.log(data)
        "use strict";
        window.activity_geojson = data

        var mapSource= map.getSource('layer-source-contacts');
        if( typeof mapSource !== 'undefined') {
          map.getSource('layer-source-contacts').setData(window.activity_geojson);
        }

        load_countries_dropdown()
        load_languages_dropdown()
        load_type_dropdown()
        load_title_stats()
      })
  }

  function load_map_activity() {
    container.empty()
    let spinner = jQuery('.loading-spinner')
    spinner.addClass('active')
    let data = get_filters()

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
  load_geojson()
  load_map_activity()

  jQuery('.input-filter').on('change', function(e){
    load_map_activity()
    limit_cluster_to_filter()
  })

  function get_filters() {
    window.current_bounds = map.getBounds()
    let country = jQuery('#country-dropdown').val()
    let language = jQuery('#language-dropdown').val()
    let type = jQuery('#type-dropdown').val()
    return {
      bounds: { 'n_lat': window.current_bounds._ne.lat, 's_lat': window.current_bounds._sw.lat, 'e_lng': window.current_bounds._ne.lng, 'w_lng': window.current_bounds._sw.lng},
      timezone: 'America/Denver',
      country: country,
      language: language,
      type: type,
      zoom: map.getZoom()
    }
  }
  function limit_cluster_to_filter() {
    let data = get_filters()
    console.log(data)

    let geojson = {
      "type": "FeatureCollection",
      "features": []
    }

    jQuery.each( window.activity_geojson.features, function(i,v){
      // none set
      if ( 'none' === data.type && 'none' === data.language && 'none' === data.country ) {
        geojson.features.push(v)
      }
      // type set
      else if ( v.properties.type === data.type && 'none' === data.language && 'none' === data.country ) {
        geojson.features.push(v)
      }
      // language set
      else if ( 'none' === data.type && v.properties.language === data.language && 'none' === data.country ) {
        geojson.features.push(v)
      }
      // country set
      else if ( 'none' === data.type && 'none' === data.language && v.properties.country === data.country ) {
        geojson.features.push(v)
      }
      // language & type set
      else if ( v.properties.type === data.type && v.properties.language === data.language && 'none' === data.country ) {
        geojson.features.push(v)
      }
      // country & type set
      else if ( v.properties.type === data.type && 'none' === data.language && v.properties.country === data.country ) {
        geojson.features.push(v)
      }
      // country & language set
      else if ( 'none' === data.type && v.properties.language === data.language && v.properties.country === data.country ) {
        geojson.features.push(v)
      }
      // country & language & type set
      else if ( v.properties.type === data.type && v.properties.language === data.language && v.properties.country === data.country ) {
        geojson.features.push(v)
      }

    })

    var mapSource= map.getSource('layer-source-contacts');
    if(typeof mapSource === 'undefined') {
      load_geojson()
    } else {
      map.getSource('layer-source-contacts').setData(geojson);
    }
  }

  function update_activity_list(){
    container.empty()
    let spinner = jQuery('.loading-spinner')

    jQuery.each( window.activity_list.list, function(i,v){
      if ( '' === v.note ) {
        return
      }
      container.append(`<li class="${v.type} ${v.country} ${v.language}"><strong>(${v.time})</strong> ${v.note} </li>`)
    })

    if ( ! window.activity_list.list  ) {
      container.append(`<li><strong>Results</strong> 0</li>`)
    }

    if ( window.activity_list.count > 250 ) {
      container.append(`<hr><li><strong>${window.activity_list.count - 250} Additional Records. (Zoom or Filter map to focus results)</strong></li><br><br>`)
    }

    spinner.removeClass('active')
  }

  function load_countries_dropdown() {
    let country_dropdown = jQuery('#country-dropdown')
    let points = window.activity_geojson
    window.selected_country = country_dropdown.val()
    country_dropdown.empty()

    let add_selected = ''
    country_dropdown.append(`<option value="none">All Countries</option>`)
    country_dropdown.append(`<option disabled>---</option>`)
    jQuery.each(points.countries, function(i,v){
      add_selected = ''
      if ( v.code === window.selected_country ) {
        add_selected = ' selected'
      }
      country_dropdown.append(`<option value="${v.code}" ${add_selected}>${v.name} (${v.count})</option>`)
    })
  }
  function load_languages_dropdown() {
    let language_dropdown = jQuery('#language-dropdown')
    let points = window.activity_geojson
    window.selected_language = language_dropdown.val()
    language_dropdown.empty()

    let add_selected = ''
    language_dropdown.append(`<option value="none">All Languages</option>`)
    language_dropdown.append(`<option disabled>---</option>`)
    jQuery.each(points.languages, function(i,v){
      add_selected = ''
      if ( v.code === window.selected_language ) {
        add_selected = ' selected'
      }
      language_dropdown.append(`<option value="${v.code}" ${add_selected}>${v.name} (${v.count})</option>`)
    })
  }
  function load_type_dropdown() {
    let type_dropdown = jQuery('#type-dropdown')
    let stats_list = jQuery('#stats-list')
    let points = window.activity_geojson
    window.selected_type = type_dropdown.val()

    let add_selected = ''
    type_dropdown.empty().append(
      `<option value="none">All Types</option>
        <option disabled>---</option>
        <option value="" class="dd learning"></option>
        <option value="" class="dd joining"></option>
        <option value="" class="dd producing"></option>`
    )
    stats_list.empty().append(`
    <div>
        <span class="stats learning"></span><br>
        <span class="stats joining"></span><br>
        <span class="stats producing"></span><br>
        <span>Total: ${points.total}</span>
    </div>
    <hr>
    `)
    jQuery.each(points.types, function(i,v){
      add_selected = ''
      if ( v.code === window.selected_type ) {
        add_selected = ' selected'
      }
      jQuery('.dd.'+v.code).val(v.code).html(`${v.name} (${v.count})`)
      jQuery('.stats.'+v.code).html(`${v.name}: ${v.count}`)
    })
  }
  function load_title_stats() {
    jQuery('#country_count').html(window.activity_geojson.countries_count)
    jQuery('#languages_count').html(window.activity_geojson.languages_count)
  }
})
