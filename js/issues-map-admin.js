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

/* Admin scripts for the issues-map plugin. */
jQuery(document).ready(function ($) {

    /* Icon colour selected. */
    $('.im-color-option').each(function () {
        $(this).wpColorPicker({
            change: function (event, ui) {
                let val = ui.color.toString();
                $('.form-field div.im-icon-preview').css('background-color', val);
            }
        });
    });
    
    /* Icon picker icon clicked. */
    $('div.im-icon-picker-icons a').on('click', function(e) {
        e.preventDefault();
        let val = $(this).children(':first').text();
        $('.form-field div.im-icon-preview').children(':first').text(val);
        $('#im_icon_name').val(val);
    });

});
