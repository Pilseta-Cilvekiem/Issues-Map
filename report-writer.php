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

require_once 'tfpdf/tfpdf.php';
require_once 'utils/wp-utils.php';

/*
 * Creates issue report PDF files.
 */

class ReportWriter extends \tFPDF {

    protected $_plugin;
    protected $_font_name = 'DejaVu';
    protected $_font_size = 11;
    protected $_line_height = 5;
    protected $_margin = 20;
    /* From fpdf.org HTML writer tutorial */
    protected $B = 0;
    protected $I = 0;
    protected $U = 0;
    protected $HREF = '';
    /* End fpdf.org */

    public function __construct($plugin) {
        parent::__construct();
        $this->_plugin = $plugin;
    }

    /*
     * Create a PDF file for a report.
     */

    public function create_pdf($report_id, $overwrite = false) {
        $filename = '';
        $issue_id = get_post_meta($report_id, META_ISSUE_ID, true);
        $post = get_post($report_id);
        if ($issue_id && $post) {
            $ref = get_post_meta($report_id, META_REF, true);
            // Include a random salt in the filename to prevent the PDF's url
            // being guessable and therefore accessible to unauthorised users.
            $salt = get_post_meta($report_id, META_SALT, true);
            $filename = $ref . '-' . $salt . '.pdf';
            $dir = $this->_plugin->get_upload_dir();
            $filepath = path_join($dir, $filename);
            if (preg_match("/^[0-9a-z-]+\\.pdf$/i", $filename) && // Defensive check
                    ($overwrite || !file_exists($filepath))) {
                $report_meta = get_post_meta($report_id, '', false);
                $from_address = WPUtils::get_meta_val($report_meta, META_FROM_ADDRESS);
                $from_email = WPUtils::get_meta_val($report_meta, META_FROM_EMAIL);
                $to_address = WPUtils::get_meta_val($report_meta, META_TO_ADDRESS);
                $greeting = WPUtils::get_meta_val($report_meta, META_GREETING);
                $addressee = WPUtils::get_meta_val($report_meta, META_ADDRESSEE);
                $body = nl2br($post->post_content);        
                $sign_off = WPUtils::get_meta_val($report_meta, META_SIGN_OFF);
                $added_by = WPUtils::get_meta_val($report_meta, META_ADDED_BY);
                $date = WPUtils::get_meta_val($report_meta, META_DATE);
                $ref_str = sanitize_text_field(__('Ref:', 'issues-map'));

                $this->AddFont($this->_font_name, '', 'DejaVuSansCondensed.ttf', true);
                $this->AddFont($this->_font_name, 'B', 'DejaVuSansCondensed-Bold.ttf', true);
                $this->AddFont($this->_font_name, 'I', 'DejaVuSansCondensed-Oblique.ttf', true);
                $this->AddFont($this->_font_name, 'BI', 'DejaVuSansCondensed-BoldOblique.ttf', true);
                $this->SetMargins($this->_margin, $this->_margin);
                $this->AddPage();
                $this->SetFont($this->_font_name, '', $this->_font_size);
                $this->MultiCell(0, $this->_line_height, $to_address, 0, 'R');
                $this->Ln();
                $this->MultiCell(0, $this->_line_height, $from_address, 0, 'R');
                $this->Cell(0, $this->_line_height, $from_email, 0, 1, 'R');
                $this->Ln();
                $this->Cell(0, $this->_line_height, $ref_str . ' ' . $ref, 0, 1, 'R');
                $this->Ln();
                $this->Ln();
                $this->Ln();
                $this->Cell(0, $this->_line_height, $greeting . ' ' . $addressee, 0, 1);
                $this->Ln();
                $this->WriteHTML($body);
                $this->Ln();
                $this->Ln();
                $this->Cell(0, $this->_line_height, $sign_off, 0, 1);
                $this->Ln();
                $this->Cell(0, $this->_line_height, $added_by, 0, 1);
                $this->Cell(0, $this->_line_height, $date, 0, 1);
                $this->Ln();

                // Add images if required
                $include_images = get_option(OPTION_INCLUDE_IMAGES_IN_REPORTS, DEFAULT_INCLUDE_IMAGES_IN_REPORTS);
                if ($include_images) {
                    $this->add_issue_images($issue_id);
                }

                // Save file
                $this->Output('F', $filepath);
            }
        }

        return $filename;
    }

    /*
     * Add the issue's images.
     */

    private function add_issue_images($issue_id) {
        $issue_data_mgr = $this->_plugin->get_issue_data_mgr();
        $image_data = $issue_data_mgr->get_image_meta_data($issue_id);
        if (count($image_data) !== 0) {
            $this->AddPage();
            $upload_dir = $this->_plugin->get_upload_dir();
            $upload_url = $this->_plugin->get_upload_url();
            foreach ($image_data as $image_meta) {
                $img = $image_meta[META_FILENAME];
                $timestamp = $image_meta[META_TIMESTAMP];
                $lat = $image_meta[META_LATITUDE];
                $lng = $image_meta[META_LONGITUDE];
                $src = esc_url($upload_url . $img);
                $thumbnail = str_replace('.', '-thumb.', $img);
                $img_path = path_join($upload_dir, $thumbnail);
                if (file_exists($img_path)) {
                    $this->Image($img_path);
                    $this->PutLink($src, $img);
                    $this->Ln();
                    if ($timestamp) {
                        $this->Cell(0, $this->_line_height, $timestamp, 0, 1);
                    }
                    if ($lat || $lng) {
                        $gps_line = 'GPS: ' . $lat . ', ' . $lng;
                        $this->Cell(0, $this->_line_height, $gps_line, 0, 1);
                    }
                    $this->Ln();
                }
            }
        }
    }

    /* From fpdf.org HTML writer tutorial */

    protected function WriteHTML($html) {
        // HTML parser
        $html = str_replace("\n", '', $html);   // Changed from tutorial, which replaced newlines with spaces.
        $a = preg_split('/<(.*)>/U', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($a as $i => $e) {
            if ($i % 2 == 0) {
                // Text
                if ($this->HREF)
                    $this->PutLink($this->HREF, $e);
                else
                    $this->Write($this->_line_height, $e);
            } else {
                // Tag
                if ($e[0] == '/')
                    $this->CloseTag(strtoupper(substr($e, 1)));
                else {
                    // Extract attributes
                    $a2 = explode(' ', $e);
                    $tag = strtoupper(array_shift($a2));
                    $attr = array();
                    foreach ($a2 as $v) {
                        if (preg_match('/([^=]*)=["\']?([^"\']*)/', $v, $a3))
                            $attr[strtoupper($a3[1])] = $a3[2];
                    }
                    $this->OpenTag($tag, $attr);
                }
            }
        }
    }

    /* From fpdf.org HTML writer tutorial */

    protected function OpenTag($tag, $attr) {
        // Opening tag
        if ($tag == 'B' || $tag == 'I' || $tag == 'U')
            $this->SetStyle($tag, true);
        if ($tag == 'A')
            $this->HREF = $attr['HREF'];
        if ($tag == 'BR')
            $this->Ln();
    }

    /* From fpdf.org HTML writer tutorial */

    protected function CloseTag($tag) {
        // Closing tag
        if ($tag == 'B' || $tag == 'I' || $tag == 'U')
            $this->SetStyle($tag, false);
        if ($tag == 'A')
            $this->HREF = '';
    }

    /* From fpdf.org HTML writer tutorial */

    protected function SetStyle($tag, $enable) {
        // Modify style and select corresponding font
        $this->$tag += ($enable ? 1 : -1);
        $style = '';
        foreach (array('B', 'I', 'U') as $s) {
            if ($this->$s > 0)
                $style .= $s;
        }
        $this->SetFont('', $style);
    }

    /* From fpdf.org HTML writer tutorial */

    protected function PutLink($URL, $txt) {
        // Put a hyperlink
        $this->SetTextColor(0, 0, 255);
        $this->SetStyle('U', true);
        $this->Write($this->_line_height, $txt, $URL);
        $this->SetStyle('U', false);
        $this->SetTextColor(0);
    }

}

