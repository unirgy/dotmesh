<?php 

$config = array(
    'db' => array(
        'host' => 'localhost',
        'username' => 'dotmesh',
        'password' => 'cSc4ngqG*I!Ssbz',
        'dbname' => 'dotmesh1',
        'table_prefix' => '',
        'logging' => '1',
        'implicit_migration' => '1',
    ),
  'web' => array (
    'hide_script_name' => '1',
  ),
  'cookie' => array (
    'session_check_ip' => '1',
    //'timeout' => '',
    //'domain' => '',
    //'path' => '/',
    //'session_namespace' => '',
    'session_handler' => 'apc',
  ),
);

return $config;