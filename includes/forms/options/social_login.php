<h3><?php _e('Google','cftp_admin'); ?></h3>

<div class="options_column">
    <div class="form-group">
        <label for="google_signin_enabled" class="col-sm-4 control-label"><?php _e('Enabled','cftp_admin'); ?></label>
        <div class="col-sm-8">
            <select name="google_signin_enabled" id="google_signin_enabled" class="form-control">
                <option value="1" <?php echo (GOOGLE_SIGNIN_ENABLED == '1') ? 'selected="selected"' : ''; ?>><?php _e('Yes','cftp_admin'); ?></option>
                <option value="0" <?php echo (GOOGLE_SIGNIN_ENABLED == '0') ? 'selected="selected"' : ''; ?>><?php _e('No','cftp_admin'); ?></option>
            </select>
        </div>
    </div>
    <div class="form-group">
        <label for="google_client_id" class="col-sm-4 control-label"><?php _e('Client ID','cftp_admin'); ?></label>
        <div class="col-sm-8">
            <input type="text" name="google_client_id" id="google_client_id" class="form-control empty" value="<?php echo html_output(GOOGLE_CLIENT_ID); ?>" />
        </div>
    </div>
    <div class="form-group">
        <label for="google_client_secret" class="col-sm-4 control-label"><?php _e('Client Secret','cftp_admin'); ?></label>
        <div class="col-sm-8">
            <input type="text" name="google_client_secret" id="google_client_secret" class="form-control empty" value="<?php echo html_output(GOOGLE_CLIENT_SECRET); ?>" />
        </div>
    </div>
    <div class="form-group">
        <div class="col-sm-8 col-sm-offset-4">
            <a href="<?php echo LINK_DOC_GOOGLE_SIGN_IN; ?>" class="external_link" target="_blank"><?php _e('How do I obtain this credentials?','cftp_admin'); ?></a>
        </div>
    </div>
    <div class="form-group">
        <div class="col-sm-4">
            <?php _e('Callback URI','cftp_admin'); ?>
        </div>
        <div class="col-sm-8">
            <span class="format_url"><?php echo BASE_URI . 'sociallogin/google/callback.php'; ?></span>
        </div>
    </div>
</div>
