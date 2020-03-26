(function () {
  'use strict';

  admin.pages.options = function () {
    $(document).ready(function () {
      $('#allowed_file_types')
        .tagify()
        .on('add', function (e, tagName) {
        });

      $('#ldap_signin_enabled, #google_signin_enabled').on('change', function () {
        if (this.value === '1') {
          $(this).closest('div.options_column').find('input').prop('required', true).addClass('required');
        } else {
          $(this).closest('div.options_column').find('input').prop('required', false).removeClass('required');
        }
      });

      $('#options').validate({
        errorPlacement: function (error, element) {
          error.appendTo(element.closest('div'));
        }
      });
    });
  };
})();
