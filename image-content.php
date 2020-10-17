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
 * Generates HTML for displaying issue images.
 */
class ImageContentManager {
    
    /*
     * Get the html to display issue images optionally with captions.
     */

    public function get_images_html(
            $image_data,
            $upload_url,
            $args = array(
                'selectable' => false,
                'featured_image' => '',
                'hyperlink' => false,
                'timestamp' => true,
                'gps' => false,
            )
    ) {
        $content = '';

        $selectable_class = (isset($args['selectable']) && $args['selectable']) ? ' im-selectable' : '';
        $featured_image = (isset($args['featured_image']) && $args['featured_image']) ? $args['featured_image'] : '';
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
    
}
