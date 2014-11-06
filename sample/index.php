<?php
header('Content-type:text/html;charset=utf-8;');

$boot = dirname(__FILE__) . '/../tea/Tea.php';
$config = require dirname(__FILE__) . '/protected/config/main.php';
require_once($boot);

Tea::runConsole($config);

echo '<p /> Running Time: ';
echo APP_USED_TIME . ' sec';

$usage = (int)((APP_USED_MEM) / 1024);
echo "<br /><span style='color:red'>App Used Memory {$usage} kb.</span>";