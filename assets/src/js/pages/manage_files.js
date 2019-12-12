(function () {
    'use strict';

    admin.pages.manageFiles = function () {

        $(document).ready(function(e) {
            $(".delete-button").on('click', function(e) {
                var button = $(this);
                var _formatted = sprintf(json_strings.translations.confirm_delete, 1);
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
                            window.location.href = button.attr('href');
                        }
                    }
                });
                e.preventDefault();
            });
        });
    };
})();
