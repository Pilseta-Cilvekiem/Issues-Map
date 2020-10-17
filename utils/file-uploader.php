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

require_once 'wp-utils.php';

/**
 * Implements drag and drop image uploading.
 * This module is closely based on the Drag & Drop Multiple File Upload Cf7 plugin by codedropz.com.
 */
class FileUploader {

    private $_upload_dir;

    /**
     * Constructor
     */
    public function __construct($upload_dir) {
        $this->_upload_dir = $upload_dir;
    }

    /*
     * Get the html for a drag and drop upload control.
     */

    public function do_file_uploader_control() {
        $supported_image_types = esc_attr(SUPPORTED_IMAGE_TYPES);
        $max_image_file_size = esc_attr(MAX_IMAGE_FILE_SIZE);

        $this->enqueue_file_uploader_resources();
        $content = <<<EOS
        <span class="wpcf7-form-control-wrap im-file-uploader">
            <input type="file" size="40" class="wpcf7-drag-n-drop-file d-none im-file-uploader" id="im-file-uploader" aria-invalid="false" multiple="multiple" data-name="im-file-uploader" data-type="{$supported_image_types}" data-limit="{$max_image_file_size}" />
        </span>
EOS;

        return $content;
    }

    /*
     * Load JS and CSS resources required by file uploader control.
     */

    private function enqueue_file_uploader_resources() {
        wp_enqueue_style('dnd-upload-cf7',
                plugins_url('../css/dnd-upload-cf7.css', __FILE__));

        wp_enqueue_script('codedropz-uploader',
                plugins_url('../js/codedropz-uploader-min.js', __FILE__),
                array('jquery'));

        wp_enqueue_script('dnd-upload-cf7', plugins_url('../js/dnd-upload-cf7.js', __FILE__), array('jquery', 'codedropz-uploader'));

        //  registered script with data for a JavaScript variable.
        wp_localize_script('dnd-upload-cf7', 'dnd_cf7_uploader',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'ajax_nonce' => wp_create_nonce("dnd-cf7-security-nonce"),
                    'drag_n_drop_upload' => array(
                        'text' => esc_html__('Drag & Drop Files Here', 'issues-map'),
                        'or_separator' => esc_html__('or', 'issues-map'),
                        'browse' => esc_html__('Browse Files', 'issues-map'),
                        'server_max_error' => $this->dnd_cf7_error_msg('server_limit'),
                        'large_file' => $this->dnd_cf7_error_msg('large_file'),
                        'inavalid_type' => $this->dnd_cf7_error_msg('invalid_type'),
                        'max_file_limit' => $this->dnd_cf7_error_msg('max_file_limit'),
                        'required' => $this->dnd_cf7_error_msg('required'),
                        'delete' => array(
                            'text' => esc_html__('deleting', 'issues-map'),
                            'title' => esc_html__('Remove', 'issues-map')
                        )
                    )
                )
        );
    }

    /**
     * Handle uploaded file.
     */
    public function file_uploaded_async() {

        // check and verify ajax request
        //if (is_user_logged_in()) {
        check_ajax_referer('dnd-cf7-security-nonce', 'security');
        //}
        // input type file 'name'
        $name = 'upload-file';

        // Get File ( name, type, tmp_name, size, error )
        $file = isset($_FILES[$name]) ? $_FILES[$name] : null;

        // Tells whether the file was uploaded via HTTP POST
        if (!is_uploaded_file($file['tmp_name'])) {
            $failed_error = esc_html__('Failed to upload file.', 'issues-map');
            wp_send_json_error('(' . $file['error'] . ') ' . $failed_error);
        }

        // File type validation
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $supported_types = explode('|', SUPPORTED_IMAGE_TYPES);
        $success = false;
        foreach ($supported_types as $supported_type) {
            if ($supported_type === $extension) {
                $success = true;
                break;
            }
        }

        // validate file type
        if (!$success) {
            wp_send_json_error($this->dnd_cf7_error_msg('invalid_type'));
        }

        // validate file size limit
        if ($file['size'] > MAX_IMAGE_FILE_SIZE) {
            wp_send_json_error($this->dnd_cf7_error_msg('large_file'));
        }

        // Create file name
        $dir = $this->_upload_dir;
        $filename = 'tmp-' . time() . '.' . $extension;
        $filename = wp_unique_filename($dir, $filename);
        $new_file = path_join($dir, $filename);

        // Upload File
        if (false === move_uploaded_file($file['tmp_name'], $new_file)) {
            wp_send_json_error($this->dnd_cf7_error_msg('failed_upload'));
        } else {

            $files = array(
                'path' => basename($dir),
                'file' => $filename
            );

            // Change file permission to 0400
            chmod($new_file, 0644);

            wp_send_json_success($files);
        }

        die;
    }

    /**
     * Delete uploaded file.
     */
    public function upload_deleted_async() {

        // check and verify ajax request
        //if (is_user_logged_in()) {
        check_ajax_referer('dnd-cf7-security-nonce', 'security');
        //}
        // Sanitize Path
        $success = false;
        $path = sanitize_text_field(filter_input(INPUT_POST, 'path', FILTER_DEFAULT));
        if ($path) {

            $filename = str_replace(IMAGES_FOLDER_NAME . '/', '', $path);
            if (preg_match("/^tmp-[0-9-]+\\.(" . SUPPORTED_IMAGE_TYPES . ")$/i", $filename)) {

                $file_path = path_join($this->_upload_dir, $filename);

                // Check if file exists
                if (file_exists($file_path)) {
                    wp_delete_file($file_path);
                    $success = true;
                }
            }
        }

        $response = array('success' => $success);
        WPUtils::send_json_response($response);
    }

    /*
     * File uploader error messages.
     */

    private function dnd_cf7_error_msg($error_key) {

        // Array of default error message
        $errors = array(
            'server_limit' => esc_html__('The uploaded file exceeds the maximum upload size of your server.', 'issues-map'),
            'failed_upload' => esc_html__('File upload failed.', 'issues-map'),
            'large_file' => esc_html__('Uploaded file is too large.', 'issues-map'),
            'invalid_type' => esc_html__('Uploaded file has an unsupported file type.', 'issues-map'),
            'max_file_limit' => esc_html__('The maximum number of files allowed was exceeded.', 'issues-map'),
            'required' => esc_html__('This field is required.', 'issues-map'),
        );

        // return error message based on $error_key request
        if (isset($errors[$error_key])) {
            return $errors[$error_key];
        }

        return false;
    }

}
