<?php

define('DOTMESH_ROOT_DIR', __DIR__.'/dotmesh');

require DOTMESH_ROOT_DIR.'/buckyball.php';

BConfig::i()->add(include(DOTMESH_ROOT_DIR.'/config.php'));

require DOTMESH_ROOT_DIR.'/DotMesh.php';

BModuleRegistry::i()->scan(DOTMESH_ROOT_DIR.'/plugins/*');

BApp::i()->run();