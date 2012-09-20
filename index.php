<?php

define('DOTMESH_ROOT_DIR', __DIR__.'/dotmesh');

require DOTMESH_ROOT_DIR.'/buckyball.php';

BDebug::mode('DEBUG');

require DOTMESH_ROOT_DIR.'/DotMesh.php';

BConfig::i()->add(include(DOTMESH_ROOT_DIR.'/config.php'));

BModuleRegistry::i()->scan(DOTMESH_ROOT_DIR.'/plugins');

BApp::i()->run();