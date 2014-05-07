<?php
header('Content-type:text/html;charset=utf-8;');

define('APP_PATH', dirname(__FILE__));
$boot = dirname(__FILE__) . '/../tea/Tea.php';
$config = require dirname(__FILE__) . '/protected/config/main.php';
require_once($boot);
Tea::run($config);

echo '<p /> Running Time: ';
echo APP_END_TIME - APP_BEGIN_TIME . ' sec';

$usage = (int)((APP_END_MEM - APP_BEGIN_MEM) / 1024);
echo "<br /><span style='color:red'>App Used Memory {$usage} kb.</span>";