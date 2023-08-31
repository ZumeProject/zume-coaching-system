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
                    <div class="z-card-main zume-list ${hover} ${key}" >
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
                    <div class="z-card-footer ${key}">
                        <div class="grid-x">
                            <div class="cell small-6 z-card-sub-left sub ${goal_valence} ${key}">
                                <div class="z-card-title ${key}">
                                      GOAL
                                  </div>
                                  <div class="z-card-value ${key}">
                                      ${goal_percent}%
                                  </div>
                                  <div class="z-card-description ${key}">
                                       goal for this period (${goal})
                                   </div>
                            </div>
                            <div class="cell small-6 z-card-sub-right sub ${trend_valence} ${key}">
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
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    window.load_list = ( data ) => {
      jQuery('.zume-list.'+data.key).click(function(){
        jQuery('#modal-large').foundation('open')
        jQuery('#modal-large-title').empty().html(`${data.label} <span style="float:right; margin-right: 2em;"><a href="https://zume5.training/contacts?query=eyJmaWVsZHMiOlt7InR5cGUiOlsidXNlciJdfV0sInNvcnQiOiJuYW1lIiwib2Zmc2V0IjowfQ%3D%3D&labels=W3siaWQiOiJ1c2VyIiwibmFtZSI6IkNvbnRhY3QgVHlwZTogVXNlciIsImZpZWxkIjoidHlwZSJ9XQ%3D%3D&filter_id=1689018512.323&filter_tab=" class="button small">Take Action</a></span> <hr>`)
        jQuery('#modal-large-content').empty().html('<span class="loading-spinner active"></span>')

        makeRequest('GET', 'list', {}, window.site_info.rest_root ).done( function( data_list ) {
          jQuery('#modal-large-content').empty().html('<table class="hover"><tbody id="zume-goals-list-modal"></tbody></table>')
          jQuery('#zume-goals-list-modal').append( `<tr><td></td><td><strong>Name</strong></td><td><strong>Registered</strong></td></tr>`)
          jQuery.each(data_list, function(i,v)  {
            jQuery('#zume-goals-list-modal').append( `<tr><td><input type="checkbox" /></td><td><a href="https://zume5.training/contacts?query=eyJmaWVsZHMiOlt7InR5cGUiOlsidXNlciJdfV0sInNvcnQiOiJuYW1lIiwib2Zmc2V0IjowfQ%3D%3D&labels=W3siaWQiOiJ1c2VyIiwibmFtZSI6IkNvbnRhY3QgVHlwZTogVXNlciIsImZpZWxkIjoidHlwZSJ9XQ%3D%3D&filter_id=1689018512.323&filter_tab=">${ v.display_name }</a></td><td>${v.user_registered}</td></tr>`)
          })
          jQuery('.loading-spinner').removeClass('active')
        })
      })
    }
    window.load_map = ( data ) => {
      console.log(data)
      jQuery('.zume-map.'+data.key).click(function(){
        jQuery('#modal-full').foundation('open')
        jQuery('#modal-full-title').empty().html(`${data.label}<hr>`)
        jQuery('#modal-full-content').empty().html('<span class="loading-spinner active"></span>')

        makeRequest('GET', 'map', { stage: data.stage, key: data.key }, window.site_info.rest_root ).done( function( data_map ) {
          console.log(data_map)
          let height = window.innerHeight - 150;
          jQuery('#modal-full-content').html(`
                    <div class="grid-x grid-padding-x">
                        <div class="cell small-6 medium-8">
                            <div id="map" style="position:relative;height: ${height}px !important;"></div>
                        </div>
                        <div class="cell small-6 medium-4">
                            <h2>List</h2>
                            <div id="list-results"></div>
                        </div>
                    </div>
                        `)

          mapboxgl.accessToken = window.site_info.map_key;
          var map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/light-v10',
            center: [0, 0],
            minZoom: 0,
            zoom: 0
          });

          map.dragRotate.disable();
          map.touchZoomRotate.disableRotation();

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
            console.log(map.getBounds())
            let bounds = map.getBounds()
            jQuery('#list-results').empty().html(`North: ${bounds._ne.lat}<br>East: ${bounds._ne.lng}, <br>South: ${bounds._sw.lat}, <br>West: ${bounds._sw.lng}`)
          }

          jQuery('.loading-spinner').removeClass('active')
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
