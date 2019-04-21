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

/** Security */
require_once ROOT_DIR . '/includes/security/xsrf.php';

/** Basic system constants */
require_once ROOT_DIR.'/includes/app.php';

/** Flash messages */
require_once ROOT_DIR . '/includes/flash.php';

/** Load the database class */
require_once ROOT_DIR.'/includes/database.php';

/** Load the site options */
if ( !defined( 'IS_MAKE_CONFIG' ) ) {
	require_once ROOT_DIR.'/includes/site.options.php';
}

/** Load the language class and translation file */
require_once ROOT_DIR.'/includes/language.php';

/** Text strings used on various files */
require_once ROOT_DIR.'/includes/text.strings.php';

/** Basic functions to be accessed from anywhere */
require_once ROOT_DIR.'/includes/functions.php';

/** Require the updates functions */
require_once ROOT_DIR.'/includes/updates.functions.php';

/** Contains the session and cookies validation functions */
require_once ROOT_DIR.'/includes/userlevel_check.php';

/** Template list functions */
require_once ROOT_DIR.'/includes/functions.templates.php';

/** Contains the current session information */
if ( !defined( 'IS_INSTALL' ) ) {
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

/**
 * Google Login
 */
require_once ROOT_DIR . '/includes/Google/Oauth2/service/Google_ServiceResource.php';
require_once ROOT_DIR . '/includes/Google/Oauth2/service/Google_Service.php';
require_once ROOT_DIR . '/includes/Google/Oauth2/service/Google_Model.php';
require_once ROOT_DIR . '/includes/Google/Oauth2/contrib/Google_Oauth2Service.php';
require_once ROOT_DIR . '/includes/Google/Oauth2/Google_Client.php';
