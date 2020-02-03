<div class="widget">
    <h4><?php _e('Used Disk Space', 'cftp_admin'); ?>: <?php
        global $total_file_size;
        echo html_output(format_file_size($total_file_size));
    ?></h4>
</div>
