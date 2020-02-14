<?php
/**
 * Find and delete expired files
 *
 * @package ProjectSend
 */
require_once 'bootstrap.php';
$file_ids = get_expired_file_ids();

foreach ($file_ids as $file_id) {
    $this_file        = new ProjectSend\Classes\FilesActions;
    $delete_status    = $this_file->deleteFiles($file_id, true);
}
