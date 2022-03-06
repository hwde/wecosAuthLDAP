# wecosAuthLDAP - LDAP Authentication for Revive Adserver

This plugin offers the possibility to replace the standard (local) login with LDAP. There is an option to fallback to local authentication - hoping not to lock itself out after installation. In this respect, here briefly the usual procedure for the installation:

* Login to your Revive adserver as an administrator
* Change to the administration if you are not already there (Working as "Administrator Account“)
* Go to the Configuration -\> Plugins menu
* disable the option "Enable newly installed plugins" to be on the safe side
* Now install the plugin "wecosAuthLDAP" in the plugins.
* Click on "Details" of the plugin wecosAuthLDAP, and there first on the settings of the "LDAP Authentication Plugin“.
* Enter your LDAP server settings there.
* DONT check "LDAP Authentication only (fallback to OpenX database otherwise)" at this time
* now you should enable the plugin
* switch to the settings of the second plugin part (LDAP Login Test Plugin) and activate "Enable LDAP Authentication Testpage". This activates a new menu item "LDAP Testpage" in the main menu.
* If the login test was successful, switch back to the plugin configuration and deactivate the LDAP test login option (the new menu item should disappear).
* feel free to activate the option "LDAP Authentication ony", this should now be possible without any risk.

Please note: The accounts must still be created on the Revive ad server as usual, only the password verification takes place via the LDAP server.
