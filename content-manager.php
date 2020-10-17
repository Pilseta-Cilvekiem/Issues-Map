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
 * Implements filters and content generation.
 */
class ContentManager {

    private $_plugin;

    public function __construct($plugin) {
        $this->_plugin = $plugin;
    }

    /*
     * Expand ISSUES_MAP_SHORTCODE shortcode into the required content.     
     */

    public function issues_map_shortcode_action($atts) {
        $satts = shortcode_atts(array(
            'id' => '0',
            'view' => get_query_var('view'),
                ),
                $atts, ISSUES_MAP_SHORTCODE);

        $id = intval($satts['id']);
        $view = sanitize_text_field($satts['view']);
        $content = '';
        switch ($view) {
            case '':
            case LIST_VIEW:
            default:
                if (get_option(OPTION_SHOW_HEADER_LINKS, DEFAULT_SHOW_HEADER_LINKS)) {
                    $content .= $this->get_links_menu(true);
                }
                require_once 'issues-list.php';
                $issues_list = new IssuesList($this->_plugin);
                $content .= $issues_list->get_list_view_html();
                if (get_option(OPTION_SHOW_FOOTER_LINKS, DEFAULT_SHOW_FOOTER_LINKS)) {
                    $content .= $this->get_links_menu(false);
                }
                break;
            case MAP_VIEW:
                if (get_option(OPTION_SHOW_HEADER_LINKS, DEFAULT_SHOW_HEADER_LINKS)) {
                    $content .= $this->get_links_menu(true);
                }
                require_once 'map-view.php';
                $map_view = new MapView($this->_plugin);
                $content .= $map_view->get_map_view_html();
                if (get_option(OPTION_SHOW_FOOTER_LINKS, DEFAULT_SHOW_FOOTER_LINKS)) {
                    $content .= $this->get_links_menu(false);
                }
                break;
            case ISSUE_VIEW:
                $content .= $this->_plugin->get_issue_content_mgr()->get_issue_view($id);
                if (get_option(OPTION_SHOW_FOOTER_LINKS, DEFAULT_SHOW_FOOTER_LINKS)) {
                    $content .= $this->get_links_menu(false);
                }
                break;
            case REPORT_VIEW:
                if ($this->can_add_or_edit($id)) {
                    $content = $this->_plugin->get_report_content_mgr()->get_report_view($id);
                }
                else {
                    $content = esc_html__('You are not authorised to view this report.', 'issues-map');
                }
                if (get_option(OPTION_SHOW_FOOTER_LINKS, DEFAULT_SHOW_FOOTER_LINKS)) {
                    $content .= $this->get_links_menu(false);
                }
                break;
            case ADD_ISSUE_VIEW:
            case EDIT_ISSUE_VIEW:
                if ($this->can_add_or_edit($id)) {
                    $content = $this->_plugin->get_issue_content_mgr()->get_edit_issue_form($id);
                } else {
                    $content = esc_html__('You are not authorised to add or edit issues.', 'issues-map');
                }
                break;
            case ADD_IMAGES_VIEW:
            case EDIT_IMAGES_VIEW:
                $auth_mgr = $this->_plugin->get_auth_mgr();
                if ($this->can_add_or_edit($id) && $auth_mgr->current_user_can_upload_images()) {
                    $content = $this->_plugin->get_issue_content_mgr()->get_add_images_form($id, $view);
                } else {
                    $content = esc_html__('You are not authorised to add images.', 'issues-map');
                }
                break;
            case SET_LOCATION_VIEW:
            case EDIT_LOCATION_VIEW:
                if ($this->can_add_or_edit($id)) {
                    $content = $this->_plugin->get_issue_content_mgr()->get_edit_location_form($id, $view);
                } else {
                    $content = esc_html__('You are not authorised to set issue locations.', 'issues-map');
                }
                break;
            case ADD_REPORT_VIEW:
            case EDIT_REPORT_VIEW:
                if ($this->can_add_or_edit($id)) {
                    $content = $this->_plugin->get_report_content_mgr()->get_edit_report_form($id, $view);
                } else {
                    $content = esc_html__('You are not authorised to add or edit issue reports.', 'issues-map');
                }
                break;
        }
        $content .= '<noscript><p class="im-message">Javascript is disabled so some features of this page may not be available.</p></noscript>';
        return $content;
    }
    
    /*
     * Get whether the current user can add a post or edit the specified post.
     */
    
    private function can_add_or_edit($post_id) {
        $auth_mgr = $this->_plugin->get_auth_mgr();
        return $post_id ? $auth_mgr->current_user_can_edit_post($post_id) : $auth_mgr->current_user_can_add_post();
    }
    
    /*
     * Get a menu of links to the main plugin pages.
     */
    
    private function get_links_menu($is_header = false) {
        $list_page_id = intval(get_option(OPTION_LIST_PAGE_ID, 0));
        $map_page_id = intval(get_option(OPTION_MAP_PAGE_ID, 0));
        $add_issue_page_id = intval(get_option(OPTION_ADD_ISSUE_PAGE_ID, 0));
        $content = $this->append_links_menu_item($list_page_id, __('Issues list', 'issues-map'));
        $content .= $this->append_links_menu_item($map_page_id, __('Issues map', 'issues-map'));
        $content .= $this->append_links_menu_item($add_issue_page_id, __('Submit issue', 'issues-map'));
        $content = trim($content, " |");

        if ($is_header) {        
            $content = '<div class="im-menu">' . $content . '<hr/></div>';
        }
        else {
            $content = '<div class="im-menu"><hr/>' . $content . '</div>';
        }
        
        return $content;        
    }
    
    private function append_links_menu_item($page_id, $text) {
        $content = '';
        if ($page_id !== get_the_ID()) {
            $permalink = get_permalink($page_id);
            if ($permalink) {
                $content = '<a href="' . $permalink . '">' . esc_html($text) . '</a> | ';
            }
        }
        return $content;
    }

    /*
     * Register custom query string parameters.
     */
    
    function query_vars_filter($qvars) {
        $qvars[] = 'view';
        return $qvars;
    }

    /*
     * Enable or disable comments on issues.
     */
    
    function comments_open_filter($open, $post_id) {
        if (is_singular(ISSUE_POST_TYPE)) {
            $open = get_query_var('view') === '' && $this->_plugin->get_auth_mgr()->current_user_can_comment($post_id);
        }
        return $open;
    }

    /*
     * Register edit_post_link filter to allow front-end editing.
     */
    
    function edit_post_link_filter($link, $id, $text) {
        if (get_post_type($id) === ISSUE_POST_TYPE) {
            $url = add_query_arg('view', EDIT_ISSUE_VIEW, get_permalink($id));
            $link = '<a class="' . esc_attr('post-edit-link') . '" href="' . esc_url($url) . '">' . $text . '</a>';
        }
        return $link;
    }

    /*
     * Filter issue post title.
     */

    public function the_title_filter($title) {
        // Check if we're inside the main loop in a single Post.
        if (!is_admin() && in_the_loop() && is_main_query()) {
            $view = get_query_var('view');
            switch ($view) {
                case '':
                    break;
                case LIST_VIEW:
                    $title = esc_html__('Issues list', 'issues-map');
                    break;
                case MAP_VIEW:
                    $title = esc_html__('Issues map', 'issues-map');
                    break;
                case ADD_ISSUE_VIEW:
                    $title = esc_html__('Submit issue', 'issues-map');
                    break;
                case ADD_IMAGES_VIEW:
                case EDIT_IMAGES_VIEW:
                    $title = esc_html__('Add images', 'issues-map');
                    break;
                case SET_LOCATION_VIEW:
                    $title = esc_html__('Set location', 'issues-map');
                    break;
                case EDIT_ISSUE_VIEW:
                    $title = esc_html__('Edit issue', 'issues-map');
                    break;
                case EDIT_LOCATION_VIEW:
                    $title = esc_html__('Edit location', 'issues-map');
                    break;
                case ADD_REPORT_VIEW:
                    $title = esc_html__('Create report', 'issues-map');
                    break;
                case EDIT_REPORT_VIEW:
                    $title = esc_html__('Edit report', 'issues-map');
                    break;
            }
        }

        return $title;
    }

    /*
     * Filter issue post content.
     */

    function the_content_filter($content) {
        // Check if we're inside the main loop in a single Post.
        if (!is_admin() && in_the_loop() && is_main_query()) {
            $post_id = get_the_ID();
            if (is_singular(ISSUE_POST_TYPE)) {
                $view = get_query_var('view');
                $atts = array(
                    'id' => $post_id,
                    'view' => $view ? $view : ISSUE_VIEW,
                );
                $content = $this->issues_map_shortcode_action($atts);
            } else if (is_singular(REPORT_POST_TYPE) || is_singular(REPORT_TEMPLATE_POST_TYPE)) {
                $view = get_query_var('view');
                $atts = array(
                    'id' => $post_id,
                    'view' => $view ? $view : REPORT_VIEW,
                );
                $content = $this->issues_map_shortcode_action($atts);
            }
            else if (get_option(OPTION_OVERRIDE_EXISTING_CONTENT, DEFAULT_OVERRIDE_EXISTING_CONTENT)) {
                if ($post_id === intval(get_option(OPTION_LIST_PAGE_ID, 0))) { 
                    $content = $this->issues_map_shortcode_action(array('view' => LIST_VIEW));
                }
                else if ($post_id === intval(get_option(OPTION_MAP_PAGE_ID, 0))) { 
                    $content = $this->issues_map_shortcode_action(array('view' => MAP_VIEW));
                }
                else if ($post_id === intval(get_option(OPTION_ADD_ISSUE_PAGE_ID, 0))) { 
                    $content = $this->issues_map_shortcode_action(array('view' => ADD_ISSUE_VIEW));
                }
            }
        }

        return $content;
    }

}
