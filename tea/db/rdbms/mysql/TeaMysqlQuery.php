<?php

/**
 * TeaMysqlQuery class file
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package db.mysql
 */
class TeaMysqlQuery extends TeaDbQuery {

    // param type
    const PARAM_BOOL = PDO::PARAM_BOOL;
    const PARAM_NULL = PDO::PARAM_NULL;
    const PARAM_INT = PDO::PARAM_INT;
    const PARAM_STR = PDO::PARAM_STR;
    const PARAM_BOOL_INOUT = -2147483643; // PDO::PARAM_BOOL | PDO::PARAM_INPUT_OUTPUT
    const PARAM_NULL_INOUT = -2147483648; // PDO::PARAM_NULL | PDO::PARAM_INPUT_OUTPUT
    const PARAM_INT_INOUT = -2147483647; // PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT
    const PARAM_STR_INOUT = -2147483646; // PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT
    // fetch style
    const FETCH_NUM = PDO::FETCH_NUM;
    const FETCH_ASSOC = PDO::FETCH_ASSOC;
    const FETCH_CLASS = PDO::FETCH_CLASS;
    const FETCH_BOTH = PDO::FETCH_BOTH;
    // cursor orientation
    const CUR_ORI_NEXT = PDO::FETCH_ORI_NEXT;
    const CUR_ORI_PREV = PDO::FETCH_ORI_PRIOR;
    const CUR_ORI_FIRST = PDO::FETCH_ORI_FIRST;
    const CUR_ORI_LAST = PDO::FETCH_ORI_LAST;
    const CUR_ORI_ABS = PDO::FETCH_ORI_ABS;
    const CUR_ORI_REL = PDO::FETCH_ORI_REL;

    /**
     * PDOStatement instance.
     * @var PDOStatement
     */
    protected $_statement;

    /**
     * Final executed sql.
     * @var string
     */
    protected $_sql;

    /**
     * Binded params of the final executed sql.
     * @var array
     */
    protected $_params = array();

    /**
     * Get inner connection instance.
     * @return object Proper inner connection instance.
     */
    public function getConn() {
        return Tea::getDbConnection()->getConn();
    }

    /**
     * Execute an sql statement immediately.
     * @param string $sql Sql statement.
     * @return int The number of affected rows.
     * @throws TeaDbException
     */
    public function exec($sql) {
        $this->_sql = $sql;
        try {
            $num = $this->getConn()->exec($sql);
        } catch (PDOException $e) {
            throw new TeaDbException(get_class($this) . " sql execution error: {$this->getLastSql()}", (int) $e->getCode(), $e->errorInfo);
        }
        return $num;
    }

    /**
     * Initiate a transaction, turn off autocommit mode.
     * @return bool
     */
    public function beginTransaction() {
        return $this->getConn()->beginTransaction();
    }

    /**
     * Commit a transaction, turn on autocommit mode.
     * @return bool
     */
    public function commit() {
        return $this->getConn()->commit();
    }

    /**
     * Roll back the transaction initiated by TeaDbQuery::beginTransaction().
     */
    public function rollBack() {
        return $this->getConn()->rollBack();
    }

    /**
     * Prepare a sql statement.
     * Known issue: http://www.php.net/manual/en/pdostatement.fetch.php#105277
     * Scrollable cursor is not supported by mysql and sqlite.
     * @param string $sql Sql statement.
     * @param bool $scrollCursor Scroll cursor or not.
     * @return $this
     * @throws TeaDbException
     */
    public function prepare($sql, $scrollCursor = false) {
        $option = $scrollCursor ? array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL) : array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY);
        try {
            $this->_statement = $this->getConn()->prepare($sql, $option);
        } catch (PDOException $e) {
            throw new TeaDbException("TeaDbQuery could not prepare the statement: {$sql}.", (int) $e->getCode(), $e->errorInfo);
        }
        return $this;
    }

    /**
     * Bind a parameter to a variable name.
     * @param mixed $paramName Parameter identifier in the prepared sql.
     * Name style: this will be in the form of string ':name';
     * Question mark style: this will be the position of the parameter, one-based int.
     * @param mixed $var PHP variable name. The variable is bound as a reference and will only be evaluated at the time that TeaDbQuery::execPrepare() is called.
     * @param bool $inOut An INOUT parameter for a stored procedure or not.
     * @param int $length Length of the data type. To indicate that a parameter is an OUT parameter from a stored procedure, you must explicitly set the length.
     * @return $this
     */
    public function bindParam($paramName, &$var, $inOut = false, $length = null) {
        $this->_params[$paramName] = $var;
        $paramType = $this->getParamType($var, $inOut);
        $this->_statement->bindParam($paramName, $var, $paramType, $length);
        return $this;
    }

    /**
     * Bind a value to a parameter.
     * @param mixed $paramName Parameter identifier in the prepared sql.
     * Name style: this will be in the form of string ':name';
     * Question mark style: this will be the position of the parameter, one-based int.
     * @param mixed $var PHP variable name. The variable is bound as a reference and will only be evaluated at the time that TeaDbQuery::execPrepare() is called.
     * @return $this
     */
    public function bindValue($paramName, $val) {
        $this->_params[$paramName] = $val;
        $paramType = $this->getParamType($val);
        $this->_statement->bindValue($paramName, $val, $paramType);
        return $this;
    }

    /**
     * Execute the prepared sql.
     * @param array $params An array of values with as many elements as there are bound parameters in the prepared sql being executed.
     * @return $this
     * @throws TeaDbException
     */
    public function execPrepare($params = array()) {
        $this->_sql = $this->_statement->queryString;
        if (!empty($params)) {
            foreach ($params as $key => $val) {
                if (is_int($key)) {
                    $key += 1;
                }
                $this->bindValue($key, $val);
            }
        }
        if ($this->_statement instanceof PDOStatement) {
            $this->_statement->closeCursor();
        }
        try {
            $this->_statement->execute();
            $this->_params = array();
        } catch (PDOException $e) {
            $lastSql = $this->getLastSql();
            $lastSqlPart = mb_strlen($lastSql) > 500 ? mb_substr($lastSql, 0, 500) . '...' : $lastSql;
            $errMsg = get_class($this) . " prepared sql execution error: {$lastSqlPart}\n";
            $errMsg .= "{$e->getMessage()}\n";
            foreach ($this->_params as $key => $val) {
                $key = gettype($key) . ' ' . var_export($key, true);
                $val = gettype($val) . ' ' . var_export($val, true);
                $errMsg .= "param {$key} => {$val}\n";
            }
            throw new TeaDbException($errMsg, (int) $e->getCode(), $e->errorInfo);
        }
        return $this;
    }

    /**
     * Prepare a statement and execute it.
     * Notice: Mysql and sqlite do not support scrollable cursor.
     * @param string $sql Sql statement to be prepared.
     * @param array $params An array of values with as many elements as there are bound parameters in the prepared sql being executed.
     * @param bool $scrollCursor A scrollable cursor or not.
     * @return $this
     */
    public function query($sql, $params = array(), $scrollCursor = false) {
        $this->prepare($sql, $scrollCursor);
        $this->execPrepare($params);
        return $this;
    }

    /**
     * Fetches the next row from a result set
     * @param int $fetchStyle How the next row will be returned to the caller.
     * This value must be one of the TeaDbQuery::FETCH_* constants, defaults to TeaDbQuery::FETCH_ASSOC.
     * @param int $curOri For representing a scrollable cursor, this value cursor orientation determines which row will be returned to the caller.
     * This value must be one of the TeaDbQuery::FETCH_ORI_* constants, you must set the parameter $scrollCursor of TeaDbQuery::prepare() or TeaDbQuery::query() to true.
     * @param int $offset The offset depends on $curOri.
     * @return mixed The return value of this function on success depends on the fetch type. In all cases, false is returned on failure.
     */
    public function fetch($fetchStyle = self::FETCH_ASSOC, $curOri = self::CUR_ORI_NEXT, $offset = 0) {
        return $this->_statement->fetch($fetchStyle, $curOri, $offset);
    }

    /**
     * Returns an array containing all of the result set rows.
     * @param int $fetchStyle How the rows will be returned to the caller.
     * @param string $className If $fetchStyle is TeaDbQuery::FETCH_CLASS, this will be the class name.
     * @param array $constructorArgs Arguments of $className constructor if $fetchStyle is TeaDbQuery::FETCH_CLASS.
     * @return array Returns an array containing all of the remaining rows in the result set.
     */
    public function fetchAll($fetchStyle = self::FETCH_ASSOC, $className = null, $constructorArgs = null) {
        is_array($constructorArgs) || ($constructorArgs = array()); // to prevent pdo throwing exception
        if (func_num_args() <= 1) {
            return $this->_statement->fetchAll($fetchStyle);
        }
        return $this->_statement->fetchAll($fetchStyle, $className, $constructorArgs);
    }

    /**
     * Fetch the next row array from the result set.
     * @param bool $isAssoc Fetch associated result or not.
     * @return array Return an array result of the row in the result set.
     */
    public function fetchRow($isAssoc = true) {
        $fetchStyle = $isAssoc ? self::FETCH_ASSOC : self::FETCH_BOTH;
        $row = $this->fetch($fetchStyle);
        return is_array($row) ? $row : array();
    }

    /**
     * Fetch an array of array results containing all rows.
     * @param bool $isAssoc Fetch associated result or not.
     * @return array Return an array of array results containing all rows.
     */
    public function fetchRows($isAssoc = true) {
        $fetchStyle = $isAssoc ? self::FETCH_ASSOC : self::FETCH_BOTH;
        $rows = $this->fetchAll($fetchStyle);
        return is_array($rows) ? $rows : array();
    }

    /**
     * Fetch the next row object from the result set.
     * @param string @className The class name to fetch with. If empty, it will be the 'stdClass'.
     * @param array @constructorArgs The class constructor arguments.
     * @return mixed Return an object result of the row in the result set on success, or false on failure.
     */
    public function fetchObj($className = null, $constructorArgs = array()) {
        if (empty($className)) {
            $className = 'stdClass';
        }
        $obj = $this->_statement->fetchObject($className, $constructorArgs);
        if ($obj === false) {
            return false;
        } else {
            return $obj instanceof $className ? $obj : new $obj($constructorArgs);
        }
    }

    /**
     * Fetch an array of object results containing all rows.
     * @param string @className The class name to fetch with. If empty, it will be the 'stdClass'.
     * @param array @constructorArgs The class constructor arguments.
     * @return array Return an array of object results containing all rows.
     */
    public function fetchObjs($className = null, $constructorArgs = array()) {
        if (empty($className)) {
            $className = 'stdClass';
        }
        $objs = $this->fetchAll(self::FETCH_CLASS, $className, $constructorArgs);
        return is_array($objs) ? $objs : array();
    }

    /**
     * Fetch a single column value from the next row of a result set.
     * @param mixed $col Int zero-based offset of the column or string column name.
     * @return mixed Return the value of the column, false on failure, so use === for testing value.
     */
    public function fetchCol($col) {
        $rst = $this->fetchRow(false);
        return isset($rst[$col]) ? $rst[$col] : false;
    }

    /**
     * Get the number of rows affected by the last INSERT, SELECT, UPDATE or DELETE statement.
     * @return int
     */
    public function getRowCount() {
        return $this->_statement->rowCount();
    }

    /**
     * Get last insert id.
     * Notice: it is not support insert many rows at one time.
     * @return int Last insert id.
     */
    public function getLastInsertId() {
        return intval($this->getConn()->lastInsertId());
    }

    /**
     * Get last query sql statement.
     * @return string Sql statement.
     */
    public function getLastSql() {
        return $this->_sql;
    }

    /**
     * Escape value manually for sql statement to prevent sql injection.
     * @param mixed $val Value to be escaped.
     * @return string Escaped string.
     */
    public function escape($val) {
        if ($val === null) {
            return 'NULL';
        } else if (is_bool($val)) {
            return $val ? 1 : 0;
        } else if (is_int($val) || is_float($val) || $val instanceof TeaDbExpr) {
            return $val;
        } else {
            return $this->getConn()->quote($val);
        }
    }

    /**
     * Get param type.
     * Known issue: https://bugs.php.net/bug.php?id=48855
     */
    protected function getParamType($param, $inOut = false) {
        if (is_bool($param)) {
            return $inOut ? self::PARAM_BOOL_INOUT : self::PARAM_BOOL;
        } else if ($param === null) {
            return $inOut ? self::PARAM_NULL_INOUT : self::PARAM_NULL;
        } else if (is_int($param)) {
            return $inOut ? self::PARAM_INT_INOUT : self::PARAM_INT;
        } else {
            return $inOut ? self::PARAM_STR_INOUT : self::PARAM_STR;
        }
    }

}