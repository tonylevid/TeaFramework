<?php

/**
 * TeaBase class file
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package system
 */
defined('APP_BEGIN_TIME') or define('APP_BEGIN_TIME', microtime(true));
defined('TEA_PATH') or define('TEA_PATH', dirname(__FILE__));

class TeaBase {

    public static $config = array();

    private static $_routerInstance;

    public static function run($config = array()) {
        self::$config = $config;
        self::init();
        self::getRouter()->route();
    }

    public static function init() {

    }

    public static function import() {

    }

    public static function autoload($className) {

    }

    public static function getRouter() {
        $routeInfo = array();
        if (!self::$_routerInstance instanceof TeaRouter) {
            self::$_routerInstance = new TeaRouter($routeInfo);
        }
        return self::$_routerInstance;
    }

    public static function getConfig($key, $prop = null) {
        $cfg = self::$config[$key];
        if (is_string($prop) && !empty($prop)) {
            return $cfg[$prop];
        }
        return is_array($cfg) && !empty($cfg) ? $cfg : array();
    }

    public static function setInstProps($instance, $properties = array()) {
        if (empty($properties)) {
            $properties = self::getConfig(get_class($instance));
        }
        foreach ($properties as $property => $value) {
            if (property_exists($instance, $property)) {
                $instance->{$property} = $value;
            }
        }
    }

}

// register autoload
spl_autoload_register(array('TeaBase', 'autoload'));