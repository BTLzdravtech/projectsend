<?php
/**
 * Contains the form that is used on the login page
 *
 * @package    ProjectSend
 * @subpackage Files
 */

global $auth_url;

?>
<form action="process.php?do=login" name="login_admin" role="form" id="login_form" method="post">
    <input type="hidden" name="do" value="login">
    <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>"/>
    <fieldset>
        <div class="form-group">
            <label for="username"><?php _e('Username', 'cftp_admin'); ?></label>
            <input type="text" name="username" id="username"
                   value="<?php echo isset($sysuser_username) ? htmlspecialchars($sysuser_username) : ''; ?>"
                   class="form-control" autofocus required/>
        </div>

        <div class="form-group">
            <label for="password"><?php _e('Password', 'cftp_admin'); ?></label>
            <input type="password" name="password" id="password" class="form-control" required/>
        </div>

        <div class="form-group">
            <label for="language"><?php _e('Language', 'cftp_admin'); ?></label>
            <select name="language" id="language" class="form-control">
                <?php
                // scan for language files
                $available_langs = get_available_languages();
                foreach ($available_langs as $filename => $lang_name) {
                    ?>
                    <option value="<?php echo $filename; ?>" <?php echo (LOADED_LANG == $filename) ? 'selected' : ''; ?>>
                        <?php echo $lang_name . ($filename == SITE_LANG ? ' [' . __('default', 'cftp_admin') . ']' : '') ?>
                    </option>
                    <?php
                }
                ?>
            </select>
        </div>

        <div class="inside_form_buttons">
            <button type="submit" id="submit" class="btn btn-wide btn-primary"
                    data-text="<?php echo $json_strings['login']['button_text']; ?>"
                    data-loading-text="<?php echo $json_strings['login']['logging_in']; ?>"><?php echo $json_strings['login']['button_text']; ?></button>
        </div>

        <div class="google-login">
            <?php /** @noinspection PhpUndefinedConstantInspection */
            if (GOOGLE_SIGNIN_ENABLED == '1') : ?>
                <a href="<?php echo $auth_url; ?>"
                   class="btn btn-wide btn-secondary"><?php echo __('Login as BTL Employee', 'cftp_admin') ?></a>
            <?php endif; ?>
        </div>
    </fieldset>
</form>
