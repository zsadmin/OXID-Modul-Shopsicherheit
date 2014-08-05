<?php

ini_set('max_execution_time', 3000);
ini_set('memory_limit', '256M');

/**
 * File in a background check store security. Part of module  "Protect integrity oxid system files"
 *
 *
 *
 * LICENSE:  
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 3 of the License
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/
 *
 * @category   Security
 * @package    Protect integrity oxid system files
 * @author     ZinitSolutions GmbH info@zinitsolutions.com
 * @copyright  Copyright, ZinitSolutions GmbH
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    1.1.0
 * @link       http://zinitsolutions.com/
 * @lastmodified    $Date: rene 06/12/2013 $
 */
if (file_exists('script_is_running')) {
    die('Script is already running');
}
touch('script_is_running');

function getShopBasePath() {

    $dir = dirname((__FILE__));

    $dir = str_replace('modules/zs_oxidprotect', '', $dir);

    return $dir;
}

require_once getShopBasePath() . "/bootstrap.php";

class file {

    /**
     * List of file extensions that you want to check (separated by commas)
     *
     * @var string
     */
    protected $_file;

    /**
     * List of email addresses to send reports (separated by commas)
     *
     * @var string
     */
    protected $_email;

    /**
     * List of modified files
     *
     * @var array
     */
    protected $_aModifiedFiles = array();

    /**
     * List of new files
     *
     * @var array
     */
    protected $_aNewFiles = array();

    /**
     * List of deleted files
     *
     * @var array
     */
    protected $_aDeletedFiles = array();

    /**
     * Sign of the first start of the module
     * 
     * @var boolean 
     */
    protected $_blFirstRun = false;

    /**
     * Path to the module directory in the file system
     * 
     * @var string
     */
    protected $_sModuleLocation = '';

    /**
     * DB connect object
     * 
     * @var mysqli object
     */
    protected $_db = null;

    /**
     * Class constructor.
     * Set _sModuleLocation.
     * Get db connect link
     * 
     * @return null
     * 
     */
    public function __construct() {

        include $this->getShopBasePathOb() . '/config.inc.php';

        $this->_db = new mysqli($this->dbHost, $this->dbUser, $this->dbPwd, $this->dbName);

        $this->_sModuleLocation = $this->getShopBasePathOb() . '/modules/zs_oxidprotect/';
    }

    /**
     * Checks shop files and return true if something changed
     *
     * @return bolean
     */
    public function chek() {
        $sQuery = 'SELECT COUNT(*) AS C FROM `zs_oxidprotect`';
        $oRes = $this->_db->query($sQuery);
        $aRes = $oRes->fetch_assoc();

        $this->_blFirstRun = ($aRes['C'] ? false : true);

        $this->resetState();

        $this->_getDirContents($this->getShopBasePathOb()); //check new and modified files

        if (!$this->_blFirstRun)
            $this->checkDeletedFiles();

        return (count($this->_aDeletedFiles) || count($this->_aModifiedFiles) || count($this->_aNewFiles));
    }

    /**
     * Send email to admin if some changes found
     * 
     * @return null 
     */
    public function sendEmail() {
        $aMailStrings = array();
        $aMailStrings[] = 'OXIDPROTECT Module LOG';
        $aMailStrings[] = '';

        if (!$this->_blFirstRun) {
            if (count($this->_aNewFiles)) {
                $aMailStrings[] = ' ';
                $aMailStrings[] = 'New files in the system:';
                foreach ($this->_aNewFiles as $aFileData) {
                    $aMailStrings[] = 'File ' . $aFileData['zs_path'] . ' was ADDED on ' . $aFileData['zs_lastmod'];
                }
            }

            if (count($this->_aModifiedFiles)) {
                $aMailStrings[] = ' ';
                $aMailStrings[] = 'Modified files in the system:';
                foreach ($this->_aModifiedFiles as $aFileData) {
                    $aMailStrings[] = 'File ' . $aFileData['zs_path'] . ' was MODIFIED on ' . $aFileData['zs_dateMod'];
                }
            }

            if (count($this->_aDeletedFiles)) {
                $aMailStrings[] = ' ';
                $aMailStrings[] = 'Deleted files in the system:';
                foreach ($this->_aDeletedFiles as $aFileData) {
                    $aMailStrings[] = 'File ' . $aFileData['zs_path'] . ' was DELETED after ' . $aFileData['zs_lastmod'];
                }
            }
        } else {
            $aMailStrings[] = 'Shop under protect!';
        }

        $sHtmlMail = implode('<br />', $aMailStrings);
        $sTextMail = implode(PHP_EOL, $aMailStrings);

        $this->writeLog($sHtmlMail);

        try {

            $oxEmail = oxNew('oxemail');

            $myConfig = $oxEmail->getConfig();
            if ($iLangId === null) {
                $oShop = $myConfig->getActiveShop();
            } else {
                $oShop = oxNew('oxshop');
                $oShop->loadInLang($iLangId, $myConfig->getShopId());
            }

            $oxEmail->setSmtp($oShop);
            $oxEmail->isHtml(true);
            $oxEmail->setFrom($oShop->oxshops__oxinfoemail->value, $oShop->oxshops__oxname->getRawValue());

            $oxEmail->setBody($sHtmlMail);
            $oxEmail->setAltBody($sTextMail);
            $oxEmail->setSubject('OXIDPROTECT');

            $aMails = explode(',', $this->_email);

            if (count($aMails) > 0) {
                foreach ($aMails as $sVal) {
                    $oxEmail->setRecipient(trim($sVal), "");
                }
            } else {
                $oxEmail->setRecipient(trim($this->_email), "");
            }

            $oxEmail->send();
        } catch (Exception $e) {
            // TODO send mails without oxemail
        }
    }

    /**
     * @param string $dir Path to directory  for protect 
     * 
     * @return null
     */
    protected function _getDirContents($sDir) {
        $handle = opendir($sDir);
        if (!$handle)
            return array();

        while ($entry = readdir($handle)) {
            if ($entry == '.' || $entry == '..')
                continue;

            $entry = $sDir . DIRECTORY_SEPARATOR . $entry;
            if (is_file($entry) && $this->filter($entry)) {

                $hash = hash_file('md5', $entry);

                $sQuery = "SELECT *  FROM `zs_oxidprotect` WHERE `zs_path`='" . $entry . "'";
                $oRes = $this->_db->query($sQuery);

                if ($oRes->num_rows == 0) {
                    $sysDateupdate = date("Y-m-d H:i:s", filemtime($entry));
                    $sQuery = "INSERT INTO `zs_oxidprotect` SET `zs_path`='" . $entry . "',`zs_hash`='" . $hash . "',`zs_lastmod`='" . $sysDateupdate . "', `zs_date`=now(),`zs_checked`=1";
                    $this->_db->query($sQuery);
                    $this->_aNewFiles[] = array('zs_path' => $entry, 'zs_lastmod' => $sysDateupdate);
                } else {
                    while ($aRow = $oRes->fetch_assoc()) {
                        if ($hash !== $aRow['zs_hash']) {
                            $sysDateupdate = date("Y-m-d H:i:s", filemtime($entry));
                            $aRow['zs_dateMod'] = $sysDateupdate;
                            $this->_aModifiedFiles[] = $aRow;
                            $sQuery = "UPDATE `zs_oxidprotect` SET `zs_hash`='" . $hash . "',`zs_lastmod`='" . $sysDateupdate . "', `zs_date`=now(), `zs_checked`=1  WHERE `zs_oxid`='" . $aRow['zs_oxid'] . "'";
                        } else {
                            $sQuery = 'UPDATE `zs_oxidprotect` SET `zs_date`=now(),`zs_checked`=1 WHERE `zs_oxid`=' . $aRow['zs_oxid'];
                        }
                        $this->_db->query($sQuery);
                    }
                }
            } else if (is_dir($entry)) {
                if ($entry != $this->getShopBasePathOb() . '/tmp' && $entry != $this->_sModuleLocation . 'log')
                    $this->_getDirContents($entry);
                else
                    continue;
            }
        }
        closedir($handle);
    }

    /**
     * set field `checked` to 0; call before check start
     * 
     * @return null;
     */
    protected function resetState() {
        $sQuery = 'UPDATE `zs_oxidprotect` SET `zs_checked`=0';
        $this->_db->query($sQuery);
    }

    /**
     * Search deleted files
     * 
     * @return null;
     */
    protected function checkDeletedFiles() {
        $sQuery = 'SELECT * FROM `zs_oxidprotect` WHERE `zs_checked`=0';
        $oRes = $this->_db->query($sQuery);
        while ($aRow = $oRes->fetch_assoc()) {
            $this->_aDeletedFiles[] = $aRow;
            $this->_db->query('DELETE FROM `zs_oxidprotect` WHERE `zs_oxid`=' . $aRow['zs_oxid']);            
        }
    }

    /**
     * Check if file need protect, filter by extension
     * 
     * @param type $filename
     * 
     * @return boolean
     */
    protected function filter($sFilename) {
        $sExt = pathinfo($sFilename, PATHINFO_EXTENSION);
        $aHaystack = explode(',', $this->_file);

        foreach ($aHaystack as $iKey => $sVal) {
            $aHaystack[$iKey] = trim($sVal);
        }

        if (in_array($sExt, $aHaystack)) {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Return shop root folder in file system
     * 
     * @return string
     */
    public function getShopBasePathOb() {
        $sDir = dirname((__FILE__));
        $sDir = str_replace('/modules/zs_oxidprotect', '', $sDir);
        return $sDir;
    }

    /**
     * Return shop root folder in file system
     * 
     * @return null
     */
    protected function writeLog($sHTML) {
        $aLines = explode('<br />', $sHTML);
        $aLines[0] = PHP_EOL . PHP_EOL . PHP_EOL . '--------------------------------------------------------------' . PHP_EOL;
        $aLines[0].= date("Y-m-d H:i:s");
        $aLines[1].= '--------------------------------------------------------------';
        $sHTML = implode(PHP_EOL, $aLines);

        file_put_contents($this->_sModuleLocation . 'log/zs_oxidprotect_log', $sHTML, FILE_APPEND);
    }

    
    /**
     * Setter for $this->_file
     * @param string $sParam
     * 
     * @return null
     */
    public function setFile($sParam) {
        $this->_file = $sParam;
    }

    /**
     * Setter for $this->_email
     * @param string $sParam
     * 
     * @return null
     */
    public function setEmail($sParam) {
        $this->_email = $sParam;
    }

}

$test = new file();
$test->setFile($argv[1]);
$test->setEmail($argv[2]);

if ($test->chek()) {
    $test->sendEmail();
}

unlink('script_is_running');