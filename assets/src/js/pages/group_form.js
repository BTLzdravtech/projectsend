(function () {
    'use strict';

    admin.pages.groupForm = function () {

        $(document).ready(function(){
            var validator = $("#group_form").validate({
                rules: {
                    name: {
                        required: true
                    },
                    description: {
                        required: true
                    }
                },
                messages: {
                    name: {
                        required: json_strings.validation.no_name
                    },
                    description: {
                        required: json_strings.validation.no_description
                    }
                },
                errorPlacement: function(error, element) {
                    error.appendTo(element.closest('div'));
                }
            });
        });
    };
})();