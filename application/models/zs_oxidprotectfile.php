<?php
/**
 * Main file. module  Protect integrity oxid system files
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
 * @license    http://www.gnu.org/licenses/
 * @version    1.1.0
 * @link       http://zinitsolutions.com/
 * @lastmodified    $Date: rene 30/05/2013 $
 */


class zs_oxidprotectfile extends zs_oxidprotectfile_parent {

    /**
     * overridden method
     * 
     * @return string
     */
    public function getProductId() {
        
        /*Main part module */

        $myconfig = $this->getConfig();

        $lastchek = $myconfig->getShopConfVar('zs_LastChek');

        if (!isset($lastchek)) {
            $date = date("Y-m-d H:i:s");
            $lastchek = $date;
            
            $myconfig->saveShopConfVar('date', 'zs_LastChek', $date);
        }

        $end = date("Y-m-d H:i:s");

        $time = $myconfig->getConfigParam("zs_blTime");

        if ($time <= (abs(strtotime($lastchek) - strtotime($end)) / 60)) {
            $myconfig->saveShopConfVar('date', 'zs_LastChek', $end);

            $email = $myconfig->getConfigParam("zs_EmailForAlert");

            if ($myconfig->getConfigParam("zs_blSendAdminEmail")) {

                if ($iLangId === null) {
                    $oShop = $myconfig->getActiveShop();
                } else {
                    $oShop = oxNew('oxshop');
                    $oShop->loadInLang($iLangId, $myConfig->getShopId());
                }
                
                $email.=' '.$oShop->oxshops__oxinfoemail->value;
            }

            $typefiles = $myconfig->getConfigParam("zs_Files");

            /* set dealult file types */
            if (empty($typefiles)) {
                $typefiles = 'php, js, html, tpl';
            }

            $exe = "php " . $_SERVER['DOCUMENT_ROOT'] . "/modules/zs_oxidprotect/chekfiles.php '" . $typefiles . "' '" . $email . "' > /dev/null &";
            
            //var_dump($exe);
            system($exe, $out);
            
        }

        /* end module */

        return parent::getProductId();
    }

   

}

?>
