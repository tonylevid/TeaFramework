<?php
header('Content-type:text/html;charset=utf-8;');

$bootstrap = dirname(__FILE__) . '/../T/T.php';
$config = dirname(__FILE__) . '/app/config/main.config.php';
require_once($bootstrap);
T::init($config)->route();

echo '<p /> running time: ';
echo microtime(true) - T_BEGIN_TIME . ' sec';

$usage = (int)(memory_get_usage()/1024);
echo "<br /><span style='color:red'>memory used {$usage} kb.</span>";