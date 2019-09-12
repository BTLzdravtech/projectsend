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

            $('.copy-all').click(function(event) {
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

                            var selected = new Array();
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

        });
    };
})();