<?php

namespace ProjectSend\Classes;

class LDAP
{
    public $ldap;

    public function bind($username, $password) {
        $this->ldap = ldap_connect(LDAP_HOST, LDAP_PORT);
        ldap_set_option($this->ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->ldap, LDAP_OPT_REFERRALS, 0);

        return ldap_bind($this->ldap, LDAP_DOMAIN . "\\" . $username, $password);
    }

    public function get_entry_attributes($username) {
        $filter='(sAMAccountName=' . $username . ')';
        $attributes = array('sAMAccountName', 'displayName', 'mail', 'mail');
        $search_result = ldap_search($this->ldap, LDAP_BASEDN, $filter, $attributes);
        $ldap_user = ldap_first_entry($this->ldap, $search_result);

        return ldap_get_attributes($this->ldap, $ldap_user);
    }
}