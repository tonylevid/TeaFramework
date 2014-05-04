<?php

$bootstrap = dirname(__FILE__) . '/../T/T.php';
$config = dirname(__FILE__) . '/app/config/main.config.php';
require_once($bootstrap);
$router = Tea::init($config);
array_shift($argv);
call_user_func_array(array($router, 'route'), $argv);