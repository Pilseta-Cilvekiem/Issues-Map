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

require_once 'admin-manager.php';

/*
 * Deletes plugin data and settings when the plugin is deleted.
 */

function issues_map_delete_plugin() {

    $admin_mgr = new AdminManager();

    $admin_mgr->delete_custom_posts();
    $admin_mgr->delete_taxonomy_terms();
    $admin_mgr->delete_uploads_dir();
    $admin_mgr->delete_plugin_pages();
    $admin_mgr->delete_settings();
}

issues_map_delete_plugin();
