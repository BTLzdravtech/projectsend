<h3><?php _e('Allowed file extensions','cftp_admin'); ?></h3>
<p><?php _e('Be careful when changing this options. They could affect not only the system but the whole server it is installed on.','cftp_admin'); ?><br />
<strong><?php _e('Important','cftp_admin'); ?></strong>: <?php _e('Separate allowed file types with a comma.','cftp_admin'); ?></p>

<div class="form-group">
    <label for="file_types_limit_to" class="col-sm-4 control-label"><?php _e('Limit file types uploading to','cftp_admin'); ?></label>
    <div class="col-sm-8">
        <select class="form-control" name="file_types_limit_to" id="file_types_limit_to" required>
            <option value="noone" <?php echo (FILE_TYPES_LIMIT_TO == 'noone') ? 'selected="selected"' : ''; ?>><?php _e('No one','cftp_admin'); ?></option>
            <option value="all" <?php echo (FILE_TYPES_LIMIT_TO == 'all') ? 'selected="selected"' : ''; ?>><?php _e('Everyone','cftp_admin'); ?></option>
            <option value="clients" <?php echo (FILE_TYPES_LIMIT_TO == 'clients') ? 'selected="selected"' : ''; ?>><?php _e('Clients only','cftp_admin'); ?></option>
        </select>
    </div>
</div>

<div class="form-group">
    <input name="allowed_file_types" id="allowed_file_types" value="<?php echo $allowed_file_types; ?>" required />
</div>

<?php
    if ( isset( $php_allowed_warning ) && $php_allowed_warning == true ) {
        $msg = __('Warning: php extension is allowed. This is a serious security problem. If you are not sure that you need it, please remove it from the list.','cftp_admin');
        echo system_message('danger',$msg);
    }
?>

<div class="options_divide"></div>

<h3><?php _e('Passwords','cftp_admin'); ?></h3>
<p><?php _e('When setting up a password for an account, require at least:','cftp_admin'); ?></p>

<div class="form-group">
    <div class="col-sm-8 col-sm-offset-4">
        <label for="pass_require_upper">
            <input type="checkbox" value="1" name="pass_require_upper" id="pass_require_upper" class="checkbox_options" <?php echo (PASS_REQUIRE_UPPER == 1) ? 'checked="checked"' : ''; ?> /> <?php echo $json_strings['validation']['req_upper']; ?>
        </label>
    </div>
</div>

<div class="form-group">
    <div class="col-sm-8 col-sm-offset-4">
        <label for="pass_require_lower">
            <input type="checkbox" value="1" name="pass_require_lower" id="pass_require_lower" class="checkbox_options" <?php echo (PASS_REQUIRE_LOWER == 1) ? 'checked="checked"' : ''; ?> /> <?php echo $json_strings['validation']['req_lower']; ?>
        </label>
    </div>
</div>

<div class="form-group">
    <div class="col-sm-8 col-sm-offset-4">
        <label for="pass_require_number">
            <input type="checkbox" value="1" name="pass_require_number" id="pass_require_number" class="checkbox_options" <?php echo (PASS_REQUIRE_NUMBER == 1) ? 'checked="checked"' : ''; ?> /> <?php echo $json_strings['validation']['req_number']; ?>
        </label>
    </div>
</div>

<div class="form-group">
    <div class="col-sm-8 col-sm-offset-4">
        <label for="pass_require_special">
            <input type="checkbox" value="1" name="pass_require_special" id="pass_require_special" class="checkbox_options" <?php echo (PASS_REQUIRE_SPECIAL == 1) ? 'checked="checked"' : ''; ?> /> <?php echo $json_strings['validation']['req_special']; ?>
        </label>
    </div>
</div>

<div class="options_divide"></div>

<h3><?php _e('reCAPTCHA','cftp_admin'); ?></h3>
<p><?php _e('Helps prevent SPAM on your registration form.','cftp_admin'); ?></p>

<div class="form-group">
    <div class="col-sm-8 col-sm-offset-4">
        <label for="recaptcha_enabled">
            <input type="checkbox" value="1" name="recaptcha_enabled" id="recaptcha_enabled" class="checkbox_options" <?php echo (RECAPTCHA_ENABLED == 1) ? 'checked="checked"' : ''; ?> /> <?php _e('Use reCAPTCHA','cftp_admin'); ?>
        </label>
    </div>
</div>

<div class="form-group">
    <label for="recaptcha_site_key" class="col-sm-4 control-label"><?php _e('Site key','cftp_admin'); ?></label>
    <div class="col-sm-8">
        <input type="text" name="recaptcha_site_key" id="recaptcha_site_key" class="form-control" value="<?php echo html_output(RECAPTCHA_SITE_KEY); ?>" />
    </div>
</div>

<div class="form-group">
    <label for="recaptcha_secret_key" class="col-sm-4 control-label"><?php _e('Secret key','cftp_admin'); ?></label>
    <div class="col-sm-8">
        <input type="text" name="recaptcha_secret_key" id="recaptcha_secret_key" class="form-control" value="<?php echo html_output(RECAPTCHA_SECRET_KEY); ?>" />
    </div>
</div>

<div class="form-group">
    <div class="col-sm-8 col-sm-offset-4">
        <a href="<?php echo LINK_DOC_RECAPTCHA; ?>" class="external_link" target="_blank"><?php _e('How do I obtain this credentials?','cftp_admin'); ?></a>
    </div>
</div>
<div class="options_divide"></div>

<h3><?php _e('Account Lockout','cftp_admin'); ?></h3>
<p><?php _e('Configure account lockout of user and client accounts','cftp_admin'); ?></p>
<div class="form-group">
    <label for="user_observation_window" class="col-sm-4 control-label"><?php _e('User observation window (minutes)','cftp_admin'); ?></label>
    <div class="col-sm-8">
        <input type="text" name="user_observation_window" id="user_observation_window" class="form-control" value="<?php echo USER_OBSERVATION_WINDOW; ?>" />
        <p class="field_note"><?php _e('Define the period of time invalid logins will be observed over in minutes','cftp_admin'); ?></p>
    </div>
</div>

<div class="form-group">
    <label for="user_max_invalid_auth_attempts" class="col-sm-4 control-label"><?php _e('User maximum invalid login count','cftp_admin'); ?></label>
    <div class="col-sm-8">
        <input type="text" name="user_max_invalid_auth_attempts" id="user_max_invalid_auth_attempts" class="form-control" value="<?php echo USER_MAX_INVALID_AUTH_ATTEMPTS; ?>" />
        <p class="field_note"><?php _e('Once an account reached this number it will be disabled. Set to 0 to disable user account lockout.','cftp_admin'); ?></p>
    </div>
</div>

<div class="form-group">
    <label for="client_observation_window" class="col-sm-4 control-label"><?php _e('Client observation window (minutes)','cftp_admin'); ?></label>
    <div class="col-sm-8">
        <input type="text" name="client_observation_window" id="client_observation_window" class="form-control" value="<?php echo CLIENT_OBSERVATION_WINDOW; ?>" />
        <p class="field_note"><?php _e('Define the period of time invalid logins will be observed over in minutes','cftp_admin'); ?></p>
    </div>
</div>

<div class="form-group">
    <label for="client_max_invalid_auth_attempts" class="col-sm-4 control-label"><?php _e('Client maximum invalid login count','cftp_admin'); ?></label>
    <div class="col-sm-8">
        <input type="text" name="client_max_invalid_auth_attempts" id="client_max_invalid_auth_attempts" class="form-control" value="<?php echo CLIENT_MAX_INVALID_AUTH_ATTEMPTS; ?>" />
        <p class="field_note"><?php _e('Once an account reached this number it will be disabled. Set to 0 to disable client account lockout.','cftp_admin'); ?></p>
    </div>
</div>

<div class="options_divide"></div>

<h3><?php _e('Logging','cftp_admin'); ?></h3>
<p><?php _e('Options for logging of security events','cftp_admin'); ?></p>

<div class="form-group">
    <div class="col-sm-8 col-sm-offset-4">
        <label for="log_failed_auth">
            <input type="checkbox" value="1" name="log_failed_auth" id="log_failed_auth" class="checkbox_options" <?php echo (LOG_FAILED_AUTH == 1) ? 'checked="checked"' : ''; ?> /> <?php _e('Log failed authentication attempts','cftp_admin'); ?>
        </label>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function()
        {
            $('#user_observation_window').spinedit({
                minimum: <?php echo MIN_OBSERVED_WINDOW; ?>,
                maximum: <?php echo MAX_OBSERVED_WINDOW; ?>,
                step: 1,
                value: <?php echo USER_OBSERVATION_WINDOW; ?>,
                numberOfDecimals: 0
            });
            $('#user_max_invalid_auth_attempts').spinedit({
                minimum: <?php echo MIN_INVALID_AUTH_ATTEMPTS; ?>,
                maximum: <?php echo MAX_INVALID_AUTH_ATTEMPTS; ?>,
                step: 1,
                value: <?php echo USER_MAX_INVALID_AUTH_ATTEMPTS; ?>,
                numberOfDecimals: 0
            });
            $('#client_observation_window').spinedit({
                minimum: <?php echo MIN_OBSERVED_WINDOW; ?>,
                maximum: <?php echo MAX_OBSERVED_WINDOW; ?>,
                step: 1,
                value: <?php echo CLIENT_OBSERVATION_WINDOW; ?>,
                numberOfDecimals: 0
            });
            $('#client_max_invalid_auth_attempts').spinedit({
                minimum: <?php echo MIN_INVALID_AUTH_ATTEMPTS; ?>,
                maximum: <?php echo MAX_INVALID_AUTH_ATTEMPTS; ?>,
                step: 1,
                value: <?php echo CLIENT_MAX_INVALID_AUTH_ATTEMPTS; ?>,
                numberOfDecimals: 0
            });
        }
    );
</script>
