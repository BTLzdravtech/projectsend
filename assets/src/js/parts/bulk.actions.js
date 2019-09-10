/**
 * Apply bulk actions
 */
(function () {
    'use strict';

    admin.parts.bulkActions = function () {

        $(document).ready(function(e) {
            $(".batch_actions").on('submit', function(e) {
                var checks = $("td>input:checkbox").serializeArray();
                var action = $('#action').val();
                if (action != 'none') {
                        // Generic actions
                        if (action == 'delete') {
                            if (checks.length == 0) {
                                bootbox.alert({
                                    title: json_strings.validation.errors_found_title + ':',
                                    message: json_strings.translations.select_one_or_more,
                                    buttons: {
                                        ok: {
                                            label: json_strings.modal.ok
                                        }
                                    },
                                });
                                return false;
                            }
                            else {
                                var _formatted = sprintf(json_strings.translations.confirm_delete, checks.length);
                                bootbox.confirm({
                                    message: _formatted,
                                    buttons: {
                                        confirm: {
                                            label: json_strings.modal.ok
                                        },
                                        cancel: {
                                            label: json_strings.modal.cancel
                                        }
                                    },
                                    callback: function(result) {
                                        if (result) {
                                            $('.batch_actions').unbind('submit');
                                            $('.batch_actions').find('button[type="submit"]').click();
                                        }
                                    }
                                });
                                e.preventDefault();
                            }
                        }

                        // Activities log actions
                        if (action == 'log_clear') {
                            bootbox.confirm({
                                message: json_strings.translations.confirm_delete_log,
                                buttons: {
                                    confirm: {
                                        label: json_strings.modal.ok
                                    },
                                    cancel: {
                                        label: json_strings.modal.cancel
                                    }
                                },
                                callback: function(result) {
                                    if (result) {
                                        $('.batch_actions').unbind('submit');
                                        $('.batch_actions').find('button[type="submit"]').click();
                                    }
                                }
                            });
                            e.preventDefault();
                        }

                        if (action == 'log_download') {
                            e.preventDefault();
                            $(document).psendmodal();
                            Cookies.set('log_download_started', 0, { expires: 100 });
                            setTimeout(check_log_download_cookie, 1000);

                            $('.modal_content').html('<p class="loading-img">'+
                                                        '<img src="'+json_strings.uri.assets_img+'/ajax-loader.gif" alt="Loading" /></p>'+
                                                        '<p class="lead text-center text-info">'+json_strings.translations.download_wait+'</p>'
                                                    );
                            $('.modal_content').append('<iframe src="'+json_strings.uri.base+'includes/actions.log.export.php?format=csv"></iframe>');

                            return false;
                        }

                        // Manage files actions
                        if (action == 'unassign') {
                            var _formatted = sprintf(json_strings.translations.confirm_unassign, checks.length);
                            if (!confirm(_formatted)) {
                                e.preventDefault();
                            }
                        }

                        // Templates
                        if (action == 'zip') {
                            e.preventDefault();
                            var checkboxes = $("td>input:checkbox").serializeArray();

                            $(document).psendmodal();

                            Cookies.set('download_started', 0, { expires: 100 });
                            setTimeout(check_download_cookie, 1000);
                            $('.modal_content').html('<p class="loading-img"><img src="'+json_strings.uri.assets_img+'/ajax-loader.gif" alt="Loading" /></p>'+
                                                        '<p class="lead text-center text-info">'+json_strings.translations.download_wait+'</p>'+
                                                        '<p class="text-center text-info">'+json_strings.translations.download_long_wait+'</p>'
                                                    );
                            $.ajax({
                                method: 'GET',
                                url: json_strings.uri.base + 'process.php',
                                data: { do:"return_files_ids", files:checkboxes }
                            }).done( function(rsp) {
                                var url = json_strings.uri.base + 'process.php?do=download_zip&files=' + rsp;
                                $('.modal_content').append("<iframe id='modal_zip'></iframe>");
                                $('#modal_zip').attr('src', url);
                            });
                            return false;
                        }
                }
                else {
                    return false;
                }
            });
        });
    };
})();