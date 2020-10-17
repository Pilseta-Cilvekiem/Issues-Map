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

require_once 'utils/utils.php';
require_once 'utils/wp-utils.php';

/*
 * Implements asynchronous AJAX callbacks.
 */

class AsyncManager {

    private $_plugin;

    public function __construct($plugin) {
        $this->_plugin = $plugin;
    }

    /**
     * Add/edit issue details.
     */
    public function edit_details_async() {
        // Get posted values
        check_ajax_referer('im-edit-details', 'security');
        $inputs = filter_input_array(INPUT_POST, array(
            'issue_id' => FILTER_VALIDATE_INT,
            'issue_category' => FILTER_VALIDATE_INT,
            'issue_status' => FILTER_VALIDATE_INT,
            'issue_title' => FILTER_DEFAULT,
            'description' => FILTER_DEFAULT,
            'added_by' => FILTER_DEFAULT,
            'email_address' => FILTER_VALIDATE_EMAIL
        ));
        $issue_id = $inputs['issue_id'];
        $issue_category_id = $inputs['issue_category'];
        $issue_status_id = $inputs['issue_status'];
        $issue_title = Utils::cap_str_len(sanitize_text_field($inputs['issue_title']), MAX_LEN_64);
        $description = Utils::cap_str_len(sanitize_textarea_field($inputs['description']), MAX_DESCRIPTION_LEN);
        $added_by = Utils::cap_str_len(sanitize_text_field($inputs['added_by']), MAX_LEN_32);
        $email_address = $inputs['email_address'];

        $success = true;
        $message = '';
        $redirect_url = '';
        
        // Validate
        if (!$issue_title) {
            $message = esc_html__('Please enter an issue title.', 'issues-map');
            $success = false;
        }
        else if (!$added_by) {
            $message = esc_html__('Please enter your name.', 'issues-map');
            $success = false;
        }

        if ($success) {
            $success = false;
            $auth_mgr = $this->_plugin->get_auth_mgr();
            $issue_data_mgr = $this->_plugin->get_issue_data_mgr();

            if ($issue_id) {
                // Edit issue details
                if ($auth_mgr->current_user_can_edit_post($issue_id)) {
                    $issue_id = $issue_data_mgr->update_issue_details(
                            $issue_id,
                            $issue_category_id,
                            $issue_status_id,
                            $issue_title,
                            $description,
                            $added_by,
                            $email_address);
                    if ($issue_id) {
                        $redirect_url = get_permalink($issue_id);   // Back to issue page
                        $success = true;
                    } else {
                        $message = esc_html__('Error while updating the issue details.', 'issues-map');
                    }
                } else {
                    $message = esc_html__('You are not authorised to edit this issue.', 'issues-map');
                }
            } else {
                // Add new issue
                if ($auth_mgr->current_user_can_add_post()) {
                    $default_lat = get_option(OPTION_CENTRE_LAT, DEFAULT_CENTRE_LAT);
                    $default_lng = get_option(OPTION_CENTRE_LNG, DEFAULT_CENTRE_LNG);
                    $default_status = get_term_by('slug', ISSUE_STATUS_UNREPORTED_SLUG, ISSUE_STATUS_TAXONOMY);
                    $issue_status_id = $default_status ? $default_status->term_id : 0;
                    $user_meta_id = $auth_mgr->get_val(META_USER_ID);
                    $issue_id = $issue_data_mgr->add_issue(
                            $user_meta_id,
                            $issue_category_id,
                            $issue_status_id,
                            $issue_title,
                            $description,
                            $added_by,
                            $email_address,
                            $default_lat,
                            $default_lng);
                    if ($issue_id) {
                        $permalink = get_permalink($issue_id);
                        if ($auth_mgr->current_user_can_upload_images()) {
                            $redirect_url = add_query_arg('view', ADD_IMAGES_VIEW, $permalink); // Next step: add images
                        } else {
                            $redirect_url = add_query_arg('view', SET_LOCATION_VIEW, $permalink); // Next step: set location                       
                        }
                        $success = true;
                    } else {
                        $message = esc_html__('Error while adding a new issue.', 'issues-map');
                    }
                } else {
                    $message = esc_html__('You are not authorised to add issues.', 'issues-map');
                }
            }
        }

        $response = array('success' => $success, 'redirect_url' => $redirect_url, 'message' => $message);
        WPUtils::send_json_response($response);
    }

    /**
     * Add issue images.
     */
    public function add_images_async() {
        // Get posted values
        check_ajax_referer('im-add-images', 'security');
        $inputs = filter_input_array(INPUT_POST, array(
            'issue_id' => FILTER_VALIDATE_INT,
            'view' => FILTER_DEFAULT,
            'image_list' => array('filter' => FILTER_DEFAULT,
                'flags' => FILTER_REQUIRE_ARRAY),
        ));
        $issue_id = $inputs['issue_id'];

        $auth_mgr = $this->_plugin->get_auth_mgr();
        $issue_data_mgr = $this->_plugin->get_issue_data_mgr();

        $success = false;
        $redirect_url = '';
        $message = '';
        if ($issue_id && $auth_mgr->current_user_can_edit_post($issue_id)) {
            $view = Utils::apply_default_val(sanitize_text_field($inputs['view']), EDIT_IMAGES_VIEW);

            // Move temporary uploaded image files to permanent locations by replacing 'tmp' with issue id in filename
            $success = $issue_data_mgr->add_issue_images($issue_id, $inputs['image_list']);
            if ($success) {
                // Extract and update location and timestamp data for issue images
                $image_data = $issue_data_mgr->update_image_meta_data($issue_id);

                $redirect_url = get_permalink($issue_id);
                if ($view === ADD_IMAGES_VIEW) {
                    $redirect_url = add_query_arg('view', SET_LOCATION_VIEW, $redirect_url);
                }
            } else {
                $message = esc_html__('Error while adding images to the issue.', 'issues-map');
            }

            // Delete any orphaned image files while we are here
            $issue_data_mgr->delete_orphaned_image_files();
        } else {
            $message = esc_html__('You are not authorised to add images to this issue.', 'issues-map');
        }

        $response = array('success' => $success, 'redirect_url' => $redirect_url, 'message' => $message);
        WPUtils::send_json_response($response);
    }

    /**
     * Handle cancelling in Add images form.
     */
    public function cancel_add_images_async() {
        // Get posted values
        check_ajax_referer('im-add-images', 'security');

        // Delete any images the user has just uploaded
        $image_list = filter_input(INPUT_POST, 'image_list', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        $issue_data_mgr = $this->_plugin->get_issue_data_mgr();
        $issue_data_mgr->delete_tmp_images($image_list);

        // Delete any old unused image files while we are here        
        $issue_data_mgr->delete_orphaned_image_files();

        $response = array('success' => true, 'error' => '');
        WPUtils::send_json_response($response);
    }

    /**
     * Update the location of an issue.
     * 
     */
    public function edit_location_async() {
        // Get posted values
        check_ajax_referer('im-edit-location', 'security');
        $issue_id = filter_input(INPUT_POST, 'issue_id', FILTER_VALIDATE_INT);

        $auth_mgr = $this->_plugin->get_auth_mgr();
        $issue_data_mgr = $this->_plugin->get_issue_data_mgr();

        if (!$issue_id || !$auth_mgr->current_user_can_edit_post($issue_id)) {
            wp_die();
        }
        $lat = filter_input(INPUT_POST, 'lat', FILTER_VALIDATE_FLOAT);
        $lng = filter_input(INPUT_POST, 'lng', FILTER_VALIDATE_FLOAT);
        $success = false;
        $redirect_url = '';
        $message = '';
        if ($lat > 0 || $lng > 0) {
            $result = $issue_data_mgr->update_issue_location($issue_id, $lat, $lng);
            if ($result) {
                $redirect_url = get_permalink($issue_id);
                $success = true;
            } else {
                $message = esc_html__('Error updating issue location.', 'issues-map');
            }
        } else {
            $message = esc_html__('Missing latitude and longitude parameter values.', 'issues-map');
        }

        $response = array('success' => $success, 'redirect_url' => $redirect_url, 'message' => $message);
        WPUtils::send_json_response($response);
    }

    /*
     * Add / edit issue report (async callback).
     */

    public function edit_report_async() {
        // Get posted values
        check_ajax_referer('im-edit-report', 'security');
        $report_id = filter_input(INPUT_POST, 'report_id', FILTER_VALIDATE_INT);
        $issue_id = filter_input(INPUT_POST, 'issue_id', FILTER_VALIDATE_INT);
        $this->edit_report($report_id, $issue_id, true);
    }

    /*
     * Add / edit issue report or report template (async or via save post).
     */

    public function edit_report($report_id, $issue_id, $send_response) {
        $auth_mgr = $this->_plugin->get_auth_mgr();
        $report_data_mgr = $this->_plugin->get_report_data_mgr();
        $inputs = filter_input_array(INPUT_POST, array(
            'im-report-template-id' => FILTER_VALIDATE_INT,
            'im-report-recipient-name' => FILTER_DEFAULT,
            'im-report-recipient-email' => FILTER_DEFAULT,
            'im-report-email-body' => FILTER_DEFAULT,
            'im-report-to-address' => FILTER_DEFAULT,
            'im-report-from-address' => FILTER_DEFAULT,
            'im-report-from-email' => FILTER_DEFAULT,
            'im-report-greeting' => FILTER_DEFAULT,
            'im-report-addressee' => FILTER_DEFAULT,
            'im-report-body' => FILTER_DEFAULT,
            'im-report-sign-off' => FILTER_DEFAULT,
            'im-report-added-by' => FILTER_DEFAULT,
        ));
        $template_id = $inputs['im-report-template-id'] !== null ? $inputs['im-report-template-id'] : 0;
        $recipient_name = Utils::cap_str_len(sanitize_text_field($inputs['im-report-recipient-name']), MAX_LEN_64);
        $recipient_email = Utils::cap_str_len(sanitize_text_field($inputs['im-report-recipient-email']), MAX_LEN_64);
        $email_body = Utils::cap_str_len(sanitize_textarea_field($inputs['im-report-email-body']), MAX_LEN_1024);
        $to_address = Utils::cap_str_len(sanitize_textarea_field($inputs['im-report-to-address']), MAX_LEN_256);
        $from_address = Utils::cap_str_len(sanitize_textarea_field($inputs['im-report-from-address']), MAX_LEN_256);
        $from_email = Utils::cap_str_len(sanitize_text_field($inputs['im-report-from-email']), MAX_LEN_64);
        $greeting = Utils::cap_str_len(sanitize_text_field($inputs['im-report-greeting']), MAX_LEN_64);
        $addressee = Utils::cap_str_len(sanitize_text_field($inputs['im-report-addressee']), MAX_LEN_64);
        $allowable_tags = array('<b>', '<i>', '<u>', '<a>');
        $body = strip_tags(Utils::cap_str_len($inputs['im-report-body'], MAX_REPORT_LEN), $allowable_tags);
        $sign_off = Utils::cap_str_len(sanitize_text_field($inputs['im-report-sign-off']), MAX_LEN_64);
        $added_by = Utils::cap_str_len(sanitize_text_field($inputs['im-report-added-by']), MAX_LEN_64);
        $date = date('d.m.Y');
        if ($issue_id) {
            // Issue report
            $from_email = filter_var($from_email, FILTER_VALIDATE_EMAIL);
        }
        $user_meta_id = $auth_mgr->get_val(META_USER_ID);
        
        $success = true;
        $message = '';
        $redirect_url = '';
        
        // Validate issue reports
        if ($issue_id) {
            if (!$greeting || !$addressee) {
                $message = esc_html__('Please enter a greeting and recipient name.', 'issues-map');
                $success = false;
            }
            else if (!$from_address && !$from_email) {
                $message = esc_html__('Please enter your address and/or email address.', 'issues-map');
                $success = false;
            }        
            else if (!$body) {
                $message = esc_html__('Please enter some content for the report.', 'issues-map');
                $success = false;
            }       
            else if (!$sign_off || !$added_by) {
                $message = esc_html__('Please enter a sign off and your name.', 'issues-map');
                $success = false;
            }        
        }
        
        if ($success) {
            $success = false;
            if ($report_id) {
                // Edit report                       
                if ($auth_mgr->current_user_can_edit_post($report_id)) {
                    if ($report_data_mgr->update_report(
                                    $report_id,
                                    $user_meta_id,
                                    $issue_id,
                                    $template_id,
                                    $recipient_name,
                                    $recipient_email,
                                    $email_body,
                                    $to_address,
                                    $from_address,
                                    $from_email,
                                    $greeting,
                                    $addressee,
                                    $body,
                                    $sign_off,
                                    $added_by,
                                    $date)) {
                        // Delete out-dated draft pdf
                        $date_sent = get_post_meta($report_id, META_DATE_SENT, true);
                        if (!$date_sent) {
                            $report_data_mgr->delete_pdf($report_id);
                        }

                        $redirect_url = get_permalink($report_id);   // Go to report page
                        $success = true;
                    } else {
                        $message = esc_html__('Error while updating report information.', 'issues-map');
                    }
                } else {
                    $message = esc_html__('You are not authorised to edit this information.', 'issues-map');
                }
            } else {
                // Add new report or report template
                if ($auth_mgr->current_user_can_add_post()) {
                    $report_id = $report_data_mgr->add_report(
                            $user_meta_id,
                            $issue_id,
                            $template_id,
                            $recipient_name,
                            $recipient_email,
                            $email_body,
                            $to_address,
                            $from_address,
                            $from_email,
                            $greeting,
                            $addressee,
                            $body,
                            $sign_off,
                            $added_by,
                            $date);
                    if ($report_id) {
                        // Update status for issue reports
                        if ($issue_id) {
                            $issue_data_mgr = $this->_plugin->get_issue_data_mgr();
                            $issue_data_mgr->do_issue_status_workflow($issue_id, ISSUE_STATUS_UNREPORTED_SLUG, ISSUE_STATUS_REPORT_CREATED_SLUG);
                        }

                        // Direct user to report page
                        $redirect_url = get_permalink($report_id);
                        $success = true;
                    } else {
                        if ($issue_id) {
                            $message = esc_html__('Error while creating report.', 'issues-map');
                        } else {
                            $message = esc_html__('Error while creating report template.', 'issues-map');
                        }
                    }
                } else {
                    $message = esc_html__('You are not authorised to add new reports.', 'issues-map');
                }
            }
        }

        if ($send_response) {
            $response = array('success' => $success, 'redirect_url' => $redirect_url, 'message' => $message);
            WPUtils::send_json_response($response);
        }
    }
    
    /*
     * Send report by email.
     */

    public function send_report_async() {

        check_ajax_referer('im-issue-or-report', 'security');
        $inputs = filter_input_array(INPUT_POST, array(
            'report_id' => FILTER_VALIDATE_INT,
        ));
        $report_id = $inputs['report_id'];
        $issue_id = get_post_meta($report_id, META_ISSUE_ID, true);

        $auth_mgr = $this->_plugin->get_auth_mgr();

        $success = false;
        $message = '';
        $recipient_email = filter_var( get_post_meta($report_id, META_RECIPIENT_EMAIL, true), FILTER_VALIDATE_EMAIL);
        $user_email = filter_var( get_post_meta($report_id, META_FROM_EMAIL, true), FILTER_VALIDATE_EMAIL);
        $moderator_email = filter_var(get_option(OPTION_MODERATOR_EMAIL, get_bloginfo('admin_email')), FILTER_VALIDATE_EMAIL);
        $can_send_reports = $auth_mgr->current_user_can_send_reports();
        $recipient_user = get_user_by('email', $recipient_email);
        $recipient_is_moderator = $recipient_user && $auth_mgr->is_moderator($recipient_user->ID);

        if (DEMO_VERSION) {        
            $message = esc_html__('Report sending is disabled in demo mode.', 'issues-map');
        }
        else if (!$report_id || !$issue_id || !$moderator_email || !$can_send_reports ||
                !$auth_mgr->current_user_can_edit_post($report_id))
        {
            $message = esc_html__('You are not authorised to send this report.', 'issues-map');
        }
        else if (!$recipient_email || !$user_email) {
            $message = esc_html__('Please specify valid email addresses in the report for both yourself and the recipient.', 'issues-map');            
        }
        else if (!$recipient_is_moderator && !$auth_mgr->current_user_can_send_reports_to_anyone()) {
            $message = esc_html__('You are only authorised to send reports to moderators.', 'issues-map');
        }
        else {
            $recipient_name = get_post_meta($report_id, META_RECIPIENT_NAME, true);
            $ref = get_post_meta($report_id, META_REF, true);            
            $subject = sanitize_text_field(__("Issue report", 'issues-map') . ' ' . $ref);
            $body = get_post_meta($report_id, META_EMAIL_BODY, true);
            $footer = sprintf(__("This email has been sent automatically from %s.", 'issues-map'), get_bloginfo('url'));
            $body .= "\n\n" . $footer;                       
            
            require_once 'report-writer.php';
            $report_writer = new ReportWriter($this->_plugin);
            $filename = $report_writer->create_pdf($report_id, false);
            if ($filename) {
                $filepath = $this->_plugin->get_upload_dir() . $filename;
                // Send from the moderator with the user's email as the reply-to address
                $headers = array('From: ' . sanitize_text_field(__('Issues Map', 'issues-map')) . ' <' . $moderator_email . '>',
                    //'Reply-To: ' . $user_email,   // Including this may result in 'data not accepted' error from SMTP server
                    'Cc: ' . $user_email,
                    );                        
                if (wp_mail($recipient_email, $subject, $body, $headers, array($filepath))) {
                    // Update issue status
                    $issue_data_mgr = $this->_plugin->get_issue_data_mgr();
                    $issue_data_mgr->do_issue_status_workflow($issue_id, ISSUE_STATUS_REPORT_CREATED_SLUG, ISSUE_STATUS_REPORT_SENT_SLUG);
                    update_post_meta($report_id, META_DATE_SENT, date('d.m.Y'));
                    $message = esc_html(sprintf(__('The report has been sent to %s', 'issues-map'), $recipient_name));
                    $success = true;
                } else {
                    $message = esc_html__('Error while trying to send the report.', 'issues-map');
                }
            } else {
                $message = esc_html__('Unable to create a PDF file for this report.', 'issues-map');
            }           
        }

        $response = array('success' => $success, 'message' => $message);
        WPUtils::send_json_response($response);
    }

    /*
     * Download report in PDF format.
     */

    public function download_report_async() {

        check_ajax_referer('im-issue-or-report', 'security');
        $inputs = filter_input_array(INPUT_POST, array(
            'report_id' => FILTER_VALIDATE_INT,
        ));
        $report_id = $inputs['report_id'];

        $success = false;
        $redirect_url = '';
        $message = '';
        $auth_mgr = $this->_plugin->get_auth_mgr();
        if ($report_id && $auth_mgr->current_user_can_edit_post($report_id)) {
            require_once 'report-writer.php';
            $report_writer = new ReportWriter($this->_plugin);
            $filename = $report_writer->create_pdf($report_id, false);
            if ($filename) {
                $success = true;
                $redirect_url = $this->_plugin->get_upload_url() . $filename;
            } else {
                $message = esc_html__('Unable to create a PDF file for this report.', 'issues-map');
            }
        } else {
            $message = esc_html__('You are not authorised to download this report.', 'issues-map');
        }

        $response = array('success' => $success, 'redirect_url' => $redirect_url, 'message' => $message);
        WPUtils::send_json_response($response);
    }

    /*
     * Delete image for issue.     
     */

    public function delete_issue_image_async() {

        check_ajax_referer('im-issue-or-report', 'security');
        $issue_id = filter_input(INPUT_POST, 'issue_id', FILTER_VALIDATE_INT);

        $auth_mgr = $this->_plugin->get_auth_mgr();
        if (!$issue_id || !$auth_mgr->current_user_can_edit_post($issue_id)) {
            wp_die();
        }
        $filename = sanitize_text_field(filter_input(INPUT_POST, 'filename', FILTER_DEFAULT));

        // Delete image and its thumbnail  
        $message = '';
        $issue_data_mgr = $this->_plugin->get_issue_data_mgr();
        $success = $issue_data_mgr->delete_issue_image($issue_id, $filename);
        if (!$success) {
            $message = esc_html__('Error while deleting image.', 'issues-map');
        }

        // Return the faetured image in case it has been updated
        $featured_image = esc_attr($issue_data_mgr->get_featured_image($issue_id));

        $response = array('success' => $success, 'featured_image' => $featured_image, 'message' => $message);
        WPUtils::send_json_response($response);
    }

    /*
     * Set the featured image for an issue.
     */

    public function set_featured_image_async() {

        check_ajax_referer('im-issue-or-report', 'security');
        $issue_id = filter_input(INPUT_POST, 'issue_id', FILTER_VALIDATE_INT);

        $auth_mgr = $this->_plugin->get_auth_mgr();
        if (!$issue_id || !$auth_mgr->current_user_can_edit_post($issue_id)) {
            wp_die();
        }
        $filename = sanitize_text_field(filter_input(INPUT_POST, 'filename', FILTER_DEFAULT));

        $success = false;
        $message = '';
        $featured_image = '';
        $issue_data_mgr = $this->_plugin->get_issue_data_mgr();
        if ($issue_data_mgr->update_featured_image($issue_id, $filename)) {
            $featured_image = esc_attr($filename);
            $success = true;
        } else {
            $message = esc_html__('Error while setting the featured image.', 'issues-map');
        }

        $response = array('success' => $success, 'featured_image' => $featured_image, 'message' => $message);
        WPUtils::send_json_response($response);
    }

    /**
     * Handle uploaded image
     */
    public function file_uploaded_async() {
        require_once 'utils/file-uploader.php';
        $file_uploader = new FileUploader($this->_plugin->get_upload_dir());
        $file_uploader->file_uploaded_async();
    }

    /**
     * Handle deletion of uploaded image.
     */
    public function upload_deleted_async() {
        require_once 'utils/file-uploader.php';
        $file_uploader = new FileUploader($this->_plugin->get_upload_dir());
        $file_uploader->upload_deleted_async();
    }

    /**
     * Delete issue.
     */
    public function delete_issue_async() {

        check_ajax_referer('im-issue-or-report', 'security');
        $issue_id = filter_input(INPUT_POST, 'issue_id', FILTER_VALIDATE_INT);

        $success = false;
        $message = '';
        $redirect_url = '/';
        $auth_mgr = $this->_plugin->get_auth_mgr();
        $issue_data_mgr = $this->_plugin->get_issue_data_mgr();
        if ($issue_id && $auth_mgr->current_user_can_edit_post($issue_id)) {
            if ($issue_data_mgr->delete_issue($issue_id)) {
                $plugin_home_id = get_option(OPTION_LIST_PAGE_ID, 0);
                $redirect_url = get_permalink($plugin_home_id);
                if (!$redirect_url) {
                    $redirect_url = '/';
                }
                $success = true;
            } else {
                $message = esc_html__('Unable to delete this issue.', 'issues-map');
            }
        } else {
            $message = esc_html__('You are not authorised to delete this issue.', 'issues-map');
        }

        $response = array('success' => $success, 'redirect_url' => $redirect_url, 'message' => $message);
        WPUtils::send_json_response($response);
    }

    /**
     * Delete report.
     */
    public function delete_report_async() {

        check_ajax_referer('im-issue-or-report', 'security');
        $report_id = filter_input(INPUT_POST, 'report_id', FILTER_VALIDATE_INT);

        $success = false;
        $message = '';
        $auth_mgr = $this->_plugin->get_auth_mgr();
        if ($report_id && $auth_mgr->current_user_can_edit_post($report_id)) {
            $issue_id = get_post_meta($report_id, META_ISSUE_ID, true);
            $report_data_mgr = $this->_plugin->get_report_data_mgr();
            if ($report_data_mgr->delete_report($report_id)) {
                // Last report deleted so reset issue status
                $issue_data_mgr = $this->_plugin->get_issue_data_mgr();
                $issue_data_mgr->do_issue_status_workflow($issue_id, ISSUE_STATUS_REPORT_CREATED_SLUG, ISSUE_STATUS_UNREPORTED_SLUG);
                $success = true;
            } else {
                $message = esc_html__('Unable to delete the report.', 'issues-map');
            }
        } else {
            $message = esc_html__('You are not authorised to delete the report.', 'issues-map');
        }

        $response = array('success' => $success, 'message' => $message);
        WPUtils::send_json_response($response);
    }

    /**
     * Get the items to display in the issue list (e.g. on next/prev page clicked or when the filters options are changed).
     */
    public function get_issues_list_async() {
        check_ajax_referer('im-list-view', 'security');
        $page_num = filter_input(INPUT_POST, 'page_num', FILTER_VALIDATE_INT);
        $filters = array();
        $filters['category'] = sanitize_text_field(filter_input(INPUT_POST, 'category_filter', FILTER_DEFAULT));
        $filters['status'] = sanitize_text_field(filter_input(INPUT_POST, 'status_filter', FILTER_DEFAULT));
        $filters['own_issues'] = filter_input(INPUT_POST, 'own_issues_filter', FILTER_VALIDATE_BOOLEAN);

        require_once 'issues-list.php';
        $issues_list = new IssuesList($this->_plugin);
        $data = $issues_list->get_list_items_html($filters, $page_num);

        $response = array('success' => true, 'data' => $data);
        WPUtils::send_json_response($response);
    }

    /**
     * Get the items to display on the map (e.g. when filters options are changed).
     */
    public function get_map_items_async() {
        check_ajax_referer('im-map-view', 'security');
        $filters = array();
        $filters['category'] = sanitize_text_field(filter_input(INPUT_POST, 'category_filter', FILTER_DEFAULT));
        $filters['status'] = sanitize_text_field(filter_input(INPUT_POST, 'status_filter', FILTER_DEFAULT));
        $filters['own_issues'] = filter_input(INPUT_POST, 'own_issues_filter', FILTER_VALIDATE_BOOLEAN);

        require_once 'map-view.php';
        $map_view = new MapView($this->_plugin);
        $map_content = $map_view->get_map_content($filters);
        $data = json_encode($map_content);

        $response = array('success' => true, 'data' => $data);
        WPUtils::send_json_response($response);
    }

    /*
     * Get the content for the popup info window in the map view. 
     */

    public function get_info_window_content_async() {
        check_ajax_referer('im-map', 'security');
        $issue_id = filter_input(INPUT_POST, 'issue_id', FILTER_VALIDATE_INT);

        $issue_content_mgr = $this->_plugin->get_issue_content_mgr();
        $html = $issue_content_mgr->get_issue_preview_html($issue_id, 'popup');

        $response = array('success' => true, 'data' => $html);
        WPUtils::send_json_response($response);
    }

    /**
     * Respond to unauthorised requests from users who are not logged in.
     */
    public function unauthorised_async() {
        // Send response
        $response = array('success' => false, 'message' => esc_html__('Please log in.', 'issues-map'));
        WPUtils::send_json_response($response);
    }

}
