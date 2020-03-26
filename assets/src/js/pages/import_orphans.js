(function () {
    'use strict';

    admin.pages.importOrphans = function () {

        $(document).ready(
            function () {
                var fading;

                $("#import_orphans").validate(
                    {
                        rules: {
                            "add[]": {
                                required: true,
                                minlength: 1
                            }
                        },
                        showErrors: function (errorMap, errorList) {
                            var alert_info = $('form').find('.alert-info');
                            var alert_danger = $('form').find('.alert-danger');
                            var errors = this.numberOfInvalids();
                            if (errors) {
                                if (fading !== undefined) {
                                    clearTimeout(fading);
                                }
                                $(alert_info).hide();
                                $(alert_danger).hide();
                                $(alert_danger).fadeIn('slow');
                                fading = setTimeout(function () {
                                    alert_info.hide();
                                    alert_danger.hide();
                                    alert_info.fadeIn("slow");
                                }, 3000);
                            } else {
                                if (fading !== undefined) {
                                    clearTimeout(fading);
                                }
                                $(alert_info).hide();
                                $(alert_danger).hide();
                                $(alert_info).fadeIn('slow');
                            }
                        }
                    }
                );

                /**
                 * Only select the current file when clicking an "edit" button
                 */
                $('.btn-edit-file').on('click', function (e) {
                    $('#select_all').prop('checked', false);
                    $('td .select_file_checkbox').prop('checked', false);
                    $(this).parents('tr').find('td .select_file_checkbox').prop('checked', true);
                    $('#upload-continue').click();
                });
            }
        );
    };
})();