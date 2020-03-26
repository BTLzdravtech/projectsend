(function () {
  'use strict'

  admin.pages.fileEditor = function () {
    $(document).ready(function () {
      $('#edit_file').validate({
        errorPlacement: function (error, element) {
          error.appendTo(element.closest('div'))
        }
      })

      var file = $('input[name^="file"]')

      file.filter('input[name$="[name]"]').each(function () {
        $(this).rules('add', {
          required: true,
          messages: {
            required: json_strings.validation.no_name
          }
        })
      })

      file.filter('input[name$="[expiry_date]"]').each(function () {
        $(this).rules('add', {
          required: true,
          messages: {
            required: json_strings.validation.no_expires
          }
        })
      })

      file.filter('input[name$="[expires]"]').each(function () {
        $(this).rules('add', {
          required: true,
          messages: {
            required: json_strings.validation.no_file_expires
          }
        })
      })

      /*
            file.filter('input[name$="[public]"]').each(function () {
                $(this).rules("add", {
                    required: true,
                    messages: {
                        required: json_strings.validation.no_public
                    }
                });
            });
            */
    })
  }
})()
