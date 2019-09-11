<?php

namespace ProjectSend\Classes;

use \PDO;

class LDAP
{
    public $ldap;
    private $dbh;

    public function __construct(PDO $dbh = null)
    {
        if (empty($dbh)) {
            global $dbh;
        }

        $this->dbh = $dbh;
    }

    public function bind($username, $password) {
        $this->ldap = ldap_connect(LDAP_HOST, LDAP_PORT);
        ldap_set_option($this->ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->ldap, LDAP_OPT_REFERRALS, 0);

        return ldap_bind($this->ldap, LDAP_DOMAIN . "\\" . $username, $password);
    }

    public function get_entry_attributes($username) {
        $filter='(sAMAccountName=' . $username . ')';
        $attributes = array('sAMAccountName', 'displayName', 'mail', 'objectGUID');
        $search_result = ldap_search($this->ldap, LDAP_BASEDN, $filter, $attributes);
        $ldap_user = ldap_first_entry($this->ldap, $search_result);

        return ldap_get_attributes($this->ldap, $ldap_user);
    }

    public function update_db($username, $attributes = null) {
        if (!$attributes) {
            $attributes = self::get_entry_attributes($username);
        }
        $attributes['objectGUID'][0] = bin2hex($attributes['objectGUID'][0]);

        $this->query = "UPDATE " . TABLE_USERS . " SET
                                        user = :user,
                                        name = :name,
										email = :email
										";

        $this->query .= " WHERE objectguid = :objectguid";

        $this->statement = $this->dbh->prepare($this->query);
        $this->statement->bindParam(':user', $attributes['sAMAccountName'][0]);
        $this->statement->bindParam(':name', $attributes['displayName'][0]);
        $this->statement->bindParam(':email', $attributes['mail'][0]);
        $this->statement->bindParam(':objectguid', $attributes['objectGUID'][0]);

        $this->statement->execute();

        $this->statement = $this->dbh->prepare("SELECT id FROM " . TABLE_USERS . " WHERE objectguid = :objectguid");
        $this->statement->bindParam(':objectguid', $attributes['objectGUID'][0]);
        $this->statement->execute();
        $this->statement->setFetchMode(PDO::FETCH_ASSOC);
        return $this->statement->fetch()['id'];
    }

    public function check_by_guid($objectguid) {
        $this->query = "SELECT 1 FROM " . TABLE_USERS . " WHERE objectguid = :objectguid LIMIT 1";
        $this->statement = $this->dbh->prepare($this->query);
        $this->statement->bindParam(':objectguid', $objectguid);

        $this->statement->execute();

        return $this->statement->rowCount() > 0;
    }
}