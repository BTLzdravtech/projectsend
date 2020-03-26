<?php
/**
 * Home page for logged in system users.
 *
 * @package ProjectSend
 */
$allowed_levels = array(9, 8, 7);
require_once 'bootstrap.php';
$page_title = __('Dashboard', 'cftp_admin');

$active_nav = 'dashboard';

$body_class = array('dashboard', 'home', 'hide_title');
$page_id = 'dashboard';

require_once ADMIN_VIEWS_DIR . DS . 'header.php';

define('CAN_INCLUDE_FILES', true);

$log_allowed = array(9);

$show_log = false;
$sys_info = false;
$disk_info = false;

if (current_role_in($log_allowed)) {
    $show_log = true;
    $sys_info = true;
    $disk_info = true;

    /**
     * Get the size of all files
     */
    $dir = new DirectoryIterator(UPLOADED_FILES_DIR);
    $total_file_size = 0;
    foreach ($dir as $fileinfo) {
        if ($fileinfo->isFile()) {
            $total_file_size += get_real_size($fileinfo->getPathname());
        }
    }
}

?>
    <div class="col-sm-8">
        <?php
        $msg = __('Please be informed that all your files will be deleted after their expiration.', 'cftp_admin');
        echo system_message('danger', $msg);
        ?>
        <div class="row">
            <div class="col-sm-12 container_widget_statistics">
                <?php require_once WIDGETS_FOLDER . 'statistics.php'; ?>
            </div>
        </div>
        <?php
        if ($sys_info == true) {
            ?>
            <div class="row">
                <div class="col-sm-6">
                    <?php include_once WIDGETS_FOLDER . 'disk.php'; ?>
                    <?php include_once WIDGETS_FOLDER . 'news.php'; ?>
                </div>
                <div class="col-sm-6">
                    <?php include_once WIDGETS_FOLDER . 'system-information.php'; ?>
                </div>
            </div>
            <?php
        } ?>
    </div>

<?php
if ($show_log == true) {
            ?>
    <div class="col-sm-4 container_widget_actions_log">
        <?php include_once WIDGETS_FOLDER . 'actions-log.php'; ?>
    </div>
    <?php
        }
?>

<?php
require_once ADMIN_VIEWS_DIR . DS . 'footer.php';
