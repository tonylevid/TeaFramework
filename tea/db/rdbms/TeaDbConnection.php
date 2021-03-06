<?php

/**
 * TeaDbConnection class file
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.teaframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.teaframework.com/license/
 * @package db
 */
abstract class TeaDbConnection {

    /**
     * Connection information array.
     * @var array
     */
    public $connInfo;

    /**
     * DSN string.
     * @var string
     */
    public $dsn;

    /**
     * Driver type.
     * @var string
     */
    public $driverType;

    /**
     * Database name.
     * @var string
     */
    public $dbname;

    /**
     * Database login username.
     * @var string
     */
    public $username;

    /**
     * Database login password.
     * @var string
     */
    public $password;

    /**
     * Database charset.
     * @var string
     */
    public $charset;

    /**
     * Database table prefix.
     * @var string
     */
    public $tablePrefix;

    /**
     * Database alias mark.
     * @var string
     */
    public $aliasMark;

    /**
     * Table column link mark. Used for TeaModel::getProperExprs().
     * @var string
     */
    public $tableColumnLinkMark;

    /**
     * Persistent connection or not.
     * @var bool
     */
    public $persistent;

    /**
     * Emulate prepare or not.
     * @var bool
     */
    public $emulatePrepare;

    /**
     * Connection established or not.
     * @var bool
     */
    protected $_connected;

    /**
     * PDO instance.
     * @var PDO
     */
    protected $_conn;
    
    /**
     * Keys in the connection info group, mapping value to the properties in this class.
     * @var array
     */
    protected $_connInfoKeys = array(
        'dsn', 'username', 'password', 'charset', 'tablePrefix', 
        'aliasMark', 'tableColumnLinkMark', 'persistent', 'emulatePrepare'
    );

    /**
     * Constructor.
     * @param array $connInfo Connection info array. See TeaModel::$config.
     */
    public function __construct($connInfo) {
        $this->connInfo = $connInfo;
        $this->setConnInfo($connInfo);
    }

    /**
     * Set connection information.
     */
    public function setConnInfo() {
        foreach ($this->connInfo as $key => $val) {
            if (in_array($key, $this->_connInfoKeys)) {
                $this->$key = $val;
            }
        }
        if (preg_match('/charset=(\w+)/', $this->dsn, $matches)) {
            $this->charset = $matches[1];
        } else {
            if (!empty($this->charset)) {
                $this->dsn = rtrim($this->dsn, ';') . ";charset={$this->charset};";
            }
        }
        $this->dbname = preg_match('/dbname=(\w+)/', $this->dsn, $matches) ? $matches[1] : null;
        $this->driverType = preg_match('/^(\w+):/', $this->dsn, $matches) ? $matches[1] : null;
    }

    /**
     * Connection options.
     * @return array Options array.
     */
    public function connOptions() {
        $connOptions = array();
        if (isset($this->persistent)) {
            $connOptions[PDO::ATTR_PERSISTENT] = $this->persistent;
        }
        if (isset($this->emulatePrepare)) {
            $connOptions[PDO::ATTR_EMULATE_PREPARES] = $this->emulatePrepare; // if emulate, numbers return as string.
        }
        $connOptions[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
        return $connOptions;
    }

    /**
     * Connect to database.
     * @return bool
     * @throws TeaDbException
     */
    public function connect() {
        $hasErr = false;
        $connClass = get_class($this);
        try {
            $this->beforeConnecting();
            $this->_conn = new PDO($this->dsn, $this->username, $this->password, $this->connOptions());
        } catch (PDOException $e) {
            $hasErr = true;
            throw new TeaDbException("{$connClass} could not connect to {$this->driverType} database '{$this->dbname}'.", (int) $e->getCode(), $e->errorInfo);
        }
        if (!$hasErr) {
            $this->afterConnecting();
            return $this->_connected = true;
        }
        return false;
    }

    /**
     * Error handler for pdo constructor, to catch the errors and fix bugs.
     * Fix the bug 'PDO::__construct(): {database} server has gone away'.
     */
    public function pdoConstructorErrHandler($errno, $errstr) {
        // PDO::__construct(): {database} server has gone away warning may appear if database service restart but TeaDbQuery use the same pdo instance.
        if (stripos($errstr, 'server has gone away') !== false) {
            $this->connect();
        } else {
            trigger_error($errstr, E_USER_ERROR);
        }        
    }

    /**
     * Hook method before connecting.
     */
    public function beforeConnecting() {
        set_error_handler(array($this, 'pdoConstructorErrHandler'));
    }

    /**
     * Hook method after connecting.
     */
    public function afterConnecting() {
        restore_error_handler();
    }

    /**
     * Connection established or not.
     * @return bool
     */
    public function connected() {
        return $this->_connected;
    }

    /**
     * Get inner connection instance.
     * @return PDO
     * @throws TeaDbException If database connection has not established.
     */
    public function getConn() {
        if ($this->_conn instanceof PDO) {
            return $this->_conn;
        } else {
            $connClass = get_class($this);
            throw new TeaDbException("{$connClass} could not connect to {$this->driverType} database '{$this->dbname}'.");
        }
    }

    /**
     * Get proper TeaDbSqlBuilder subclass instance.
     * @return object Proper TeaDbSqlBuilder subclass instance.
     */
    abstract public function getSqlBuilder();
    
    /**
     * Get proper TeaDbCriteria subclass instance.
     * @return object Proper TeaDbCriteria subclass instance.
     */
    abstract public function getCriteria();

    /**
     * Get proper TeaDbSchema subclass instance.
     * @return object Proper TeaDbSchema subclass instance.
     */
    abstract public function getSchema();

    /**
     * Get proper TeaDbQuery subclass instance.
     * @return object Proper TeaDbQuery subclass instance.
     */
    abstract public function getQuery();
}