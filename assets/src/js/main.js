(function () {
    'use strict';

    $(document).ready(function() {
        admin.parts.bulkActions();
        admin.parts.main();
        admin.parts.jqueryValidationCustomMethods();
        admin.parts.passwordVisibilityToggle();

        // Switch pages
        switch ($("body").data("page-id")) {
            case 'install':
                admin.pages.install();
                break;
            case 'login':
                admin.pages.loginForm();
                break;
            case 'dashboard':
                admin.pages.dashboard();
                break;
            case 'categories_list':
                admin.pages.categoriesAdmin();
                break;
            case 'clients_memberships_requests':
                admin.pages.clientsAccountsRequests();
                break;
            case 'clients_accounts_requests':
                admin.pages.clientsAccountsRequests();
                break;
            case 'new_uploads_editor':
                admin.pages.newUploadsEditor();
                break;
            case 'file_editor':
                admin.pages.fileEditor();
                break;
            case 'client_form':
                admin.pages.clientForm();
                break;
            case 'user_form':
                admin.pages.userForm();
                break;
            case 'group_form':
                admin.pages.groupForm();
                break;
            case 'email_templates':
                admin.pages.emailTemplates();
                break;
            case 'reset_password_enter_email':
                admin.pages.resetPasswordEnterEmail();
                break;
            case 'reset_password_enter_new':
                admin.pages.resetPasswordEnterNew();
                break;
            case 'upload_form':
                admin.pages.uploadForm();
                break;
            case 'import_orphans':
                admin.pages.importOrphans();
                break;
            case 'options':
                admin.pages.options();
                break;
            default:
                // do nothing
                break;
        }
    });
})();