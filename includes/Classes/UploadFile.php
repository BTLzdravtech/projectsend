<?php
/**
 * Class that handles all the actions and functions that can be applied to
 * files that are being uploaded.
 *
 * @package    ProjectSend
 * @subpackage Classes
 */

namespace ProjectSend\Classes;

use \PDO;

class UploadFile
{
    /**
     * @var PDO $dbh
     */
    private $dbh;
    private $logger;

    private $users;
    private $clients;
    private $groups;

    public $file_id;
    private $file_name;
    public $folder;
    public $assign_to;
    public $uploader;
    private $uploader_id;
    private $uploader_type;
    public $file;
    public $name;
    public $description;
    public $upload_state;
    private $hidden;
    /**
     * the $separator is used to replace invalid characters on a file name.
     */
    public $separator = '_';

    public function __construct()
    {
        global $dbh;

        $this->dbh = $dbh;
        $this->logger = new ActionsLog;
    }

    /**
     * Set the ID
     * @param $id
     */
    public function setId($id)
    {
        $this->file_id = $id;
    }

    /**
     * Return the ID
     *
     * @return int
     */
    public function getId()
    {
        if (!empty($this->file_id)) {
            return $this->file_id;
        }

        return false;
    }

    /**
     * Convert a string into a url safe address.
     * Original name: formatURL
     * John Magnolia / svick on StackOverflow
     *
     * @param string $unformatted
     * @return string
     * @link   http://stackoverflow.com/questions/2668854/sanitizing-strings-to-make-them-url-and-filename-safe
     */
    public function generateSafeFilename($unformatted)
    {
        $got = pathinfo(strtolower(trim($unformatted)));
        $url = $got['filename'];
        $ext = $got['extension'];

        //replace accent characters, forien languages
        $search = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ');
        $replace = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o');
        $url = str_replace($search, $replace, $url);

        //replace common characters
        $search = array('&', '£', '$');
        $replace = array('and', 'pounds', 'dollars');
        $url = str_replace($search, $replace, $url);

        // remove - for spaces and union characters
        $find = array(' ', '&', '\r\n', '\n', '+', ',', '//');
        $url = str_replace($find, '-', $url);

        //delete and replace rest of special chars
        $find = array('/[^a-z0-9\-<>_]/', '/[\-]+/', '/<[^>]*>/');
        $replace = array('', '-', '');
        $uri = preg_replace($find, $replace, $url);

        return $uri . '.' . $ext;
    }

    /**
     * Check if the file extension is among the allowed ones, that are defined on
     * the options page.
     * @param $filename
     * @return bool
     */
    public function isFiletypeAllowed($filename)
    {
        if (true === CAN_UPLOAD_ANY_FILE_TYPE) {
            return true;
        } else {
            $safe_filename = $filename;
            /** @noinspection PhpUndefinedConstantInspection */
            $allowed_file_types = str_replace(',', '|', ALLOWED_FILE_TYPES);
            $file_types = "/^\.(" . $allowed_file_types . "){1}$/i";
            if (preg_match($file_types, strrchr($safe_filename, '.'))) {
                return true;
            }
        }
    }

    /**
     * Generate a safe filename that includes only letters, numbers and underscores.
     * If there are multiple invalid characters in a row, only one replacement character
     * will be used, to avoid unnecessarily long file names.
     * @param $name
     * @return string
     */
    public function safeRename($name)
    {
        $this->name = $name;
        $safe_filename = $this->generateSafeFilename($this->name);
        return basename($safe_filename);
    }

    /**
     * Rename a file using only letters, numbers and underscores.
     * Used when reading the temp folder to add files to ProjectSend via the "Add from FTP"
     * feature.
     *
     * Files are renamed before being shown on the list.
     * @param $name
     * @param $folder
     * @return bool|string
     */
    public function safeRenameOnDisk($name, $folder)
    {
        $this->name = $name;
        $this->folder = $folder;
        $safe_filename = $this->generateSafeFilename($this->name);
        if (rename($this->folder . '/' . $this->name, $this->folder . '/' . $safe_filename)) {
            return $safe_filename;
        } else {
            return false;
        }
    }

    /**
     * Used to copy a file from the temporary folder (the default location where it's put
     * after uploading it) to the final folder.
     * If succesful, the original file is then deleted.
     * @param $arguments
     * @return array|bool|string
     */
    public function moveFile($arguments)
    {
        $uploaded_name = $arguments['uploaded_name'];
        $filename = $arguments['filename'];
        $uid = CURRENT_USER_ID;
        $username = CURRENT_USER_USERNAME;
        $makehash = sha1($username);

        $filename_on_disk = time() . '-' . $makehash . '-' . $filename;
        //$this->file_final_name = $filename;
        $path = UPLOADED_FILES_DIR . DS . $filename_on_disk;
        if (rename($uploaded_name, $path)) {
            chmod($path, 0644);
            $path = array(
                'filename_original' => $filename,
                'filename_disk' => $filename_on_disk,
            );
            return $path;
        } else {
            return false;
        }
    }

    /**
     * Called after correctly moving the file to the final location.
     * @param $arguments
     * @return mixed
     */
    public function addNew($arguments)
    {
        $file_on_disk = (!empty($arguments['file_disk'])) ? $arguments['file_disk'] : '';
        $post_file = (!empty($arguments['file_original'])) ? $arguments['file_original'] : '';
        $this->name = encode_html($arguments['name']);
        $this->description = encode_html($arguments['description']);
        $this->uploader = $arguments['uploader'];
        $this->uploader_id = $arguments['uploader_id'];
        $this->uploader_type = $arguments['uploader_type'];
        $this->hidden = (!empty($arguments['hidden'])) ? 1 : 0;
        $expires = (!empty($arguments['expires'])) ? 1 : 0;
        $expiry_date = (!empty($arguments['expiry_date'])) ? date("Y-m-d", strtotime($arguments['expiry_date'])) : date("Y-m-d");
        $is_public = (!empty($arguments['public'])) ? 1 : 0;
        $is_workspace = (!empty($arguments['workspaces'])) ? 1 : 0;
        $public_token = generateRandomString(32);

        $statement = $this->dbh->prepare(
            "INSERT INTO " . TABLE_FILES . " (url, original_url, filename, description, owner_id, uploader, expires, expiry_date, public_allow, workspace_included, public_token)"
            . "VALUES (:url, :original_url, :name, :description, :owner_id, :uploader, :expires, :expiry_date, :public, :workspace, :token)"
        );
        $statement->bindParam(':url', $file_on_disk);
        $statement->bindParam(':original_url', $post_file);
        $statement->bindParam(':name', $this->name);
        $statement->bindParam(':owner_id', $this->uploader_id);
        $statement->bindParam(':description', $this->description);
        $statement->bindParam(':uploader', $this->uploader);
        $statement->bindParam(':expires', $expires, PDO::PARAM_INT);
        $statement->bindParam(':expiry_date', $expiry_date);
        $statement->bindParam(':public', $is_public, PDO::PARAM_INT);
        $statement->bindParam(':workspace', $is_workspace, PDO::PARAM_INT);

        $statement->bindParam(':token', $public_token);
        $statement->execute();

        $this->file_id = $this->dbh->lastInsertId();
        $state['new_file_id'] = $this->file_id;

        $state['public_token'] = $public_token;

        if (!empty($statement)) {
            /**
             * Record the action log
             */
            if ($this->uploader_type == 'user') {
                $action_type = 5;
            } elseif ($this->uploader_type == 'client') {
                $action_type = 6;
            }

            $this->logger->addEntry(
                [
                    'action' => $action_type,
                    'owner_id' => $this->uploader_id,
                    'affected_file' => $this->file_id,
                    'affected_file_name' => $this->name,
                    'affected_account_name' => $this->uploader
                ]
            );

            $state['database'] = true;
        } else {
            $state['database'] = false;
        }

        return $state;
    }

    /**
     * Called after correctly moving the file to the final location.
     * @param $arguments
     * @return mixed
     */
    public function saveExisting($arguments)
    {
        $file_on_disk = (!empty($arguments['file_disk'])) ? $arguments['file_disk'] : '';
        $post_file = (!empty($arguments['file_original'])) ? $arguments['file_original'] : '';
        $this->name = encode_html($arguments['name']);
        $this->description = encode_html($arguments['description']);
        $this->uploader = $arguments['uploader'];
        $this->uploader_id = $arguments['uploader_id'];
        $this->uploader_type = $arguments['uploader_type'];
        $this->hidden = (!empty($arguments['hidden'])) ? 1 : 0;
        $expires = (!empty($arguments['expires'])) ? 1 : 0;
        $expiry_date = (!empty($arguments['expiry_date'])) ? date("Y-m-d", strtotime($arguments['expiry_date'])) : date("Y-m-d");
        $is_public = (!empty($arguments['public'])) ? 1 : 0;
        $is_workspace = (!empty($arguments['workspaces'])) ? 1 : 0;
        $public_token = generateRandomString(32);

        $statement = $this->dbh->prepare("SELECT id, public_allow, public_token FROM " . TABLE_FILES . " WHERE url = :url");
        $statement->bindParam(':url', $post_file);
        $statement->execute();
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        while ($row = $statement->fetch()) {
            $this->file_id = $row["id"];
            $state['new_file_id'] = $this->file_id;
            if (!empty($row["public_token"])) {
                $public_token = $row["public_token"];
                $state['public_token'] = $row["public_token"];
            }
            /**
             * If a client is editing a file, the public settings should
             * not be reset.
             */
            if (CURRENT_USER_LEVEL == 0) {
                $is_public = $row["public_allow"];
            }
        }
        $statement = $this->dbh->prepare(
            "UPDATE " . TABLE_FILES . " SET
                filename = :title,
                description = :description,
                expires = :expires,
                expiry_date = :expiry_date,
                public_allow = :public,
                workspace_included = :workspace,
                public_token = :token
                WHERE id = :id
            "
        );
        $statement->bindParam(':title', $this->name);
        $statement->bindParam(':description', $this->description);
        $statement->bindParam(':expires', $expires, PDO::PARAM_INT);
        $statement->bindParam(':expiry_date', $expiry_date);
        $statement->bindParam(':public', $is_public, PDO::PARAM_INT);
        $statement->bindParam(':workspace', $is_workspace, PDO::PARAM_INT);
        $statement->bindParam(':token', $public_token);
        $statement->bindParam(':id', $this->file_id, PDO::PARAM_INT);
        $statement->execute();

        if (!empty($statement)) {
            /**
             * Record the action log
             */
            if ($this->uploader_type == 'user') {
                $action_type = 32;
            } elseif ($this->uploader_type == 'client') {
                $action_type = 33;
            }

            $this->logger->addEntry(
                [
                    'action' => $action_type,
                    'owner_id' => $this->uploader_id,
                    'affected_file' => $this->file_id,
                    'affected_file_name' => $this->name,
                    'affected_account_name' => $this->uploader
                ]
            );

            $state['database'] = true;
        } else {
            $state['database'] = false;
        }

        return $state;
    }

    /**
     * Used to add new assignments and notifications
     * @param $arguments
     */
    public function addFileAssignment($arguments)
    {
        $this->name = encode_html($arguments['name']);
        $this->uploader_id = $arguments['uploader_id'];
        $this->groups = $arguments['all_groups'];
        $this->users = $arguments['all_users'];
        $this->clients = $arguments['all_clients'];

        if (!empty($arguments['assign_to']['users'])) {
            foreach ($arguments['assign_to']['users'] as $user_id) {
                self::saveAssignment('user', $user_id);
            }
        }

        if (!empty($arguments['assign_to']['clients'])) {
            foreach ($arguments['assign_to']['clients'] as $client_id) {
                self::saveAssignment('client', $client_id);
            }
        }

        if (!empty($arguments['assign_to']['groups'])) {
            foreach ($arguments['assign_to']['groups'] as $group_id) {
                self::saveAssignment('group', $group_id);
            }
        }
    }

    private function saveAssignment($type, $id)
    {
        if (empty($type) || empty($id)) {
            return false;
        }

        if ($type != 'user' && $type != 'client' && $type != 'group') {
            return false;
        }

        switch ($type) {
            case 'user':
                $add_to = 'user_id';
                $account_name = $this->users[$id];
                $action_number = 46;
                break;
            case 'client':
                $add_to = 'client_id';
                $account_name = $this->clients[$id];
                $action_number = 25;
                break;
            case 'group':
                $add_to = 'group_id';
                $account_name = $this->groups[$id];
                $action_number = 26;
                break;
            default:
                return false;
                break;
        }

        $statement = $this->dbh->prepare("INSERT INTO " . TABLE_FILES_RELATIONS . " (file_id, " . $add_to . ", hidden) VALUES (:file_id, :assignment, :hidden)");
        $statement->bindParam(':file_id', $this->file_id, PDO::PARAM_INT);
        $statement->bindParam(':assignment', $id);
        $statement->bindParam(':hidden', $this->hidden, PDO::PARAM_INT);
        $statement->execute();

        if ($this->uploader_type == 'user') {
            /**
             * Record the action log
             */
            $this->logger->addEntry(
                [
                    'action' => $action_number,
                    'owner_id' => $this->uploader_id,
                    'affected_file' => $this->file_id,
                    'affected_file_name' => $this->name,
                    'affected_account' => $id,
                    'affected_account_name' => $account_name
                ]
            );
        }

        return true;
    }

    /**
     * Used to create the new notifications on the database
     * @param $arguments
     */
    public function addNotifications($arguments)
    {
        $this->uploader_type = $arguments['uploader_type'];
        $this->file_id = $arguments['new_file_id'];

        /**
         * Define type of uploader for the notifications queries.
         */
        if ($this->uploader_type == 'user') {
            $notif_uploader_type = 1;
        } elseif ($this->uploader_type == 'client') {
            $notif_uploader_type = 0;
        }

        if (!empty($arguments['assign_to'])) {
            $this->assign_to = $arguments['assign_to'];
            $distinct_notifications = array();

            foreach ($this->assign_to as $key => $assignments) {
                foreach ($assignments as $assignment) {
                    $id_only = $assignment;
                    switch ($key) {
                        case 'users':
                            $add_to = 'user_id';
                            break;
                        case 'clients':
                            $add_to = 'client_id';
                            break;
                        case 'groups':
                            $add_to = 'group_id';
                            break;
                    }
                    /**
                     * Add the notification to the table
                     */
                    $members_to_notify = array();

                    if ($add_to == 'group_id') {
                        $statement = $this->dbh->prepare("SELECT DISTINCT client_id FROM " . TABLE_MEMBERS . " WHERE group_id = :id");
                        $statement->bindParam(':id', $id_only, PDO::PARAM_INT);
                        $statement->execute();
                        $statement->setFetchMode(PDO::FETCH_ASSOC);
                        while ($row = $statement->fetch()) {
                            $members_to_notify[] = $row['client_id'];
                        }
                    } else {
                        $members_to_notify[] = $id_only;
                    }

                    if (!empty($members_to_notify)) {
                        foreach ($members_to_notify as $add_notify) {
                            $current_assignment = $this->file_id . '-' . $add_notify;
                            if (!in_array($current_assignment, $distinct_notifications)) {
                                $statement = $this->dbh->prepare(
                                    "INSERT INTO " . TABLE_NOTIFICATIONS . " (file_id, $add_to, upload_type, sent_status, times_failed)
                                    VALUES (:file_id, :notification, :type, '0', '0')"
                                );
                                $statement->bindParam(':file_id', $this->file_id, PDO::PARAM_INT);
                                $statement->bindParam(':notification', $add_notify, PDO::PARAM_INT);
                                $statement->bindParam(':type', $notif_uploader_type);
                                $statement->execute();

                                $distinct_notifications[] = $current_assignment;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Used when editing a file
     * @param $arguments
     */
    public function cleanAssignments($arguments)
    {
        $this->assign_to = $arguments['assign_to'];
        $this->file_id = $arguments['file_id'];
        $this->file_name = $arguments['file_name'];
        $current_users = $arguments['current_users'];
        $current_clients = $arguments['current_clients'];
        $current_groups = $arguments['current_groups'];
        $owner_id = $arguments['owner_id'];

        $assign_to_clients = array();
        $assign_to_groups = array();
        $delete_from_db_clients = array();
        $delete_from_db_groups = array();

        foreach ($this->assign_to as $type => $ids) {
            $id_only = $ids;
            switch ($type) {
                case 'users':
                    $assign_to_users = $id_only;
                    break;
                case 'clients':
                    $assign_to_clients = $id_only;
                    break;
                case 'groups':
                    $assign_to_groups = $id_only;
                    break;
            }
        }

        foreach ($current_users as $user) {
            if (!in_array($user, $assign_to_users)) {
                $delete_from_db_users[] = $user;
            }
        }
        foreach ($current_clients as $client) {
            if (!in_array($client, $assign_to_clients)) {
                $delete_from_db_clients[] = $client;
            }
        }
        foreach ($current_groups as $group) {
            if (!in_array($group, $assign_to_groups)) {
                $delete_from_db_groups[] = $group;
            }
        }

        $delete_arguments = array(
            'users' => $delete_from_db_users,
            'clients' => $delete_from_db_clients,
            'groups' => $delete_from_db_groups,
            'owner_id' => $owner_id
        );

        $this->deleteAssignments($delete_arguments);
    }

    /**
     * Used when editing a file
     * @param $arguments
     */
    public function cleanAllAssignments($arguments)
    {
        $this->file_id = $arguments['file_id'];
        $this->file_name = $arguments['file_name'];
        $owner_id = $arguments['owner_id'];

        $delete_from_db_clients = array();
        $delete_from_db_groups = array();

        $statement = $this->dbh->prepare("SELECT id, file_id, client_id, group_id FROM " . TABLE_FILES_RELATIONS . " WHERE file_id = :id");
        $statement->bindParam(':id', $this->file_id, PDO::PARAM_INT);
        $statement->execute();
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        while ($row = $statement->fetch()) {
            if (!empty($row['client_id'])) {
                $delete_from_db_clients[] = $row['client_id'];
            } elseif (!empty($row['group_id'])) {
                $delete_from_db_groups[] = $row['group_id'];
            }
        }

        $delete_arguments = array(
            'clients' => $delete_from_db_clients,
            'groups' => $delete_from_db_groups,
            'owner_id' => $owner_id
        );

        $this->deleteAssignments($delete_arguments);
    }


    /**
     * Receives the data from any of the 2 clear assignments functions
     * @param $arguments
     */
    private function deleteAssignments($arguments)
    {
        $users = $arguments['users'];
        $clients = $arguments['clients'];
        $groups = $arguments['groups'];
        $owner_id = $arguments['owner_id'];

        /**
         * Get a list of users names for the log
         */
        if (!empty($users)) {
            $delete_users = implode(',', array_unique($users));

            $statement = $this->dbh->prepare("SELECT id, name FROM " . TABLE_USERS . " WHERE FIND_IN_SET(id, :users)");
            $statement->bindParam(':users', $delete_users);
            $statement->execute();
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            while ($row = $statement->fetch()) {
                $users_names[$row['id']] = $row['name'];
            }

            /**
             * Remove existing assignments of this file/clients
             */
            $statement = $this->dbh->prepare("DELETE FROM " . TABLE_FILES_RELATIONS . " WHERE file_id = :file_id AND FIND_IN_SET(user_id, :users)");
            $statement->bindParam(':file_id', $this->file_id, PDO::PARAM_INT);
            $statement->bindParam(':users', $delete_users);
            $statement->execute();

            /**
             * Record the action log
             */
            foreach ($users as $deleted_user) {
                $this->logger->addEntry(
                    [
                        'action' => 47,
                        'owner_id' => $owner_id,
                        'affected_file' => $this->file_id,
                        'affected_file_name' => $this->file_name,
                        'affected_account' => $deleted_user,
                        'affected_account_name' => $users_names[$deleted_user]
                    ]
                );
            }
        }

        /**
         * Get a list of clients names for the log
         */
        if (!empty($clients)) {
            $delete_clients = implode(',', array_unique($clients));

            $statement = $this->dbh->prepare("SELECT id, name FROM " . TABLE_USERS . " WHERE FIND_IN_SET(id, :clients)");
            $statement->bindParam(':clients', $delete_clients);
            $statement->execute();
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            while ($row = $statement->fetch()) {
                $clients_names[$row['id']] = $row['name'];
            }

            /**
             * Remove existing assignments of this file/clients
             */
            $statement = $this->dbh->prepare("DELETE FROM " . TABLE_FILES_RELATIONS . " WHERE file_id = :file_id AND FIND_IN_SET(client_id, :clients)");
            $statement->bindParam(':file_id', $this->file_id, PDO::PARAM_INT);
            $statement->bindParam(':clients', $delete_clients);
            $statement->execute();

            /**
             * Record the action log
             */
            foreach ($clients as $deleted_client) {
                $this->logger->addEntry(
                    [
                        'action' => 10,
                        'owner_id' => $owner_id,
                        'affected_file' => $this->file_id,
                        'affected_file_name' => $this->file_name,
                        'affected_account' => $deleted_client,
                        'affected_account_name' => $clients_names[$deleted_client]
                    ]
                );
            }
        }
        /**
         * Get a list of groups names for the log
         */
        if (!empty($groups)) {
            $delete_groups = implode(',', array_unique($groups));

            $statement = $this->dbh->prepare("SELECT id, name FROM " . TABLE_GROUPS . " WHERE FIND_IN_SET(id, :groups)");
            $statement->bindParam(':groups', $delete_groups);
            $statement->execute();
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            while ($row = $statement->fetch()) {
                $groups_names[$row['id']] = $row['name'];
            }

            /**
             * Remove existing assignments of this file/groups
             */
            $statement = $this->dbh->prepare("DELETE FROM " . TABLE_FILES_RELATIONS . " WHERE file_id = :file_id AND FIND_IN_SET(group_id, :groups)");
            $statement->bindParam(':file_id', $this->file_id, PDO::PARAM_INT);
            $statement->bindParam(':groups', $delete_groups);
            $statement->execute();

            /**
             * Record the action log
             */
            foreach ($groups as $deleted_group) {
                $this->logger->addEntry(
                    [
                        'action' => 11,
                        'owner_id' => $owner_id,
                        'affected_file' => $this->file_id,
                        'affected_file_name' => $this->file_name,
                        'affected_account' => $deleted_group,
                        'affected_account_name' => $groups_names[$deleted_group]
                    ]
                );
            }
        }
    }

    /**
     * Used to save the categories relations
     * @param $arguments
     */
    public function setCategories($arguments)
    {
        $this->file_id = $arguments['file_id'];
        $categories = $arguments['categories'];

        if (!empty($categories)) {
            $categories_current = array();
            $categories_to_delete = array();

            $statement = $this->dbh->prepare("SELECT * FROM " . TABLE_CATEGORIES_RELATIONS . " WHERE file_id = :file_id");
            $statement->bindParam(':file_id', $this->file_id, PDO::PARAM_INT);
            $statement->execute();
            $statement->setFetchMode(PDO::FETCH_ASSOC);
            while ($row = $statement->fetch()) {
                $categories_current[$row['cat_id']] = $row['cat_id'];
            }

            /**
             * Add existing -on DB- but not selected on the form to
             * the delete array. This uses the ID of the record.
             */
            if (!empty($categories_current)) {
                foreach ($categories_current as $cat) {
                    if (!in_array($cat, $categories)) {
                        $categories_to_delete[$cat] = $cat;
                    }
                }

                $categories_to_delete = implode(',', array_unique($categories_to_delete));

                $statement = $this->dbh->prepare("DELETE FROM " . TABLE_CATEGORIES_RELATIONS . " WHERE file_id = :file_id AND FIND_IN_SET(cat_id, :categories)");
                $statement->bindParam(':file_id', $this->file_id, PDO::PARAM_INT);
                $statement->bindParam(':categories', $categories_to_delete);
                $statement->execute();
            }

            /**
             * Compare the ones passed through the form to the
             * ones that are already on the database.
             * If it's not in the current array, add the row.
             */
            foreach ($categories as $cat) {
                if (!in_array($cat, $categories_current)) {
                    $statement = $this->dbh->prepare(
                        "INSERT INTO " . TABLE_CATEGORIES_RELATIONS . " (file_id, cat_id)"
                        . "VALUES (:file_id, :cat_id)"
                    );
                    $statement->bindParam(':file_id', $this->file_id, PDO::PARAM_INT);
                    $statement->bindParam(':cat_id', $cat, PDO::PARAM_INT);
                    $statement->execute();
                }
            }
        } else {
            /**
             * No value came from the form, so delete all existing
             */
            $statement = $this->dbh->prepare("DELETE FROM " . TABLE_CATEGORIES_RELATIONS . " WHERE file_id = :file_id");
            $statement->bindParam(':file_id', $this->file_id, PDO::PARAM_INT);
            $statement->execute();
        }
    }
}
