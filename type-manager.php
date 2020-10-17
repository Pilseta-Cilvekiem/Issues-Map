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

require_once 'utils/wp-utils.php';

/*
 * Registers custom post types and taxonomies.
 */

class TypeManager {

    /*
     * Register custom post type for storing issues.
     */
    
    public function register_issue_cpt() {
        $labels = array(
            'name' => __('Issues', 'issues-map'),
            'singular_name' => __('Issue', 'issues-map'),
            'menu_name' => __('Issues', 'issues-map'),
            'name_admin_bar' => __('Issue', 'issues-map'),
            'add_new' => __("Add New", 'issues-map'),
            'add_new_item' => __('Add New Issue', 'issues-map'),
            'new_item' => __('New Issue', 'issues-map'),
            'edit_item' => __('Edit Issue', 'issues-map'),
            'view_item' => __('View Issue', 'issues-map'),
            'all_items' => __('All Issues', 'issues-map'),
            'search_items' => __('Search Issues', 'issues-map'),
            'not_found' => __('No issues found.', 'issues-map'),
        );

        WPUtils::register_custom_post_type(
                ISSUE_POST_TYPE,
                ISSUE_POST_TYPE_PLURAL,
                $labels,
                array('title', 'comments'/*, 'custom-fields'*/),
                array(
                    'show_ui' => false /*true*/,
                    'show_in_menu' => false /*true*/,
                    'public' => true,
                    'has_archive' => false,
                    'hierarchical' => false,
                )
        );
    }
    
    /*
     * Register custom post type for storing reports.
     */
    
    public function register_report_cpt() {
        $labels = array(
            'name' => __('Reports', 'issues-map'),
            'singular_name' => __('Report', 'issues-map'),
            'menu_name' => __('Reports', 'issues-map'),
            'name_admin_bar' => __('Report', 'issues-map'),
            'add_new' => __("Add New", 'issues-map'),
            'add_new_item' => __('Add New Report', 'issues-map'),
            'new_item' => __('New Report', 'issues-map'),
            'edit_item' => __('Edit Report', 'issues-map'),
            'view_item' => __('View Report', 'issues-map'),
            'all_items' => __('All Report', 'issues-map'),
            'search_items' => __('Search Reports', 'issues-map'),
            'not_found' => __('No reports found.', 'issues-map'),
        );

        WPUtils::register_custom_post_type(
                REPORT_POST_TYPE,
                REPORT_POST_TYPE_PLURAL,
                $labels,
                array('title',),
                array(
                    'show_ui' => false,
                    'show_in_menu' => false,
                    'public' => true,
                    'has_archive' => false,
                    'hierarchical' => false,
                )
        );
    }
    
    /*
     * Register custom post type for storing report templates.
     */
    
    public function register_report_template_cpt() {
        $labels = array(
            'name' => __('Report Templates', 'issues-map'),
            'singular_name' => __('Report Template', 'issues-map'),
            'menu_name' => __('Report Templates', 'issues-map'),
            'name_admin_bar' => __('Report Template', 'issues-map'),
            'add_new' => __("Add New", 'issues-map'),
            'add_new_item' => __('Add New Report Template', 'issues-map'),
            'new_item' => __('New Report Template', 'issues-map'),
            'edit_item' => __('Edit Report Template', 'issues-map'),
            'view_item' => __('View Report Template', 'issues-map'),
            'all_items' => __('All Report Templates', 'issues-map'),
            'search_items' => __('Search Report Templates', 'issues-map'),
            'not_found' => __('No report templates found.', 'issues-map'),
        );

        WPUtils::register_custom_post_type(
                REPORT_TEMPLATE_POST_TYPE,
                REPORT_TEMPLATE_POST_TYPE_PLURAL,
                $labels,
                array('title'/* , 'custom-fields' */),
                array(
                    'show_ui' => true,
                    'show_in_menu' => false,
                    'public' => true,
                    'has_archive' => false,
                    'hierarchical' => false,
                )
        );
    }

    /*
     * Register the issue category taxonomy.
     */
    
    public function register_issue_category_taxonomy() {
        $labels = array(
            'name' => __('Issue Categories', 'issues-map'),
            'singular_name' => __('Issue Category', 'issues-map'),
            'menu_name' => __('Issue Categories', 'issues-map'),
            'all_items' => __('All Issue Categories', 'issues-map'),
            'edit_item' => __('Edit Issue Category', 'issues-map'),
            'view_item' => __('View Issue Category', 'issues-map'),
            'update_item' => __('Update Issue Category', 'issues-map'),
            'add_new_item' => __('Add New Issue Category', 'issues-map'),
            'new_item_name' => __('New Issue Category Name', 'issues-map'),
            'parent_item' => __('Parent Issue Category', 'issues-map'),
            'parent_item_colon' => __('Parent Issue Category:', 'issues-map'),
            'search_items' => __('Search Issue Categories', 'issues-map'),
            'popular_items' => __('Popular Issue Categories', 'issues-map'),
            'separate_items_with_commas' => __('Separate issue categories with commas', 'issues-map'),
            'add_or_remove_items' => __('Add or remove issue categories', 'issues-map'),
            'choose_from_most_used' => __('Choose from the most used issue categories', 'issues-map'),
            'not_found' => __('No issue categories found', 'issues-map'),
            'back_to_items' => __('Back to issue categories', 'issues-map'),
        );

        WPUtils::register_taxonomy(
                ISSUE_CATEGORY_TAXONOMY,
                ISSUE_CATEGORY_TAXONOMY_PLURAL,
                array(ISSUE_POST_TYPE, REPORT_TEMPLATE_POST_TYPE),
                $labels,
                array(
                    'show_ui' => true,
                    'hierarchical' => true,
                )
        );
    }

    /*
     * Register the issue status taxonomy.
     */
    
    public function register_issue_status_taxonomy() {
        $labels = array(
            'name' => __('Issue Statuses', 'issues-map'),
            'singular_name' => __('Issue Status', 'issues-map'),
            'menu_name' => __('Issue Statuses', 'issues-map'),
            'all_items' => __('All Issue Statuses', 'issues-map'),
            'edit_item' => __('Edit Issue Status', 'issues-map'),
            'view_item' => __('View Issue Status', 'issues-map'),
            'update_item' => __('Update Issue Status', 'issues-map'),
            'add_new_item' => __('Add New Issue Status', 'issues-map'),
            'new_item_name' => __('New Issue Status Name', 'issues-map'),
            'parent_item' => __('Parent Issue Status', 'issues-map'),
            'parent_item_colon' => __('Parent Issue Status:', 'issues-map'),
            'search_items' => __('Search Issue Statuses', 'issues-map'),
            'popular_items' => __('Popular Issue Statuses', 'issues-map'),
            'separate_items_with_commas' => __('Separate issue statuses with commas', 'issues-map'),
            'add_or_remove_items' => __('Add or remove issue statuses', 'issues-map'),
            'choose_from_most_used' => __('Choose from the most used issue statuses', 'issues-map'),
            'not_found' => __('No issue statuses found', 'issues-map'),
            'back_to_items' => __('Back to issue statuses', 'issues-map'),
        );

        WPUtils::register_taxonomy(
                ISSUE_STATUS_TAXONOMY,
                ISSUE_STATUS_TAXONOMY_PLURAL,
                array(ISSUE_POST_TYPE),
                $labels,
                array(
                    'show_ui' => true,
                    'hierarchical' => false,
                )
        );
    }
    
}
