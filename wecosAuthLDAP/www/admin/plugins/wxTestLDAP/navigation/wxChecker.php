<?php

require_once(MAX_PATH . '/lib/OA/Admin/Menu/IChecker.php');
require_once MAX_PATH . '/lib/OA/Dal.php';
require_once MAX_PATH . '/www/admin/lib-zones.inc.php';

class Plugins_admin_wxAuthLDAP_wxTestLDAPChecker implements OA_Admin_Menu_IChecker {

    public function check($oSection) {
        global $conf;

        return !empty($conf['wxTestLDAP']['enableTest'] ?? 0);
    }
}
