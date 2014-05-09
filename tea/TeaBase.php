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
defined('APP_PATH') or define('APP_PATH', dirname($_SERVER['SCRIPT_FILENAME']) . '/');
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
    public static function run($config = array()) {
        self::$config = ArrayHelper::mergeArray($config, self::getTeaBaseConfig());
        self::init();
        self::getRouter()->route();
        defined('APP_END_TIME') or define('APP_END_TIME', microtime(true));
        defined('APP_END_MEM') or define('APP_END_MEM', memory_get_usage());
    }
    
    /**
     * Tea initialization.
     */
    public static function init() {
        self::setModuleMap();
        self::setAutoImport();
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
                        throw new TeaException("Class '{$className}' has been imported, check if you have redeclared it. File '{$importedFile}'.");
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

    public static function load($name, $args = array()) {
        $nameParts = explode('.', $name);
        $className = array_pop($nameParts);
        if (!array_key_exists($className, self::$importMap)) {
            $first = array_shift($nameParts);
            if (array_key_exists($first, self::getConfig('TeaBase.pathAliasMap'))) {
                $loadName = $name;
            } else {
                $router = self::getRouter();
                $moduleName = $router->getModuleName();
                $loadName = empty($moduleName) ? 'protected.' . $name : "module.{$moduleName}." . $name;
            }
            self::import($loadName);
        }
        $rfc = new ReflectionClass($className);
        return $rfc->newInstanceArgs($args);
    }

    public static function loadHelper($name, $args = array()) {
        $name = ucfirst($name);
        return self::load("helper.{$name}Helper", $args);
    }

    public static function loadLib($name, $args = array()) {
        $name = ucfirst($name);
        return self::load("lib.{$name}", $args);
    }

    public static function loadModel($name, $args = array()) {
        $name = ucfirst($name);
        return self::load("model.{$name}Model", $args);
    }

    public static function getModel($tableName) {
        return new TeaTempModel($tableName);
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
     * Get proper TeaDbConnection subclass instance and connect if autoConnect is true.
     * @param mixed $connInfo String or array, defaults to string 'default'. If string, it should be the connection info group key in main config model node.
     * @return TeaDbConnection Proper TeaDbConnection subclass instance.
     */
    public static function getDbConnection($connInfo = null) {
        if (empty($connInfo) && isset(self::$_connection)) {
            return self::$_connection;
        }
        empty($connInfo) && ($connInfo = self::getConfig('TeaModel.defaultConnection'));
        is_string($connInfo) && ($connInfo = self::getConfig("TeaModel.connections.{$connInfo}"));
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
                $autoConnect && self::$_connection->connect();
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
     * Get proper TeaDbCriteriaBuilder subclass instance if autoConnect is true.
     * @return TeaDbCriteriaBuilder Proper TeaDbCriteriaBuilder subclass instance.
     */
    public static function getDbCriteriaBuilder() {
        return self::getDbConnection()->getCriteriaBuilder();
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