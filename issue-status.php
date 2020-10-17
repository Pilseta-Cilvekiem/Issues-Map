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
 * Manages issue status editing.
 */
class IssueStatusManager {
    private $_plugin;

    public function __construct($plugin = null) {
        $this->_plugin = $plugin;
    }

    /*
     * Save selected icon and colour on saving issue status.
     */

    public function save_issue_status_meta($term_id) {
        $color = sanitize_text_field(filter_input(INPUT_POST, 'im_color', FILTER_DEFAULT));
        update_term_meta($term_id, META_COLOR, $color);
    }

    /*
     * Customise columns for issue status taxonomy.
     */

    public function manage_edit_issue_status_columns($columns) {
        $new_columns['cb'] = '<input type="checkbox" />';
        $new_columns['name'] = esc_html_x('Name', 'Category or tag name', 'issues-map');
        $new_columns['color'] = esc_html__('Colour', 'issues-map');
        $new_columns['description'] = esc_html__('Description', 'issues-map');
        $new_columns['slug'] = esc_html__('Slug', 'issues-map');
        $new_columns['posts'] = esc_html__('Count', 'issues-map');
        return $new_columns;
    }

    /*
     * Provide content for issue status taxonomy columns.
     */

    public function manage_issue_status_custom_column($out, $column_name, $term_id) {
        switch ($column_name) {
            case 'color':
                $color = get_term_meta($term_id, META_COLOR, true);
                $out .= '<div class="im-icon-preview" style="background-color: ' . $color . ';">';
                $out .= '<i class="material-icons">' . ISSUE_STATUS_ICON_NAME . '</i></div>';
                break;
        }
        return $out;
    }

    /*
     * Add icon selection to 'add issue status' page.
     */

    public function add_issue_status_form_fields() {

        $label_str = esc_html__('Colour', 'issues-map');
        $content = '<div class="form-field"><label for="im_color">' . $label_str . '</label>';
        $content .= $this->get_issue_status_form_fields();
        $content .= '</div>';

        echo $content;
    }

    /*
     * Add icon selection to 'edit issue status' page.
     */

    public function edit_issue_status_form_fields($term) {
        $label_str = esc_html__('Colour', 'issues-map');
        $content = '<tr class="form-field"><th scope="row"><label for="im_color">' . $label_str . '</label><td>';
        $content .= $this->get_issue_status_form_fields($term);
        $content .= '</td></th></tr>';

        echo $content;
    }

    /*
     * Create icon and colour selector controls.
     */

    private function get_issue_status_form_fields($term = null) {
        $selected_color = $term !== null ? get_term_meta($term->term_id, META_COLOR, true) : DEFAULT_COLOR;
        $content = '<div class="im-color-picker">';
        $content .= '<input type="text" id="im_color" class="im-color-option color-field" name="im_color" value="' . $selected_color . '"></input>';
        $content .= '</div>';

        return $content;
    }

}
