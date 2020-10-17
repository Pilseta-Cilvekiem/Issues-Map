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

}
