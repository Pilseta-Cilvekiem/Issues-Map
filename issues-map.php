<?php

/*
  Plugin Name: Issues Map
  Plugin URI: https://github.com/Pilseta-Cilvekiem/issues-map
  Description: Submit issues, view them in a list or on a map, save them as PDFs, send them by email.
  Version: 1.2
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

/*
 * Implements the issues-map plugin.
 */

class IssuesMapPlugin {

    private $_upload_dir;
    private $_upload_url;
    private $_admin_mgr;
    private $_async_mgr;
    private $_content_mgr;
    private $_auth_mgr;
    private $_issue_data_mgr;
    private $_issue_content_mgr;
    private $_report_data_mgr;
    private $_report_content_mgr;
    private $_issue_category_mgr;
    private $_issue_status_mgr;

    public function __construct() {

        // Add initialisation / deactivation actions
        add_action('init', array($this, 'init_action'));
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain_action'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts_action'));

        // Add admin functionality
        add_action('admin_init', array($this, 'admin_init_action'));
        add_action('admin_menu', array($this, 'admin_menu_action'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts_action'));
        add_action('save_post', array($this, 'save_post_action'));
        add_action('delete_post', array($this, 'delete_post_action'));

        // Issue category taxonomy
        add_action(ISSUE_CATEGORY_TAXONOMY . '_add_form_fields', array($this, 'add_issue_category_form_fields'));
        add_action(ISSUE_CATEGORY_TAXONOMY . '_edit_form_fields', array($this, 'edit_issue_category_form_fields'));
        add_action('edited_' . ISSUE_CATEGORY_TAXONOMY, array($this, 'save_issue_category_meta'));
        add_action('create_' . ISSUE_CATEGORY_TAXONOMY, array($this, 'save_issue_category_meta'));
        add_filter('manage_edit-' . ISSUE_CATEGORY_TAXONOMY . '_columns', array($this, 'manage_edit_issue_category_columns'));
        add_action('manage_' . ISSUE_CATEGORY_TAXONOMY . '_custom_column', array($this, 'manage_issue_category_custom_column'), 10, 3);

        // Issue status taxonomy
        add_action(ISSUE_STATUS_TAXONOMY . '_add_form_fields', array($this, 'add_issue_status_form_fields'));
        add_action(ISSUE_STATUS_TAXONOMY . '_edit_form_fields', array($this, 'edit_issue_status_form_fields'));
        add_action('edited_' . ISSUE_STATUS_TAXONOMY, array($this, 'save_issue_status_meta'));
        add_action('create_' . ISSUE_STATUS_TAXONOMY, array($this, 'save_issue_status_meta'));
        add_filter('manage_edit-' . ISSUE_STATUS_TAXONOMY . '_columns', array($this, 'manage_edit_issue_status_columns'));
        add_action('manage_' . ISSUE_STATUS_TAXONOMY . '_custom_column', array($this, 'manage_issue_status_custom_column'), 10, 3);

        // Content generation and filtering
        add_shortcode(ISSUES_MAP_SHORTCODE, array($this, 'issues_map_shortcode_action'));
        add_filter('query_vars', array($this, 'query_vars_filter'));
        add_filter('comments_open', array($this, 'comments_open_filter'), 10, 2);
        add_filter('edit_post_link', array($this, 'edit_post_link_filter'), 10, 3);
        add_filter('the_title', array($this, 'the_title_filter'));
        add_filter('the_content', array($this, 'the_content_filter'));

        // Add AJAX callbacks
        add_action('wp_ajax_edit_details_async', array($this, 'edit_details_async'));
        add_action('wp_ajax_nopriv_edit_details_async', array($this, 'edit_details_async'));
        add_action('wp_ajax_add_images_async', array($this, 'add_images_async'));
        add_action('wp_ajax_nopriv_add_images_async', array($this, 'add_images_async'));
        add_action('wp_ajax_cancel_add_images_async', array($this, 'cancel_add_images_async'));
        add_action('wp_ajax_nopriv_cancel_add_images_async', array($this, 'cancel_add_images_async'));
        add_action('wp_ajax_edit_location_async', array($this, 'edit_location_async'));
        add_action('wp_ajax_nopriv_edit_location_async', array($this, 'edit_location_async'));
        add_action('wp_ajax_edit_report_async', array($this, 'edit_report_async'));
        add_action('wp_ajax_nopriv_edit_report_async', array($this, 'edit_report_async'));
        add_action('wp_ajax_send_report_async', array($this, 'send_report_async'));
        add_action('wp_ajax_nopriv_send_report_async', array($this, 'send_report_async'));
        add_action('wp_ajax_download_report_async', array($this, 'download_report_async'));
        add_action('wp_ajax_nopriv_download_report_async', array($this, 'download_report_async'));
        add_action('wp_ajax_delete_issue_image_async', array($this, 'delete_issue_image_async'));
        add_action('wp_ajax_nopriv_delete_issue_image_async', array($this, 'delete_issue_image_async'));
        add_action('wp_ajax_set_featured_image_async', array($this, 'set_featured_image_async'));
        add_action('wp_ajax_nopriv_set_featured_image_async', array($this, 'set_featured_image_async'));
        add_action('wp_ajax_dnd_codedropz_upload', array($this, 'file_uploaded_async'));
        add_action('wp_ajax_nopriv_dnd_codedropz_upload', array($this, 'file_uploaded_async'));
        add_action('wp_ajax_dnd_codedropz_upload_delete', array($this, 'upload_deleted_async'));
        add_action('wp_ajax_nopriv_dnd_codedropz_upload_delete', array($this, 'upload_deleted_async'));
        add_action('wp_ajax_delete_issue_async', array($this, 'delete_issue_async'));
        add_action('wp_ajax_nopriv_delete_issue_async', array($this, 'delete_issue_async'));
        add_action('wp_ajax_delete_report_async', array($this, 'delete_report_async'));
        add_action('wp_ajax_nopriv_delete_report_async', array($this, 'delete_report_async'));
        add_action('wp_ajax_get_issues_list_async', array($this, 'get_issues_list_async'));
        add_action('wp_ajax_nopriv_get_issues_list_async', array($this, 'get_issues_list_async'));
        add_action('wp_ajax_get_map_items_async', array($this, 'get_map_items_async'));
        add_action('wp_ajax_nopriv_get_map_items_async', array($this, 'get_map_items_async'));
        add_action('wp_ajax_get_info_window_content_async', array($this, 'get_info_window_content_async'));
        add_action('wp_ajax_nopriv_get_info_window_content_async', array($this, 'get_info_window_content_async'));
    }

    /*
     * Accessors.
     */

    public function get_upload_dir() {
        return $this->_upload_dir;
    }

    public function get_upload_url() {
        return $this->_upload_url;
    }

    public function get_content_mgr() {
        if (!$this->_content_mgr) {
            require_once 'content-manager.php';
            $this->_content_mgr = new ContentManager($this);
        }
        return $this->_content_mgr;
    }

    public function get_issue_content_mgr() {
        if (!$this->_issue_content_mgr) {
            require_once 'issue-content.php';
            $this->_issue_content_mgr = new IssueContentManager($this);
        }
        return $this->_issue_content_mgr;
    }

    public function get_issue_data_mgr() {
        if (!$this->_issue_data_mgr) {
            require_once 'issue-data.php';
            $this->_issue_data_mgr = new IssueDataManager($this);
        }        
        return $this->_issue_data_mgr;
    }

    public function get_report_content_mgr() {
        if (!$this->_report_content_mgr) {
            require_once 'report-content.php';
            $this->_report_content_mgr = new ReportContentManager($this);
        }
        return $this->_report_content_mgr;
    }

    public function get_report_data_mgr() {
        if (!$this->_report_data_mgr) {
            require_once 'report-data.php';
            $this->_report_data_mgr = new ReportDataManager($this);
        }
        return $this->_report_data_mgr;
    }

    public function get_auth_mgr() {
        if (!$this->_auth_mgr) {
            require_once 'auth-manager.php';
            $this->_auth_mgr = new AuthManager($this);
        }
        return $this->_auth_mgr;
    }

    public function get_async_mgr() {
        if (!$this->_async_mgr) {
            require_once 'async-manager.php';
            $this->_async_mgr = new AsyncManager($this);
        }
        return $this->_async_mgr;
    }

    private function get_issue_category_mgr() {
        if (!$this->_issue_category_mgr) {
            require_once 'issue-category.php';
            $this->_issue_category_mgr = new IssueCategoryManager($this);
        }
        return $this->_issue_category_mgr;
    }

    private function get_issue_status_mgr() {
        if (!$this->_issue_status_mgr) {
            require_once 'issue-status.php';
            $this->_issue_status_mgr = new IssueStatusManager($this);
        }
        return $this->_issue_status_mgr;
    }

    private function get_admin_mgr() {
        if (!$this->_admin_mgr) {
            require_once 'admin-manager.php';
            $this->_admin_mgr = new AdminManager($this);
        }
        return $this->_admin_mgr;
    }

    /*
     * Plugin initialisation.
     */

    public function init_action() {
        
        // Create plugin upload directory
        $upload_dir = wp_upload_dir();
        $this->_upload_url = $upload_dir['baseurl'] . '/' . IMAGES_FOLDER_NAME . '/';
        $this->_upload_dir = trailingslashit(path_join($upload_dir['basedir'], IMAGES_FOLDER_NAME));
        if (!is_dir($this->_upload_dir )) {
            wp_mkdir_p($this->_upload_dir );
        }
        
        // Register custom post types and taxonomies
        require_once 'type-manager.php';
        $type_manager = new TypeManager();
        $type_manager->register_issue_category_taxonomy();
        $type_manager->register_issue_status_taxonomy();
        $type_manager->register_issue_cpt();
        $type_manager->register_report_cpt();
        $type_manager->register_report_template_cpt();
    }

    /*
     * Load the text domain to allow translation.
     */

    public function load_plugin_textdomain_action() {
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
                array('jquery', 'jquery-ui-dialog'), PLUGIN_BUILD_NUMBER);

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

    /*
     * Admin hooks.
     */

    public function admin_init_action() {
        $this->get_admin_mgr()->admin_init_action();
    }

    public function admin_menu_action() {
        $this->get_admin_mgr()->admin_menu_action();
    }

    public function admin_enqueue_scripts_action() {
        $this->get_admin_mgr()->admin_enqueue_scripts_action();
    }

    public function save_post_action($id = false) {
        $this->get_admin_mgr()->save_post_action($id);
    }

    public function delete_post_action($post_id) {
        $this->get_admin_mgr()->delete_post_action($post_id);
    }

    /*
     * Issue category hooks and filters.
     */

    public function add_issue_category_form_fields() {
        $this->get_issue_category_mgr()->add_issue_category_form_fields();
    }

    public function edit_issue_category_form_fields($term) {
        $this->get_issue_category_mgr()->edit_issue_category_form_fields($term);
    }

    public function save_issue_category_meta($term_id) {
        $this->get_issue_category_mgr()->save_issue_category_meta($term_id);
    }

    public function manage_edit_issue_category_columns($columns) {
        return $this->get_issue_category_mgr()->manage_edit_issue_category_columns($columns);
    }

    public function manage_issue_category_custom_column($out, $column_name, $term_id) {
        return $this->get_issue_category_mgr()->manage_issue_category_custom_column($out, $column_name, $term_id);
    }

    /*
     * Issue status hooks and filters.
     */

    public function add_issue_status_form_fields() {
        $this->get_issue_status_mgr()->add_issue_status_form_fields();
    }

    public function edit_issue_status_form_fields($term) {
        $this->get_issue_status_mgr()->edit_issue_status_form_fields($term);
    }

    public function save_issue_status_meta($term_id) {
        $this->get_issue_status_mgr()->save_issue_status_meta($term_id);
    }

    public function manage_edit_issue_status_columns($columns) {
        return $this->get_issue_status_mgr()->manage_edit_issue_status_columns($columns);
    }

    public function manage_issue_status_custom_column($out, $column_name, $term_id) {
        return $this->get_issue_status_mgr()->manage_issue_status_custom_column($out, $column_name, $term_id);
    }

    /*
     * Content generation and filtering.
     */

    public function issues_map_shortcode_action($atts) {
        return $this->get_content_mgr()->issues_map_shortcode_action($atts);
    }

    public function query_vars_filter($qvars) {
        return $this->get_content_mgr()->query_vars_filter($qvars);
    }

    public function comments_open_filter($open, $post_id) {
        return $this->get_content_mgr()->comments_open_filter($open, $post_id);
    }

    public function edit_post_link_filter($link, $id, $text) {
        return $this->get_content_mgr()->edit_post_link_filter($link, $id, $text);
    }

    public function the_title_filter($title) {
        return $this->get_content_mgr()->the_title_filter($title);
    }

    public function the_content_filter($content) {
        return $this->get_content_mgr()->the_content_filter($content);
    }

    /*
     * Asynchronous callbacks.
     */
    
    public function edit_details_async() {
        $this->get_async_mgr()->edit_details_async();
    }
    
    public function add_images_async() {
        $this->get_async_mgr()->add_images_async();
    }
    
    public function cancel_add_images_async() {
        $this->get_async_mgr()->cancel_add_images_async();
    }

    public function edit_location_async() {
        $this->get_async_mgr()->edit_location_async();
    }

    public function edit_report_async() {
        $this->get_async_mgr()->edit_report_async();
    }

    public function send_report_async() {
        $this->get_async_mgr()->send_report_async();
    }

    public function download_report_async() {
        $this->get_async_mgr()->download_report_async();
    }

    public function delete_issue_image_async() {
        $this->get_async_mgr()->delete_issue_image_async();
    }

    public function set_featured_image_async() {
        $this->get_async_mgr()->set_featured_image_async();
    }

    public function file_uploaded_async() {
        $this->get_async_mgr()->file_uploaded_async();
    }

    public function upload_deleted_async() {
        $this->get_async_mgr()->upload_deleted_async();
    }

    public function delete_issue_async() {
        $this->get_async_mgr()->delete_issue_async();
    }

    public function delete_report_async() {
        $this->get_async_mgr()->delete_report_async();
    }

    public function get_issues_list_async() {
        $this->get_async_mgr()->get_issues_list_async();
    }

    public function get_map_items_async() {
        $this->get_async_mgr()->get_map_items_async();
    }

    public function get_info_window_content_async() {
        $this->get_async_mgr()->get_info_window_content_async();
    }
    
}

// Instantiate plugin
if (!isset($_issues_map_plugin)) {
    $_issues_map_plugin = new IssuesMapPlugin();
}
