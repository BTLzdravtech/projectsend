<?php
/**
 * Show the list of current clients.
 *
 * @package		ProjectSend
 * @subpackage	Clients
 *
 */
$allowed_levels = array(9,8);
require_once 'bootstrap.php';

$active_nav = 'clients';

$page_title = __('Clients Administration','cftp_admin');
include_once ADMIN_VIEWS_DIR . DS . 'header.php';
?>

<div class="col-xs-12">
<?php
	/**
	 * Apply the corresponding action to the selected clients.
	 */
	if(isset($_GET['action'])) {
		/** Continue only if 1 or more clients were selected. */
		if(!empty($_GET['batch'])) {
			$selected_clients = $_GET['batch'];

			switch($_GET['action']) {
				case 'activate':
					/**
					 * Changes the value on the "active" column value on the database.
					 * Inactive clients are not allowed to log in.
					 */
					foreach ($selected_clients as $work_client) {
                        $this_client = new \ProjectSend\Classes\Users($dbh);
                        if ($this_client->get($work_client)) {
                            $hide_user = $this_client->setActiveStatus(1);
                        }
					}
                    
                    $msg = __('The selected clients were marked as active.','cftp_admin');
					echo system_message('success',$msg);
					break;
				case 'deactivate':
					/**
					 * Reverse of the previous action. Setting the value to 0 means
					 * that the client is inactive.
					 */
					foreach ($selected_clients as $work_client) {
                        $this_client = new \ProjectSend\Classes\Users($dbh);
                        if ($this_client->get($work_client)) {
                            $hide_user = $this_client->setActiveStatus(0);
                        }
					}
                    
                    $msg = __('The selected clients were marked as inactive.','cftp_admin');
					echo system_message('success',$msg);
					break;
				case 'delete':
					foreach ($selected_clients as $work_client) {
                        $this_client = new \ProjectSend\Classes\Users($dbh);
                        if ($this_client->get($work_client)) {
                            $delete_user = $this_client->delete();
                        }
					}
					
					$msg = __('The selected clients were deleted.','cftp_admin');
					echo system_message('success',$msg);
					break;
			}
		}
		else {
			$msg = __('Please select at least one client.','cftp_admin');
			echo system_message('danger',$msg);
		}
	}

	/** Query the clients */
	$params = array();

	$cq = "SELECT id FROM " . TABLE_USERS . " WHERE level='0' AND account_requested='0'";

	/** Add the search terms */	
	if ( isset( $_GET['search'] ) && !empty( $_GET['search'] ) ) {
		$cq .= " AND (name LIKE :name OR user LIKE :user OR address LIKE :address OR phone LIKE :phone OR email LIKE :email OR contact LIKE :contact)";
		$no_results_error = 'search';

		$search_terms		= '%'.$_GET['search'].'%';
		$params[':name']	= $search_terms;
		$params[':user']	= $search_terms;
		$params[':address']	= $search_terms;
		$params[':phone']	= $search_terms;
		$params[':email']	= $search_terms;
		$params[':contact']	= $search_terms;
	}

	/** Add the active filter */	
	if(isset($_GET['active']) && $_GET['active'] != '2') {
		$cq .= " AND active = :active";
		$no_results_error = 'filter';

		$params[':active']	= (int)$_GET['active'];
	}
	
	/**
	 * Add the order.
	 * Defaults to order by: name, order: ASC
	 */
	$cq .= sql_add_order( TABLE_USERS, 'name', 'asc' );

	/**
	 * Pre-query to count the total results
	*/
	$count_sql = $dbh->prepare( $cq );
	$count_sql->execute($params);
	$count_for_pagination = $count_sql->rowCount();

	/**
	 * Repeat the query but this time, limited by pagination
	 */
	$cq .= " LIMIT :limit_start, :limit_number";
	$sql = $dbh->prepare( $cq );

	$pagination_page			= ( isset( $_GET["page"] ) ) ? $_GET["page"] : 1;
	$pagination_start			= ( $pagination_page - 1 ) * RESULTS_PER_PAGE;
	$params[':limit_start']		= $pagination_start;
	$params[':limit_number']	= RESULTS_PER_PAGE;

	$sql->execute( $params );
	$count = $sql->rowCount();
?>
		<div class="form_actions_left">
			<div class="form_actions_limit_results">
				<?php show_search_form('clients.php'); ?>

				<form action="clients.php" name="clients_filters" method="get" class="form-inline">
					<?php form_add_existing_parameters( array('active', 'action') ); ?>
					<div class="form-group group_float">
						<select name="active" id="active" class="txtfield form-control">
							<?php
								$status_options = array(
														'2'		=> __('All statuses','cftp_admin'),
														'1'		=> __('Active','cftp_admin'),
														'0'		=> __('Inactive','cftp_admin'),
													);
								foreach ( $status_options as $val => $text ) {
							?>
									<option value="<?php echo $val; ?>" <?php if ( isset( $_GET['active'] ) && $_GET['active'] == $val ) { echo 'selected="selected"'; } ?>><?php echo $text; ?></option>
							<?php
								}
							?>
						</select>
					</div>
					<button type="submit" id="btn_proceed_filter_clients" class="btn btn-sm btn-default"><?php _e('Filter','cftp_admin'); ?></button>
				</form>
			</div>
		</div>

		<form action="clients.php" name="clients_list" method="get" class="form-inline batch_actions">
			<?php form_add_existing_parameters(); ?>
			<div class="form_actions_right">
				<div class="form_actions">
					<div class="form_actions_submit">
						<div class="form-group group_float">
							<label class="control-label hidden-xs hidden-sm"><i class="glyphicon glyphicon-check"></i> <?php _e('Selected clients actions','cftp_admin'); ?>:</label>
							<select name="action" id="action" class="txtfield form-control">
								<?php
									$actions_options = array(
															'none'			=> __('Select action','cftp_admin'),
															'activate'		=> __('Activate','cftp_admin'),
															'deactivate'	=> __('Deactivate','cftp_admin'),
															'delete'		=> __('Delete','cftp_admin'),
														);
									foreach ( $actions_options as $val => $text ) {
								?>
										<option value="<?php echo $val; ?>"><?php echo $text; ?></option>
								<?php
									}
								?>
							</select>
						</div>
						<button type="submit" id="do_action" class="btn btn-sm btn-default"><?php _e('Proceed','cftp_admin'); ?></button>
					</div>
				</div>
			</div>
			<div class="clear"></div>

			<div class="form_actions_count">
				<p><?php _e('Found','cftp_admin'); ?>: <span><?php echo $count_for_pagination; ?> <?php _e('clients','cftp_admin'); ?></span></p>
			</div>

			<div class="clear"></div>

			<?php
				if (!$count) {
					if (isset($no_results_error)) {
						switch ($no_results_error) {
							case 'search':
								$no_results_message = __('Your search keywords returned no results.','cftp_admin');
								break;
							case 'filter':
								$no_results_message = __('The filters you selected returned no results.','cftp_admin');
								break;
						}
					}
					else {
						$no_results_message = __('There are no clients at the moment','cftp_admin');
					}
					echo system_message('danger',$no_results_message);
				}

				if ($count > 0) {
					/**
					 * Generate the table using the class.
					 */
					$table_attributes	= array(
												'id'		=> 'clients_tbl',
												'class'		=> 'footable table',
											);
					$table = new \ProjectSend\Classes\TableGenerate( $table_attributes );
	
					$thead_columns		= array(
												array(
													'select_all'	=> true,
													'attributes'	=> array(
																			'class'		=> array( 'td_checkbox' ),
																		),
												),
												array(
													'sortable'		=> true,
													'sort_url'		=> 'name',
													'sort_default'	=> true,
													'content'		=> __('Full name','cftp_admin'),
												),
												array(
													'sortable'		=> true,
													'sort_url'		=> 'user',
													'content'		=> __('Log in username','cftp_admin'),
													'hide'			=> 'phone,tablet',
												),
												array(
													'sortable'		=> true,
													'sort_url'		=> 'email',
													'content'		=> __('E-mail','cftp_admin'),
													'hide'			=> 'phone,tablet',
												),
												array(
													'content'		=> __('Uploads','cftp_admin'),
													'hide'			=> 'phone',
												),
												array(
													'content'		=> __('Files: Own','cftp_admin'),
													'hide'			=> 'phone',
												),
												array(
													'content'		=> __('Files: Groups','cftp_admin'),
													'hide'			=> 'phone',
												),
												array(
													'sortable'		=> true,
													'sort_url'		=> 'active',
													'content'		=> __('Status','cftp_admin'),
												),
												array(
													'content'		=> __('Groups on','cftp_admin'),
													'hide'			=> 'phone',
												),
												array(
													'content'		=> __('Notify','cftp_admin'),
													'hide'			=> 'phone,tablet',
												),
												array(
													'sortable'		=> true,
													'sort_url'		=> 'max_file_size',
													'content'		=> __('Max. upload size','cftp_admin'),
													'hide'			=> 'phone',
												),
												array(
													'sortable'		=> true,
													'sort_url'		=> 'timestamp',
													'content'		=> __('Added on','cftp_admin'),
													'hide'			=> 'phone,tablet',
												),
												array(
													'content'		=> __('View','cftp_admin'),
													'hide'			=> 'phone',
												),
												array(
													'content'		=> __('Actions','cftp_admin'),
													'hide'			=> 'phone',
												),
											);
					$table->thead( $thead_columns );
	
					$sql->setFetchMode(PDO::FETCH_ASSOC);
					while ( $row = $sql->fetch() ) {
                        $table->addRow();
                        
                        $client_object = new \ProjectSend\Classes\Users($dbh);
                        $client_object->get($row["id"]);
                        $client_data = $client_object->getProperties();

						$count_groups = count($client_data['groups']);
						
                        /* Get account creation date */
                        $created_at = format_date($client_data['created_date']);

                        /* Count uploads */
                        $count_files = (!empty($client_data['files'])) ? count($client_data['files']) : 0;
						
						/* Count OWN and GROUP files */
						$own_files = 0;
						$groups_files = 0;

						$found_groups = ($count_groups > 0) ? implode( ',', $client_data['groups'] ) : '';
                        $files_query = "SELECT DISTINCT id, file_id, client_id, group_id FROM " . TABLE_FILES_RELATIONS . " WHERE client_id=:id";
						if ( !empty( $found_groups ) ) {
							$files_query .= " OR FIND_IN_SET(group_id, :group_id)";
						}
						$sql_files = $dbh->prepare( $files_query );
						$sql_files->bindParam(':id', $client_data['id'], PDO::PARAM_INT);
						if ( !empty( $found_groups ) ) {
							$sql_files->bindParam(':group_id', $found_groups);
						}

						$sql_files->execute();
						$sql_files->setFetchMode(PDO::FETCH_ASSOC);
						while ( $row_files = $sql_files->fetch() ) {
							if (!is_null($row_files['client_id'])) {
								$own_files++;
							}
							else {
								$groups_files++;
							}
						}

						/* Get active status */
						$label = ($client_data['active'] == 0) ? __('Inactive','cftp_admin') : __('Active','cftp_admin');
						$class = ($client_data['active'] == 0) ? 'danger' : 'success';
												
						/* Actions buttons */
						if ($own_files + $groups_files > 0) {
							$files_link		= 'manage-files.php?client_id='.$client_data["id"];
							$files_button	= 'btn-primary';
						}
						else {
							$files_link		= 'javascript:void(0);';
							$files_button	= 'btn-default disabled';
						}

						if ($count_groups > 0) {
							$groups_link	= 'groups.php?member='.$client_data["id"];
							$groups_button	= 'btn-primary';
						}
						else {
							$groups_link	= 'javascript:void(0);';
							$groups_button	= 'btn-default disabled';
						}
						
						/**
						 * Add the cells to the row
						 */
						$tbody_cells = array(
												array(
														'checkbox'		=> true,
														'value'			=> $client_data["id"],
													),
												array(
														'content'		=> $client_data["name"],
													),
												array(
														'content'		=> $client_data["username"],
													),
												array(
														'content'		=> $client_data["email"],
													),
												array(
														'content'		=> $count_files,
													),
												array(
														'content'		=> $own_files,
													),
												array(
														'content'		=> $groups_files,
													),
												array(
														'content'		=> '<span class="label label-' . $class . '">' . $label . '</span>',
													),
												array(
														'content'		=> $count_groups,
													),
												array(
														'content'		=> ( $client_data["notify_upload"] == '1' ) ? __('Yes','cftp_admin') : __('No','cftp_admin'),
													),
												array(
														'content'		=> ( $client_data["max_file_size"] == '0' ) ? __('Default','cftp_admin') : $client_data["max_file_size"] . 'mb',
													),
												array(
														'content'		=> $created_at,
													),
												array(
														'actions'		=> true,
														'content'		=>  '<a href="' . $files_link . '" class="btn btn-sm ' . $files_button . '">' . __("Files","cftp_admin") . '</a>' . "\n" .
																			'<a href="' . $groups_link . '" class="btn btn-sm ' . $groups_button . '">' . __("Groups","cftp_admin") . '</a>' . "\n" .
																			'<a href="' . CLIENT_VIEW_FILE_LIST_URL . '?client=' . html_output( $client_data["username"] ) . '" class="btn btn-primary btn-sm" target="_blank">' . __('As client','cftp_admin') . '</a>' . "\n"
													),
												array(
														'actions'		=> true,
														'content'		=>  '<a href="clients-edit.php?id=' . $client_data["id"] . '" class="btn btn-primary btn-sm"><i class="fa fa-pencil"></i><span class="button_label">' . __('Edit','cftp_admin') . '</span></a>' . "\n"
													),
											);

						foreach ( $tbody_cells as $cell ) {
							$table->addCell( $cell );
						}
		
						$table->end_row();
					}

					echo $table->render();
	
					/**
					 * PAGINATION
					 */
					$pagination_args = array(
											'link'		=> 'clients.php',
											'current'	=> $pagination_page,
											'pages'		=> ceil( $count_for_pagination / RESULTS_PER_PAGE ),
										);
					
					echo $table->pagination( $pagination_args );
				}
			?>
		</form>
	</div>
</div>

<?php
	include_once ADMIN_VIEWS_DIR . DS . 'footer.php';