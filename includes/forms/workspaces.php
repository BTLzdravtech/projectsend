<?php
/**
 * Contains the form that is used when adding or editing workspaces.
 *
 * @package		ProjectSend
 * @subpackage	Workspaces
 *
 */

switch ($workspaces_form_type) {
	case 'new_workspace':
		$submit_value = __('Create workspace','cftp_admin');
		$form_action = 'workspaces-add.php';
		break;
	case 'edit_workspace':
		$submit_value = __('Save workspace','cftp_admin');
		$form_action = 'workspaces-edit.php?id='.$workspace_id;
		break;
}
?>

<form action="<?php echo html_output($form_action); ?>" name="workspace_form" id="workspace_form" method="post" class="form-horizontal">
    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>" />

	<div class="form-group">
		<label for="name" class="col-sm-4 control-label"><?php _e('Workspace name','cftp_admin'); ?></label>
		<div class="col-sm-8">
			<input type="text" name="name" id="name" class="form-control required" value="<?php echo (isset($workspace_arguments['name'])) ? html_output(stripslashes($workspace_arguments['name'])) : ''; ?>" required />
		</div>
	</div>

	<div class="form-group">
		<label for="description" class="col-sm-4 control-label"><?php _e('Description','cftp_admin'); ?></label>
		<div class="col-sm-8">
			<textarea name="description" id="description" class="ckeditor form-control required" required><?php echo (isset($workspace_arguments['description'])) ? html_output($workspace_arguments['description']) : ''; ?></textarea>
		</div>
	</div>

	<div class="form-group assigns">
		<label for="members" class="col-sm-4 control-label"><?php _e('Members','cftp_admin'); ?></label>
		<div class="col-sm-8">
			<select multiple="multiple" id="members" class="form-control chosen-select" name="users[]" data-placeholder="<?php _e('Select one or more options. Type to search.', 'cftp_admin');?>">
				<?php
					$sql = $dbh->prepare("SELECT * FROM " . TABLE_USERS . " WHERE level IN ('9', '8') ORDER BY name ASC");
					$sql->execute();
					$sql->setFetchMode(PDO::FETCH_ASSOC);
					while ( $row = $sql->fetch() ) {
				?>
						<option value="<?php echo $row["id"]; ?>"
							<?php
								if ($workspaces_form_type == 'edit_workspace') {
                                    if (!empty($workspace_arguments['users'])) {
									    if (in_array($row["id"], $workspace_arguments['users'])) {
										    echo ' selected="selected"';
                                        }
                                    }
								}
							?>
						><?php echo html_output($row["name"]); ?></option>
				<?php
					}
				?>
			</select>
			<div class="list_mass_members">
				<a href="#" class="btn btn-default add-all" data-type="assigns"><?php _e('Add all','cftp_admin'); ?></a>
				<a href="#" class="btn btn-default remove-all" data-type="assigns"><?php _e('Remove all','cftp_admin'); ?></a>
			</div>
		</div>
	</div>

	<div class="inside_form_buttons">
		<button type="submit" class="btn btn-wide btn-primary"><?php echo html_output($submit_value); ?></button>
	</div>
</form>
