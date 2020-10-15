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
 * Generates the front end forms for viewing and editing issues.
 */
class IssueContentManager {

    private $_plugin;

    public function __construct($plugin) {
        $this->_plugin = $plugin;
    }

    /*
     * Add / edit issue form.
     */

    public function get_edit_issue_form($issue_id) {
        $issue_data_mgr = $this->_plugin->get_issue_data_mgr();
        $intro_str = esc_html__('Enter the details for the issue.', 'issues-map');
        $warning = esc_html__('Issues submitted are visible to everyone. However, your email address (if provided) will only be visible to our moderators.', 'issues-map');
        $issue_category_str = esc_html__('Issue category', 'issues-map');
        $issue_status_str = esc_html__('Issue status', 'issues-map');
        $issue_title_str = esc_html__('Issue title', 'issues-map');
        $description_str = esc_html__('Description', 'issues-map');
        $added_by_str = esc_html__('Your name (publicly visible)', 'issues-map');
        $email_address_str = esc_html__('Your email address (optional, only visible to moderators)', 'issues-map');
        $ok_str = $issue_id ? esc_attr__('OK', 'issues-map') : esc_attr__('Next', 'issues-map');
        $cancel_str = esc_attr__('Cancel', 'issues-map');
        $wpnonce = wp_create_nonce('im-edit-details');
        $max_issue_title_len = MAX_LEN_64;
        $max_description_len = MAX_DESCRIPTION_LEN;
        $max_username_len = MAX_LEN_32;
        $max_email_len = MAX_LEN_64;

        $issue_category_id = 0;
        $issue_status_id = 0;
        $issue_title = '';
        $description = '';
        $added_by = '';
        $email_address = '';
        if ($issue_id) {
            // Editing existing issue
            $issue = get_post($issue_id);
            $cat = $issue_data_mgr->get_issue_category($issue_id);
            if (!$cat) {
                $cat = get_term_by('slug', DEFAULT_ISSUE_CATEGORY_SLUG, ISSUE_CATEGORY_TAXONOMY);
            }
            $issue_category_id = $cat ? $cat->term_id : 0;
            $status = $issue_data_mgr->get_issue_status($issue_id);
            if (!$status) {
                $status = get_term_by('slug', ISSUE_STATUS_UNREPORTED_SLUG, ISSUE_STATUS_TAXONOMY);
            }
            $issue_status_id = $status ? $status->term_id : 0;
            $issue_title = esc_html($issue->post_title);
            $description = esc_textarea($issue->post_content);
            $added_by = esc_html(get_post_meta($issue_id, META_ADDED_BY, true));
            $email_address = esc_html(get_post_meta($issue_id, META_EMAIL_ADDRESS, true));
        } else {
            // Adding new issue
            $current_user_id = get_current_user_id();
            if ($current_user_id !== 0) {
                $user_data = get_userdata($current_user_id);
                $email_address = $user_data->data->user_email;
                $added_by = $user_data->data->display_name;
            }
        }
        $category_options = $issue_data_mgr->get_issue_category_options($issue_category_id, null);

        $content = <<<EOS
        <form action='' id='im-edit-details-form' class='im-form' method='post'><div class='im-form-inner'>
            <p class="im-intro">{$intro_str}</p>
            <p class="im-message"><span class="dashicons dashicons-info-outline"></span> {$warning}</p>
            <div class='im-form-section'>
                <label for='im-issue-category'>{$issue_category_str}</label>
                <select id='im-issue-category' name='im-issue-category' class='im-select'>
                    {$category_options}
                </select>
            </div>
EOS;
        if ($issue_id) {
            $status_options = $issue_data_mgr->get_issue_status_options($issue_status_id, null);
            $content .= "<div class='im-form-section'><label for='im-issue-status'>{$issue_status_str}</label><select id='im-issue-status' name='im-issue-status' class='im-select'>{$status_options}</select></div>";
        }
        $content .= <<<EOS
            <div class='im-form-section'>
                <label for='im-issue-title'>{$issue_title_str}</label>
                <input type='text' id='im-issue-title' name='im-issue-title' class='im-text im-issue-title' maxlength='{$max_issue_title_len}' value='{$issue_title}'></input>
            </div>
            <div class='im-form-section'>
                <label for='im-description'>{$description_str}</label>
                <textarea id='im-description' name='im-description' rows='6' cols='40' maxlength='{$max_description_len}'>{$description}</textarea>
            </div>
            <div class='im-form-section'>
                <label for='im-added-by'>{$added_by_str}</label>
                <input type='text' id='im-added-by' name='im-added-by' class='im-text' maxlength='{$max_username_len}' value='{$added_by}'></input>
            </div>
            <div class='im-form-section' id='im-email-address-section'>
                <label for='im-email-address'>{$email_address_str}</label>
                <input type='text' id='im-email-address' name='im-email-address' class='im-text' maxlength='{$max_email_len}' value='{$email_address}'></input>
            </div>
            <div class='im-form-section'>
                <p id='im-message' class='im-message'></p>
                <input id='im-ok-button' class='im-button' type='submit' value='{$ok_str}'>
                <input id='im-back-button' class='im-button im-back-button' type='button' value='{$cancel_str}'>
                <input type='hidden' id='im-edit-details-nonce' name='im-edit-details-nonce' value='{$wpnonce}'></input>
            </div>
        </div></form>
EOS;

        return $content;
    }

    /*
     * Add images form.
     */

    public function get_add_images_form($issue_id, $view) {
        $intro_str = esc_html__('Add any images to illustrate the issue. You can also add images later.', 'issues-map');
        $warning = esc_html__('Images submitted are visible to everyone.', 'issues-map');
        $ok_str = $view === ADD_IMAGES_VIEW ? esc_attr__('Next', 'issues-map') : esc_attr__('OK', 'issues-map');
        $cancel_str = esc_attr__('Cancel', 'issues-map');
        $view_attr = esc_attr($view);
        $wpnonce = wp_create_nonce('im-add-images', 'issues-map');

        require_once 'utils/file-uploader.php';
        $file_uploader = new FileUploader($this->_plugin->get_upload_dir());
        $upload_control = $file_uploader->do_file_uploader_control();
        $cancel_button = '';
        if ($view === EDIT_IMAGES_VIEW) {
            $cancel_button = '<input id="im-cancel-add-images-button" class="im-button im-secondary-button" type="button" value="' . $cancel_str . '"></input>';
        }
        $content = <<<EOS
            <form action="" id="im-add-images-form" class="im-form" method="post" enctype="multipart/form-data">
                <div class="im-form-inner">
                    <div class="im-form-section">
                        <p class="im-intro">{$intro_str}</p>
                        <p class="im-message"><span class="dashicons dashicons-info-outline"></span> {$warning}</p>
                    </div>
                    <div class="im-form-section">
                        $upload_control
                    </div>
                    <div class="im-form-section">
                        <p id="im-message" class="im-message"></p>
                        <input id="im-ok-button" class="im-button" type="submit" value="{$ok_str}"></input>
                        $cancel_button
                        <input type="hidden" id="im-view" name="im-view" value="{$view_attr}"></input>
                        <input type="hidden" id="im-add-images-nonce" name="im-add-images-nonce" value="{$wpnonce}"></input>
                    </div>
                </div>
            </form>
EOS;
        return $content;
    }

    /*
     * Edit issue location form.
     */

    public function get_edit_location_form($issue_id, $view) {
        $intro_str = esc_html__('Drag the pin on the map to set the issue location.', 'issues-map');
        $warning = esc_html__('Issue locations are visible to everyone.', 'issues-map');
        $ok_str = $view === SET_LOCATION_VIEW ? esc_attr__('Finish', 'issues-map') : esc_attr__('OK', 'issues-map');
        $wpnonce = wp_create_nonce('im-edit-location');
        $lat = floatval(get_post_meta($issue_id, META_LATITUDE, true));
        $lon = floatval(get_post_meta($issue_id, META_LONGITUDE, true));
        if ($view === SET_LOCATION_VIEW) {
            $zoom = intval(get_option(OPTION_ZOOM_MAP_VIEW, DEFAULT_ZOOM_MAP_VIEW));
        } else {
            $zoom = intval(get_option(OPTION_ZOOM_ISSUE_VIEW, DEFAULT_ZOOM_ISSUE_VIEW));
        }
        $api_key = get_option(OPTION_GMAPS_API_KEY, '');
        require_once 'google-map.php';
        $map_control = new GoogleMap($api_key);
        $map_content = $map_control->do_location_picker_map($lat, $lon, $zoom);

        $cancel_button = '';
        if ($view === EDIT_LOCATION_VIEW) {
            $cancel_str = esc_attr__('Cancel', 'issues-map');
            $cancel_button = '<input id="im-back-button" class="im-button im-back-button" type="button" value="' . $cancel_str . '">';
        }

        $content = <<<EOS
            <form action='' id='im-edit-location-form' class='im-form' method='post'>
                <div class='im-form-section'>
                    <p class="im-intro">{$intro_str}</p>
                    <p class="im-message"><span class="dashicons dashicons-info-outline"></span> {$warning}</p>
                </div>
                <div class='im-form-section'>
                    $map_content
                </div>
                <div class='im-form-section'>
                    <p id='im-message' class='im-message'></p>
                    <input id='im-ok-button' class='im-button' type='submit' value='{$ok_str}'></input>
                    $cancel_button
                    <input type='hidden' id='im-edit-location-nonce' name='im-edit-location-nonce' value='{$wpnonce}'></input>
                </div>
            </form>
EOS;
        return $content;
    }

    /*
     * Issue view.
     */

    public function get_issue_view($issue_id) {

        $user_profile = $this->_plugin->get_user_profile();
        $can_edit = $user_profile->current_user_can_edit_post($issue_id);

        $content = '<div class="im-issue-view">';

        // Show details
        $content .= '<div class="im-form-section">';
        $content .= $this->get_issue_details_html($issue_id);
        // Details editing options
        if ($can_edit) {
            $edit_details_str = esc_html__('Edit details', 'issues-map');
            $edit_details_title = esc_attr__('Edit details', 'issues-map');
            $edit_details_url = esc_url( add_query_arg('view', EDIT_ISSUE_VIEW, get_permalink($issue_id)) );
            $content .= '<p><a class="im-edit-link" href="' . $edit_details_url . '" title="' . $edit_details_title . '">' . $edit_details_str . '</a></p>';
        }
        $content .= '<hr/></div>';

        // Show uploaded images with captions
        $images_str = esc_html__('Images', 'issues-map');
        $content .= '<div class="im-form-section"><h3>' . $images_str . '</h3>';
        $args = array(
            'selectable' => $can_edit,
            'hyperlink' => false,
            'timestamp' => true,
            'gps' => false,
            'featured_image' => $can_edit,
        );
        $images_html = $this->get_images_html($issue_id, $args);
        if ($images_html) {
            $content .= $images_html;
        } else {
            $content .= '<p>' . __('No images added yet.', 'issues-map') . '</p>';
        }
        // Image editing options
        $can_upload = $can_edit && $user_profile->current_user_can_upload_images();
        if ($can_upload) {
            $add_images_str = esc_html__('Add images', 'issues-map');
            $add_images_title = esc_attr__('Add images', 'issues-map');
            $add_images_url = esc_url( add_query_arg('view', EDIT_IMAGES_VIEW, get_permalink($issue_id)) );
            $content .= '<p>';
            $content .= '<a id="im-add-images-link" class="im-edit-link" href="' . $add_images_url . '" title="' . $add_images_title . '">' . $add_images_str . '</a>';
            if ($images_html) {
                $delete_image_str = esc_html__('Delete selected image', 'issues-map');
                $delete_image_title = esc_attr__('Delete the selected image', 'issues-map');
                $set_as_featured_str = esc_html__('Set as featured image', 'issues-map');
                $set_as_featured_title = esc_attr__('Make the selected image the main image', 'issues-map');
                $content .= '<a id="im-delete-image-link" href="#" class="im-edit-link" title="' . $delete_image_title . '">' . $delete_image_str . '</a>';
                $content .= '<a id="im-set-as-featured-link" href="#" class="im-edit-link" title="' . $set_as_featured_title . '">' . $set_as_featured_str . '</a>';
            }
            $content .= '</p>';
            $content .= '<p id="im-message" class="im-message"></p>';
        }
        $content .= '<hr/></div>';

        // Reports summary
        $reports_str = esc_html__('Reports', 'issues-map');
        $content .= '<div class="im-form-section"><h3>' . $reports_str . '</h3>';
        $content .= $this->get_reports_summary_html($issue_id);
        // Report editing options
        if ($can_edit) {
            // Create report button
            $create_report_str = esc_html__('Create report', 'issues-map');
            $create_report_title = esc_attr__('Create a report for this issue', 'issues-map');
            $create_report_href = esc_url( add_query_arg('view', ADD_REPORT_VIEW, get_permalink($issue_id)) );
            $content .= '<p><a href="' . $create_report_href . '" title="' . $create_report_title . '" id="im-create-report-button" class="button im-button">' . $create_report_str . '</a></p>';
        }
        $content .= '<hr/></div>';

        // Location map
        $location_str = esc_html__('Location', 'issues-map');
        $content .= '<div class="im-form-section"><h3>' . $location_str . '</h3>';
        $lat = get_post_meta($issue_id, META_LATITUDE, true);
        $lon = get_post_meta($issue_id, META_LONGITUDE, true);
        $zoom = intval(get_option(OPTION_ZOOM_ISSUE_VIEW, DEFAULT_ZOOM_ISSUE_VIEW));
        $api_key = get_option(OPTION_GMAPS_API_KEY, '');
        require_once 'google-map.php';
        $map_control = new GoogleMap($api_key);
        $content .= $map_control->do_static_single_location_map($lat, $lon, $zoom);
        // Editing links
        if ($can_edit) {
            // Edit location option
            $edit_location_str = esc_html__('Edit location', 'issues-map');
            $edit_location_title = esc_attr__('Edit location', 'issues-map');
            $edit_location_url = esc_url( add_query_arg('view', EDIT_LOCATION_VIEW, get_permalink($issue_id)) );
            $content .= '<p><a class="im-edit-link" href="' . $edit_location_url . '" title="' . $edit_location_title . '">' . $edit_location_str . '</a></p>';
            // Delete issue option
            $delete_issue_str = esc_html__('Delete issue', 'issues-map');
            $delete_issue_title = esc_attr__('Delete issue', 'issues-map');
            $content .= '<p><a id="im-delete-issue-link" class="im-edit-link" href="#" title="' . $delete_issue_title . '">' . $delete_issue_str . '</a></p>';
        }
        $content .= '</div>';

        // Hidden elements
        $wpnonce = wp_create_nonce('im-issue-or-report');
        $content .= '<input type="hidden" id="im-issue-or-report-nonce" name="im-issue-or-report-nonce" value="' . $wpnonce . '"></input>';
        $content .= '<div id="im-confirm-dialog"></div>';
        $content .= '<div id="im-alert-dialog"></div>';

        $content .= '</div>';

        return $content;
    }

    /*
     * Get the html to display issue images optionally with captions.
     */

    public function get_images_html(
            $issue_id,
            $args = array(
                'selectable' => false,
                'featured_image' => false,
                'hyperlink' => false,
                'timestamp' => true,
                'gps' => false,
            )
    ) {
        $content = '';

        $issue_data_mgr = $this->_plugin->get_issue_data_mgr();
        $upload_url = $this->_plugin->get_upload_url();
        $image_data = $issue_data_mgr->get_image_meta_data($issue_id);

        $selectable_class = (isset($args['selectable']) && $args['selectable']) ? ' im-selectable' : '';
        $featured_image = (isset($args['featured_image']) && $args['featured_image']) ? $issue_data_mgr->get_featured_image($issue_id) : '';
        $include_hyperlink = isset($args['hyperlink']) && $args['hyperlink'];
        $include_timestamp = isset($args['timestamp']) && $args['timestamp'];
        $include_gps = isset($args['gps']) && $args['gps'];

        $count = 0;
        foreach ($image_data as $image_meta) {
            $count++;
            $filename = $image_meta[META_FILENAME];
            $featured_class = $filename === $featured_image ? ' im-featured' : '';
            $thumbnail = str_replace('.', '-thumb.', $filename);
            $fileref = esc_attr($filename);
            $orig_src = esc_url($upload_url . $filename);
            $src = esc_url($upload_url . $thumbnail);
            $alt = esc_attr($filename);
            $width = THUMBNAIL_WIDTH;
            $content .= '<div class="im-image-item' . $featured_class . '" data-fileref="' . $fileref . '">';
            $content .= '<figure><img id="im-image-' . $count . '" class="im-image' . $selectable_class . '" alt="' . $alt . '" src="' . $src . '" width="' . $width . '" />';
            $content .= '<figcaption class="im-caption">';
            if ($include_hyperlink) {
                $content .= '<a href="' . esc_url($orig_src) . '">' . esc_html($filename) . '</a><br/>';
            }
            if ($include_timestamp && $image_meta[META_TIMESTAMP]) {
                $content .= esc_html($image_meta[META_TIMESTAMP]) . '<br/>';
            }
            $lat = $image_meta[META_LATITUDE];
            $lng = $image_meta[META_LONGITUDE];
            if ($include_gps && ($lat || $lng)) {
                $gps_line = 'GPS: ' . $lat . ', ' . $lng;
                $content .= esc_html($gps_line) . '<br/>';
            }
            $content .= '</figcaption></figure>';
            if ($selectable_class || $featured_image) {
                $content .= '<div class="im-featured-star"><span class="dashicons dashicons-star-filled"></span></div>';
            }
            $content .= '</div>';
        }
        return $content;
    }

    /*
     * Generate issue item html for list view or map popup info window.
     */

    public function get_issue_preview_html($issue_id, $style = 'full') {
        $title = get_the_title($issue_id);
        $view_issue_title = esc_attr__('View this issue', 'issues-map');
        $open_in_new_tab = get_option(OPTION_OPEN_IN_NEW_TAB, DEFAULT_OPEN_IN_NEW_TAB);
        $target = $open_in_new_tab ? ' target="_blank"' : '';
        $content = '<div class="im-listing-item im-listing-style-' . $style . '">';
        if ($style !== 'popup') {
            $content .= '<div class="im-listing-item-image">' . $this->get_featured_image_html($issue_id, $style) . '</div>';
        }
        $content .= '<div class="im-listing-item-summary"><div class="im-listing-item-title">';
        $content .= '<a href="' . esc_attr(get_permalink($issue_id)) . '" title="' . $view_issue_title . '" class="im-listing-item-title"' . $target . '><h3>' . esc_html($title) . '</h3></a>';
        $content .= '</div>';
        $content .= $this->get_issue_details_html($issue_id, $style);
        $content .= '</div></div>';
        return $content;
    }

    /**
     * Issue details section (status, author, date added, etc.).
     * @param int $issue_id
     * @param string $style 'full' | 'list' | 'popup'
     * @return string
     */
    public function get_issue_details_html($issue_id, $style = 'full') {

        $post = get_post($issue_id);

        $issue_data_mgr = $this->_plugin->get_issue_data_mgr();
        $issue_category_slug = DEFAULT_ISSUE_CATEGORY_SLUG;
        $issue_category = __(DEFAULT_ISSUE_CATEGORY, 'issues-map');
        $category_icon = DEFAULT_CATEGORY_ICON_NAME;
        $icon_color = DEFAULT_COLOR;
        $cat = $issue_data_mgr->get_issue_category($issue_id);
        if ($cat) {
            $issue_category = $cat->name;
            $issue_category_slug = $cat->slug;
            $category_icon = get_term_meta($cat->term_id, META_ICON_NAME, true);
            $icon_color = get_term_meta($cat->term_id, META_COLOR, true);
        }
        $issue_status_slug = ISSUE_STATUS_UNREPORTED_SLUG;
        $issue_status = __(ISSUE_STATUS_UNREPORTED, 'issues-map');
        $status_color = DEFAULT_COLOR;
        $status = $issue_data_mgr->get_issue_status($issue_id);
        if ($status) {
            $issue_status = $status->name;
            $issue_status_slug = $status->slug;
            $status_color = get_term_meta($status->term_id, META_COLOR, true);
        }
        $added_by_str = esc_html__('Added by:', 'issues-map');
        $author = get_post_meta($issue_id, META_ADDED_BY, true);
        $added_on_str = esc_html__('Added on:', 'issues-map');
        $date = esc_html(date('d.m.Y', strtotime($post->post_date)));
        $issue_id_str = esc_html__('Issue ID:', 'issues-map');
        $comments_str = esc_html__('Comments:', 'issues-map');
        $num_comments = $post->comment_count;

        $html = '<div class="im-issue-details"><div class="im-issue-groups">';
        if ($issue_category_slug !== DEFAULT_ISSUE_CATEGORY_SLUG) {     // Don't show if uncategorised
            $html .= '<div class="im-issue-category" style="background-color: ' . $icon_color . ';">';
            $html .= '<i class="material-icons">' . $category_icon . '</i> <span>' . $issue_category . '</span>';
            $html .= '</div>';
        }
        if ($issue_status_slug !== ISSUE_STATUS_UNREPORTED_SLUG) {     // Don't status show if unreported
            $html .= '<div class="im-issue-status" style="background-color: ' . $status_color . ';">';
            $html .= '<i class="material-icons">' . ISSUE_STATUS_ICON_NAME . '</i> <span>' . $issue_status . '</span>';
            $html .= '</div>';
        }
        $html .= '</div><div class="im-issue-summary">';
        $html .= '<div class="im-issue-meta"><label>' . $added_by_str . '</label><span class="im-author-name">' . $author . '</span></div>';
        $html .= '<div class="im-issue-meta"><label>' . $added_on_str . '</label><span class="im-date">' . $date . '</span></div>';
        $html .= '<div class="im-issue-meta"><label>' . $issue_id_str . '</label><span class="im-date">' . $issue_id . '</span></div>';
        if ($num_comments > 0) {
            $html .= '<div class="im-issue-meta"><label>' . $comments_str . '</label><span class="im-num-comments">' . $num_comments . '</span></div>';
        }
        $html .= '</div>';

        $description = '';
        if ($style === 'full') {
            $description = nl2br(esc_html($post->post_content));
        }
        else {
            $description = esc_html(Utils::cap_str_len($post->post_content, MAX_LEN_200, true));
        }
        $html .= '<div class="im-issue-description">' . $description . '</div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Get the html to display a featured image for an issue.
     * Note: this does not use the Wordpress featured image functionality.
     * @param int $issue_id
     * @param string $style 'full' | 'list' | 'popup'
     * @return string
     */
    public function get_featured_image_html($issue_id, $style) {
        $issue_data_mgr = $this->_plugin->get_issue_data_mgr();
        $filename = $issue_data_mgr->get_featured_image($issue_id);
        if ($filename) {
            $filename = str_replace('.', '-thumb.', $filename);
            $url = $this->_plugin->get_upload_url() . $filename;
        } else {
            // Show default image
            $url = plugin_dir_url(__FILE__) . 'img/issue_default.png';
        }

        $href = esc_attr(get_permalink($issue_id));
        $view_issue_title = esc_attr__('View this issue', 'issues-map');
        $src = esc_url($url);
        $alt = esc_attr(get_the_title($issue_id));
        $width = THUMBNAIL_WIDTH;
        $open_in_new_tab = get_option(OPTION_OPEN_IN_NEW_TAB, DEFAULT_OPEN_IN_NEW_TAB);
        $target = $open_in_new_tab ? 'target="_blank"' : '';
        $html = "<div class='im-featured-img'><a href='$href' title='$view_issue_title' $target><img alt='$alt' width='$width' src='$src' /></a></div>";

        return $html;
    }

    /**
     * Get a summary of all the reports for the specified issue.
     * @param int $issue_id
     * @return string
     */
    private function get_reports_summary_html($issue_id) {

        $content = '<div class="im-reports-summary">';

        $args = array(
            'post_type' => REPORT_POST_TYPE,
            'post_status' => 'publish',
            'orderby' => 'post_date',
            'order' => 'ASC',
            'meta_query' => array(
                'issue_clause' => array(
                    'key' => META_ISSUE_ID,
                    'value' => $issue_id,
                ),
            ),
        );

        $query_posts = new \WP_Query($args);
        if ($query_posts->have_posts()) {
            $view_str = esc_html__('View', 'issues-map');
            $view_title = esc_attr__('View this report', 'issues-map');
            $edit_str = esc_html__('Edit', 'issues-map');
            $edit_title = esc_attr__('Edit this report', 'issues-map');
            $sent_prefix = __('Report %s sent by %s', 'issues-map');
            $created_prefix = __('Report %s created by %s', 'issues-map');
            $delete_str = esc_html__('Delete', 'issues-map');
            $delete_title = esc_attr__('Delete this report', 'issues-map');
            $user_profile = $this->_plugin->get_user_profile();
            while ($query_posts->have_posts()) {
                $query_posts->the_post();
                $post = get_post();
                $added_by = get_post_meta($post->ID, META_ADDED_BY, true);
                if (!$added_by) {
                    $added_by = __('anonymous user', 'issues-map');
                }
                $added_by = esc_html($added_by);
                $ref = esc_html(get_post_meta($post->ID, META_REF, true));
                $date_sent = esc_html(get_post_meta($post->ID, META_DATE_SENT, true));
                $date = $date_sent ? $date_sent : esc_html(get_post_meta($post->ID, META_DATE, true));
                $prefix = $date_sent ? $sent_prefix : $created_prefix;
                $view_href = get_permalink($post->ID);
                $content .= '<div class="im-report-summary-item" id="im-report-summary-item-' . $post->ID . '">';
                $content .= '<span class="im-date">' . date('d.m.Y', strtotime($date)) . ': </span>';
                $content .= '<span class="im-report-summary-text">' . esc_html(sprintf($prefix, $ref, $added_by)) . '</span>';
                // View / edit links
                if ($user_profile->current_user_can_edit_post($post->ID)) {
                    $content .= '<a href="' . esc_url($view_href) . '" title="' . $view_title . '" class="im-edit-link">' . $view_str . '</a>';
                    $edit_href = esc_url(add_query_arg('view', EDIT_REPORT_VIEW, $view_href));
                    $content .= "<a href='{$edit_href}' title='{$edit_title}' class='im-edit-link'>{$edit_str}</a>";
                    $content .= "<a href='#' title='{$delete_title}' class='im-issue-delete-report-link im-edit-link' data-report-id='{$post->ID}'>{$delete_str}</a>";
                }
                $content .= '</div>';
            }
        } else {
            $content .= __('No reports available for this issue.', 'issues-map');
        }
        wp_reset_postdata();

        $content .= '</div>';
        return $content;
    }

}
