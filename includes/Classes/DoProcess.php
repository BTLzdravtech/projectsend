<?php
/**
 * Class that handles actions that do not return any UI.
 *
 * @todo replace! This functions should go into routes and more specific classes
 *
 * @package ProjectSend
 */

namespace ProjectSend\Classes;

use \PDO;
use \ZipArchive;

class DoProcess
{
    private $dbh;
    private $logger;

    private $username;
    private $password;
    private $language;

    private $auth;

    public function __construct(PDO $dbh = null, Auth $auth = null)
    {
        if (empty($dbh)) {
            global $dbh;
        }

        $this->dbh = $dbh;
        $this->logger = new ActionsLog;

        if (empty($auth)) {
            $this->auth = new Auth($this->dbh);
        }
    }

    public function login($username, $password, $language = SITE_LANG)
    {
        $this->username = $username;
        $this->password = $password;
        $this->language = $language;
        return $this->auth->login($username, $password, $language);
    }

    public function logout()
    {
        $this->auth->logout();
    }


    /**
     * @todo From here on, move everything into a Download class
     */

    /**
     * Download
     * @param $file_id
     * @return bool
     */
    public function download($file_id)
    {
        if (!$file_id) {
            return false;
        }

        /**
         * Do a permissions check for logged in user
         */
        $check_level = array(9, 8, 7, 0);
        if (isset($check_level) && current_role_in($check_level)) {

            /**
             * Get the file name
             */
            $statement = $this->dbh->prepare("SELECT url, original_url, expires, expiry_date FROM " . TABLE_FILES . " WHERE id=:id");
            $statement->bindParam(':id', $file_id, PDO::PARAM_INT);
            $statement->execute();
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            $row = $statement->fetch();
            $filename_find = $row['url'];
            $filename_save = (!empty($row['original_url'])) ? $row['original_url'] : $row['url'];
            $expires = $row['expires'];
            $expiry_date = $row['expiry_date'];

            $expired = false;
            if ($expires == '1' && time() > strtotime($expiry_date)) {
                $expired = true;
            }

            $can_download = false;

            if (CURRENT_USER_LEVEL == 0) {
                if ($expires == '0' || $expired == false) {
                    /**
                     * Does the client have permission to download the file?
                     * First, get the list of different groups the client belongs to.
                     *
                     * @todo move into a method for an yet to create File class, for example can_download_this_file($client_id)
                     */
                    $get_groups = new MembersActions();
                    $get_arguments = array(
                        'client_id' => CURRENT_USER_ID,
                        'return' => 'list',
                    );
                    $found_groups = $get_groups->client_get_groups($get_arguments);

                    /**
                     * Get assignments
                     */
                    $params = array(
                        ':client_id' => CURRENT_USER_ID,
                    );
                    $fq = "SELECT * FROM " . TABLE_FILES_RELATIONS . " WHERE (client_id=:client_id";
                    // Add found groups, if any
                    if (!empty($found_groups)) {
                        $fq .= ' OR FIND_IN_SET(group_id, :groups)';
                        $params[':groups'] = $found_groups;
                    }
                    // Continue assembling the query
                    $fq .= ') AND file_id=:file_id AND hidden = "0"';
                    $params[':file_id'] = (int)$file_id;

                    $files = $this->dbh->prepare($fq);
                    $files->execute($params);

                    /**
                     * Continue
                     */
                    if ($files->rowCount() > 0) {
                        $can_download = true;
                        $log_action = 8;
                    }
                }
            } elseif (CURRENT_USER_LEVEL < 9) {
                $user_id = CURRENT_USER_ID;

                $fq = 'SELECT * FROM ' . TABLE_FILES . ' F INNER JOIN ' . TABLE_USERS . ' U ON F.owner_id = U.id AND (U.id = :user_id OR U.owner_id = :owner_id) WHERE F.id=:file_id';
                $params = array();
                $params[':owner_id'] = $user_id;
                $params[':user_id'] = $user_id;
                $params[':file_id'] = (int)$file_id;

                $files = $this->dbh->prepare($fq);
                $files->execute($params);

                /**
                 * Continue
                 */
                if ($files->rowCount() > 0) {
                    $can_download = true;
                    $log_action = 7;
                }

                $fq = 'SELECT F.* FROM ' . TABLE_FILES . ' F INNER JOIN ' . TABLE_USERS . ' U ON F.owner_id = U.id INNER JOIN ' . TABLE_WORKSPACES_USERS . ' WU ON U.id = WU.user_id INNER JOIN ' . TABLE_WORKSPACES . ' W ON WU.workspace_id = W.id INNER JOIN ' . TABLE_WORKSPACES_USERS . ' WU2 ON WU2.workspace_id = W.id AND WU2.user_id = :user_id WHERE F.id=:file_id AND F.workspace_included=1 GROUP BY F.id';
                $params = array();
                $params[':user_id'] = $user_id;
                $params[':file_id'] = (int)$file_id;

                $files = $this->dbh->prepare($fq);
                $files->execute($params);

                /**
                 * Continue
                 */
                if ($files->rowCount() > 0) {
                    $can_download = true;
                    $log_action = 7;
                }
            } else {
                $can_download = true;
                $log_action = 7;
            }

            if ($can_download == true) {
                /**
                 * Add +1 to the download count
                 *
                 * @todo move into a method for an yet to create File class, for example add_to_download_count($file, $amount = 1)
                 */
                $statement = $this->dbh->prepare("INSERT INTO " . TABLE_DOWNLOADS . " (user_id , file_id, remote_ip, remote_host) VALUES (:user_id, :file_id, :remote_ip, :remote_host)");
                $statement->bindValue(':user_id', CURRENT_USER_ID, PDO::PARAM_INT);
                $statement->bindParam(':file_id', $file_id, PDO::PARAM_INT);
                $statement->bindParam(':remote_ip', $_SERVER['REMOTE_ADDR']);
                $statement->bindParam(':remote_host', $_SERVER['REMOTE_HOST']);
                $statement->execute();

                $this->downloadFile($filename_find, $filename_save, $file_id, $log_action);
            } else {
                header('Location:' . PAGE_STATUS_CODE_403);
                exit;
            }
        }
    }

    /**
     * Make a list of files ids to download on a compressed zip file
     *
     * @param $file_ids
     * @return string
     */
    public function returnFilesIds($file_ids)
    {
        $check_level = array(9, 8, 7, 0);
        if (isset($file_ids)) {
            // do a permissions check for logged in user
            if (isset($check_level) && current_role_in($check_level)) {
                $file_list = array();
                foreach ($file_ids as $key => $data) {
                    $file_list[] = $data['value'];
                }
                ob_clean();
                flush();
                $return = implode(',', $file_list);
            } else {
                return false;
            }
        } else {
            return false;
        }

        echo $return;
    }

    /**
     * Make and serve a zip file
     * @param $file_ids
     */
    public function downloadZip($file_ids)
    {
        $files_to_zip = array_map('intval', explode(',', $file_ids));

        foreach ($files_to_zip as $idx => $file) {
            $file = UPLOADED_FILES_DIR . DS . $file;
            if (!(realpath($file) && substr(realpath($file), 0, strlen(UPLOADED_FILES_DIR))) === UPLOADED_FILES_DIR) {
                unset($files_to_zip[$idx]);
            }
        }

        $added_files = 0;

        /**
         * Get the list of different groups the client belongs to.
         */
        $get_groups = new MembersActions();
        $get_arguments = array(
            'client_id' => CURRENT_USER_ID,
            'return' => 'list',
        );
        $found_groups = $get_groups->client_get_groups($get_arguments);

        $allowed_to_zip = []; // Files allowed to be downloaded

        foreach ($files_to_zip as $file_to_zip) {
            $statement = $this->dbh->prepare("SELECT id, url, original_url, expires, expiry_date FROM " . TABLE_FILES . " WHERE id = :file");
            $statement->bindParam(':file', $file_to_zip, PDO::PARAM_INT);
            $statement->execute();
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            $row = $statement->fetch();

            $this_file_id = $row['id'];
            $this_file_on_disk = $row['url'];
            $this_file_save_as = (!empty($row['original_url'])) ? $row['original_url'] : $row['url'];
            $this_file_expires = $row['expires'];
            $this_file_expiry_date = $row['expiry_date'];

            $this_file_expired = false;
            if ($this_file_expires == '1' && time() > strtotime($this_file_expiry_date)) {
                $this_file_expired = true;
            }

            /**
             * Check download permission
             */
            if (CURRENT_USER_LEVEL == 0) {
                if ($this_file_expires == '0' || $this_file_expired == false) {
                    $statement = $this->dbh->prepare("SELECT * FROM " . TABLE_FILES_RELATIONS . " WHERE (client_id = :client_id OR FIND_IN_SET(group_id, :groups)) AND file_id = :file_id AND hidden = '0'");
                    $statement->bindValue(':client_id', CURRENT_USER_ID, PDO::PARAM_INT);
                    $statement->bindParam(':groups', $found_groups);
                    $statement->bindParam(':file_id', $this_file_id, PDO::PARAM_INT);
                    $statement->execute();
                    $statement->setFetchMode(PDO::FETCH_ASSOC);
                    $row = $statement->fetch();

                    if ($row) {
                        /**
                         * Add the file
                         */
                        $allowed_to_zip[$row['file_id']] = array(
                            'on_disk' => $this_file_on_disk,
                            'save_as' => $this_file_save_as
                        );
                    }
                }
            } else {
                $allowed_to_zip[] = array(
                    'on_disk' => $this_file_on_disk,
                    'save_as' => $this_file_save_as
                );
            }
        }

        /**
         * Start adding the files to the zip
         */
        if (count($allowed_to_zip) > 0) {
            $zip_file = tempnam("tmp", "zip");
            $zip = new ZipArchive();
            $zip->open($zip_file, ZipArchive::OVERWRITE);

            //echo $zip_file;print_array($allowed_to_zip); die();

            foreach ($allowed_to_zip as $allowed_file_id => $allowed_file_info) {
                if ($zip->addFile(UPLOADED_FILES_DIR . DS . $allowed_file_info['on_disk'], $allowed_file_info['save_as'])) {
                    $added_files++;

                    /**
                     * Add +1 to the download count
                     *
                     * @todo move into a method for an yet to create File class, for example add_to_download_count($file, $amount = 1)
                     */
                    $statement = $this->dbh->prepare(
                        "INSERT INTO " . TABLE_DOWNLOADS . " (user_id , file_id, remote_ip, remote_host)"
                        . " VALUES (:user_id, :file_id, :remote_ip, :remote_host)"
                    );
                    $statement->bindValue(':user_id', CURRENT_USER_ID, PDO::PARAM_INT);
                    $statement->bindParam(':file_id', $this_file_id, PDO::PARAM_INT);
                    $statement->bindParam(':remote_ip', $_SERVER['REMOTE_ADDR']);
                    $statement->bindParam(':remote_host', $_SERVER['REMOTE_HOST']);
                    $statement->execute();

                    /**
                     * @todo log this specific file download
                     */
                }
            }

            $zip->close();

            if ($added_files > 0) {
                /**
                 * Record the action log
                 */
                $this->logger->addEntry(
                    [
                        'action' => 9,
                        'owner_id' => CURRENT_USER_ID,
                        'affected_account_name' => CURRENT_USER_USERNAME
                    ]
                );

                if (file_exists($zip_file)) {
                    setCookie("download_started", 1, time() + 20, '/', "", false, false);

                    $save_as = 'files_' . generateRandomString() . '.zip';
                    $this->serveFile($zip_file, $save_as);

                    unlink($zip_file);
                }
            }
        }
    }

    /**
     * Sends the file to the browser
     *
     * @param $filename
     * @param $save_as
     * @param $file_id
     * @param $log_action_number
     * @return void
     * @todo move into a Download class
     *
     */
    private function downloadFile($filename, $save_as, $file_id, $log_action_number)
    {
        $file_location = UPLOADED_FILES_DIR . DS . $filename;

        if (file_exists($file_location)) {
            /**
             * Record the action log
             */
            $this->logger->addEntry(
                [
                    'action' => $log_action_number,
                    'owner_id' => CURRENT_USER_ID,
                    'affected_file' => (int)$file_id,
                    'affected_file_name' => $filename,
                    'affected_account' => CURRENT_USER_ID,
                    'file_title_column' => true
                ]
            );

            $save_file_as = UPLOADED_FILES_DIR . DS . $save_as;

            $this->serveFile($file_location, $save_file_as);
            exit;
        } else {
            header('Location:' . PAGE_STATUS_CODE_404);
            exit;
        }
    }

    /**
     * Send file to the browser
     *
     * @param string $filename absolute full path to the file on disk
     * @param string $save_as original filename
     * @return void
     */
    private function serveFile($filename, $save_as)
    {
        if (file_exists($filename)) {
            session_write_close();
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($save_as));
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Cache-Control: private', false);
            header('Content-Length: ' . get_real_size($filename));
            header('Connection: close');
            //readfile($file_location);

            $context = stream_context_create();
            $file = fopen($filename, 'rb', false, $context);
            while (!feof($file)) {
                //usleep(1000000); //Reduce download speed
                echo stream_get_contents($file, 2014);
            }

            fclose($file);
        } else {
            header('Location:' . PAGE_STATUS_CODE_404);
            exit;
        }
    }
}
