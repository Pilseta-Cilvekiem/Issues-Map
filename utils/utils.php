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
 * General utility methods.
 */
class Utils {

    public function __construct() {
        
    }

    /*
     * Get the files in a directory which match a regular expression.
     */

    public static function get_files_in_dir($regex, $dir) {
        $files = array();
        $d = dir($dir);
        if ($d) {
            while (false !== ($f = $d->read())) {
                if (preg_match($regex, $f)) {
                    $files[] = $f;
                }
            }
            $d->close();
        }
        return $files;
    }

    /*
     * Validate that a string's length is >= min_len and <= max_len.
     * If not valid, return the default string.
     */

    public static function apply_default_val($str, $default = '', $min_len = 1, $max_len = 64) {
        $len = strlen($str);
        return ($len >= $min_len && $len <= $max_len) ? $str : $default;
    }

    /*
     * Limit the length of a string to the specified maximum.
     */

    public static function cap_str_len($str, $max_len = 64, $ellipsis = false) {
        $len = strlen($str);
        if ($len > $max_len) {
            if ($ellipsis) {
                $str = substr($str, 0, $max_len - 3) . '...';
            } else {
                $str = substr($str, 0, $max_len);
            }
        }
        return $str;
    }
    
    /*
     * Mask an email address in the form x***@y***.zzz,
     * or ******** if the input was in an unexpected format.
     */
    
    public static function mask_email_address($email) {        
        $result = '';
        $at_pos = strpos($email, '@');
        if ($at_pos > 0) {
            $dot_pos = strrpos($email, '.', -1);
            if ($dot_pos > $at_pos + 1) {
                $result = $email[0] . '***@' . $email[$at_pos + 1] . '***' . substr($email, $dot_pos);
            }
        }
        if (!$result) {
            $result = '********';
        }        
        return $result;
    }

    /* Sanitize email address. */
    
    public static function filter_email($input) {
        $val = filter_var($input, FILTER_VALIDATE_EMAIL);
        if ($val === false) {
            $val = '';
        }
        return $val;
    }

                
    /* Sanitize Google Maps API key. */
    
    public static function filter_gmaps_api_key($input) {
        $input = trim($input);
        if (preg_match('/^[A-za-z0-9]+$/', $input)) {
            return $input;
        }
        else {
            return '';
        }
    }
    
    /* Sanitize latitude value. */

    public static function filter_latitude($input) {
        $val = filter_var($input, FILTER_VALIDATE_FLOAT, array('min_range' => -90, 'max_range' => 90));
        if ($val === false) {
            $val = DEFAULT_CENTRE_LAT;
        }
        return $val;
    }

    /* Sanitize longitude value. */

    public static function filter_longitude($input) {
        $val = filter_var($input, FILTER_VALIDATE_FLOAT, array('min_range' => -180, 'max_range' => 180));
        if ($val === false) {
            $val = DEFAULT_CENTRE_LNG;
        }
        return $val;
    }

    /* Sanitize Google maps zoom level. */

    public static function filter_zoom_level($input) {
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

    public static function parse_moderators_list($input) {
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
