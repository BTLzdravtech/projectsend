<?php
/**
 * Class that handles log in, log out and account status checks.
 *
 * @package		ProjectSend
 * @subpackage	Classes
 */
namespace ProjectSend\Classes;
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
        $this->logger = new \ProjectSend\Classes\ActionsLog;
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
        global $hasher;
        
        if ( !$username || !$password )
            return false;

		$this->selected_form_lang	= (!empty( $language ) ) ? $language : SITE_LANG;

        $this->ldap = new LDAP($this->dbh);

        /** Look up the system users table to see if the entered username exists */
        $this->statement = $this->dbh->prepare("SELECT * FROM " . TABLE_USERS . " WHERE user=:username OR email=:email");
        $this->statement->execute(
            array(
                ':username'	=> $username,
                ':email'	=> $username,
            )
        );
        $this->count_user = $this->statement->rowCount();
        if ($this->count_user > 0) {
            /** If the username was found on the users table */
            $this->statement->setFetchMode(PDO::FETCH_ASSOC);
            while ( $this->row = $this->statement->fetch() ) {
                $this->db_username              = $this->row['user'];
                $this->db_pass                  = $this->row['password'];
                $this->user_level               = $this->row["level"];
                $this->active_status            = $this->row['active'];
                $this->logged_id                = $this->row['id'];
                $this->name                     = $this->row['name'];
                $this->start_observation_window = $this->row['start_observation_window'];
                $this->invalid_auth_attempts    = $this->row['invalid_auth_attempts'];
            }
            $authenticated = false;
            if (LDAP_SIGNIN_ENABLED && ($this->user_level == '8' || $this->user_level == '9')) {
                $authenticated = $this->ldap->bind($username, $password);
                if ($authenticated) {
                    $this->ldap->update_db($username);
                }
            }
            if (!$authenticated) {
                $authenticated = password_verify($password, $this->db_pass);
            }
            if (!$authenticated) {
                //$errorstate = 'wrong_password';
                $this->errorstate = 'invalid_credentials';
                $this->checkLockout();
            }
        } else {
            if (LDAP_SIGNIN_ENABLED) {
                if ($authenticated = $this->ldap->bind($username, $password)) {
                    $attributes = $this->ldap->get_entry_attributes($username);

                    $already_present = $this->ldap->check_by_guid(bin2hex($attributes['objectGUID'][0]));
                    if ($already_present) {
                        $id = $this->ldap->update_db(null, $attributes);
                    } else {
                        $this->new_user = new Users($this->dbh);
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

                        $this->new_user->setType('new_user');
                        $this->new_user->set($user_arguments);
                        $created_user = $this->new_user->create();
                    }

                    $this->db_username	    = $attributes['sAMAccountName'][0];
                    $this->user_level		= '8';
                    $this->active_status	= '1';
                    $this->logged_id		= $id ?? $created_user['id'] ;
                    $this->name	        	= $attributes['displayName'][0];
                } else {
                    //$errorstate = 'wrong_username';
                    $this->errorstate = 'invalid_credentials';
                    $this->checkLockout();
                }
            } else {
                $authenticated = false;
                //$errorstate = 'wrong_username';
                $this->errorstate = 'invalid_credentials';
                if (LOG_FAILED_AUTH) {
                    /** Record the action log */
                    $this->logger->addEntry([
                        'action' => 44,
                        'owner_id' => -1,
                        'owner_user' =>  $_GET['username']
                    ]);
                }
            }
        }

        if ($authenticated) {
            if ($this->active_status != '0') {
                /** Set SESSION values */
                $_SESSION['loggedin'] = $this->db_username;
                $_SESSION['userlevel'] = $this->user_level;
                $_SESSION['lang'] = $this->selected_form_lang;

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

                if ($this->user_level != '0') {
                    $this->access_string = 'admin';
                    $_SESSION['access'] = $this->access_string;
                } else {
                    $this->access_string = $this->db_username;
                    $_SESSION['access'] = $this->db_username;
                }

                /** Record the action log */
                $this->new_record_action = $this->logger->addEntry([
                    'action' => 1,
                    'owner_id' => $this->logged_id,
                    'owner_user' => $this->name,
                    'affected_account_name' => $this->name
                ]);

                $results = array(
                    'status' => 'success',
                    'message' => system_message('success', 'Login success. Redirecting...', 'login_response'),
                );
                if ($this->user_level == '0') {
                    $results['location'] = CLIENT_VIEW_FILE_LIST_URL;
                } else {
                    $results['location'] = BASE_URI . "dashboard.php";
                }

                /** Using an external form */
                if (!empty($_GET['external']) && $_GET['external'] == '1' && empty($_GET['ajax'])) {
                    /** Success */
                    if ($results['status'] == 'success') {
                        header('Location: ' . $results['location']);
                        exit;
                    }
                }

                echo json_encode($results);
                exit;
            } else {
                $this->errorstate = 'inactive_client';
            }
        }

        $this->error_message = $this->getLoginError($this->errorstate);
		$results = array(
						'status'	=> 'error',
						'message'	=> system_message('danger',$this->error_message,'login_error'),
					);

		/** Using an external form */
		if ( !empty( $_GET['external'] ) && $_GET['external'] == '1' && empty( $_GET['ajax'] ) ) {
			/** Error */
			if ( $results['status'] == 'error' ) {
				header('Location: ' . BASE_URI . '?error=invalid_credentials');
                exit;
			}
		}

        echo json_encode($results);
    }

    /** Social Login via hybridauth */
    public function socialLogin() {
        // If !user_exists
            // new \ProjectSend\Classes\Users
            // $user->set($data_from_hybridauth)
            // $user->create
            // $uid = $user->getId();
            // get_user_by_id($uid);

        // login
            // Set $_SESSION
            // $logger->addEntry
            // redirect according to account type (user or client)
    }

    /**
     * Login error strings
     * 
     * @param string errorstate
     * @return string
     */
    public function getLoginError($errorstate)
    {
        $this->error = __("Error during log in.",'cftp_admin');;

		if (isset($errorstate)) {
			switch ($errorstate) {
				case 'invalid_credentials':
					$this->error = __("The supplied credentials are not valid.",'cftp_admin');
					break;
				case 'wrong_username':
					$this->error = __("The supplied username doesn't exist.",'cftp_admin');
					break;
				case 'wrong_password':
					$this->error = __("The supplied password is incorrect.",'cftp_admin');
					break;
				case 'inactive_client':
					$this->error = __("This account is not active.",'cftp_admin');
					if (CLIENTS_CAN_REGISTER == 1 && CLIENTS_AUTO_APPROVE == 0) {
						$this->error .= ' '.__("If you just registered, please wait until a system administrator approves your account.",'cftp_admin');
					}
					break;
				case 'no_self_registration':
					$this->error = __('Client self registration is not allowed. If you need an account, please contact a system administrator.','cftp_admin');
					break;
				case 'no_account':
					$this->error = __('Sign-in with Google cannot be used to create new accounts at this time.','cftp_admin');
					break;
				case 'access_denied':
					$this->error = __('You must approve the requested permissions to sign in with Google.','cftp_admin');
					break;
			}
        }
        
        return $this->error;
    }

    private function checkLockout() {
        if (LOG_FAILED_AUTH) {
            $action = ($this->user_level != '0' ? 41 : 40);
            $this->logger->addEntry([
                'action' => $action,
                'owner_id' => $this->logged_id,
                'owner_user' => $this->name,
                'affected_account_name' => $this->name
            ]);
        }
        /**
         * account lockout logic
         */
        // set the correct limits based on the user_level of the account
        if ($this->user_level != '0') {
            $this->max_invalid_auth_attempts = USER_MAX_INVALID_AUTH_ATTEMPTS;
            $this->observation_window = USER_OBSERVATION_WINDOW;
        } else {
            $this->max_invalid_auth_attempts = CLIENT_MAX_INVALID_AUTH_ATTEMPTS;
            $this->observation_window = CLIENT_OBSERVATION_WINDOW;
        }
        // only bother where the account is active and lockout functionality is enabled (i.e. _MAX_INVALID_AUTH_ATTEMPTS > 0)
        if ($this->active_status != '0' && $this->max_invalid_auth_attempts != '0') {
            if ($this->invalid_auth_attempts < $this->max_invalid_auth_attempts) {
                if (time() <= $this->start_observation_window + $this->observation_window * 60) {
                    // this invalid login is in an existing observation_window
                    // update user table incrementing invalid_auth_attempts by one if current value < MAX_INVALID_AUTH_ATTEMPTS
                    if ($this->invalid_auth_attempts < MAX_INVALID_AUTH_ATTEMPTS) {
                        $this->sql = "UPDATE " . TABLE_USERS . " SET invalid_auth_attempts = :invalid_auth_attempts WHERE id = :logged_id";
                        $this->statement = $this->dbh->prepare($this->sql);
                        $this->statement->bindValue(':invalid_auth_attempts', $this->invalid_auth_attempts + 1, PDO::PARAM_INT);
                        $this->statement->bindParam(':logged_id', $this->logged_id, PDO::PARAM_INT);
                        $this->statement->execute();
                    }
                    // requery to refresh $this
                    $this->refresh_account_status($this->logged_id);
                    if ($this->invalid_auth_attempts >= $this->max_invalid_auth_attempts) {
                        // maximum attempts in window exceded so disable user account and refresh status
                        $this->disable_account($this->logged_id);
                        $this->refresh_account_status($this->logged_id);
                        /** Record the lockout action */
                        $action = ($this->user_level != '0' ? 43 : 42);
                        /** Record the action log */
                        $this->logger->addEntry([
                            'action' => $action,
                            'owner_id' => $this->logged_id,
                            'owner_user' => $this->name,
                            'affected_account_name' => $this->name
                        ]);
                    }
                } else {
                    // this invalid login is in a new observation_window
                    $this->sql = "UPDATE " . TABLE_USERS . " SET invalid_auth_attempts = :invalid_auth_attempts, start_observation_window = :start_observation_window WHERE id = :logged_id";
                    $this->statement = $this->dbh->prepare($this->sql);
                    $this->statement->bindValue(':invalid_auth_attempts', 1, PDO::PARAM_INT);
                    $this->statement->bindValue(':start_observation_window', time(), PDO::PARAM_INT);
                    $this->statement->bindParam(':logged_id', $this->logged_id, PDO::PARAM_INT);
                    $this->statement->execute();
                    // requery to refresh $this
                    $this->refresh_account_status($this->logged_id);
                }
            } else {
                // this could happen if _MAX_INVALID_AUTH_ATTEMPTS is reduced
                // maximum attempts exceded so disable user account and refresh status
                $this->disable_account($this->logged_id);
                $this->refresh_account_status($this->logged_id);
                /** Record the lockout action */
                $action = ($this->user_level != '0' ? 43 : 42);
                /** Record the action log */
                $this->logger->addEntry([
                    'action' => $action,
                    'owner_id' => $this->logged_id,
                    'owner_user' => $this->name,
                    'affected_account_name' => $this->name
                ]);
            }
            // user hasn't authenticated correctly so don't bleed any state information about the account
            $this->errorstate = 'invalid_credentials';
        } else {
            // user hasn't authenticated correctly so don't bleed any state information about the account
            $this->errorstate = 'invalid_credentials';
        }
    }

    private function refresh_account_status($id) {
        $this->statement = $this->dbh->prepare("SELECT * FROM " . TABLE_USERS . " WHERE id = :logged_id");
        $this->statement->bindParam(':logged_id', $id, PDO::PARAM_INT);
        $this->statement->execute();
        $this->statement->setFetchMode(PDO::FETCH_ASSOC);
        while ( $this->row = $this->statement->fetch() ) {
            $this->active_status		= $this->row['active'];
            $this->start_observation_window = $this->row['start_observation_window'];
            $this->invalid_auth_attempts	= $this->row['invalid_auth_attempts'];
        }
    }

    private function disable_account($id) {
        $this->sql = "UPDATE " . TABLE_USERS . " SET active = :active_status WHERE id = :logged_id";
        $this->statement = $this->dbh->prepare($this->sql);
        $this->statement->bindValue(':active_status', ACCOUNT_INACTIVE, PDO::PARAM_INT);
        $this->statement->bindParam(':logged_id', $id, PDO::PARAM_INT);
        $this->statement->execute();
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

		/** Record the action log */
		$new_record_action = $this->logger->addEntry([
            'action'	=> 31,
            'owner_id'	=> CURRENT_USER_ID,
            'affected_account_name' => CURRENT_USER_NAME
        ]);

		$redirect_to = 'index.php';
		if ( isset( $_GET['timeout'] ) ) {
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