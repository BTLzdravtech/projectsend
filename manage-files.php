<?php
/**
 * Allows to hide, show or delete the files assigend to the
 * selected client.
 *
 * @package ProjectSend
 */

use ProjectSend\Classes\TableGenerate;
use ProjectSend\Classes\Workspaces;

$allowed_levels = array(9, 8, 7, 0);
require_once 'bootstrap.php';

/** @var PDO $dbh */
global $dbh;

$active_nav = 'files';

if (isset($_GET['workspace'])) {
    $active_nav = 'workspaces';
}

$page_title = __('Manage files', 'cftp_admin');

$page_id = 'manage_files';

/**
 * Used to distinguish the current page results.
 * Global means all files.
 * Client or group is only when looking into files
 * assigned to any of them.
 */
$results_type = 'global';

/**
 * The client's id is passed on the URI.
 * Then get_client_by_id() gets all the other account values.
 */
if (isset($_GET['client_id'])) {
    $this_id = $_GET['client_id'];
    $this_client = get_client_by_id($this_id);

    /** Add the name of the client to the page's title. */
    if (!empty($this_client)) {
        $page_title .= ' ' . __('for client', 'cftp_admin') . ' ' . html_entity_decode($this_client['name']);
        $search_on = 'client_id';
        $results_type = 'client';
    }
}

/**
 * The group's id is passed on the URI also.
 */
if (isset($_GET['group_id'])) {
    $this_id = $_GET['group_id'];
    $group = get_group_by_id($this_id);

    /** Add the name of the client to the page's title. */
    if (!empty($group['name'])) {
        $page_title .= ' ' . __('for group', 'cftp_admin') . ' ' . html_entity_decode($group['name']);
        $search_on = 'group_id';
        $results_type = 'group';
    }
}

/**
 * Filtering by category
 */
if (isset($_GET['category'])) {
    $this_id = $_GET['category'];
    $this_category = get_category($this_id);

    /** Add the name of the client to the page's title. */
    if (!empty($this_category)) {
        $page_title .= ' ' . __('on category', 'cftp_admin') . ' ' . html_entity_decode($this_category['name']);
        $results_type = 'category';
    }
}

$clients = array();

if (CURRENT_USER_LEVEL == 8 || CURRENT_USER_LEVEL == 7) {

    /** Fill the users array that will be used on the notifications process */
    $statement = $dbh->prepare("SELECT id FROM " . TABLE_USERS . " WHERE level=0 AND owner_id=" . CURRENT_USER_ID);
    $statement->execute();
    $statement->setFetchMode(PDO::FETCH_ASSOC);
    while ($row = $statement->fetch()) {
        $clients[] = $row["id"];
    }
}

include_once ADMIN_VIEWS_DIR . DS . 'header.php';
?>

    <div class="col-xs-12">
        <?php
        /**
         * Apply the corresponding action to the selected files.
         */
        if (isset($_GET['action'])) {
            /** Continue only if 1 or more files were selected. */
            if (!empty($_GET['batch'])) {
                $selected_files = array_map('intval', array_unique($_GET['batch']));

                switch ($_GET['action']) {
                    case 'hide':
                        /**
                         * Changes the value on the "hidden" column value on the database.
                         * This files are not shown on the client's file list. They are
                         * also not counted on the dashboard.php files count when the logged in
                         * account is the client.
                         */
                        foreach ($selected_files as $work_file) {
                            $this_file = new ProjectSend\Classes\FilesActions;
                            $hide_file = $this_file->changeHiddenStatus('1', $work_file, $_GET['modify_type'], $_GET['modify_id']);
                        }
                        $msg = __('The selected files were marked as hidden.', 'cftp_admin');
                        echo system_message('success', $msg);
                        break;

                    case 'show':
                        /**
                         * Reverse of the previous action. Setting the value to 0 means
                         * that the file is visible.
                         */
                        foreach ($selected_files as $work_file) {
                            $this_file = new ProjectSend\Classes\FilesActions;
                            $show_file = $this_file->changeHiddenStatus('0', $work_file, $_GET['modify_type'], $_GET['modify_id']);
                        }
                        $msg = __('The selected files were marked as visible.', 'cftp_admin');
                        echo system_message('success', $msg);
                        break;

                    case 'unassign':
                        /**
                         * Remove the file from this client or group only.
                         */
                        foreach ($selected_files as $work_file) {
                            $this_file = new ProjectSend\Classes\FilesActions;
                            $unassign_file = $this_file->unassignFile($work_file, $_GET['modify_type'], $_GET['modify_id']);
                        }
                        $msg = __('The selected files were unassigned from this client.', 'cftp_admin');
                        echo system_message('success', $msg);
                        break;

                    case 'delete':
                        $delete_results = array(
                            'ok' => 0,
                            'errors' => 0,
                        );
                        foreach ($selected_files as $index => $file_id) {
                            $this_file = new ProjectSend\Classes\FilesActions;
                            $delete_status = $this_file->deleteFiles($file_id);

                            if ($delete_status == true) {
                                $delete_results['ok']++;
                            } else {
                                $delete_results['errors']++;
                            }
                        }

                        if ($delete_results['ok'] > 0) {
                            $msg = __('The selected files were deleted.', 'cftp_admin');
                            echo system_message('success', $msg);
                        }
                        if ($delete_results['errors'] > 0) {
                            $msg = __('Some files could not be deleted.', 'cftp_admin');
                            echo system_message('danger', $msg);
                        }
                        break;
                }
            } else {
                $msg = __('Please select at least one file.', 'cftp_admin');
                echo system_message('danger', $msg);
            }
        }

        /**
         * Global form action
         */
        $query_table_files = true;
        $sql = null;

        if (isset($search_on)) {
            $params = array();
            $rq = "SELECT * FROM " . TABLE_FILES_RELATIONS . " WHERE $search_on = :id";
            $params[':id'] = $this_id;

            /** Add the status filter */
            if (isset($_GET['hidden']) && $_GET['hidden'] != 'all') {
                $set_and = true;
                $rq .= " AND hidden = :hidden";
                $no_results_error = 'filter';

                $params[':hidden'] = $_GET['hidden'];
            }

            /**
             * Count the files assigned to this client. If there is none, show
             * an error message.
             */
            $sql = $dbh->prepare($rq);
            $sql->execute($params);

            if ($sql->rowCount() > 0) {
                /**
                 * Get the IDs of files that match the previous query.
                 */
                $sql->setFetchMode(PDO::FETCH_ASSOC);
                while ($row_files = $sql->fetch()) {
                    $files_ids[] = $row_files['file_id'];
                    $gotten_files = implode(',', $files_ids);
                }
            } else {
                $count = 0;
                $no_results_error = 'filter';
                $query_table_files = false;
            }
        }

        if ($query_table_files === true) {
            /**
             * Get the files
             */
            $params = array();

            /**
             * Add the download count to the main query.
             * If the page is filtering files by client, then
             * add the client ID to the subquery.
             */
            $add_user_to_query = '';
            if (isset($search_on) && $results_type == 'client') {
                $add_user_to_query = "AND user_id = :user_id";
                $params[':user_id'] = $this_id;
            }
            $cq = "SELECT files.*, SUM(IF(downloads.id IS NULL, 0, 1)) as download_count FROM " . TABLE_FILES . " files LEFT JOIN " . TABLE_DOWNLOADS . " downloads ON files.id = downloads.file_id " . $add_user_to_query;

            if (isset($search_on) && !empty($gotten_files)) {
                $conditions[] = "FIND_IN_SET(id, :files)";
                $params[':files'] = $gotten_files;
            }

            /** Add the search terms */
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $conditions[] = "(filename LIKE :name OR description LIKE :description)";
                $no_results_error = 'search';

                $search_terms = '%' . $_GET['search'] . '%';
                $params[':name'] = $search_terms;
                $params[':description'] = $search_terms;
            }

            /**
             * Filter by uploader
             */
            if (isset($_GET['uploader']) && !empty($_GET['uploader'])) {
                $conditions[] = "uploader = :uploader";
                $no_results_error = 'filter';

                $params[':uploader'] = $_GET['uploader'];
            }

            /**
             * Filter by workspace
             */
            if (isset($_GET['workspace']) && !empty($_GET['workspace'])) {
                $workspace_object = new Workspaces($dbh);
                $workspace_object->get($_GET['workspace']);
                $workspace_data = $workspace_object->getProperties();

                $workspace_condition = '';
                foreach (array_diff(array_merge($workspace_data['admins'], $workspace_data['users']), [CURRENT_USER_ID]) as $key => $client) {
                    if (strlen($workspace_condition) > 0) {
                        $workspace_condition .= ",";
                    }
                    $workspace_condition .= ":user" . $key;
                    $params[":user" . $key] = $client;
                }
                $conditions[] = "workspace_included = 1";
                $conditions[] = "(owner_id = :owner_id_workspace" . (strlen($workspace_condition) > 0 ? " OR owner_id IN (" . $workspace_condition . ")" : "") . ")";
                $no_results_error = 'filter';

                $params[':owner_id_workspace'] = CURRENT_USER_ID;
            } else {

                /**
                 * If the user is an account manager is editing his files
                 * only show files uploaded by that account.
                 */
                if (CURRENT_USER_LEVEL == '8') {
                    $params[':owner_id'] = CURRENT_USER_ID;

                    $files_query = "SELECT FR.id, FR.file_id, FR.client_id, FR.group_id FROM " . TABLE_FILES_RELATIONS . " FR INNER JOIN " . TABLE_FILES . " F ON FR.file_id = F.id WHERE (FR.user_id = :id";
                    $files_query .= ") AND FR.hidden = '0' AND (F.expires = 0 OR (F.expires = 1 AND F.expiry_date > NOW()))";
                    $files_sql = $dbh->prepare($files_query);

                    $files_sql->bindParam(':id', $params[':owner_id'], PDO::PARAM_INT);
                    $files_sql->execute();
                    $files_sql->setFetchMode(PDO::FETCH_ASSOC);

                    $files_from_other_users_ids = array();
                    while ($row_files = $files_sql->fetch()) {
                        $files_from_other_users_ids[] = $row_files['file_id'];
                    }
                    $users_condition = count($files_from_other_users_ids) > 0 ? "(files.id IN (" . join(",", $files_from_other_users_ids) . ") OR " : "";

                    $clients_condition = '';
                    foreach ($clients as $key => $client) {
                        if (strlen($clients_condition) > 0) {
                            $clients_condition .= ",";
                        }
                        $clients_condition .= ":client" . $key;
                        $params[":client" . $key] = $client;
                    }
                    if ($users_condition != "") {
                        $conditions[] = $users_condition . "owner_id = :owner_id" . (strlen($clients_condition) > 0 ? " OR owner_id IN (" . $clients_condition . ")" : "") . ")";
                    } else {
                        $conditions[] = "(owner_id = :owner_id" . (strlen($clients_condition) > 0 ? " OR owner_id IN (" . $clients_condition . ")" : "") . ")";
                    }
                    $no_results_error = 'account_level';
                }
            }

            /**
             * If the user is an uploader, or a client is editing his files
             * only show files uploaded by that account.
             */
            if (CURRENT_USER_LEVEL == '7' || CURRENT_USER_LEVEL == '0') {
                $clients_condition = '';
                foreach ($clients as $key => $client) {
                    if (strlen($clients_condition) > 0) {
                        $clients_condition .= ",";
                    }
                    $clients_condition .= ":client" . $key;
                    $params[":client" . $key] = $client;
                }
                $conditions[] = "(owner_id = :owner_id" . (strlen($clients_condition) > 0 ? " OR owner_id IN (" . $clients_condition . ")" : "") . ")";
                //$conditions[] = "uploader = :uploader";
                $no_results_error = 'account_level';

                $params[':owner_id'] = CURRENT_USER_ID;
                //$params[':uploader'] = $global_user;
            }

            /**
             * Add the category filter
             */
            if (isset($results_type) && $results_type == 'category') {
                $files_id_by_cat = array();
                $statement = $dbh->prepare("SELECT file_id FROM " . TABLE_CATEGORIES_RELATIONS . " WHERE cat_id = :cat_id");
                $statement->bindParam(':cat_id', $this_category['id'], PDO::PARAM_INT);
                $statement->execute();
                $statement->setFetchMode(PDO::FETCH_ASSOC);
                while ($file_data = $statement->fetch()) {
                    $files_id_by_cat[] = $file_data['file_id'];
                }
                $files_id_by_cat = implode(',', $files_id_by_cat);

                /** Overwrite the parameter set previously */
                $conditions[] = "FIND_IN_SET(id, :files)";
                $params[':files'] = $files_id_by_cat;

                $no_results_error = 'category';
            }

            /**
             * Build the final query
             */
            if (!empty($conditions)) {
                foreach ($conditions as $index => $condition) {
                    $cq .= ($index == 0) ? ' WHERE ' : ' AND ';
                    $cq .= $condition;
                }
            }

            $cq .= " GROUP BY files.id";

            /**
             * Add the order.
             * Defaults to order by: date, order: ASC
             */
            $cq .= sql_add_order(TABLE_FILES, 'timestamp', 'desc');

            /**
             * Pre-query to count the total results
             */
            $count_sql = $dbh->prepare($cq);
            $count_sql->execute($params);
            $count_for_pagination = $count_sql->rowCount();

            /**
             * Repeat the query but this time, limited by pagination
             */
            $cq .= " LIMIT :limit_start, :limit_number";
            $sql = $dbh->prepare($cq);

            $pagination_page = (isset($_GET["page"])) ? $_GET["page"] : 1;
            $pagination_start = ($pagination_page - 1) * RESULTS_PER_PAGE;
            $params[':limit_start'] = $pagination_start;
            $params[':limit_number'] = RESULTS_PER_PAGE;

            $sql->execute($params);
            $count = $sql->rowCount();

        /** Debug query */
            //echo $cq;
            //print_r( $conditions );
        } else {
            $count_for_pagination = 0;
        }
        ?>
        <div class="form_actions_left">
            <div class="form_actions_limit_results">
                <?php show_search_form('manage-files.php'); ?>

                <?php
                if (CURRENT_USER_LEVEL == 9 || CURRENT_USER_LEVEL == 8) {
                    ?>
                    <form action="manage-files.php" name="files_filters" method="get" class="form-inline form_filters">
                        <?php form_add_existing_parameters(array('hidden', 'action', 'workspace')); ?>
                        <div class="form-group group_float">
                            <select name="workspace" id="workspace" class="txtfield form-control">
                                <?php
                                $status_options = array(
                                    '0' => __('Workspace', 'cftp_admin'),
                                );

                    $sql_workspaces = $dbh->prepare("SELECT W.id, W.name FROM " . TABLE_WORKSPACES . " W INNER JOIN " . TABLE_WORKSPACES_USERS . " WU ON W.id = WU.workspace_id AND WU.user_id = " . CURRENT_USER_ID . " GROUP BY W.id");
                    $sql_workspaces->execute();
                    $sql_workspaces->setFetchMode(PDO::FETCH_ASSOC);

                    while ($data_workspaces = $sql_workspaces->fetch()) {
                        $status_options[$data_workspaces['id']] = $data_workspaces['name'];
                    }

                    foreach ($status_options as $val => $text) {
                        ?>
                                    <option value="<?php echo $val; ?>" <?php echo isset($_GET['workspace']) && $_GET['workspace'] == $val ? 'selected="selected"' : ''; ?>><?php echo $text; ?></option>
                                    <?php
                    } ?>
                            </select>
                        </div>
                        <button type="submit" id="btn_proceed_filter_clients"
                                class="btn btn-sm btn-default"><?php _e('Filter', 'cftp_admin'); ?></button>
                    </form>
                    <?php
                }

                if (CURRENT_USER_LEVEL != '0' && $results_type == 'global') {
                    ?>
                    <form action="manage-files.php" name="files_filters" method="get" class="form-inline form_filters">
                        <?php form_add_existing_parameters(array('hidden', 'action', 'uploader')); ?>
                        <div class="form-group group_float">
                            <select name="uploader" id="uploader" class="txtfield form-control">
                                <?php
                                $status_options = array(
                                    '0' => __('Uploader', 'cftp_admin'),
                                );

                    $owner_condition = '';
                    if (CURRENT_USER_LEVEL == 8 || CURRENT_USER_LEVEL == 7) {
                        if (CURRENT_USER_LEVEL == 8 && isset($_GET['workspace']) && !empty($_GET['workspace'])) {
                            $workspace_object = new Workspaces($dbh);
                            $workspace_object->get($_GET['workspace']);
                            $workspace_data = $workspace_object->getProperties();

                            $owner_condition = " WHERE owner_id=" . CURRENT_USER_ID . (count(array_diff(array_merge($workspace_data['admins'], $workspace_data['users']), [CURRENT_USER_ID])) > 0 ? " OR owner_id IN(" . implode(',', array_diff(array_merge($workspace_data['admins'], $workspace_data['users']), [CURRENT_USER_ID])) . ")" : "") . (count($files_from_other_users_ids) > 0 ? " OR id IN (" . join(",", $files_from_other_users_ids) . ")" : "");
                        } else {
                            $owner_condition = " WHERE owner_id=" . CURRENT_USER_ID . (count($clients) > 0 ? " OR owner_id IN(" . implode(',', $clients) . ")" : "") . (count($files_from_other_users_ids) > 0 ? " OR id IN (" . join(",", $files_from_other_users_ids) . ")" : "");
                        }
                    }

                    $sql_uploaders = $dbh->prepare("SELECT uploader FROM " . TABLE_FILES . $owner_condition . " GROUP BY uploader");
                    $sql_uploaders->execute();
                    $sql_uploaders->setFetchMode(PDO::FETCH_ASSOC);

                    while ($data_uploaders = $sql_uploaders->fetch()) {
                        $status_options[$data_uploaders['uploader']] = $data_uploaders['uploader'];
                    }

                    foreach ($status_options as $val => $text) {
                        ?>
                                    <option value="<?php echo $val; ?>" <?php echo isset($_GET['uploader']) && $_GET['uploader'] == $val ? 'selected="selected"' : ''; ?>><?php echo $text; ?></option>
                                    <?php
                    } ?>
                            </select>
                        </div>
                        <button type="submit" id="btn_proceed_filter_clients"
                                class="btn btn-sm btn-default"><?php _e('Filter', 'cftp_admin'); ?></button>
                    </form>
                    <?php
                }

                /** Filters are not available for clients */
                if (CURRENT_USER_LEVEL != '0' && $results_type != 'global') {
                    ?>
                    <form action="manage-files.php" name="files_filters" method="get" class="form-inline form_filters">
                        <?php form_add_existing_parameters(array('hidden', 'action', 'uploader')); ?>
                        <div class="form-group group_float">
                            <select name="hidden" id="hidden" class="txtfield form-control">
                                <?php
                                $status_options = array(
                                    '2' => __('All statuses', 'cftp_admin'),
                                    '0' => __('Visible', 'cftp_admin'),
                                    '1' => __('Hidden', 'cftp_admin'),
                                );

                    foreach ($status_options as $val => $text) {
                        ?>
                                    <option value="<?php echo $val; ?>" <?php echo isset($_GET['hidden']) && $_GET['hidden'] == $val ? 'selected="selected"' : ''; ?>><?php echo $text; ?></option>
                                    <?php
                    } ?>
                            </select>
                        </div>
                        <button type="submit" id="btn_proceed_filter_clients"
                                class="btn btn-sm btn-default"><?php _e('Filter', 'cftp_admin'); ?></button>
                    </form>
                    <?php
                }
                ?>
            </div>
        </div>


        <form action="manage-files.php" name="files_list" method="get" class="form-inline batch_actions">
            <?php form_add_existing_parameters(array('modify_id', 'modify_type')); ?>
            <?php
            /** Actions are not available for clients */
            /** @noinspection PhpUndefinedConstantInspection */
            if (CURRENT_USER_LEVEL != '0' || CLIENTS_CAN_DELETE_OWN_FILES == '1') {
                ?>
                <div class="form_actions_right">
                    <div class="form_actions">
                        <div class="form_actions_submit">
                            <div class="form-group group_float">
                                <label for="action" class="control-label hidden-xs hidden-sm"><i
                                            class="glyphicon glyphicon-check"></i> <?php _e('Selected files actions', 'cftp_admin'); ?>
                                    :</label>
                                <?php
                                if (isset($search_on)) {
                                    ?>
                                    <input type="hidden" name="modify_type" id="modify_type"
                                           value="<?php echo $search_on; ?>"/>
                                    <input type="hidden" name="modify_id" id="modify_id"
                                           value="<?php echo $this_id; ?>"/>
                                    <?php
                                } ?>
                                <select name="action" id="action" class="txtfield form-control">
                                    <?php
                                    if (CURRENT_USER_LEVEL != '0') {
                                        $actions_options = array(
                                            'none' => __('Select action', 'cftp_admin'),
                                            'zip' => __('Download zip', 'cftp_admin'),
                                        );
                                    } else {
                                        $actions_options = array(
                                            'none' => __('Select action', 'cftp_admin'),
                                        );
                                    }

                /** Options only available when viewing a client/group files list */
                if (isset($search_on)) {
                    $actions_options['hide'] = __('Hide', 'cftp_admin');
                    $actions_options['show'] = __('Show', 'cftp_admin');
                    $actions_options['unassign'] = __('Unassign', 'cftp_admin');
                } else {
                    $actions_options['delete'] = __('Delete', 'cftp_admin');
                }

                foreach ($actions_options as $val => $text) {
                    ?>
                                        <option value="<?php echo $val; ?>"><?php echo $text; ?></option>
                                        <?php
                } ?>
                                </select>
                            </div>
                            <button type="submit" id="do_action"
                                    class="btn btn-sm btn-default"><?php _e('Proceed', 'cftp_admin'); ?></button>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>

            <div class="clear"></div>

            <div class="form_actions_count">
                <p class="form_count_total"><?php _e('Found', 'cftp_admin'); ?>:
                    <span><?php echo $count_for_pagination; ?> <?php _e('files', 'cftp_admin'); ?></span></p>
            </div>

            <div class="clear"></div>

            <?php
            if (!$count) {
                if (isset($no_results_error)) {
                    switch ($no_results_error) {
                        case 'search':
                            $no_results_message = __('Your search keywords returned no results.', 'cftp_admin');
                            break;
                        case 'category':
                            $no_results_message = __('There are no files assigned to this category.', 'cftp_admin');
                            break;
                        case 'filter':
                            $no_results_message = __('The filters you selected returned no results.', 'cftp_admin');
                            break;
                        case 'none_assigned':
                            $no_results_message = __('There are no files assigned to this client.', 'cftp_admin');
                            break;
                        case 'account_level':
                            $no_results_message = __('You have not uploaded any files for this account.', 'cftp_admin');
                            break;
                    }
                } else {
                    $no_results_message = __('There are no files for this client.', 'cftp_admin');
                }
                echo system_message('danger', $no_results_message);
            }

            if ($count_for_pagination > 0) {
                /**
                 * Generate the table using the class.
                 */
                $table_attributes = array(
                    'id' => 'files_tbl',
                    'class' => 'footable table',
                );
                $table = new TableGenerate($table_attributes);

                /**
                 * Set the conditions to true or false once here to
                 * avoid repetition
                 * They will be used to generate or no certain columns
                 */
                /** @noinspection PhpUndefinedConstantInspection */
                $conditions = array(
                    'select_all' => (CURRENT_USER_LEVEL != '0' || CLIENTS_CAN_DELETE_OWN_FILES == '1') ? true : false,
                    'is_not_client' => (CURRENT_USER_LEVEL != '0') ? true : false,
                    'total_downloads' => (CURRENT_USER_LEVEL != '0' && !isset($search_on)) ? true : false,
                    'is_search_on' => (isset($search_on)) ? true : false,
                );

                $thead_columns = array(
                    array(
                        'select_all' => true,
                        'attributes' => array(
                            'class' => array('td_checkbox'),
                        ),
                        'condition' => $conditions['select_all'],
                    ),
                    array(
                        'content' => __('Thumbnail', 'cftp_admin'),
                        'hide' => 'phone,tablet',
                    ),
                    array(
                        'sortable' => true,
                        'sort_url' => 'timestamp',
                        'sort_default' => true,
                        'content' => __('Added on', 'cftp_admin'),
                        'hide' => 'phone',
                    ),
                    array(
                        'content' => __('Type', 'cftp_admin'),
                        'hide' => 'phone,tablet',
                    ),
                    array(
                        'sortable' => true,
                        'sort_url' => 'filename',
                        'content' => __('Title', 'cftp_admin'),
                    ),
                    array(
                        'content' => __('Size', 'cftp_admin'),
                    ),
                    array(
                        'sortable' => true,
                        'sort_url' => 'uploader',
                        'content' => __('Uploader', 'cftp_admin'),
                        'hide' => 'phone,tablet',
                        'condition' => $conditions['is_not_client'],
                    ),
                    array(
                        'content' => __('Assigned', 'cftp_admin'),
                        'hide' => 'phone',
                        'condition' => ($conditions['is_not_client'] && !$conditions['is_search_on']),
                    ),
                    array(
                        'sortable' => true,
                        'sort_url' => 'public_allow',
                        'content' => __('Public permissions', 'cftp_admin'),
                        'hide' => 'phone',
                        'condition' => $conditions['is_not_client'],
                    ),
                    array(
                        'content' => __('Expiry', 'cftp_admin'),
                        'hide' => 'phone',
                        'condition' => $conditions['is_not_client'],
                    ),
                    array(
                        'content' => __('Status', 'cftp_admin'),
                        'hide' => 'phone',
                        'condition' => $conditions['is_search_on'],
                    ),
                    array(
                        'sortable' => true,
                        'sort_url' => 'download_count',
                        'content' => __('Download count', 'cftp_admin'),
                        'hide' => 'phone',
                        'condition' => $conditions['is_search_on'],
                    ),
                    array(
                        'sortable' => true,
                        'sort_url' => 'download_count',
                        'content' => __('Total downloads', 'cftp_admin'),
                        'hide' => 'phone',
                        'condition' => $conditions['total_downloads'],
                    ),
                    array(
                        'content' => __('Actions', 'cftp_admin'),
                        'hide' => 'phone',
                    ),
                );

                //echo '<pre>';
                //print_r($thead_columns);
                //echo '</pre>';

                $table->thead($thead_columns);


                $sql->setFetchMode(PDO::FETCH_ASSOC);
                while ($row = $sql->fetch()) {
                    $table->addRow();

                    /**
                     * Prepare the information to be used later on the cells array
                     */
                    $file_id = $row['id'];
                    $download_link = make_download_link(array('id' => $file_id));

                    /**
                     * Visibility is only available when filtering by client or group.
                     */
                    $assignations = get_file_assignations($row['id']);

                    $count_assignations = 0;
                    if (!empty($assignations['users'])) {
                        $count_assignations += count($assignations['users']);
                    }
                    if (!empty($assignations['clients'])) {
                        $count_assignations += count($assignations['clients']);
                    }
                    if (!empty($assignations['groups'])) {
                        $count_assignations += count($assignations['groups']);
                    }

                    switch ($results_type) {
                        case 'client':
                            $hidden = $assignations['clients'][$this_id]['hidden'];
                            break;
                        case 'group':
                            $hidden = $assignations['groups'][$this_id]['hidden'];
                            break;
                    }

                    $date = format_date($row['timestamp']);

                    $file_absolute_path = UPLOADED_FILES_DIR . DS . $row['url'];

                    /**
                     * Get file size only if the file exists
                     */
                    $this_file_absolute = UPLOADED_FILES_DIR . DS . $row['url'];
                    if (file_exists($this_file_absolute)) {
                        $this_file_size = get_real_size($this_file_absolute);
                        $formatted_size = html_output(format_file_size($this_file_size));
                    } else {
                        $this_file_size = '0';
                        $formatted_size = '-';
                    }

                    /***/
                    $pathinfo = pathinfo($row['url']);
                    $extension = (!empty($pathinfo['extension'])) ? strtolower($pathinfo['extension']) : '';

                    /** Thumbnail */
                    $thumbnail_cell = '';
                    if (file_is_image($file_absolute_path)) {
                        $thumbnail = make_thumbnail($file_absolute_path, null, 50, 50);
                        if (!empty($thumbnail['thumbnail']['url'])) {
                            $thumbnail_cell = '<img src="' . $thumbnail['thumbnail']['url'] . '" class="thumbnail" />';
                        }
                    }

                    /** Is file assigned? */
                    $assigned_class = ($count_assignations == 0) ? 'danger' : 'success';
                    $assigned_status = ($count_assignations == 0) ? __('No', 'cftp_admin') : __('Yes', 'cftp_admin');

                    /**
                     * Visibility
                     */
                    if ($row['public_allow'] == '1') {
                        $visibility_link = '<a href="javascript:void(0);" class="btn btn-primary btn-sm public_link" data-type="file" data-id="' . $row['id'] . '" data-token="' . html_output($row['public_token']) . '">';
                        $visibility_label = __('URL', 'cftp_admin');
                    } else {
                        /** @noinspection PhpUndefinedConstantInspection */
                        if (ENABLE_LANDING_FOR_ALL_FILES == '1') {
                            $visibility_link = '<a href="javascript:void(0);" class="btn btn-default btn-sm public_link" data-type="file" data-id="' . $row['id'] . '" data-token="' . html_output($row['public_token']) . '">';
                            $visibility_label = __('View information', 'cftp_admin');
                        } else {
                            $visibility_link = '<a href="javascript:void(0);" class="btn btn-default btn-sm disabled" title="">';
                            $visibility_label = __('None', 'cftp_admin');
                        }
                    }

                    /**
                     * Expiration
                     */
                    if ($row['expires'] == '0') {
                        $expires_button = 'success';
                        $expires_label = __('Does not expire', 'cftp_admin');
                    } else {
                        /** @noinspection PhpUndefinedConstantInspection */
                        $expires_date = date(TIMEFORMAT, strtotime($row['expiry_date']));

                        if (time() > strtotime($row['expiry_date'])) {
                            $expires_button = 'danger';
                            $expires_label = __('Expired on', 'cftp_admin') . ' ' . $expires_date;
                        } elseif (strtotime($row['expiry_date']) - time() < 172800) {
                            $expires_button = 'warning';
                            $expires_label = __('Expired on', 'cftp_admin') . ' ' . $expires_date;
                        } else {
                            $expires_button = 'success';
                            $expires_label = __('Expires on', 'cftp_admin') . ' ' . $expires_date;
                        }
                    }

                    /**
                     * Visibility
                     */
                    $status_label = '';
                    $status_class = '';
                    if (isset($search_on)) {
                        $status_class = ($hidden == 1) ? 'danger' : 'success';
                        $status_label = ($hidden == 1) ? __('Hidden', 'cftp_admin') : __('Visible', 'cftp_admin');
                    }

                    /**
                     * Download count when filtering by group or client
                     */
                    if (isset($search_on)) {
                        $download_count_content = $row['download_count'] . ' ' . __('times', 'cftp_admin');

                        switch ($results_type) {
                            case 'client':
                                break;
                            case 'group':
                            case 'category':
                                $download_count_class = ($row['download_count'] > 0) ? 'downloaders btn-primary' : 'btn-default disabled';
                                $download_count_content = '<a href="' . BASE_URI . 'download-information.php?id=' . $row['id'] . '" class="' . $download_count_class . ' btn btn-sm" title="' . html_output($row['filename']) . '">' . $download_count_content . '</a>';
                                break;
                        }
                    }

                    /**
                     * Download count and link on the unfiltered files table
                     * (no specific client or group selected)
                     */
                    if (!isset($search_on)) {
                        if (CURRENT_USER_LEVEL != '0') {
                            if ($row["download_count"] > 0) {
                                $btn_class = 'downloaders btn-primary';
                            } else {
                                $btn_class = 'btn-default disabled';
                            }

                            $downloads_table_link = '<a href="' . BASE_URI . 'download-information.php?id=' . $row['id'] . '" class="' . $btn_class . ' btn btn-sm" title="' . html_output($row['filename']) . '">' . $row["download_count"] . ' ' . __('downloads', 'cftp_admin') . '</a>';
                        }
                    }

                    /**
                     * Add the cells to the row
                     */
                    $tbody_cells = array(
                        array(
                            'checkbox' => true,
                            'value' => $row["id"],
                            'condition' => $conditions['select_all'],
                        ),
                        array(
                            'content' => $thumbnail_cell,
                        ),
                        array(
                            'content' => $date,
                        ),
                        array(
                            'content' => html_output($extension),
                        ),
                        array(
                            'attributes' => array(
                                'class' => array('file_name'),
                            ),
                            'content' => $conditions['is_not_client'] ? '<a href="' . $download_link . '" target="_blank">' . html_output($row['filename']) . '</a>' : html_output($row['filename']),
                        ),
                        array(
                            'content' => $formatted_size,
                        ),
                        array(
                            'content' => html_output($row['uploader']),
                            'condition' => $conditions['is_not_client'],
                        ),
                        array(
                            'content' => '<span class="label label-' . $assigned_class . '">' . $assigned_status . '</span>',
                            'condition' => ($conditions['is_not_client'] && !$conditions['is_search_on']),
                        ),
                        array(
                            'attributes' => array(
                                'class' => array('col_visibility'),
                            ),
                            'content' => $visibility_link . $visibility_label . '</a>',
                            'condition' => $conditions['is_not_client'],
                        ),
                        array(
                            'content' => '<a href="javascript:void(0);" class="btn btn-' . $expires_button . ' disabled btn-sm" rel="" title="">' . $expires_label . '</a>',
                            'condition' => $conditions['is_not_client'],
                        ),
                        array(
                            'content' => '<span class="label label-' . $status_class . '">' . $status_label . '</span>',
                            'condition' => $conditions['is_search_on'],
                        ),
                        array(
                            'content' => (!empty($download_count_content)) ? $download_count_content : false,
                            'condition' => $conditions['is_search_on'],
                        ),
                        array(
                            'content' => (!empty($downloads_table_link)) ? $downloads_table_link : false,
                            'condition' => $conditions['total_downloads'],
                        ),
                        array(
                            'content' => $row["owner_id"] == CURRENT_USER_ID ? '<a href="edit-file.php?file_id=' . $row["id"] . '" class="btn btn-primary btn-sm edit-button"><i class="fa fa-pencil"></i><span class="button_label">' . __('Edit', 'cftp_admin') . '</span></a><a href="manage-files.php?action=delete&batch%5B%5D=' . $row["id"] . '" class="btn btn-primary btn-sm delete-button"><i class="fa fa-trash"></i><span class="button_label">' . __('Delete', 'cftp_admin') . '</span></a>' : '',
                        ),
                    );

                    foreach ($tbody_cells as $cell) {
                        $table->addCell($cell);
                    }

                    $table->end_row();
                }


                echo $table->render();

                /**
                 * PAGINATION
                 */
                $pagination_args = array(
                    'link' => 'manage-files.php',
                    'current' => $pagination_page,
                    'pages' => ceil($count_for_pagination / RESULTS_PER_PAGE),
                );

                echo $table->pagination($pagination_args);
            }
            ?>
        </form>
    </div>

<?php
include_once ADMIN_VIEWS_DIR . DS . 'footer.php';
