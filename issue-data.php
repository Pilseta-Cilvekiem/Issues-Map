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
 * Handles data relating to issues and issue categories, statuses and images.
 */
class IssueDataManager {

    private $_plugin;

    public function __construct($plugin) {
        $this->_plugin = $plugin;
    }

    /*
     * Add an issue. 
     */
    public function add_issue(
            $user_id,
            $issue_category_id,            
            $issue_status_id,
            $issue_title,
            $description,
            $added_by,
            $email_address,
            $latitude,
            $longitude) {

        // Create custom post
        $post_status = 'publish';   // 'draft';
        $issue_id = wp_insert_post(array(
            'post_type' => ISSUE_POST_TYPE,
            'post_title' => ISSUE_POST_TYPE, // Ensures sensible slug value
            'post_content' => $description,
            'post_status' => $post_status,
            'comment_status' => 'open',
            'ping_status' => 'closed',
//            'tax_input' => array(
//                ISSUE_CATEGORY_TAXONOMY => array($issue_category_id),
//                ISSUE_STATUS_TAXONOMY => array($issue_status_id),
//            ),
            'meta_input' => array(
                META_USER_ID => $user_id,
                META_ADDED_BY => $added_by,
                META_EMAIL_ADDRESS => $email_address,
                META_LATITUDE => $latitude,
                META_LONGITUDE => $longitude,
            ),
                )
        );

        if ($issue_id) {
            // Set category and status
            // Note: Doing this using 'tax_input' field in the wp_insert_post() call fails for anonymous users.
            wp_set_object_terms( $issue_id, array($issue_category_id), ISSUE_CATEGORY_TAXONOMY );
            wp_set_object_terms( $issue_id, array($issue_status_id), ISSUE_STATUS_TAXONOMY );
            
            // Update the title
            $issue_id = wp_update_post(array(
                'ID' => $issue_id,
                'post_title' => $issue_title,
            ));
        }

        return $issue_id;
    }
    
    /*
     * Delete an issue.
     */
    public function delete_issue($issue_id) {
        $success = false;
        // Check it's an issue post
        if (ISSUE_POST_TYPE === get_post_type($issue_id)) {
            wp_delete_post($issue_id);
            $success = true;
        }
        
        return $success;
    }

    /*
     * Update issue details.
     */
    public function update_issue_details(
            $issue_id,
            $issue_category_id,
            $issue_status_id,
            $issue_title,
            $description,
            $added_by,
            $email_address  // If null, will not be updated
    ) {

        // Update custom post
        $post_data = array(
            'ID' => $issue_id,
            'post_title' => $issue_title,
            'post_content' => $description,
//            'tax_input' => array(
//                ISSUE_CATEGORY_TAXONOMY => array($issue_category_id),
//                ISSUE_STATUS_TAXONOMY => array($issue_status_id),
//            ),
            'meta_input' => array(
                META_ADDED_BY => $added_by,
            ),
        );

        if ($email_address !== null) {
            $post_data['meta_input']['email_address'] = $email_address;
        }

        $result = wp_update_post($post_data);
        if ($result) {        
            // Update category and status
            // Note: Doing this using 'tax_input' field in the wp_update_post() call fails for anonymous users.
            wp_set_object_terms( $issue_id, array($issue_category_id), ISSUE_CATEGORY_TAXONOMY );
            wp_set_object_terms( $issue_id, array($issue_status_id), ISSUE_STATUS_TAXONOMY );
        }
        
        return $result;
    }
    
    /*
     * Update issue location.
     */
    public function update_issue_location($issue_id, $latitude, $longitude) {
        // Update custom post
        $post_status = 'publish';   // 'draft';
        $post_data = array(
            'ID' => $issue_id,
            'post_status' => $post_status,
            'meta_input' => array(
                META_LATITUDE => $latitude,
                META_LONGITUDE => $longitude,
            ),
        );

        return wp_update_post($post_data);
    }

    /*
     * Update the meta data for an issue which describes its images.
     */
    public function update_image_meta_data($issue_id) {
        // Get the uploaded image files for this issue
        $dir = $this->_plugin->get_upload_dir();
        $regex = '/^' . $issue_id . '-[0-9-]+\\.(' . SUPPORTED_IMAGE_TYPES . ')$/';
        $filenames = Utils::get_files_in_dir($regex, $dir);

        // See if the issue location needs to be set
        $lat = get_post_meta($issue_id, META_LATITUDE, true);
        $lng = get_post_meta($issue_id, META_LONGITUDE, true);
        $default_lat = get_option(OPTION_CENTRE_LAT, DEFAULT_CENTRE_LAT);
        $default_lng = get_option(OPTION_CENTRE_LNG, DEFAULT_CENTRE_LNG);
        $init_location = $lat == $default_lat && $lng == $default_lng;

        // See if the featured image has been set
        $featured_image = $this->get_featured_image($issue_id);

        $image_data = array();
        require_once 'utils/image-utils.php';
        foreach ($filenames as $filename) {
            // Extract image meta data
            $filepath = path_join($dir, $filename);
            $meta_data = ImageUtils::get_image_meta_data($filepath);
            $image_data[] = $meta_data;

            // Initialise the issue location if not set
            if ($init_location && ($meta_data[META_LATITUDE] !== 0.0 || $meta_data[META_LONGITUDE] !== 0.0)) {
                $this->update_issue_location($issue_id, $meta_data[META_LATITUDE], $meta_data[META_LONGITUDE]);
                $init_location = false;
            }

            // Initialise the featured image if not set
            if (!$featured_image) {
                $featured_image = $meta_data[META_FILENAME];
                $this->update_featured_image($issue_id, $featured_image);
            }
        }
        
        // Store meta data
        $json = json_encode($image_data);
        WPUtils::update_post_meta($issue_id, META_IMAGE_DATA, $json);

        return $image_data;
    }

    /* 
     * Add images to an issue.
     */
    public function add_issue_images($issue_id, $image_list) {
        require_once 'utils/image-utils.php';
        $success = true;
        if ($image_list) {
            $dir = $this->_plugin->get_upload_dir();
            foreach ($image_list as $image_input) {
                // Check filename is safe
                $src_path = Utils::cap_str_len(sanitize_text_field($image_input), MAX_LEN_64);
                $src_filename = str_replace(IMAGES_FOLDER_NAME . '/', '', $src_path);
                if (preg_match("/^tmp-[0-9-]+\\.(" . SUPPORTED_IMAGE_TYPES . ")$/i", $src_filename)) {
                    $src_path = path_join($dir, $src_filename);
                    if (file_exists($src_path)) {
                        // Rename tmp file
                        $dest_filename = str_replace('tmp', $issue_id, $src_filename);
                        $dest_path = path_join($dir, wp_unique_filename($dir, $dest_filename));
                        $success &= rename($src_path, $dest_path);
                        // Create thumbnail
                        $success &= ImageUtils::create_thumbnail($dest_path, THUMBNAIL_WIDTH, true);
                    }
                }
            }
        }
        return $success;
    }

    /*
     * Delete issue images and thumbnails for an issue.
     */
    public function delete_images_for_issue($issue_id) {
        $dir = $this->_plugin->get_upload_dir();
        $regex = '/^' . $issue_id . '-[0-9-]+(-thumb)?\\.(' . SUPPORTED_IMAGE_TYPES . ')$/';
        $filenames = Utils::get_files_in_dir($regex, $dir);
        foreach ($filenames as $filename) {
            $filepath = path_join($dir, $filename);
            unlink($filepath);
        }
        $this->update_featured_image($issue_id, '');
    }

    /* 
     * Delete an issue image and its thumbnail.
     */
    public function delete_issue_image($issue_id, $filename) {
        $success = false;
        // Check the file belongs to the issue
        if (preg_match("/^{$issue_id}-[0-9-]+\\.(" . SUPPORTED_IMAGE_TYPES . ")$/i", $filename)) {
            $dir = $this->_plugin->get_upload_dir();
            $name = preg_replace('/\.(' . SUPPORTED_IMAGE_TYPES . ')/i', '', $filename);
            // Note: we will delete the image and its thumbnail
            $regex = '/^' . $name . '(-thumb)?\\.(' . SUPPORTED_IMAGE_TYPES . ')$/i';
            $fnames = Utils::get_files_in_dir($regex, $dir);
            foreach ($fnames as $f) {
                $path = path_join($dir, $f);
                unlink($path);
                $success = true;
            }

            // Update image data
            $image_data = $this->update_image_meta_data($issue_id);

            // Attempt to set the featured image if not set
            $featured_image = get_post_meta($issue_id, META_FEATURED_IMAGE, true);
            if ($featured_image === $filename) {
                $featured_image = '';
                if (count($image_data) !== 0) {
                    $featured_image = $image_data[0][META_FILENAME];
                }
                $this->update_featured_image($issue_id, $featured_image);
            }
        }

        return $success;
    }
    
    /*
     * Delete temporary images uploaded in the Add images view.
     */
    public function delete_tmp_images($image_list) {
        $dir = $this->_plugin->get_upload_dir();
        foreach ($image_list as $image_input) {
            // Check filename is safe
            $src_path = Utils::cap_str_len(sanitize_text_field($image_input), MAX_LEN_64);
            $src_filename = str_replace(IMAGES_FOLDER_NAME . '/', '', $src_path);
            if (preg_match("/^tmp-[0-9-]+\\.(" . SUPPORTED_IMAGE_TYPES . ")$/i", $src_filename)) {
                $src_path = path_join($dir, $src_filename);
                if (file_exists($src_path)) {
                    unlink($src_path);
                }
            }
        }
    }
    
    /**
     * Delete any tmp image files that have been orphaned as a result of
      a user uploading them but not clicking OK in the Add images view.
     */
    public function delete_orphaned_image_files() {
        $dir = $this->_plugin->get_upload_dir();
        $regex = '/^tmp-[0-9-]+\\.(' . SUPPORTED_IMAGE_TYPES . ')$/';
        $filenames = Utils::get_files_in_dir($regex, $dir);
        $max_age = 3600;   // 1 hour
        foreach ($filenames as $filename) {
            $filepath = path_join($dir, $filename);

            // Get file time of files OLD files.
            $mtime = filemtime($filepath);

            if ($mtime && time() > $mtime + absint($max_age)) {
                unlink($filepath);
            }
        }
    }

    /*
     * Get the featured image for an issue.
     */
    public function get_featured_image($issue_id) {
        return get_post_meta($issue_id, META_FEATURED_IMAGE, true);
    }

    /*
     * Set the featured image for an issue.
     */
    public function update_featured_image($issue_id, $filename) {
        $success = false;
        if ($filename === '' || preg_match("/^{$issue_id}-[0-9-]+\\.(" . SUPPORTED_IMAGE_TYPES . ")$/i", $filename)) {
            WPUtils::update_post_meta($issue_id, META_FEATURED_IMAGE, $filename);
            $success = true;
        }        
        return $success;
    }

    /*
     * Get the image meta data for an issue.
     */
    public function get_image_meta_data($issue_id) {
        $json = get_post_meta($issue_id, META_IMAGE_DATA, true);
        return $json ? json_decode($json, true) : array();
    }    

    /*
     * Get the issue category.
     */
    public function get_issue_category($issue_id) {
        $terms = wp_get_post_terms($issue_id, ISSUE_CATEGORY_TAXONOMY);
        return isset($terms[0]) ? $terms[0] : null;
    }

    /*
     * Get the issue status.
     */
    public function get_issue_status($issue_id) {
        $terms = wp_get_post_terms($issue_id, ISSUE_STATUS_TAXONOMY);
        return isset($terms[0]) ? $terms[0] : null;
    }

    /*
     * Get the options tags for an html select for choosing an issue category.
     */
    public function get_issue_category_options($selected_id, $default, $issue_categories = null, $parent_id = 0) {
        $category_options = '';

        if ($default) {
            $category_options .= '<option value="0">' . esc_html($default) . '</option>';
        }
        
        if (!$issue_categories) {
            $issue_categories = get_terms( array('taxonomy' => ISSUE_CATEGORY_TAXONOMY, 'hide_empty' => false) );
        }
        foreach ($issue_categories as $category) {
            if ($category->parent === $parent_id) {
                $val = esc_attr($category->term_id);
                $prefix = $category->parent !== 0 ? '- ' : '';
                $text = esc_html($prefix . $category->name);
                $selected = $category->term_id === $selected_id ? ' selected' : '';
                $category_options .= '<option value="' . $val . '"' . $selected . '>' . $text . '</option>';
                $category_options .= $this->get_issue_category_options($selected_id, null, $issue_categories, $category->term_id);
            }
        }
        return $category_options;
    }
    
    /*
     * Get the options tags for an html select for choosing an issue status.
     */
    public function get_issue_status_options($selection, $default) {
        $status_options = '';
        
        if ($default) {
            $status_options .= '<option value="0">' . esc_html($default) . '</option>';
        }
        
        $issue_statuses = get_terms( array('taxonomy' => ISSUE_STATUS_TAXONOMY, 'hide_empty' => false) );
        foreach ($issue_statuses as $status) {
            $selected = $status->term_id === $selection ? ' selected' : '';
            $status_options .= '<option value="' . esc_attr($status->term_id) . '"' . $selected . '>' . esc_html($status->name) . '</option>';
        }
        return $status_options;
    }
    
    /*
     * Change the issue status from one value to another.
     */
    public function do_issue_status_workflow($issue_id, $from_status_slug, $to_status_slug) {
        $to_status = get_term_by('slug', $to_status_slug, ISSUE_STATUS_TAXONOMY);
        if ($to_status) {
            $status = $this->get_issue_status($issue_id);
            if ($status && $status->slug === $from_status_slug) {            
                wp_set_post_terms($issue_id, array($to_status->term_id), ISSUE_STATUS_TAXONOMY, false);
            }
        }
    }
    
    /**
     * Generate some artificial issue posts for testing and demonstration purposes.
     */
    /*
    public function debug_generate_test_data() {

        $num_posts = 20;
        $centre_lat = floatval(get_option(OPTION_CENTRE_LAT, DEFAULT_CENTRE_LAT));
        $centre_lng = floatval(get_option(OPTION_CENTRE_LNG, DEFAULT_CENTRE_LNG));
        $radius_lat = 0.05;
        $radius_lon = 0.1;

        for ($i = 0; $i < $num_posts; $i++) {

            $rand1 = rand(0, 1000);
            $rand2 = rand(0, 1000);
            $rand3 = rand(0, 1000);

            // Generate a random location, issue category and status
            $latitude = $centre_lat + (-1.0 + 0.002 * $rand1) * $radius_lat;
            $longitude = $centre_lon + (-1.0 + 0.002 * $rand2) * $radius_lon;
            $issue_category = ($rand1 % 2) ? 'Bedres' : 'Ietvju apmales';
            $issue_category = ($rand2 % 3) ? $issue_category : 'Velonovietnes';
            $issue_status = ($rand2 % 3) ? ISSUE_STATUS_UNREPORTED : ISSUE_STATUS_REPORT_SENT;   // Some reported, some not
            $issue_status = ($rand2 % 5) ? $issue_status : ISSUE_STATUS_RESOLVED;    // Some issues resolved
            $description = "Issue description";

            $issue_id = $this->add_issue($issue_category,
                    $issue_status,
                    $description);
            if ($issue_id) {
                $this->update_issue_location($issue_id, $latitude, $longitude);

                // Attach 1, 2 or 3 images
                $dir = $this->_plugin->get_upload_dir();
                $num_images = ($rand3 % 3) + 1;
                for ($j = 1; $j <= $num_images; $j++) {
                    $source_image = $dir . '/sample/' . sprintf("data/%s/%03d.jpg", str_replace(' ', '-', $issue_category), $j);
                    $dest_image = $dir . $issue_id . '_' . $j . '.jpg';
                    copy($source_image, $dest_image);
                }
            }
        }

        echo "<div style='color: orange'>Generated $num_posts artificial issue posts!</div>";
    }
*/
}
