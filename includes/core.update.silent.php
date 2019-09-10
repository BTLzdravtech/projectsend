<?php
/**
 * This file includes updates that should be done regardless of the
 * user / client status.
 *
 * @package		ProjectSend
 * @subpackage	Updates
 */
global $updates_made;

/** Remove "r" from version */
$current_version = substr(CURRENT_VERSION, 1);

$statement = $dbh->prepare("SELECT value FROM " . TABLE_OPTIONS . " WHERE name = 'last_update'");
$statement->execute();
if ( $statement->rowCount() > 0 ) {
	$statement->setFetchMode(PDO::FETCH_ASSOC);
	while( $row = $statement->fetch() ) {
		$last_update = $row['value'];
	}
}

if ($last_update < $current_version || !isset($last_update)) {
	/**
	 * r431 updates
	 * A new database table was added.
	 * Password reset support is now supported.
	 */
	if ($last_update < 431) {
		if ( !tableExists( TABLE_PASSWORD_RESET ) ) {
			$query = '
			CREATE TABLE IF NOT EXISTS `' . TABLE_PASSWORD_RESET . '` (
			  `id` int(11) NOT NULL AUTO_INCREMENT,
			  `user_id` int(11) DEFAULT NULL,
			  `token` varchar(32) NOT NULL,
			  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
			  `used` int(1) DEFAULT \'0\',
			  FOREIGN KEY (`user_id`) REFERENCES ' . TABLE_USERS . '(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
			';
			$dbh->query($query);
			$updates_made++;
		}
	}

	/**
	 * r437 updates
	 * A new database table was added.
	 * Password reset support is now supported.
	 */
	if ($last_update < 520) {
		$q = $dbh->query("ALTER TABLE " . TABLE_USERS . " MODIFY user VARCHAR(".MAX_USER_CHARS.") NOT NULL");
		$q2 = $dbh->query("ALTER TABLE " . TABLE_USERS . " MODIFY password VARCHAR(".MAX_PASS_CHARS.") NOT NULL");
		if ($q && $q2) {
			$updates_made++;
		}
	}

    /**
     * r1106 updates
     * Added users, groups, files, categories with owner_id.
     * Password reset support is now supported.
     */
    if ($last_update < 1106) {
        $q = $dbh->query("ALTER TABLE " . TABLE_USERS . " ADD COLUMN owner_id INT(11) AFTER contact");
        $q2 = $dbh->query("ALTER TABLE " . TABLE_GROUPS . " ADD COLUMN owner_id INT(11) AFTER public_token");
        $q3 = $dbh->query("ALTER TABLE " . TABLE_FILES . " ADD COLUMN owner_id INT(11) AFTER description");
        $q4 = $dbh->query("ALTER TABLE " . TABLE_CATEGORIES . " ADD COLUMN owner_id INT(11) AFTER description");

        $q5 = $dbh->query("UPDATE " . TABLE_USERS . " U1 INNER JOIN " . TABLE_USERS . " U2 ON U2.user = U1.created_by AND U1.level = 0 AND U1.created_by IS NOT NULL SET U1.owner_id = U2.id");
        $q6 = $dbh->query("UPDATE " . TABLE_GROUPS . " U1 INNER JOIN " . TABLE_USERS . " U2 ON U2.user = U1.created_by SET U1.owner_id = U2.id");
        $q7 = $dbh->query("UPDATE " . TABLE_FILES . " U1 INNER JOIN " . TABLE_USERS . " U2 ON U2.user = U1.uploader SET U1.owner_id = U2.id");
        $q8 = $dbh->query("UPDATE " . TABLE_CATEGORIES . " U1 INNER JOIN " . TABLE_USERS . " U2 ON U2.user = U1.created_by SET U1.owner_id = U2.id");
        if ($q && $q2 && $q3 && $q4 && $q5 && $q6 && $q7 && $q8) {
            $updates_made++;
        }
    }
}