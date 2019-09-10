<h3><?php _e('Connection settings','cftp_admin'); ?></h3>

<div class="options_column">
    <div class="form-group">
        <label for="ldap_signin_enabled" class="col-sm-4 control-label"><?php _e('Enabled','cftp_admin'); ?></label>
        <div class="col-sm-8">
            <select name="ldap_signin_enabled" id="ldap_signin_enabled" class="form-control">
                <option value="1" <?php echo (LDAP_SIGNIN_ENABLED == '1') ? 'selected="selected"' : ''; ?>><?php _e('Yes','cftp_admin'); ?></option>
                <option value="0" <?php echo (LDAP_SIGNIN_ENABLED == '0') ? 'selected="selected"' : ''; ?>><?php _e('No','cftp_admin'); ?></option>
            </select>
        </div>
    </div>
    <div class="form-group">
        <label for="ldap_host" class="col-sm-4 control-label"><?php _e('Host','cftp_admin'); ?></label>
        <div class="col-sm-8">
            <input type="text" name="ldap_host" id="ldap_host" class="form-control<?php echo LDAP_SIGNIN_ENABLED ? ' required' : ''; ?>" value="<?php echo html_output(LDAP_HOST); ?>"<?php echo LDAP_SIGNIN_ENABLED ? ' required' : ''; ?> />
        </div>
    </div>
    <div class="form-group">
        <label for="ldap_port" class="col-sm-4 control-label"><?php _e('Port','cftp_admin'); ?></label>
        <div class="col-sm-8">
            <input type="text" name="ldap_port" id="ldap_port" class="form-control<?php echo LDAP_SIGNIN_ENABLED ? ' required' : ''; ?>" value="<?php echo html_output(LDAP_PORT); ?>"<?php echo LDAP_SIGNIN_ENABLED ? ' required' : ''; ?> />
        </div>
    </div>
    <div class="form-group">
        <label for="ldap_basedn" class="col-sm-4 control-label"><?php _e('Base DN','cftp_admin'); ?></label>
        <div class="col-sm-8">
            <input type="text" name="ldap_basedn" id="ldap_basedn" class="form-control<?php echo LDAP_SIGNIN_ENABLED ? ' required' : ''; ?>" value="<?php echo html_output(LDAP_BASEDN); ?>"<?php echo LDAP_SIGNIN_ENABLED ? ' required' : ''; ?> />
        </div>
    </div>
    <div class="form-group">
        <label for="ldap_domain" class="col-sm-4 control-label"><?php _e('Domain','cftp_admin'); ?></label>
        <div class="col-sm-8">
            <input type="text" name="ldap_domain" id="ldap_domain" class="form-control<?php echo LDAP_SIGNIN_ENABLED ? ' required' : ''; ?>" value="<?php echo html_output(LDAP_DOMAIN); ?>"<?php echo LDAP_SIGNIN_ENABLED ? ' required' : ''; ?> />
        </div>
    </div>
</div>
