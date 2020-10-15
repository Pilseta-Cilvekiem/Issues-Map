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

/*
 * Displays a map of issues.
 */

class MapView extends FilterableView {
    
    /*
     * Generate issues map view html.
     */

    public function get_map_view_html() {

        $wpnonce = wp_create_nonce('im-map-view');
        $default_lat = floatval(get_option(OPTION_CENTRE_LAT, DEFAULT_CENTRE_LAT));
        $default_lng = floatval(get_option(OPTION_CENTRE_LNG, DEFAULT_CENTRE_LNG));        
        $zoom_level = intval(get_option(OPTION_ZOOM_MAP_VIEW, DEFAULT_ZOOM_MAP_VIEW));
        $content = "<div class='im-map-view'>";
        $content .= $this->get_filter_section_html();
        $map_content = $this->get_map_content();
        $map_options = array(
            'center' => array('lat' => $default_lat, 'lng' => $default_lng),
            'zoom' => $zoom_level,
            'showInfo' => true,
            'clustered' => true);
        $api_key = get_option(OPTION_GMAPS_API_KEY, '');
        require_once 'google-map.php';
        $map_control = new GoogleMap($api_key);
        $content .= $map_control->do_interactive_map($map_options, $map_content);
        $content .= "<input type='hidden' id='im-map-view-nonce' name='im-map-view-nonce' value='" . $wpnonce . "'></input>";
        $content .= '</div>';

        return $content;
    }

    /*
     * Create the content to display on the issues map.
     */
    public function get_map_content($filters = null) {

        $map_content = array();

        $query_args = array(
            'post_type' => ISSUE_POST_TYPE,
            'post_status' => 'publish',
        );
        if ($filters) {
            $taxonomy_query = $this->create_taxonomy_query($filters);
            $query_args['tax_query'] = $taxonomy_query;
            $meta_query = $this->create_meta_query($filters);
            $query_args['meta_query'] = $meta_query;
        }

        $markers = array();
        $query = new \WP_Query($query_args);
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $issue_id = get_the_ID();
                $icon_name = DEFAULT_ICON_NAME;
                $icon_color = DEFAULT_COLOR;
                $terms = wp_get_post_terms($issue_id, ISSUE_CATEGORY_TAXONOMY, array('fields' => 'ids'));
                if (isset($terms[0])) {
                    $issue_category_id = $terms[0];
                    $icon_name = get_term_meta($issue_category_id, META_ICON_NAME, true);
                    $icon_color = get_term_meta($issue_category_id, META_COLOR, true);
                }
                $lat = floatval(get_post_meta($issue_id, META_LATITUDE, true));
                $lng = floatval(get_post_meta($issue_id, META_LONGITUDE, true));
                $marker = array(
                    'position' => array('lat' => $lat, 'lng' => $lng),
                    'draggable' => false,
                    'issue_id' => $issue_id,
                    'info' => '',
                    'icon' => array(
                        'path' => DEFAULT_MARKER_ICON_PATH,
                        'fillColor' => esc_attr($icon_color),
                        'fillOpacity' => 1,
                        'strokeColor' => '',
                        'strokeWeight' => 0,
                    'scale' => 0.75,
                    ),
                    'labelContent' => '<i class="material-icons">' . esc_html($icon_name) . '</i>',
                    'labelAnchorX' => 18,
                    'labelAnchorY' => 32,
                    'labelClass' => "im-marker",
                    'labelStyle' => array('opacity' => 1.0)
                );
                $markers[] = $marker;
            }
        }
        wp_reset_postdata();
        
        $map_content['markers'] = $markers;

        return $map_content;
    }

    
}
