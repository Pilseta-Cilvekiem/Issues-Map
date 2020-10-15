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

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

require_once 'constants.php';
require_once 'admin-manager.php';
require_once 'utils/wp-utils.php';

/*
 * Deletes plugin data and settings when the plugin is deleted.
 */

function issues_map_delete_plugin() {
    // Delete custom posts
    $posts = get_posts(array(
        'post_type' => array(
            ISSUE_POST_TYPE,
            REPORT_POST_TYPE,
            REPORT_TEMPLATE_POST_TYPE,
        ),
        'post_status' => 'any',
        'numberposts' => -1
            ));

    foreach ($posts as $post) {
        wp_delete_post($post->ID, true);
    }

    // Delete issue categories
    WPUtils::delete_custom_terms(ISSUE_CATEGORY_TAXONOMY);

    // Delete issue statuses
    WPUtils::delete_custom_terms(ISSUE_STATUS_TAXONOMY);

    // Delete plugin's uploads subdirectory
    $admin_mgr = new AdminManager();
    $admin_mgr->delete_uploads_dir();

    // Delete automatically created plugin pages
    $admin_mgr->delete_plugin_pages();

    // Delete plugin settings
    $admin_mgr->delete_settings();
}

issues_map_delete_plugin();
