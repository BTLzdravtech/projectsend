<?php
/**
 * Check if a workspace id exists on the database.
 * Used on the Edit workspace page.
 *
 * @param $id
 * @return bool
 */
function workspace_exists_id($id)
{
    /** @var PDO $dbh */
    global $dbh;

    $user_id = CURRENT_USER_ID;

    $statement = $dbh->prepare("SELECT W.* FROM " . TABLE_WORKSPACES . " W INNER JOIN " . TABLE_WORKSPACES_USERS . " WU ON W.id = WU.workspace_id" . (CURRENT_USER_LEVEL != 9 ? " AND WU.user_id=:user_id AND admin=1" : "") . " WHERE W.id=:id GROUP BY W.id");
    $statement->bindParam(':id', $id, PDO::PARAM_INT);
    if (CURRENT_USER_LEVEL != 9) {
        $statement->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    }
    $statement->execute();
    if ($statement->rowCount() > 0) {
        return true;
    } else {
        return false;
    }
}

/**
 * Get all the workspace information knowing only the id
 *
 * @param $id
 * @return array|bool
 */
function get_workspace_by_id($id)
{
    global $dbh;
    $statement = $dbh->prepare("SELECT * FROM " . TABLE_WORKSPACES . " WHERE id=:id");
    $statement->bindParam(':id', $id, PDO::PARAM_INT);
    $statement->execute();
    $statement->setFetchMode(PDO::FETCH_ASSOC);

    while ($row = $statement->fetch()) {
        $information = array(
            'id' => html_output($row['id']),
            'created_by' => html_output($row['created_by']),
            'created_date' => html_output($row['timestamp']),
            'name' => html_output($row['name']),
            'description' => html_output($row['description']),
        );
        if (!empty($information)) {
            return $information;
        } else {
            return false;
        }
    }
}

/**
 * Return an array of existing workspaces
 * @param $arguments
 * @return array|bool
 * @todo add limit and order to the query
 */
function get_workspaces($arguments)
{
    global $dbh;

    $workspace_ids = !empty($arguments['workspace_ids']) ? $arguments['workspace_ids'] : array();
    $workspace_ids = is_array($workspace_ids) ? $workspace_ids : array( $workspace_ids );
    $owner_id = !empty($arguments['owner_id']) ? $arguments['owner_id'] : '';
    $created_by = !empty($arguments['created_by']) ? $arguments['created_by'] : '';
    $search = !empty($arguments['search']) ? $arguments['search'] : '';

    $query = "SELECT * FROM " . TABLE_WORKSPACES;

    $parameters = array();
    if (!empty($workspace_ids)) {
        $parameters[] = "FIND_IN_SET(id, :ids)";
    }
    if (!empty($created_by)) {
        $parameters[] = "created_by=:created_by";
    }
    if (!empty($owner_id)) {
        $parameters[] = "owner_id=:owner_id";
    }
    if (!empty($search)) {
        $parameters[] = "(name LIKE :name OR description LIKE :description)";
    }
    
    if (!empty($parameters)) {
        $p = 1;
        foreach ($parameters as $parameter) {
            if ($p == 1) {
                $connector = " WHERE ";
            } else {
                $connector = " AND ";
            }
            $p++;
            
            $query .= $connector . $parameter;
        }
    }

    $statement = $dbh->prepare($query);

    if (!empty($workspace_ids)) {
        $workspace_ids = implode(',', $workspace_ids);
        $statement->bindParam(':ids', $workspace_ids);
    }
    if (!empty($created_by)) {
        $statement->bindParam(':created_by', $created_by);
    }
    if (!empty($owner_id)) {
        $statement->bindParam(':owner_id', $owner_id);
    }
    if (!empty($search)) {
        $search_value = '%' . $search . '%';
        $statement->bindValue(':name', $search_value);
        $statement->bindValue(':description', $search_value);
    }
    
    $statement->execute();
    $statement->setFetchMode(PDO::FETCH_ASSOC);

    $all_workspaces = array();
    while ($data_workspace = $statement->fetch()) {
        $all_workspaces[$data_workspace['id']] = array(
                                    'id'            => $data_workspace['id'],
                                    'name'          => $data_workspace['name'],
                                    'description'   => $data_workspace['description'],
                                    'created_by'    => $data_workspace['created_by'],
                                    'owner_id'    => $data_workspace['owner_id'],
                                );
    }
    
    if (!empty($all_workspaces) > 0) {
        return $all_workspaces;
    } else {
        return false;
    }
}

/**
 * Get a count of members assigned to a workspace
 *
 * @param int $workspace_id
 * @return int
 */
function count_members_on_workspace($workspace_id)
{
    global $dbh;

    $workspace_id = (int)$workspace_id;
    $allowed_levels = array(9,8);
    // Do a permissions check
    if (isset($allowed_levels) && current_role_in($allowed_levels)) {
        if (workspace_exists_id($workspace_id)) {
            $statement = $dbh->prepare("SELECT COUNT(user_id) as count FROM " . TABLE_WORKSPACES_USERS . " WHERE workspace_id = :workspace_id");
            $statement->bindValue(':workspace_id', $workspace_id, PDO::PARAM_INT);
            $statement->execute();
            $result = $statement->fetch();

            return $result['count'];
        } else {
            return 0;
        }
    } else {
        return 0;
    }
}

 /**
 * Delete an existing workspace.
 * @param int $workspace_id
 * @return bool
 */
function delete_workspace($workspace_id)
{
    global $dbh;

    $allowed_levels = array(9,8);
    if (isset($workspace_id)) {
        $workspace_id = (int)$workspace_id;
        // Do a permissions check
        if (isset($allowed_levels) && current_role_in($allowed_levels)) {
            $workspace_data = get_workspace_by_id($workspace_id);

            if (!empty($workspace_data)) {
                $statement = $dbh->prepare('DELETE FROM ' . TABLE_WORKSPACES . ' WHERE id=:id');
                $statement->bindParam(':id', $workspace_id, PDO::PARAM_INT);
                $statement->execute();

                // Record the action log
                $logger = new ProjectSend\Classes\ActionsLog;
                $log_action_args = array(
                                        'action' => 43,
                                        'owner_id' => CURRENT_USER_ID,
                                        'affected_account_name' => $workspace_data['name']
                                    );
                $logger->addEntry($log_action_args);

                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    } else {
        return false;
    }
}
