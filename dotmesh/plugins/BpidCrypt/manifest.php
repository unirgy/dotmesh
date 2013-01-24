<?php return array('modules' => array('BpidCrypt' => array(
    'version' => '0.1.0',
    'bootstrap' => array('file'=>'BpidCrypt.php', 'callback'=>'BpidCrypt::bootstrap'),
    'depends' => array('DotMesh'),
    'migrate' => 'BpidCrypt::migrate',
)));