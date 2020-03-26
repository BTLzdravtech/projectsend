<?php
/**
 * This file generates the header for pages shown to unlogged users and
 * clients (log in form and, if allowed, self registration form).
 *
 * @package ProjectSend
 */

/**
 * This file is shared with the installer. Let's start by checking
 * where is it being called from.
 */
if (defined('IS_INSTALL')) {
    $lang = (defined('SITE_LANG')) ? SITE_LANG : 'en';

    $header_vars = array(
        'html_lang' => $lang,
        'title' => $page_title_install . ' &raquo; ' . SYSTEM_NAME,
        'header_title' => SYSTEM_NAME . ' ' . __('setup', 'cftp_admin'),
    );
} else {
    /**
     * Check if the ProjectSend is installed. Done only on the log in form
     * page since all other are inaccessible if no valid session or cookie
     * is set.
     */
    /** @noinspection PhpUndefinedConstantInspection */
    $header_vars = array(
        'html_lang' => SITE_LANG,
        'title' => $page_title . ' &raquo; ' . html_output(THIS_INSTALL_TITLE),
        'header_title' => html_output(THIS_INSTALL_TITLE),
    );

    if (!is_projectsend_installed()) {
        header("Location:install/index.php");
        exit;
    }

    /**
     * This is defined on the public download page.
     * So even logged in users can access it.
     */
    if (!isset($dont_redirect_if_logged)) {
        /**
         * If logged as a system user, go directly to the back-end homepage
         */
        if (current_role_in($allowed_levels)) {
            header("Location:" . BASE_URI . "dashboard.php");
        }

        /**
         * If client is logged in, redirect to the files list.
         */
        check_for_client();
    }
    /**
     * Silent updates that are needed even if no user is logged in.
     */
    include_once INCLUDES_DIR . DS . 'core.update.silent.php';
}

if (!isset($body_class)) {
    $body_class = '';
}
?>
<!doctype html>
<html lang="<?php echo $header_vars['html_lang']; ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php meta_noindex(); ?>

    <title><?php echo html_output($header_vars['title']); ?></title>
    <?php meta_favicon(); ?>

    <?php
    $load_theme_css = true;
    require_once INCLUDES_DIR . DS . 'assets.php';

    load_js_header_files();
    load_css_files();
    ?>
    <script type="text/javascript" src='https://cdn.jsdelivr.net/npm/airbrake-js@1.6.8/dist/client.min.js'></script>
    <script type="text/javascript">
        if (location.hostname !== 'localhost') {
            var airbrake = new airbrakeJs.Client({
                projectId: 1,
                projectKey: 'c5219993229b4611584ff66a14a80fa4',
                reporter: 'xhr',
                host: 'https://errbit.medictech.com'
            });
            airbrake.addFilter(function (notice) {
                notice.context.environment = 'production';
                return notice;
            });
        }
    </script>
</head>

<body <?php echo add_body_class($body_class); ?> <?php echo !empty($page_id) ? add_page_id($page_id) : ''; ?>>
<div class="container-custom">
    <header id="header" class="navbar navbar-static-top navbar-fixed-top header_unlogged">
        <div class="navbar-header text-center">
                <span class="navbar-brand">
                    <?php echo $header_vars['header_title']; ?>
                </span>
        </div>
    </header>

    <div class="main_content_unlogged">
        <div class="container-fluid">
            <div class="row">