<?php
/**
 * Class that handles all the actions and functions regarding workspaces memberships.
 *
 * @package		ProjectSend
 * @subpackage	Classes
 */

namespace ProjectSend\Classes;
use \PDO;

class WorkspacesUsers
{
    private $dbh;
    private $logger;

	var $user	= '';
	var $workspaces	= '';

    public function __construct(PDO $dbh = null)
    {
        if (empty($dbh)) {
            global $dbh;
        }

        $this->dbh = $dbh;
        $this->logger = new ActionsLog;
    }

	function workspace_add_users($arguments)
	{
		$this->user_ids	= is_array( $arguments['user_id'] ) ? $arguments['user_id'] : array( $arguments['user_id'] );
		$this->workspace_id		= $arguments['workspace_id'];
		$this->added_by		= $arguments['added_by'];

		$this->results 		= array(
									'added'		=> 0,
									'queue'		=> count( $this->workspace_ids ),
									'errors'	=> array(),
								);

		foreach ( $this->user_ids as $this->user_id ) {
			$statement = $this->dbh->prepare("INSERT INTO " . TABLE_WORKSPACES_USERS . " (added_by,user_id,workspace_id)"
												." VALUES (:added_by, :id, :workspace)");
			$statement->bindParam(':added_by', $this->added_by);
			$statement->bindParam(':id', $this->user_id, PDO::PARAM_INT);
			$statement->bindParam(':workspace', $this->workspace_id, PDO::PARAM_INT);
			$this->status = $statement->execute();
			
			if ( $this->status ) {
				$this->results['added']++;
			}
			else {
				$this->results['errors'][] = array(
													'user'	=> $this->user_id,
												);
			}
		}
		
		return $this->results;
	}

	function workspace_remove_users($arguments)
	{
		$this->user_ids	= is_array( $arguments['user_id'] ) ? $arguments['user_id'] : array( $arguments['user_id'] );
		$this->workspace_id		= $arguments['workspace_id'];

		$this->results 		= array(
									'removed'	=> 0,
									'queue'		=> count( $this->user_ids ),
									'errors'	=> array(),
								);

		foreach ( $this->user_ids as $this->user_id ) {
			$statement = $this->dbh->prepare("DELETE FROM " . TABLE_WORKSPACES_USERS . " WHERE user_id = :user AND workspace_id = :workspace");
			$statement->bindParam(':user', $this->user_id, PDO::PARAM_INT);
			$statement->bindParam(':workspace_id', $this->workspace_id, PDO::PARAM_INT);
			$this->status = $statement->execute();
			
			if ( $this->status ) {
				$this->results['removed']++;
			}
			else {
				$this->results['errors'][] = array(
													'user'	=> $this->user_id,
												);
			}
		}
		
		return $this->results;
	}

	function user_get_workspaces($arguments)
	{
		$this->user_id	= $arguments['user_id'];
		$this->return_type	= !empty( $arguments['return'] ) ? $arguments['return'] : 'array';

		$this->found_workspaces = array();
		$this->sql_workspaces = $this->dbh->prepare("SELECT DISTINCT workspace_id FROM " . TABLE_WORKSPACES_USERS . " WHERE user_id=:id");
		$this->sql_workspaces->bindParam(':id', $this->user_id, PDO::PARAM_INT);
		$this->sql_workspaces->execute();
		$this->count_workspaces = $this->sql_workspaces->rowCount();
	
		if ($this->count_workspaces > 0) {
			$this->sql_workspaces->setFetchMode(PDO::FETCH_ASSOC);
			while ( $this->row_workspaces = $this->sql_workspaces->fetch() ) {
				$this->found_workspaces[] = $this->row_workspaces["workspace_id"];
			}
		}
		
		switch ( $this->return_type ) {
			case 'array':
					$this->results = $this->found_workspaces;
				break;
			case 'list':
					$this->results = implode(',', $this->found_workspaces);
				break;
		}
		
		return $this->results;
	}

	function user_add_to_workspaces($arguments)
	{
		$this->user_id	= $arguments['user_id'];
		$this->workspace_ids	= is_array( $arguments['workspace_ids'] ) ? $arguments['workspace_ids'] : array( $arguments['workspace_ids'] );
		$this->added_by		= $arguments['added_by'];
		
		if ( in_array( CURRENT_USER_LEVEL, array(9,8) ) || ( defined('AUTOGROUP') ) ) {
			$this->results 		= array(
										'added'		=> 0,
										'queue'		=> count( $this->workspace_ids ),
										'errors'	=> array(),
									);
	
			foreach ( $this->workspace_ids as $this->workspace_id ) {
				$statement = $this->dbh->prepare("INSERT INTO " . TABLE_WORKSPACES_USERS . " (added_by,user_id,workspace_id)"
													." VALUES (:added_by, :id, :workspace)");
				$statement->bindParam(':added_by', $this->added_by);
				$statement->bindParam(':id', $this->user_id, PDO::PARAM_INT);
				$statement->bindParam(':workspace', $this->workspace_id, PDO::PARAM_INT);
				$this->status = $statement->execute();
				
				if ( $this->status ) {
					$this->results['added']++;
				}
				else {
					$this->results['errors'][] = array(
														'workspace'	=> $this->workspace_id,
													);
				}
			}
			
			return $this->results;
		}
	}

	function user_edit_workspaces($arguments)
	{
		$this->user_id	= $arguments['user_id'];
		$this->workspace_ids	= is_array( $arguments['workspace_ids'] ) ? $arguments['workspace_ids'] : array( $arguments['workspace_ids'] );
		$this->added_by		= $arguments['added_by'];

		if ( in_array( CURRENT_USER_LEVEL, array(9,8) ) ) {
			$this->results 		= array(
										'added'		=> 0,
										'queue'		=> count( $this->workspace_ids ),
										'errors'	=> array(),
									);

			$this->found_workspaces = array();
			$this->sql_workspaces = $this->dbh->prepare("SELECT DISTINCT workspace_id FROM " . TABLE_WORKSPACES_USERS . " WHERE user_id=:id");
			$this->sql_workspaces->bindParam(':id', $this->user_id, PDO::PARAM_INT);
			$this->sql_workspaces->execute();
			$this->count_workspaces = $this->sql_workspaces->rowCount();
		
			if ($this->count_workspaces > 0) {
				$this->sql_workspaces->setFetchMode(PDO::FETCH_ASSOC);
				while ( $this->row_workspaces = $this->sql_workspaces->fetch() ) {
					$this->found_workspaces[] = $this->row_workspaces["workspace_id"];
				}
			}
			
			/**
			 * 1- Make an array of workspaces where the user is actually a member,
			 * but they are not on the array of selected workspaces.
			 */
			$this->remove_workspaces = array_diff($this->found_workspaces, $this->workspace_ids);

			if ( !empty( $this->remove_workspaces) ) {
				$this->delete_ids = implode( ',', $this->remove_workspaces );
				$this->statement = $this->dbh->prepare("DELETE FROM " . TABLE_WORKSPACES_USERS . " WHERE user_id=:user_id AND FIND_IN_SET(workspace_id, :delete)");
				$this->statement->bindParam(':user_id', $this->user_id, PDO::PARAM_INT);
				$this->statement->bindParam(':delete', $this->delete_ids);
				$this->statement->execute();
			}
			 
			/**
			 * 2- Make an array of workspaces in which the user is not a current member.
			 */
			$this->new_workspaces = array_diff($this->workspace_ids, $this->found_workspaces);
			if ( !empty( $this->new_workspaces) ) {
				$this->new_workspaces_add	= new WorkspacesUsers();
				$this->add_arguments	= array(
												'user_id'	=> $this->user_id,
												'workspace_ids'	=> $this->new_workspaces,
												'added_by'	=> CURRENT_USER_USERNAME,
											);
				$this->results['new']	= $this->new_workspaces_add->user_add_to_workspaces($this->add_arguments);
			}
	
			return $this->results;
		}
	}

}
