<?php
/**
 * Show the form to add a new group.
 *
 * @package    ProjectSend
 * @subpackage Groups
 */

use ProjectSend\Classes\Groups;

$allowed_levels = array(9, 8);
require_once '../bootstrap.php';

global $dbh;

$active_nav = 'groups';

$page_title = __('Add clients group', 'cftp_admin');

$page_id = 'group_form';

$new_group = new Groups($dbh);

?>

<div class="white-box">
    <div class="white-box-interior">

        <?php
        // If the form was submited with errors, show them here.
        echo $new_group->getValidationErrors();

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
            $groups_form_type = 'new_group';
            include_once FORMS_DIR . DS . 'groups.php';
        }
        ?>

    </div>
</div>
