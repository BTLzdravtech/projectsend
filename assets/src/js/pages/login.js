(function () {
  'use strict'

  admin.pages.loginForm = function () {
    $(document).ready(
      function () {
        $('#login_form').validate(
          {
            rules: {
              username: {
                required: true
              },
              password: {
                required: true
              }
            },
            messages: {
              username: {
                required: json_strings.validation.no_user
              },
              password: {
                required: json_strings.validation.no_pass
              }
            },
            errorPlacement: function (error, element) {
              error.appendTo(element.closest('div'))
            },
            submitHandler: function (form) {
              var buttonText = json_strings.login.buttonText
              var buttonLoadingText = json_strings.login.logging_in
              var buttonRedirectingText = json_strings.login.redirecting

              var url = $(form).attr('action')
              $('.ajax_response').html('').removeClass('alert-danger alert-success').slideUp()
              $('#submit').html('<i class="fa fa-cog fa-spin fa-fw"></i><span class="sr-only"></span> ' + buttonLoadingText + '...')
              $.ajax(
                {
                  cache: false,
                  type: 'post',
                  url: url,
                  data: $(form).serialize(),
                  success: function (response) {
                    var json = jQuery.parseJSON(response)
                    if (json.status === 'success') {
                      $('#submit').html('<i class="fa fa-check"></i><span class="sr-only"></span> ' + buttonRedirectingText + '...')
                      $('#submit').removeClass('btn-primary').addClass('btn-success')
                      // eslint-disable-next-line no-implied-eval
                      setTimeout('window.location.href = "' + json.location + '"', 1000)
                    } else {
                      $('.ajax_response').addClass('alert-danger').slideDown().html(json.message)
                      $('#submit').html(buttonText)
                    }
                  }
                }
              )

              return false
            }
          }
        )
      }
    )
  }
})()
