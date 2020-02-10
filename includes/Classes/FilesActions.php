<?php
/**
 * Class that handles all the actions and functions that can be applied to
 * the already uploaded files.
 *
 * @package    ProjectSend
 * @subpackage Classes
 */
namespace ProjectSend\Classes;

use Exception;
use \PDO;

class FilesActions
{
    private $dbh;
    private $logger;

    public $files = array();

    public function __construct(PDO $dbh = null)
    {
        if (empty($dbh)) {
            global $dbh;
        }

        $this->dbh = $dbh;
        $this->logger = new ActionsLog;
    }

    public function deleteFiles($rel_id, $service_run = false)
    {
        $can_delete = false;
        $check_level = array(9,8,7,0);

        if (isset($rel_id)) {
            /**
             * Do a permissions check
            */
            if ($service_run || (isset($check_level) && current_role_in($check_level))) {
                $file_id = $rel_id;
                $sql = $this->dbh->prepare("SELECT url, original_url, uploader, filename FROM " . TABLE_FILES . " WHERE id = :file_id");
                $sql->bindParam(':file_id', $file_id, PDO::PARAM_INT);
                $sql->execute();
                $sql->setFetchMode(PDO::FETCH_ASSOC);
                while ($row = $sql->fetch()) {
                    if (CURRENT_USER_LEVEL == '0') {
                        /** @noinspection PhpUndefinedConstantInspection */
                        if (CLIENTS_CAN_DELETE_OWN_FILES == '1' && $row['uploader'] == CURRENT_USER_USERNAME) {
                            $can_delete    = true;
                        }
                    } elseif (CURRENT_USER_LEVEL == '7') {
                        if ($row['uploader'] == CURRENT_USER_USERNAME) {
                            $can_delete = true;
                        }
                    } else {
                        $can_delete = true;
                    }

                    $file_url = $row['url'];
                    $title = $row['filename'];
                    
                    /**
                     * Thumbnails should be deleted too.
                     * Start by making a pattern with the file name, a shorter version of what's
                     * used on make_thumbnail.
                     */
                    $thumbnails_pattern = 'thumb_' . md5($row['url']);
                    $find_thumbnails = glob(THUMBNAILS_FILES_DIR . DS . $thumbnails_pattern . '*.*');
                    //print_array($find_thumbnails);
                }

                /**
                 * Delete the reference to the file on the database
                */
                if (true === $can_delete) {
                    $sql = $this->dbh->prepare("DELETE FROM " . TABLE_FILES . " WHERE id = :file_id");
                    $sql->bindParam(':file_id', $file_id, PDO::PARAM_INT);
                    $sql->execute();
                    /**
                     * Use the id and uri information to delete the file.
                     *
                     * @see delete_file_from_disk
                     */
                    delete_file_from_disk(UPLOADED_FILES_DIR . DS . $file_url);
                    
                    /**
                     * Delete the thumbnails
                    */
                    foreach ($find_thumbnails as $thumbnail) {
                        delete_file_from_disk($thumbnail);
                    }

                    /**
                     * Record the action log
                    */
                    $this->logger->addEntry(
                        [
                            'action' => 12,
                            'owner_id' => $service_run? '-1' : CURRENT_USER_ID,
                            'owner_user' => $service_run? 'service' : CURRENT_USER_NAME,
                            'affected_file' => $file_id,
                            'affected_file_name' => $title
                        ]
                    );

                    return true;
                }

                return false;
            }
        }
    }

    public function changeHiddenStatus($change_to, $file_id, $modify_type, $modify_id)
    {
        $check_level = array(9,8,7);
        
        if (empty($file_id)) {
            return false;
        }

        switch ($change_to) {
            case 1:
                $log_action_number = 21;
                break;
            case 0:
                $log_action_number = 22;
                break;
            default:
                throw new Exception('Invalid status code');
        }

        switch ($modify_type) {
            case 'client_id':
                $client = get_client_by_id($modify_id);
                $log_account_name = $client['name'];
                break;
            case 'group_id':
                $group = get_group_by_id($modify_id);
                $log_account_name = $group['name'];
                break;
            default:
                throw new Exception('Invalid modify type');
        }

        /**
         * Do a permissions check
        */
        if (isset($check_level) && current_role_in($check_level)) {
            $sql = "UPDATE " . TABLE_FILES_RELATIONS . " SET hidden=:hidden WHERE file_id = :file_id AND " . $modify_type . " = :modify_id";
            $statement = $this->dbh->prepare($sql);
            $statement->bindParam(':hidden', $change_to, PDO::PARAM_INT);
            $statement->bindParam(':file_id', $file_id, PDO::PARAM_INT);
            $statement->bindParam(':modify_id', $modify_id, PDO::PARAM_INT);
            $statement->execute();

            $file = get_file_by_id($file_id);

            /**
             * Record the action log
            */
            $this->logger->addEntry(
                [
                    'action' => $log_action_number,
                    'owner_id' => CURRENT_USER_ID,
                    'affected_file' => $file_id,
                    'affected_file_name' => $file['title'],
                    'affected_account_name' => $log_account_name,
                ]
            );

            return true;
        }
        
        return false;
    }

    public function hideForEveryone($file_id)
    {
        $check_level = array(9,8,7);
        
        if (empty($file_id)) {
            return false;
        }

        /**
         * Do a permissions check
        */
        if (isset($check_level) && current_role_in($check_level)) {
            $sql = $this->dbh->prepare("UPDATE " . TABLE_FILES_RELATIONS . " SET hidden='1' WHERE file_id = :file_id");
            $sql->bindParam(':file_id', $file_id, PDO::PARAM_INT);
            $sql->execute();

            $file = get_file_by_id($file_id);

            /**
             * Record the action log
            */
            $this->logger->addEntry(
                [
                    'action' => 40,
                    'owner_id' => CURRENT_USER_ID,
                    'affected_file' => $file_id,
                    'affected_file_name' => $file['title']
                ]
            );

            return true;
        }

        return false;
    }

    public function unassignFile($file_id, $modify_type, $modify_id)
    {
        $check_level = array(9,8,7);
        
        if (empty($file_id)) {
            return false;
        }

        switch ($modify_type) {
            case 'client_id':
                $log_action_number = 10;
                $client = get_client_by_id($modify_id);
                $log_account_name = $client['name'];
                break;
            case 'group_id':
                $log_action_number = 11;
                $group = get_group_by_id($modify_id);
                $log_account_name = $group['name'];
                break;
            default:
                throw new Exception('Invalid modify type');
        }

        /**
         * Do a permissions check
        */
        if (isset($check_level) && current_role_in($check_level)) {
            $sql = "DELETE FROM " . TABLE_FILES_RELATIONS . " WHERE file_id = :file_id AND " . $modify_type . " = :modify_id";
            $statement = $this->dbh->prepare($sql);
            $statement->bindParam(':file_id', $file_id, PDO::PARAM_INT);
            $statement->bindParam(':modify_id', $modify_id, PDO::PARAM_INT);
            $statement->execute();

            $file = get_file_by_id($file_id);

            /**
             * Record the action log
            */
            $this->logger->addEntry(
                [
                    'action' => $log_action_number,
                    'owner_id' => CURRENT_USER_ID,
                    'affected_file' => $file_id,
                    'affected_file_name' => $file['title'],
                    'affected_account_name' => $log_account_name,
                ]
            );

            return true;
        }

        return false;
    }
}
