<?php
	if ( isset( $_GET['ajax_call'] ) ) {
		require_once('../../bootstrap.php');
	}

	$allowed_news = array(9,8,7);
	if (in_array(CURRENT_USER_LEVEL,$allowed_news)) {
?>
		<div class="widget">
			<h4><?php _e('ProjectSend news','cftp_admin'); ?></h4>
			<div class="widget_int">
                <?php if ( function_exists( 'simplexml_load_file' ) ) { ?>
                    <ul class="home_news">
                        <?php
                            $feed = simplexml_load_file(NEWS_FEED_URI);
                            $max_news = 3;
                            $n = 0;
                            foreach ($feed->channel->item as $item) {
                                if ($n < $max_news) {
                            ?>
                                    <li>
                                        <span class="date"><?php echo date(TIMEFORMAT,strtotime($item->pubDate)); ?></span>
                                        <a href="<?php echo html_output($item->link); ?>" target="_blank">
                                            <h5><?php echo html_output($item->title); ?></h5>
                                        </a>
                                        <p><?php echo make_excerpt(html_output(strip_tags($item->description, '<br />')),200); ?>
                                    </li>
                            <?php
                                    $n++;
                                }
                            }
                        ?>
                    </ul>
                <?php } else { ?>
                    <div class="alert alert-warning">
 						<?php echo sprintf(__('News cannot be loaded. Please enable the %s php module to fix this issue.', 'cftp_admin'), 'simplexml_load_file'); ?>
 					</div>
                <?php } ?>
            </div>
		</div>
<?php
	}