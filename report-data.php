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
 * Manages data for issue reports and report templates.
 */
class ReportDataManager {
    
    private $_plugin;

    public function __construct($plugin) {
        $this->_plugin = $plugin;
    }

    /*
     * Add an issue report.
     */
    public function add_report(
                    $user_id,
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
                    $date) {
        // Create custom post
        $post_type = $issue_id === 0 ? REPORT_TEMPLATE_POST_TYPE : REPORT_POST_TYPE;
        $post_status = 'publish';   // 'draft';
        $report_id = wp_insert_post(array(
            'post_type' => $post_type,
            'post_title' => $post_type, // Ensures sensible slug value
            'post_content' => $body,
            'post_status' => $post_status,
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'meta_input' => array(
                META_USER_ID => $user_id,
                META_ISSUE_ID => $issue_id,
                META_TEMPLATE_ID => $template_id,
                META_RECIPIENT_NAME => $recipient_name,
                META_RECIPIENT_EMAIL => $recipient_email,
                META_EMAIL_BODY => $email_body,
                META_TO_ADDRESS => $to_address,
                META_FROM_ADDRESS => $from_address,
                META_FROM_EMAIL => $from_email,
                META_GREETING => $greeting,
                META_ADDRESSEE => $addressee,
                META_SIGN_OFF => $sign_off,
                META_ADDED_BY => $added_by,
                META_DATE => $date,
            ),
                )
        );

        if ($report_id && $issue_id) {
            // Update the title and reference number
            $next_num = $this->get_next_report_num($issue_id);
            $ref = $issue_id . '-' . $next_num;
            $salt = dechex(rand());
            $report_title = sanitize_text_field(__('Issue report', 'issues-map') . ' ' . $ref);
            $report_id = wp_update_post(array(
                'ID' => $report_id,
                'post_title' => $report_title,
                'meta_input' => array(META_REF => $ref, META_SALT => $salt),
            ));
        }

        return $report_id;
    }
    
    /*
     * Get the next unused report number for an issue.
     */
    public function get_next_report_num($issue_id) {
        $num = 0;
        
        $args = array(
            'post_type' => REPORT_POST_TYPE,
            'post_status' => 'publish',
            'meta_query' => array(
                'issue_clause' => array(
                    'key' => META_ISSUE_ID,
                    'value' => $issue_id,
                ),
            ),
        );

        $query_posts = new \WP_Query($args);
        if ($query_posts->have_posts()) {
            while ($query_posts->have_posts()) {
                $query_posts->the_post();
                $ref = get_post_meta(get_the_ID(), META_REF, true);
                $ref = str_replace($issue_id . '-', '', $ref);
                $num = max($num, $ref);
            }
        }
        wp_reset_postdata();

        return $num + 1;
    }
    
    /*
     * Update an issue report.
     */
    public function update_report(
                    $report_id,
                    $user_id,
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
                    $date) {
        $post_data = array(
            'ID' => $report_id,
            'post_content' => $body,
            'meta_input' => array(
                META_USER_ID => $user_id,
                META_ISSUE_ID => $issue_id,
                META_TEMPLATE_ID => $template_id,
                META_RECIPIENT_NAME => $recipient_name,
                META_RECIPIENT_EMAIL => $recipient_email,
                META_EMAIL_BODY => $email_body,
                META_TO_ADDRESS => $to_address,
                META_FROM_ADDRESS => $from_address,
                META_FROM_EMAIL => $from_email,
                META_GREETING => $greeting,
                META_ADDRESSEE => $addressee,
                META_SIGN_OFF => $sign_off,
                META_ADDED_BY => $added_by,
                META_DATE => $date,
            ),
        );
        
        return wp_update_post($post_data);
    }
    
    /*
     * Delete all the reports for an issue.
     */
    public function delete_reports_for_issue($issue_id) {        
        $args = array(
            'post_type' => REPORT_POST_TYPE,
            'meta_query' => array(
                'issue_clause' => array(
                    'key' => META_ISSUE_ID,
                    'value' => $issue_id,
                ),
            ),
        );

        $query_posts = new \WP_Query($args);
        if ($query_posts->have_posts()) {
            while ($query_posts->have_posts()) {
                $query_posts->the_post();
                $report_id = get_the_ID();
                $this->delete_report($report_id);
            }
        }
        wp_reset_postdata();
    }
    
    /*
     * Delete an issue report.
     */
    public function delete_report($report_id) {
        $success = false;
        // Check it's a report post
        if (REPORT_POST_TYPE === get_post_type($report_id)) {
            $this->delete_pdf($report_id);
            wp_delete_post($report_id);
            $success = true;
        }
        return $success;
    }
    
    /*
     * Delete the PDF file for a report.
     */
    public function delete_pdf($report_id) {
        $filename = '';
        $issue_id = get_post_meta($report_id, META_ISSUE_ID, true);
        if ($issue_id) {
            $ref = get_post_meta($report_id, META_REF, true);
            $salt = get_post_meta($report_id, META_SALT, true);
            $filename = $ref . '-' . $salt . '.pdf';
            if (preg_match("/^[0-9a-z-]+\\.pdf$/i", $filename)) {    // Defensive check
                $dir = $this->_plugin->get_upload_dir();
                $filepath = path_join($dir, $filename);
                if (file_exists($filepath)) {
                    unlink($filepath);
                }        
            }
        }
    }
}
