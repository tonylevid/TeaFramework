<?php

require dirname(__FILE__) . DIRECTORY_SEPARATOR . 'helper' . DIRECTORY_SEPARATOR . 'ArrayHelper.php';

/**
 * TeaBase类文件。
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
defined('APP_PROTECTED') or define('APP_PROTECTED', 'protected');
defined('TEA_PATH') or define('TEA_PATH', dirname(__FILE__));

class TeaBase {

    /**
     * Tea配置数组。
     * @var array
     */
    public static $config = array();

    /**
     * Tea原配置数组（即合并后的数组，不受动态配置影响）。
     * @var array
     */
    public static $originalConfig = array();
    
    /**
     * 当前TeaRequest类实例。
     * @var TeaRequest
     */
    public static $request;

    /**
     * 所有 模块 => 路径 映射数组。
     * @var array
     */
    public static $moduleMap = array();

    /**
     * 已加载 类 => 文件 映射数组。
     * @var array
     */
    public static $importMap = array();
    
    /**
     * 当前项目入口TeaRouter类实例。
     * @var TeaRouter
     */
    private static $_routerInstance;

    /**
     * 当前运行期所适配的TeaDbConnection子类实例。
     * @var TeaDbConnection
     */
    private static $_connection;

    /**
     * 数据库连接类型 => 数据库连接类 映射数组。
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
     * 运行程序。
     * @param array $config 用户配置数组。
     * @param array $routeArgs 手动设置的路由参数。
     */
    public static function run($config = array(), $routeArgs = array()) {
        try {
            self::init($config);
            self::getRouter()->route($routeArgs);
            defined('APP_END_TIME') or define('APP_END_TIME', microtime(true));
            defined('APP_END_MEM') or define('APP_END_MEM', memory_get_usage());
            defined('APP_USED_TIME') or define('APP_USED_TIME', APP_END_TIME - APP_BEGIN_TIME);
            defined('APP_USED_MEM') or define('APP_USED_MEM', APP_END_MEM - APP_BEGIN_MEM);
        } catch (Exception $exception) {
            $exceptionFile = self::getConfig('TeaBase.exceptionFile');
            $tryExcptFile = self::aliasToPath($exceptionFile) . '.php';
            if (is_file($tryExcptFile)) {
                $exceptionFile = $tryExcptFile;
            }
            $errorPageUrl = self::getConfig('TeaBase.errorPageUrl');
            if (!empty($exceptionFile) && is_file($exceptionFile)) {
                include $exceptionFile;
            } else if (!empty($errorPageUrl)) {
                self::getRouter()->getController()->redirect($errorPageUrl);
            } else {
                echo $exception->getMessage();
                echo "\n";
                echo $exception->getTraceAsString();
            }
        }
    }

    /**
     * 运行程序并支持cli模式。
     * @param array $config 用户配置数组。
     * @param array $routeArgs 手动设置的路由参数。
     */
    public static function runConsole($config = array(), $routeArgs = array()) {
        if (php_sapi_name() === 'cli') {
            global $argv;
            $routeArgs = array();
            if (isset($argv) && is_array($argv)) {
                array_shift($argv);
                $routeArgs = $argv;
            }
        }
        self::run($config, $routeArgs);
    }
    
    /**
     * 运行组件。
     * @param array $routeArgs 手动设置的路由参数。
     */
    public static function runComponent($routeArgs) {
        $entryRouter = clone self::getRouter();
        self::getRouter()->route($routeArgs);
        self::$_routerInstance = $entryRouter;
    }

    /**
     * Tea框架初始化。
     * @param array $config 用户配置数组。
     */
    public static function init($config = array()) {        
        session_start();
        self::clear();
        self::setCharset();
        self::$config = ArrayHelper::mergeArray(self::getTeaBaseConfig(), $config);
        self::$originalConfig = self::$config;
        self::setModuleMap();
        self::setAutoImport();
        self::$request = Tea::loadLib('TeaRequest');
    }
    
    /**
     * 清空TeaBase成员。
     */
    protected static function clear() {
        self::$config = array();
        self::$originalConfig = array();
        self::$request = null;
        self::$moduleMap = array();
        self::$importMap = array();
        self::$_routerInstance = null;
        self::$_connection = null;
    }

    /**
     * 导入一个类或者一个文件夹下的所有类（不会递归导入）。
     * 在运行期间不能导入类名相同的文件。
     * 如果$forceImport为true，则可以导入非类文件。
     * @param string $alias 圆点记法别名。别名可使用配置项TeaBase.pathAliasMap中的别名，如system.lib.*。
     * @param bool $forceImport 是否立即导入，默认为false，false为惰性加载。注意：$forceImport为true时，如果多次导入相同文件，可能会照成页面为空。
     * @return bool
     * @throws TeaException
     */
    public static function import($alias, $forceImport = false) {
        $files = self::aliasToFiles($alias);
        if (!empty($files)) {
            foreach ($files as $file) {
                if (!is_file($file)) {
                    throw new TeaException("Imported file '{$file}' not exists.");
                }
                if ($forceImport) {
                    require $file;
                } else {
                    $className = basename($file, '.php');
                    if (isset(self::$importMap[$className])) {
                        throw new TeaException("Cannot redeclare class '{$className}' in '{$file}'.");
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
     * 自动加载。
     * @param string $className 自动加载类名。
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
     * 加载类并获取实例。
     * @param string $name 圆点记法别名。如果第一个别名不是TeaBase.pathAliasMap中的别名，则会根据当前运行期自动判断。
     * @param array $args 类构造函数的参数，默认为空数组array()。
     * @return mixed 成功则返回类实例，失败则返回false。
     */
    public static function load($name, $args = array()) {
        $nameParts = explode('.', $name);
        $className = array_pop($nameParts);
        if (!array_key_exists($className, self::$importMap)) {
            $importAlias = self::getProperLoadName($name);
            self::import($importAlias);
        }
        if (class_exists($className)) {
            $rfc = new ReflectionClass($className);
            return $rfc->newInstanceArgs($args);
        }
        return false;
    }
    
    /**
     * 根据路由自动获取合适的加载类别名，用于self::import()或者self::aliasToFiles()。
     * @param string $name 圆点记法别名。如果第一个别名不是TeaBase.pathAliasMap中的别名，则会根据当前运行期自动判断。
     * @return string 合适的加载类别名。
     */
    public static function getProperLoadName($name) {
        if (self::isLoadNameAbsolute($name)) {
            $importAlias = $name;
        } else {
            $moduleName = self::getRouter()->getModuleName();
            $importAlias = empty($moduleName) ? 'protected.' . $name : "module.{$moduleName}." . $name;
        }
        return $importAlias;
    }

    /**
     * 加载帮组类并获取实例。
     * @param string $name 圆点记法别名，请省略helper文件夹。例如：'array' 或者 'protected.array'。
     * @param array $args 类构造函数的参数，默认为空数组array()。
     * @return mixed 成功则返回类实例，失败则返回false。
     */
    public static function loadHelper($name, $args = array()) {
        $nameParts = explode('.', $name);
        $lastKey = count($nameParts) - 1;
        $nameParts[$lastKey] = ucfirst($nameParts[$lastKey]);
        if (self::isLoadNameAbsolute($name)) {
            array_splice($nameParts, -1, 0, 'helper');
            return self::load(implode('.', $nameParts) . 'Helper', $args);
        }
        $name = implode('.', $nameParts);
        return self::load("helper.{$name}Helper", $args);
    }

    /**
     * 加载类库并获取实例。
     * @param string $name 圆点记法别名，请省略lib文件夹。例如：'pager' 或者 'protected.pager'。
     * @param array $args 类构造函数的参数，默认为空数组array()。
     * @return mixed 成功则返回类实例，失败则返回false。
     */
    public static function loadLib($name, $args = array()) {
        $nameParts = explode('.', $name);
        $lastKey = count($nameParts) - 1;
        $nameParts[$lastKey] = ucfirst($nameParts[$lastKey]);
        if (self::isLoadNameAbsolute($name)) {
            array_splice($nameParts, -1, 0, 'lib');
            return self::load(implode('.', $nameParts), $args);
        }
        $name = implode('.', $nameParts);
        return self::load("lib.{$name}", $args);
    }
    
    /**
     * 加载第三方类库并获取实例。
     * @param string $name 圆点记法别名，请省略vendor文件夹。例如：'curl' 或者 'protected.curl'。
     * @param array $args 类构造函数的参数，默认为空数组array()。
     * @return mixed 成功则返回类实例，失败则返回false。
     */
    public static function loadVendor($name, $args = array()) {
        $nameParts = explode('.', $name);
        $lastKey = count($nameParts) - 1;
        $nameParts[$lastKey] = ucfirst($nameParts[$lastKey]);
        if (self::isLoadNameAbsolute($name)) {
            array_splice($nameParts, -1, 0, 'vendor');
            return self::load(implode('.', $nameParts), $args);
        }
        $name = implode('.', $nameParts);
        return self::load("vendor.{$name}", $args);
    }

    /**
     * 加载模型类并获取实例。
     * 如果无法找到此模型类, 则会根据名称（此名称被视为表名）创建一个TeaTempModel实例。
     * @param string $name 圆点记法别名，请省略model文件夹。例如：'test' 或者 'protected.test'。
     * @param array $args 类构造函数的参数，默认为空数组array()。
     * @return mixed 成功则返回类实例，失败则返回false。
     */
    public static function loadModel($name, $args = array()) {
        $nameParts = explode('.', $name);
        $lastKey = count($nameParts) - 1;
        $nameParts[$lastKey] = ucfirst($nameParts[$lastKey]);
        if (self::isLoadNameAbsolute($name)) {
            array_splice($nameParts, -1, 0, 'model');
            $loadName = implode('.', $nameParts) . 'Model';
        } else {
            $ucName = implode('.', $nameParts);
            $loadName = "model.{$ucName}Model";
        }
        $aliasFiles = self::aliasToFiles(self::getProperLoadName($loadName));
        $importedFile = array_pop($aliasFiles);
        if (is_file($importedFile)) {
            return self::load($loadName, $args);
        } else {
            return new TeaTempModel($name);
        }
    }

    /**
     * 获取当前运行期TeaRouter类实例。
     * @return TeaRouter 当前运行期TeaRouter类实例。
     */
    public static function getRouter() {
        if (!self::$_routerInstance instanceof TeaRouter) {
            self::$_routerInstance = new TeaRouter();
        }
        return self::$_routerInstance;
    }

    /**
     * 创建链接。
     * @param string $route 路由字符串。
     * @param array $queries $_GET相关参数。
     * @param string $anchor 链接后面的锚点，默认为null。
     * @return string 生成的链接。
     */
    public static function createUrl($route = '', $queries = array(), $anchor = null) {
        $request = self::$request;
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
        $returnUrl = !empty($url) ? $request->getBaseUri() . '/' . $url : '/';
        return $returnUrl;
    }
    
    /**
     * 获取生成请求过滤条件的完整url用于TeaModel::withRequestFilter()。
     * @param mixed $criteria TeaDbCriteria类实例或者条件数组。
     * @return string
     */
    public static function getModelFilterUrl($criteria = null, $route = '') {
        if ($criteria instanceof TeaDbCriteria) {
            $criteriaArr = $criteria->criteriaArr;
        } else if (is_array($criteria) && !empty($criteria)) {
            $criteriaArr = $criteria;
        } else {
            $criteriaArr = array();
        }
        $filterKey = Tea::getConfig('TeaModel.requestFilterKey');
        $filterParam = array(
            $filterKey => MiscHelper::encodeArr($criteriaArr)
        );
        $queries = Tea::$request->getQuery();
        $filterQueries = array_merge($queries, $filterParam);
        $queryStr = http_build_query($filterQueries);
        if (empty($route)) {
            $basePathUrl = Tea::$request->getBasePathUrl();
        } else {
            $basePathUrl = Tea::$request->getBaseUrl() . '/' . $route;
        }
        $nowUrl = $basePathUrl . ($queryStr ? '?' . $queryStr : null);
        return $nowUrl;
    }

    /**
     * 根据数据库连接信息获取适配的TeaDbConnection子类实例，并当配置autoConnect是true时自动连接。
     * @param mixed $connInfo 字符串或数组，默认为配置项TeaModel.defaultConnection。如果为字符串，那么它应当是配置项TeaModel.connections中的一项。
     * @return TeaDbConnection 适配的TeaDbConnection子类实例。
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
            self::$_connection = new $connClass($connInfo);
            if ($autoConnect) {
                self::$_connection->connect();
            }
        } else {
            throw new TeaDbException("TeaBase could not determine the driver type, check your dsn '{$dsn}'.");
        }
        return self::$_connection;
    }

    /**
     * 获取当前运行期所适配的TeaDbQuery子类实例。
     * @return TeaDbQuery 适配的TeaDbQuery子类实例。
     */
    public static function getDbQuery() {
        return self::getDbConnection()->getQuery();
    }

    /**
     * 获取当前运行期所适配的TeaDbSchema子类实例。
     * @return TeaDbSchema 适配的TeaDbSchema子类实例。
     */
    public static function getDbSchema() {
        return self::getDbConnection()->getSchema();
    }

    /**
     * 获取当前运行期所适配的TeaDbSqlBuilder子类实例。
     * @return TeaDbSqlBuilder 适配的TeaDbSqlBuilder子类实例。
     */
    public static function getDbSqlBuilder() {
        return self::getDbConnection()->getSqlBuilder();
    }

    /**
     * 获取当前运行期所适配的TeaDbCriteria子类实例。
     * @return TeaDbCriteria 适配的TeaDbCriteria子类实例。
     */
    public static function getDbCriteria() {
        return self::getDbConnection()->getCriteria();
    }

    /**
     * 根据路径别名获取真实路径。
     * @param string $alias 路径别名。
     * @return string 真实路径。
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
     * 根据路径别名获取真实路径下的文件。
     * @param string $alias 路径别名。
     * @return mixed 返回文件路径数组，如果没有文件或失败则返回false。
     */
    public static function aliasToFiles($alias) {
        $path = self::aliasToPath($alias);
        $last = basename($path);
        $files = array();
        if ($last === '*') {
            $files = glob($path . '.php');
        } else {
            $files = array($path . '.php');
        }
        if (is_array($files) && !empty($files)) {
            return $files;
        }
        return false;
    }

    /**
     * 通过圆点记法字符串获取运行期配置。
     * @param string $nodeStr 圆点记法字符串，默认为null。如果此项为空，此方法将返回整个配置数组。
     * @param bool $isOriginal 是否返回原始（即初始化后的）配置，默认为false。
     * @return mixed 成功则返回配置的值，失败则返回false。
     */
    public static function getConfig($nodeStr = null, $isOriginal = false) {
        $config = $isOriginal ? self::$originalConfig : self::$config;
        if (empty($nodeStr)) {
            return $config;
        } else {
            $nodes = explode('.', $nodeStr);
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
     * 通过圆点记法字符串设置运行期配置。
     * @param string $nodeStr 圆点记法字符串。
     * @param mixed $value 配置项值。
     * @return mixed 返回设置的配置项值。
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
     * 设置类通用配置项，用于将类配置导入Tea框架。
     * @param string $className 类名称，你可以在当前类使用 __CLASS__ 。
     * @param string $configParam 类配置项静态变量名，默认为'config'。
     */
    public static function setClassConfig($className, $configParam = 'config') {
        $classConfig = self::getConfig($className);
        if (is_array($classConfig) && !empty($classConfig)) {
            $className::$$configParam = ArrayHelper::mergeArray($className::$$configParam, $classConfig);
        }
        self::setConfig($className, $className::$$configParam);
    }
    
    /**
     * 查看别名是否为配置项TeaBase.pathAliasMap中的别名。
     * @param string $name 别名
     * @return bool
     */
    public static function isLoadNameAbsolute($name) {
        $nameParts = explode('.', $name);
        $first = array_shift($nameParts);
        if (array_key_exists($first, self::getConfig('TeaBase.pathAliasMap'))) {
            return true;
        }
        return false;
    }
    
    /**
     * 设置全局编码。
     */
    protected static function setCharset() {
        mb_internal_encoding('utf-8');
        if (function_exists('ini_set')) {
            ini_set('default_charset', 'utf-8');
        }
        header('Content-Type:text/html; charset=utf-8');
    }

    /**
     * 设置模块映射。
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
     * 设置自动导入。
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
     * 获取TeaBase默认配置。
     * @return array
     */
    protected static function getTeaBaseConfig() {
        return array(
            'TeaBase' => array(
                'pathAliasMap' => array(
                    'app' => APP_PATH,
                    'system' => TEA_PATH,
                    'protected' => APP_PATH . DIRECTORY_SEPARATOR . APP_PROTECTED,
                    'module' => APP_PATH . DIRECTORY_SEPARATOR . APP_PROTECTED . DIRECTORY_SEPARATOR . 'module',
                ),
                'autoImport' => array(
                    'system.base.*',
                    'system.helper.*',
                    'system.lib.*',
                    'system.lib.cache.*',
                    'system.lib.pager.*',
                    'system.vendor.*',
                    'system.db.rdbms.*',
                    'system.db.rdbms.mssql.*',
                    'system.db.rdbms.mysql.*',
                    'system.db.rdbms.oci.*',
                    'system.db.rdbms.odbc.*',
                    'system.db.rdbms.pgsql.*',
                    'system.db.rdbms.sqlite.*',
                ),
                'privateHashKey' => 'TeaFrameworkRocks',
                'exceptionFile' => null,
                'errorPageUrl' => null
            )
        );
    }

}

spl_autoload_register(array('TeaBase', 'autoload'));