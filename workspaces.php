<?php
/**
 * Show the list of current workspaces.
 *
 * @package    ProjectSend
 * @subpackage Workspaces
 */

use ProjectSend\Classes\TableGenerate;
use ProjectSend\Classes\Workspaces;
use ProjectSend\Classes\WorkspacesUsers;

$allowed_levels = array(9, 8);
require_once 'bootstrap.php';

global $dbh;

$active_nav = 'workspaces';

$page_title = __('Workspaces administration', 'cftp_admin');

/**
 * Used when viewing workspaces a certain client belongs to.
 */
if (!empty($_GET['member'])) {
    $member = get_user_by_id($_GET['member']);
    /**
     * Add the name of the client to the page's title.
     */
    if ($member) {
        $page_title = __('Workspaces where', 'cftp_admin') . ' ' . html_entity_decode($member['name']) . ' ' . __('is member', 'cftp_admin');
        $member_exists = 1;

        /**
         * Get workspaces where this user is member
         */
        $get_workspaces = new WorkspacesUsers();
        $get_arguments = array(
            'user_id' => $member['id'],
            'return' => 'list',
        );
        $found_workspaces = $get_workspaces->user_get_workspaces($get_arguments);
        if (empty($found_workspaces)) {
            $found_workspaces = '';
        }
    } else {
        $no_results_error = 'client_not_exists';
    }
}

require_once ADMIN_VIEWS_DIR . DS . 'header.php';
?>

    <div class="col-xs-12">

        <?php

        /**
         * Apply the corresponding action to the selected users.
         */
        if (isset($_GET['action']) && $_GET['action'] != 'none') {
            /**
             * Continue only if 1 or more users were selected.
             */
            if (!empty($_GET['batch'])) {
                $selected_workspaces = $_GET['batch'];

                switch ($_GET['action']) {
                    case 'delete':
                        $deleted_workspaces = 0;

                        foreach ($selected_workspaces as $workspace) {
                            $this_workspace = new Workspaces($dbh);
                            if ($this_workspace->get($workspace)) {
                                $delete_workspace = $this_workspace->delete();
                                $deleted_workspaces++;
                            }
                        }

                        if ($deleted_workspaces > 0) {
                            $msg = __('The selected workspaces were deleted.', 'cftp_admin');
                            echo system_message('success', $msg);
                        }
                        break;
                }
            } else {
                $msg = __('Please select at least one workspace.', 'cftp_admin');
                echo system_message('danger', $msg);
            }
        }

        $params = array();
        $cq = "SELECT W.id FROM " . TABLE_WORKSPACES . " W INNER JOIN " . TABLE_WORKSPACES_USERS . " WU ON W.id = WU.workspace_id";

        /**
         * Add the search terms
         */
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $cq .= " WHERE (name LIKE :name OR description LIKE :description)";
            $next_clause = ' AND';
            $no_results_error = 'search';

            $search_terms = '%' . $_GET['search'] . '%';
            $params[':name'] = $search_terms;
            $params[':description'] = $search_terms;
        } else {
            $next_clause = ' WHERE';
        }

        if (CURRENT_USER_LEVEL == '8') {
            $cq .= $next_clause . " WU.user_id = :user_id AND WU.admin = 1";
            $next_clause = ' AND';

            $params[':user_id'] = CURRENT_USER_ID;
        }

        /**
         * Add the member
         */
        if (isset($found_workspaces)) {
            if ($found_workspaces != '') {
                $cq .= $next_clause . " FIND_IN_SET(W.id, :workspaces)";
                $params[':workspaces'] = $found_workspaces;
            } else {
                $cq .= $next_clause . " id = NULL";
            }
            $no_results_error = 'is_not_member';
        }

        $cq .= ' GROUP BY W.id';

        /**
         * Add the order.
         * Defaults to order by: name, order: ASC
         */
        $cq .= sql_add_order(TABLE_WORKSPACES, 'name', 'asc');

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

        ?>

        <div class="form_actions_left">
            <div class="form_actions_limit_results">
                <?php show_search_form('workspaces.php'); ?>
            </div>
        </div>

        <form action="workspaces.php" name="workspaces_list" method="get" class="form-inline batch_actions">
            <?php form_add_existing_parameters(); ?>
            <div class="form_actions_right">
                <div class="form_actions">
                    <div class="form_actions_submit">
                        <div class="form-group group_float">
                            <label for="action" class="control-label hidden-xs hidden-sm"><i
                                        class="glyphicon glyphicon-check"></i> <?php _e('Selected workspaces actions', 'cftp_admin'); ?>
                                :</label>
                            <select name="action" id="action" class="txtfield form-control">
                                <?php
                                $actions_options = array(
                                    'none' => __('Select action', 'cftp_admin'),
                                    'delete' => __('Delete', 'cftp_admin'),
                                );
                                foreach ($actions_options as $val => $text) {
                                    ?>
                                    <option value="<?php echo $val; ?>"><?php echo $text; ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                        </div>
                        <button type="submit" id="do_action"
                                class="btn btn-sm btn-default"><?php _e('Proceed', 'cftp_admin'); ?></button>
                    </div>
                </div>
            </div>
            <div class="clear"></div>

            <div class="form_actions_count">
                <p><?php _e('Found', 'cftp_admin'); ?>:
                    <span><?php echo $count_for_pagination; ?><?php _e('workspaces', 'cftp_admin'); ?></span></p>
            </div>

            <div class="clear"></div>

            <?php
            if (!$count) {
                if (isset($no_results_error)) {
                    switch ($no_results_error) {
                        case 'search':
                            $no_results_message = __('Your search keywords returned no results.', 'cftp_admin');
                            break;
                        case 'filter':
                            $no_results_message = __('The filters you selected returned no results.', 'cftp_admin');
                            break;
                        case 'client_not_exists':
                            $no_results_message = __('The client does not exist.', 'cftp_admin');
                            break;
                        case 'is_not_member':
                            $no_results_message = __('There are no workspaces where this client is member.', 'cftp_admin');
                            break;
                    }
                } else {
                    $no_results_message = __('There are no workspaces created yet.', 'cftp_admin');
                }
                echo system_message('danger', $no_results_message);
            }

            if ($count > 0) {
                /**
                 * Generate the table using the class.
                 */
                $table_attributes = array(
                    'id' => 'workspaces_tbl',
                    'class' => 'footable table',
                );
                $table = new TableGenerate($table_attributes);

                $thead_columns = array(
                    array(
                        'select_all' => true,
                        'attributes' => array(
                            'class' => array('td_checkbox'),
                        ),
                    ),
                    array(
                        'sortable' => true,
                        'sort_url' => 'name',
                        'sort_default' => true,
                        'content' => __('Workspace name', 'cftp_admin'),
                    ),
                    array(
                        'sortable' => true,
                        'sort_url' => 'description',
                        'content' => __('Description', 'cftp_admin'),
                        'hide' => 'phone',
                    ),
                    array(
                        'content' => __('Users', 'cftp_admin'),
                    ),
                    array(
                        'sortable' => true,
                        'sort_url' => 'created_by',
                        'content' => __('Created by', 'cftp_admin'),
                        'hide' => 'phone',
                    ),
                    array(
                        'sortable' => true,
                        'sort_url' => 'timestamp',
                        'content' => __('Added on', 'cftp_admin'),
                        'hide' => 'phone',
                    ),
                    array(
                        'content' => __('Actions', 'cftp_admin'),
                        'hide' => 'phone',
                    ),
                );
                $table->thead($thead_columns);

                $sql->setFetchMode(PDO::FETCH_ASSOC);
                while ($row = $sql->fetch()) {
                    $table->addRow();

                    $workspace_object = new Workspaces($dbh);
                    $workspace_object->get($row["id"]);
                    $workspace_data = $workspace_object->getProperties();

                    /* Get workspace creation date */
                    $created_at = format_date($workspace_data['created_date']);

                    /**
                     * Add the cells to the row
                     */
                    $tbody_cells = array(
                        array(
                            'checkbox' => true,
                            'value' => $workspace_data["id"],
                        ),
                        array(
                            'content' => $workspace_data["name"],
                        ),
                        array(
                            'content' => $workspace_data["description"],
                        ),
                        array(
                            'content' => ((!empty($workspace_data['admins'])) ? count($workspace_data['admins']) : 0) + ((!empty($workspace_data['users'])) ? count($workspace_data['users']) : 0) - 1,
                        ),
                        array(
                            'content' => $workspace_data["created_by"],
                        ),
                        array(
                            'content' => $created_at,
                        ),
                        array(
                            'actions' => true,
                            'content' => '<a href="workspaces-edit.php?id=' . $workspace_data["id"] . '" class="btn btn-primary btn-sm"><i class="fa fa-pencil"></i><span class="button_label">' . __('Edit', 'cftp_admin') . '</span></a>' . "\n"
                        ),
                    );

                    foreach ($tbody_cells as $cell) {
                        $table->addCell($cell);
                    }

                    $table->end_row();
                }

                echo html_entity_decode($table->render());

                /**
                 * PAGINATION
                 */
                $pagination_args = array(
                    'link' => 'workspaces.php',
                    'current' => $pagination_page,
                    'pages' => ceil($count_for_pagination / RESULTS_PER_PAGE),
                );

                echo $table->pagination($pagination_args);
            }

            ?>
        </form>

    </div>

<?php
require_once ADMIN_VIEWS_DIR . DS . 'footer.php';
