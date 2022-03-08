<?php

/***
 *** WecosLDAPClient.php
 ***
 *** (c) Heiko Weber
 ***     2010, 2022
 ***
 *** heiko@wecos.de
 ***
 ***/

/*
 * sample installation parameter, provided during plugin setup

$params = array(
                'connectionHost'        => 'ldap.host',
                'connectionVersion'     => 3,
                'connectionPort'        => 389,
                'connectionName'        => 'cn=binduser,ou=accounts,ou=company,ou=people,o=companyldap',
                'connectionPassword'    => 'private',
                'userBase'              => 'ou=accounts,ou=company,ou=people,o=companyldap',
//              'userSubtree'           => 1,
                'userSearch'            => '(uid=%s)',
//              'roleBase'              => ou=usergroups,ou=company,ou=people,o=companyldap,
//              'roleName'              => cn,
//              'roleSearch'            => (memberUid=%s),
               );


// username, password to authorize
$username = 'username';
$password = 'foobar';

*
*/

// -----------------------------------------------------
// no change required below this line
//

// $ldap_client = new WecosLDAPClient($params);
// $ret = $ldap_client->checkPassword($username, $password);

// var_dump($ret);
// var_dump($ldap_client->last_error);

class WecosLDAPClient {
    var $log      = null;
    var $ldap_con = false;
    var $ldap_param;
    var $last_error;
    
    /** @param array $aConnectionParams key/value
     */
    public function __construct($aConnectionParams) {
        $this->ldap_param = $aConnectionParams;
    }

    public function enableTestLog() {
        $this->log = [];
    }

    public function getTestLog() {
        return $this->log;
    }

    private function log_err($msg) {
        if (is_array($this->log)) {
            $this->log[] = $msg;
        }
    }
    
    private function set_error($func, $what) {
        $this->log_err([$func, $what]);
        $this->last_error = 'WecosLDAPClient::'.$func.': '.$what;
        return false;
    }
    
    private function clear_error() {
        $this->last_error = true;
        return true;
    }

    public function get_last_error() {
        return $this->last_error;
    }
    
    /** connect with the default connection user
     */
    function connect_default() {
        $func = 'connect_default';
        
        if (!function_exists('ldap_connect')) {
            return $this->set_error($func, 'ldap_connect undefined');
        }
        
        if (($this->ldap_con = @ldap_connect(
                                            $this->ldap_param['connectionHost'],
                                            $this->ldap_param['connectionPort'])) !== false) {
            
            if (!@ldap_set_option($this->ldap_con, LDAP_OPT_PROTOCOL_VERSION, $this->ldap_param['connectionVersion'])) {
                return $this->set_error($func, 'Failed to set version to protocol '.$this->ldap_param['connectionVersion']);
            }

            if (!@ldap_bind($this->ldap_con,
                           $this->ldap_param['connectionName'],
                           $this->ldap_param['connectionPassword'])) {
                
                $this->close();
                return $this->set_error($func, 'ldap_bind failed');
            }

            return $this->clear_error();
        }
        
        return $this->set_error($func, 'ldap_connect failed');
    }

    /** connect with a real user user
     *
     * @param string password
     * @param string dn
     *
     * return boolean, true/false if connected
     */
    function connect_user($password, $dn) {
        $func = 'connect_user';
        if(!$password) {
            return $this->set_error($func, 'Anonymous bind denied');
        }
        
        if (($this->ldap_con = @ldap_connect(
                                            $this->ldap_param['connectionHost'],
                                            $this->ldap_param['connectionPort'])) !== false) {
            
            if (!@ldap_set_option($this->ldap_con, LDAP_OPT_PROTOCOL_VERSION, $this->ldap_param['connectionVersion'])) {
                return $this->set_error($func, 'Failed to set version to protocol '.$this->ldap_param['connectionVersion']);
            }

            if (!@ldap_bind($this->ldap_con, $dn, $password)) {
                $this->close();
                return $this->set_error($func, 'ldap_bind failed');
            }

            return $this->clear_error();
        }
        
        return $this->set_error($func, 'ldap_connect failed');
    }

    /**
     */
    function close() {
        if ($this->is_connected()) {
            @ldap_unbind($this->ldap_con);
            $this->ldap_con = false;
        }
    }
    
    /** 
     *  @return boolean
     */
    function is_connected() {
        return $this->ldap_con !== false;
    }

    /**
     *  @param string username
     *  @param string password
     */
    function checkPassword($username, $password) {
        $func = 'checkPassword';
        if(!$password) {
            return $this->set_error($func, 'Empty password supplied');
        }

        if ($this->connect_default()) {
            $filter = sprintf($this->ldap_param['userSearch'], self::escape($username));

            if (!function_exists('ldap_search')) {
                return $this->set_error($func, 'ldap_search undefined');
            }
            
            $rs = @ldap_search($this->ldap_con, $this->ldap_param['userBase'], $filter, array('dn'));

            if ($rs === false) {
                $this->close();
                return $this->set_error($func, 'ldap_search');
            }
            
            if (!function_exists('ldap_get_entries')) {
                return $this->set_error($func, 'ldap_get_entries undefined');
            }
            
            $info = @ldap_get_entries($this->ldap_con, $rs);

            if (!is_array($info) || $info['count'] <= 0) {
                $this->close();
                $this->clear_error(); // not a failure to find nothing
                return false;
            }

            if (is_array($info[0]) && array_key_exists('dn', $info[0])) {
                $this->close();
                if ($this->connect_user($password, $info[0]['dn'])) {
                    $this->close();
                    return $this->clear_error();
                }
                
                // wrong password
                return false;
            }

            $this->close();
            return $this->set_error($func, 'unexpected result of ldap_search/ldap_get_entries');
        }

        // connect_default should have set an error
        return false;
    }

    
    /** escape any special LDAP characters: ASCII<32, ()*\ in dn
     *  @param string $s
     *  @return string
     */
    static function escape($s) {
        $ret = '';
        while ($s != '') {
            $c = ord($s);
            if ($c >= 0 && $c <= 31) {
                $c = dechex($c);
                $ret .= '\\'.((strlen($c) != 2) ? '0'.$c : $c);
            } else {
                if ($c == ord('(') || $c == ord(')') || $c == ord('*') || $c == ord('\\')) {
                    $ret .= '\\';
                }
                $ret .= chr($c);
            }
            $s = substr($s, 1);
        }
        return $ret;
    }
}

?>
