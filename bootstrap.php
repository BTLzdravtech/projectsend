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

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

/** Basic system constants */
require_once ROOT_DIR . '/includes/app.php';

/** Flash messages */
require_once ROOT_DIR . '/includes/flash.php';

/** Load the database class */
require_once ROOT_DIR . '/includes/database.php';

/** Load the site options */
if (!defined('IS_MAKE_CONFIG')) {
    require_once ROOT_DIR . '/includes/site.options.php';
}

if (defined('IS_MAKE_CONFIG') || defined('IS_INSTALL')) {
    require_once ROOT_DIR . '/includes/install.constants.php';
}

/** Load the language class and translation file */
require_once ROOT_DIR . '/includes/language.php';

/** Text strings used on various files */
require_once ROOT_DIR . '/includes/text.strings.php';

/** Basic functions to be accessed from anywhere */
require_once ROOT_DIR . '/includes/functions.php';

/** Options functions */
require_once ROOT_DIR . '/includes/functions.options.php';

/** Require the updates functions */
require_once ROOT_DIR . '/includes/updates.functions.php';

/** Contains the session and cookies validation functions */
require_once ROOT_DIR . '/includes/userlevel_check.php';

/** Template list functions */
require_once ROOT_DIR . '/includes/functions.templates.php';

/** Contains the current session information */
if (!defined('IS_INSTALL')) {
    require_once ROOT_DIR . '/includes/active.session.php';
}

/** Recreate the function if it doesn't exist. By Alan Reiblein */
require_once ROOT_DIR . '/includes/timezone_identifiers_list.php';

/** Categories functions */
require_once ROOT_DIR . '/includes/functions.categories.php';

/** Search, filters and actions forms */
require_once ROOT_DIR . '/includes/functions.forms.php';

/** Search, filters and actions forms */
require_once ROOT_DIR . '/includes/functions.groups.php';

/** Search, filters and actions forms */
require_once ROOT_DIR . '/includes/functions.workspaces.php';

/** Security */
require_once ROOT_DIR . '/includes/security/csrf.php';
require_once ROOT_DIR . '/includes/security/brute_force_block.php';

if (!is_null($_ENV['SENTRY_DSN'])) {
    if (!is_null($_SERVER['SENTRY_RELEASE']) && !empty($_SERVER['SENTRY_RELEASE'])) {
        $revision = trim($_SERVER['SENTRY_RELEASE']);
    } elseif (file_exists($revisionFile = __DIR__ . '/.git')) {
        exec("git rev-parse HEAD 2>&1", $output, $exit_code);
        if (!is_null($output) && !empty($output[0]) && $exit_code == 0) {
            $revision = trim($output[0]);
        }
    } elseif (file_exists($revisionFile = __DIR__ . '/REVISION')) {
        $revision = trim(file_get_contents($revisionFile));
    }

    Sentry\init([
        'dsn' => $_ENV['SENTRY_DSN'],
        'release' => $revision ?? null,
        'environment' => 'production',
        'send_default_pii' => true,
        'error_types' => E_ALL,
        'before_send' => function (Sentry\Event $event): ?Sentry\Event {
            if (!is_null(CURRENT_USER_ID) && !is_null(CURRENT_USER_EMAIL)) {
                $event->getUser()->setId(CURRENT_USER_ID);
                $event->getUser()->setEmail(CURRENT_USER_EMAIL);
            }
            return $event;
        }
    ]);
}
