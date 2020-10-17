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
 * Manages user identity and authorisation.
 * Stores a user ID using a cookie to allow support for both logged in 
 * and anonymous users.
 */
class AuthManager {

    private $_plugin;
    private $_user_settings = array();
    private $_is_moderator = false;

    public function __construct($plugin) {
        $this->_plugin = $plugin;
        
        $user_id = get_current_user_id();
        if ($user_id) {
            // User is logged in - use their settings stored in user meta data
            $this->_user_settings[META_USER_ID] = $user_id;
            // Store whether the user is a moderator
            $this->_is_moderator = $this->is_moderator($user_id);
        } else {
            // Anonymous user - store settings in a cookie (if possible)
            if (isset($_COOKIE[COOKIE_NAME])) {
                // Retrieve settings from cookie
                $cookie = stripslashes($_COOKIE[COOKIE_NAME]);
                $settings = json_decode($cookie, true);
                $this->_user_settings[META_USER_ID] = sanitize_text_field($settings[META_USER_ID]);
            } else {
                // Initialise settings and store in cookie
                $time = time();
                $this->_user_settings[META_USER_ID] = 'anon_' . dechex(rand());
                $cookie = json_encode($this->_user_settings);
                $expiry = $time + 60 * 60 * 24 * COOKIE_EXPIRY_DAYS;
                setcookie(COOKIE_NAME, $cookie, $expiry);
            }
        }
    }

    /*
     * Return whether a user is a moderator or not
     */
    
    public function is_moderator($user_id) {
        $is_moderator = false;
        $moderators_list = get_option(OPTION_MODERATORS_LIST, '');
        $moderator_ids = explode(',', $moderators_list);
        foreach ($moderator_ids as $mod_id) {
            if (intval($mod_id) === $user_id) {
                $is_moderator = true;
                break;
            }
        }
        return $is_moderator;
    }
    
    /*
     * Get a user setting.
     */

    public function get_val($key) {
        $val = '';
        if (isset($this->_user_settings[$key])) {
            $val = $this->_user_settings[$key];
        }
        return $val;
    }

    /*
     * Return whether the current user can add issues and issue reports.
     */

    public function current_user_can_add_post() {
        $authorised = false;
        $user_id = get_current_user_id();
        if ($user_id) {
            $authorised = $this->_is_moderator || get_option(OPTION_CAN_LOGGED_IN_ADD_ISSUE, DEFAULT_CAN_LOGGED_IN_ADD_ISSUE);
        } else {
            $authorised = get_option(OPTION_CAN_ANON_ADD_ISSUE, DEFAULT_CAN_ANON_ADD_ISSUE);
        }
        return $authorised;
    }

    /*
     * Return whether the current user can edit the specified post.
     */

    public function current_user_can_edit_post($post_id) {
        $authorised = false;
        $post = get_post($post_id);
        if ($post) {
            $user_id = get_current_user_id();
            if ($user_id) {
                // Logged in user
                // Author and moderators can edit
                $authorised = $this->_is_moderator || ($post->post_author === (string) $user_id);
            } else {
                // Anonymous user can edit if they created the post
                $user_id = $this->get_val(META_USER_ID);
                $authorised = $user_id && $user_id === get_post_meta($post_id, META_USER_ID, true);
            }
        }

        return $authorised;
    }

    /*
     * Return whether the current user can add issues and issue reports.
     */

    public function current_user_can_upload_images() {
        $authorised = false;
        $user_id = get_current_user_id();
        if ($user_id) {
            $authorised = $this->_is_moderator || get_option(OPTION_CAN_LOGGED_IN_UPLOAD_IMAGES, DEFAULT_CAN_LOGGED_IN_UPLOAD_IMAGES);
        } else {
            $authorised = get_option(OPTION_CAN_ANON_UPLOAD_IMAGES, DEFAULT_CAN_ANON_UPLOAD_IMAGES);
        }
        return $authorised;
    }

    /*
     * Return whether the current user can add issues and issue reports.
     */

    public function current_user_can_comment() {
        $authorised = false;
        $user_id = get_current_user_id();
        if ($user_id) {
            $authorised = $this->_is_moderator || get_option(OPTION_CAN_LOGGED_IN_COMMENT, DEFAULT_CAN_LOGGED_IN_COMMENT);
        } else {
            $authorised = get_option(OPTION_CAN_ANON_COMMENT, DEFAULT_CAN_ANON_COMMENT);
        }
        return $authorised;
    }

    /*
     * Return whether the current user can send issue reports to moderators.
     */

    public function current_user_can_send_reports() {
        $authorised = false;        
        $user_id = get_current_user_id();
        if ($user_id) {
            $authorised = $this->_is_moderator || get_option(OPTION_CAN_LOGGED_IN_SEND_REPORTS, DEFAULT_CAN_LOGGED_IN_SEND_REPORTS);
        } else {
            $authorised = get_option(OPTION_CAN_ANON_SEND_REPORTS, DEFAULT_CAN_ANON_SEND_REPORTS);
        }
        return $authorised;
    }

    /*
     * Return whether the current user can send issue reports to moderators.
     */

    public function current_user_can_send_reports_to_anyone() {
        $authorised = false;        
        $user_id = get_current_user_id();
        if ($user_id) {
            $authorised = $this->_is_moderator || get_option(OPTION_CAN_LOGGED_IN_SEND_REPORTS_TO_ANYONE, DEFAULT_CAN_LOGGED_IN_SEND_REPORTS_TO_ANYONE);
        } else {
            $authorised = get_option(OPTION_CAN_ANON_SEND_REPORTS_TO_ANYONE, DEFAULT_CAN_ANON_SEND_REPORTS_TO_ANYONE);
        }
        return $authorised;
    }

}
