(function () {
  'use strict';

  admin.pages.clientsAccountsRequests = function () {
    $(document).ready(
      function () {
        $('.change_all').on('click', function (e) {
          e.preventDefault();
          var target = $(this).data('target');
          var check = $(this).data('check');
          $("input[data-client='" + target + "']").prop('checked', check).change();
          checkClient(target);
        });

        $('.account_action').on('change', function () {
          if ($(this).prop('checked') === false) {
            var target = $(this).data('client');
            $(".membership_action[data-client='" + target + "']").prop('checked', false).change();
          }
        });

        $('.checkbox_toggle').change(
          function () {
            var target = $(this).data('client');
            checkClient(target, $(this).prop('checked'));
          }
        );

        function checkClient (clientId, checked) {
          $("input[data-clientid='" + clientId + "']").prop('checked', checked);
        }
      }
    );
  };
})();
