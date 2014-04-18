<?php

require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'helper' . DIRECTORY_SEPARATOR . 'ArrayHelper.php';

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
defined('APP_PATH') or define('APP_PATH', dirname(__FILE__));
defined('TEA_PATH') or define('TEA_PATH', dirname(__FILE__));

class TeaBase {

    public static $config = array();

    public static $moduleList = array();

    private static $_routerInstance;

    public static function run($config = array()) {
        self::$config = ArrayHelper::mergeArray(self::getTeaBaseConfig(), $config);
        var_dump(self::$config);
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
        if (!self::$_routerInstance instanceof TeaRouter) {
            self::$_routerInstance = new TeaRouter();
        }
        return self::$_routerInstance;
    }

    /**
     * Convert alias to path.
     * @param string $alias Alias string.
     * @return string Path string.
     */
    public static function aliasToPath($alias) {
        $pathAliases = self::getConfig('autoload.pathAliasMap');
        $parts = explode('.', $alias);
        foreach ($parts as &$v) {
            if (array_key_exists($v, $pathAliases)) {
                $v = $pathAliases[$v];
            }
        }
        unset($v);
        $path = implode(DIRECTORY_SEPARATOR, $parts);
        return $path;
    }

    /**
     * Get config by dot notation.
     * @param string $nodeStr Dot notation path, defaults to null. If empty, function will return full config array.
     * @return mixed Return config value on success, false on failure.
     */
    public static function getConfig($nodeStr = null) {
        if (empty($nodeStr)) {
            return self::$config;
        } else {
            $nodes = explode('.', $nodeStr);
            $config = self::$config;
            foreach ($nodes as $node) {
                if (isset($config[$node])) {
                    $config = $config[$node];
                } else {
                    return false;
                }
            }
            return $config;
        }
    }

    /**
     * Set config by dot notation.
     * @param string $nodeStr Dot notation path.
     * @param mixed $value Value to be set.
     * @return mixed Return the value has been set.
     */
    public static function setConfig($nodeStr, $value) {
        $nodes = explode('.', $nodeStr);
        $config =& self::$config;
        foreach ($nodes as $node) {
            if (!isset($config[$node])) {
                $config[$node] = array();
            }
            $config =& $config[$node];
        }
        return $config = $value;
    }

    private static function getTeaBaseConfig() {
        return array(
            'TeaBase' => array(
                'pathAliasMap' => array(
                    'app' => APP_PATH,
                    'system' => TEA_PATH,
                    'protected' => APP_PATH . DIRECTORY_SEPARATOR . 'protected',
                    'public' => APP_PATH . DIRECTORY_SEPARATOR . 'public',
                    'cache' => APP_PATH . DIRECTORY_SEPARATOR . 'protected' . DIRECTORY_SEPARATOR . 'cache',
                    'config' => APP_PATH . DIRECTORY_SEPARATOR . 'protected' . DIRECTORY_SEPARATOR . 'config',
                    'controller' => APP_PATH . DIRECTORY_SEPARATOR . 'protected' . DIRECTORY_SEPARATOR . 'controller',
                    'data' => APP_PATH . DIRECTORY_SEPARATOR . 'protected' . DIRECTORY_SEPARATOR . 'data',
                    'helper' => APP_PATH . DIRECTORY_SEPARATOR . 'protected' . DIRECTORY_SEPARATOR . 'helper',
                    'lib' => APP_PATH . DIRECTORY_SEPARATOR . 'protected' . DIRECTORY_SEPARATOR . 'lib',
                    'log' => APP_PATH . DIRECTORY_SEPARATOR . 'protected' . DIRECTORY_SEPARATOR . 'log',
                    'model' => APP_PATH . DIRECTORY_SEPARATOR . 'protected' . DIRECTORY_SEPARATOR . 'model',
                    'module' => APP_PATH . DIRECTORY_SEPARATOR . 'protected' . DIRECTORY_SEPARATOR . 'module',
                    'vendor' => APP_PATH . DIRECTORY_SEPARATOR . 'protected' . DIRECTORY_SEPARATOR . 'vendor',
                    'view' => APP_PATH . DIRECTORY_SEPARATOR . 'protected' . DIRECTORY_SEPARATOR . 'view',
                ),
                'autoImport' => array(

                )
            )
        );
    }

}

// register autoload
spl_autoload_register(array('TeaBase', 'autoload'));