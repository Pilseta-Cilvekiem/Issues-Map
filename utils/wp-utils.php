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
 * Wordpress-specific utility methods.
 */
class WPUtils {
    /*
     * Register a taxonomy.
     */

    public static function register_taxonomy($slug, $plural, $post_types, $labels, $options = null) {
        $args = array(
            'labels' => $labels,
            'public' => false,
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => false,
            'rewrite' => array('slug' => $plural),
        );
        if ($options) {
            foreach ($options as $key => $val) {
                $args[$key] = $val;
            }
        }

        register_taxonomy($slug, $post_types, $args);
    }

    /*
     * Register a custom post type.
     */

    public static function register_custom_post_type($slug, $plural, $labels, $supports = null, $options = null) {
        if ($supports === null) {
            $supports = array(
                'title', // post title
                'editor', // post content
                'author', // post author
                    //'thumbnail', // featured images
                    //'excerpt', // post excerpt
                    //'custom-fields', // custom fields
                    //'comments', // post comments
                    //'revisions', // post revisions
                    //'post-formats', // post formats
            );
        }

        $args = array(
            'supports' => $supports,
            'labels' => $labels,
            'rewrite' => array('slug' => $plural),
        );
        if ($options) {
            foreach ($options as $key => $val) {
                $args[$key] = $val;
            }
        }

        register_post_type($slug, $args);
    }

    /*
     * Send the specified array as JSON response then wp_die().
     */

    public static function send_json_response($response) {
        wp_send_json($response);
        wp_die();
    }

    /*
     * Get a value from a post's meta data array.
     */

    public static function get_meta_val($post_meta, $meta_key) {
        $meta_value = '';
        if (isset($post_meta[$meta_key])) {
            $meta_value = $post_meta[$meta_key][0];
        }
        return $meta_value;
    }

    /*
     * Replace placeholder tokens like '{user_full_name}' with their corresponding values.
     */

    public static function expand_placeholders($str, $issue_id, $placeholders) {
        foreach ($placeholders as $placeholder) {
            if (strpos($str, $placeholder) !== false) {
                $replacement = '';
                switch ($placeholder) {
                    case '{user_email}':
                        $user = wp_get_current_user();
                        if ($user) {
                            $replacement = $user->user_email;
                        }
                        break;
                    case '{user_full_name}':
                        $user = wp_get_current_user();
                        if ($user) {
                            $replacement = $user->first_name . ' ' . $user->last_name;
                        }
                        break;
                    case '{user_display_name}':
                        $user = wp_get_current_user();
                        if ($user) {
                            $replacement = $user->nickname;
                        }
                        break;
                    case '{issue_title}':
                        $issue = get_post($issue_id);
                        if ($issue) {
                            $replacement = $issue->post_title;
                        }
                        break;
                    case '{issue_description}':
                        $issue = get_post($issue_id);
                        if ($issue) {
                            $replacement = $issue->post_content;
                        }
                        break;
                    case '{issue_added_by}':
                        $replacement = get_post_meta($issue_id, META_ADDED_BY, true);
                        break;
                    case '{issue_added_date}':
                        $issue = get_post($issue_id);
                        if ($issue) {
                            $replacement = date('d.m.Y', strtotime($issue->post_date));
                        }
                        break;
                    case '{issue_added_time}':
                        $issue = get_post($issue_id);
                        if ($issue) {
                            $replacement = date('H.i.s', strtotime($issue->post_date));
                        }
                        break;
                    case '{issue_updated_date}':
                        $issue = get_post($issue_id);
                        if ($issue) {
                            $replacement = date('d.m.Y', strtotime($issue->post_modified));
                        }
                        break;
                    case '{issue_updated_time}':
                        $issue = get_post($issue_id);
                        if ($issue) {
                            $replacement = date('H.i.s', strtotime($issue->post_modified));
                        }
                        break;
                    case '{issue_link}':
                        $replacement = DEMO_VERSION ? __('{issue_link} not supported in demo mode.', 'issues-map') : get_permalink($issue_id);
                        break;
                    case '{issue_lat}':
                        $replacement = get_post_meta($issue_id, META_LATITUDE, true);
                        break;
                    case '{issue_lng}':
                        $replacement = get_post_meta($issue_id, META_LONGITUDE, true);
                        break;
                    case '{date_today}':
                        $replacement = date('d.m.Y');
                        break;
                }

                $str = str_replace($placeholder, $replacement, $str);
            }
        }
        return $str;
    }

    /* 
     * Delete taxonomy terms.
     */

    public static function delete_custom_terms($taxonomy) {
        // Check that the taxonomy is registered (avoids problems during uninstall)
        if (!get_taxonomy($taxonomy)) {
            register_taxonomy($taxonomy, null);
        }
        $terms = get_terms($taxonomy, array('hide_empty' => false));
        if (is_array($terms)) {
            foreach ($terms as $term) {
                wp_delete_term($term->term_id, $taxonomy);
            }
        }     
    }

    /*
     * Create a page with the given slug if it doesn't already exist. 
     * Returns the page ID.
     */

    public static function create_page($slug, $title) {
        $page = get_page_by_path($slug);
        if ($page) {
            $page_id = $page->ID;
        } else {
            $page_id = wp_insert_post(array(
                'post_name' => $slug,
                'post_title' => $title,
                'post_content' => '',
                'post_status' => 'publish',
                'post_author' => get_current_user_id(),
                'post_type' => 'page'
            ));
        }
        return $page_id;
    }

    /*
     * Delete a page if it is empty. 
     * As a precaution, doesn't delete if the page is set to be the site's homepage or blog posts page.
     */

    public static function delete_page_if_empty($slug) {
        $result = false;
        $page = get_page_by_path($slug);
        if ($page && !$page->post_content && $page->ID != get_option('page_on_front') && $page->ID != get_option('page_for_posts')) {
            $result = wp_delete_post($page->ID, true);
        }
        return $result;
    }

    /*
     * Insert a taxonomy term, optionally with meta data.
     */

    public static function insert_term($name, $taxonomy, $args = null, $meta_data = null) {
        $term = wp_insert_term(
                $name,
                $taxonomy,
                $args,
        );
        if (!is_wp_error($term) && $meta_data) {
            foreach ($meta_data as $key => $val) {
                update_term_meta($term['term_id'], $key, $val);
            }
        }
    }

}
