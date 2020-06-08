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

    public function bind($username, $password)
    {
        /** @noinspection PhpUndefinedConstantInspection */
        $this->ldap = ldap_connect(LDAP_HOST, LDAP_PORT);
        ldap_set_option($this->ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->ldap, LDAP_OPT_REFERRALS, 0);

        /** @noinspection PhpUndefinedConstantInspection */
        return ldap_bind($this->ldap, LDAP_DOMAIN . "\\" . $username, $password);
    }

    public function get_entry_attributes($username)
    {
        $filter = '(sAMAccountName=' . $username . ')';
        $attributes = array('sAMAccountName', 'displayName', 'mail', 'objectGUID');
        /** @noinspection PhpUndefinedConstantInspection */
        $search_result = ldap_search($this->ldap, LDAP_BASEDN, $filter, $attributes);
        $ldap_user = ldap_first_entry($this->ldap, $search_result);

        return ldap_get_attributes($this->ldap, $ldap_user);
    }

    public function update_db($username, $attributes = null)
    {
        if (!$attributes) {
            $attributes = self::get_entry_attributes($username);
        }
        $attributes['objectGUID'][0] = bin2hex($attributes['objectGUID'][0]);

        $query = "UPDATE " . TABLE_USERS . " SET
                                        user = :user,
                                        name = :name,
										email = :email
										";

        $query .= " WHERE objectguid = :objectguid";

        $statement = $this->dbh->prepare($query);
        $statement->bindParam(':user', $attributes['sAMAccountName'][0]);
        $statement->bindParam(':name', $attributes['displayName'][0]);
        $statement->bindParam(':email', $attributes['mail'][0]);
        $statement->bindParam(':objectguid', $attributes['objectGUID'][0]);

        $statement->execute();

        $statement = $this->dbh->prepare("SELECT id FROM " . TABLE_USERS . " WHERE objectguid = :objectguid");
        $statement->bindParam(':objectguid', $attributes['objectGUID'][0]);
        $statement->execute();
        $statement->setFetchMode(PDO::FETCH_ASSOC);
        return $statement->fetch()['id'];
    }

    public function check_by_guid($objectguid)
    {
        $query = "SELECT 1 FROM " . TABLE_USERS . " WHERE objectguid = :objectguid LIMIT 1";
        $statement = $this->dbh->prepare($query);
        $statement->bindParam(':objectguid', $objectguid);

        $statement->execute();

        return $statement->rowCount() > 0;
    }
}
