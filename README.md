Oxid Modul Shopsicherheit 
===========

Author
---------
ZinitSolutions GmbH  http://zinitsolutions.com/

Version  1.1.0

Description
------

The module allows you to regularly check the integrity of files and notify by email.

For example, if the server has been hacked, stolen FTP access or SSH, often malicious add malicious code to the site to get the passwords, to redirect users to a site potdelny and etc..
The module responds promptly to any changes that are made to store important files. 
If the changes were committed on the configured email module (eg a developer who has the right to change the files), and, if desired, store administrator is notified. 
The notification comprises information about how the file or files have been changed and times when changes were recorded. 
Module settings allow you to specify:
eat-mail address where you want to send notification;
Frequency of checks in minutes;
file types that are subject to verification by default (php js html tpl).
The module makes the minimum load on the file system and does not affect the performance of the store. While checking the background check site is about 1 minute.

> **Install:**
1. Copy the files to your modules folder.
2. Set the permissions on the folder "modules/zs_oxidprotect/log" to 777
3. If you used the old version of this module, you should delete the table `zs_oxidprotect` from the database
4. Activate module in admin area