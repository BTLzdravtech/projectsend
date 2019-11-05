<?php
/**
 * Search the database for unsent notifications and email them.
 *
 * @package		ProjectSend
 * @subpackage	Upload
 *
 */

/** This file MUST be included by another one */
require_once 'bootstrap.php';

//prevent_direct_access();
global $dbh;
$allowed_levels = array(9, 8, 0);
$notifications_sent = array();

/**
* Continue if there are notifications to be sent.
*/
$statement = $dbh->prepare("SELECT F.filename as filename, F.id as client_id, U.id as user, U.email as email, DATE(F.expiry_date) as expiry_date 
                            FROM btl_files F
                            LEFT JOIN btl_downloads D
                            ON F.id = D.file_id  
                            INNER JOIN btl_users U
                            ON F.owner_id = U.id  
                            WHERE D.id IS NULL AND (DATE(expiry_date) - DATE(NOW())) = 2									
                            
                        ");

$statement->execute();
if ( $statement->rowCount() > 0 ) {
    $statement->setFetchMode(PDO::FETCH_ASSOC);
    while ($row = $statement->fetch()) {
        $file_data[$row['user']] = array(
            'client_id' => $row['client_id'],
            'filename' => $row['filename'],
            'user' => $row['user'],
            'email' => $row['email'],
            'expiry_date' => $row['expiry_date']
        );
        $mail_by_user[$row['user']] = $row['email'];
    }
}

/** Prepare the emails for CLIENTS */

foreach ($file_data as $mail_username => $mail_files) {
    /** Reset the files list UL contents */
    $files_list = '';
    foreach ($mail_files as $mail_file) {
        /** Make the list of files */
        $files_list .= '<li style="margin-bottom:11px;">';
        $files_list .= '<p style="font-weight:bold; margin:0 0 5px 0; font-size:14px;">' . $mail_file['filename'] . '</p>';
        if (!empty($mail_file['expiry_date'])) {
            $files_list .= '<p>' . $mail_file['expiry_date'] . '</p>';
        }
        $files_list .= '</li>';
    }

    $address = $mail_by_user[$mail_username];
    /** Create the object and send the email */
    $notify_admin = new \ProjectSend\Classes\Emails;
    $email_arguments_admin = array(
        'type' => 'limit_retention',
        'address' => $address,
        'files_list' => $files_list
    );
    $try_sending = $notify_admin->send( $email_arguments_admin);
    if ($try_sending == 1) {
        $notifications_sent = array($notifications_sent);
    }
    else {
        $notifications_failed = array($notifications_failed);
    }
}

if (count($notifications_sent) > 0) {
    $notifications_sent = implode(',',array_unique($notifications_sent));
    $statement = $dbh->prepare("UPDATE " . TABLE_NOTIFICATIONS . " SET sent_status = '1' WHERE FIND_IN_SET(id, :sent)");
    $statement->bindParam(':sent', $notifications_sent);
    $statement->execute();

    $msg = __('E-mail notifications have been sent.','cftp_admin');
    echo system_message('success',$msg);
}

if (count($notifications_failed) > 0) {
    $notifications_failed = implode(',',array_unique($notifications_failed));
    $statement = $dbh->prepare("UPDATE " . TABLE_NOTIFICATIONS . " SET sent_status = '0', times_failed = times_failed + 1 WHERE FIND_IN_SET(id, :failed)");
    $statement->bindParam(':failed', $notifications_failed);
    $statement->execute();

    $msg = __("One or more notifications couldn't be sent.",'cftp_admin');
    echo system_message('danger',$msg);
}
