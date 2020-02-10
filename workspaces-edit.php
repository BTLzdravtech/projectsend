<?php
/**
 * Show the form to edit an existing workspace.
 *
 * @package    ProjectSend
 * @subpackage Workspaces
 */

use ProjectSend\Classes\Workspaces;

$allowed_levels = array(9,8);
require_once 'bootstrap.php';

global $dbh;

$active_nav = 'workspaces';

$page_title = __('Edit workspace', 'cftp_admin');

$page_id = 'workspace_form';

require_once ADMIN_VIEWS_DIR . DS . 'header.php';

/**
 * Create the object
*/
$edit_workspace = new Workspaces($dbh);

/**
 * Check if the id parameter is on the URI.
*/
if (isset($_GET['id'])) {
    $workspace_id = $_GET['id'];
    /**
     * Check if the id corresponds to a real workspace.
     * Return 1 if true, 2 if false.
     **/
    $page_status = (workspace_exists_id($workspace_id)) ? 1 : 2;
} else {
    /**
     * Return 0 if the id is not set.
     */
    $page_status = 0;
}

/**
 * Get the workspace information from the database to use on the form.
 *
 * @todo replace when a Workspace class is made
 */
if ($page_status === 1) {
    $edit_workspace->get($workspace_id);
    $workspace_arguments = $edit_workspace->getProperties();
}

if ($_POST) {
    /**
     * Clean the posted form values to be used on the workspaces actions,
     * and again on the form if validation failed.
     * Also, overwrites the values gotten from the database so if
     * validation failed, the new unsaved values are shown to avoid
     * having to type them again.
     */
    $workspace_arguments = array(
        'id' => $workspace_id,
        'name' => $_POST['name'],
        'description' => $_POST['description'],
        'admins' => (!empty($_POST["admins"])) ? $_POST['admins'] : [],
        'users' => (!empty($_POST["users"])) ? $_POST['users'] : null,
    );

    /**
     * Validate the information from the posted form.
    */
    $edit_workspace->set($workspace_arguments);
    if ($edit_workspace->validate()) {
        $edit_response = $edit_workspace->edit();

        $location = BASE_URI . 'workspaces-edit.php?id=' . $workspace_id . '&status=' . $edit_response['query'];
        header("Location: $location");
        die();
    }
}
?>

<div class="col-xs-12 col-sm-12 col-lg-6">
    <?php
    /**
     * Get the process state and show the corresponding ok or error message.
     */
    if (isset($_GET['status'])) {
        switch ($_GET['status']) {
            case 1:
                $msg = __('Workspace edited correctly.', 'cftp_admin');
                if (isset($_GET['is_new'])) {
                    $msg = __('Workspace created successfuly.', 'cftp_admin');
                }

                echo system_message('success', $msg);
                break;
            case 0:
                $msg = __('There was an error. Please try again.', 'cftp_admin');
                echo system_message('danger', $msg);
                break;
        }
    }
    ?>

    <div class="white-box">
        <div class="white-box-interior">
            <?php
            // If the form was submited with errors, show them here.
            echo $edit_workspace->getValidationErrors();

            $direct_access_error = __('This page is not intended to be accessed directly.', 'cftp_admin');
            if ($page_status === 0) {
                $msg = __('No workspace was selected.', 'cftp_admin');
                echo system_message('danger', $msg);
                echo '<p>'.$direct_access_error.'</p>';
            } elseif ($page_status === 2) {
                $msg = __('There is no workspace with that ID number.', 'cftp_admin');
                echo system_message('danger', $msg);
                echo '<p>'.$direct_access_error.'</p>';
            } else {
                /**
                 * Include the form.
                 */
                $workspaces_form_type = 'edit_workspace';
                include_once FORMS_DIR . DS . 'workspaces.php';
            }
            ?>
        </div>
    </div>
</div>

<?php
    require_once ADMIN_VIEWS_DIR . DS . 'footer.php';
