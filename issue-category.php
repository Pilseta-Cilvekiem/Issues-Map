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
 * Manages issue category editing.
 */

class IssueCategoryManager {

    private $_plugin;

    public function __construct($plugin = null) {
        $this->_plugin = $plugin;
    }

    /*
     * Save selected icon and colour on saving issue category.
     */

    public function save_issue_category_meta($term_id) {
        $icon = sanitize_text_field(filter_input(INPUT_POST, 'im_icon_name', FILTER_DEFAULT));
        $color = sanitize_text_field(filter_input(INPUT_POST, 'im_color', FILTER_DEFAULT));
        update_term_meta($term_id, META_ICON_NAME, $icon);
        update_term_meta($term_id, META_COLOR, $color);
    }

    /*
     * Customise columns for issue category taxonomy.
     */

    public function manage_edit_issue_category_columns($columns) {
        $new_columns['cb'] = '<input type="checkbox" />';
        $new_columns['name'] = esc_html_x('Name', 'Category or tag name', 'issues-map');
        $new_columns['icon'] = esc_html__('Icon', 'issues-map');
        $new_columns['description'] = esc_html__('Description', 'issues-map');
        $new_columns['slug'] = esc_html__('Slug', 'issues-map');
        $new_columns['posts'] = esc_html__('Count', 'issues-map');
        return $new_columns;
    }

    /*
     * Provide content for issue category taxonomy columns.
     */

    public function manage_issue_category_custom_column($out, $column_name, $term_id) {
        switch ($column_name) {
            case 'icon':
                $icon_name = get_term_meta($term_id, META_ICON_NAME, true);
                $color = get_term_meta($term_id, META_COLOR, true);
                $out .= '<div class="im-icon-preview" style="background-color: ' . $color . ';">';
                $out .= '<i class="material-icons">' . $icon_name . '</i></div>';
                break;
        }
        return $out;
    }

    /*
     * Add icon selection to 'add issue category' page.
     */

    public function add_issue_category_form_fields() {

        $label_str = esc_html__('Icon', 'issues-map');
        $content = '<div class="form-field"><label for="im_icon_name">' . $label_str . '</label>';
        $content .= $this->get_issue_category_form_fields();
        $content .= '</div>';

        echo $content;
    }

    /*
     * Add icon selection to 'edit issue category' page.
     */

    public function edit_issue_category_form_fields($term) {
        $label_str = esc_html__('Icon', 'issues-map');
        $content = '<tr class="form-field"><th scope="row"><label for="im_icon_name">' . $label_str . '</label><td>';
        $content .= $this->get_issue_category_form_fields($term);
        $content .= '</td></th></tr>';

        echo $content;
    }

    /*
     * Create icon and colour selector controls.
     */

    private function get_issue_category_form_fields($term = null) {
        $choose_str = esc_html__('Select icon:', 'issues-map');
        $selected_icon = $term !== null ? get_term_meta($term->term_id, META_ICON_NAME, true) : 'place';
        $selected_color = $term !== null ? get_term_meta($term->term_id, META_COLOR, true) : DEFAULT_COLOR;
        $content = '<div class="im-icon-preview" style="background-color: ' . $selected_color . ';">';
        $content .= '<i class="material-icons">' . $selected_icon . '</i>';
        $content .= '<input type="hidden" id="im_icon_name" name="im_icon_name" value="' . $selected_icon . '" ></input>';
        $content .= '</div><div class="im-color-picker">';
        $content .= '<input type="text" id="im_color" class="im-color-option color-field" name="im_color" value="' . $selected_color . '"></input>';
        $content .= '</div><div class="im-icon-picker">';
        $content .= '<div class="im-icon-picker-text">' . $choose_str . '</div>';
        $content .= '<div class="im-icon-picker-icons">';
        $icons_list = $this->get_issue_category_icons_list();
        foreach ($icons_list as $icon) {
            $content .= '<a href="#" title="' . $icon . '"><i class="material-icons im-icon-picker-icon">' . $icon . '</i></a>';
        }
        $content .= '</div></div>';

        return $content;
    }

    /*
     * Icon names for 'material icons' font (https://material.io).
     */

    private function get_issue_category_icons_list() {
        return array(
            // Maps category
            '360',
            'add_location',
            'atm',
            'beenhere',
            'category',
            'compass_calibration',
            'departure_board',
            'directions',
            'directions_bike',
            'directions_boat',
            'directions_bus',
            'directions_car',
            'directions_railway',
            'directions_run',
            'directions_subway',
            'directions_transit',
            'directions_walk',
            'edit_attributes',
            'edit_location',
            'ev_station',
            'fastfood',
            'flight',
            'hotel',
            'layers',
            'layers_clear',
            'local_activity',
            'local_airport',
            'local_atm',
            'local_bar',
            'local_cafe',
            'local_car_wash',
            'local_convenience_store',
            'local_dining',
            'local_drink',
            'local_florist',
            'local_gas_station',
            'local_grocery_store',
            'local_hospital',
            'local_hotel',
            'local_laundry_service',
            'local_library',
            'local_mall',
            'local_movies',
            'local_offer',
            'local_parking',
            'local_pharmacy',
            'local_phone',
            'local_pizza',
            'local_play',
            'local_post_office',
            'local_printshop',
            'local_see',
            'local_shipping',
            'local_taxi',
            'map',
            'money',
            'my_location',
            'navigation',
            'near_me',
            'not_listed_location',
            'person_pin',
            'person_pin_circle',
            'pin_drop',
            'place',
            'rate_review',
            'restaurant',
            'restaurant_menu',
            'satellite',
            'store_mall_directory',
            'streetview',
            'subway',
            'terrain',
            'traffic',
            'train',
            'tram',
            'transfer_within_a_station',
            'transit_enterexit',
            'trip_origin',
            'zoom_out_map',
                // End - Maps category
        );
    }

}
