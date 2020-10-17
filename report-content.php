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

/**
 * Generates the front end forms for viewing and editing issue reports and report templates.
 */
class ReportContentManager {

    private $_plugin;

    public function __construct($plugin) {
        $this->_plugin = $plugin;
    }

    /*
     * Get the content for a report or report template.
     */

    public function get_report_view($report_id) {

        $post = get_post($report_id);
        if (!$post) {
            return esc_html__('Invalid report ID.', 'issues-map');
        }

        $report_meta = get_post_meta($post->ID, '', false);
        $recipient_name = esc_html(WPUtils::get_meta_val($report_meta, META_RECIPIENT_NAME));
        $recipient_email = esc_html(WPUtils::get_meta_val($report_meta, META_RECIPIENT_EMAIL));
        $email_body = nl2br(esc_html(WPUtils::get_meta_val($report_meta, META_EMAIL_BODY)));
        $from_address = nl2br(esc_textarea(WPUtils::get_meta_val($report_meta, META_FROM_ADDRESS)));
        $from_email = esc_html(WPUtils::get_meta_val($report_meta, META_FROM_EMAIL));
        $to_address = nl2br(esc_textarea(WPUtils::get_meta_val($report_meta, META_TO_ADDRESS)));
        $greeting = esc_html(WPUtils::get_meta_val($report_meta, META_GREETING));
        $addressee = esc_html(WPUtils::get_meta_val($report_meta, META_ADDRESSEE));
        $allowable_tags = array('<b>', '<i>', '<u>', '<a>');
        $body = nl2br(strip_tags($post->post_content, $allowable_tags));
        $sign_off = esc_html(WPUtils::get_meta_val($report_meta, META_SIGN_OFF));
        $added_by = esc_html(WPUtils::get_meta_val($report_meta, META_ADDED_BY));
        $date = esc_html(WPUtils::get_meta_val($report_meta, META_DATE));
        $date_sent = esc_html(WPUtils::get_meta_val($report_meta, META_DATE_SENT));
        $ref = esc_html(WPUtils::get_meta_val($report_meta, META_REF));
        $issue_id = intval(WPUtils::get_meta_val($report_meta, META_ISSUE_ID));

        // Authorisation
        // Reports are sent from the moderator's email address so check it is set
        $moderator_email = filter_var(get_option(OPTION_MODERATOR_EMAIL, get_bloginfo('admin_email')), FILTER_VALIDATE_EMAIL);
        $auth_mgr = $this->_plugin->get_auth_mgr();
        $can_edit = $auth_mgr->current_user_can_edit_post($report_id);
        $can_send = $moderator_email && (!$issue_id || $auth_mgr->current_user_can_send_reports());

        $content = '';
        $images_html = '';
        if ($issue_id) {
            // Issue report
            $issue = get_post($issue_id);
            if ($issue) {
                $for_issue_str = esc_html__('For issue:', 'issues-map');
                $issue_title = esc_html($issue->post_title);
                $href = esc_url(get_permalink($issue_id));
                $content .= '<div class="im-form-section im-report-intro">' . $for_issue_str . ' <a href="' . $href . '">' . $issue_title . '</a></div>';

                // Get issue images html
                $include_images = get_option(OPTION_INCLUDE_IMAGES_IN_REPORTS, DEFAULT_INCLUDE_IMAGES_IN_REPORTS);
                if ($include_images) {
                    // Get the image content
                    $upload_url = $this->_plugin->get_upload_url();
                    $issue_data_mgr = $this->_plugin->get_issue_data_mgr();
                    $image_data = $issue_data_mgr->get_image_meta_data($issue_id);
                    $args = array(
                        'selectable' => false,
                        'hyperlink' => true,
                        'timestamp' => true,
                        'gps' => true,
                        'featured_image' => '',
                    );
                    require_once 'image-content.php';
                    $image_content_mgr = new ImageContentManager();
                    $images_html = $image_content_mgr->get_images_html($image_data, $upload_url, $args);
                    if ($images_html) {
                        $images_html = '<hr/>' . $images_html;
                    }
                }
            }
        } else {
            // Report template
            $intro = esc_html__('Template for issue categories:', 'issues-map');
            $terms = wp_get_post_terms($report_id, ISSUE_CATEGORY_TAXONOMY, array('fields' => 'names'));
            $cats = implode(', ', $terms);
            $content .= '<div class="im-form-section im-report-intro">' . $intro . ' <span class="im-bold">' . $cats . '</span></div>';
        }

        if ($can_send) {
            // Email section
            $email_str = esc_html__('Email:', 'issues-map');
            $to_str = esc_html__('To:', 'issues-map');
            $message_str = esc_html__('Message:', 'issues-map');
            $attachment_str = esc_html__("Attachment:", 'issues-map');
            $to_val = $recipient_name;
            if ($recipient_email) {
                $to_val .= ' &lt;' . $recipient_email . '&gt;';
            }
            $content .= '<h3>' . $email_str . '</h3><div class="im-email-view">';
            $content .= '<div><div class="im-email-label">' . $to_str . '</div><div class="im-email-header">' . $to_val . '&nbsp;</div></div>';
            $content .= '<div><div class="im-email-label">' . $message_str . '</div><div class="im-email-body">' . $email_body . '&nbsp;</div></div>';
            $content .= '</div>';
            $content .= '<h3>' . $attachment_str . '</h3>';
        }
        
        // Attachment section
        $ref_str = esc_html__('Ref:', 'issues-map');
        $content .= <<<EOS
            <div class='im-report-view'>
                <div class='im-form-section im-clear'>
                    <div class='im-report-field-rhs'>
                        {$to_address}
                    </div>
                </div>
                <div class='im-form-section im-clear'>
                    <div class='im-report-field-rhs'>
                        {$from_address}<br/>
                        {$from_email}
                    </div>
                </div>
                <div class='im-form-section im-clear'>
                    <div class='im-report-field-rhs'>
                        {$ref_str} {$ref}
                    </div>
                </div>
                <div class='im-form-section im-clear'>
                    <div class='im-salutation-line-section'>
                        {$greeting} {$addressee}
                    </div>
                </div>
                <div class='im-form-section im-body-section'>
                    {$body}
                </div>
                <div class='im-form-section im-sign-off-section'>
                    {$sign_off}
                </div>
                <div class='im-form-section im-added-by-section'>
                    {$added_by}<br/>
                    {$date}
                </div>
                {$images_html}    
            </div>            
EOS;

        // Info message
        $message_text = $date_sent ? sprintf(__('This report was sent on %s.', 'issues-map'), $date_sent) : '';
        $content .= '<p id="im-message" class="im-message">' . $message_text . '</p>';

        // Buttons
        if ($issue_id) {
            if ($can_edit) {
                if ($can_send) {
                    $send_str = esc_html__('Send report', 'issues-map');
                    $content .= '<input id="im-send-report-button" class="button im-button" type="button" value="' . $send_str . '"></input>';
                }

                $download_pdf_str = esc_html__('Download as PDF', 'issues-map');
                $content .= '<input id="im-download-pdf-button" class="button im-button" type="button" value="' . $download_pdf_str . '"></input>';
            }

            $back_str = esc_html__('Back to issue', 'issues-map');
            $issue_href = esc_url(get_permalink($issue_id));
            $content .= '<a id="im-back-link" href="' . $issue_href . '" class="button im-button im-secondary-button">' . $back_str . '</a>';
        }

        // Editing links
        if ($can_edit) {
            $content .= '<div class="im-form-section">';
            $edit_str = $issue_id ? esc_html__('Edit report', 'issues-map') : esc_html__('Edit report template', 'issues-map');
            $edit_title = $issue_id ? esc_attr__('Edit this report', 'issues-map') : esc_attr__('Edit this report template', 'issues-map');
            $edit_href = $issue_id ? esc_url(add_query_arg('view', EDIT_REPORT_VIEW, get_permalink($report_id))) : get_edit_post_link($report_id);
            $content .= '<p><a href="' . $edit_href . '" title="' . $edit_title . '" class="im-edit-link">' . $edit_str . '</a></p>';

            if ($issue_id) {
                $delete_str = esc_html__('Delete report', 'issues-map');
                $delete_title = esc_attr__('Delete this report', 'issues-map');
                $content .= '<p><a id="im-delete-report-link" class="im-edit-link" href="#" title="' . $delete_title . '">' . $delete_str . '</a></p>';
            }
        }

        // Hidden elements
        $wpnonce = wp_create_nonce('im-issue-or-report');
        $content .= '<input type="hidden" id="im-issue-or-report-nonce" name="im-issue-or-report-nonce" value="' . $wpnonce . '"></input>';
        $content .= '<div id="im-confirm-dialog"></div>';

        return $content;
    }

    /*
     * Get the form for adding / editing a report template.
     */

    public function get_edit_report_template_form_content($template_id) {

        $post = null;
        if ($template_id !== 0) {
            // Editing report template
            $auth_mgr = $this->_plugin->get_auth_mgr();
            if ($auth_mgr->current_user_can_edit_post($template_id)) {
                $post = get_post($template_id);
            } else {
                return esc_html__('You are not authorised to edit this report.', 'issues-map');
            }
        }

        $moderator_email = filter_var(get_option(OPTION_MODERATOR_EMAIL, get_bloginfo('admin_email')), FILTER_VALIDATE_EMAIL);
        $report_sending_enabled = !!$moderator_email;
        $warning = esc_html__('Please do not include personal information in report templates. Use the suggested {...} tags instead.', 'issues-map');
        $content = '<div class="im-form-inner">';
        $content .= '<p class="im-message"><span class="dashicons dashicons-info-outline"></span> ' . $warning . '</p>';
        $fields = $this->get_report_field_values($post, 0, false, true);
        $content .= $this->get_edit_report_body($fields, $report_sending_enabled);
        $content .= '</div>';

        return $content;
    }

    /*
     * Get the form for adding / editing a report.
     */

    public function get_edit_report_form($id, $view) {

        $expand_placeholders = false;
        $template_id = 0;
        $post = null;
        $auth_mgr = $this->_plugin->get_auth_mgr();
        $moderator_email = filter_var(get_option(OPTION_MODERATOR_EMAIL, get_bloginfo('admin_email')), FILTER_VALIDATE_EMAIL);
        $can_send = $moderator_email && $auth_mgr->current_user_can_send_reports();

        if ($view === ADD_REPORT_VIEW) {
            // Adding report for issue
            $report_id = 0;
            $issue_id = $id;
            $expand_placeholders = true;
            if ($auth_mgr->current_user_can_edit_post($issue_id)) {
                $terms = wp_get_post_terms($issue_id, ISSUE_CATEGORY_TAXONOMY, array('fields' => 'ids'));
                $cat_id = isset($terms[0]) ? $terms[0] : 0;
                // Get the template to base the report on (if there is one)
                $template_id = $this->get_report_template($cat_id);
                if ($template_id) {
                    $post = get_post($template_id);
                }
            } else {
                return esc_html__('You are not authorised to create a report for this issue.', 'issues-map');
            }
        } else {
            // Editing report
            $report_id = $id;
            if ($auth_mgr->current_user_can_edit_post($report_id)) {
                $post = get_post($report_id);
                if ($post) {
                    $issue_id = get_post_meta($report_id, META_ISSUE_ID, true);
                } else {
                    return esc_html__('Invalid report ID.', 'issues-map');
                }
            } else {
                return esc_html__('You are not authorised to edit this report.', 'issues-map');
            }
        }

        if (!$issue_id) {
            return esc_html__('Invalid issue ID.', 'issues-map');
        }

        $issue_title = '';
        $issue = get_post($issue_id);
        if ($issue) {
            $issue_title = esc_html($issue->post_title);
        }
        $for_issue_str = esc_html__('For issue:', 'issues-map');
        $intro_str = esc_html__('Enter the details for the issue report.', 'issues-map');
        $cancel_str = esc_attr__('Cancel', 'issues-map');
        $ok_str = esc_attr__('OK', 'issues-map');
        if ($can_send) {
            $intro_str .= ' ' . esc_html__('The report will not be sent at this stage.', 'issues-map');
        }
        $warning = esc_html__('Other users can see who has created a report for an issue. However, report contents are only visible to the person who created them and to our site moderators.', 'issues-map');
        $href = esc_url(get_permalink($issue_id));
        $fields = $this->get_report_field_values($post, $issue_id, $expand_placeholders, false);
        $report_body = $this->get_edit_report_body($fields, $can_send);

        $content = <<<EOS
        <form action="" id="im-edit-report-form" class="im-form" method="post">
            <div class="im-form-inner">
                <div class="im-form-section im-report-intro">
                    $for_issue_str <a href="$href">$issue_title</a>
                </div>
                <p class="im-intro">$intro_str</p>
                <p class="im-message"><span class="dashicons dashicons-info-outline"></span> $warning</p>
                $report_body
                <div class='im-form-section'>
                    <p id='im-message' class='im-message'></p>
                    <input type='hidden' id='im-report-template-id' name='im-report-template-id' value='{$template_id}'></input>
                    <input id='im-ok-button' class='im-button' type='submit' value='{$ok_str}'>
                    <input id='im-back-button' class='im-button im-back-button' type='button' value='{$cancel_str}'>
                </div>
            </div>
        </form>
EOS;

        return $content;
    }

    /*
     * Get the data to populate a report with.
     */

    private function get_report_field_values($post, $issue_id, $expand_placeholders, $is_template) {
        $fields = array();
        $fields['recipient_name'] = '';
        $fields['recipient_email'] = '';
        $fields['email_body'] = '';
        $fields['from_address'] = '';
        $fields['from_email'] = '';
        $fields['from_address'] = '';
        $fields['to_address'] = '';
        $fields['greeting'] = '';
        $fields['addressee'] = '';
        $fields['body'] = '';
        $fields['sign_off'] = '';
        $fields['added_by'] = '';
        $fields['from_email_help'] = '';
        $fields['message_help'] = '';
        $fields['body_help'] = '';
        $fields['added_by_help'] = '';

        $from_email_placeholders = array('{user_email}');
        $body_placeholders = array('{user_display_name}', '{user_full_name}', '{user_email}', '{date_today}', '{issue_title}', '{issue_description}', '{issue_added_by}', '{issue_added_date}', '{issue_added_time}', '{issue_updated_date}', '{issue_updated_time}', '{issue_lat}', '{issue_lng}');
        if (!DEMO_VERSION) {
            $body_placeholders[] = '{issue_link}';
        }
        $added_by_placeholders = array('{user_display_name}', '{user_full_name}');
        if ($post) {
            $report_meta = get_post_meta($post->ID, '', false);
            $fields['recipient_name'] = esc_html(WPUtils::get_meta_val($report_meta, META_RECIPIENT_NAME));
            $fields['recipient_email'] = esc_html(WPUtils::get_meta_val($report_meta, META_RECIPIENT_EMAIL));
            $fields['email_body'] = WPUtils::get_meta_val($report_meta, META_EMAIL_BODY);
            if ($expand_placeholders) {
                $fields['email_body'] = WPUtils::expand_placeholders($fields['email_body'], $issue_id, $body_placeholders);
            }
            $fields['email_body'] = esc_textarea($fields['email_body']);
            $fields['from_address'] = esc_textarea(WPUtils::get_meta_val($report_meta, META_FROM_ADDRESS));
            $fields['from_email'] = WPUtils::get_meta_val($report_meta, META_FROM_EMAIL);
            if ($expand_placeholders) {
                $fields['from_email'] = WPUtils::expand_placeholders($fields['from_email'], $issue_id, $from_email_placeholders);
            }
            $fields['from_email'] = esc_html($fields['from_email']);
            $fields['to_address'] = esc_textarea(WPUtils::get_meta_val($report_meta, META_TO_ADDRESS));
            $fields['greeting'] = esc_html(WPUtils::get_meta_val($report_meta, META_GREETING));
            $fields['addressee'] = esc_html(WPUtils::get_meta_val($report_meta, META_ADDRESSEE));
            $fields['body'] = $post->post_content;
            if ($expand_placeholders) {
                $fields['body'] = WPUtils::expand_placeholders($fields['body'], $issue_id, $body_placeholders);
            }
            $fields['body'] = esc_textarea($fields['body']);
            $fields['sign_off'] = esc_html(WPUtils::get_meta_val($report_meta, META_SIGN_OFF));
            $fields['added_by'] = WPUtils::get_meta_val($report_meta, META_ADDED_BY);
            if ($expand_placeholders) {
                $fields['added_by'] = WPUtils::expand_placeholders($fields['added_by'], $issue_id, $added_by_placeholders);
            }
            $fields['added_by'] = esc_html($fields['added_by']);
        }

        if ($is_template) {
            // Create help captions to explain which tags can be used in report templates
            $fields['from_email_help'] = $this->get_placeholder_help($from_email_placeholders, false);
            $fields['message_help'] = $this->get_placeholder_help($body_placeholders, false);
            $fields['body_help'] = $this->get_placeholder_help($body_placeholders, true);
            $fields['added_by_help'] = $this->get_placeholder_help($added_by_placeholders, false);
        }

        return $fields;
    }

    /*
     * Get the main body that is the same for the report and report template editing views.
     */

    private function get_edit_report_body($fields, $include_email_section) {

        $max_len_64 = MAX_LEN_64;
        $max_len_256 = MAX_LEN_256;
        $max_len_1024 = MAX_LEN_1024;
        $max_body_len = MAX_REPORT_LEN;
        $from_address_str = esc_html__('Your address', 'issues-map');
        $from_email_str = esc_html__('Your email', 'issues-map');
        $to_address_str = esc_html__('To address', 'issues-map');
        $greeting_str = esc_html__('Greeting', 'issues-map');
        $body_str = esc_html__('Report content', 'issues-map');
        $sign_off_str = esc_html__('Sign off', 'issues-map');
        $added_by_str = esc_html__('Your name', 'issues-map');
        $email_str = esc_html__('Email:', 'issues-map');
        $recipient_name_str = esc_html__('Recipient name', 'issues-map');
        $recipient_email_str = esc_html__('Recipient email', 'issues-map');
        $message_str = esc_html__('Message', 'issues-map');
        $attachment_str = esc_html__('Attachment:', 'issues-map');

        $wpnonce = wp_create_nonce('im-edit-report');
        $content = '';

        // Email section
        if ($include_email_section) {
            $content .= <<<EOS
                <h3>{$email_str}</h3>
                <div class='im-edit-email-view'>
                    <div class='im-form-section im-recipient-name-section'>
                        <label for='im-report-recipient-name'>{$recipient_name_str}</label>
                        <input type='text' id='im-report-recipient-name' name='im-report-recipient-name' class='im-text' maxlength='{$max_len_64}' value='{$fields['recipient_name']}'></input>
                    </div>
                    <div class='im-form-section im-recipient-email-section'>
                        <label for='im-report-recipient-email'>{$recipient_email_str}</label>
                        <input type='text' id='im-report-recipient-email' name='im-report-recipient-email' class='im-text' maxlength='{$max_len_64}' value='{$fields['recipient_email']}'></input>
                    </div>
                    <div class='im-form-section im-email-body-section'>
                        <label for='im-report-email-body'>{$message_str}</label>
                        <textarea id='im-report-email-body' name='im-report-email-body' class='im-textarea' rows='8' cols='50' maxlength='{$max_len_1024}'>{$fields['email_body']}</textarea>
                        {$fields['message_help']}
                    </div>
                </div>
                <h3>{$attachment_str}</h3>
EOS;
        }

        $content .= <<<EOS
            <div class='im-edit-report-view'>
                <div class='im-form-section im-clear'>
                    <div class='im-report-field-rhs'>
                        <label for='im-report-to-address'>{$to_address_str}</label>
                        <textarea id='im-report-to-address' name='im-report-to-address' rows='4' cols='25' maxlength='{$max_len_256}'>{$fields['to_address']}</textarea>
                    </div>
                </div>
                <div class='im-form-section im-clear'>
                    <div class='im-report-field-rhs'>
                        <label for='im-report-from-address'>{$from_address_str}</label>
                        <textarea id='im-report-from-address' name='im-report-from-address' rows='4' cols='25' maxlength='{$max_len_256}'>{$fields['from_address']}</textarea>
                    </div>
                </div>
                <div class='im-form-section im-clear'>
                    <div class='im-report-field-rhs'>
                        <label for='im-report-from-email'>{$from_email_str}</label>
                        <input type='text' id='im-report-from-email' name='im-report-from-email' class='im-text' maxlength='{$max_len_64}' value='{$fields['from_email']}'></input>
                        {$fields['from_email_help']}
                    </div>                        
                </div>
                <div class='im-form-section im-clear'>
                    <div class='im-greeting-section'>
                        <label for='im-report-greeting'>{$greeting_str}</label>
                        <input type='text' id='im-report-greeting' name='im-report-greeting' class='im-text' maxlength='{$max_len_64}' value='{$fields['greeting']}'></input>
                    </div>
                    <div class='im-addressee-section'>
                        <label for='im-report-addressee'>{$recipient_name_str}</label>
                        <input type='text' id='im-report-addressee' name='im-report-addressee' class='im-text' maxlength='{$max_len_64}' value='{$fields['addressee']}'></input>
                    </div>
                </div>
                <div class='im-form-section im-body-section'>
                    <label for='im-report-body'>{$body_str}</label>
                    <textarea id='im-report-body' name='im-report-body' class='im-textarea' rows='8' cols='50' maxlength='{$max_body_len}'>{$fields['body']}</textarea>
                    {$fields['body_help']}
                </div>
                <div class='im-form-section im-sign-off-section'>
                    <label for='im-report-sign-off'>{$sign_off_str}</label>
                    <input type='text' id='im-report-sign-off' name='im-report-sign-off' class='im-text' maxlength='{$max_len_64}' value='{$fields['sign_off']}'></input>
                </div>
                <div class='im-form-section im-added-by-section'>
                    <label for='im-report-added-by'>{$added_by_str}</label>
                    <input type='text' id='im-report-added-by' name='im-report-added-by' class='im-text' maxlength='{$max_len_64}' value='{$fields['added_by']}'></input>
                    {$fields['added_by_help']}
                </div>
            </div>
            <input type='hidden' id='im-edit-report-nonce' name='im-edit-report-nonce' value='{$wpnonce}'></input>
EOS;
        return $content;
    }

    /*
     * Get the help captions to display beneath report fields.
     */

    private function get_placeholder_help($placeholders, $allow_html = false) {
        $help = '<div class="im-field-help">';
        $help .= esc_html__('You can include ', 'issues-map');
        foreach ($placeholders as $placeholder) {
            $help .= ' ' . $placeholder;
        }
        if ($allow_html) {
             $help .= esc_html__(' and the HTML tags &lt;b&gt; &lt;i&gt; &lt;u&gt; and &lt;a&gt;', 'issues-map');
        }
        $help .= '</div>';
        return $help;
    }

    /*
     * Get the ID of the report template to use for the specified issue category.
     */

    private function get_report_template($issue_category_id) {
        $template_id = 0;
        $args = array(
            'post_type' => REPORT_TEMPLATE_POST_TYPE,
            'post_status' => 'publish',
            'tax_query' => array(
                'category_clause' => array(
                    'taxonomy' => ISSUE_CATEGORY_TAXONOMY,
                    'field' => 'term_id',
                    'terms' => $issue_category_id,
                ),
            ),
        );

        $query_posts = new \WP_Query($args);
        if ($query_posts->have_posts()) {
            $query_posts->the_post();
            $post = get_post();
            $template_id = $post->ID;
        }
        wp_reset_postdata();

        return $template_id;
    }

}
