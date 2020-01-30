<?php

use ProjectSend\Classes\ActionsLog;
use ProjectSend\Classes\Users;

require_once '../bootstrap.php';

$googleClient = getGoogleLoginClient();
$oauth2 = new Google_Service_Oauth2($googleClient);

if (isset($_GET['error'])) {
    if ($_GET['error'] == 'access_denied') {
        $_SESSION['errorstate'] = 'access_denied';
        header("location:" . BASE_URI);
        return;
    }
    $_SESSION['errorstate'] = 'invalid_credentials';
    header("location:" . BASE_URI);
    return;
}

if (isset($_GET['code'])) {
    $token = $googleClient->fetchAccessTokenWithAuthCode($_GET['code']);
    $googleClient->setAccessToken($token['access_token']);
    $_SESSION['id_token_token'] = $token;
}

if (isset($_SESSION['id_token_token']) && isset($_SESSION['id_token_token']['id_token'])) {
    $ticket = $googleClient->verifyIdToken($_SESSION['id_token_token']['id_token']);
    if ($ticket) {
        if (!isset($_SESSION['google_user'])) {
            $userData = $oauth2->userinfo->get();
            $email = $userData->email;
        } else {
            $email = $_SESSION['google_user']['email'];
            unset($_SESSION['google_user']);
        }

        global $dbh;
        $statement = $dbh->prepare("SELECT * FROM " . TABLE_USERS . " WHERE email= :email");
        $statement->execute(array(':email' => $email));

        $count_user = $statement->rowCount();
        if ($count_user > 0) {
            /** If the username was found on the users table */
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            while ($row = $statement->fetch()) {
                $sysuser_username = $row['user'];
                $user_level = $row["level"];
                $active_status = $row['active'];
                $logged_id = $row['id'];
                $global_name = $row['name'];
            }
            if ($active_status != '0') {
                /** Set SESSION values */
                $_SESSION['loggedin'] = $sysuser_username;
                $_SESSION['userlevel'] = $user_level;

                if ($user_level != '0') {
                    $access_string = 'admin';
                    $_SESSION['access'] = $access_string;
                } else {
                    $access_string = $sysuser_username;
                    $_SESSION['access'] = $sysuser_username;
                }

                /** Record the action log */
                $logger = new ActionsLog();
                $log_action_args = array(
                    'action' => 1,
                    'owner_id' => $logged_id,
                    'affected_account_name' => $global_name
                );
                $new_record_action = $logger->addEntry($log_action_args);

                if ($user_level == '0') {
                    header("location:" . BASE_URI . "upload-from-computer.php");
                } else {
                    header("location:" . BASE_URI . "upload-from-computer.php");
                }
                exit;
            } else {
                $_SESSION['errorstate'] = 'invalid_credentials';
            }
        } else {
            $_SESSION['errorstate'] = 'no_account';
            $new_user = new Users($dbh);
            $username = generateUsername($userData['email']);

            $clientData = array(
                'username' => $username,
                'password' => '',
                'name' => $userData['name'],
                'email' => $userData['email'],
                'role' => '8',
                'max_file_size' => '',
                'notify_account' => '0',
                'notify_upload' => '1',
                'active' => '1',
                'google_user' => '1',
            );

            $new_user->setType('new_google_user');
            $new_user->set($clientData);
            if ($new_user->validate()) {
                $_SESSION['google_user'] = $userData;
                $new_user->create();
                header("location:" . BASE_URI . "google/callback.php");
                return;
            }
        }
    }
    $_SESSION['errorstate'] = 'invalid_credentials';
    header("location:" . BASE_URI);
}
