<?php
/**
 * Class that handles all the actions and functions regarding workspaces memberships.
 *
 * @package    ProjectSend
 * @subpackage Classes
 */

namespace ProjectSend\Classes;

use \PDO;

class WorkspacesUsers
{
    private $dbh;
    private $logger;

    public $user    = '';
    public $workspaces    = '';

    public function __construct(PDO $dbh = null)
    {
        if (empty($dbh)) {
            global $dbh;
        }

        $this->dbh = $dbh;
        $this->logger = new ActionsLog;
    }

    public function workspace_add_users($arguments)
    {
        $user_ids = is_array($arguments['user_id']) ? $arguments['user_id'] : array( $arguments['user_id'] );
        $workspace_id = $arguments['workspace_id'];
        $added_by = $arguments['added_by'];

        $results = array(
            'added' => 0,
            'queue' => count($user_ids),
            'errors' => array(),
        );

        foreach ($user_ids as $user_id) {
            $statement = $this->dbh->prepare(
                "INSERT INTO " . TABLE_WORKSPACES_USERS . " (added_by,user_id,workspace_id)"
                ." VALUES (:added_by, :id, :workspace)"
            );
            $statement->bindParam(':added_by', $added_by);
            $statement->bindParam(':id', $user_id, PDO::PARAM_INT);
            $statement->bindParam(':workspace', $workspace_id, PDO::PARAM_INT);
            $status = $statement->execute();
            
            if ($status) {
                $results['added']++;
            } else {
                $results['errors'][] = array(
                    'user' => $user_id,
                );
            }
        }
        
        return $results;
    }

    public function workspace_remove_users($arguments)
    {
        $user_ids = is_array($arguments['user_id']) ? $arguments['user_id'] : array( $arguments['user_id'] );
        $workspace_id = $arguments['workspace_id'];

        $results = array(
            'removed' => 0,
            'queue' => count($user_ids),
            'errors' => array(),
        );

        foreach ($user_ids as $user_id) {
            $statement = $this->dbh->prepare("DELETE FROM " . TABLE_WORKSPACES_USERS . " WHERE user_id = :user AND workspace_id = :workspace");
            $statement->bindParam(':user', $user_id, PDO::PARAM_INT);
            $statement->bindParam(':workspace_id', $workspace_id, PDO::PARAM_INT);
            $status = $statement->execute();
            
            if ($status) {
                $results['removed']++;
            } else {
                $results['errors'][] = array(
                    'user' => $user_id,
                );
            }
        }
        
        return $results;
    }

    public function user_get_workspaces($arguments)
    {
        $user_id = $arguments['user_id'];
        $return_type = !empty($arguments['return']) ? $arguments['return'] : 'array';

        $found_workspaces = array();
        $sql_workspaces = $this->dbh->prepare("SELECT DISTINCT workspace_id FROM " . TABLE_WORKSPACES_USERS . " WHERE user_id=:id");
        $sql_workspaces->bindParam(':id', $user_id, PDO::PARAM_INT);
        $sql_workspaces->execute();
        $count_workspaces = $sql_workspaces->rowCount();
    
        if ($count_workspaces > 0) {
            $sql_workspaces->setFetchMode(PDO::FETCH_ASSOC);
            while ($row_workspaces = $sql_workspaces->fetch()) {
                $found_workspaces[] = $row_workspaces["workspace_id"];
            }
        }
        
        switch ($return_type) {
            case 'array':
                $results = $found_workspaces;
                break;
            case 'list':
                $results = implode(',', $found_workspaces);
                break;
        }
        
        return $results;
    }

    public function user_add_to_workspaces($arguments)
    {
        $user_id = $arguments['user_id'];
        $workspace_ids = is_array($arguments['workspace_ids']) ? $arguments['workspace_ids'] : array( $arguments['workspace_ids'] );
        $added_by = $arguments['added_by'];
        
        if (in_array(CURRENT_USER_LEVEL, array(9,8)) || (defined('AUTOGROUP'))) {
            $results = array(
                'added' => 0,
                'queue' => count($workspace_ids),
                'errors' => array(),
            );
    
            foreach ($workspace_ids as $workspace_id) {
                $statement = $this->dbh->prepare(
                    "INSERT INTO " . TABLE_WORKSPACES_USERS . " (added_by,user_id,workspace_id)"
                    ." VALUES (:added_by, :id, :workspace)"
                );
                $statement->bindParam(':added_by', $added_by);
                $statement->bindParam(':id', $user_id, PDO::PARAM_INT);
                $statement->bindParam(':workspace', $workspace_id, PDO::PARAM_INT);
                $status = $statement->execute();
                
                if ($status) {
                    $results['added']++;
                } else {
                    $results['errors'][] = array(
                        'workspace' => $workspace_id,
                    );
                }
            }
            
            return $results;
        }
    }

    public function user_edit_workspaces($arguments)
    {
        $user_id = $arguments['user_id'];
        $workspace_ids = is_array($arguments['workspace_ids']) ? $arguments['workspace_ids'] : array( $arguments['workspace_ids'] );

        if (in_array(CURRENT_USER_LEVEL, array(9,8))) {
            $results = array(
                'added' => 0,
                'queue' => count($workspace_ids),
                'errors' => array(),
            );

            $found_workspaces = array();
            $sql_workspaces = $this->dbh->prepare("SELECT DISTINCT workspace_id FROM " . TABLE_WORKSPACES_USERS . " WHERE user_id=:id");
            $sql_workspaces->bindParam(':id', $user_id, PDO::PARAM_INT);
            $sql_workspaces->execute();
            $count_workspaces = $sql_workspaces->rowCount();
        
            if ($count_workspaces > 0) {
                $sql_workspaces->setFetchMode(PDO::FETCH_ASSOC);
                while ($row_workspaces = $sql_workspaces->fetch()) {
                    $found_workspaces[] = $row_workspaces["workspace_id"];
                }
            }
            
            /**
             * 1- Make an array of workspaces where the user is actually a member,
             * but they are not on the array of selected workspaces.
             */
            $remove_workspaces = array_diff($found_workspaces, $workspace_ids);

            if (!empty($remove_workspaces)) {
                $delete_ids = implode(',', $remove_workspaces);
                $statement = $this->dbh->prepare("DELETE FROM " . TABLE_WORKSPACES_USERS . " WHERE user_id=:user_id AND FIND_IN_SET(workspace_id, :delete)");
                $statement->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $statement->bindParam(':delete', $delete_ids);
                $statement->execute();
            }
             
            /**
             * 2- Make an array of workspaces in which the user is not a current member.
             */
            $new_workspaces = array_diff($workspace_ids, $found_workspaces);
            if (!empty($new_workspaces)) {
                $new_workspaces_add = new WorkspacesUsers();
                $add_arguments = array(
                    'user_id' => $user_id,
                    'workspace_ids' => $new_workspaces,
                    'added_by' => CURRENT_USER_USERNAME,
                );
                $results['new'] = $new_workspaces_add->user_add_to_workspaces($add_arguments);
            }
    
            return $results;
        }
    }
}
