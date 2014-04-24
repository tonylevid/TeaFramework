<?php
header('Content-type:text/html;charset=utf-8;');

define('APP_PATH', dirname(__FILE__));
$bootstrap = dirname(__FILE__) . '/../tea/Tea.php';
$config = require dirname(__FILE__) . '/protected/config/main.php';
require_once($bootstrap);
Tea::run($config);

echo '<p /> running time: ';
echo microtime(true) - APP_BEGIN_TIME . ' sec';

$usage = (int)(memory_get_usage()/1024);
echo "<br /><span style='color:red'>memory used {$usage} kb.</span>";