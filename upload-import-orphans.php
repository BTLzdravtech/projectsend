<?php
/**
 * Shows a list of files found on the upload/ folder that
 * are not yet on the database, meaning they were uploaded
 * via FTP.
 * Only shows files that are allowed according to the sytem
 * settings.
 * Submits an array of file names.
 *
 * @package    ProjectSend
 * @subpackage Upload
 */

use ProjectSend\Classes\TableGenerate;

$allowed_levels = array(9, 8, 7);
require_once 'bootstrap.php';

global $dbh;

$active_nav = 'files';

$page_title = __('Find orphan files', 'cftp_admin');

$page_id = 'import_orphans';

require_once ADMIN_VIEWS_DIR . DS . 'header.php';

if (false === CAN_UPLOAD_ANY_FILE_TYPE) {
    $msg = __('This list only shows the files that are allowed according to your security settings. If the file type you need to add is not listed here, add the extension to the "Allowed file extensions" box on the options page.', 'cftp_admin');
    echo system_message('warning', $msg);
}

/**
 * Count clients to show an error message, or the form
 */
$statement = $dbh->query("SELECT id FROM " . TABLE_USERS . " WHERE level = '0'");
$count_clients = $statement->rowCount();
$statement = $dbh->query("SELECT id FROM " . TABLE_GROUPS);
$count_groups = $statement->rowCount();

if ((!$count_clients or $count_clients < 1) && (!$count_groups or $count_groups < 1)) {
    message_no_clients();
}

/**
 * Make a list of existing files on the database.
 * When a file doesn't correspond to a record, it can
 * be safely renamed.
 */
$sql = $dbh->query("SELECT original_url, url, id, public_allow FROM " . TABLE_FILES);
$db_files = array();
$sql->setFetchMode(PDO::FETCH_ASSOC);
while ($row = $sql->fetch()) {
    $db_files[$row["url"]] = $row["id"];
    $db_files[$row["original_url"]] = $row["id"];
    if ($row['public_allow'] == 1) {
        $db_files_public[$row["url"]] = $row["id"];
    }
}

/**
 * Make an array of already assigned files
 */
$sql = $dbh->query("SELECT DISTINCT file_id FROM " . TABLE_FILES_RELATIONS . " WHERE client_id IS NOT NULL OR group_id IS NOT NULL OR folder_id IS NOT NULL");
$assigned = array();
$sql->setFetchMode(PDO::FETCH_ASSOC);
while ($row = $sql->fetch()) {
    $assigned[] = $row["file_id"];
}

/**
 * We consider public file as assigned file
 */
if (!empty($db_files_public)) {
    foreach ($db_files_public as $file_id) {
        $assigned[] = $file_id;
    }
}

$files_to_add = array();

/**
 * Read the temp folder and list every allowed file
 */
if ($handle = opendir(UPLOADED_FILES_DIR)) {
    while (false !== ($filename = readdir($handle))) {
        $filename_path = UPLOADED_FILES_DIR . DS . $filename;
        if (!is_dir($filename_path)) {
            $ignore = [
                ".",
                "..",
                ".htaccess",
                "index.php"
            ];
            if (!in_array($filename, $ignore)) {
                /**
                 * Check types of files that are not on the database
                 */
                if (!array_key_exists($filename, $db_files)) {
                    $file_object = new ProjectSend\Classes\UploadFile;
                    $new_filename = $file_object->safeRenameOnDisk($filename, UPLOADED_FILES_DIR);
                    /**
                     * Check if the filetype is allowed
                     */
                    if ($file_object->isFiletypeAllowed($new_filename)) {

                        /**
                         * Add it to the array of available files
                         */
                        $new_filename_path = UPLOADED_FILES_DIR . DS . $new_filename;
                        //$files_to_add[$new_filename] = $new_filename_path;
                        $files_to_add[] = array(
                            'path' => $new_filename_path,
                            'name' => $new_filename,
                            'reason' => 'not_on_db',
                        );
                    }
                }
            }
        }
    }
    closedir($handle);
}

if (!empty($_GET['search'])) {
    $no_results_error = 'search';
    $search = htmlspecialchars($_GET['search']);

    function search_text($item)
    {
        global $search;
        if (stripos($item['name'], $search) !== false) {
            /**
             * Items that match the search
             */
            return true;
        } else {
            /**
             * Remove other items
             */
            unset($item);
        }
        return false;
    }

    $files_to_add = array_filter($files_to_add, 'search_text');
}

// var_dump($result);
?>
    <div class="col-xs-12">
        <div class="form_actions_limit_results">
            <?php show_search_form('upload-import-orphans.php'); ?>
        </div>

        <div class="form_actions_count">
            <p class="form_count_total"><?php _e('Showing', 'cftp_admin'); ?>:
                <span><?php echo count($files_to_add); ?><?php _e('files', 'cftp_admin'); ?></span></p>
        </div>


        <form action="upload-process-form.php" name="import_orphans" id="import_orphans" method="post"
              enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo getCsrfToken(); ?>"/>

            <?php
            /**
             * Generate the list of files if there is at least 1
             * available and allowed.
             */
            if (isset($files_to_add) && count($files_to_add) > 0) {
                $table_attributes = array(
                    'id' => 'import_orphans_table',
                    'class' => 'footable table',
                    'data-page-size' => FOOTABLE_PAGING_NUMBER,
                );
                $table = new TableGenerate($table_attributes);

                $thead_columns = array(
                    array(
                        'select_all' => true,
                        'attributes' => array(
                            'class' => array('td_checkbox'),
                            'data-sortable' => 'false',
                        ),
                    ),
                    array(
                        'content' => __('File name', 'cftp_admin'),
                        'attributes' => array(
                            'data-sort-initial' => 'true',
                        ),
                    ),
                    array(
                        'content' => __('File size', 'cftp_admin'),
                        'hide' => 'phone',
                        'attributes' => array(
                            'data-type' => 'numeric',
                        ),
                    ),
                    array(
                        'content' => __('Last modified', 'cftp_admin'),
                        'hide' => 'phone',
                        'attributes' => array(
                            'data-type' => 'numeric',
                        ),
                    ),
                    array(
                        'content' => __('Actions', 'cftp_admin'),
                    ),
                );
                $table->thead($thead_columns);

                foreach ($files_to_add as $add_file) {
                    $table->addRow();
                    /**
                     * Add the cells to the row
                     */
                    /** @noinspection PhpUndefinedConstantInspection */
                    $tbody_cells = array(
                        array(
                            'content' => '<input type="checkbox" name="add[]" class="batch_checkbox select_file_checkbox" value="' . html_output($add_file['name']) . '" />',
                        ),
                        array(
                            'content' => html_output($add_file['name']),
                        ),
                        array(
                            'content' => html_output(format_file_size(get_real_size($add_file['path']))),
                            'attributes' => array(
                                'data-value' => filesize($add_file['path']),
                            ),
                        ),
                        array(
                            'content' => date(TIMEFORMAT, filemtime($add_file['path'])),
                            'attributes' => array(
                                'data-value' => filemtime($add_file['path']),
                            ),
                        ),
                        array(
                            'actions' => true,
                            'content' => '<button type="button" name="file_edit" class="btn btn-primary btn-sm btn-edit-file"><i class="fa fa-upload"></i><span class="button_label">' . __('Edit', 'cftp_admin') . '</span></button>' . "\n"
                        ),
                    );

                    foreach ($tbody_cells as $cell) {
                        $table->addCell($cell);
                    }

                    $table->end_row();
                }

                echo $table->render(); ?>
                <nav aria-label="<?php _e('Results navigation', 'cftp_admin'); ?>">
                    <div class="pagination_wrapper text-center">
                        <ul class="pagination hide-if-no-paging"></ul>
                    </div>
                </nav>
                <?php
                $msg = __('Please note that the listed files will be renamed if they contain invalid characters.', 'cftp_admin');
                echo system_message('info', $msg);

                $msg = __('Please select at least one checkbox.', 'cftp_admin');
                echo system_message('danger', $msg); ?>
                <div class="after_form_buttons">
                    <button type="submit" class="btn btn-wide btn-primary"
                            id="upload-continue"><?php _e('Continue', 'cftp_admin'); ?></button>
                </div>
                <?php
            } else { /* No files found */
                if (isset($no_results_error)) {
                    switch ($no_results_error) {
                        case 'search':
                            $no_results_message = __('Your search keywords returned no results.', 'cftp_admin');
                            break;
                    }
                } else {
                    $no_results_message = __('There are no files available to add right now.', 'cftp_admin');
                    $no_results_message .= __('To use this feature you need to upload your files via FTP to the folder', 'cftp_admin');
                    $no_results_message .= ' <span class="format_url"><strong>' . html_output(UPLOADED_FILES_DIR) . '</strong></span>.';
                }

                echo system_message('danger', $no_results_message);
            }
            ?>
        </form>
    </div>

<?php
require_once ADMIN_VIEWS_DIR . DS . 'footer.php';
