<?php

if (!in_array($_SERVER['REMOTE_ADDR'], explode(',', '67.170.161.220,64.85.162.30'))) {
    echo "<h1>UNDER CONSTRUCTION</h1>";
    exit;  
}

define('DOTMESH_ROOT_DIR', __DIR__.'/dotmesh');

require DOTMESH_ROOT_DIR.'/buckyball.php';

BDebug::mode('DEBUG');

require DOTMESH_ROOT_DIR.'/DotMesh.php';

BConfig::i()->add(include(DOTMESH_ROOT_DIR.'/config.php'));

BModuleRegistry::i()->scan(DOTMESH_ROOT_DIR.'/plugins');

BApp::i()->run();