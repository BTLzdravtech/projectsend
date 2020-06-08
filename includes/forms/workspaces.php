<?php
/**
 * Contains the form that is used when adding or editing workspaces.
 *
 * @package    ProjectSend
 * @subpackage Workspaces
 */

global $dbh;

global $workspaces_form_type;
global $workspace_id;
global $workspace_arguments;

switch ($workspaces_form_type) {
    case 'new_workspace':
        $submit_value = __('Create workspace', 'cftp_admin');
        $form_action = 'workspaces-add.php';
        break;
    case 'edit_workspace':
        $submit_value = __('Save workspace', 'cftp_admin');
        $form_action = 'workspaces-edit.php?id=' . $workspace_id;
        break;
}

if ($workspace_id == null) {
    $users_inner_join = " INNER JOIN " . TABLE_USERS . " U2 ON U.id = U2.id AND U2.id <> " . CURRENT_USER_ID;
} else {
    $users_inner_join = " INNER JOIN (SELECT U.* FROM " . TABLE_USERS . ' U LEFT JOIN ' . TABLE_WORKSPACES . " W ON U.id = W.owner_id AND W.id = " . $workspace_id . " WHERE W.owner_id IS NULL) U2 ON U.id = U2.id";
}

?>

<form action="<?php echo html_output($form_action); ?>" name="workspace_form" id="workspace_form" method="post"
      class="form-horizontal">
    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>"/>

    <div class="form-group">
        <label for="name" class="col-sm-4 control-label"><?php _e('Workspace name', 'cftp_admin'); ?></label>
        <div class="col-sm-8">
            <input type="text" name="name" id="name" class="form-control required"
                   value="<?php echo (isset($workspace_arguments['name'])) ? html_output(stripslashes($workspace_arguments['name'])) : ''; ?>"
                   required/>
        </div>
    </div>

    <div class="form-group">
        <label for="description" class="col-sm-4 control-label"><?php _e('Description', 'cftp_admin'); ?></label>
        <div class="col-sm-8">
            <textarea name="description" id="description" class="ckeditor form-control required"
                      required><?php echo (isset($workspace_arguments['description'])) ? html_output($workspace_arguments['description']) : ''; ?></textarea>
        </div>
    </div>

    <div class="form-group assigns">
        <label for="admins" class="col-sm-4 control-label"><?php _e('Admins', 'cftp_admin'); ?></label>
        <div class="col-sm-8">
            <select multiple="multiple" id="admins" class="form-control chosen-select" name="admins[]"
                    data-placeholder="<?php _e('Select one or more options. Type to search.', 'cftp_admin'); ?>">
                <?php
                $sql = $dbh->prepare("SELECT U.* FROM " . TABLE_USERS . " U" . $users_inner_join . " WHERE U.level IN ('9', '8') ORDER BY name");
                $sql->execute();
                $sql->setFetchMode(PDO::FETCH_ASSOC);

                while ($row = $sql->fetch()) {
                    ?>
                    <option value="<?php echo $row["id"]; ?>"
                        <?php
                        if ($workspaces_form_type == 'edit_workspace') {
                            if (!empty($workspace_arguments['admins'])) {
                                if (in_array($row["id"], $workspace_arguments['admins'])) {
                                    echo ' selected="selected"';
                                }
                            }
                        } ?>
                    ><?php echo html_output($row["name"]); ?></option>
                    <?php
                }
                ?>
            </select>
            <div class="list_mass_admins">
                <a href="#" class="btn btn-default add-all"
                   data-type="assigns"><?php _e('Add all', 'cftp_admin'); ?></a>
                <a href="#" class="btn btn-default remove-all"
                   data-type="assigns"><?php _e('Remove all', 'cftp_admin'); ?></a>
            </div>
        </div>
    </div>

    <div class="form-group assigns">
        <label for="members" class="col-sm-4 control-label"><?php _e('Members', 'cftp_admin'); ?></label>
        <div class="col-sm-8">
            <select multiple="multiple" id="members" class="form-control chosen-select" name="users[]"
                    data-placeholder="<?php _e('Select one or more options. Type to search.', 'cftp_admin'); ?>">
                <?php
                $sql = $dbh->prepare("SELECT U.* FROM " . TABLE_USERS . " U" . $users_inner_join . " WHERE U.level IN ('9', '8') ORDER BY name");
                $sql->execute();
                $sql->setFetchMode(PDO::FETCH_ASSOC);

                while ($row = $sql->fetch()) {
                    ?>
                    <option value="<?php echo $row["id"]; ?>"
                        <?php
                        if ($workspaces_form_type == 'edit_workspace') {
                            if (!empty($workspace_arguments['users'])) {
                                if (in_array($row["id"], $workspace_arguments['users'])) {
                                    echo ' selected="selected"';
                                }
                            }
                        } ?>
                    ><?php echo html_output($row["name"]); ?></option>
                    <?php
                }
                ?>
            </select>
            <div class="list_mass_members">
                <a href="#" class="btn btn-default add-all"
                   data-type="assigns"><?php _e('Add all', 'cftp_admin'); ?></a>
                <a href="#" class="btn btn-default remove-all"
                   data-type="assigns"><?php _e('Remove all', 'cftp_admin'); ?></a>
            </div>
        </div>
    </div>

    <div class="inside_form_buttons">
        <button type="submit" class="btn btn-wide btn-primary"><?php echo html_output($submit_value); ?></button>
    </div>
</form>
