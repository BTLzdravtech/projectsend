(function () {
  'use strict';

  admin.pages.clientForm = function () {
    $(document).ready(
      function () {
        var formType = $('#client_form').data('form-type');
        $('#client_form').validate(
          {
            rules: {
              name: {
                required: true
              },
              username: {
                required: true,
                minlength: json_strings.character_limits.user_min,
                maxlength: json_strings.character_limits.user_max,
                alphanumericUsername: true
              },
              email: {
                required: true,
                email: true
              },
              max_file_size: {
                required: {
                  param: true,
                  depends: function (element) {
                    return formType !== 'new_client_self';
                  }
                },
                digits: true
              },
              password: {
                required: {
                  param: true,
                  depends: function (element) {
                    if (formType === 'new_client' || formType === 'new_client_self') {
                      return true;
                    }
                    if (formType === 'edit_client' || formType === 'edit_client_self') {
                      if ($.trim($('#password').val()).length > 0) {
                        return true;
                      }
                    }
                    return false;
                  }
                },
                minlength: json_strings.character_limits.password_min,
                maxlength: json_strings.character_limits.password_max,
                passwordValidCharacters: true
              }
            },
            messages: {
              name: {
                required: json_strings.validation.no_name
              },
              username: {
                required: json_strings.validation.no_user,
                minlength: json_strings.validation.length_user,
                maxlength: json_strings.validation.length_user,
                alphanumericUsername: json_strings.validation.alpha_user
              },
              email: {
                required: json_strings.validation.no_email,
                email: json_strings.validation.invalid_email
              },
              max_file_size: {
                digits: json_strings.validation.file_size
              },
              password: {
                required: json_strings.validation.no_pass,
                minlength: json_strings.validation.length_pass,
                maxlength: json_strings.validation.length_pass,
                passwordValidCharacters: json_strings.validation.alpha_pass
              }
            },
            errorPlacement: function (error, element) {
              if (element.attr('id') === 'password') {
                error.insertAfter(element.closest('div'));
              } else {
                error.appendTo(element.closest('div'));
              }
            }
          }
        );

        if (!$('#client_form').closest('.white-box').hasClass('ajax')) {
          $('#client_form').on('submit', function (e) {
            if ($('#client_form').valid()) {
              $.ajax(
                {
                  url: 'ajax/check_client.php',
                  cache: false,
                  data: {
                    user_name: $('#name').val(),
                    user_email: $('#email').val()
                  },
                  success: function (response) {
                    if (response.exists === 'true') {
                      // eslint-disable-next-line no-undef
                      var _formatted = sprintf(json_strings.translations.confirm_taken, response.owner);
                      bootbox.confirm(
                        {
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
                              $('#client_form').append("<input type='hidden' name='transfer' value='on'>");
                              $('#client_form').unbind('submit');
                              $('#client_form').find('button[type="submit"]').click();
                            }
                          }
                        }
                      );
                      e.preventDefault();
                    } else {
                      $('#client_form').unbind('submit');
                      $('#client_form').find('button[type="submit"]').click();
                    }
                  }
                }
              );
            }
            e.preventDefault();
          });
        }
      }
    );
  };
})();
