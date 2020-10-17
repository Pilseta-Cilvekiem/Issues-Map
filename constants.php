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

// Debug/demo modes
define('IssuesMap\DEBUG_VERSION', false);
define('IssuesMap\DEMO_VERSION', false);

// Slug for the custom post type used to store issues
define('IssuesMap\PLUGIN_NAME', 'issues-map');
define('IssuesMap\PLUGIN_BUILD_NUMBER', '5');     // Increment this to invalidate users' CSS / JS cache
define('IssuesMap\COOKIE_NAME', 'issues-map');
define('IssuesMap\COOKIE_EXPIRY_DAYS', 28);
define('IssuesMap\IMAGES_FOLDER_NAME', 'issues-map');

// Custom post types / taxonomies
// Note: Taxonomy names are hardwired in some places in the code to allow string translation.
define('IssuesMap\ISSUE_POST_TYPE', 'issue');
define('IssuesMap\ISSUE_POST_TYPE_PLURAL', 'issues');
define('IssuesMap\REPORT_POST_TYPE', 'report');
define('IssuesMap\REPORT_POST_TYPE_PLURAL', 'reports');
define('IssuesMap\REPORT_TEMPLATE_POST_TYPE', 'report_template');
define('IssuesMap\REPORT_TEMPLATE_POST_TYPE_PLURAL', 'report_templates');
define('IssuesMap\ISSUE_CATEGORY_TAXONOMY', 'issue_category');
define('IssuesMap\ISSUE_CATEGORY_TAXONOMY_PLURAL', 'issue_categories');
define('IssuesMap\ISSUE_STATUS_TAXONOMY', 'issue_status');
define('IssuesMap\ISSUE_STATUS_TAXONOMY_PLURAL', 'issue_statuses');

// Shortcodes
define('IssuesMap\ISSUES_MAP_SHORTCODE', 'issues-map');

// Plugin pages
define('IssuesMap\LIST_PAGE_SLUG', 'issues-list');
define('IssuesMap\MAP_PAGE_SLUG', 'issues-map');
define('IssuesMap\ADD_ISSUE_PAGE_SLUG', 'add-issue');

// Types of view
define('IssuesMap\LIST_VIEW', 'list');
define('IssuesMap\MAP_VIEW', 'map');
define('IssuesMap\ISSUE_VIEW', 'issue');
define('IssuesMap\ADD_ISSUE_VIEW', 'add-issue');
define('IssuesMap\EDIT_ISSUE_VIEW', 'edit-issue');
define('IssuesMap\ADD_IMAGES_VIEW', 'add-images');
define('IssuesMap\EDIT_IMAGES_VIEW', 'edit-images');
define('IssuesMap\SET_LOCATION_VIEW', 'set-location');
define('IssuesMap\EDIT_LOCATION_VIEW', 'edit-location');
define('IssuesMap\REPORT_VIEW', 'report');
define('IssuesMap\ADD_REPORT_VIEW', 'add-report');
define('IssuesMap\EDIT_REPORT_VIEW', 'edit-report');

// Plugin settings
define('IssuesMap\SETTINGS_GROUP_NAME', 'issues-map-settings-group');
define('IssuesMap\OPTION_PLUGIN_INITIALIZED', 'im_plugin_initialized');
define('IssuesMap\OPTION_LIST_PAGE_ID', 'im_list_page_id');
define('IssuesMap\OPTION_MAP_PAGE_ID', 'im_map_page_id');
define('IssuesMap\OPTION_ADD_ISSUE_PAGE_ID', 'im_add_issue_page_id');
define('IssuesMap\OPTION_OVERRIDE_EXISTING_CONTENT', 'im_override_existing_content');
define('IssuesMap\OPTION_OPEN_IN_NEW_TAB', 'im_open_in_new_tab');
define('IssuesMap\OPTION_SHOW_HEADER_LINKS', 'im_show_header_links');
define('IssuesMap\OPTION_SHOW_FOOTER_LINKS', 'im_show_footer_links');
define('IssuesMap\OPTION_GMAPS_API_KEY', 'im_gmaps_api_key');
define('IssuesMap\OPTION_CENTRE_LAT', 'im_centre_lat');
define('IssuesMap\OPTION_CENTRE_LNG', 'im_centre_lng');
define('IssuesMap\OPTION_ZOOM_MAP_VIEW', 'im_zoom_map_view');
define('IssuesMap\OPTION_ZOOM_ISSUE_VIEW', 'im_zoom_issue_view');
define('IssuesMap\OPTION_INCLUDE_IMAGES_IN_REPORTS', 'im_include_images_in_reports');
define('IssuesMap\OPTION_CAN_LOGGED_IN_ADD_ISSUE', 'im_can_logged_in_add_issue');
define('IssuesMap\OPTION_CAN_LOGGED_IN_UPLOAD_IMAGES', 'im_can_logged_in_upload_images');
define('IssuesMap\OPTION_CAN_LOGGED_IN_COMMENT', 'im_can_logged_in_comment');
define('IssuesMap\OPTION_CAN_LOGGED_IN_SEND_REPORTS', 'im_can_logged_in_reports');
define('IssuesMap\OPTION_CAN_LOGGED_IN_SEND_REPORTS_TO_ANYONE', 'im_can_logged_in_send_reports_to_anyone');
define('IssuesMap\OPTION_CAN_ANON_ADD_ISSUE', 'im_can_anon_add_issue');
define('IssuesMap\OPTION_CAN_ANON_UPLOAD_IMAGES', 'im_can_anon_upload_images');
define('IssuesMap\OPTION_CAN_ANON_COMMENT', 'im_can_anon_comment');
define('IssuesMap\OPTION_CAN_ANON_SEND_REPORTS', 'im_can_anon_send_reports');
define('IssuesMap\OPTION_CAN_ANON_SEND_REPORTS_TO_ANYONE', 'im_can_anon_send_reports_to_anyone');
define('IssuesMap\OPTION_MODERATOR_EMAIL', 'im_moderator_email');
define('IssuesMap\OPTION_MODERATORS_LIST', 'im_moderators_list');
define('IssuesMap\OPTION_ONLY_SEND_REPORTS_TO_USERS', 'im_only_send_reports_to_users');

// Defaults
define('IssuesMap\DEFAULT_OVERRIDE_EXISTING_CONTENT', true);
define('IssuesMap\DEFAULT_OPEN_IN_NEW_TAB', false);
define('IssuesMap\DEFAULT_SHOW_HEADER_LINKS', false);
define('IssuesMap\DEFAULT_SHOW_FOOTER_LINKS', true);
define('IssuesMap\DEFAULT_INCLUDE_IMAGES_IN_REPORTS', true);
define('IssuesMap\DEFAULT_CAN_LOGGED_IN_ADD_ISSUE', true);
define('IssuesMap\DEFAULT_CAN_ANON_ADD_ISSUE', true);
define('IssuesMap\DEFAULT_CAN_LOGGED_IN_UPLOAD_IMAGES', true);
define('IssuesMap\DEFAULT_CAN_ANON_UPLOAD_IMAGES', true);
define('IssuesMap\DEFAULT_CAN_LOGGED_IN_COMMENT', true);
define('IssuesMap\DEFAULT_CAN_ANON_COMMENT', false);
define('IssuesMap\DEFAULT_CAN_LOGGED_IN_SEND_REPORTS', true);
define('IssuesMap\DEFAULT_CAN_ANON_SEND_REPORTS', false);
define('IssuesMap\DEFAULT_CAN_LOGGED_IN_SEND_REPORTS_TO_ANYONE', false);
define('IssuesMap\DEFAULT_CAN_ANON_SEND_REPORTS_TO_ANYONE', false);
define('IssuesMap\DEFAULT_POSTS_PER_PAGE', 10);
define('IssuesMap\DEFAULT_COLOR', '#aaa');
define('IssuesMap\DEFAULT_REPORT_CREATED_COLOR', '#1ea5ce');
define('IssuesMap\DEFAULT_REPORT_SENT_COLOR', '#9a72ad');
define('IssuesMap\DEFAULT_ICON_NAME', 'place');
define('IssuesMap\DEFAULT_MARKER_ICON_PATH', 'M22-48h-44v43h16l6 5 6-5h16z');
define('IssuesMap\DEFAULT_CENTRE_LAT', 56.9514934);
define('IssuesMap\DEFAULT_CENTRE_LNG', 24.1111156);
define('IssuesMap\DEFAULT_ZOOM_MAP_VIEW', 11);
define('IssuesMap\DEFAULT_ZOOM_ISSUE_VIEW', 16);

// Issue categories
define('IssuesMap\DEFAULT_CATEGORY_ICON_NAME', 'topic');
define('IssuesMap\DEFAULT_ISSUE_CATEGORY', 'Uncategorized');
define('IssuesMap\DEFAULT_ISSUE_CATEGORY_SLUG', 'uncategorized');

// Issue statuses
define('IssuesMap\ISSUE_STATUS_ICON_NAME', 'assignment_turned_in');
define('IssuesMap\ISSUE_STATUS_UNREPORTED', 'Unreported');
define('IssuesMap\ISSUE_STATUS_UNREPORTED_SLUG', 'unreported');
define('IssuesMap\ISSUE_STATUS_REPORT_CREATED', 'Report created');
define('IssuesMap\ISSUE_STATUS_REPORT_CREATED_SLUG', 'report_created');
define('IssuesMap\ISSUE_STATUS_REPORT_SENT', 'Report sent');
define('IssuesMap\ISSUE_STATUS_REPORT_SENT_SLUG', 'report_sent');

// Maximum string lengths
define('IssuesMap\MAX_LEN_32', 32);
define('IssuesMap\MAX_LEN_64', 64);
define('IssuesMap\MAX_LEN_100', 100);
define('IssuesMap\MAX_LEN_128', 128);
define('IssuesMap\MAX_LEN_200', 200);
define('IssuesMap\MAX_LEN_256', 256);
define('IssuesMap\MAX_LEN_1024', 1024);
define('IssuesMap\MAX_DESCRIPTION_LEN', 4096);
define('IssuesMap\MAX_REPORT_LEN', 4096);

// Maps
define('IssuesMap\MAP_STATIC_IMAGE_WIDTH', 480);
define('IssuesMap\MAP_STATIC_IMAGE_HEIGHT', 360);

// Image upload
define('IssuesMap\SUPPORTED_IMAGE_TYPES', 'jpg|jpeg|png');
define('IssuesMap\MAX_IMAGE_FILE_SIZE', 10000000);
define('IssuesMap\THUMBNAIL_WIDTH', 240);

// Custom fields (meta data fields) for issues and reports
define('IssuesMap\META_LATITUDE', 'Latitude');
define('IssuesMap\META_LONGITUDE', 'Longitude');
define('IssuesMap\META_FEATURED_IMAGE', 'FeaturedImage');
define('IssuesMap\META_IMAGE_DATA', 'ImageData');
define('IssuesMap\META_FILENAME', 'Filename');
define('IssuesMap\META_TIMESTAMP', 'Timestamp');
define('IssuesMap\META_ADDED_BY', 'AddedBy');
define('IssuesMap\META_EMAIL_ADDRESS', 'EmailAddress');
define('IssuesMap\META_ISSUE_ID', 'IssueId');
define('IssuesMap\META_TEMPLATE_ID', 'TemplateId');
define('IssuesMap\META_FROM_ADDRESS', 'FromAddress');
define('IssuesMap\META_FROM_EMAIL', 'FromEmail');
define('IssuesMap\META_TO_ADDRESS', 'ToAddress');
define('IssuesMap\META_DATE', 'Date');
define('IssuesMap\META_GREETING', 'Greeting');
define('IssuesMap\META_ADDRESSEE', 'Addressee');
define('IssuesMap\META_SIGN_OFF', 'SignOff');
define('IssuesMap\META_RECIPIENT_NAME', 'Recipient');
define('IssuesMap\META_RECIPIENT_EMAIL', 'RecipientEmail');
define('IssuesMap\META_EMAIL_BODY', 'EmailBody');
define('IssuesMap\META_DATE_SENT', 'DateSent');
define('IssuesMap\META_REF', 'Ref');
define('IssuesMap\META_SALT', 'Salt');
define('IssuesMap\META_USER_ID', 'UserId');
define('IssuesMap\META_ICON_NAME', 'IconName');
define('IssuesMap\META_COLOR', 'Color');
