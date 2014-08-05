<?php

class zs_oxidprotect_install {

    /**
     * creates a table in the database when you activate the module, 
     * if it does not exist yet
     *  
     * @return null
     */
    public static function onActivate() {
        
        $sQuery = 'CREATE TABLE IF NOT EXISTS `zs_oxidprotect` (
                    `zs_oxid` int(11) NOT NULL AUTO_INCREMENT,
                    `zs_path` varchar(255) NOT NULL,
                    `zs_hash` varchar(300) NOT NULL,
                    `zs_date` datetime NOT NULL,
                    `zs_lastmod` datetime NOT NULL,
                    `zs_checked` int(1) NOT NULL DEFAULT "0",
                    PRIMARY KEY (`zs_oxid`)
                   ) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1';
        
        oxDB::getDb()->execute($sQuery);
    }

}

?>
