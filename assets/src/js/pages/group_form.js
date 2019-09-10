(function () {
    'use strict';

    admin.pages.groupForm = function () {

        $(document).ready(function(){
            CKEDITOR.on("instanceReady", function(event)
            {
                $(".cke_top").addClass("required_ck_editor");
            });
            var validator = $("#group_form").validate({
                ignore: [],
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