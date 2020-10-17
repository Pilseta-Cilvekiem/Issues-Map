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

require_once 'filterable-view.php';

/**
 * Generates the issues list content.
 */
class IssuesList extends FilterableView {

    /*
     * Generate issues list view html.
     */

    public function get_list_view_html() {

        $wpnonce = wp_create_nonce('im-list-view');
        $content = "<div class='im-list-view'>";
        $content .= $this->get_filter_section_html();
        $content .= "<div class='im-list-view-items'>";
        $content .= $this->get_list_items_html();
        $content .= "</div><input type='hidden' id='im-list-view-nonce' name='im-list-view-nonce' value='" . $wpnonce . "'></input></div>";

        return $content;
    }

    /*
     * Get the html for the issues list.
     */

    public function get_list_items_html($filters = null, $page_num = 1) {
        $content = '';
        $issue_content_mgr = $this->_plugin->get_issue_content_mgr();

        $query_args = array(
            'post_type' => ISSUE_POST_TYPE,
            'post_status' => 'publish',
            'orderby' => 'post_date',
            'order' => 'DESC',
            'posts_per_page' => DEFAULT_POSTS_PER_PAGE,
            'paged' => $page_num,
        );
        if ($filters) {
            $taxonomy_query = $this->create_taxonomy_query($filters);
            $query_args['tax_query'] = $taxonomy_query;
            $meta_query = $this->create_meta_query($filters);
            $query_args['meta_query'] = $meta_query;
        }

        $query = new \WP_Query($query_args);
        if ($query->have_posts()) {
            $num_pages = $query->max_num_pages;
            while ($query->have_posts()) {
                $query->the_post();
                $content .= $issue_content_mgr->get_issue_preview_html(get_the_ID(), 'list');
            }

            // Add page navigation
            if ($num_pages > 1) {
                $prev_str = esc_html__('Previous', 'issues-map');
                $next_str = esc_html__('Next', 'issues-map');
                $prev_page_num = $page_num - 1;
                $next_page_num = $page_num + 1;
                $content .= "<div class='im-page-nav'>";
                if ($page_num > 1) {
                    $content .= "<a id='im-list-prev-button' class='im-page-nav-link' data-go-to-page-num='{$prev_page_num}'>{$prev_str}</a>&nbsp;&nbsp;";
                }
                $content .= esc_html(sprintf(__('Page %1$d / %2$d', 'issues-map'), $page_num, $num_pages));
                if ($page_num < $num_pages) {
                    $content .= "&nbsp;&nbsp;<a id='im-list-next-button' class='im-page-nav-link' data-go-to-page-num='{$next_page_num}'>{$next_str}</a>";
                }
                $content .= "</div>";
            } else {
                $content .= "<p id='im-message'>" . esc_html(sprintf(_n('%d issue found.', '%d issues found.', $query->post_count, 'issues-map'), $query->post_count)) . "</p>";
            }
        } else {
            $content .= "<p id='im-message'>" . esc_html__('No issues found.', 'issues-map') . "</p>";
        }
        wp_reset_postdata();

        return $content;
    }

}
