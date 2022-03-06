<?php

/***
 *** wxAuthLDAP.class.php
 ***
 *** (c) Heiko Weber
 ***     2010, 2022
 ***
 *** heiko@wecos.de
 ***
 ***/


require_once MAX_PATH . '/lib/max/other/common.php';
require_once MAX_PATH . '/lib/max/Plugin/Translation.php';
require_once LIB_PATH . '/Extension/authentication/authentication.php';
require_once __DIR__  . '/WecosLDAPClient.php';

/**
 * Authentication LDAP plugin which authenticates users against an LDAP server
 *
 * This plugin uses information stored in "wecosAuthLDAP" section in configuration file.
 */
class Plugins_Authentication_WxAuthLDAP_WxAuthLDAP extends Plugins_Authentication {
    
    function getName()
    {
        return $this->translate('Wecos Auth LDAP');
    }

    /**
     * A method to check a username and password
     *
     * @param string $username
     * @param string $password
     * @return mixed A DataObjects_Users instance, or false if no matching user was found
     */
    function checkPassword($username, $password)
    {
        global $conf;

        $ldap_client = new WecosLDAPClient($conf['wxAuthLDAP']);

        if ($ldap_client->checkPassword($username, $password)) {
            $doUser = OA_Dal::factoryDO('users');
            $doUser->username = strtolower($username);
            $doUser->find();

            if ($doUser->fetch()) {
                return parent::checkPassword($username, $doUser->password);
            }
            OA::debug("WxAuthLDAP: User '".$username."' authenticated by LDAP, but no matching OpenX user exists.");
        } else {
            $msg = $ldap_client->get_last_error();
            if ($msg !== true) {
                OA::debug("WxAuthLDAP: User '".$username."' not authenticated by LDAP: ".$msg);
            }
        }

        return !empty($conf['wxAuthLDAP']['ldapOnly']) ? false : parent::checkPassword($username, $password);
    }
}

?>
