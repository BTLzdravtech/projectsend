<?php
use ProjectSend\Classes\Emails;

require_once 'bootstrap.php';

/** @var PDO $dbh */
global $dbh;

$file_data = array();
$mail_by_user = array();

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
        $file_data[$row['user']][] = array(
            'client_id' => $row['client_id'],
            'filename' => $row['filename'],
            'user' => $row['user'],
            'email' => $row['email'],
            'expiry_date' => $row['expiry_date']
        );
        $mail_by_user[$row['user']] = $row['email'];
    }
}

if (count($file_data) > 0) {
    foreach ($file_data as $mail_username => $mail_files) {
        $files_list = '';
        foreach ($mail_files as $mail_file) {
            $files_list .= '<li style="margin-bottom:11px;">';
            $files_list .= '<p style="font-weight:bold; margin:0 0 5px 0; font-size:14px;">' . $mail_file['filename'] . '</p>';
            if (!empty($mail_file['expiry_date'])) {
                $files_list .= '<p>' . $mail_file['expiry_date'] . '</p>';
            }
            $files_list .= '</li>';
        }

        $address = $mail_by_user[$mail_username];
        $notifier = new Emails;
        $email_arguments_admin = array(
            'type' => 'limit_retention',
            'address' => $address,
            'files_list' => $files_list
        );
        $notifier->send( $email_arguments_admin);
    }
}
