<?php
/**
 * This file generates the header for the back-end and also for the default
 * template.
 *
 * Other checks for user level are performed later to generate the different
 * menu items, and the content of the page that called this file.
 *
 * @package ProjectSend
 * @see     check_for_session
 * @see     check_for_admin
 * @see     can_see_content
 */
/**
 * Check for an active session or cookie
 */
check_for_session();

/**
 * Check if the current user has permission to view this page.
 * If not, an error message is generated instead of the actual content.
 * The allowed levels are defined on each individual page before the
 * inclusion of this file.
 */
can_see_content($allowed_levels);

/**
 * Check if the active account belongs to a system user or a client.
 */
//check_for_admin();

/**
 * If no page title is defined, revert to a default one
 */
if (!isset($page_title)) {
    $page_title = __('System Administration', 'cftp_admin');
}

if (!isset($body_class)) {
    $body_class = array();
}

if (!empty($_COOKIE['menu_contracted']) && $_COOKIE['menu_contracted'] == 'true') {
    $body_class[] = 'menu_contracted';
}

$body_class[] = 'menu_hidden';

/**
 * Silent updates that are needed even if no user is logged in.
 */
require_once INCLUDES_DIR . DS . 'core.update.silent.php';

/**
 * Call the database update file to see if any change is needed,
 * but only if logged in as a system user.
 */
$core_update_allowed = array(9, 8, 7);
if (current_role_in($core_update_allowed)) {
    include_once INCLUDES_DIR . DS . 'core.update.php';
}
?>
<!doctype html>
<html lang="<?php echo SITE_LANG; ?>">
<head>
    <meta charset="<?php echo(CHARSET); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?php /** @noinspection PhpUndefinedConstantInspection */
        echo html_output($page_title . ' &raquo; ' . htmlspecialchars(THIS_INSTALL_TITLE, ENT_QUOTES, CHARSET)); ?></title>
    <?php meta_favicon(); ?>

    <?php
    require_once INCLUDES_DIR . DS . 'assets.php';

    load_js_header_files();
    load_css_files();
    ?>
    <?php
    if (!is_null($_ENV['SENTRY_JS_DSN'])) {
        ?>
        <script src="<?php echo $_ENV['SENTRY_JS_DSN'] ?>" crossorigin="anonymous" data-lazy="no"></script>
        <script type="text/javascript">
            Sentry.onLoad(function() {
                Sentry.init({
                    environment: "production",
                    release: "<?php echo Sentry\SentrySdk::getCurrentHub()->getClient()->getOptions()->getRelease() ?>",
                    ignoreErrors: ['ResizeObserver loop limit exceeded'],
                    beforeSend(event) {
                        if (event.user === undefined) {
                            event.user = <?php echo !is_null(CURRENT_USER_ID) && !is_null(CURRENT_USER_EMAIL) ? "{id:" . CURRENT_USER_ID . ", email: '" . CURRENT_USER_EMAIL . "'}" : "{}" ?>
                        }
                        return event;
                    }
                });
            });
        </script>
    <?php
    }
    ?>
</head>

<body <?php echo add_body_class($body_class); ?> <?php echo !empty($page_id) ? add_page_id($page_id) : ''; ?>>
<div class="container-custom">
    <header id="header" class="navbar navbar-static-top navbar-fixed-top">
        <ul class="nav pull-left nav_toggler">
            <li>
                <a href="#" class="toggle_main_menu"><i class="fa fa-bars"
                                                        aria-hidden="true"></i><span><?php _e('Toogle menu', 'cftp_admin'); ?></span></a>
            </li>
        </ul>

        <div class="navbar-header">
            <span class="navbar-brand"><a href="<?php echo SYSTEM_URI; ?>"
                                          target="_blank"><?php require_once 'assets/img/ps-icon.svg'; ?></a> <?php /** @noinspection PhpUndefinedConstantInspection */
                echo html_output(THIS_INSTALL_TITLE); ?></span>
        </div>

        <ul class="nav pull-right nav_account">
            <li id="header_welcome">
                <span><?php echo CURRENT_USER_NAME; ?></span>
            </li>
            <li>
                <?php
                $my_account_link = (CURRENT_USER_LEVEL == 0) ? 'clients-edit.php' : 'users-edit.php';
                $my_account_link .= '?id=' . CURRENT_USER_ID;
                ?>
                <a href="<?php echo BASE_URI . $my_account_link; ?>" class="my_account"><i class="fa fa-user-circle"
                                                                                           aria-hidden="true"></i> <?php _e('My Account', 'cftp_admin'); ?>
                </a>
            </li>
            <li>
                <a href="<?php echo BASE_URI; ?>process.php?do=logout"><i class="fa fa-sign-out"
                                                                          aria-hidden="true"></i> <?php _e('Logout', 'cftp_admin'); ?>
                </a>
            </li>
        </ul>
    </header>

    <div class="main_side_menu">
        <?php
        require_once 'header-menu.php';
        ?>
    </div>

    <div class="main_content">
        <div class="container-fluid">
            <?php
            // Gets the mark up and values for the System Updated and errors messages.
            require_once INCLUDES_DIR . DS . 'updates.messages.php';

            // Check if we are on a development version
            if (IS_DEV == true) {
                ?>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="system_msg">
                            <p>
                                <strong><?php _e('System Notice:', 'cftp_admin'); ?></strong> <?php _e('You are using a development version. Some features may be unfinished or not working correctly.', 'cftp_admin'); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>

            <div class="row">
                <div class="col-xs-12">
                    <div id="section_title">
                        <h2><?php echo $page_title; ?></h2>
                    </div>
                </div>
            </div>

            <div class="row">
