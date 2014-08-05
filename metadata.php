<?php


/**
 * Metadata version
 */
$sMetadataVersion = '1.1';

/**
 * Module information
 */
$aModule = array(
    'id' => 'oxidprotect',
    'title' => 'Protect integrity oxid system files',
    'description' => array(
        'de' => 'This module protect integrity oxid system files',
        'en' => 'This module protect integrity oxid system files'
    ),
    'thumbnail' => 'out/admin/img/logo.jpg',
    'version' => '1.1',
    'author' => 'ZinitSolutions',
    'url' => 'http://zinitsolutions.com/',
    'email' => 'info@zinitsolutions.com',
    'extend' => array(
        'oxarticle' => 'zs_oxidprotect/application/models/zs_oxidprotectfile'
    ),
    'files' => array(
        'zs_oxidprotect_install' => 'zs_oxidprotect/admin/zs_oxidprotect_install.php'
    ),
    'events' => array(
        'onActivate' => 'zs_oxidprotect_install::onActivate'
    ),
    'settings' => array(
        array('group' => 'main', 'name' => 'zs_EmailForAlert', 'type' => 'str'),
        array('group' => 'main', 'name' => 'zs_Files', 'type' => 'str', 'value' => 'php, html, tpl, js'),
        array('group' => 'main', 'name' => 'zs_blSendAdminEmail', 'type' => 'bool', 'value' => 'true'),
        array('group' => 'main', 'name' => 'zs_blTime', 'type' => 'str', 'value' => '3')
    )
);
