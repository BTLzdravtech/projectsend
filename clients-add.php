<?php
/**
 * Show the form to add a new client.
 *
 * @package    ProjectSend
 * @subpackage Clients
 */

use ProjectSend\Classes\ActionsLog;
use ProjectSend\Classes\MembersActions;
use ProjectSend\Classes\Users;

$allowed_levels = array(9, 8);
require_once 'bootstrap.php';

/**
 * @var PDO $dbh
 */
global $dbh;

$active_nav = 'clients';

$page_title = __('Add client', 'cftp_admin');

$page_id = 'client_form';

$new_client = new Users($dbh);

if (!isset($_POST['ajax'])) {
    include_once ADMIN_VIEWS_DIR . DS . 'header.php';
}

/**
 * Set checkboxes as 1 to default them to checked when first entering
 * the form
 */
$client_arguments = array(
    'notify_upload' => 1,
    'active' => 1,
    'notify_account' => 1,
);

if ($_POST) {
    /**
     * Clean the posted form values to be used on the clients actions,
     * and again on the form if validation failed.
     */
    $client_arguments = array(
        'username' => $_POST['username'],
        'password' => $_POST['password'],
        'name' => $_POST['name'],
        'email' => $_POST['email'],
        'max_file_size' => (isset($_POST["max_file_size"])) ? $_POST['max_file_size'] : '',
        'notify_upload' => (isset($_POST["notify_upload"])) ? 1 : 0,
        'notify_account' => (isset($_POST["notify_account"])) ? 1 : 0,
        'active' => (isset($_POST["active"])) ? 1 : 0,
        'type' => 'new_client',
    );

    if (!isset($_POST['transfer'])) {
        /**
         * Validate the information from the posted form.
         */
        /**
         * Create the user if validation is correct.
         */
        $new_client->setType('new_client');
        $new_client->set($client_arguments);

        if ($new_client->validate()) {
            $new_response = $new_client->create();

            $add_to_groups = (!empty($_POST['groups_request'])) ? $_POST['groups_request'] : '';
            if (!empty($add_to_groups)) {
                array_map('encode_html', $add_to_groups);
                $memberships = new MembersActions;
                $arguments = array(
                    'client_id' => $new_client->getId(),
                    'group_ids' => $add_to_groups,
                    'added_by' => CURRENT_USER_USERNAME,
                );

                $memberships->client_add_to_groups($arguments);
            }

            if (!empty($new_response['id'])) {
                if ($_POST['ajax']) {
                    header('Content-Type: application/json');
                    echo json_encode(array('status' => 'true', 'client_id' => $new_response['id'], 'client_name' => $new_response['name']));
                    exit;
                } else {
                    $redirect_to = BASE_URI . 'clients-edit.php?id=' . $new_response['id'] . '&status=' . $new_response['query'] . '&is_new=1&notification=' . $new_response['email'];
                    header('Location:' . $redirect_to);
                    exit;
                }
            }
        } else {
            if ($_POST['ajax']) {
                header('Content-Type: application/json');
                echo json_encode(array('status' => 'false', 'message' => $new_client->getValidationErrors()));
                exit;
            }
        }
    } else {
        $client = get_user_by('client', 'email', $client_arguments['email']);
        $transferred_from_id = $client['owner_id'];

        // remove client from all groups
        $statement = $dbh->prepare("DELETE FROM " . TABLE_MEMBERS . " WHERE client_id = :client_id");
        $statement->execute(array(':client_id' => $client['id']));

        // change owner of client files to old owner before transfer

        $statement = $dbh->prepare("UPDATE " . TABLE_FILES . " SET owner_id = :owner_id WHERE owner_id = :client_id");
        $statement->execute(array(':owner_id' => $transferred_from_id, 'client_id' => $client['id']));

        // change client owner_id
        $statement = $dbh->prepare("UPDATE " . TABLE_USERS . " SET owner_id = :owner_id WHERE id = :id");
        $result = $statement->execute(array(':owner_id' => CURRENT_USER_ID, 'id' => $client['id']));
        $logger = new ActionsLog;
        $logger->addEntry(
            [
                'action' => 41,
                'owner_id' => $transferred_from_id,
                'affected_account' => $client['id'],
                'affected_account_name' => $client['name'],
            ]
        );
        if (!isset($_POST['ajax'])) {
            $redirect_to = BASE_URI . 'clients-edit.php?id=' . $client['id'] . '&status=' . ($result ? 1 : 0);
            header('Location:' . $redirect_to);
            exit;
        } else {
            header('Content-Type: application/json');
            echo json_encode(array('status' => 'true', 'client_id' => $client['id'], 'client_name' => $client['name']));
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
                echo $new_client->getValidationErrors();

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
                    $clients_form_type = 'new_client';
                    include_once FORMS_DIR . DS . 'clients.php';
                }
                ?>
            </div>
        </div>
    </div>

<?php
require_once ADMIN_VIEWS_DIR . DS . 'footer.php';
