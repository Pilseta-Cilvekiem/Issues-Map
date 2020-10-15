/* 
    The Issues Map plugin is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

google.maps.event.addDomListener(window, "load", imInitMaps);

/* Init maps on page. */
function imInitMaps() {
    let mapElts = document.querySelectorAll('div.im-map');
    mapElts.forEach(function(mapElt) {
       imInitMap(mapElt);
    });
}

/* Initialise a map. */
var _imMaps = {};
function imInitMap(mapElt) {

    let mapOptions = JSON.parse(mapElt.getAttribute('data-map-options'));
    let mapContent = JSON.parse(mapElt.getAttribute('data-map-content'));
    let args = { center: mapOptions.center, 
                zoom: mapOptions.zoom };
    let map = new google.maps.Map(mapElt, args);
    var infoWindow = new google.maps.InfoWindow({ content: '' });
    
    let mapEntry = { 
        map: map, 
        options: mapOptions,
        content: mapContent, 
        markers: [], 
        infoWindow: infoWindow, 
        clusterer: null };
    map.imMapEntry = mapEntry;
    _imMaps[mapElt.id] = mapEntry;

    if (mapOptions.showInfo) {
        map.addListener('click', function() {
            this.imMapEntry.infoWindow.close();
        });
    }

    imUpdateMap(mapElt.id, null);
}

/* Get map data. */
function imGetMap(id) {
    return _imMaps[id];
}

/* Update map data. */
function imUpdateMap(id, content) {
    
    let mapEntry = _imMaps[id];
    if (typeof mapEntry !== 'undefined') {
        if (content !== null) {            
            
            // Remove any existing markers / clusters
            if (mapEntry.clusterer !== null) {
                mapEntry.clusterer.clearMarkers();
            }            
            mapEntry.markers.forEach(function(marker) {
                marker.setMap(null);
            });
            mapEntry.markers.length = 0;                       
            
            // Update content
            mapEntry.content = content;
        }

        // Add new markers
        for (let i=0; i<mapEntry.content.markers.length; i++) {
            let item = mapEntry.content.markers[i];
            item.map = mapEntry.map;
            item.labelAnchor = new google.maps.Point(item.labelAnchorX, item.labelAnchorY);
            marker = new MarkerWithLabel(item);
            marker.imMapEntry = mapEntry;
            marker.imData = item;
            //marker.setMap(mapEntry.map);
            if (item.draggable) {
                marker.addListener('dragend', function(e) {
                    // Centre map and store new centre location
                    let pos = e.latLng;                    
                    this.imMapEntry.map.setCenter(pos);
                    this.imMapEntry.options.center.lat = pos.lat();
                    this.imMapEntry.options.center.lng = pos.lng();
                });
            }
            if (mapEntry.options.showInfo) {
                marker.addListener('click', function(e) {
                    showInfoWindow(this);
                });                
            }
            mapEntry.markers.push(marker);
        }
                    
        if (mapEntry.options.clustered) {
            mapEntry.clusterer = new MarkerClusterer(mapEntry.map, mapEntry.markers, {imagePath: mapEntry.options.imagePath});
        }
    }
}

/* Show popup info window for marker. */
function showInfoWindow(marker) {
    let mapEntry = marker.imMapEntry;
    let item = marker.imData;
    if (item.info) {
        mapEntry.infoWindow.setContent(item.info);
        mapEntry.infoWindow.open(mapEntry.map, marker);
    }
    else {
        let xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                let response = JSON.parse(this.responseText);
                if (response.success) {
                    item.info = response.data;
                    mapEntry.infoWindow.setContent(item.info);
                    mapEntry.infoWindow.open(mapEntry.map, marker);
                }
            }
        };
        
        let nonceElt = document.querySelector('#im-map-nonce');
        let nonceVal = nonceElt.getAttribute('value');        
        let postData = 'issue_id=' + item.issue_id + '&security=' + nonceVal;
        xhttp.open("POST", issues_map.ajax_url + '?action=get_info_window_content_async', true);
        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhttp.send(postData);
    }
}
