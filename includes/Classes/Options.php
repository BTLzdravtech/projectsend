<?php
/**
 * Get custom system options from the database
 */

namespace ProjectSend\Classes;

use Exception;
use PDO;

class Options
{
    /**
     * @var PDO $dbh
     */
    private $dbh;

    public $options;

    public function __construct()
    {
        global $dbh;
        $this->dbh = $dbh;
    }

    /**
     * Gets the values from the options table, which has 2 columns.
     * The first one is the option name, and the second is the assigned value.
     *
     * @param $option
     * @return array|bool
     */
    public function getOption($option)
    {
        if (empty($option)) {
            return false;
        }

        try {
            $statement = $this->dbh->prepare("SELECT value FROM " . TABLE_OPTIONS . " WHERE name = :option");
            $statement->bindParam(':option', $option);
            $statement->execute();
            $results = $statement->fetch();

            $value = $results['value'];

            if ((!empty($value))) {
                return $value;
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Gets the values from the options table, which has 2 columns.
     * The first one is the option name, and the second is the assigned value.
     *
     * @return array|bool
     */
    private function getOptions()
    {
        $this->options = array();
        try {
            $query = $this->dbh->query("SELECT * FROM " . TABLE_OPTIONS);
            $query->setFetchMode(PDO::FETCH_ASSOC);

            if ($query->rowCount() > 0) {
                while ($row = $query->fetch()) {
                    $this->options[$row['name']] = $row['value'];
                }
            }

            return $this->options;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Makes the options available to the app
     */
    public function getAll()
    {
        $this->options = $this->getOptions();

        /**
         * In case an option should not be set as a const
        */
        $exceptions = [
        ];

        if (!empty($this->options)) {
            /**
             * Set a const for each value on the options table
             */
            foreach ($this->options as $name => $value) {
                if (in_array($name, $exceptions)) {
                    continue;
                }
                
                $const = strtoupper($name);
                define($const, $value);
            }

            /**
             * Set the default timezone based on the value of the Timezone select box
             * of the options page.
             */
            /** @noinspection PhpUndefinedConstantInspection */
            date_default_timezone_set(TIMEZONE);
            
            /**
             * Options that do not come from the db
            */
            /** @noinspection PhpUndefinedConstantInspection */
            define('TEMPLATE_PATH', ROOT_DIR.DS.'templates'.DS.SELECTED_CLIENTS_TEMPLATE.DS.'template.php');

            /* Recaptcha */
            /** @noinspection PhpUndefinedConstantInspection */
            if (RECAPTCHA_ENABLED == 1 && !empty(RECAPTCHA_SITE_KEY) && !empty(RECAPTCHA_SECRET_KEY)
            ) {
                define('RECAPTCHA_AVAILABLE', true);
            }

            /* Landing page for public groups and files */
            define('PUBLIC_DOWNLOAD_URL', BASE_URI.'download.php');
            define('PUBLIC_LANDING_URI', BASE_URI.'public.php');
            define('PUBLIC_GROUP_URL', BASE_URI.'public.php');

            /* URLs */
            define('THUMBNAILS_FILES_URL', BASE_URI.'upload/thumbnails');
            define('EMAIL_TEMPLATES_URL', BASE_URI.'emails/');
            define('TEMPLATES_URL', BASE_URI.'templates/');
        
            /* Widgets */
            define('WIDGETS_URL', BASE_URI.'includes/widgets/');
            
            /* Logo Uploads */
            define('ADMIN_UPLOADS_URI', BASE_URI . 'upload/admin/');
        
            /* Assets */
            define('ASSETS_URL', BASE_URI . 'assets');
            define('ASSETS_CSS_URL', ASSETS_URL . '/css');
            define('ASSETS_IMG_URL', ASSETS_URL . '/img');
            define('ASSETS_JS_URL', ASSETS_URL . '/js');
            define('ASSETS_LIB_URL', ASSETS_URL . '/lib');

            /**
             * Client's landing URI
            */
            define('CLIENT_VIEW_FILE_LIST_URL_PATH', 'my_files/');
            //define('CLIENT_VIEW_FILE_LIST_URL_PATH', 'private.php');
            define('CLIENT_VIEW_FILE_LIST_URL', BASE_URI . CLIENT_VIEW_FILE_LIST_URL_PATH);

            /* Set a page for each status code */
            define('STATUS_PAGES_DIR', ADMIN_VIEWS_DIR . DS . 'http_status_pages');
            define('PAGE_STATUS_CODE_URL', BASE_URI . 'error.php');
            define('PAGE_STATUS_CODE_403', PAGE_STATUS_CODE_URL . '?e=403');
            define('PAGE_STATUS_CODE_404', PAGE_STATUS_CODE_URL . '?e=404');

            /**
             * Oauth login callback
            */
            define('OAUTH_LOGIN_CALLBACK_URL', BASE_URI . 'login-callback.php');
            define('LOGIN_CALLBACK_URI_GOOGLE', OAUTH_LOGIN_CALLBACK_URL . '?service=google');
        } else {
            define('BASE_URI', '/');
        }
    }

    /**
     * Save to the database
     * @param $options
     */
    public function save($options)
    {
    }
}
