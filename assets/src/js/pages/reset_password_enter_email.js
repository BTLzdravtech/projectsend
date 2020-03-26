(function () {
  'use strict'

  admin.pages.resetPasswordEnterEmail = function () {
    $(document).ready(
      function () {
        $('#reset_password_enter_email').validate(
          {
            rules: {
              email: {
                required: true,
                email: true
              }
            },
            messages: {
              email: {
                required: json_strings.validation.no_email,
                email: json_strings.validation.invalid_email
              }
            },
            errorPlacement: function (error, element) {
              error.appendTo(element.closest('div'))
            }
          }
        )
      }
    )
  }
})()
