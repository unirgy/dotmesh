<?php return array('modules' => array('BTwitterAdapter' => array(
    'version' => '0.1.0',
    'bootstrap' => array('file'=>'BTwitterAdapter.php', 'callback'=>'BTwitterAdapter::bootstrap'),
    'depends' => array('DotMesh'),
    'migrate' => 'BTwitterAdapter::migrate',
)));