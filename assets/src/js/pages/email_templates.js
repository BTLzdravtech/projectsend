(function () {
  'use strict';

  admin.pages.emailTemplates = function () {
    $(document).ready(
      function () {
        $('.load_default').on('click', function (e) {
          e.preventDefault();

          var file = jQuery(this).data('file');
          var textarea = jQuery(this).data('textarea');
          bootbox.confirm(
            {
              message: json_strings.translations.email_templates.confirm_replace,
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
                  $.ajax(
                    {
                      url: 'emails/' + file,
                      cache: false,
                      success: function (data) {
                        $('#' + textarea).val(data);
                      },
                      error: function () {
                        var alert = bootbox.alert(
                          {
                            message: json_strings.translations.email_templates.loading_error,
                            buttons: {
                              ok: {
                                label: json_strings.modal.ok
                              }
                            }
                          }
                        );
                        alert.on('shown.bs.modal', function () {
                          $('body').addClass('modal-open');
                        });
                      }
                    }
                  );
                }
              }
            }
          );
        });

        $('.preview').on('click', function (e) {
          e.preventDefault();
          var type = jQuery(this).data('preview');
          var url = json_strings.uri.base + 'email-preview.php?t=' + type;
          window.open(url, 'previewWindow', 'width=800,height=600,scrollbars=yes');
        });
      }
    );
  };
})();
