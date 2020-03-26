<?php
/**
 * Class that handles all the actions and functions that can be applied to
 * files categories.
 *
 * @package    ProjectSend
 * @subpackage Classes
 */

namespace ProjectSend\Classes;

use \PDO;

class Categories
{
    private $dbh;
    private $logger;

    private $id;
    private $name;
    private $parent;
    private $description;
    private $owner_id;
    private $created_by;

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

        $this->allowed_actions_roles = [9, 8, 7];
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
     * Set the properties when editing
     * @param array $arguments
     */
    public function set($arguments = [])
    {
        $this->id = (!empty($arguments['id'])) ? encode_html($arguments['id']) : null;
        $this->name = (!empty($arguments['name'])) ? encode_html($arguments['name']) : null;
        $this->parent = (!empty($arguments['parent'])) ? (int)$arguments['parent'] : null;
        $this->description = (!empty($arguments['description'])) ? encode_html($arguments['description']) : null;
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

        $statement = $this->dbh->prepare("SELECT * FROM " . TABLE_CATEGORIES . " WHERE id=:id");
        $statement->bindParam(':id', $this->id, PDO::PARAM_INT);
        $statement->execute();
        $statement->setFetchMode(PDO::FETCH_ASSOC);

        if ($statement->rowCount() == 0) {
            return false;
        }

        while ($row = $statement->fetch()) {
            $this->name = html_output($row['name']);
            $this->parent = html_output($row['parent']);
            $this->description = html_output($row['description']);
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
            'parent' => $this->parent,
            'description' => $this->description,
        ];
    }

    /**
     * Validate the information from the form.
     */
    public function validate()
    {
        $validation = new Validation;

        global $json_strings;

        /**
         * These validations are done both when creating a new client and
         * when editing an existing one.
         */
        $validation->validate('completed', $this->name, $json_strings['validation']['no_name']);

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
     * Save or create, according the the ACTION parameter
     */
    public function create()
    {
        $state = array();

        /**
         * Who is creating the category?
         */
        $this->owner_id = CURRENT_USER_ID;
        $this->created_by = CURRENT_USER_USERNAME;

        /**
         * Insert the category information into the database
         */
        $statement = $this->dbh->prepare(
            "INSERT INTO " . TABLE_CATEGORIES . " (name,parent,description,owner_id,created_by)"
            . "VALUES (:name, :parent, :description, :owner_id, :created_by)"
        );
        $statement->bindParam(':name', $this->name);

        if (empty($this->parent)) {
            $this->parent = 0;
            $statement->bindValue(':parent', $this->parent, PDO::PARAM_NULL);
        } else {
            $statement->bindValue(':parent', $this->parent, PDO::PARAM_INT);
        }

        $statement->bindParam(':description', $this->description);
        $statement->bindParam(':owner_id', $this->owner_id);
        $statement->bindParam(':created_by', $this->created_by);

        $statement->execute();

        if ($statement) {
            $state['query'] = 1;
            $this->id = $this->dbh->lastInsertId();
            $state['id'] = $this->id;

            /**
             * Record the action log
             */
            $this->logger->addEntry(
                [
                    'action' => 34,
                    'owner_id' => CURRENT_USER_ID,
                    'affected_account' => $this->id,
                    'affected_account_name' => $this->name
                ]
            );
        } else {
            /**
             * Query couldn't be executed
             */
            $state['query'] = 0;
        }

        return $state;
    }

    /**
     * Edit an existing user.
     */
    public function edit()
    {
        if (empty($this->id)) {
            return false;
        }

        $state = array();

        /**
         * SQL query
         */
        $edit_category_query = "UPDATE " . TABLE_CATEGORIES . " SET 
            name = :name,
            parent = :parent,
            description = :description
            WHERE id = :id
        ";


        $statement = $this->dbh->prepare($edit_category_query);
        $statement->bindParam(':name', $this->name);
        if ($this->parent == '0') {
            $this->parent == null;
            $statement->bindValue(':parent', $this->parent, PDO::PARAM_NULL);
        } else {
            $statement->bindValue(':parent', $this->parent, PDO::PARAM_INT);
        }
        $statement->bindParam(':description', $this->description);
        $statement->bindParam(':id', $this->id, PDO::PARAM_INT);

        $statement->execute();

        $state['id'] = $this->id;

        if ($statement) {
            $state['query'] = 1;

            /**
             * Record the action log
             */
            $this->logger->addEntry(
                [
                    'action' => 35,
                    'owner_id' => CURRENT_USER_ID,
                    'affected_account' => $this->id,
                    'affected_account_name' => $this->name
                ]
            );
        } else {
            $state['query'] = 0;
        }

        return $state;
    }

    /**
     * Delete an existing category.
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
            $sql = $this->dbh->prepare('DELETE FROM ' . TABLE_CATEGORIES . ' WHERE id=:id');
            $sql->bindParam(':id', $this->id, PDO::PARAM_INT);
            $sql->execute();

            /**
             * Record the action log
             */
            $this->logger->addEntry(
                [
                    'action' => 36,
                    'owner_id' => CURRENT_USER_ID,
                    'affected_account_name' => $this->name,
                ]
            );
        }

        return true;
    }
}
