<?php
/**
 * Show the form to add a new client.
 *
 * @package		ProjectSend
 * @subpackage	Clients
 *
 */
$allowed_levels = array(9,8);
require_once '../bootstrap.php';

$active_nav = 'clients';

$page_id = 'client_form';

global $dbh;

$new_client = new \ProjectSend\Classes\Users($dbh);

/**
 * Set checkboxes as 1 to default them to checked when first entering
 * the form
 */
$client_arguments = array(
    'notify_upload'     => 1,
    'active'            => 1,
    'notify_account'    => 1,
);

?>

<div class="white-box ajax">
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
                        $msg = __('There was an error. Please try again.','cftp_admin');
                        echo system_message('danger',$msg);
                    break;
                }
            }
            else {
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
