<?php
/**
 * Class that handles all the actions and functions that can be applied to
 * users workspaces.
 *
 * @package    ProjectSend
 * @subpackage Classes
 */

namespace ProjectSend\Classes;

use \PDO;

class Workspaces
{
    private $dbh;
    private $logger;

    private $id;
    private $name;
    private $description;
    private $users;
    private $owner_id;
    private $created_by;
    private $created_date;

    private $validation_passed;
    private $validation_errors;

    // Permissions
    private $allowed_actions_roles;

    public function __construct(PDO $dbh = null)
    {
        if (empty($dbh)) {
            global $dbh;
        }

        $this->dbh = $dbh;
        $this->logger = new ActionsLog;

        $this->allowed_actions_roles = [9, 8];
    }

    /**
     * Set the ID
     *
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
     * Set the properties when editing
     *
     * @param array $arguments
     */
    public function set($arguments = [])
    {
        $this->name = (!empty($arguments['name'])) ? encode_html($arguments['name']) : null;
        $this->description = (!empty($arguments['description'])) ? encode_html($arguments['description']) : null;
        $this->users = (!empty($arguments['users'])) ? $arguments['users'] : null;
    }

    /**
     * Get existing user data from the database
     *
     * @return bool
     */
    public function get($id)
    {
        $this->id = $id;

        $statement = $this->dbh->prepare("SELECT * FROM " . TABLE_WORKSPACES . " WHERE id=:id");
        $statement->bindParam(':id', $this->id, PDO::PARAM_INT);
        $statement->execute();
        $statement->setFetchMode(PDO::FETCH_ASSOC);

        if ($statement->rowCount() == 0) {
            return false;
        }
    
        while ($row = $statement->fetch()) {
            $this->name = html_output($row['name']);
            $this->description = html_output($row['description']);
            $this->owner_id = html_output($row['owner_id']);
            $this->created_by = html_output($row['created_by']);
            $this->created_date = html_output($row['timestamp']);
        }

        /* Get workspace users IDs */
        $statement = $this->dbh->prepare("SELECT user_id FROM " . TABLE_WORKSPACES_USERS . " WHERE workspace_id = :id");
        $statement->bindParam(':id', $this->id, PDO::PARAM_INT);
        $statement->execute();
        
        if ($statement->rowCount() > 0) {
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            while ($user = $statement->fetch()) {
                $this->users[] = $user['user_id'];
            }
        }

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
            'description' => $this->description,
            'users' => $this->users,
            'owner_id' => $this->owner_id,
            'created_by' => $this->created_by,
            'created_date' => $this->created_date,
        ];
    }

    /**
     * Validate the information from the form.
     */
    public function validate()
    {
        $validation = new Validation;

        global $json_strings;
        $state = array();

        /**
         * These validations are done both when creating a new workspace and
         * when editing an existing one.
         */
        $validation->validate('completed', $this->name, $json_strings['validation']['no_name']);
        $validation->validate('completed', $this->description, $json_strings['validation']['no_description']);

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

    /**
     * Create a new group.
     */
    public function create()
    {
        $state = array();
        
        if (!empty($this->name)) {

            /**
             * Who is creating the client?
            */
            $this->owner_id = CURRENT_USER_ID;
            $this->created_by = CURRENT_USER_USERNAME;

            $sql_query = $this->dbh->prepare(
                "INSERT INTO " . TABLE_WORKSPACES . " (name, description, owner_id, created_by)"
                ." VALUES (:name, :description, :owner_id, :admin)"
            );
            $sql_query->bindParam(':name', $this->name);
            $sql_query->bindParam(':description', $this->description);
            $sql_query->bindParam(':owner_id', $this->owner_id);
            $sql_query->bindParam(':admin', $this->created_by);
            $sql_query->execute();

            $this->id = $this->dbh->lastInsertId();
            $state['id'] = $this->id;
            $state['name'] = $this->name;

            /**
             * Create the users records
            */
            if (!empty($this->users)) {
                foreach ($this->users as $user) {
                    $admin = 0;

                    $sql_member = $this->dbh->prepare(
                        "INSERT INTO " . TABLE_WORKSPACES_USERS . " (added_by,user_id,workspace_id,admin)"
                        ." VALUES (:added_by, :user, :id, :admin)"
                    );
                    $sql_member->bindParam(':added_by', $this->created_by);
                    $sql_member->bindParam(':user', $user, PDO::PARAM_INT);
                    $sql_member->bindParam(':id', $this->id, PDO::PARAM_INT);
                    $sql_member->bindParam(':admin', $admin, PDO::PARAM_INT);
                    $sql_member->execute();
                }
            }

            if ($sql_query) {
                $state['query'] = 1;

                /**
                 * Record the action log
                */
                $new_record_action = $this->logger->addEntry(
                    [
                        'action' => 23,
                        'owner_id' => CURRENT_USER_ID,
                        'affected_account' => $this->id,
                        'affected_account_name' => $this->name,
                    ]
                );
            } else {
                $state['query'] = 0;
            }
        }
        
        return $state;
    }

    /**
     * Edit an existing workspace.
     */
    public function edit()
    {
        if (empty($this->id)) {
            return false;
        }

        $state = array();

        /**
         * Who is creating the client?
        */
        $this->created_by = CURRENT_USER_USERNAME;

        /**
         * SQL query
        */
        $sql_query = $this->dbh->prepare("UPDATE " . TABLE_WORKSPACES . " SET name = :name, description = :description WHERE id = :id");
        $sql_query->bindParam(':name', $this->name);
        $sql_query->bindParam(':description', $this->description);
        $sql_query->bindParam(':id', $this->id, PDO::PARAM_INT);
        $sql_query->execute();

        /**
         * Clean the users table
        */
        $sql_clean = $this->dbh->prepare("DELETE FROM " . TABLE_WORKSPACES_USERS . " WHERE workspace_id = :id");
        $sql_clean->bindParam(':id', $this->id, PDO::PARAM_INT);
        $sql_clean->execute();
        
        /**
         * Create the users records
        */
        if (!empty($this->users)) {
            foreach ($this->users as $user) {
                $sql_user = $this->dbh->prepare(
                    "INSERT INTO " . TABLE_WORKSPACES_USERS . " (added_by,user_id,workspace_id)"
                    ." VALUES (:added_by, :user, :id)"
                );
                $sql_user->bindParam(':added_by', $this->created_by);
                $sql_user->bindParam(':user', $user, PDO::PARAM_INT);
                $sql_user->bindParam(':id', $this->id, PDO::PARAM_INT);
                $sql_user->execute();
            }
        }

        if ($sql_query) {
            $state['query'] = 1;

            /**
             * Record the action log
            */
            $new_record_action = $this->logger->addEntry(
                [
                    'action' => 15,
                    'owner_id' => CURRENT_USER_ID,
                    'affected_account' => $this->id,
                    'affected_account_name' => $this->name,
                ]
            );
        } else {
            $state['query'] = 0;
        }
        
        return $state;
    }

    /**
     * Delete an existing workspace.
     */
    public function delete()
    {
        if (empty($this->id)) {
            return false;
        }

        /**
         * Do a permissions check
        */
        if (isset($this->allowed_actions_roles) && current_role_in($this->allowed_actions_roles)) {
            $sql = $this->dbh->prepare('DELETE FROM ' . TABLE_WORKSPACES . ' WHERE id=:id');
            $sql->bindParam(':id', $this->id, PDO::PARAM_INT);
            $sql->execute();
        }
        
        /**
         * Record the action log
        */
        $record = $this->logger->addEntry(
            [
                'action' => 18,
                'owner_id' => CURRENT_USER_ID,
                'affected_account_name' => $this->name,
            ]
        );

        return true;
    }
}
