(function () {
  'use strict';

  admin.pages.categoriesAdmin = function () {
    $(document).ready(
      function () {
        $('#process_category').validate(
          {
            rules: {
              category_name: {
                required: true
              }
            },
            messages: {
              category_name: {
                required: json_strings.validation.no_name
              }
            },
            errorPlacement: function (error, element) {
              error.appendTo(element.closest('div'));
            }
          }
        );
      }
    );
  };
})();
