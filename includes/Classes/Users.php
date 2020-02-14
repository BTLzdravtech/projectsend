<?php /**
       * @noinspection PhpUndefinedConstantInspection
       */

/**
 * Class that handles all the actions and functions that can be applied to
 * users accounts.
 *
 * @package    ProjectSend
 * @subpackage Classes
 */

namespace ProjectSend\Classes;

use \PDO;

class Users
{
    private $dbh;
    private $logger;

    private $validation_type;
    private $validation_passed;
    private $validation_errors;

    private $id;
    private $name;
    private $email;
    private $username;
    private $password;
    private $role;
    private $active;
    private $notify_account;
    private $max_file_size;
    private $owner_id;
    private $created_by;
    private $created_date;
    private $objectguid;
    private $google_user;

    // Uploaded files
    private $files;

    // Groups where the client is member
    private $groups;

    // Workspaces where the user is member
    private $workspaces;
    
    // @todo implement meta data
    private $meta;

    // @todo Move this to meta
    private $notify_upload;
    private $account_request;
    private $recaptcha;

    // Permissions
    private $allowed_actions_roles;
    
    public function __construct(PDO $dbh = null)
    {
        if (empty($dbh)) {
            global $dbh;
        }

        $this->dbh = $dbh;
        $this->logger = new ActionsLog;

        $this->role = 0; // by default, create "client" role

        $this->allowed_actions_roles = [9];
    }

    /**
     * Set the ID
     * @param $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
  
    /**
     * Return the ID
     *
     * @return int
     */
    public function getId()
    {
        if (!empty($this->id)) {
            return $this->id;
        }

        return false;
    }

    /**
     * Set the validation type (user or client, new or edit)
     * @param $type
     */
    public function setType($type)
    {
        $this->validation_type = $type;

        self::setActionsPermissions();
    }

    /**
     * Set the permissions to delete, activate, deactivate, approve or deny an account
     */
    private function setActionsPermissions()
    {
        /* Allowed roles for:
            Delete users: [9]
            Delete clients: [8, 9]
        */
        switch ($this->role) {
            case 7:
            case 8:
            case 9:
                $this->allowed_actions_roles = [9];
                break;
            case 0:
                $this->allowed_actions_roles = [8, 9];
                break;
        }
    }

    /**
     * Set the properties when editing
     * @param array $arguments
     */
    public function set($arguments = [])
    {
        $this->name = (!empty($arguments['name'])) ? encode_html($arguments['name']) : null;
        $this->email = (!empty($arguments['email'])) ? encode_html($arguments['email']) : null;
        $this->username = (!empty($arguments['username'])) ? encode_html($arguments['username']) : null;
        $this->password = (!empty($arguments['password'])) ? $arguments['password'] : null;
        $this->role = (!empty($arguments['role'])) ? (int)$arguments['role'] : 0;
        $this->active = (!empty($arguments['active'])) ? (int)$arguments['active'] : 0;
        $this->notify_account = (!empty($arguments['notify_account'])) ? $arguments['notify_account'] : 0;
        $this->max_file_size = (!empty($arguments['max_file_size'])) ? $arguments['max_file_size'] : 0;
        $this->objectguid = (!empty($arguments['objectguid'])) ? encode_html($arguments['objectguid']) : null;
        $this->google_user = (!empty($arguments['google_user'])) ? encode_html($arguments['google_user']) : 0;

        // Specific for clients
        $this->notify_upload = (!empty($arguments['notify_upload'])) ? (int)$arguments['notify_upload'] : 0;
        $this->account_request = (!empty($arguments['account_requested'])) ? (int)$arguments['account_requested'] : 0;
        $this->recaptcha = (!empty($arguments['recaptcha'])) ? $arguments['recaptcha'] : null;

        self::setActionsPermissions();
    }

    /**
     * Get existing user data from the database
     *
     * @param $id
     * @return bool
     */
    public function get($id)
    {
        $this->id = $id;

        if (CURRENT_USER_LEVEL != 9 && $id != CURRENT_USER_ID) {
            $restict = true;
        } else {
            $restict = false;
        }

        $statement = $this->dbh->prepare("SELECT * FROM " . TABLE_USERS . " WHERE id=:id" . ($restict ? " AND owner_id=:owner_id" : ""));
        $statement->bindParam(':id', $this->id, PDO::PARAM_INT);
        if ($restict) {
            $owner_id = CURRENT_USER_ID;
            $statement->bindParam(':owner_id', $owner_id, PDO::PARAM_INT);
        }
        $statement->execute();
        $statement->setFetchMode(PDO::FETCH_ASSOC);

        if ($statement->rowCount() == 0) {
            return false;
        }
    
        while ($row = $statement->fetch()) {
            $this->name = html_output($row['name']);
            $this->email = html_output($row['email']);
            $this->username = html_output($row['user']);
            $this->password = html_output($row['password']);
            $this->role = html_output($row['level']);
            $this->active = html_output($row['active']);
            $this->max_file_size = html_output($row['max_file_size']);
            $this->created_date = html_output($row['timestamp']);
            $this->owner_id = html_output($row['owner_id']);
            $this->created_by = html_output($row['created_by']);
            $this->objectguid = html_output($row['objectguid']);
            $this->google_user = html_output($row['google_user']);

            // Specific for clients
            $this->notify_upload = html_output($row['notify']);

            // Files
            $statement = $this->dbh->prepare("SELECT DISTINCT id FROM " . TABLE_FILES . " WHERE uploader = :username");
            $statement->bindParam(':username', $this->username);
            $statement->execute();

            if ($statement->rowCount() > 0) {
                $statement->setFetchMode(PDO::FETCH_ASSOC);
                while ($file = $statement->fetch()) {
                    $this->files[] = $file['id'];
                }
            }
    
            // Groups
            $groups_object = new MembersActions($this->dbh);
            $this->groups = $groups_object->client_get_groups(
                [
                    'client_id' => $this->id
                ]
            );

            // Workspaces
            $workspaces_object = new WorkspacesUsers($this->dbh);
            $this->workspaces = $workspaces_object->user_get_workspaces(
                [
                    'user_id' => $this->id
                ]
            );

            $this->validation_type = "existing_user";
        }

        self::setActionsPermissions();

        return true;
    }

    /**
     * Return the current properties
     */
    public function getProperties()
    {
        return [
           'id' => $this->id,
           'name' => $this->name,
           'email' => $this->email,
           'username' => $this->username,
           'password' => $this->password,
           'role' => $this->role,
           'active' => $this->active,
           'max_file_size' => $this->max_file_size,
           'created_date' => $this->created_date,
           'notify_upload' => $this->notify_upload,
           'files' => $this->files,
           'groups' => $this->groups,
           'workspaces' => $this->workspaces,
           'meta' => $this->meta,
           'objectguid' => $this->objectguid,
           'google_user' => $this->google_user,
        ];
    }

    /**
     * Is user active
     *
     * @return bool
     */
    public function isActive()
    {
        if ($this->active == 1) {
            return true;
        }

        return false;
    }

    /**
     * Validate the information from the form.
     */
    public function validate()
    {
        $validation = new Validation;

        global $json_strings;

        /**
         * These validations are done both when creating a new user and
         * when editing an existing one.
         */
        $validation->validate('completed', $this->name, $json_strings['validation']['no_name']);
        $validation->validate('completed', $this->email, $json_strings['validation']['no_email']);
        $validation->validate('completed', $this->role, $json_strings['validation']['no_role']);
        $validation->validate('email', $this->email, $json_strings['validation']['invalid_email']);
        $validation->validate('number', $this->max_file_size, $json_strings['validation']['file_size']);

        /**
         * Validations for NEW USER submission only.
         */
        if ($this->validation_type == 'new_user' || $this->validation_type == 'new_client' || $this->validation_type == 'new_google_user') {
            $validation->validate('email_exists', $this->email, $json_strings['validation']['email_exists']);
            $validation->validate('user_exists', $this->username, $json_strings['validation']['user_exists']);
            $validation->validate('completed', $this->username, $json_strings['validation']['no_user']);
            $validation->validate('alpha_dot', $this->username, $json_strings['validation']['alpha_user']);
            $validation->validate('length', $this->username, $json_strings['validation']['length_user'], MIN_USER_CHARS, MAX_USER_CHARS);

            if (!$this->validation_type == 'new_google_user') {
                $validate_password = true;
            }
        } elseif ($this->validation_type == 'existing_user') { /* Validations for USER EDITING only. */
            /**
             * Changing password is optional.
             */
            if (!empty($this->password)) {
                $validate_password = true;
            }
            /**
             * Check if the email is currently assigned to this users's id.
             * If not, then check if it exists.
             */
            $validation->validate('email_exists', $this->email, $json_strings['validation']['email_exists'], '', '', '', '', '', $this->id);
        }

        /**
         * Password checks
        */
        if (isset($validate_password) && $validate_password === true) {
            $validation->validate('completed', $this->password, $json_strings['validation']['no_pass']);
            $validation->validate('password', $this->password, $json_strings['validation']['valid_pass'] . " " . addslashes($json_strings['validation']['valid_chars']));
            $validation->validate('pass_rules', $this->password, $json_strings['validation']['rules_pass']);
            $validation->validate('length', $this->password, $json_strings['validation']['length_pass'], MIN_PASS_CHARS, MAX_PASS_CHARS);
        }

        if (!empty($this->recaptcha)) {
            $validation->validate('recaptcha', $this->recaptcha, $json_strings['validation']['recaptcha']);
        }

        if ($validation->passed()) {
            $this->validation_passed = true;
            return true;
        } else {
            $this->validation_passed = false;
            $this->validation_errors = $validation->list_errors();
        }

        return false;
    }

    /**
     * Return the validation errors the the front end
     */
    public function getValidationErrors()
    {
        if (!empty($this->validation_errors)) {
            return $this->validation_errors;
        }

        return false;
    }

    private function hashPassword($password)
    {
        return password_hash(password, PASSWORD_DEFAULT, [ 'cost' => HASH_COST_LOG2 ]);
    }

    /**
     * Create a new user.
     */
    public function create()
    {
        $state = array();

        if (isset($_SESSION['google_user']) || (LDAP_SIGNIN_ENABLED && $this->objectguid != null)) {
            $password_hashed = null;
        } else {
            $password_hashed = self::hashPassword($this->password);
        }

        if (strlen($password_hashed) >= 20 || isset($_SESSION['google_user']) || (LDAP_SIGNIN_ENABLED && $this->objectguid != null)) {

            /**
             * Who is creating the client?
            */
            if (defined('CURRENT_USER_ID')) {
                $this->owner_id = CURRENT_USER_ID;
            }
            if (defined('CURRENT_USER_USERNAME')) {
                $this->created_by = CURRENT_USER_USERNAME;
            }

            /**
             * Insert the client information into the database
            */
            $statement = $this->dbh->prepare(
                "INSERT INTO " . TABLE_USERS . " (
                    name, user, password, level, email, notify, owner_id, created_by, active, account_requested, max_file_size, objectguid, google_user
                )
			    VALUES (
                    :name, :username, :password, :role, :email, :notify_upload, :owner_id, :created_by, :active, :request, :max_file_size , :objectguid, :google_user
                )"
            );
            $statement->bindParam(':name', $this->name);
            $statement->bindParam(':username', $this->username);
            $statement->bindParam(':password', $password_hashed);
            $statement->bindParam(':role', $this->role, PDO::PARAM_INT);
            $statement->bindParam(':email', $this->email);
            $statement->bindParam(':notify_upload', $this->notify_upload, PDO::PARAM_INT);
            $statement->bindParam(':owner_id', $this->owner_id);
            $statement->bindParam(':created_by', $this->created_by);
            $statement->bindParam(':active', $this->active, PDO::PARAM_INT);
            $statement->bindParam(':request', $this->account_request, PDO::PARAM_INT);
            $statement->bindParam(':max_file_size', $this->max_file_size, PDO::PARAM_INT);
            $statement->bindParam(':objectguid', $this->objectguid);
            $statement->bindParam(':google_user', $this->google_user, PDO::PARAM_INT);

            $statement->execute();

            if ($statement) {
                $this->id = $this->dbh->lastInsertId();
                $state['id'] = $this->id;
                $state['name'] = $this->name;

                $state['query'] = 1;

                if (!defined('CURRENT_USER_ID') && !defined('CURRENT_USER_USERNAME')) {
                    $statement = $this->dbh->prepare("UPDATE " . TABLE_USERS . " SET owner_id = :owner_id, created_by = :created_by WHERE id = :id");
                    $statement->execute(array('owner_id' => $this->id, 'created_by' => $this->username, 'id' => $this->id));
                }

                /**
                 * Record the action log
                */
                $created_by = !empty(CURRENT_USER_ID) ? CURRENT_USER_ID : $this->id;
                $this->logger->addEntry(
                    [
                        'action' => 2,
                        'owner_id' => $created_by,
                        'affected_account' => $this->id,
                        'affected_account_name' => $this->name
                    ]
                );

                $email_type = "";

                switch ($this->role) {
                    case 0:
                        $email_type = "new_client";
                        break;
                    case 7:
                    case 8:
                    case 9:
                        $email_type = "new_user";
                        break;
                }
                
                /**
                 * Send account data by email
                */
                $notify_user = new Emails;
                $email_arguments = array(
                    'type' => $email_type,
                    'address' => $this->email,
                    'username' => $this->username,
                    'password' => $this->password
                );
                if ($this->notify_account == 1) {
                    $notify_send = $notify_user->send($email_arguments);

                    if ($notify_send == 1) {
                        $state['email'] = 1;
                    } else {
                        $state['email'] = 0;
                    }
                } else {
                    $state['email'] = 2;
                }
            } else {
                $state['query'] = 0;
            }
        } else {
            $state['hash'] = 0;
        }

        return $state;
    }

    /**
     * Edit an existing user.
     */
    public function edit()
    {
        if (empty($this->id) || !user_exists_id($this->id)) {
            return false;
        }

        $state = array();

        $password_hashed = self::hashPassword($this->password);

        if (strlen($password_hashed) >= 20) {
            $state['hash'] = 1;

            /**
             * SQL query
            */
            $query = "UPDATE " . TABLE_USERS . " SET
                name = :name,
                level = :role,
                email = :email,
                notify = :notify_upload,
                active = :active,
                max_file_size = :max_file_size
            ";

            /**
             * Add the password to the query if it's not the dummy value ''
            */
            if (!empty($this->password)) {
                $query .= ", password = :password";
            }
            
            $query .= " WHERE id = :id";
            
            $statement = $this->dbh->prepare($query);
            $statement->bindParam(':name', $this->name);
            $statement->bindParam(':role', $this->role, PDO::PARAM_INT);
            $statement->bindParam(':email', $this->email);
            $statement->bindParam(':notify_upload', $this->notify_upload, PDO::PARAM_INT);
            $statement->bindParam(':active', $this->active, PDO::PARAM_INT);
            $statement->bindParam(':max_file_size', $this->max_file_size, PDO::PARAM_INT);
            $statement->bindParam(':id', $this->id, PDO::PARAM_INT);
            if (!empty($this->password)) {
                $statement->bindParam(':password', $password_hashed);
            }

            $statement->execute();

            if ($statement) {
                $state['query'] = 1;

                $log_action_number = null;

                switch ($this->role) {
                    case 0:
                        $log_action_number = 14;
                        break;
                    case 7:
                    case 8:
                    case 9:
                        $log_action_number = 13;
                        break;
                }

                /**
                 * Record the action log
                */
                $this->logger->addEntry(
                    [
                        'action' => $log_action_number,
                        'owner_id' => CURRENT_USER_ID,
                        'affected_account' => $this->id,
                        'affected_account_name' => $this->username,
                        'username_column' => true
                    ]
                );
            } else {
                $state['query'] = 0;
            }
        } else {
            $state['hash'] = 0;
        }

        return $state;
    }

    /**
     * Delete an existing user.
     */
    public function delete()
    {
        if ($this->id == CURRENT_USER_ID) {
            return false;
        }

        if (isset($this->id)) {
            /**
             * Do a permissions check
            */
            if (isset($this->allowed_actions_roles) && current_role_in($this->allowed_actions_roles)) {
                $sql = $this->dbh->prepare('DELETE FROM ' . TABLE_USERS . ' WHERE id=:id');
                $sql->bindParam(':id', $this->id, PDO::PARAM_INT);
                $sql->execute();

                switch ($this->role) {
                    case 0:
                        $log_action_number = 17;
                        break;
                    case 7:
                    case 8:
                    case 9:
                        $log_action_number = 16;
                        break;
                }

                /**
                 * Record the action log
                */
                $this->logger->addEntry(
                    [
                        'action' => $log_action_number,
                        'owner_id' => CURRENT_USER_ID,
                        'affected_account_name' => $this->name,
                    ]
                );
                
                return true;
            }
        }
        
        return false;
    }

    /**
     * Mark the user as active or inactive.
     * @param $change_to
     * @return bool
     */
    public function setActiveStatus($change_to)
    {
        if ($this->id == CURRENT_USER_ID) {
            return false;
        }

        $user = self::get($this->id);
        if (!$user) {
            return false;
        }

        switch ($change_to) {
            case 0:
                $log_action_number = ($this->role == 0) ? 20 : 28;
                break;
            case 1:
                $log_action_number = ($this->role == 0) ? 19 : 27;
                break;
            default:
                return false;
                break;
        }

        if (isset($this->id)) {
            /**
             * Do a permissions check
            */
            if (isset($this->allowed_actions_roles) && current_role_in($this->allowed_actions_roles)) {
                $sql = $this->dbh->prepare('UPDATE ' . TABLE_USERS . ' SET active=:active_state WHERE id=:id');
                $sql->bindParam(':active_state', $change_to, PDO::PARAM_INT);
                $sql->bindParam(':id', $this->id, PDO::PARAM_INT);
                $sql->execute();

                /**
                 * Record the action log
                */
                $this->logger->addEntry(
                    [
                        'action' => $log_action_number,
                        'owner_id' => CURRENT_USER_ID,
                        'affected_account_name' => $this->name,
                    ]
                );
                
                return true;
            }
        }
        
        return false;
    }


    /**
     * Approve account
     */
    public function accountApprove()
    {
        if (isset($this->id)) {
            /**
             * Do a permissions check
            */
            if (isset($this->allowed_actions_roles) && current_role_in($this->allowed_actions_roles)) {
                $sql = $this->dbh->prepare('UPDATE ' . TABLE_USERS . ' SET active=:active, account_requested=:requested, account_denied=:denied WHERE id=:id');
                $sql->bindValue(':active', 1, PDO::PARAM_INT);
                $sql->bindValue(':requested', 0, PDO::PARAM_INT);
                $sql->bindValue(':denied', 0, PDO::PARAM_INT);
                $sql->bindValue(':id', $this->id, PDO::PARAM_INT);
                $sql->execute();

                /**
                 * Record the action log
                */
                $this->logger->addEntry(
                    [
                        'action' => 38,
                        'owner_id' => CURRENT_USER_ID,
                        'affected_account_name' => $this->name,
                    ]
                );
                
                return true;
            }
        }

        return false;
    }
 
    /**
     * Deny account
     */
    public function accountDeny()
    {
        if (isset($this->id)) {
            /**
             * Do a permissions check
            */
            if (isset($this->allowed_actions_roles) && current_role_in($this->allowed_actions_roles)) {
                $sql = $this->dbh->prepare('UPDATE ' . TABLE_USERS . ' SET active=:active, account_requested=:account_requested, account_denied=:account_denied WHERE id=:id');
                $sql->bindValue(':active', 0, PDO::PARAM_INT);
                $sql->bindValue(':account_requested', 1, PDO::PARAM_INT);
                $sql->bindValue(':account_denied', 1, PDO::PARAM_INT);
                $sql->bindValue(':id', $this->id, PDO::PARAM_INT);
                $sql->execute();

                /**
                 * Record the action log
                */
                $this->logger->addEntry(
                    [
                        'action' => 38,
                        'owner_id' => CURRENT_USER_ID,
                        'affected_account_name' => $this->name,
                    ]
                );
                
                return true;
            }
        }
 
        return false;
    }
}
