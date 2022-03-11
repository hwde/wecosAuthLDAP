<?php

require_once '../../../../init.php';
require_once '../../config.php';

require_once LIB_PATH . '/Admin/Redirect.php';

if (empty($conf['wxTestLDAP']['enableTest'] ?? 0)) {
    exit;
}

if (!empty($_POST)) {
    $session['wxTestLDAP'] = [
        'username' => $_POST['name'] ?? null,
        'password' => $_POST['pw'] ?? null,
        'submit'   => $_POST['testLDAP'] ?? null,
    ];
    phpAds_SessionDataStore();
    OX_Admin_Redirect::redirect('plugins/wxTestLDAP/wxTestLDAP.php');
}

phpAds_PageHeader("wxTestLDAP-i", '', '../../');

if (isset($session['wxTestLDAP'])) {
    $params = $session['wxTestLDAP'];
    unset($session['wxTestLDAP']);
    phpAds_SessionDataStore();
    
    try {
        require_once MAX_PATH . '/plugins/authentication/wxAuthLDAP/WecosLDAPClient.php';

        $log  = 'WecosLDAPClient failed to load';

        try {
            $plug = new WecosLDAPClient($conf['wxAuthLDAP']);
            $plug->enableTestLog();
            $plug->checkPassword($params['username'], $params['password']);
            $log = $plug->getTestLog();
        }
        catch(Exception $e) {
            $log = $e->__toString();
        }
        
        print str_replace("\n", "<br />", htmlspecialchars(var_export($log, true)));
    }
    catch(Exception $e) {
        print "Outer Exception: ".htmlspecialchars($e->__toString());
    }
}

?>

<form method="POST">

   Username:<br />
   <input name="name" type="text" />
   <br />
   Password:<br />
   <input name="pw" type="password" autocomplete="off" />
   <br />
   <br />
   <input type="submit" name="testLDAP" value="Test LDAP Login" />
</form>

<?php
    
phpAds_PageFooter();
