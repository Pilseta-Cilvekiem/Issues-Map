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
 * Base class for the issues list and map views.
 */
class FilterableView {

    protected $_plugin;

    public function __construct($plugin) {
        $this->_plugin = $plugin;
    }

    /*
     * Get the html for displaying filters in the issues list / map views.
     */

    protected function get_filter_section_html() {
        $own_issues_str = __('Only my issues', 'issues-map');
        $issue_data_mgr = $this->_plugin->get_issue_data_mgr();
        $content = '<div id="im-filter-section" class="im-form-section"><select id="im-category-filter" name="im-category-filter" class="im-category-filter im-select">';
        $content .= $issue_data_mgr->get_issue_category_options(0, __('(Filter by category)', 'issues-map'));
        $content .= '</select><select id="im-status-filter" name="im-status-filter" class="im-status-filter im-select">';
        $content .= $issue_data_mgr->get_issue_status_options('', __('(Filter by status)', 'issues-map'));
        $content .= '</select><div class="im-inline"><input type="checkbox" id="im-own-issues-filter" name="im-own-issues-filter" class="im-own-issues-filter"> <span>' . $own_issues_str . '</span></input></div></div>';
        return $content;
    }

    /*
     * Create the meta_query parameters for filtering issues.
     */

    protected function create_meta_query($filters) {
        $meta_query = array();
        if (isset($filters['own_issues']) && $filters['own_issues']) {
            $user_meta_id = $this->_plugin->get_user_profile()->get_val(META_USER_ID);
            $meta_query['user_id_clause'] = array('key' => META_USER_ID, 'value' => $user_meta_id);
        }
        return $meta_query;
    }

    /*
     * Create the tax_query parameters for filtering issues.
     */

    protected function create_taxonomy_query($filters) {
        $taxonomy_query = array();
        if (isset($filters['category']) && $filters['category']) {
            // 
            
            $taxonomy_query['category_clause'] = array(
                'taxonomy' => ISSUE_CATEGORY_TAXONOMY,
                'field' => 'term_id',
                'terms' => $filters['category']);
        }
        if (isset($filters['status']) && $filters['status']) {
            $taxonomy_query['status_clause'] = array(
                'taxonomy' => ISSUE_STATUS_TAXONOMY,
                'field' => 'term_id',
                'terms' => $filters['status']);
        }
        return $taxonomy_query;
    }
    
}
