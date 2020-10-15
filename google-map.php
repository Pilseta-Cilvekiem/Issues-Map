<?php

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

namespace IssuesMap;

/**
 * Displays a Google map.
 * The map can either be static (no user interaction allowed) or interactive
 * (allows the user to drag the location marker).
 */
class GoogleMap {

    private $_api_key;

    public function __construct($api_key) {
        $this->_api_key = $api_key;
    }

    /**
     * Show a Google map with a single moveable marker.
     */
    public function do_location_picker_map($lat, $lng, $zoom) {
        $map_options = array(
            'center' => array('lat' => $lat, 'lng' => $lng), 
            'zoom' => $zoom, 
            'showInfo' => false, 
            'clustered' => false, 
            'cssClasses' => 'im-location-picker');
        $map_config = array(
            'markers' => array(
                array(
                    'position' => array('lat' => $lat, 'lng' => $lng),
                    'draggable' => true,
                    'issue_id' => 0,
                    'info' => '',
                )
            ),
        );
        return $this->do_interactive_map($map_options, $map_config);
    }

    /**
     * Show an interactive map with multiple markers.
     */
    public function do_interactive_map($map_options, $map_content) {
        $api_key = $this->_api_key;
        if ($api_key) {
            $css_classes = isset($map_options['cssClasses']) ? $map_options['cssClasses'] : 'im-standard-map';
            $clustered = isset($map_options['clustered']) && $map_options['clustered'];
            if ($clustered) {
                $map_options['imagePath'] = plugins_url('/img/m', __FILE__);
                wp_enqueue_script('im-clusterer', plugins_url('/js/clusterer/index.min.js', __FILE__));
            }            
            wp_enqueue_script('gmaps',
                    "https://maps.googleapis.com/maps/api/js?key={$api_key}",
                    array(), false, true);
            wp_enqueue_script('markerwithlabel', plugins_url('/js/markerwithlabel_packed.js', __FILE__), array('gmaps'));
            wp_enqueue_script('im-google-map', plugins_url('/js/google-map.js', __FILE__), array('gmaps', 'markerwithlabel'), PLUGIN_BUILD_NUMBER);
            $jsonOptions = json_encode($map_options);
            $jsonContent = json_encode($map_content);
            $wpnonce = wp_create_nonce('im-map');
            $content = "<div id='im-map' class='im-map " . $css_classes . "' data-map-options='" . $jsonOptions . "' data-map-content='" . $jsonContent . "'></div>";
            $content .= '<input type="hidden" id="im-map-nonce" name="im-map-nonce" value="' . $wpnonce .'"></input>';
            
        } else {
            $content = __('Unable to display Google map - missing API key in plugin settings.', 'issues-map');
        }
        return $content;
    }

    /**
     * Show a static Google map image with a single marker.
     */
    public function do_static_single_location_map($lat, $lng, $zoom) {
        $api_key = $this->_api_key;
        if ($api_key) {
            // Static map image
            $scheme = is_ssl() ? 'https' : 'http';
            $width = MAP_STATIC_IMAGE_WIDTH;
            $height = MAP_STATIC_IMAGE_HEIGHT;
            $alt = esc_attr(__('Map of issue location', 'issues-map'));
            $src = esc_url("$scheme://maps.googleapis.com/maps/api/staticmap?size={$width}x{$height}&zoom={$zoom}&markers={$lat},{$lng}&key={$api_key}");
            if (DEBUG_VERSION) {
                // Avoids loading Google map (and any associated costs) during development
                $src = plugin_dir_url(__FILE__) . 'img/dummy_map.png';
            }
            $content = "<img class='im-image' alt='$alt' src='$src'></img>";
        } else {
            $content = __('Unable to display Google map - missing API key in plugin settings.', 'issues-map');
        }
        return $content;
    }

}
