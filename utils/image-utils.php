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

class ImageUtils {

    /**
     * Get an array of meta data from an image file (e.g. date taken, lat, lon).
     */
    public static function get_image_meta_data($filepath) {
        $filename = basename($filepath);
        $date_time_original = '';
        $latitude = 0.0;
        $longitude = 0.0;

        $exif = exif_read_data($filepath, 0, true);
        if ($exif) {

            if (isset($exif['EXIF']) &&
                    isset($exif['EXIF']['DateTimeOriginal'])) {
                $date_time_original = $exif['EXIF']['DateTimeOriginal'];
            }

            if (isset($exif['GPS']) &&
                    isset($exif['GPS']['GPSLatitudeRef']) &&
                    isset($exif['GPS']['GPSLatitude']) &&
                    isset($exif['GPS']['GPSLongitudeRef']) &&
                    isset($exif['GPS']['GPSLongitudeRef'])) {
                $GPSLatitudeRef = $exif['GPS']['GPSLatitudeRef'];
                $GPSLatitude = $exif['GPS']['GPSLatitude'];
                $GPSLongitudeRef = $exif['GPS']['GPSLongitudeRef'];
                $GPSLongitude = $exif['GPS']['GPSLongitude'];

                $lat_degrees = count($GPSLatitude) > 0 ? self::gps2Num($GPSLatitude[0]) : 0;
                $lat_minutes = count($GPSLatitude) > 1 ? self::gps2Num($GPSLatitude[1]) : 0;
                $lat_seconds = count($GPSLatitude) > 2 ? self::gps2Num($GPSLatitude[2]) : 0;

                $lon_degrees = count($GPSLongitude) > 0 ? self::gps2Num($GPSLongitude[0]) : 0;
                $lon_minutes = count($GPSLongitude) > 1 ? self::gps2Num($GPSLongitude[1]) : 0;
                $lon_seconds = count($GPSLongitude) > 2 ? self::gps2Num($GPSLongitude[2]) : 0;

                $lat_direction = ($GPSLatitudeRef == 'W' or $GPSLatitudeRef == 'S') ? -1 : 1;
                $lon_direction = ($GPSLongitudeRef == 'W' or $GPSLongitudeRef == 'S') ? -1 : 1;

                $latitude = $lat_direction * ($lat_degrees + ($lat_minutes / 60) + ($lat_seconds / (60 * 60)));
                $longitude = $lon_direction * ($lon_degrees + ($lon_minutes / 60) + ($lon_seconds / (60 * 60)));
            }
        }

        return array(
            META_FILENAME => $filename,
            META_TIMESTAMP => $date_time_original,
            META_LATITUDE => $latitude,
            META_LONGITUDE => $longitude,
        );
    }

    /**
     * Convert GPS string token of the form 'A/B' to a number.
     */
    private static function gps2Num($coordPart) {
        $parts = explode('/', $coordPart);
        if (count($parts) <= 0)
            return 0;
        if (count($parts) == 1)
            return $parts[0];
        return floatval($parts[0]) / floatval($parts[1]);
    }

    /**
     * Resize an image (jpeg and png supported).
     */
    public static function create_thumbnail($file, $dim, $dim_is_width = true) {
        $success = false;
        $src = null;
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                $src = imagecreatefromjpeg($file);
                break;
            case 'png':
                $src = imagecreatefrompng($file);
                break;
        }

        if ($src) {
            list($width, $height) = getimagesize($file);
            $r = $width / $height;
            if ($dim_is_width) {
                $newwidth = $dim;
                $newheight = ceil($dim / $r);
            } else {
                $newheight = $dim;
                $newwidth = ceil($dim * $r);
            }

            $dst = imagecreatetruecolor($newwidth, $newheight);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
            $len = strlen($file);
            $outfile = substr($file, 0, $len - strlen($ext) - 1) . "-thumb." . $ext;
            switch ($ext) {
                case 'jpg':
                case 'jpeg':
                    imagejpeg($dst, $outfile);
                    break;
                case 'png':
                    imagepng($dst, $outfile);
                    break;
            }

            $success = true;
        }

        return $success;
    }

}
