<?php
/**
 * Find and delete expired files
 *
 * @package ProjectSend
 */
require_once 'bootstrap.php';
$file_ids = get_expired_file_ids();

$delete_results	= array(
    'ok'		=> 0,
    'errors'	=> 0,
);

foreach ($file_ids as $file_id) {
    $this_file		= new ProjectSend\Classes\FilesActions;
    $delete_status	= $this_file->deleteFiles($file_id, true);

    if ( $delete_status == true ) {
        $delete_results['ok']++;
    }
    else {
        $delete_results['errors']++;
    }
}

if ( $delete_results['ok'] > 0 ) {
    $msg = __('The selected files were deleted.','cftp_admin');
    echo system_message('success',$msg);
}
if ( $delete_results['errors'] > 0 ) {
    $msg = __('Some files could not be deleted.','cftp_admin');
    echo system_message('danger',$msg);
}
