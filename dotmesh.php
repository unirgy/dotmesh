<?php

define('DOTMESH_ROOT_DIR', __DIR__.'/dotmesh');

require DOTMESH_ROOT_DIR.'/buckyball.php';

BDebug::mode('PRODUCTION');
BConfig::i()->add(include(DOTMESH_ROOT_DIR.'/config.php'));

if (!in_array($_SERVER['REMOTE_ADDR'], explode(',', '67.170.161.220,64.85.162.30,76.105.154.150,178.85.253.152,24.103.18.105'))) {
    echo "<h1>UNDER CONSTRUCTION</h1>";
    exit;
}

require DOTMESH_ROOT_DIR.'/DotMesh.php';
BModuleRegistry::i()->scan(DOTMESH_ROOT_DIR.'/plugins/*');

BApp::i()->run();