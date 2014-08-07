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
defined('APP_BEGIN_MEM') or define('APP_BEGIN_MEM', memory_get_usage());
defined('APP_PATH') or define('APP_PATH', str_replace('/', DIRECTORY_SEPARATOR, dirname($_SERVER['SCRIPT_FILENAME'])));
defined('TEA_PATH') or define('TEA_PATH', dirname(__FILE__));

class TeaBase {
    
    /**
     * Tea config array.
     * @var array
     */
    public static $config = array();
    
    /**
     * All module => path map.
     * @var array
     */
    public static $moduleMap = array();
    
    /**
     * Imported class => file map.
     * @var array
     */
    public static $importMap = array();

    /**
     * TeaRouter instance.
     * @var TeaRouter
     */
    private static $_routerInstance;

    /**
     * Proper TeaDbConnection subclass instance.
     * @var TeaDbConnection
     */
    private static $_connection;

    /**
     * Connection type to connection class map.
     * @var array
     */
    private static $_connectionTypeMap = array(
        'mysql' => 'TeaMysqlConnection',
        'mssql' => 'TeaMssqlConnection',
        'dblib' => 'TeaMssqlConnection',
        'sqlsrv' => 'TeaMssqlConnection',
        'oci' => 'TeaOciConnection',
        'pgsql' => 'TeaPgsqlConnection',
        'sqlite' => 'TeaSqliteConnection',
        'sqlite2' => 'TeaSqliteConnection',
        'odbc' =>  'TeaOdbcConnection',
        'mongodb' => 'TeaMongodbConnection'
    );
    
    /**
     * Run the application.
     * @param array $config User's config array.
     */
    public static function run($config = array(), $routeArgs = array()) {
        self::init($config);
        self::getRouter()->route($routeArgs);
        defined('APP_END_TIME') or define('APP_END_TIME', microtime(true));
        defined('APP_END_MEM') or define('APP_END_MEM', memory_get_usage());
        defined('APP_USED_TIME') or define('APP_USED_TIME', APP_END_TIME - APP_BEGIN_TIME);
        defined('APP_USED_MEM') or define('APP_USED_MEM', APP_END_MEM - APP_BEGIN_MEM);
    }

    /**
     * Run the console application.
     * @param array $config User's config array.
     */
    public static function runConsole($config = array()) {
        global $argv;
        $routeArgs = array();
        if (isset($argv) && is_array($argv)) {
            array_shift($argv);
            $routeArgs = $argv;
        }
        self::run($config, $routeArgs);
    }
    
    /**
     * Tea initialization.
     * @param array $config User's config array.
     */
    public static function init($config = array()) {
        self::$config = ArrayHelper::mergeArray($config, self::getTeaBaseConfig());
        self::setModuleMap();
        self::setAutoImport();
    }

    /**
     * Import a class or directory.
     * @param string $alias Dot notation alias.
     * @param bool $forceImport Whether to import immediately.
     * @return bool
     * @throws TeaException
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
                        throw new TeaException("Cannot redeclare class '{$className}' in '{$importedFile}'.");
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

    /**
     * Load class and get the instance.
     * @param string $name Dot notation alias. You can use relative dot notation path.
     * @param array $args Class's constructor parameters, defaults to array().
     * @return mixed Return class instance on success, false on failure.
     */
    public static function load($name, $args = array()) {
        $nameParts = explode('.', $name);
        $className = array_pop($nameParts);
        if (!array_key_exists($className, self::$importMap)) {
            if (self::isLoadNameAbsolute($name)) {
                $loadName = $name;
            } else {
                $router = self::getRouter();
                $moduleName = $router->getModuleName();
                $loadName = empty($moduleName) ? 'protected.' . $name : "module.{$moduleName}." . $name;
            }
            self::import($loadName);
        }
        if (class_exists($className)) {
            $rfc = new ReflectionClass($className);
            return $rfc->newInstanceArgs($args);
        }
        return false;
    }

    /**
     * Load helper and get the instance.
     * @param string $name Dot notation alias. For example: 'array' or 'protected.helper.array'.
     * @param array $args Helper's constructor parameters, defaults to array().
     * @return mixed Return Helper instance on success, false on failure.
     */
    public static function loadHelper($name, $args = array()) {
        $nameParts = explode('.', $name);
        $lastKey = count($nameParts) - 1;
        $nameParts[$lastKey] = ucfirst($nameParts[$lastKey]);
        if (self::isLoadNameAbsolute($name)) {
            array_splice($nameParts, -1, 0, 'helper');
            return self::load(implode('.', $nameParts) . 'Helper');
        }
        $name = implode('.', $nameParts);
        return self::load("helper.{$name}Helper", $args);
    }

    /**
     * Load library and get the instance.
     * @param string $name Dot notation alias. For example: 'pager' or 'protected.lib.pager'.
     * @param array $args Library's constructor parameters, defaults to array().
     * @return mixed Return Library instance on success, false on failure.
     */
    public static function loadLib($name, $args = array()) {
        $nameParts = explode('.', $name);
        $lastKey = count($nameParts) - 1;
        $nameParts[$lastKey] = ucfirst($nameParts[$lastKey]);
        if (self::isLoadNameAbsolute($name)) {
            array_splice($nameParts, -1, 0, 'lib');
            return self::load(implode('.', $nameParts));
        }
        $name = implode('.', $nameParts);
        return self::load("lib.{$name}", $args);
    }

    /**
     * Load model and get the instance.
     * If could not load the model class, this will try to create a TeaTempModel instance.
     * @param string $name Dot notation alias. For example: 'test' or 'protected.model.test'.
     * @param array $args Model's constructor parameters, defaults to array().
     * @return mixed Return Model instance on success, false on failure.
     */
    public static function loadModel($name, $args = array()) {
        $nameParts = explode('.', $name);
        $lastKey = count($nameParts) - 1;
        $nameParts[$lastKey] = ucfirst($nameParts[$lastKey]);
        if (self::isLoadNameAbsolute($name)) {
            array_splice($nameParts, -1, 0, 'model');
            $model = self::load(implode('.', $nameParts) . 'Model');
        } else {
            $ucName = implode('.', $nameParts);
            $model = self::load("model.{$ucName}Model", $args);
        }
        if ($model instanceof TeaModel) {
            return $model;
        } else {
            return new TeaTempModel($name);
        }
    }
    
    /**
     * Get current running TeaRouter instance.
     * @return TeaRouter Current running TeaRouter instance.
     */
    public static function getRouter() {
        if (!self::$_routerInstance instanceof TeaRouter) {
            self::$_routerInstance = new TeaRouter();
        }
        return self::$_routerInstance;
    }

    /**
     * Create url.
     * @param string $route Url route string.
     * @param array $queries Parameters of $_GET after route.
     * @param string $anchor Anchor at the end of url.
     * @return string Generated url string.
     */
    public static function createUrl($route = '', $queries = array(), $anchor = null) {
        $request = self::loadLib('TeaRequest');
        $route = rtrim(ltrim($route, '/'), '/');
        $routeMode = self::getConfig('TeaRouter.routeMode');
        $routeModeGetName = self::getConfig('TeaRouter.routeModeGetName');
        $routeUrlSuffix = self::getConfig('TeaRouter.urlSuffix');
        $mergedQueries = !empty($route) ? array_merge(array($routeModeGetName => $route), $queries) : $queries;
        $queryStr = http_build_query($queries);
        $mergedQueryStr = http_build_query($mergedQueries);
        $urlQueryStr = !empty($queryStr) ? '?' . $queryStr : '';
        $urlMergedQueryStr = !empty($mergedQueryStr) ? '?' . $mergedQueryStr : '';
        switch ($routeMode) {
            case 'auto':
                $queryPathinfo = $request->getQuery($routeModeGetName);
                if (!empty($queryPathinfo)) {
                    $url = $urlMergedQueryStr . $anchor;
                } else {
                    $url = $route . $routeUrlSuffix . $urlQueryStr . $anchor;
                }
                break;
            case 'path':
                $url = $route . $routeUrlSuffix . $urlQueryStr . $anchor;
                break;
            case 'get':
                $url = $urlMergedQueryStr . $anchor;
                break;
            default:
                $url = '';
                break;
        }
        $returnUrl = !empty($url) ? $request->getBaseUri() . '/' . $url : '';
        return $returnUrl;
    }
    
    /**
     * Get proper TeaDbConnection subclass instance and connect if autoConnect is true.
     * @param mixed $connInfo String or array, defaults to string 'default'. If string, it should be the connection info group key in main config model node.
     * @return TeaDbConnection Proper TeaDbConnection subclass instance.
     * @throws TeaDbException
     */
    public static function getDbConnection($connInfo = null) {
        if (empty($connInfo) && isset(self::$_connection)) {
            return self::$_connection;
        }
        if (empty($connInfo)) {
            $connInfo = self::getConfig('TeaModel.defaultConnection');
        }
        if (is_string($connInfo)) {
            $connInfo = self::getConfig("TeaModel.connections.{$connInfo}");
        }
        $dsn = $driverType = $autoConnect = null;
        if (is_array($connInfo)) {
            $dsn = isset($connInfo['dsn']) ? $connInfo['dsn'] : null;
            $driverType = preg_match('/^(\w+):/', $dsn, $matches) ? $matches[1] : null;
            $autoConnect = isset($connInfo['autoConnect']) && $connInfo['autoConnect'] ? true : false;
        }
        if (isset(self::$_connectionTypeMap[$driverType])) {
            $connClass = self::$_connectionTypeMap[$driverType];
            if (!self::$_connection instanceof $connClass) {
                self::$_connection = new $connClass($connInfo);
                if ($autoConnect) {
                    self::$_connection->connect();
                }
            }
        } else {
            throw new TeaDbException("TeaBase could not determine the driver type, check your dsn '{$dsn}'.");
        }
        return self::$_connection;
    }

    /**
     * Get proper TeaDbQuery subclass instance if autoConnect is true.
     * @return TeaDbQuery Proper TeaDbQuery subclass instance.
     */
    public static function getDbQuery() {
        return self::getDbConnection()->getQuery();
    }

    /**
     * Get proper TeaDbSchema subclass instance if autoConnect is true.
     * @return TeaDbSchema Proper TeaDbSchema subclass instance.
     */
    public static function getDbSchema() {
        return self::getDbConnection()->getSchema();
    }

    /**
     * Get proper TeaDbSqlBuilder subclass instance if autoConnect is true.
     * @return TeaDbSqlBuilder Proper TeaDbSqlBuilder subclass instance.
     */
    public static function getDbSqlBuilder() {
        return self::getDbConnection()->getSqlBuilder();
    }
    
    /**
     * Get proper TeaDbCriteria subclass instance if autoConnect is true.
     * @return TeaDbCriteria Proper TeaDbCriteria subclass instance.
     */
    public static function getDbCriteria() {
        return self::getDbConnection()->getCriteria();
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
    
    /**
     * Set config for class.
     * @param string $className Class name, you can use __CLASS__ in class.
     * @param string $configParam Config variable name, defaults to 'config'.
     */
    public static function setClassConfig($className, $configParam = 'config') {
        $classConfig = self::getConfig($className);
        if (is_array($classConfig) && !empty($classConfig)) {
            $className::$$configParam = ArrayHelper::mergeArray($className::$$configParam, $classConfig);
        }
        self::setConfig($className, $className::$$configParam);
    }
    
    /**
     * Set module map.
     */
    protected static function setModuleMap() {
        $modulePaths = glob(self::aliasToPath('module.*'), GLOB_ONLYDIR);
        if (is_array($modulePaths) && !empty($modulePaths)) {
            foreach ($modulePaths as $modulePath) {
                $moduleName = basename($modulePath);
                self::$moduleMap[$moduleName] = $modulePath;
            }
        }
    }
    
    /**
     * Set auto import.
     */
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
     * Get TeaBase default config.
     * @return array
     */
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
                    'system.helper.*',
                    'system.lib.*',
                    'system.lib.imgborn.*',
                    'system.lib.imgborn.driver.*',
                    'system.lib.imgborn.driver.gd.*',
                    'system.lib.imgborn.driver.imagick.*',
                    'system.lib.pager.*',
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

    /**
     * Check whether the name for load methods is absolute.
     * @param string $name The name for load methods.
     * @return bool
     */
    private static function isLoadNameAbsolute($name) {
        $nameParts = explode('.', $name);
        $first = array_shift($nameParts);
        if (array_key_exists($first, self::getConfig('TeaBase.pathAliasMap'))) {
            return true;
        }
        return false;
    }

}

spl_autoload_register(array('TeaBase', 'autoload'));