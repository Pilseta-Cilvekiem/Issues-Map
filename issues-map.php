<?php

/*
  Plugin Name: Issues Map
  Plugin URI: https://github.com/Pilseta-Cilvekiem/Issues-Map
  Description: Submit issues, view them in a list or on a map, save them as PDFs, send them by email.
  Version: 1.1
  Author: Tim Brogden
  Author URI: https://github.com/Tim-Brogden
  License: GPLv3
  License URI: https://www.gnu.org/licenses/gpl-3.0.html
  Text Domain: issues-map
  Domain Path: /languages
 */
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

if (!defined('WPINC')) {
    die;
}

require_once 'constants.php';
require_once 'utils/utils.php';
require_once 'utils/wp-utils.php';
require_once 'user-profile.php';
require_once 'report-content.php';
require_once 'report-data.php';
require_once 'issue-data.php';
require_once 'issue-content.php';
require_once 'filterable-view.php';
require_once 'issues-list.php';
require_once 'map-view.php';
require_once 'admin-manager.php';
require_once 'async-manager.php';
require_once 'content-manager.php';
require_once 'issue-category.php';
require_once 'issue-status.php';

/*
 * Implements the issues-map plugin.
 */

class IssuesMapPlugin {

    private $_upload_dir;
    private $_upload_url;
    private $_admin_mgr;
    private $_async_mgr;
    private $_content_mgr;
    private $_user_profile;
    private $_issue_data_mgr;
    private $_issue_content_mgr;
    private $_report_data_mgr;
    private $_report_content_mgr;
    private $_issue_category_mgr;
    private $_issue_status_mgr;
    
    public function __construct() {

        // Initialise plugin modules
        $this->_admin_mgr = new AdminManager($this);
        $this->_content_mgr = new ContentManager($this);
        $this->_async_mgr = new AsyncManager($this);
        $this->_user_profile = new UserProfile($this);
        $this->_issue_data_mgr = new IssueDataManager($this);
        $this->_issue_content_mgr = new IssueContentManager($this);
        $this->_report_data_mgr = new ReportDataManager($this);
        $this->_report_content_mgr = new ReportContentManager($this);
        $this->_issue_category_mgr = new IssueCategoryManager($this);
        $this->_issue_status_mgr = new IssueStatusManager($this);

        // Add initialisation / deactivation actions
        add_action('init', array($this, 'init_action'));
        register_deactivation_hook( __FILE__, array($this, 'deactivation_hook') );
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain_action'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts_action'));
        
        // Add admin functionality
        add_action('admin_init', array($this->_admin_mgr, 'admin_init_action'));
        add_action('admin_menu', array($this->_admin_mgr, 'admin_menu_action'));
        add_action('admin_enqueue_scripts', array($this->_admin_mgr, 'admin_enqueue_scripts_action'));
        add_action('save_post', array($this->_admin_mgr, 'save_post_action'));
        add_action('delete_post', array($this->_admin_mgr, 'delete_post_action'));
        
        // Issue category taxonomy
        add_action(ISSUE_CATEGORY_TAXONOMY . '_add_form_fields', array($this->_issue_category_mgr, 'add_issue_category_form_fields'));
        add_action(ISSUE_CATEGORY_TAXONOMY . '_edit_form_fields', array($this->_issue_category_mgr, 'edit_issue_category_form_fields'));
        add_action('edited_' . ISSUE_CATEGORY_TAXONOMY, array($this->_issue_category_mgr, 'save_issue_category_meta'));
        add_action('create_' . ISSUE_CATEGORY_TAXONOMY, array($this->_issue_category_mgr, 'save_issue_category_meta'));
        add_filter('manage_edit-' . ISSUE_CATEGORY_TAXONOMY . '_columns', array($this->_issue_category_mgr, 'manage_edit_issue_category_columns'));
        add_action('manage_' . ISSUE_CATEGORY_TAXONOMY . '_custom_column', array($this->_issue_category_mgr, 'manage_issue_category_custom_column'), 10, 3);

        // Issue status taxonomy
        add_action(ISSUE_STATUS_TAXONOMY . '_add_form_fields', array($this->_issue_status_mgr, 'add_issue_status_form_fields'));
        add_action(ISSUE_STATUS_TAXONOMY . '_edit_form_fields', array($this->_issue_status_mgr, 'edit_issue_status_form_fields'));
        add_action('edited_' . ISSUE_STATUS_TAXONOMY, array($this->_issue_status_mgr, 'save_issue_status_meta'));
        add_action('create_' . ISSUE_STATUS_TAXONOMY, array($this->_issue_status_mgr, 'save_issue_status_meta'));
        add_filter('manage_edit-' . ISSUE_STATUS_TAXONOMY . '_columns', array($this->_issue_status_mgr, 'manage_edit_issue_status_columns'));
        add_action('manage_' . ISSUE_STATUS_TAXONOMY . '_custom_column', array($this->_issue_status_mgr, 'manage_issue_status_custom_column'), 10, 3);

        // Add short codes
        add_shortcode(ISSUES_MAP_SHORTCODE, array($this->_content_mgr, 'issues_map_shortcode_action'));

        // Add filters
        add_filter('query_vars', array($this->_content_mgr, 'query_vars_filter'));
        add_filter('comments_open', array($this->_content_mgr, 'comments_open_filter'), 10, 2);
        add_filter('edit_post_link', array($this->_content_mgr, 'edit_post_link_filter'), 10, 3);
        add_filter('the_title', array($this->_content_mgr, 'the_title_filter'));
        add_filter('the_content', array($this->_content_mgr, 'the_content_filter'));

        // Add AJAX callbacks
        add_action('wp_ajax_edit_details_async', array($this->_async_mgr, 'edit_details_async'));
        add_action('wp_ajax_nopriv_edit_details_async', array($this->_async_mgr, 'edit_details_async'));
        add_action('wp_ajax_add_images_async', array($this->_async_mgr, 'add_images_async'));
        add_action('wp_ajax_nopriv_add_images_async', array($this->_async_mgr, 'add_images_async'));
        add_action('wp_ajax_cancel_add_images_async', array($this->_async_mgr, 'cancel_add_images_async'));
        add_action('wp_ajax_nopriv_cancel_add_images_async', array($this->_async_mgr, 'cancel_add_images_async'));
        add_action('wp_ajax_edit_location_async', array($this->_async_mgr, 'edit_location_async'));
        add_action('wp_ajax_nopriv_edit_location_async', array($this->_async_mgr, 'edit_location_async'));
        add_action('wp_ajax_edit_report_async', array($this->_async_mgr, 'edit_report_async'));
        add_action('wp_ajax_nopriv_edit_report_async', array($this->_async_mgr, 'edit_report_async'));
        add_action('wp_ajax_send_report_async', array($this->_async_mgr, 'send_report_async'));
        add_action('wp_ajax_nopriv_send_report_async', array($this->_async_mgr, 'send_report_async'));
        add_action('wp_ajax_download_report_async', array($this->_async_mgr, 'download_report_async'));
        add_action('wp_ajax_nopriv_download_report_async', array($this->_async_mgr, 'download_report_async'));
        add_action('wp_ajax_delete_issue_image_async', array($this->_async_mgr, 'delete_issue_image_async'));
        add_action('wp_ajax_nopriv_delete_issue_image_async', array($this->_async_mgr, 'delete_issue_image_async'));
        add_action('wp_ajax_set_featured_image_async', array($this->_async_mgr, 'set_featured_image_async'));
        add_action('wp_ajax_nopriv_set_featured_image_async', array($this->_async_mgr, 'set_featured_image_async'));
        add_action('wp_ajax_dnd_codedropz_upload', array($this->_async_mgr, 'file_uploaded_async'));
        add_action('wp_ajax_nopriv_dnd_codedropz_upload', array($this->_async_mgr, 'file_uploaded_async'));
        add_action('wp_ajax_dnd_codedropz_upload_delete', array($this->_async_mgr, 'upload_deleted_async'));
        add_action('wp_ajax_nopriv_dnd_codedropz_upload_delete', array($this->_async_mgr, 'upload_deleted_async'));
        add_action('wp_ajax_delete_issue_async', array($this->_async_mgr, 'delete_issue_async'));
        add_action('wp_ajax_nopriv_delete_issue_async', array($this->_async_mgr, 'delete_issue_async'));
        add_action('wp_ajax_delete_report_async', array($this->_async_mgr, 'delete_report_async'));
        add_action('wp_ajax_nopriv_delete_report_async', array($this->_async_mgr, 'delete_report_async'));
        add_action('wp_ajax_get_issues_list_async', array($this->_async_mgr, 'get_issues_list_async'));
        add_action('wp_ajax_nopriv_get_issues_list_async', array($this->_async_mgr, 'get_issues_list_async'));
        add_action('wp_ajax_get_map_items_async', array($this->_async_mgr, 'get_map_items_async'));
        add_action('wp_ajax_nopriv_get_map_items_async', array($this->_async_mgr, 'get_map_items_async'));
        add_action('wp_ajax_get_info_window_content_async', array($this->_async_mgr, 'get_info_window_content_async'));
        add_action('wp_ajax_nopriv_get_info_window_content_async', array($this->_async_mgr, 'get_info_window_content_async'));
    }

    /*
     * Accessors.
     */

    public function get_issue_content_mgr() {
        return $this->_issue_content_mgr;
    }

    public function get_issue_data_mgr() {
        return $this->_issue_data_mgr;
    }

    public function get_report_content_mgr() {
        return $this->_report_content_mgr;
    }

    public function get_report_data_mgr() {
        return $this->_report_data_mgr;
    }

    public function get_user_profile() {
        return $this->_user_profile;
    }

    public function get_async_manager() {
        return $this->_async_mgr;
    }

    public function get_upload_dir() {
        return $this->_upload_dir;
    }

    public function get_upload_url() {
        return $this->_upload_url;
    }

    /*
     * Plugin initialisation.
     */

    public function init_action() {
        WPUtils::register_taxonomy(
                ISSUE_CATEGORY_TAXONOMY,
                ISSUE_CATEGORY_TAXONOMY_PLURAL,
                array(ISSUE_POST_TYPE, REPORT_TEMPLATE_POST_TYPE),
                array(
                    'show_ui' => true,
                    'hierarchical' => true,
                )
        );

        WPUtils::register_taxonomy(
                ISSUE_STATUS_TAXONOMY,
                ISSUE_STATUS_TAXONOMY_PLURAL,
                array(ISSUE_POST_TYPE),
                array(
                    'show_ui' => true,
                    'hierarchical' => false,
                )
        );

        WPUtils::register_custom_post_type(
                ISSUE_POST_TYPE,
                ISSUE_POST_TYPE_PLURAL,
                array('title', 'comments'/*, 'custom-fields'*/),
                array(
                    'show_ui' => false /*true*/,
                    'show_in_menu' => false /*true*/,
                    'public' => true,
                    'has_archive' => false,
                    'hierarchical' => false,
                )
        );
        WPUtils::register_custom_post_type(
                REPORT_POST_TYPE,
                REPORT_POST_TYPE_PLURAL,
                array('title',),
                array(
                    'show_ui' => false,
                    'show_in_menu' => false,
                    'public' => true,
                    'has_archive' => false,
                    'hierarchical' => false,
                )
        );
        WPUtils::register_custom_post_type(
                REPORT_TEMPLATE_POST_TYPE,
                REPORT_TEMPLATE_POST_TYPE_PLURAL,
                array('title'/* , 'custom-fields' */),
                array(
                    'show_ui' => true,
                    'show_in_menu' => false,
                    'public' => true,
                    'has_archive' => false,
                    'hierarchical' => false,
                )
        );

        // Create upload dir if required
        $this->_upload_dir = $this->_admin_mgr->init_uploads_dir();
        $upload_dir = wp_get_upload_dir();
        $this->_upload_url = $upload_dir['baseurl'] . '/' . IMAGES_FOLDER_NAME . '/';

        // Initialise user data
        $this->_user_profile->init();
    }
    
    /* Handle plugin deactivation. */
    
    function deactivation_hook() {
    }

    /*
     * Load the text domain to allow translation.
     */

    function load_plugin_textdomain_action() {
        load_plugin_textdomain('issues-map', FALSE, basename(dirname(__FILE__)) . '/languages/');
    }

    /*
     * Register front end scripts and styles.
     */

    public function enqueue_scripts_action() {

        // Styles
        wp_enqueue_style('dashicons');
        wp_enqueue_style('material-icons', 'https://fonts.googleapis.com/icon?family=Material+Icons');
        wp_enqueue_style('jquery-ui',
                plugins_url('/css/jquery-ui.min.css', __FILE__));
        wp_enqueue_style('issues-map',
                plugins_url('/css/issues-map.css', __FILE__), array(), PLUGIN_BUILD_NUMBER);

        // General plugin scripts
        wp_enqueue_script('issues-map',
                plugins_url('/js/issues-map.js', __FILE__),
                array( 'jquery', 'jquery-ui-dialog' ), PLUGIN_BUILD_NUMBER);

        // Text strings for use in javascript
        $translation_array = array(
            'ajax_url' => admin_url('admin-ajax.php'),
        );
        $post_id = get_the_ID();
        if (is_singular(ISSUE_POST_TYPE)) {
            $translation_array['issue_id'] = $post_id;
            $translation_array['report_id'] = 0;
            $translation_array['select_an_image_str'] = esc_html__('Select an image', 'issues-map');
            $translation_array['select_an_image_full_str'] = esc_html__('Please select an image first.', 'issues-map');
            $translation_array['already_featured_image_str'] = esc_html__('Already featured image', 'issues-map');
            $translation_array['already_featured_image_full_str'] = esc_html__('The selected image is already the featured image.', 'issues-map');
            $translation_array['confirm_delete_image_str'] = esc_html__('Delete the selected image?', 'issues-map');
            $translation_array['confirm_delete_issue_str'] = esc_html__('Delete this issue including any associated images and reports?', 'issues-map');
            $translation_array['issue_deleted_str'] = esc_html__('Issue deleted', 'issues-map');
            $translation_array['issue_deleted_full_str'] = esc_html__('Issue successfully deleted. Click OK to go to the issues list.', 'issues-map');
            
        } else if (is_singular(REPORT_POST_TYPE) || is_singular(REPORT_TEMPLATE_POST_TYPE)) {
            $translation_array['issue_id'] = get_post_meta($post_id, META_ISSUE_ID, true);
            $translation_array['report_id'] = $post_id;
            $translation_array['confirm_send_report_str'] = esc_html__('Email the report to the recipient?', 'issues-map');
            $translation_array['send_str'] = esc_html__('Send', 'issues-map');
        }
        $translation_array['confirm_delete_report_str'] = esc_html__('Delete this report?', 'issues-map');
        $translation_array['confirm_str'] = esc_html__('Please confirm', 'issues-map');
        $translation_array['delete_str'] = esc_html__('Delete', 'issues-map');
        $translation_array['ok_str'] = esc_html__('OK', 'issues-map');
        $translation_array['cancel_str'] = esc_html__('Cancel', 'issues-map');

        wp_localize_script('issues-map', 'issues_map', $translation_array);
    }

}

// Instantiate plugin
if (!isset($_issues_map_plugin)) {
    $_issues_map_plugin = new IssuesMapPlugin();
}
