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
jQuery(document).ready(function ($) {
    
    /* Dialog box. */
    class ImDialog {

        constructor(id, dialogType) {
            this.id = id;
            this.dialogType = dialogType;
            let args = {
                autoOpen: false,
                resizable: false,
                height: "auto",
                width: 300,
                modal: true,
                title: 'Confirm',
                buttons: {
                }
            };            
            if (dialogType === 'OKCancel') {
                // Confirmation box
                args.buttons.OK = {
                    id: id + '-ok',
                    class: 'button im-button',
                    text: 'OK'
                };
                args.buttons.Cancel = {
                    id: id + '-cancel',
                    class: 'button im-button im-secondary-button',
                    text: 'Cancel',
                    click: function() {
                        $( this ).dialog( "close" );
                    }
                };
            }
            else {
                // Alert box
                args.buttons.OK = {
                    id: id + '-ok',
                    class: 'button im-button',
                    text: 'OK',
                };
            }
            $( '#' + id ).dialog(args);
        }

        /* Show dialog. */
        show(args) {
            let sel = '#' + this.id;
            $(sel).dialog('option', 'title', args.title);
            $(sel).html(args.text);
            $(sel + '-ok').text(args.ok);
            $(sel + '-cancel').text(args.cancel);
            $(sel + '-ok').unbind('click');
            $(sel + '-ok').on('click', args.action);
            $(sel).dialog("open");
        }

        /* Close dialog. */
        close() {
            $( '#' + this.id ).dialog("close");
        }
    }
    var _imConfirmDialog = new ImDialog('im-confirm-dialog', 'OKCancel');            
    var _imAlertDialog = new ImDialog('im-alert-dialog', 'OK');
    
    function closeAlert() {
        _imAlertDialog.close();
    }
        
    /* Add/edit issue details. */
    $('#im-edit-details-form').on('submit', function (e) {
        e.preventDefault();
        $("#im-message").text('');
        let issueCategory = $('#im-issue-category').val();        
        let issueStatus = $('#im-issue-status').val();
        let issueTitle = $('#im-issue-title').val();
        let description = $('#im-description').val();
        let addedBy = $('#im-added-by').val();
        let emailAddress = $('#im-email-address').val();
        let security = $('#im-edit-details-nonce').val();
        $.ajax({
            url: issues_map.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'edit_details_async',
                security: security,
                issue_id: issues_map.issue_id,
                issue_category: issueCategory,
                issue_status: issueStatus,
                issue_title: issueTitle,
                description: description,
                added_by: addedBy,
                email_address: emailAddress
            },
            success: function (response) {                
                if (response) {
                    if (response.success) {
                        document.location.href = response.redirect_url;
                    } else {
                        $("#im-message").text(response.message);
                    }
                }
            }
        });
    });

    /* Add images. */
    $('#im-add-images-form').on('submit', function (e) {
        e.preventDefault();
        $("#im-message").text('');
        let security = $('#im-add-images-nonce').val();
        let view = $('#im-view').val();
        let images = [];
        $('.dnd-upload-details input').each(function() {
            let image = $(this).val();
            images.push(image);
        });
        $.ajax({
            url: issues_map.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'add_images_async',
                security: security,
                view: view,
                issue_id: issues_map.issue_id,
                image_list: images
            },
            success: function (response) {                
                if (response) {
                    if (response.success) {
                        document.location.href = response.redirect_url;
                    } else {
                        $("#im-message").text(response.message);
                    }
                }
            }
        });
    });
    
    /* Cancel added images. */
    $('#im-cancel-add-images-button').click(function(e) {
        e.preventDefault();
        let security = $('#im-add-images-nonce').val();
        let images = [];
        $('.dnd-upload-details input').each(function() {
            let image = $(this).val();
            images.push(image);
        });
        $.ajax({
            url: issues_map.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'cancel_add_images_async',
                security: security,
                issue_id: issues_map.issue_id,
                image_list: images
            },
            success: function () {
                window.history.back();
            }
        });
    });
    
    /* Edit location. */
    $('#im-edit-location-form').on('submit', function (e) {
        e.preventDefault();
        $("#im-message").text('');        
        // Get the Google map's centre location
        if (typeof imGetMap === 'function') {
            let mapEntry = imGetMap('im-map');
            if (typeof mapEntry != 'undefined') {            
                let lat = mapEntry.options.center.lat;
                let lng = mapEntry.options.center.lng;
                let security = $('#im-edit-location-nonce').val();
                $.ajax({
                    url: issues_map.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'edit_location_async',
                        security: security,
                        issue_id: issues_map.issue_id,
                        lat: lat,
                        lng: lng
                    },
                    success: function (response) {                
                        if (response) {
                            if (response.success) {
                                document.location.href = response.redirect_url;
                            } else {
                                $("#im-message").text(response.message);
                            }
                        }
                    }
                });
            }
        }
    });

    /* Add/edit report. */
    $('#im-edit-report-form').on('submit', function (e) {
        e.preventDefault();
        $("#im-message").text('');
        let templateId = $('#im-report-template-id').val();
        let recipientName = $('#im-report-recipient-name').val();
        let recipientEmail = $('#im-report-recipient-email').val();
        let emailBody = $('#im-report-email-body').val();
        let toAddress = $('#im-report-to-address').val();
        let fromAddress = $('#im-report-from-address').val();
        let fromEmail = $('#im-report-from-email').val();
        let greeting = $('#im-report-greeting').val();
        let addressee = $('#im-report-addressee').val();
        let body = $('#im-report-body').val();
        let signOff = $('#im-report-sign-off').val();
        let addedBy = $('#im-report-added-by').val();
        let security = $('#im-edit-report-nonce').val();
        $.ajax({
            url: issues_map.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'edit_report_async',
                security: security,
                issue_id: issues_map.issue_id,
                report_id: issues_map.report_id,
                'im-report-template-id': templateId,
                'im-report-recipient-name': recipientName,
                'im-report-recipient-email': recipientEmail,
                'im-report-email-body': emailBody,
                'im-report-to-address': toAddress,
                'im-report-from-address': fromAddress,
                'im-report-from-email': fromEmail,
                'im-report-greeting': greeting,
                'im-report-addressee': addressee,
                'im-report-body': body,
                'im-report-sign-off': signOff,
                'im-report-added-by': addedBy
            },
            success: function (response) {                
                if (response) {
                    if (response.success) {
                        document.location.href = response.redirect_url;
                    } else {
                        $("#im-message").text(response.message);
                    }
                }
            }
        });
    });    
    
    /* Back clicked. */
    $('.im-back-button').click(function(e) {
        e.preventDefault();
        window.history.back();
    });

    /* Issue image selected. */
    $('img.im-selectable').on('click', function(e) {
        e.preventDefault();
        let selected = $(this).hasClass('im-selected');
        $('img.im-selected').removeClass('im-selected');
        if (!selected) {
            $(this).addClass('im-selected');
        }
    });

    /* Delete issue image. */
    $('#im-delete-image-link').on('click', function(e) {
        e.preventDefault();        
        $("#im-message").text('');
        let sel = $('img.im-selected');
        if (sel.length == 0) {
            let args = {
                title: issues_map.select_an_image_str,
                text: issues_map.select_an_image_full_str,
                ok: issues_map.ok_str,
                action: closeAlert
            };
            _imAlertDialog.show(args);
        }
        else {
            let args = {
                title: issues_map.confirm_str,
                text: issues_map.confirm_delete_image_str,
                ok: issues_map.delete_str,
                cancel: issues_map.cancel_str,
                action: do_delete_image
            };
            _imConfirmDialog.show(args);
        } 
    });

    /* Delete selected images. */
    function do_delete_image() {
        _imConfirmDialog.close();
        $('img.im-selected').each(function() {
            let container = $(this).parents('.im-image-item').first();
            let filename = container.data('fileref');
            let security = $('#im-issue-or-report-nonce').val();
            $.ajax({
                url: issues_map.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'delete_issue_image_async',
                    security: security,
                    issue_id: issues_map.issue_id,
                    filename: filename
                },
                success: function (response) {
                    if (response) {
                        if (response.success) {
                            container.remove();
                            if (response.featured_image !== '') {
                                $("div.im-image-item[data-fileref='" + response.featured_image + "']").addClass('im-featured');
                            }
                            if ($('div.im-image-item').length == 0) {
                                $('#im-delete-image-link').addClass('im-hidden');
                                $('#im-set-as-featured-link').addClass('im-hidden');                                    
                            }    
                        } else {
                            $("#im-message").text(response.message);
                        }
                    }
                }
            });
        });        
    }

    /* Set featured image. */
    $('#im-set-as-featured-link').on('click', function(e) {
        e.preventDefault();
        $("#im-message").text('');
        let sel = $('img.im-selected').first();
        if (sel.length == 0) {
            let args = {
                title: issues_map.select_an_image_str,
                text: issues_map.select_an_image_full_str,
                ok: issues_map.ok_str,
                action: closeAlert
            };
            _imAlertDialog.show(args);
        }
        else {
            let container = sel.parents('.im-image-item').first();
            if (container.hasClass('im-featured')) {
                let args = {
                    title: issues_map.already_featured_image_str,
                    text: issues_map.already_featured_image_full_str,
                    ok: issues_map.ok_str,
                    action: closeAlert
                };
                _imAlertDialog.show(args);
            }
            else {
                let filename = container.data('fileref');
                let security = $('#im-issue-or-report-nonce').val();
                $.ajax({
                    url: issues_map.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'set_featured_image_async',
                        security: security,
                        issue_id: issues_map.issue_id,
                        filename: filename
                    },
                    success: function (response) {
                        if (response) {
                            if (response.success) {                        
                                $("div.im-image-item").removeClass('im-featured');
                                if (response.featured_image !== '') {
                                    $("div.im-image-item[data-fileref='" + response.featured_image + "']").addClass('im-featured');
                                }                        
                            } else {
                                $("#im-message").text(response.message);
                            }
                        }
                    }
                });
            }
        }        
    });
    
    /* Send report. */
    $('#im-send-report-button').on('click', function(e) {
        e.preventDefault();
        $("#im-message").text('');
        let args = {
            title: issues_map.confirm_str,
            text: issues_map.confirm_send_report_str,
            ok: issues_map.send_str,
            cancel: issues_map.cancel_str,
            action: do_send_report
        };
        _imConfirmDialog.show(args);               
    });
    
    /* Perform report sending. */
    function do_send_report() {
        _imConfirmDialog.close();
        let security = $('#im-issue-or-report-nonce').val();
        $.ajax({
            url: issues_map.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'send_report_async',
                security: security,
                report_id: issues_map.report_id
            },
            success: function (response) {
                if (response) {
                    if (response.success) {                        
                        $("#im-message").text(response.message);
                    } else {
                        $("#im-message").text(response.message);
                    }
                }
            }
        });
    };
    
    /* Unfocus buttons after click. */
    $('.im-button').on('click', function(e) {
        $(this).blur();
    });

    /* Download report PDF. */
    $('#im-download-pdf-button').on('click', function(e) {
        e.preventDefault();
        $("#im-message").text('');
        let security = $('#im-issue-or-report-nonce').val();
        $.ajax({
            url: issues_map.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'download_report_async',
                security: security,
                report_id: issues_map.report_id
            },
            success: function (response) {
                if (response) {
                    if (response.success) {                        
                        document.location.href = response.redirect_url;
                    } else {
                        $("#im-message").text(response.message);
                    }
                }
            }
        });
    });

    /* Delete issue. */
    $('#im-delete-issue-link').on('click', function(e) {
        e.preventDefault();        
        $("#im-message").text('');
        let args = {
            title: issues_map.confirm_str,
            text: issues_map.confirm_delete_issue_str,
            ok: issues_map.delete_str,
            cancel: issues_map.cancel_str,
            action: do_delete_issue
        };
        _imConfirmDialog.show(args);               
    });
    
    /* Perform issue deletion. */
    function do_delete_issue() {
        _imConfirmDialog.close();
        let security = $('#im-issue-or-report-nonce').val();
        $.ajax({
            url: issues_map.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'delete_issue_async',
                security: security,
                issue_id: issues_map.issue_id
            },
            success: function (response) {
                if (response) {
                    if (response.success) {
                        issues_map.temp_data = { 
                            href: response.redirect_url 
                        };
                        let args = {
                            title: issues_map.issue_deleted_str,
                            text: issues_map.issue_deleted_full_str,
                            ok: issues_map.ok_str,
                            action: issue_deleted
                        };
                        _imAlertDialog.show(args);
                    } else {
                        $("#im-message").text(response.message);
                    }
                }
            }
        });             
    }
    
    /* Redirect user after deleting issue. */
    function issue_deleted() {
        closeAlert();
        document.location.href = issues_map.temp_data.href;
    }

    /* Delete report from issue page. */
    $('.im-issue-delete-report-link').on('click', function(e) {
        e.preventDefault();        
        $("#im-message").text('');
        issues_map.temp_data = { 
            report_id: $(this).data('report-id'), 
            href: null 
        };
        let args = {
            title: issues_map.confirm_str,
            text: issues_map.confirm_delete_report_str,
            ok: issues_map.delete_str,
            cancel: issues_map.cancel_str,
            action: do_delete_report
        };
        _imConfirmDialog.show(args);
    });

    /* Delete report from report page. */
    $('#im-delete-report-link').on('click', function(e) {
        e.preventDefault();        
        issues_map.temp_data = { 
            report_id: issues_map.report_id,
            href: $('#im-back-link').attr('href') 
        };
        let args = {
            title: issues_map.confirm_str,
            text: issues_map.confirm_delete_report_str,
            ok: issues_map.delete_str,
            cancel: issues_map.cancel_str,
            action: do_delete_report
        };
        _imConfirmDialog.show(args);
    });
    
    /* Perform report deletion. */
    function do_delete_report() {
        _imConfirmDialog.close();
        let security = $('#im-issue-or-report-nonce').val();
        $.ajax({
            url: issues_map.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'delete_report_async',
                security: security,
                report_id: issues_map.temp_data.report_id
            },
            success: function (response) {
                if (response) {
                    if (response.success) {
                        if (issues_map.temp_data.href !== null) {
                            document.location.href = issues_map.temp_data.href;
                        }
                        else {
                            location.reload();
                        }
                    } else {
                        $("#im-message").text(response.message);
                    }
                }
            }
        });            
    }
    
    /* Issues list filter option changed. */
    $('div.im-list-view select.im-category-filter, div.im-list-view select.im-status-filter, div.im-list-view input.im-own-issues-filter').on('change', function(e) {
        e.preventDefault();
        refresh_issues_list(1);
    });
    
    /* Issues list page nav. */
    $('div.im-list-view').on('click', '.im-page-nav-link', function(e) {
        e.preventDefault();
        let page_num = $(this).data('go-to-page-num');
        refresh_issues_list(page_num);
    });
    
    /* Refresh issues list. */
    function refresh_issues_list(page_num) {
        let security = $('#im-list-view-nonce').val();
        let category_filter = $('#im-category-filter').val();
        let status_filter = $('#im-status-filter').val();
        let own_issues_filter = $('#im-own-issues-filter').prop('checked');
        $.ajax({
            url: issues_map.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_issues_list_async',
                security: security,
                page_num: page_num,
                category_filter: category_filter,
                status_filter: status_filter,
                own_issues_filter: own_issues_filter
            },
            success: function (response) {
                if (response) {
                    if (response.success) {
                        $('.im-list-view-items').html(response.data);
                    }
                }
            }
        });            
    }

    /* Map view filter option changed. */
    $('div.im-map-view select.im-category-filter, div.im-map-view select.im-status-filter, div.im-map-view input.im-own-issues-filter').on('change', function(e) {
        e.preventDefault();
        refresh_map_items();
    });
    
    /* Refresh map items. */
    function refresh_map_items() {
        let security = $('#im-map-view-nonce').val();
        let category_filter = $('#im-category-filter').val();
        let status_filter = $('#im-status-filter').val();
        let own_issues_filter = $('#im-own-issues-filter').prop('checked');
        $.ajax({
            url: issues_map.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_map_items_async',
                security: security,
                category_filter: category_filter,
                status_filter: status_filter,
                own_issues_filter: own_issues_filter
            },
            success: function (response) {
                if (response) {
                    if (response.success) {
                        if (typeof imUpdateMap === 'function') {
                            let content = JSON.parse(response.data);
                            imUpdateMap('im-map', content);
                        }
                    }
                }
            }
        });
    }

});


