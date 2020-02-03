<?php
/**
 * Requirements of basic system files.
 *
 * @package ProjectSend
 * @subpackage Core
 */
define('ROOT_DIR', dirname(__FILE__));
define('DS', DIRECTORY_SEPARATOR);

/** Composer autoload */
require_once ROOT_DIR . '/vendor/autoload.php';

/** Basic system constants */
require_once ROOT_DIR.'/includes/app.php';

/** Flash messages */
require_once ROOT_DIR . '/includes/flash.php';

/** Load the database class */
require_once ROOT_DIR.'/includes/database.php';

/** Load the site options */
if (!defined('IS_MAKE_CONFIG')) {
    require_once ROOT_DIR.'/includes/site.options.php';
}

if (defined('IS_MAKE_CONFIG') || defined('IS_INSTALL')) {
    require_once ROOT_DIR.'/includes/install.constants.php';
}

/** Load the language class and translation file */
require_once ROOT_DIR.'/includes/language.php';

/** Text strings used on various files */
require_once ROOT_DIR.'/includes/text.strings.php';

/** Basic functions to be accessed from anywhere */
require_once ROOT_DIR.'/includes/functions.php';

/** Options functions */
require_once ROOT_DIR.'/includes/functions.options.php';

/** Require the updates functions */
require_once ROOT_DIR.'/includes/updates.functions.php';

/** Contains the session and cookies validation functions */
require_once ROOT_DIR.'/includes/userlevel_check.php';

/** Template list functions */
require_once ROOT_DIR.'/includes/functions.templates.php';

/** Contains the current session information */
if (!defined('IS_INSTALL')) {
    require_once ROOT_DIR.'/includes/active.session.php';
}

/** Recreate the function if it doesn't exist. By Alan Reiblein */
require_once ROOT_DIR.'/includes/timezone_identifiers_list.php';

/** Categories functions */
require_once ROOT_DIR.'/includes/functions.categories.php';

/** Search, filters and actions forms */
require_once ROOT_DIR.'/includes/functions.forms.php';

/** Search, filters and actions forms */
require_once ROOT_DIR.'/includes/functions.groups.php';

/** Search, filters and actions forms */
require_once ROOT_DIR.'/includes/functions.workspaces.php';

/** Security */
require_once ROOT_DIR . '/includes/security/csrf.php';
require_once ROOT_DIR . '/includes/security/brute_force_block.php';

if ($_SERVER['HTTP_HOST'] != 'localhost') {
    /** Airbrake - Errbit */
    $notifier = new Airbrake\Notifier([
        'projectId' => 1,
        'projectKey' => 'c5219993229b4611584ff66a14a80fa4',
        'host' => 'https://errbit.medictech.com',
        'environment' => 'production',
        'keysBlacklist' => ['/password/i', '/user_pass/i', '/item[current_password]/i', '/item[password1]/i', '/item[password2]/i']
    ]);

    // Set global notifier instance.
    Airbrake\Instance::set($notifier);

    // Register error and exception handlers.
    $handler = new Airbrake\ErrorHandler($notifier);
    $handler->register();
}
