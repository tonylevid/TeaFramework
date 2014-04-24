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

    public static $moduleMap = array();

    public static $importMap = array();

    private static $_autoLoadAlias;

    private static $_routerInstance;

    public static function run($config = array()) {
        self::$config = ArrayHelper::mergeArray($config, self::getTeaBaseConfig());
        self::init();
        self::getRouter()->route();
    }

    public static function init() {
        self::setModuleMap();
        self::setAutoImport();
    }

    protected static function setModuleMap() {
        $modulePaths = glob(self::aliasToPath('module.*'), GLOB_ONLYDIR);
        if (is_array($modulePaths) && !empty($modulePaths)) {
            foreach ($modulePaths as $modulePath) {
                $moduleName = basename($modulePath);
                self::$moduleMap[$moduleName] = $modulePath;
            }
        }
    }

    protected static function setAutoImport() {
        // import core classes and autoImport in main config.
        $defaultLoads = self::getConfig('TeaBase.autoImport');
        if (is_array($defaultLoads) && !empty($defaultLoads)) {
            foreach ($defaultLoads as $alias) {
                self::import($alias);
            }
        }
    }

    /**
     * Import a class or directory.
     * @param string $alias Dot notation alias.
     * @param bool $forceImport Whether to import immediately.
     * @return bool
     */
    public static function import($alias, $forceImport = false) {
        $path = self::aliasToPath($alias);
        $last = basename($path);
        $files = array();
        if ($last === '*') {
            $files = glob($path . '.php');
        } else {
            $files = array($path . '.php');
        }
        if (is_array($files) && !empty($files)) {
            foreach ($files as $file) {
                if ($forceImport) {
                    require $file;
                } else {
                    $className = basename($file, '.php');
                    if (isset(self::$importMap[$className])) {
                        $importedFile = self::$importMap[$className];
                        throw new TeaException("Class '{$className}' has been imported in file '{$importedFile}', check if you have redeclared it.");
                    } else {
                        self::$importMap[$className] = $file;
                    }
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Autoloader
     * @param string $className Autoloaded class name.
     * @return bool
     */
    public static function autoload($className) {
        if (isset(self::$importMap[$className]) && is_file(self::$importMap[$className])) {
            include self::$importMap[$className];
            return true;
        }
        return false;
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
        $pathAliases = self::getConfig('TeaBase.pathAliasMap');
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

    public static function setClassConfig($className, $configParam = 'config') {
        $classConfig = Tea::getConfig($className);
        if (is_array($classConfig) && !empty($classConfig)) {
            $className::$$configParam = ArrayHelper::mergeArray($className::$$configParam, $classConfig);
        }
        Tea::setConfig($className, $className::$$configParam);
    }

    protected static function getTeaBaseConfig() {
        return array(
            'TeaBase' => array(
                'pathAliasMap' => array(
                    'app' => APP_PATH,
                    'system' => TEA_PATH,
                    'protected' => APP_PATH . DIRECTORY_SEPARATOR . 'protected',
                    'module' => APP_PATH . DIRECTORY_SEPARATOR . 'protected' . DIRECTORY_SEPARATOR . 'module',
                ),
                'autoImport' => array(
                    'system.base.*',
                    'system.console.*',
                    'system.helper.*',
                    'system.lib.*',
                    'system.vendor.*',
                    'system.db.rdbms.*',
                    'system.db.rdbms.mssql.*',
                    'system.db.rdbms.mysql.*',
                    'system.db.rdbms.oci.*',
                    'system.db.rdbms.odbc.*',
                    'system.db.rdbms.pgsql.*',
                    'system.db.rdbms.sqlite.*',
                )
            )
        );
    }

}

spl_autoload_register(array('TeaBase', 'autoload'));