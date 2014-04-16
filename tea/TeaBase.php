<?php

/**
 * TBase class file
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package system
 */
defined('APP_BEGIN_TIME') or define('APP_BEGIN_TIME', microtime(true));
defined('TEA_PATH') or define('TEA_PATH', dirname(__FILE__));

class TBase {

    public static function run($config = array()) {

    }

    public static function import() {

    }

    public static function autoload($className) {

    }

    public static function getRouter() {

    }

}

// register autoload
spl_autoload_register(array('TBase', 'autoload'));