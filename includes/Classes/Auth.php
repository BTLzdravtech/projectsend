<?php
/**
 * Class that handles log in, log out and account status checks.
 *
 * @package    ProjectSend
 * @subpackage Classes
 */
namespace ProjectSend\Classes;

use BruteForceBlock;
use \PDO;

class Auth
{
    private $dbh;
    private $logger;

    private $user;
    private $ldap;

    public function __construct(PDO $dbh = null)
    {
        if (empty($dbh)) {
            global $dbh;
        }

        $this->dbh = $dbh;
        $this->logger = new ActionsLog;
    }

    /**
     * Try to log in with a username and password
     *
     * @param $username
     * @param $password
     * @param $language
     */
    public function login($username, $password, $language)
    {
        global $logger;
        
        if (!$username || !$password) {
            return false;
        }

        $BFBresponse = BruteForceBlock::getLoginStatus();

        switch ($BFBresponse['status']) {
            case 'safe':
                $selected_form_lang = (!empty($language)) ? $language : SITE_LANG;
                $this->ldap = new LDAP($this->dbh);
                /**
                 * Look up the system users table to see if the entered username exists
                 */
                $statement = $this->dbh->prepare("SELECT * FROM " . TABLE_USERS . " WHERE user=:username OR email=:email");
                $statement->execute(
                    array(
                        ':username' => $username,
                        ':email' => $username,
                    )
                );
                $count_user = $statement->rowCount();
                if ($count_user > 0) {
                    /**
                     * If the username was found on the users table
                     */
                    $statement->setFetchMode(PDO::FETCH_ASSOC);
                    while ($row = $statement->fetch()) {
                        $db_username = $row['user'];
                        $db_pass = $row['password'];
                        $user_level = $row["level"];
                        $active_status = $row['active'];
                        $logged_id = $row['id'];
                        $name = $row['name'];
                    }
                    $authenticated = false;
                    if (LDAP_SIGNIN_ENABLED && ($user_level == '8' || $user_level == '9')) {
                        $authenticated = $this->ldap->bind($username, $password);
                        if ($authenticated) {
                            $this->ldap->update_db($username);
                        }
                    }
                    if (!$authenticated) {
                        $authenticated = password_verify($password, $db_pass);
                    }
                    if (!$authenticated) {
                        //$errorstate = 'wrong_password';
                        BruteForceBlock::addFailedLoginAttempt($username, $_SERVER['REMOTE_ADDR']);
                        $errorstate = 'invalid_credentials';
                    }
                } else {
                    if (LDAP_SIGNIN_ENABLED) {
                        if ($authenticated = $this->ldap->bind($username, $password)) {
                            $attributes = $this->ldap->get_entry_attributes($username);

                            $already_present = $this->ldap->check_by_guid(bin2hex($attributes['objectGUID'][0]));
                            $id = null;
                            $created_user = null;
                            if ($already_present) {
                                $id = $this->ldap->update_db(null, $attributes);
                            } else {
                                $new_user = new Users($this->dbh);
                                $user_arguments = array(
                                    'username' => $attributes['sAMAccountName'][0],
                                    'password' => '',
                                    'name' => $attributes['displayName'][0],
                                    'email' => $attributes['mail'][0],
                                    'role' => '8',
                                    'max_file_size' => '',
                                    'notify_account' => '0',
                                    'notify_upload' => '1',
                                    'active' => '1',
                                    'type' => 'new_user',
                                    'objectguid' => bin2hex($attributes['objectGUID'][0])
                                );

                                $new_user->setType('new_user');
                                $new_user->set($user_arguments);
                                $created_user = $new_user->create();
                            }

                            $db_username = $attributes['sAMAccountName'][0];
                            $user_level = '8';
                            $active_status = '1';
                            $logged_id = $id ?? $created_user['id'];
                            $name = $attributes['displayName'][0];
                        } else {
                            //$errorstate = 'wrong_username';
                            BruteForceBlock::addFailedLoginAttempt($username, $_SERVER['REMOTE_ADDR']);
                            $errorstate = 'invalid_credentials';
                        }
                    } else {
                        $authenticated = false;
                        //$errorstate = 'wrong_username';
                        BruteForceBlock::addFailedLoginAttempt($username, $_SERVER['REMOTE_ADDR']);
                        $errorstate = 'invalid_credentials';
                    }
                }

                if ($authenticated) {
                    if ($active_status != '0') {
                        /**
                         * Set SESSION values
                         */
                        $_SESSION['loggedin'] = $db_username;
                        $_SESSION['userlevel'] = $user_level;
                        $_SESSION['lang'] = $selected_form_lang;

                        /**
                         * Language cookie
                         * Must decide how to refresh language in the form when the user
                         * changes the language <select> field.
                         * By using a cookie and not refreshing here, the user is
                         * stuck in a language and must use it to recover password or
                         * create account, since the lang cookie is only at login now.
                         *
                         * @todo Implement.
                         */
                        //setcookie('projectsend_language', $selected_form_lang, time() + (86400 * 30), '/');

                        if ($user_level != '0') {
                            $access_string = 'admin';
                            $_SESSION['access'] = $access_string;
                        } else {
                            $access_string = $db_username;
                            $_SESSION['access'] = $db_username;
                        }

                        /**
                         * Record the action log
                         */
                        $new_record_action = $this->logger->addEntry(
                            [
                                'action' => 1,
                                'owner_id' => $logged_id,
                                'owner_user' => $name,
                                'affected_account_name' => $name
                            ]
                        );

                        $results = array(
                            'status' => 'success',
                            'message' => system_message('success', 'Login success. Redirecting...', 'login_response'),
                        );
                        if ($user_level == '0') {
                            $results['location'] = BASE_URI . "upload-from-computer.php";
                        } else {
                            $results['location'] = BASE_URI . "upload-from-computer.php";
                        }

                        /**
                         * Using an external form
                         */
                        if (!empty($_GET['external']) && $_GET['external'] == '1' && empty($_GET['ajax'])) {
                            /**
                             * Success
                             */
                            if ($results['status'] == 'success') {
                                header('Location: ' . $results['location']);
                                exit;
                            }
                        }

                        echo json_encode($results);
                        exit;
                    } else {
                        $errorstate = 'inactive_client';
                    }
                }
                break;
            case 'error':
                $errorstate = 'error';
                break;
            case 'delay':
                $errorstate = 'delay';
                break;
        }

        if ($errorstate == 'delay') {
            $error_message = $this->getLoginError($errorstate, $BFBresponse['message']);
        } else {
            $error_message = $this->getLoginError($errorstate);
        }
        $results = array(
            'status' => 'error',
            'message' => system_message('danger', $error_message, 'login_error'),
        );

        /**
         * Using an external form
        */
        if (!empty($_GET['external']) && $_GET['external'] == '1' && empty($_GET['ajax'])) {
            /**
             * Error
            */
            if ($results['status'] == 'error') {
                header('Location: ' . BASE_URI . '?error=invalid_credentials');
                exit;
            }
        }

        echo json_encode($results);
    }

    /**
     * Login error strings
     *
     * @param  string errorstate
     * @return string
     */
    public function getLoginError($errorstate, $delay = null)
    {
        $error = __("Error during log in.", 'cftp_admin');

        if (isset($errorstate)) {
            switch ($errorstate) {
                case 'invalid_credentials':
                    $error = __("The supplied credentials are not valid.", 'cftp_admin');
                    break;
                case 'wrong_username':
                    $error = __("The supplied username doesn't exist.", 'cftp_admin');
                    break;
                case 'wrong_password':
                    $error = __("The supplied password is incorrect.", 'cftp_admin');
                    break;
                case 'inactive_client':
                    $error = __("This account is not active.", 'cftp_admin');
                    if (CLIENTS_CAN_REGISTER == 1 && CLIENTS_AUTO_APPROVE == 0) {
                        $error .= ' '.__("If you just registered, please wait until a system administrator approves your account.", 'cftp_admin');
                    }
                    break;
                case 'no_self_registration':
                    $error = __('Client self registration is not allowed. If you need an account, please contact a system administrator.', 'cftp_admin');
                    break;
                case 'no_account':
                    $error = __('Sign-in with Google cannot be used to create new accounts at this time.', 'cftp_admin');
                    break;
                case 'access_denied':
                    $error = __('You must approve the requested permissions to sign in with Google.', 'cftp_admin');
                    break;
                case 'error':
                    $error = __('Sorry, we can\'t process your request right now.', 'cftp_admin');
                    break;
                case 'delay':
                    if ($delay > 1) {
                        $error = sprintf(__('There have been too many login failures from your network in a short time period.<br>Please wait %d seconds and try again.', 'cftp_admin'), $delay);
                    } else {
                        $error = sprintf(__('There have been too many login failures from your network in a short time period.<br>Please wait %d second and try again.', 'cftp_admin'), $delay);
                    }
                    break;
            }
        }
        
        return $error;
    }

    public function logout()
    {
        header("Cache-control: private");
        unset($_SESSION['loggedin']);
        unset($_SESSION['access']);
        unset($_SESSION['userlevel']);
        unset($_SESSION['lang']);
        unset($_SESSION['last_call']);
        session_destroy();

        /*
        $language_cookie = 'projectsend_language';
        setcookie ($language_cookie, "", 1);
        setcookie ($language_cookie, false);
        unset($_COOKIE[$language_cookie]);
        */

        /**
         * Record the action log
        */
        $new_record_action = $this->logger->addEntry(
            [
                'action' => 31,
                'owner_id' => CURRENT_USER_ID,
                'affected_account_name' => CURRENT_USER_NAME
            ]
        );

        $redirect_to = 'index.php';
        if (isset($_GET['timeout'])) {
            $redirect_to .= '?error=timeout';
        }

        header("Location: " . $redirect_to);
        exit;
    }

    public function unauthorized(Request $request, Response $response)
    {
        return $this->render($response, 'auth/unauthorized.twig');
    }
}
