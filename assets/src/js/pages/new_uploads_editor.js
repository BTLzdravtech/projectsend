(function () {
    'use strict';

    admin.pages.newUploadsEditor = function () {

        $(document).ready(function(){
            var validator = $("#files").validate({
                errorPlacement: function(error, element) {
                    error.appendTo(element.closest('div'));
                }
            });

            var file = $('input[name^="file"]');

            file.filter('input[name$="[name]"]').each(function() {
                $(this).rules("add", {
                    required: true,
                    messages: {
                        required: json_strings.validation.no_name
                    }
                });
            });

            file.filter('input[name$="[expiry_date]"]').each(function() {
                $(this).rules("add", {
                    required: true,
                    messages: {
                        required: json_strings.validation.no_expires
                    }
                });
            });

            file.filter('input[name$="[expires]"]').each(function() {
                $(this).rules("add", {
                    required: true,
                    messages: {
                        required: json_strings.validation.no_file_expires
                    }
                });
            });

            /*
            file.filter('input[name$="[public]"]').each(function() {
                $(this).rules("add", {
                    required: true,
                    messages: {
                        required: json_strings.validation.no_public
                    }
                });
            });
            */

            $('.copy-all').on('click', function(event) {
                bootbox.confirm({
                    message: json_strings.translations.upload_form.copy_selection,
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
                            var type = $(event.target).data('type');
                            var selector = $(event.target).closest('.file_data').find('.select-'+ type);

                            var selected = [];
                            $(selector).find('option:selected').each(function() {
                                selected.push($(this).val());
                            });

                            $('.select-'+ type).each(function() {
                                $(this).find('option').each(function() {
                                    if ($.inArray($(this).val(), selected) === -1) {
                                        $(this).prop('selected', false);
                                    }
                                    else {
                                        $(this).prop('selected', true);
                                    }
                                });
                            });
                            $('select').trigger('chosen:updated');
                        }
                    }
                });
                return false;
            });

            // Autoclick the continue button
            //$('#upload-continue').click();

            $('.create-client').on('click', function(event) {
                event.preventDefault();
                var trigger = event.target;
                var type = $(event.target).data('type');
                $.get("ajax/clients-add.php", function (data) {
                    var dialog = bootbox.dialog({
                        message: data,
                        title: "Create client",
                        closeButton: true,
                        size: 'large'
                    });
                    dialog.on('hidden.bs.modal', function() {
                        $(this).remove();
                    });
                    dialog.on('shown.bs.modal', function(event) {
                        admin.pages.clientForm();
                        admin.parts.passwordVisibilityToggle();
                        if ( $.isFunction($.fn.chosen) ) {
                            $(this).find('.chosen-select').chosen({
                                no_results_text	: json_strings.translations.no_results,
                                width			: "100%",
                                search_contains	: true
                            });
                        }
                        $(this).find('form').on('submit', function(event) {
                            event.preventDefault();
                            var form = $(this);
                            if (form.valid()) {
                                $.ajax({
                                    url: 'ajax/check_client.php',
                                    cache: false,
                                    data: {
                                        user_name: $('#name').val(),
                                        user_email: $('#email').val()
                                    },
                                    success: function (response) {
                                        if (response.exists === 'true') {
                                            var _formatted = sprintf(json_strings.translations.confirm_taken, response.owner);
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
                                                callback: function (result) {
                                                    if (result) {
                                                        form.closest('.white-box-interior').find('.alert').remove();
                                                        var formData = new FormData(form[0]);
                                                        formData.append('ajax', 'true');
                                                        formData.append('transfer', 'on');
                                                        $.ajax({
                                                            url: "clients-add.php",
                                                            type: "post",
                                                            data: formData,
                                                            processData: false,
                                                            contentType: false,
                                                            success: function (response) {
                                                                if (response.status === 'true') {
                                                                    var closest_select_id = $(trigger).closest('.file_data').find('.select-' + type).attr('id');
                                                                    $('.select-' + type).each(function () {
                                                                        if ($(this).attr('id') === closest_select_id) {
                                                                            $(this).append('<option value="' + response.client_id + '" selected>' + response.client_name + '</option>');
                                                                        } else {
                                                                            $(this).append('<option value="' + response.client_id + '">' + response.client_name + '</option>');
                                                                        }
                                                                    });
                                                                    $('.select-' + type).trigger("chosen:updated");
                                                                    form.closest('.bootbox').modal('hide');
                                                                } else if (response.status === 'false') {
                                                                    form.closest('.white-box-interior').prepend(response.message);
                                                                }
                                                            }
                                                        });
                                                    }
                                                }
                                            });
                                            e.preventDefault();
                                        } else {
                                            form.closest('.white-box-interior').find('.alert').remove();
                                            var formData = new FormData(form[0]);
                                            formData.append('ajax', 'true');
                                            $.ajax({
                                                url: "clients-add.php",
                                                type: "post",
                                                data: formData,
                                                processData: false,
                                                contentType: false,
                                                success: function (response) {
                                                    if (response.status === 'true') {
                                                        var closest_select_id = $(trigger).closest('.file_data').find('.select-' + type).attr('id');
                                                        $('.select-' + type).each(function () {
                                                            if ($(this).attr('id') === closest_select_id) {
                                                                $(this).append('<option value="' + response.client_id + '" selected>' + response.client_name + '</option>');
                                                            } else {
                                                                $(this).append('<option value="' + response.client_id + '">' + response.client_name + '</option>');
                                                            }
                                                        });
                                                        $('.select-' + type).trigger("chosen:updated");
                                                        form.closest('.bootbox').modal('hide');
                                                    } else if (response.status === 'false') {
                                                        form.closest('.white-box-interior').prepend(response.message);
                                                    }
                                                }
                                            });
                                        }
                                    }
                                });
                            }
                        });
                    });
                });
            });

            $('.create-group').on('click', function(event) {
                event.preventDefault();
                var trigger = event.target;
                var type = $(event.target).data('type');
                $.get("ajax/groups-add.php", function (data) {
                    var dialog = bootbox.dialog({
                        message: data,
                        title: "Create group",
                        closeButton: true,
                        size: 'large'
                    });
                    dialog.on('hidden.bs.modal', function() {
                        $(this).remove();
                    });
                    dialog.on('shown.bs.modal', function(event) {
                        admin.pages.groupForm();
                        if ( $.isFunction($.fn.chosen) ) {
                            $(this).find('.chosen-select').chosen({
                                no_results_text	: json_strings.translations.no_results,
                                width			: "100%",
                                search_contains	: true
                            });
                        }
                        if ( typeof CKEDITOR !== "undefined" ) {
                            CKEDITOR.replace('description');
                            for (var i in CKEDITOR.instances) {
                                (function(i) {
                                    CKEDITOR.instances[i].on('change', function() { CKEDITOR.instances[i].updateElement() });
                                })(i);
                            }
                        }
                        $(this).find('form').submit(function(event) {
                            event.preventDefault();
                            var form = $(this);
                            if (form.valid()) {
                                form.closest('.white-box-interior').find('.alert').remove();
                                var formData = new FormData(form[0]);
                                formData.append('ajax', 'true');
                                $.ajax({
                                    url: "groups-add.php",
                                    type: "post",
                                    data: formData,
                                    processData: false,
                                    contentType: false,
                                    success: function (response) {
                                        if (response.status === 'true') {
                                            var closest_select_id = $(trigger).closest('.file_data').find('.select-' + type).attr('id');
                                            $('.select-' + type).each(function () {
                                                if ($(this).attr('id') === closest_select_id) {
                                                    $(this).append('<option value="' + response.group_id + '" selected>' + response.group_name + '</option>');
                                                } else {
                                                    $(this).append('<option value="' + response.group_id + '">' + response.group_name + '</option>');
                                                }
                                            });
                                            $('.select-' + type).trigger("chosen:updated");
                                            form.closest('.bootbox').modal('hide');
                                        } else if (response.status === 'false') {
                                            form.closest('.white-box-interior').prepend(response.message);
                                        }
                                    }
                                });
                            }
                        });
                    });
                });
            });

        });
    };
})();