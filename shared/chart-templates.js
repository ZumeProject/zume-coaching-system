jQuery(document).ready(function($) {

    window.API_get = (url, data, callback ) => {
      return $.get(url, data, callback);
    }
    window.API_post = (url, callback ) => {
      return $.post(url, callback);
    }
    window.setup_filter = () => {
      let range_filter = jQuery('#range-filter')
      window.filter = range_filter.val()
      jQuery('#range-title').html( jQuery('#range-filter :selected').text() )
      range_filter.on('change', function(){
        window.filter = range_filter.val()
        jQuery('#range-title').html( jQuery('#range-filter :selected').text() )
        window.path_load( window.filter )
      })
      window.path_load( window.filter )
    }
    window.template_map_list = ({key, link, label, value, description}) => {
        let hover = '';
        if ( link ) {
          hover = 'hover'
        }
        return `
            <div class="grid-x">
              <div class="cell z-card ${key}">
                  <div class="z-card-main ${hover} ${key}" >
                      <div class="z-card-title hero ${key}">
                          ${label}
                      </div>
                      <div class="z-card-value ${key}">
                          ${value}
                      </div>
                      <div class="z-card-description ${key}">
                          ${description}
                       </div>
                  </div>
                  <div class="z-card-footer ${key}">
                      <div class="grid-x">
                          <div class="cell small-6 z-card-sub-left hover zume-list ${key}">
                              STATS
                          </div>
                          <div class="cell small-6 z-card-sub-right hover zume-map ${key}">
                              MAP
                          </div>
                      </div>
                  </div>
              </div></div>
        `;
    }
  window.template_hero_map_only = ({key, link, label, value, description}) => {
      let hover = '';
      if ( link ) {
        hover = 'hover'
      }
      return `
          <div class="grid-x">
              <div class="cell z-card ${key}">
                  <div class="z-card-main ${hover} ${key}" >
                      <div class="z-card-title hero ${key}">
                          ${label}
                      </div>
                      <div class="z-card-value ${key}">
                          ${value}
                      </div>
                      <div class="z-card-description ${key}">
                          ${description}
                       </div>
                  </div>
                  <div class="z-card-footer ${key}">
                      <div class="grid-x">
                          <div class="cell z-card-sub-bottom hover zume-map ${key}">
                              MAP
                          </div>
                      </div>
                  </div>
              </div>
          </div>
      `;
  }
    window.template_single = ({key, valence, label, value, description}) => {
      return `
              <div class="grid-x">
              <div class="cell z-card ${key} ${valence}">
                  <div class="z-card-single">
                      <div class="z-card-title ${key}">
                          ${label}
                      </div>
                      <div class="z-card-value ${key}">
                          ${value}
                      </div>
                      <div class="z-card-description ${key}">
                          ${description}
                      </div>
                  </div>
              </div>
              </div>
          `;
    }
    window.template_single_list = ({key, valence, label, value, description}) => {
      return `
              <div class="grid-x">
              <div class="cell z-card zume-list ${key} ${valence} hover">
                  <div class="z-card-single">
                      <div class="z-icon"><i class="fi-list-bullet" ></i></div>
                      <div class="z-card-title ${key}">
                          ${label}
                      </div>
                      <div class="z-card-value ${key}">
                          ${value}
                      </div>
                      <div class="z-card-description ${key}">
                          ${description}
                      </div>
                  </div>
              </div>
              </div>
          `;
    }
    window.template_single_map = ({key, valence, label, value, description}) => {
      return `
              <div class="grid-x">
              <div class="cell z-card zume-map ${key} ${valence} hover">
                  <div class="z-card-single">
                      <div class="z-icon"><i class="fi-map" ></i></div>
                      <div class="z-card-title ${key}">
                          ${label}
                      </div>
                      <div class="z-card-value ${key}">
                          ${value}
                      </div>
                      <div class="z-card-description ${key}">
                          ${description}
                      </div>
                  </div>
              </div>
              </div>
          `;
    }
    window.template_in_out = ({key, label, value_in, value_idle, value_out, description}) => {
      return `
          <div class="grid-x z-card z-card-single ">
              <div class="cell small-12 z-card-title ${key}">
                   ${label}<hr>
              </div>
              <div class="cell small-4  ${key}">
                <div class="z-card-title ${key}">
                    IN
                </div>
                <div class="z-card-value ${key}">
                  ${value_in}
                </div>
              </div>
              <div class="cell small-4 ${key}">
                <div class="z-card-title ${key}">
                    IDLE
                </div>
                <div class="z-card-value ${key}">
                  ${value_idle}
                </div>
              </div>
              <div class="cell small-4 ${key}">
                <div class="z-card-title ${key}">
                    OUT
                </div>
                <div class="z-card-value ${key}">
                  ${value_out}
                </div>
              </div>
              <div class="cell small-12 z-card-description ${key}">
                  ${description}
               </div>
          </div>
       `;
    }
    window.template_trio = ({key, link, label, goal, goal_valence, goal_percent, trend, trend_valence, trend_percent, value, description}) => {
      let hover = '';
      if ( link ) {
        hover = 'hover'
      }
      return `
            <div class="grid-x">
                <div class="cell z-card  ${key}">
                    <div class="z-card-main ${hover} ${key}" >
                        <div class="z-card-title hero ${key}">
                            ${label}
                        </div>
                        <div class="z-card-value ${key}">
                            ${value}
                        </div>
                        <div class="z-card-description ${key}">
                            ${description}
                         </div>
                    </div>
                    <div class="z-card-footer ${key}">
                        <div class="grid-x">
                            <div class="cell small-6 z-card-sub ${goal_valence} ${key}">
                                <div class="z-card-title ${key}">
                                      PACE
                                  </div>
                                  <div class="z-card-value ${key}">
                                      ${goal_percent}%
                                  </div>
                                  <div class="z-card-description ${key}">
                                       target for this period (${goal})
                                   </div>
                            </div>
                            <div class="cell small-6 z-card-sub ${trend_valence} ${key}">
                                <div class="z-card-title ${key}">
                                      TREND
                                  </div>
                                  <div class="z-card-value ${key}">
                                       ${trend_percent}%
                                  </div>
                                  <div class="z-card-description ${key}">
                                        previous period (${trend})
                                 </div>
                            </div>
                            <div class="cell z-card-sub-bottom hover zume-map ${key}">
                                <i class="fi-map"></i>
                            </div>
                        </div>
                    </div>
                    <div class="z-card-footer ${key}">
                        <div class="grid-x">

                        </div>
                  </div>
                </div>
            </div>
        `;
    }

    window.load_list = ( data ) => {
      jQuery('.zume-list.'+data.key).click(function(){
        jQuery('#modal-large').foundation('open')
        jQuery('#modal-large-title').empty().html(`${data.label} <span style="float:right; margin-right: 2em;"></span> <hr>`)
        jQuery('#modal-large-content').empty().html('<span class="loading-spinner active"></span>')

        makeRequest('GET', 'list', { stage: data.stage, key: data.key, range: data.range, data: data  }, window.site_info.rest_root ).done( function( data_list ) {
          jQuery('#modal-large-content').empty().html('<table class="hover"><tbody id="zume-goals-list-modal"></tbody></table>')

          if ( 'languages' === data.key ) {
            jQuery('#zume-goals-list-modal').append( `<tr><td></td><td><strong>Name</strong></td><td><strong>Activities</strong></td></tr>`)
            jQuery.each(data_list, function(i,v)  {
              jQuery('#zume-goals-list-modal').append( `<tr><td><input type="checkbox" /></td><td>${ v.name }</td><td>${ v.activities }</td></tr>`)
            })
          }
          if ( 'landing_page_source_users' === data.key ) {
            jQuery('#zume-goals-list-modal').append( `<tr><td></td><td><strong>Name</strong></td><td><strong>Source</strong></td><td><strong>Location</strong></td></tr>`)
            jQuery.each(data_list, function(i,v)  {
              jQuery('#zume-goals-list-modal').append( `<tr><td><input type="checkbox" /></td><td>${ v.name }</td><td>${ v.payload }</td><td>${ v.label }</td></tr>`)
            })
          }
          else {
            jQuery('#zume-goals-list-modal').append( `<tr><td></td><td><strong>Name</strong></td><td><strong>Email</strong></td><td><strong>Phone</strong></td></tr>`)
            jQuery.each(data_list, function(i,v)  {
              jQuery('#zume-goals-list-modal').append( `<tr><td><input type="checkbox" /></td><td>${ v.name }</td><td>${v.user_email}</td><td>${v.user_phone}</td></tr>`)
            })
          }

          jQuery('.loading-spinner').removeClass('active')
        })
      })
    }
    window.load_map = ( data ) => {
      console.log(data)
      jQuery('.zume-map.'+data.key).click(function(){
        console.log('click' + data.key )
        jQuery('#modal-full').foundation('open')
        jQuery('#modal-full-title').empty().html(`${data.label}<hr>`)
        jQuery('#modal-full-content').empty().html('<span class="loading-spinner active"></span>')

        makeRequest('POST', 'map', { stage: data.stage, key: data.key, range: data.range }, window.site_info.rest_root ).done( function( data_map ) {
          console.log(data_map)
          let height = window.innerHeight - 150;
          jQuery('#modal-full-content').html(`
                    <div class="grid-x grid-padding-x">
                        <div class="cell small-6 medium-8">
                            <div id="map" style="position:relative;height: ${height}px !important;"></div>
                        </div>
                        <div class="cell small-6 medium-4">
                            <h2>List</h2>
                            <div id="list-results" style="position:relative;height: ${height -50}px !important; overflow-y: scroll; "></div>
                        </div>
                    </div>
          `)

          mapboxgl.accessToken = window.site_info.map_key;
          var map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/light-v10',
            center: [-98, 38.88],
            minZoom: 1,
            zoom: 1
          });

          map.dragRotate.disable();
          map.touchZoomRotate.disableRotation();

          map.on('load', function() {
            map.addSource('layer-source', {
              type: 'geojson',
              data: data_map,
              cluster: true,
              clusterMaxZoom: 20,
              clusterRadius: 50
            });
            map.addLayer({
              id: 'clusters',
              type: 'circle',
              source: 'layer-source',
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
              source: 'layer-source',
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
              source: 'layer-source',
              filter: ['!', ['has', 'point_count']],
              paint: {
                'circle-color': '#00d9ff',
                'circle-radius':12,
                'circle-stroke-width': 1,
                'circle-stroke-color': '#fff'
              }
            });

          })

          map.on('zoomstart', function(e) {
            jQuery('#list-results').empty().html('<span class="loading-spinner active"></span>')
          })
          map.on('dragstart', function(e) {
            jQuery('#list-results').empty().html('<span class="loading-spinner active"></span>')
          })
          map.on('zoomend', function(e) {
            list_result()
          })
          map.on('dragend', function(e) {
            list_result()
          })
          function list_result() {
            // console.log(map.getBounds())
            let bounds = map.getBounds()
            makeRequest('POST', 'map_list', { stage: data.stage, key: data.key, range: data.range, north: bounds._ne.lat, south: bounds._sw.lat, east: bounds._ne.lng, west: bounds._sw.lng }, window.site_info.rest_root ).done( function( list ) {
              console.log(list)
              let container = jQuery('#list-results')
              container.empty()
              jQuery.each(list, function(i,v)  {
                container.append( `<div class="grid-x grid-padding-x">
                    <div class="cell small-12">
                        <h3><strong>${ v.name }</strong> (${ v.label })</h3>
                        <!-- <a class="button small" href="https://zume.training/${v.post_type}/${v.post_id}">Training Contact</a> -->
                        <hr>
                   </div>
                </div>`)
              })
              jQuery('.loading-spinner').removeClass('active')
            })
          }
        })
      })
    }

    window.spin_add = () => {
      if ( typeof window.spin_count === 'undefined' ){
        window.spin_count = 0
      }
      window.spin_count++
      jQuery('.loading-spinner').addClass('active')
    }
    window.spin_remove = () => {
      if ( typeof window.spin_count === 'undefined' ){
        window.spin_count = 0
      }
      window.spin_count--
      if ( window.spin_count === 0 ) {
        jQuery('.loading-spinner').removeClass('active')
      }
    }


}) /* end of templates */
