<?php
/**
 * Contains the form that is used when adding or editing users.
 *
 * @package    ProjectSend
 * @subpackage Users
 */

global $user_form_type;
global $user_id;

switch ($user_form_type) {
    case 'new_user':
        $submit_value = __('Add user', 'cftp_admin');
        $disable_user = false;
        $require_pass = true;
        $form_action = 'users-add.php';
        $extra_fields = true;
        break;
    case 'edit_user':
        $submit_value = __('Save user', 'cftp_admin');
        $disable_user = true;
        $require_pass = false;
        $form_action = 'users-edit.php?id=' . $user_id;
        $extra_fields = true;
        break;
    case 'edit_user_self':
        $submit_value = __('Update account', 'cftp_admin');
        $disable_user = true;
        $require_pass = false;
        $form_action = 'users-edit.php?id=' . $user_id;
        $extra_fields = false;
        break;
}

$is_ldap_user = isset($user_arguments['objectguid']) && $user_arguments['objectguid'] != "";
$is_google_user = isset($user_arguments['google_user']) && $user_arguments['google_user'] == 1;
?>
<form action="<?php echo html_output($form_action); ?>" name="user_form" id="user_form" method="post"
      class="form-horizontal" data-form-type="<?php echo $user_form_type; ?>">
    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>"/>

    <div class="form-group">
        <label for="name" class="col-sm-4 control-label"><?php _e('Name', 'cftp_admin'); ?></label>
        <div class="col-sm-8">
            <input type="text" name="name" id="name" class="form-control required"
                   value="<?php echo (isset($user_arguments['name'])) ? format_form_value($user_arguments['name']) : ''; ?>"
                   required<?php echo $is_ldap_user ? ' readonly' : ''; ?> />
        </div>
    </div>

    <div class="form-group">
        <label for="username" class="col-sm-4 control-label"><?php _e('Log in username', 'cftp_admin'); ?></label>
        <div class="col-sm-8">
            <input type="text" name="username" id="username"
                   class="form-control <?php echo !$disable_user ? 'required' : ''; ?>"
                   maxlength="<?php echo MAX_USER_CHARS; ?>"
                   value="<?php echo (isset($user_arguments['username'])) ? format_form_value($user_arguments['username']) : ''; ?>" <?php echo $disable_user ? 'readonly' : ''; ?>
                   placeholder="<?php _e("Must be alphanumeric", 'cftp_admin'); ?>" required/>
        </div>
    </div>

    <?php if (!$is_ldap_user && !$is_google_user) { ?>
        <div class="form-group">
            <label for="password" class="col-sm-4 control-label"><?php _e('Password', 'cftp_admin'); ?></label>
            <div class="col-sm-8">
                <div class="input-group">
                    <input type="password" name="password" id="password"
                           class="form-control <?php echo $require_pass ? 'required' : ''; ?> password_toggle"
                           maxlength="<?php echo MAX_PASS_CHARS; ?>" <?php echo $is_ldap_user ? ' readonly' : ''; ?> />
                    <div class="input-group-btn password_toggler">
                        <button type="button" class="btn pass_toggler_show"><i class="glyphicon glyphicon-eye-open"></i>
                        </button>
                    </div>
                </div>
                <?php if (!$is_ldap_user && !$is_google_user) { ?>
                    <button type="button" name="generate_password" id="generate_password"
                            class="btn btn-default btn-sm btn_generate_password" data-ref="password"
                            data-min="<?php echo MAX_GENERATE_PASS_CHARS; ?>"
                            data-max="<?php echo MAX_GENERATE_PASS_CHARS; ?>"><?php _e('Generate', 'cftp_admin'); ?></button>
                <?php } ?>
                <?php echo password_notes(); ?>
            </div>
        </div>
    <?php } ?>

    <div class="form-group">
        <label for="email" class="col-sm-4 control-label"><?php _e('E-mail', 'cftp_admin'); ?></label>
        <div class="col-sm-8">
            <input type="text" name="email" id="email" class="form-control required"
                   value="<?php echo (isset($user_arguments['email'])) ? format_form_value($user_arguments['email']) : ''; ?>"
                   placeholder="<?php _e("Must be valid and unique", 'cftp_admin'); ?>"
                   required <?php echo $is_ldap_user ? ' readonly' : ''; ?> />
        </div>
    </div>

    <?php
    if ($extra_fields == true) {
        ?>
        <?php if (!$is_ldap_user && !$is_google_user) { ?>
            <div class="form-group">
                <label for="level" class="col-sm-4 control-label"><?php _e('Role', 'cftp_admin'); ?></label>
                <div class="col-sm-8">
                    <select name="level" id="level" class="form-control" required>
                        <?php
                        $roles = [
                            '9' => USER_ROLE_LVL_9,
                            '8' => USER_ROLE_LVL_8,
                            '7' => USER_ROLE_LVL_7,
                        ];
                        foreach ($roles as $role_level => $role_name) {
                            ?>
                            <option value="<?php echo $role_level; ?>" <?php echo (isset($user_arguments['role']) && $user_arguments['role'] == $role_level) ? 'selected="selected"' : ''; ?>><?php echo $role_name; ?></option>
                            <?php
                        }
                        ?>
                    </select>
                </div>
            </div>
        <?php } ?>

        <div class="form-group">
            <label for="max_file_size"
                   class="col-sm-4 control-label"><?php _e('Max. upload filesize', 'cftp_admin'); ?></label>
            <div class="col-sm-8">
                <div class="input-group">
                    <input type="text" name="max_file_size" id="max_file_size" class="form-control"
                           value="<?php echo (isset($user_arguments['max_file_size'])) ? format_form_value($user_arguments['max_file_size']) : '0'; ?>"/>
                    <span class="input-group-addon">mb</span>
                </div>
                <p class="field_note"><?php _e("Set to 0 to use the default system limit", 'cftp_admin'); ?>
                    (<?php echo MAX_FILESIZE; ?> mb)</p>
            </div>
        </div>

        <div class="form-group">
            <div class="col-sm-8 col-sm-offset-4">
                <label for="active">
                    <input type="checkbox" name="active"
                           id="active" <?php echo (isset($user_arguments['active']) && $user_arguments['active'] == 1) ? 'checked="checked"' : ''; ?> /> <?php _e('Active (user can log in)', 'cftp_admin'); ?>
                </label>
            </div>
        </div>
        <div class="form-group">
            <div class="col-sm-8 col-sm-offset-4">
                <label for="notify">
                    <input type="checkbox" name="notify_upload"
                           id="notify_upload" <?php echo (isset($user_arguments['notify_upload']) && $user_arguments['notify_upload'] == 1) ? 'checked="checked"' : ''; ?>> <?php _e('Notify new uploads by e-mail', 'cftp_admin'); ?>
                </label>
            </div>
        </div>

        <?php
        if ($user_form_type == 'new_user') {
            ?>
            <div class="form-group">
                <div class="col-sm-8 col-sm-offset-4">
                    <label for="notify_account">
                        <input type="checkbox" name="notify_account"
                               id="notify_account" <?php echo (isset($user_arguments['notify_account']) && $user_arguments['notify_account'] == 1) ? 'checked="checked"' : ''; ?> /> <?php _e('Send welcome email', 'cftp_admin'); ?>
                    </label>
                </div>
            </div>
            <?php
        }
    }
    ?>
    <div class="inside_form_buttons">
        <button type="submit" class="btn btn-wide btn-primary"><?php echo $submit_value; ?></button>
    </div>

    <?php
    if ($user_form_type == 'new_user') {
        $msg = __('This account information will be e-mailed to the address supplied above', 'cftp_admin');
        echo system_message('info', $msg);
    }
    ?>
</form>