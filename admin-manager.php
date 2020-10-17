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

require_once 'constants.php';
require_once 'utils/utils.php';
require_once 'utils/wp-utils.php';

/**
 * Handles back end plugin functionality, creation of default content
 * and some uninstall functions.
 */
class AdminManager {

    private $_plugin;

    public function __construct($plugin = null) {
        $this->_plugin = $plugin;
    }

    /* Admin initialisation. */

    public function admin_init_action() {
        // Register settings
        $settings = $this->get_settings();
        foreach ($settings as $name => $options) {
            register_setting(SETTINGS_GROUP_NAME, $name, $options);
        }

        // On install, create default categories and statuses
        if (!get_option(OPTION_PLUGIN_INITIALIZED, false)) {
            $this->init_plugin_pages();
            $this->init_issue_categories();
            $this->init_issue_statuses();
            update_option(OPTION_PLUGIN_INITIALIZED, true);
        }
    }

    /*
     * Register back end scripts and styles.
     */

    public function admin_enqueue_scripts_action() {

        // Enqueue general plugin scripts
        $this->_plugin->enqueue_scripts_action();

        // WP 3.5 introduced a new color picker
        if (get_bloginfo('version') >= 3.5) {
            wp_enqueue_media();
            wp_enqueue_style(array('wp-color-picker'));
            wp_enqueue_script(array('wp-color-picker'));
        }

        // Admin-specific scripts
        wp_enqueue_script('issues-map-admin',
                plugins_url('/js/issues-map-admin.js', __FILE__),
                array('jquery'), PLUGIN_BUILD_NUMBER);
    }

    /*
     * Register backend menu items for the plugin.
     */

    public function admin_menu_action() {

        $settings_str = esc_html__('Settings', 'issues-map');
        $issue_categories_str = esc_html__('Issue categories', 'issues-map');
        $issue_statuses_str = esc_html__('Issue statuses', 'issues-map');
        $report_templates_str = esc_html__('Report templates', 'issues-map');
        $edit_issue_categories_url = 'edit-tags.php?taxonomy=' . ISSUE_CATEGORY_TAXONOMY . '&post_type=' . ISSUE_POST_TYPE;
        $edit_issue_statuses_url = 'edit-tags.php?taxonomy=' . ISSUE_STATUS_TAXONOMY . '&post_type=' . ISSUE_POST_TYPE;
        $edit_report_templates_url = 'edit.php?post_type=' . REPORT_TEMPLATE_POST_TYPE;
        remove_menu_page($edit_report_templates_url);

        add_menu_page(
                esc_html__('Issues Map Settings', 'issues-map'),
                esc_html__('Issues Map', 'issues-map'),
                'edit_pages',
                'issues_map',
                array($this, 'show_settings_page'),
                'dashicons-admin-site-alt',
                49
        );

        add_submenu_page('issues_map', $settings_str, $settings_str, 'edit_pages', 'issues_map', array($this, 'show_settings_page'));
        add_submenu_page('issues_map', $issue_categories_str, $issue_categories_str, 'edit_pages', $edit_issue_categories_url, '');
        add_submenu_page('issues_map', $issue_statuses_str, $issue_statuses_str, 'edit_pages', $edit_issue_statuses_url, '');
        add_submenu_page('issues_map', $report_templates_str, $report_templates_str, 'edit_pages', $edit_report_templates_url, '');

        add_meta_box('issues_map_report_template_box', 'Details', array($this, 'show_report_template_box'), REPORT_TEMPLATE_POST_TYPE, 'normal');
    }

    /*
     * Settings page for the plugin.
     */

    public function show_settings_page() {

        $override_existing_content = get_option(OPTION_OVERRIDE_EXISTING_CONTENT, DEFAULT_OVERRIDE_EXISTING_CONTENT);
        $list_page_id = get_option(OPTION_LIST_PAGE_ID, 0);
        $map_page_id = get_option(OPTION_MAP_PAGE_ID, 0);
        $add_issue_page_id = get_option(OPTION_ADD_ISSUE_PAGE_ID, 0);
        $open_in_new_tab = get_option(OPTION_OPEN_IN_NEW_TAB, DEFAULT_OPEN_IN_NEW_TAB);
        $get_api_key_caption = strip_tags(__('<a href="https://developers.google.com/maps/documentation/javascript/get-api-key" title="(opens in new tab)" target="_blank">Get an API key</a>. Please ensure that Maps Javascript API and Maps Static API are enabled for your API key.', 'issues-map'), array('<a>'));
        $moderator_email_caption = esc_html__('Used to send reports from. If not specified, report sending is disabled.', 'issues-map');
        $moderator_caption = esc_html__('Note: moderators have full permissions.', 'issues-map');
        if (DEMO_VERSION) {
            $moderator_caption .= ' ' . esc_html__('Report sending is disabled in demo mode.', 'issues-map');
        }
        ?>
        <style>
            ul.default-style{list-style:disc;}
        </style>
        <div class="wrap">
            <form method="post" action="options.php">
                <?php settings_fields('issues-map-settings-group'); ?>
                <?php do_settings_sections('issues-map-settings-group'); ?>
                <h2><?= esc_html__('Issues Map Settings', 'issues-map'); ?></h2>
                <table id="im-settings-table" class='im-settings-table'>
                    <tbody>

                        <tr>
                            <td class="im-settings-section"><span class='im-bold'><?= esc_html__('Page settings', 'issues-map') ?></span></td>
                        </tr>
                        <?php $this->output_page_selection_option(OPTION_LIST_PAGE_ID, __('List view', 'issues-map'), $list_page_id); ?>
                        <?php $this->output_page_selection_option(OPTION_MAP_PAGE_ID, __('Map view', 'issues-map'), $map_page_id); ?>
                        <?php $this->output_page_selection_option(OPTION_ADD_ISSUE_PAGE_ID, __('Add issue', 'issues-map'), $add_issue_page_id); ?>
                        <?php $this->output_checkbox_option(OPTION_OVERRIDE_EXISTING_CONTENT, __('Automatically generate content', 'issues-map'), $override_existing_content); ?>
                        <?php $this->output_checkbox_option(OPTION_OPEN_IN_NEW_TAB, __('Open issues in a new tab', 'issues-map'), $open_in_new_tab); ?>
                        <?php $this->output_checkbox_option(OPTION_SHOW_HEADER_LINKS, __('Show top links menu', 'issues-map'), get_option(OPTION_SHOW_HEADER_LINKS, DEFAULT_SHOW_HEADER_LINKS)); ?>
                        <?php $this->output_checkbox_option(OPTION_SHOW_FOOTER_LINKS, __('Show bottom links menu', 'issues-map'), get_option(OPTION_SHOW_FOOTER_LINKS, DEFAULT_SHOW_FOOTER_LINKS)); ?>

                        <tr>
                            <td class="im-settings-section"><span class='im-bold'><?= esc_html__('Map settings', 'issues-map') ?></span></td>
                        </tr>
                        <?php $this->output_text_option(OPTION_GMAPS_API_KEY, __('Google Maps API Key', 'issues-map'), get_option(OPTION_GMAPS_API_KEY), $get_api_key_caption); ?>
                        <?php $this->output_number_option(OPTION_CENTRE_LAT, __('Centre latitude', 'issues-map'), get_option(OPTION_CENTRE_LAT, DEFAULT_CENTRE_LAT), -90, 90, 'any'); ?>
                        <?php $this->output_number_option(OPTION_CENTRE_LNG, __('Centre longitude', 'issues-map'), get_option(OPTION_CENTRE_LNG, DEFAULT_CENTRE_LNG), -180, 180, 'any'); ?>
                        <?php $this->output_number_option(OPTION_ZOOM_MAP_VIEW, __('Zoom level (main map)', 'issues-map'), get_option(OPTION_ZOOM_MAP_VIEW, DEFAULT_ZOOM_MAP_VIEW), 0, 22, 1); ?>
                        <?php $this->output_number_option(OPTION_ZOOM_ISSUE_VIEW, __('Zoom level (issue location)', 'issues-map'), get_option(OPTION_ZOOM_ISSUE_VIEW, DEFAULT_ZOOM_ISSUE_VIEW), 0, 22, 1); ?>

                        <tr>
                            <td class="im-settings-section"><span class='im-bold'><?= esc_html__('Reports', 'issues-map') ?></span></td>
                        </tr>
                        <?php $this->output_checkbox_option(OPTION_INCLUDE_IMAGES_IN_REPORTS, __('Include images in reports', 'issues-map'), get_option(OPTION_INCLUDE_IMAGES_IN_REPORTS, DEFAULT_INCLUDE_IMAGES_IN_REPORTS)); ?>

                        <tr>
                            <td class="im-settings-section"><span class='im-bold'><?= esc_html__('Permissions (logged in users)', 'issues-map') ?></span></td>
                        </tr>
                        <?php $this->output_checkbox_option(OPTION_CAN_LOGGED_IN_ADD_ISSUE, __('Add issue', 'issues-map'), get_option(OPTION_CAN_LOGGED_IN_ADD_ISSUE, DEFAULT_CAN_LOGGED_IN_ADD_ISSUE)); ?>
                        <?php $this->output_checkbox_option(OPTION_CAN_LOGGED_IN_UPLOAD_IMAGES, __('Upload images', 'issues-map'), get_option(OPTION_CAN_LOGGED_IN_UPLOAD_IMAGES, DEFAULT_CAN_LOGGED_IN_UPLOAD_IMAGES)); ?>
                        <?php $this->output_checkbox_option(OPTION_CAN_LOGGED_IN_COMMENT, __('Comment on issues', 'issues-map'), get_option(OPTION_CAN_LOGGED_IN_COMMENT, DEFAULT_CAN_LOGGED_IN_COMMENT)); ?>
                        <?php $this->output_checkbox_option(OPTION_CAN_LOGGED_IN_SEND_REPORTS, __('Send issue reports to moderators', 'issues-map'), get_option(OPTION_CAN_LOGGED_IN_SEND_REPORTS, DEFAULT_CAN_LOGGED_IN_SEND_REPORTS)); ?>
                        <?php $this->output_checkbox_option(OPTION_CAN_LOGGED_IN_SEND_REPORTS, __('Send issue reports to anyone', 'issues-map'), get_option(OPTION_CAN_LOGGED_IN_SEND_REPORTS_TO_ANYONE, DEFAULT_CAN_LOGGED_IN_SEND_REPORTS_TO_ANYONE)); ?>

                        <tr>
                            <td class="im-settings-section"><span class='im-bold'><?= esc_html__('Permissions (anonymous users)', 'issues-map') ?></span></td>
                        </tr>
                        <?php $this->output_checkbox_option(OPTION_CAN_ANON_ADD_ISSUE, __('Add issue', 'issues-map'), get_option(OPTION_CAN_ANON_ADD_ISSUE, DEFAULT_CAN_ANON_ADD_ISSUE)); ?>
                        <?php $this->output_checkbox_option(OPTION_CAN_ANON_UPLOAD_IMAGES, __('Upload images', 'issues-map'), get_option(OPTION_CAN_ANON_UPLOAD_IMAGES, DEFAULT_CAN_ANON_UPLOAD_IMAGES)); ?>
                        <?php $this->output_checkbox_option(OPTION_CAN_ANON_COMMENT, __('Comment on issues', 'issues-map'), get_option(OPTION_CAN_ANON_COMMENT, DEFAULT_CAN_ANON_COMMENT)); ?>
                        <?php $this->output_checkbox_option(OPTION_CAN_ANON_SEND_REPORTS, __('Send issue reports to moderators', 'issues-map'), get_option(OPTION_CAN_ANON_SEND_REPORTS, DEFAULT_CAN_ANON_SEND_REPORTS)); ?>
                        <?php $this->output_checkbox_option(OPTION_CAN_ANON_SEND_REPORTS_TO_ANYONE, __('Send issue reports to anyone', 'issues-map'), get_option(OPTION_CAN_ANON_SEND_REPORTS_TO_ANYONE, DEFAULT_CAN_ANON_SEND_REPORTS_TO_ANYONE)); ?>
                        <tr>
                            <td colspan="2"><span class="im-italic"><?= $moderator_caption; ?></span></td>
                        </tr>

                        <tr>
                            <td class="im-settings-section"><span class='im-bold'><?= esc_html__('Moderation', 'issues-map') ?></span></td>
                        </tr>                        
                        <?php $this->output_text_option(OPTION_MODERATOR_EMAIL, __('Moderator email', 'issues-map'), get_option(OPTION_MODERATOR_EMAIL, get_bloginfo('admin_email')), $moderator_email_caption); ?>
                        <?php $this->output_moderators_list(OPTION_MODERATORS_LIST, __('Moderators', 'issues-map'), get_option(OPTION_MODERATORS_LIST, '')); ?>

                    </tbody>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /*
     * Output a row containing a page selection option. 
     */

    private function output_page_selection_option($name, $label, $selected_id) {
        ?>
        <tr>
            <td><label for="<?= esc_attr($name); ?>"><?= esc_html($label); ?></label></td>
            <td><?php wp_dropdown_pages(array('selected' => $selected_id, 'name' => esc_attr($name), 'value_field' => 'ID')); ?></td>
        </tr>    
        <?php
    }

    /*
     * Output a row containing a checkbox option. 
     */

    private function output_checkbox_option($name, $label, $val) {
        ?>
        <tr>
            <td><label for="<?= esc_attr($name); ?>"><?= esc_html($label); ?></label></td>
            <td><input type="checkbox" name="<?= esc_attr($name); ?>" <?= $val ? 'checked' : ''; ?>/></td>
        </tr>    
        <?php
    }

    /*
     * Output a row containing a number field.
     */

    private function output_number_option($name, $label, $val, $min = null, $max = null, $step = null) {
        $min_attr = $min !== null ? 'min="' . esc_attr($min) . '"' : '';
        $max_attr = $max !== null ? 'max="' . esc_attr($max) . '"' : '';
        $step_attr = $step !== null ? 'step="' . esc_attr($step) . '"' : '';
        ?>
        <tr>
            <td><label for="<?= esc_attr($name); ?>"><?= esc_html($label); ?></label></td>
            <td><input name='<?= esc_attr($name); ?>' type='number' <?= $min_attr; ?> <?= $max_attr; ?>  <?= $step_attr; ?> value='<?= esc_attr($val); ?>' /></td>
        </tr>    
        <?php
    }

    /*
     * Output a row containing a text field. 
     */

    private function output_text_option($name, $label, $val, $caption = null) {
        ?>
        <tr>
            <td><label for="<?= esc_attr($name); ?>"><?= esc_html($label); ?></label></td>
            <td>
                <input type="text" name="<?= esc_attr($name); ?>" value="<?= esc_attr($val); ?>"/>
                <?= $caption ? '<div class="im-italic">' . strip_tags($caption, '<a>') . '</div>' : ''; ?>
            </td>
        </tr>    
        <?php
    }

    /*
     * Output the list of moderators. 
     */

    private function output_moderators_list($name, $label, $val) {
        $user_names = '';
        $user_ids = explode(',', $val);
        $caption = esc_html__('Enter one user\'s email address or login per line.', 'issues-map');
        foreach ($user_ids as $user_id) {
            $user_data = get_userdata(intval($user_id));
            if ($user_data) {
                $user_names .= $user_data->data->user_login . ' (' . Utils::mask_email_address($user_data->data->user_email) . ")\n";
            }
        }
        ?>
        <tr>
            <td><label for="<?= esc_attr($name); ?>"><?= esc_html($label); ?></label></td>
            <td>
                <textarea name="<?= esc_attr($name); ?>" rows="4" cols="40"><?= esc_textarea($user_names); ?></textarea>
                <div class="im-italic"><?= $caption; ?></div>
            </td>
        </tr>    
        <?php
    }

    /*
     * Backend page for editing report templates.
     */

    public function show_report_template_box($post) {
        $status = $post->post_status;
        $template_id = $status === 'new' || $status === 'auto-draft' ? 0 : $post->ID;
        echo $this->_plugin->get_report_content_mgr()->get_edit_report_template_form_content($template_id);
    }

    /*
     * Handle save post in back end.
     */

    public function save_post_action($id = false) {

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        if (get_post_type($id) == REPORT_TEMPLATE_POST_TYPE) {
            if (!isset($_POST['im-edit-report-nonce']) ||
                    !wp_verify_nonce($_POST['im-edit-report-nonce'], 'im-edit-report')) {
                return;
            }

            if (!current_user_can('edit_post', $id)) {
                return;
            }

            if (wp_is_post_revision($id)) {
                return;
            }

            // unhook save_post so it doesn't loop infinitely
            remove_action('save_post', array($this->_plugin, 'save_post_action'));

            $this->_plugin->get_async_mgr()->edit_report($id, 0, false);

            // rehook save_post
            add_action('save_post', array($this->_plugin, 'save_post_action'));
        }
    }

    /*
     * Handle deletion of posts, e.g. issues and issue reports.
     */

    public function delete_post_action($post_id) {
        $post_type = get_post_type($post_id);
        if ($post_type === ISSUE_POST_TYPE) {
            // Delete images for issue
            $this->_plugin->get_issue_data_mgr()->delete_images_for_issue($post_id);
            $this->_plugin->get_report_data_mgr()->delete_reports_for_issue($post_id);
        } else if ($post_type === REPORT_POST_TYPE) {
            $this->_plugin->get_report_data_mgr()->delete_pdf($post_id);
        } else if ($post_id == get_option(OPTION_LIST_PAGE_ID, 0)) {
            update_option(OPTION_LIST_PAGE_ID, 0);
        } else if ($post_id == get_option(OPTION_MAP_PAGE_ID, 0)) {
            update_option(OPTION_MAP_PAGE_ID, 0);
        } else if ($post_id == get_option(OPTION_ADD_ISSUE_PAGE_ID, 0)) {
            update_option(OPTION_ADD_ISSUE_PAGE_ID, 0);
        }
    }

    /*
     * Delete all custom posts created by the plugin.
     */

    public function delete_custom_posts() {
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
    }

    /*
     * Delete taxonomy terms created by the plugin.
     */

    public function delete_taxonomy_terms() {
        WPUtils::delete_custom_terms(ISSUE_CATEGORY_TAXONOMY);
        WPUtils::delete_custom_terms(ISSUE_STATUS_TAXONOMY);
    }

    /*
     * Create the main plugin pages if necessary.
     */

    private function init_plugin_pages() {
        $override_existing_content = get_option(OPTION_OVERRIDE_EXISTING_CONTENT, DEFAULT_OVERRIDE_EXISTING_CONTENT);
        if ($override_existing_content) {
            $list_page_id = get_option(OPTION_LIST_PAGE_ID, 0);
            $map_page_id = get_option(OPTION_MAP_PAGE_ID, 0);
            $add_issue_page_id = get_option(OPTION_ADD_ISSUE_PAGE_ID, 0);

            if (!$list_page_id) {
                $list_page_id = WPUtils::create_page(LIST_PAGE_SLUG, sanitize_text_field(__('Issues List', 'issues-map')));
                update_option(OPTION_LIST_PAGE_ID, $list_page_id);
            }
            if (!$map_page_id) {
                $map_page_id = WPUtils::create_page(MAP_PAGE_SLUG, sanitize_text_field(__('Issues Map', 'issues-map')));
                update_option(OPTION_MAP_PAGE_ID, $map_page_id);
            }
            if (!$add_issue_page_id) {
                $add_issue_page_id = WPUtils::create_page(ADD_ISSUE_PAGE_SLUG, sanitize_text_field(__('Submit Issue', 'issues-map')));
                update_option(OPTION_ADD_ISSUE_PAGE_ID, $add_issue_page_id);
            }
        }
    }

    /*
     * Delete pages created by the plugin. 
     */

    public function delete_plugin_pages() {
        WPUtils::delete_page_if_empty(LIST_PAGE_SLUG);
        WPUtils::delete_page_if_empty(MAP_PAGE_SLUG);
        WPUtils::delete_page_if_empty(ADD_ISSUE_PAGE_SLUG);
    }

    /*
     * Create default issue categories. 
     */

    private function init_issue_categories() {
        $issue_categories = get_terms(array('taxonomy' => ISSUE_CATEGORY_TAXONOMY, 'hide_empty' => false));
        if (count($issue_categories) === 0) {
            WPUtils::insert_term(
                    sanitize_text_field(__('Uncategorized', 'issues-map')),
                    ISSUE_CATEGORY_TAXONOMY,
                    array('slug' => DEFAULT_ISSUE_CATEGORY_SLUG),
                    array(META_ICON_NAME => DEFAULT_CATEGORY_ICON_NAME,
                        META_COLOR => DEFAULT_COLOR)
            );
        }
    }

    /*
     * Create default issue statuses. 
     */

    private function init_issue_statuses() {
        $issue_statuses = get_terms(array('taxonomy' => ISSUE_STATUS_TAXONOMY, 'hide_empty' => false));
        if (count($issue_statuses) === 0) {
            WPUtils::insert_term(
                    sanitize_text_field(__('Unreported', 'issues-map')),
                    ISSUE_STATUS_TAXONOMY,
                    array('slug' => ISSUE_STATUS_UNREPORTED_SLUG),
                    array(META_COLOR => DEFAULT_COLOR)
            );
            WPUtils::insert_term(
                    sanitize_text_field(__('Report created', 'issues-map')),
                    ISSUE_STATUS_TAXONOMY,
                    array('slug' => ISSUE_STATUS_REPORT_CREATED_SLUG),
                    array(META_COLOR => DEFAULT_REPORT_CREATED_COLOR)
            );
            WPUtils::insert_term(
                    sanitize_text_field(__('Report sent', 'issues-map')),
                    ISSUE_STATUS_TAXONOMY,
                    array('slug' => ISSUE_STATUS_REPORT_SENT_SLUG),
                    array(META_COLOR => DEFAULT_REPORT_SENT_COLOR)
            );
        }
    }

    /* Delete the plugin's uploads subdirectory. */

    public function delete_uploads_dir() {
        if (IMAGES_FOLDER_NAME) {   // Just to be absolutely sure only the plugin's subdirectory will be deleted
            $upload_dir = wp_get_upload_dir();
            $plugin_uploads_dir = trailingslashit(path_join($upload_dir['basedir'], IMAGES_FOLDER_NAME));
            foreach (glob($plugin_uploads_dir . '*') as $file) {
                unlink($file);
            }
            rmdir($plugin_uploads_dir);
        }
    }

    /* Deregister plugin settings. */

    public function unregister_settings() {
        $settings = $this->get_settings();
        foreach ($settings as $name => $options) {
            unregister_setting(SETTINGS_GROUP_NAME, $name);
        }
    }

    /* Delete plugin settings. */

    public function delete_settings() {
        $settings = $this->get_settings();
        foreach ($settings as $name => $options) {
            delete_option($name);
        }
    }

    /* Get plugin settings. */

    private function get_settings() {

        $settings = array(
            OPTION_PLUGIN_INITIALIZED => array('type' => 'boolean'),
            OPTION_LIST_PAGE_ID => array('type' => 'integer'),
            OPTION_MAP_PAGE_ID => array('type' => 'integer'),
            OPTION_ADD_ISSUE_PAGE_ID => array('type' => 'integer'),
            OPTION_OVERRIDE_EXISTING_CONTENT => array('type' => 'boolean'),
            OPTION_OPEN_IN_NEW_TAB => array('type' => 'boolean'),
            OPTION_SHOW_HEADER_LINKS => array('type' => 'boolean'),
            OPTION_SHOW_FOOTER_LINKS => array('type' => 'boolean'),
            OPTION_GMAPS_API_KEY => array('type' => 'string', 'sanitize_callback' => array($this, 'filter_gmaps_api_key')),
            OPTION_CENTRE_LAT => array('type' => 'number', 'sanitize_callback' => array($this, 'filter_latitude')),
            OPTION_CENTRE_LNG => array('type' => 'number', 'sanitize_callback' => array($this, 'filter_longitude')),
            OPTION_ZOOM_MAP_VIEW => array('type' => 'integer', 'sanitize_callback' => array($this, 'filter_zoom_level')),
            OPTION_ZOOM_ISSUE_VIEW => array('type' => 'integer', 'sanitize_callback' => array($this, 'filter_zoom_level')),
            OPTION_INCLUDE_IMAGES_IN_REPORTS => array('type' => 'boolean'),
            OPTION_CAN_LOGGED_IN_ADD_ISSUE => array('type' => 'boolean'),
            OPTION_CAN_LOGGED_IN_UPLOAD_IMAGES => array('type' => 'boolean'),
            OPTION_CAN_LOGGED_IN_COMMENT => array('type' => 'boolean'),
            OPTION_CAN_LOGGED_IN_SEND_REPORTS => array('type' => 'boolean'),
            OPTION_CAN_LOGGED_IN_SEND_REPORTS_TO_ANYONE => array('type' => 'boolean'),
            OPTION_CAN_ANON_ADD_ISSUE => array('type' => 'boolean'),
            OPTION_CAN_ANON_UPLOAD_IMAGES => array('type' => 'boolean'),
            OPTION_CAN_ANON_COMMENT => array('type' => 'boolean'),
            OPTION_CAN_ANON_SEND_REPORTS => array('type' => 'boolean'),
            OPTION_CAN_ANON_SEND_REPORTS_TO_ANYONE => array('type' => 'boolean'),
            OPTION_MODERATOR_EMAIL => array('type' => 'string', 'sanitize_callback' => array($this, 'filter_email')),
            OPTION_MODERATORS_LIST => array('type' => 'string', 'sanitize_callback' => array($this, 'parse_moderators_list')),
        );
        return $settings;
    }

    /* Sanitize email address. */

    public function filter_email($input) {
        $val = filter_var($input, FILTER_VALIDATE_EMAIL);
        if ($val === false) {
            $val = '';
        }
        return $val;
    }

    /* Sanitize Google Maps API key. */

    public function filter_gmaps_api_key($input) {
        $input = trim($input);
        if (preg_match('/^[A-za-z0-9]+$/', $input)) {
            return $input;
        } else {
            return '';
        }
    }

    /* Sanitize latitude value. */

    public function filter_latitude($input) {
        $val = filter_var($input, FILTER_VALIDATE_FLOAT, array('min_range' => -90, 'max_range' => 90));
        if ($val === false) {
            $val = DEFAULT_CENTRE_LAT;
        }
        return $val;
    }

    /* Sanitize longitude value. */

    public function filter_longitude($input) {
        $val = filter_var($input, FILTER_VALIDATE_FLOAT, array('min_range' => -180, 'max_range' => 180));
        if ($val === false) {
            $val = DEFAULT_CENTRE_LNG;
        }
        return $val;
    }

    /* Sanitize Google maps zoom level. */

    public function filter_zoom_level($input) {
        $val = filter_var($input, FILTER_VALIDATE_INT, array('min_range' => 0, 'max_range' => 22));
        if ($val === false) {
            $val = DEFAULT_ZOOM_MAP_VIEW;
        }
        return $val;
    }

    /*
     * Sanitize and parse list of moderator logins / emails.
     * Returns a comma-delimited list of user IDs.
     */

    public function parse_moderators_list($input) {
        $user_ids = '';
        $lines = explode("\n", sanitize_textarea_field($input));
        foreach ($lines as $line) {
            $line = preg_replace("/ \\(.*/", '', $line);
            $user = get_user_by('email', $line);
            if (!$user) {
                $user = get_user_by('login', $line);
            }
            if ($user) {
                $user_ids .= $user->ID . ',';
            }
        }

        return trim($user_ids, ',');
    }

}
