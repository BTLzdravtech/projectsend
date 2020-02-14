<?php
/**
 * Show the form to add a new workspace.
 *
 * @package    ProjectSend
 * @subpackage Workspaces
 */

use ProjectSend\Classes\Workspaces;

$allowed_levels = array(9,8);
require_once 'bootstrap.php';

global $dbh;

$active_nav = 'workspaces';

$page_title = __('Add users workspace', 'cftp_admin');

$page_id = 'workspace_form';

$new_workspace = new Workspaces($dbh);

if (!isset($_POST['ajax'])) {
    include_once ADMIN_VIEWS_DIR . DS . 'header.php';
}

if ($_POST) {
    /**
     * Clean the posted form values to be used on the workspaces actions,
     * and again on the form if validation failed.
     */
    $workspace_arguments = [
        'name' => $_POST['name'],
        'description' => $_POST['description'],
        'admins' => (!empty($_POST['admins'])) ? $_POST['admins'] : null,
        'users' => (!empty($_POST['users'])) ? $_POST['users'] : null,
    ];

    /**
     * Validate the information from the posted form.
    */
    $new_workspace->set($workspace_arguments);
    if ($new_workspace->validate()) {
        $new_response = $new_workspace->create();

        if (!empty($new_response['id'])) {
            if ($_POST['ajax']) {
                header('Content-Type: application/json');
                echo json_encode(array('status' => 'true', 'workspace_id' => $new_response['id'], 'workspace_name' => $new_response['name']));
                exit;
            } else {
                $rediret_to = BASE_URI . 'workspaces-edit.php?id=' . $new_response['id'] . '&status=' . $new_response['query'] . '&is_new=1';
                header('Location:' . $rediret_to);
                exit;
            }
        }
    } else {
        if ($_POST['ajax']) {
            header('Content-Type: application/json');
            echo json_encode(array('status' => 'false', 'message' => $new_workspace->getValidationErrors()));
            exit;
        }
    }
}
?>
<div class="col-xs-12 col-sm-12 col-lg-6">
    <div class="white-box">
        <div class="white-box-interior">

            <?php
                // If the form was submited with errors, show them here.
                echo $new_workspace->getValidationErrors();

            if (isset($new_response)) {
                /**
                 * Get the process state and show the corresponding ok or error messages.
                 */
                switch ($new_response['query']) {
                    case 0:
                        $msg = __('There was an error. Please try again.', 'cftp_admin');
                        echo system_message('danger', $msg);
                        break;
                }
            } else {
                /**
                 * If not $new_response is set, it means we are just entering for the first time.
                 * Include the form.
                 */
                $workspaces_form_type = 'new_workspace';
                include_once FORMS_DIR . DS . 'workspaces.php';
            }
            ?>

        </div>
    </div>
</div>

<?php
    require_once ADMIN_VIEWS_DIR . DS . 'footer.php';
