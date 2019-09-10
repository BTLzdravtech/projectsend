(function () {
    'use strict';

    admin.pages.importOrphans = function () {

        $(document).ready(function(){
			$("#import_orphans").validate({
				rules: {
					"add[]": {
						required: true,
						minlength: 1
					}
				},
				messages: {
					"add[]": {
						required: json_strings.validation.one_checkbox
					}
				},
				errorPlacement: function(error, element) {
					$('<div>', { class: 'alert alert-danger'}).append(error).insertAfter(element.closest('form').find('.alert-info'));
				}
			});

			$('input[name="add[]"]').on('change', function() {
				$(this).closest('form').find('.alert-danger').remove();
			});

			/**
			 * Only select the current file when clicking an "edit" button
			 */
			$('.btn-edit-file').click(function(e) {
				$('#select_all').prop('checked', false);
				$('td .select_file_checkbox').prop('checked', false);
				$(this).parents('tr').find('td .select_file_checkbox').prop('checked', true);
				$('#upload-continue').click();
			});
        });
    };
})();