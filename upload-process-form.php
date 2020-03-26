<?php /** @noinspection PhpIllegalStringOffsetInspection */
/**
 * Uploading files, step 2
 *
 * This file handles all the uploaded files, whether you are
 * coming from the "Upload from computer" or "Find orphan files"
 * pages. The only difference is from which POST array it takes
 * the information to list the available files to process.
 *
 * It can display up tp 3 tables:
 * One that will list all the files that were brought in from
 * the first step. One with the confirmed uploaded and assigned
 * files, and a possible third one with the ones that failed.
 *
 * @package    ProjectSend
 * @subpackage Upload
 */
define('IS_FILE_EDITOR', true);

$allowed_levels = array(9, 8, 7, 0);
require_once 'bootstrap.php';

global $dbh;

$active_nav = 'files';

$page_title = __('Upload files', 'cftp_admin');

$page_id = 'new_uploads_editor';

require_once ADMIN_VIEWS_DIR . DS . 'header.php';

define('CAN_INCLUDE_FILES', true);
?>

    <div class="col-xs-12">

        <?php
        /**
         * Coming from the web uploader
         */
        if (isset($_POST['finished_files'])) {
            $uploaded_files = array_filter($_POST['finished_files']);
        }
        /**
         * Coming from upload by FTP
         */
        if (isset($_POST['add'])) {
            $uploaded_files = $_POST['add'];
        }

        /**
         * A hidden field sends the list of failed files as a string,
         * where each filename is separated by a comma.
         * Here we change it into an array so we can list the files
         * on a separate table.
         */
        if (isset($_POST['upload_failed'])) {
            $upload_failed_hidden_post = array_filter(explode(',', $_POST['upload_failed']));
        }
        /**
         * Files that failed are removed from the uploaded files list.
         */
        if (isset($upload_failed_hidden_post) && count($upload_failed_hidden_post) > 0) {
            foreach ($upload_failed_hidden_post as $failed) {
                $delete_key = array_search($failed, $uploaded_files);
                unset($uploaded_files[$delete_key]);
            }
        }

        /**
         * Define the arrays
         */
        $upload_failed = array();
        $move_failed = array();

        /**
         * $empty_fields counts the amount of "name" fields that
         * were not completed.
         */
        $empty_fields = 0;

        if (CURRENT_USER_LEVEL == 8 || CURRENT_USER_LEVEL == 7) {
            $owner_id_condition = " WHERE owner_id=" . CURRENT_USER_ID;
        } elseif (CURRENT_USER_LEVEL == 0) {
            $owner_id_condition = " WHERE owner_id=" . CURRENT_USER_OWNER_ID;
        }

        /**
         * Fill the users array that will be used on the notifications process
         */
        $users = array();
        $statement = $dbh->prepare("SELECT id, name, level FROM " . TABLE_USERS . $owner_id_condition . " ORDER BY name");
        $statement->execute();
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        while ($row = $statement->fetch()) {
            $users[$row["id"]] = $row["name"];
            if ($row["level"] == '0') {
                $clients[$row["id"]] = $row["name"];
            }
        }

        /**
         * Fill the groups array that will be used on the form
         */
        $groups = array();
        $statement = $dbh->prepare("SELECT id, name FROM " . TABLE_GROUPS . $owner_id_condition . " ORDER BY name");
        $statement->execute();
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        while ($row = $statement->fetch()) {
            $groups[$row["id"]] = $row["name"];
        }

        /**
         * Fill the categories array that will be used on the form
         */
        $categories = array();
        $get_categories = get_categories();

        /**
         * Make an array of file urls that are on the DB already.
         */
        $statement = $dbh->prepare("SELECT DISTINCT url FROM " . TABLE_FILES);
        $statement->execute();
        $statement->setFetchMode(PDO::FETCH_ASSOC);

        $urls_db_files = array();
        while ($row = $statement->fetch()) {
            $urls_db_files[] = $row["url"];
        }

        /**
         * A posted form will include information of the uploaded files
         * (name, description and client).
         */
        if (isset($_POST['submit'])) {
            /**
             * Get the ID of the current client that is uploading files.
             */
            if (CURRENT_USER_LEVEL == 0) {
                $client_my_info = get_client_by_username($global_user);
                $client_my_id = $client_my_info["id"];
            }

            $n = 0;

            foreach ($_POST['file'] as $file) {
                $n++;

                if (!empty($file['name'])) {
                    $this_upload = new ProjectSend\Classes\UploadFile;
                    if (!in_array($file['file'], $urls_db_files)) {
                        $file['file'] = $this_upload->safeRename($file['file']);
                    }
                    $location = UPLOADED_FILES_DIR . DS . $file['file'];

                    if (file_exists($location)) {
                        $move_arguments = array(
                            'uploaded_name' => $location,
                            'filename' => $file['file'],
                        );
                        $upload_move = $this_upload->moveFile($move_arguments);
                        $new_filename = $upload_move['filename_disk'];
                        $original_filename = $upload_move['filename_original'];

                        if (!empty($new_filename)) {
                            $delete_key = array_search($file['original'], $uploaded_files);
                            unset($uploaded_files[$delete_key]);

                            // Patch by lmsilva
                            $new_filename = basename($new_filename);
                            $original_filename = basename($original_filename);

                            /**
                             * Unassigned files are kept as orphans and can be related
                             * to clients or groups later.
                             */

                            /**
                             * Add to the database for each client / group selected
                             */
                            $add_arguments = array(
                                'file_disk' => $new_filename,
                                'file_original' => $original_filename,
                                'name' => $file['name'],
                                'description' => $file['description'],
                                'uploader' => $global_user,
                                'uploader_id' => CURRENT_USER_ID,
                            );

                            /**
                             * Set notifications to YES by default
                             */
                            $send_notifications = true;

                            if (!empty($file['hidden'])) {
                                $add_arguments['hidden'] = $file['hidden'];
                                $send_notifications = false;
                            }

                            if (!empty($file['assignments'])) {
                                $add_arguments['assign_to'] = $file['assignments'];
                                $assignations_count = count($file['assignments']);
                            } else {
                                $assignations_count = '0';
                            }

                            /**
                             * Uploader is a client
                             */
                            if (CURRENT_USER_LEVEL == 0) {
                                $add_arguments['assign_to']['clients'] = array($client_my_id);
                                $add_arguments['hidden'] = '0';
                                $add_arguments['uploader_type'] = 'client';
                                /** @noinspection PhpUndefinedConstantInspection */
                                if (CLIENTS_CAN_SET_EXPIRATION_DATE && !empty($file['expires'])) {
                                    $add_arguments['expires'] = '1';
                                    $add_arguments['expiry_date'] = $file['expiry_date'];
                                } else {
                                    $add_arguments['expires'] = '0';
                                }
                                $add_arguments['public'] = '0';
                                $add_arguments['workspaces'] = '1';
                            } else {
                                $add_arguments['uploader_type'] = 'user';
                                if (!empty($file['expires'])) {
                                    $add_arguments['expires'] = '1';
                                    $add_arguments['expiry_date'] = $file['expiry_date'];
                                }
                                if (!empty($file['public'])) {
                                    $add_arguments['public'] = '1';
                                }
                                if (!empty($file['workspaces'])) {
                                    $add_arguments['workspaces'] = '1';
                                }
                            }

                            if (!in_array($new_filename, $urls_db_files)) {
                                $process_file = $this_upload->addNew($add_arguments);
                            } else {
                                $process_file = $this_upload->saveExisting($add_arguments);
                            }

                            /**
                             * 1- Add the file to the database
                             */
                            if ($process_file['database'] == true) {
                                $add_arguments['new_file_id'] = $process_file['new_file_id'];
                                $add_arguments['all_users'] = $users;
                                $add_arguments['all_groups'] = $groups;
                                /**
                                 * 2- Add the assignments to the database
                                 */
                                $this_upload->addFileAssignment($add_arguments);

                                /**
                                 * 3- Add the assignments to the database
                                 */
                                $categories_arguments = array(
                                    'file_id' => $process_file['new_file_id'],
                                    'categories' => !empty($file['categories']) ? $file['categories'] : '',
                                );
                                $this_upload->setCategories($categories_arguments);

                                /**
                                 * 4- Add the notifications to the database
                                 */
                                if ($send_notifications == true) {
                                    $this_upload->addNotifications($add_arguments);
                                }
                                /**
                                 * 5- Mark is as correctly uploaded / assigned
                                 */
                                $upload_finish[$n] = array(
                                    'file_id' => $add_arguments['new_file_id'],
                                    'file' => $file['file'],
                                    'name' => htmlspecialchars($file['name']),
                                    'description' => htmlspecialchars($file['description']),
                                    'new_file_id' => $process_file['new_file_id'],
                                    'assignations' => $assignations_count,
                                    'public' => !empty($add_arguments['public']) ? $add_arguments['public'] : 0,
                                    'public_token' => !empty($process_file['public_token']) ? $process_file['public_token'] : null,
                                );
                                if (!empty($file['hidden'])) {
                                    $upload_finish[$n]['hidden'] = $file['hidden'];
                                }
                            }
                        }
                    }
                } else {
                    $empty_fields++;
                }
            }
        }

        /**
         * Generate the table of files that were assigned to a client
         * on this last POST. These files appear on this table only once,
         * so if there is another submition of the form, only the new
         * assigned files will be displayed.
         */
        if (!empty($upload_finish)) {
            ?>
            <h3><?php _e('Files uploaded correctly', 'cftp_admin'); ?></h3>
            <table id="uploaded_files_tbl" class="footable" data-page-size="<?php echo FOOTABLE_PAGING_NUMBER; ?>">
                <thead>
                <tr>
                    <th data-sort-initial="true"><?php _e('Title', 'cftp_admin'); ?></th>
                    <th data-hide="phone"><?php _e('Description', 'cftp_admin'); ?></th>
                    <th data-hide="phone"><?php _e('File Name', 'cftp_admin'); ?></th>
                    <?php
                    if (CURRENT_USER_LEVEL != 0) {
                        ?>
                        <th data-hide="phone"><?php _e("Status", 'cftp_admin'); ?></th>
                        <th data-hide="phone"><?php _e('Assignations', 'cftp_admin'); ?></th>
                        <th data-hide="phone"><?php _e('Public', 'cftp_admin'); ?></th>
                        <?php
                    } ?>
                    <th data-hide="phone" data-sort-ignore="true"><?php _e("Actions", 'cftp_admin'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php
                $all_public = true;
            foreach ($upload_finish as $uploaded) {
                ?>
                    <tr>
                        <td><?php echo html_output($uploaded['name']); ?></td>
                        <td><?php echo htmlentities_allowed($uploaded['description']); ?></td>
                        <td><?php echo html_output($uploaded['file']); ?></td>
                        <?php
                        if (CURRENT_USER_LEVEL != 0) {
                            ?>
                            <td class="<?php echo (!empty($uploaded['hidden'])) ? 'file_status_hidden' : 'file_status_visible'; ?>">

                                <?php
                                $status_hidden = __('Hidden', 'cftp_admin');
                            $status_visible = __('Visible', 'cftp_admin');
                            $class = (!empty($uploaded['hidden'])) ? 'danger' : 'success'; ?>
                                <span class="label label-<?php echo $class; ?>">
            <?php echo (!empty($hidden) && $hidden == 1) ? $status_hidden : $status_visible; ?>
                    </span>
                            </td>
                            <td>
                                <?php $class = ($uploaded['assignations'] > 0) ? 'success' : 'danger'; ?>
                                <span class="label label-<?php echo $class; ?>">
            <?php echo $uploaded['assignations']; ?>
                    </span>
                            </td>
                            <td class="col_visibility">
                                <?php
                                if ($uploaded['public'] == '1') {
                                    ?>
                                <a href="javascript:void(0);" class="btn btn-primary btn-sm public_link"
                                   data-type="file" data-id="<?php echo $uploaded['file_id']; ?>"
                                   data-token="<?php echo html_output($uploaded['public_token']); ?>">
                                    <?php
                                } else {
                                    ?>
                                    <a href="javascript:void(0);" class="btn btn-default btn-sm disabled" rel=""
                                       title="">
                                        <?php
                                }
                            $status_public = __('Public', 'cftp_admin');
                            $status_private = __('Private', 'cftp_admin');
                            if ($uploaded['public'] == 1) {
                                echo $status_public;
                            } else {
                                echo $status_private;
                                $all_public = false;
                            } ?>
                                    </a>
                            </td>
                            <?php
                        } ?>
                        <td>
                            <a href="edit-file.php?file_id=<?php echo html_output($uploaded['new_file_id']); ?>"
                               class="btn-primary btn btn-sm">
                                <i class="fa fa-pencil"></i><span
                                        class="button_label"><?php _e('Edit file', 'cftp_admin'); ?></span>
                            </a>
                            <?php
                            /*
                             * Show the "My files" button only to clients
                             */
                            if (CURRENT_USER_LEVEL == 0) {
                                ?>
                                <a href="<?php echo CLIENT_VIEW_FILE_LIST_URL; ?>"
                                   class="btn-primary btn btn-sm"><?php _e('View my files', 'cftp_admin'); ?></a>
                                <?php
                            } ?>
                        </td>
                    </tr>
                    <?php
            } ?>
                </tbody>
            </table>

        <?php
        /*
         * Show the "My files" button only to clients
         */
        if (CURRENT_USER_LEVEL > 0) {
            ?>
            <a href="javascript:void(0);" class="btn btn-default btn-sm public_links"
               data-name="<?php echo CURRENT_USER_NAME ?>" rel=""
               title=""><?php echo __('View public links', 'cftp_admin'); ?></a>
        <?php
        }
            if ($all_public && CURRENT_USER_LEVEL > 0) { ?>
            <script>
                $(document).ready(function () {
                    setTimeout(function () {
                        $('.public_links').click();
                    }, 100);
                });
            </script>
            <?php
        } ?>
            <?php
        }

        /**
         * Generate the table of files ready to be assigned to a client.
         */
        if (!empty($uploaded_files)) {
            ?>
            <h3><?php _e('Files ready to upload', 'cftp_admin'); ?></h3>
            <p><?php _e('Please complete the following information to finish the uploading process. Remember that "Title" is a required field.', 'cftp_admin'); ?></p>

            <?php
            if (CURRENT_USER_LEVEL != 0) {
                ?>
                <div class="message message_info">
                    <strong><?php _e('Note', 'cftp_admin'); ?></strong>: <?php _e('You can skip assigning if you want. The files are retained and you may add them to clients or groups later.', 'cftp_admin'); ?>
                </div>
                <?php
            }

            /**
             * First, do a server side validation for files that were submited
             * via the form, but the name field was left empty.
             */
            if (!empty($empty_fields)) {
                $msg = 'Name and client are required fields for all uploaded files.';
                echo system_message('danger', $msg);
            } ?>

            <form action="upload-process-form.php" name="files" id="files" method="post" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>"/>

                <?php
                foreach ($uploaded_files as $add_uploaded_field) {
                    ?>
                    <input type="hidden" name="finished_files[]" value="<?php echo $add_uploaded_field; ?>"/>
                    <?php
                } ?>

                <div class="container-fluid">
                    <?php
                    $i = 1;
            foreach ($uploaded_files

                    as $file) {
                clearstatcache();
                $this_upload = new ProjectSend\Classes\UploadFile;
                $file_original = $file;

                $location = UPLOADED_FILES_DIR . DS . $file;

                /**
                 * Check that the file is indeed present on the folder.
                 * If not, it is added to the failed files array.
                 */
                if (file_exists($location)) {
                    /**
                     * Remove the extension from the file name and replace every
                     * underscore with a space to generate a valid upload name.
                     */
                    $filename_no_ext = substr($file, 0, strrpos($file, '.'));
                    $file_title = str_replace('_', ' ', $filename_no_ext);
                    if ($this_upload->isFiletypeAllowed($file)) {
                        if (in_array($file, $urls_db_files)) {
                            $statement = $dbh->prepare("SELECT filename, description FROM " . TABLE_FILES . " WHERE url = :url");
                            $statement->bindParam(':url', $file);
                            $statement->execute();

                            while ($row = $statement->fetch()) {
                                $file_title = $row["filename"];
                                $description = $row["description"];
                            }
                        } ?>
                    <div class="file_editor <?php echo $i % 2 ? 'f_e_odd' : ''; ?>">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="file_number">
                                    <p><span class="glyphicon glyphicon-saved"
                                             aria-hidden="true"></span><?php echo html_output($file); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="row edit_files">
                            <div class="col-sm-12">
                                <div class="row edit_files_blocks">
                                    <div class="<?php /** @noinspection PhpUndefinedConstantInspection */
                                    echo ($global_level != 0 || CLIENTS_CAN_SET_EXPIRATION_DATE == '1') ? 'col-sm-6 col-md-3' : 'col-sm-12 col-md-12'; ?> column">
                                        <div class="file_data">
                                            <div class="row">
                                                <div class="col-sm-12">
                                                    <h3><?php _e('File information', 'cftp_admin'); ?></h3>
                                                    <input type="hidden" name="file[<?php echo $i; ?>][original]"
                                                           value="<?php echo html_output($file_original); ?>"/>
                                                    <input type="hidden" name="file[<?php echo $i; ?>][file]"
                                                           value="<?php echo html_output($file); ?>"/>

                                                    <div class="form-group">
                                                        <label for="file[<?php echo $i; ?>][name]"><?php _e('Title', 'cftp_admin'); ?></label>
                                                        <input type="text" id="file[<?php echo $i; ?>][name]"
                                                               name="file[<?php echo $i; ?>][name]"
                                                               value="<?php echo html_output($file_title); ?>"
                                                               class="form-control file_title"
                                                               placeholder="<?php _e('Enter here the required file title.', 'cftp_admin'); ?>"/>
                                                    </div>

                                                    <div class="form-group">
                                                        <label for="file[<?php echo $i; ?>][description]"><?php _e('Description', 'cftp_admin'); ?></label>
                                                        <textarea id="file[<?php echo $i; ?>][description]"
                                                                  name="file[<?php echo $i; ?>][description]"
                                                                  class="<?php /** @noinspection PhpUndefinedConstantInspection */
                                                                  echo FILES_DESCRIPTIONS_USE_CKEDITOR == 1 ? 'ckeditor' : ''; ?> form-control"
                                                                  placeholder="<?php _e('Optionally, enter here a description for the file.', 'cftp_admin'); ?>"><?php echo (isset($description)) ? html_output($description) : ''; ?></textarea>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <?php
                                    /**
                                     * The following options are available to users or client if clients_can_set_expiration_date set.
                                     */
                                    /** @noinspection PhpUndefinedConstantInspection */
                                    if ($global_level != 0 || CLIENTS_CAN_SET_EXPIRATION_DATE == '1') {
                                        ?>
                                    <?php if (CATEGORIES_ENABLED) { ?>
                                    <div class="col-sm-6 col-md-3 assigns column">
                                        <?php } else { ?>
                                        <div class="col-sm-6 col-md-4 assigns column">
                                            <?php } ?>
                                            <div class="file_data">
                                                <?php
                                                /**
                                                 * Only show the expiration options if the current
                                                 * uploader is a system user or client if clients_can_set_expiration_date is set.
                                                 */
                                                ?>
                                                <h3><?php _e('Expiration date', 'cftp_admin'); ?></h3>

                                                <div class="form-group">
                                                    <label for="file[<?php echo $i; ?>][expiry_date]"><?php _e('Select a date', 'cftp_admin'); ?></label>
                                                    <div class="input-group date-container">
                                                        <input type="text"
                                                               class="date-field form-control datapick-field" readonly
                                                               id="file[<?php echo $i; ?>][expiry_date]"
                                                               name="file[<?php echo $i; ?>][expiry_date]"
                                                               value="<?php echo (!empty($expiry_date)) ? $expiry_date : date('d-m-Y', strtotime('+7 day')); ?>"/>
                                                        <div class="input-group-addon">
                                                            <i class="glyphicon glyphicon-time"></i>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="checkbox form-group">
                                                    <label for="exp_checkbox_<?php echo $i; ?>">
                                                        <div class="input-group">
                                                            <input type="hidden" name="file[<?php echo $i; ?>][expires]"
                                                                   id="exp_checkbox_<?php echo $i; ?>"
                                                                   value="1" <?php echo CURRENT_USER_LEVEL == '9' || CURRENT_USER_LEVEL == '8' || CURRENT_USER_LEVEL == '7' || CURRENT_USER_LEVEL == '0' ? 'checked="checked"' : ''; ?> />
                                                        </div>
                                                    </label>
                                                </div>

                                                <?php
                                                /**
                                                 * The following options are available to users only
                                                 */
                                                if ($global_level != 0) {
                                                    ?>

                                                    <h3><?php _e('Workspaces', 'cftp_admin'); ?></h3>

                                                    <div class="checkbox form-group">
                                                        <label for="workspaces_checkbox_<?php echo $i; ?>">
                                                            <div class="input-group">
                                                                <input type="checkbox"
                                                                       id="workspaces_checkbox_<?php echo $i; ?>"
                                                                       name="file[<?php echo $i; ?>][workspaces]"
                                                                       value="1"
                                                                       checked="checked"/> <?php _e('Allow workspace downloading of this file.', 'cftp_admin'); ?>
                                                            </div>
                                                        </label>
                                                    </div>

                                                    <div class="divider"></div>

                                                    <h3><?php _e('Public downloading', 'cftp_admin'); ?></h3>

                                                    <div class="checkbox form-group">
                                                        <label for="pub_checkbox_<?php echo $i; ?>">
                                                            <div class="input-group">
                                                                <input type="checkbox"
                                                                       id="pub_checkbox_<?php echo $i; ?>"
                                                                       name="file[<?php echo $i; ?>][public]" value="1"
                                                                       checked="checked"/> <?php _e('Allow public downloading of this file.', 'cftp_admin'); ?>
                                                            </div>
                                                        </label>
                                                    </div>
                                                    <?php
                                                }
                                        /**
                                         * Close CURRENT_USER_LEVEL check
                                         */
                                                ?>
                                            </div>
                                        </div>
                                        <?php
                                    }
                        /**
                         * Close CURRENT_USER_LEVEL check
                         */ ?>

                                        <?php
                                        /**
                                         * The following options are available to users only
                                         */
                                        if ($global_level != 0) {
                                            ?>
                                        <?php if (CATEGORIES_ENABLED) { ?>
                                        <div class="col-sm-6 col-md-3 assigns column">
                                            <?php } else { ?>
                                            <div class="col-sm-6 col-md-4 assigns column">
                                                <?php } ?>
                                                <div class="file_data">
                                                    <?php
                                                    /**
                                                     * Only show the CLIENTS select field if the current
                                                     * uploader is a system user, and not a client.
                                                     */
                                                    ?>
                                                    <h3><?php _e('Assignations', 'cftp_admin'); ?></h3>
                                                    <label for="select_clients_<?php echo $i; ?>"><?php _e('Clients', 'cftp_admin'); ?>
                                                        :</label>
                                                    <select multiple="multiple"
                                                            name="file[<?php echo $i; ?>][assignments][clients][]"
                                                            id="select_clients_<?php echo $i; ?>"
                                                            class="form-control chosen-select select-clients"
                                                            data-placeholder="<?php _e('Select one or more options. Type to search.', 'cftp_admin'); ?>">
                                                        <?php
                                                        /**
                                                         * The clients list is generated early on the file so the
                                                         * array doesn't need to be made once on every file.
                                                         */
                                                        foreach ($clients as $client => $client_name) {
                                                            ?>
                                                            <option value="<?php echo $client; ?>">
                                                                <?php echo $client_name; ?>
                                                            </option>
                                                            <?php
                                                        } ?>
                                                    </select>
                                                    <div class="list_mass_members">
                                                        <a href="#" class="btn btn-xs btn-primary add-all"
                                                           data-type="clients"><?php _e('Add all', 'cftp_admin'); ?></a>
                                                        <a href="#" class="btn btn-xs btn-primary remove-all"
                                                           data-type="clients"><?php _e('Remove all', 'cftp_admin'); ?></a>
                                                        <a href="#" class="btn btn-xs btn-danger copy-all"
                                                           data-type="clients"><?php _e('Copy selections', 'cftp_admin'); ?></a>
                                                        <a href="#" class="btn btn-xs btn-primary create-client"
                                                           data-type="clients"><?php _e('Create client', 'cftp_admin'); ?></a>
                                                    </div>

                                                    <label for="select_groups_<?php echo $i; ?>"><?php _e('Groups', 'cftp_admin'); ?>
                                                        :</label>
                                                    <select multiple="multiple"
                                                            name="file[<?php echo $i; ?>][assignments][groups][]"
                                                            id="select_groups_<?php echo $i; ?>"
                                                            class="form-control chosen-select select-groups"
                                                            data-placeholder="<?php _e('Select one or more options. Type to search.', 'cftp_admin'); ?>">
                                                        <?php
                                                        /**
                                                         * The groups list is generated early on the file so the
                                                         * array doesn't need to be made once on every file.
                                                         */
                                                        foreach ($groups as $group => $group_name) {
                                                            ?>
                                                            <option value="<?php echo $group; ?>">
                                                                <?php echo $group_name; ?>
                                                            </option>
                                                            <?php
                                                        } ?>
                                                    </select>
                                                    <div class="list_mass_members">
                                                        <a href="#" class="btn btn-xs btn-primary add-all"
                                                           data-type="groups"><?php _e('Add all', 'cftp_admin'); ?></a>
                                                        <a href="#" class="btn btn-xs btn-primary remove-all"
                                                           data-type="groups"><?php _e('Remove all', 'cftp_admin'); ?></a>
                                                        <a href="#" class="btn btn-xs btn-danger copy-all"
                                                           data-type="groups"><?php _e('Copy selections', 'cftp_admin'); ?></a>
                                                        <a href="#" class="btn btn-xs btn-primary create-group"
                                                           data-type="groups"><?php _e('Create group', 'cftp_admin'); ?></a>
                                                    </div>

                                                    <div class="divider"></div>

                                                    <div class="checkbox">
                                                        <label for="hid_checkbox_<?php echo $i; ?>">
                                                            <input type="checkbox" id="hid_checkbox_<?php echo $i; ?>"
                                                                   name="file[<?php echo $i; ?>][hidden]"
                                                                   value="1"/> <?php _e('Upload hidden (will not send notifications)', 'cftp_admin'); ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php if (CATEGORIES_ENABLED) { ?>
                                                <div class="col-sm-6 col-md-3 categories column">
                                                    <div class="file_data">
                                                        <h3><?php _e('Categories', 'cftp_admin'); ?></h3>
                                                        <label for="file[<?php echo $i; ?>][categories][]"><?php _e('Add to', 'cftp_admin'); ?>
                                                            :</label>
                                                        <select multiple="multiple"
                                                                name="file[<?php echo $i; ?>][categories][]"
                                                                id="file[<?php echo $i; ?>][categories][]"
                                                                class="form-control chosen-select select-categories"
                                                                data-placeholder="<?php _e('Select one or more options. Type to search.', 'cftp_admin'); ?>">
                                                            <?php
                                                            /**
                                                             * The categories list is generated early on the file so the
                                                             * array doesn't need to be made once on every file.
                                                             */
                                                            echo generate_categories_options($get_categories['arranged'], 0);
                                                            ?>
                                                        </select>
                                                        <div class="list_mass_members">
                                                            <a href="#" class="btn btn-xs btn-primary add-all"
                                                               data-type="categories"><?php _e('Add all', 'cftp_admin'); ?></a>
                                                            <a href="#" class="btn btn-xs btn-primary remove-all"
                                                               data-type="categories"><?php _e('Remove all', 'cftp_admin'); ?></a>
                                                            <a href="#" class="btn btn-xs btn-danger copy-all"
                                                               data-type="categories"><?php _e('Copy selections', 'cftp_admin'); ?></a>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                            <?php
                                        }
                        /**
                         * Close CURRENT_USER_LEVEL check
                         */ ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                            $i++;
                    }
                } else {
                    $upload_failed[] = $file;
                }
            } ?>

                        </div> <!-- container -->

                        <?php
                        /**
                         * Take the list of failed files and store them as a text string
                         * that will be passed on a hidden field when posting the form.
                         */
                        $upload_failed = array_filter($upload_failed);
            $upload_failed_hidden = implode(',', $upload_failed); ?>
                        <input type="hidden" name="upload_failed" value="<?php echo $upload_failed_hidden; ?>"/>

                        <div class="after_form_buttons">
                            <button type="submit" name="submit" class="btn btn-wide btn-primary"
                                    id="upload-continue"><?php _e('Save', 'cftp_admin'); ?></button>
                        </div>
            </form>

            <?php
        } else { /* There are no more files to assign. Send the notifications */
            include_once INCLUDES_DIR . DS . 'upload-send-notifications.php';
        }

        /**
         * Generate the table for the failed files.
         */
        if (count($upload_failed) > 0) {
            ?>
            <h3><?php _e('Files not uploaded', 'cftp_admin'); ?></h3>
            <table id="failed_files_tbl" class="footable" data-page-size="<?php echo FOOTABLE_PAGING_NUMBER; ?>">
                <thead>
                <tr>
                    <th data-sort-initial="true"><?php _e('File Name', 'cftp_admin'); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($upload_failed as $failed) {
                    ?>
                    <tr>
                        <td><?php echo $failed; ?></td>
                    </tr>
                    <?php
                } ?>
                </tbody>
            </table>
            <?php
        }
        ?>
    </div>

<?php
require_once ADMIN_VIEWS_DIR . DS . 'footer.php';
