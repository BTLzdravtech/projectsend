<?php
/**
 * ProjectSend (previously cFTP) is a free, clients-oriented, private file
 * sharing web application.
 * Clients are created and assigned a username and a password. Then you can
 * upload as much files as you want under each account, and optionally add
 * a name and description to them. 
 *
 * ProjectSend is hosted on Google Code.
 * Feel free to participate!
 *
 * @link		http://code.google.com/p/clients-oriented-ftp/
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GNU GPL version 2
 * @package		ProjectSend
 *
 */
$allowed_levels = array(9,8,7,0);
require_once 'bootstrap.php';

$page_title = __('Log in','cftp_admin');

$body_class = array('login');

include_once ADMIN_VIEWS_DIR . DS . 'header-unlogged.php';

$login_button_text = __('Log in','cftp_admin');
	
	/**
	 * Google Sign-in
	 */
	if ( GOOGLE_SIGNIN_ENABLED == '1' ) {
		$googleClient = new Google_Client();
		$googleClient->setApplicationName(THIS_INSTALL_TITLE);
		$googleClient->setClientSecret(GOOGLE_CLIENT_SECRET);
		$googleClient->setClientId(GOOGLE_CLIENT_ID);
		$googleClient->setAccessType('online');
		$googleClient->setApprovalPrompt('auto');
		$googleClient->setRedirectUri(BASE_URI . 'sociallogin/google/callback.php');
		$googleClient->setScopes(array('profile','email'));
		$auth_url = $googleClient->createAuthUrl();
	}
	

	if ( isset($_SESSION['errorstate'] ) ) {
		$errorstate = $_SESSION['errorstate'];
		unset($_SESSION['errorstate']);
	}
?>
<div class="col-xs-12 col-sm-12 col-lg-4 col-lg-offset-4">

	<?php echo get_branding_layout(true); ?>

	<div class="white-box">
		<div class="white-box-interior">
			<div class="ajax_response">
				<?php
					/** Coming from an external form */
					if ( isset( $_GET['error'] ) ) {
						switch ( $_GET['error'] ) {
							case 1:
								echo system_message('danger',__("The supplied credentials are not valid.",'cftp_admin'),'login_error');
								break;
							case 'timeout':
								echo system_message('danger',__("Session timed out. Please log in again.",'cftp_admin'),'login_error');
								break;
						}
					}
				?>
			</div>
			<script type="text/javascript">
				$(document).ready(function() {
					$("#login_form").submit(function(e) {
						e.preventDefault();
						e.stopImmediatePropagation();
						$('.ajax_response').html();
						clean_form(this);
		
						is_complete(this.username,'<?php echo addslashes(__('Username was not completed','cftp_admin')); ?>');
						is_complete(this.password,'<?php echo addslashes(__('Password was not completed','cftp_admin')); ?>');
		
						// show the errors or continue if everything is ok
						if (show_form_errors() == false) {
							return false;
						}
						else {
							var url = $(this).attr('action');
							$('.ajax_response').html('');
							$('#submit').html('<i class="fa fa-cog fa-spin fa-fw"></i><span class="sr-only"></span> <?php echo addslashes(__('Logging in','cftp_admin')); ?>...');
							$.ajax({
									cache: false,
									type: "get",
									url: url,
									data: $(this).serialize(), // serializes the form's elements.
									success: function(response)
									{
										var json = jQuery.parseJSON(response);
										if ( json.status == 'success' ) {
											//$('.ajax_response').html(json.message);
											$('#submit').html('<i class="fa fa-check"></i><span class="sr-only"></span> <?php echo addslashes(__('Redirecting','cftp_admin')); ?>...');
											$('#submit').removeClass('btn-primary').addClass('btn-success');
											setTimeout('window.location.href = "'+json.location+'"', 1000);
										}
										else {
											$('.ajax_response').html(json.message);
											$('#submit').html('<?php echo $login_button_text; ?>');
										}
									}
							});
							return false;
						}
					});
				});
			</script>
		
            <?php include_once FORMS_DIR . DS . 'login.php'; ?>

			<div class="login_form_links">
				<p id="reset_pass_link"><?php _e("Forgot your password?",'cftp_admin'); ?> <a href="<?php echo BASE_URI; ?>reset-password.php"><?php _e('Set up a new one.','cftp_admin'); ?></a></p>
				<?php
					if (CLIENTS_CAN_REGISTER == '1') {
				?>
						<p id="register_link"><?php _e("Don't have an account yet?",'cftp_admin'); ?> <a href="<?php echo BASE_URI; ?>register.php"><?php _e('Register as a new client.','cftp_admin'); ?></a></p>
				<?php
					} else {
				?>
						<p><?php _e("This server does not allow self registrations.",'cftp_admin'); ?></p>
						<p><?php _e("If you need an account, please contact a server administrator.",'cftp_admin'); ?></p>
				<?php
					}
				?>
			</div>

		</div>
	</div>
</div>

<?php
	include_once ADMIN_VIEWS_DIR . DS . 'footer.php';