<?php

/**
 * TeaModel class file.
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package base
 * @property-read TeaDbCriteriaBuilder $criteria Proper TeaDbCriteriaBuilder subclass new instance.
 */
class TeaModel extends TeaCommon {

    public static $config = array(
        'defaultConnection' => 'default',
        'connections' => array(
            'default' => array(
                'dsn' => 'mysql:host=127.0.0.1;dbname=test;',
                'username' => 'root',
                'password' => '123456',
                'charset' => 'utf8', // if charset has been defined in dsn, this will be invalid.
                'tablePrefix' => 'tb_',
                'tableAliasMark' => '->',
                'persistent' => true,
                'emulatePrepare' => true,
                'autoConnect' => true,
            )
        )
    );

    /**
     * Table column names.
     * @var array
     */
    private $_colNames = array();
    
    /**
     * Primary key column names.
     * @var array
     */
    private $_pkColNames = array();
    
    /**
     * Saved temporary column values.
     * @var array
     */    
    private $_tmpColVals = array();

    /**
     * Constructor, set properties for instances.
     */
    public function __construct() {
        $this->setClassConfig(__CLASS__);
    }

    /**
     * Magic method __get.
     * This method is for getting column value.
     * @param string $name Column value.
     * @return mixed Column value.
     */
    public function __get($name) {
        if ($this->isTableColumn($name)) {
            return isset($this->_tmpColVals[$name]) ? $this->_tmpColVals[$name] : false;
        } else {
            $trace = debug_backtrace();
            trigger_error("Undefined property via __get(): {$name} in {$trace[0]['file']} on line {$trace[0]['line']}");
            return null;
        }
    }

    /**
     * Magic method __set.
     * This method is for setting column value.
     * @param string $name Column name.
     * @param mixed $value Column value.
     */
    public function __set($name, $value) {
        if ($this->isTableColumn($name)) {
            $this->_tmpColVals[$name] = $value;
        }
    }

    /**
     * Alias method of Tea::getDbConnection().
     * @return TeaDbConnection
     */
    public function getConnection() {
        return $this->getDbConnection();
    }

    /**
     * Alias method of Tea::getDbSqlBuilder().
     * @return TeaDbSqlBuilder
     */
    public function getSqlBuilder() {
        return $this->getDbSqlBuilder();
    }

    /**
     * Alias method of Tea::getDbSchema().
     * @return TeaDbSchema
     */
    public function getSchema() {
        return $this->getDbSchema();
    }

    /**
     * Alias method of Tea::getDbQuery().
     * @return TeaDbQuery
     */
    public function getDb() {
        return $this->getDbQuery();
    }

    /**
     * Alias method of Tea::getDbCriteriaBuilder().
     * @return TeaDbCriteriaBuilder
     */
    public function getCriteria() {
        return $this->getDbCriteriaBuilder();
    }
    
    /**
     * Hook method.
     * Return the table name for this model. Defaults to the lowercased and underscored model name.
     * @return string Table name.
     */
    public function tableName() {
        $className = get_class($this);
        return StringHelper::camelToUnderscore(preg_replace('/(.+)Model/', '$1', $className));
    }

    /**
     * Get real table name. 
     * If Table name is '{{table_name}}', and table prefix is 'tbl_'. This will return 'tbl_table_name'.
     * @return string Real table name
     */
    public function getTableName() {
        return $this->getSqlBuilder()->getTableName($this->tableName());
    }

    /**
     * Get table alias name.
     * If $tblName is '{{table_name->A}}', This will return 'A'.
     * @param string $tblName Table name.
     * @return string Table alias name.
     */
    public function getTableAlias() {
        return $this->getSqlBuilder()->getTableAlias($this->tableName());
    }
    
    /**
     * Insert record(s).
     * @param mixed $vals Value indicates the inserted data.
     * <pre>
     * There are three types of the vals:
     * One dimensional array:
     * array(col1Val, col2Val, colNVal, ...)
     * or 
     * array(
     *     'col1Name' => col1Val, 
     *     'col2Name' => col2Val, 
     *     'colNName' => colNVal, 
     *     ...
     * )
     * 
     * Two dimensional array:
     * array(
     *     array(col1Val, col2Val, colNVal),
     *     array(col1Val, col2Val, colNVal),
     *     ...
     * )
     * or
     * array(
     *     array('col1Name' => col1Val, 'col2Name' => col2Val, 'colNName' => colNVal),
     *     array('col1Name' => col1Val, 'col2Name' => col2Val, 'colNName' => colNVal),
     *     ... // all keys should be same as the first element array or just leave the keys of other elements empty except first.
     * )
     * 
     * Select sql string, could be generated by TeaDbSqlBuilder::select():
     * 'SELECT * FROM `table`'
     * </pre>
     * @param array $duplicateUpdate Update data on duplicate key occurs.
     * <pre>
     * The values will be like this:
     * array(
     *     'colName1' => colVal1,
     *     'colName2' => colVal2,
     *     ...
     * )
     * </pre>
     * @return bool
     */
    public function insert($vals = array(), $duplicateUpdate = array()) {
        if (empty($vals) && !empty($this->_tmpColVals)) {
            $vals = $this->_tmpColVals;
        }
        $criteria = is_array($duplicateUpdate) && !empty($duplicateUpdate) ? array('duplicateUpdate' => $duplicateUpdate) : null;
        $sql = $this->getSqlBuilder()->insert($this->tableName(), $vals, $criteria);
        if ($this->getDb()->query($sql)->getRowCount() > 0) {
            return true;
        }
        return false;
    }
    
    /**
     * Find a single record with the specified criteria.
     * @param mixed $criteria TeaDbCriteriaBuilder instance or criteria array.
     * @param mixed $colName Select exprs, string or array. If empty, it will be '*'.
     * @return array
     */
    public function find($criteria = array(), $colName = null) {
        $sql = $this->getSqlBuilder()->select($this->tableName(), $criteria, $colName);
        $rst = $this->getDb()->query($sql)->fetchRow();
        $this->setTmpColVals($rst);
        return $rst;
    }

    /**
     * Find a single record by sql.
     * @param string $sql Sql statement.
     * @param array $params An array of values with as many elements as there are bound parameters in the sql being executed.
     * @return array
     */
    public function findBySql($sql, $params = array()) {
        $rst = $this->getDb()->query($sql, $params)->fetchRow();
        $this->setTmpColVals($rst);
        return $rst;
    }

    /**
     * Find a single record with the condition array of criteria where.
     * @param array $condition Condition array of criteria where.
     * @param mixed $colName Select exprs, string or array. If empty, it will be '*'.
     * @return array
     */
    public function findByCondition($condition = array(), $colName = null) {
        $criteria = !empty($condition) ? array('where' => $condition) : null;
        return $this->find($criteria, $colName);
    }
    
    /**
     * Find a single record with the specified primary key value.
     * @param mixed $pkVal Primary key value or array values for multiple primary keys.
     * @param mixed $colName Select exprs, string or array. If empty, it will be '*'.
     * @return array
     */
    public function findByPk($pkVal, $colName = null) {
        return $this->find($this->getPkCriteria($pkVal), $colName);
    }
    
    /**
     * Find a single record column value or multiple columns values array with the specified criteria.
     * @param mixed $criteria TeaDbCriteriaBuilder instance or criteria array.
     * @param mixed $colName Select exprs, string or array. If empty, it will be '*'.
     * @return mixed Return a single column value or multiple columns values array.
     */
    public function findColumn($criteria = array(), $colName = null) {
        $sql = $this->getSqlBuilder()->select($this->tableName(), $criteria, $colName);
        $rst = $this->getDb()->query($sql)->fetchRow();
        $this->setTmpColVals($rst);
        if (is_array($rst) && is_string($colName) && isset($rst[$colName])) {
            return $rst[$colName];
        } else {
            return $rst;
        }
    }

    /**
     * Find a single record column value or multiple columns values array with the condition array of criteria where.
     * @param array $condition Condition array of criteria where.
     * @param mixed $colName Select exprs, string or array. If empty, it will be '*'.
     * @return mixed Return a single column value or multiple columns values array.
     */
    public function findColumnByCondition($condition = array(), $colName = null) {
        $criteria = !empty($condition) ? array('where' => $condition) : null;
        return $this->findColumn($criteria, $colName);
    }
    
    /**
     * Find all records with the specified criteria.
     * @param mixed $criteria TeaDbCriteriaBuilder instance or criteria array.
     * @param mixed $colName Select exprs, string or array. If empty, it will be '*'.
     * @return array
     */
    public function findAll($criteria = array(), $colName = null) {
        $sql = $this->getSqlBuilder()->select($this->tableName(), $criteria, $colName);
        $rst = $this->getDb()->query($sql)->fetchRows();
        return $rst;
    }

    /**
     * Find all records by sql.
     * @param string $sql Sql statement.
     * @param array $params An array of values with as many elements as there are bound parameters in the sql being executed.
     * @return array
     */
    public function findAllBySql($sql, $params = array()) {
        $rst = $this->getDb()->query($sql, $params)->fetchRows();
        return $rst;
    }

    /**
     * Find all records with the condition array of criteria where.
     * @param array $condition Condition array of criteria where.
     * @param mixed $colName Select exprs, string or array. If empty, it will be '*'.
     * @return array
     */
    public function findAllByCondition($condition = array(), $colName = null) {
        $criteria = !empty($condition) ? array('where' => $condition) : null;
        return $this->findAll($criteria, $colName);
    }
    
    /**
     * Update record(s) with the specified criteria.
     * @param mixed $criteria TeaDbCriteriaBuilder instance or criteria array.
     * @param array $vals An array indicates update data.
     * <pre>
     * It will be an array like this:
     * array(
     *     'colName1' => colVal1
     *     'colName2' => colVal2,
     *     ...
     * )
     * </pre>
     * @param bool $safe Safe update or not. If false, it will update all.
     * @return bool
     */
    public function update($criteria = array(), $vals = array(), $safe = true) {
        if (empty($vals) && !empty($this->_tmpColVals)) {
            $vals = $this->_tmpColVals;
        }
        $safe && ($criteria['limit'] = array(1));
        $sql = $this->getSqlBuilder()->update($this->tableName(), $vals, $criteria);
        var_dump($sql);
        if ($this->getDb()->query($sql)->getRowCount() > 0) {
            return true;
        }
        return false;
    }

    public function save() {
        
    }

    /**
     * Update record(s) with the condition array of criteria where.
     * @param array $condition Condition array of criteria where.
     * @param array $vals An array indicates update data.
     * <pre>
     * It will be an array like this:
     * array(
     *     'colName1' => colVal1
     *     'colName2' => colVal2,
     *     ...
     * )
     * </pre>
     * @param bool $safe Safe update or not. If false, it will update all.
     * @return bool
     */
    public function updateByCondition($condition = array(), $vals = array(), $safe = true) {
        $criteria = array('where' => $condition);
        return $this->update($criteria, $vals, $safe);
    }
    
    /**
     * Update a single record with the specified primary key value.
     * @param mixed $pkVal Primary key value or array values for multiple primary keys.
     * @param array $vals An array indicates update data.
     * <pre>
     * It will be an array like this:
     * array(
     *     'colName1' => colVal1
     *     'colName2' => colVal2,
     *     ...
     * )
     * </pre>
     * @return bool
     */
    public function updateByPk($pkVal, $vals = array()) {
        return $this->update($this->getPkCriteria($pkVal), $vals);
    }

    /**
     * Increase record(s) column value with the specified criteria.
     * @param mixed $criteria TeaDbCriteriaBuilder instance or criteria array.
     * @param string $colName Column name of the increment field.
     * @param int $val Value of increment, defaults to 1.
     * @param bool $safe Safe update or not. If false, it will update all.
     * @return bool
     */
    public function inc($criteria, $colName, $val = 1, $safe = true) {
        $expr = $this->getSqlBuilder()->quoteColumn($colName) . ' + ' . $this->getDb()->escape($val);
        $vals = array(
            $colName => new TeaDbExpr($expr)
        );
        return $this->update($criteria, $vals, $safe);
    }

    /**
     * Increase record(s) column value with the condition array of criteria where.
     * @param array $condition Condition array of criteria where.
     * @param string $colName Column name of the increment field.
     * @param int $val Value of increment, defaults to 1.
     * @param bool $safe Safe update or not. If false, it will update all.
     * @return bool
     */
    public function incByCondition($condition, $colName, $val = 1, $safe = true) {
        $criteria = array('where' => $condition);
        return $this->inc($criteria, $colName, $val, $safe);
    }

    /**
     * Increase a single record column value with the specified primary key value.
     * @param mixed $pkVal Primary key value or array values for multiple primary keys.
     * @param string $colName Column name of the increment field.
     * @param int $val Value of increment, defaults to 1.
     * @return bool
     */
    public function incByPk($pkVal, $colName, $val = 1) {
        return $this->inc($this->getPkCriteria($pkVal), $colName, $val);
    }
    
    /**
     * Delete record(s) with the specified criteria.
     * @param mixed $criteria TeaDbCriteriaBuilder instance or criteria array.
     * @param bool $safe Safe update or not. If false, it will update all.
     * @return bool 
     */
    public function delete($criteria = array(), $safe = true) {
        $safe && ($criteria['limit'] = array(1));
        $sql = $this->getSqlBuilder()->delete($this->tableName(), $criteria);
        if ($this->getDb()->query($sql)->getRowCount() > 0) {
            return true;
        }
        return false;
    }

    /**
     * Delete record(s) with the condition array of criteria where.
     * @param array $condition Condition array of criteria where.
     * @param bool $safe Safe update or not. If false, it will update all.
     * @return bool 
     */
    public function deleteByCondition($condition = array(), $safe = true) {
        $criteria = is_array($condition) && !empty($condition) ? array('where' => $condition) : null;
        return $this->delete($criteria, $safe);
    }
    
    /**
     * Delete a single record with the specified primary key value.
     * @param mixed $pkVal Primary key value or array values for multiple primary keys.
     * @return bool 
     */
    public function deleteByPk($pkVal) {
        return $this->delete($this->getPkCriteria($pkVal));
    }
    
    /**
     * Get the number of rows affected by the last INSERT, SELECT, UPDATE or DELETE statement.
     * @return int
     */
    public function getRowCount() {
        return $this->getDb()->getRowCount();
    }
    
    /**
     * Get last insert id.
     * Notice: it is not support insert many rows at one time.
     * @return int Last insert id.
     */
    public function getLastInsertId() {
        return $this->getDb()->getLastInsertId();
    }

    /**
     * Get last query sql statement.
     * @return string Sql statement.
     */
    public function getLastSql() {
        return $this->getDb()->getLastSql();
    }

    /**
     * Get table column names.
     * @return array Table column names.
     */
    public function getColumnNames() {
        if (is_array($this->_colNames) && !empty($this->_colNames)) {
            return $this->_colNames;
        }
        return $this->_colNames = array_keys($this->getSchema()->getTableColumns($this->tableName()));
    }

    /**
     * Check whether the name is a column of the table.
     * @param string $name Name to be checked.
     * @return bool
     */
    public function isTableColumn($name) {
        return in_array($name, $this->getColumnNames()) ? true : false;
    }
    
    /**
     * Get 'PRIMARY' index column names.
     * @return array 'PRIMARY' index column names.
     */
    public function getPkColumnNames() {
        if (is_array($this->_pkColNames) && !empty($this->_pkColNames)) {
            return $this->_pkColNames;
        }
        return $this->_pkColNames = $this->getSchema()->getPkColumnNames($this->tableName());
    }
    
    /**
     * Get criteria array of the primary key value.
     * @param mixed $pkVal Primary key value or array values for multiple primary keys.
     * @return array Criteria array.
     */
    public function getPkCriteria($pkVal) {
        $criteria = array();
        $pkColNames = $this->getPkColumnNames();
        if (is_array($pkVal)) {
            if (count($pkColNames) !== count($pkVal)) {
                $pkValStr = implode(', ', $pkColNames);
                throw new TeaDbException("PRIMARY key columns array({$pkValStr}) and parameter 1 should have an equal number of elements.");
            }
            $where = array_combine($pkColNames, $pkVal);
            $criteria = array('where' => $where);
        } else {
            $criteria = array('where' => array($pkColNames[0] => $pkVal));
        }
        return $criteria;
    }

    /**
     * Set $this->_tmpColVals value.
     * @param array $rst A single record result.
     */
    private function setTmpColVals($rst) {
        if (is_array($rst) && !empty($rst)) {
            foreach ($rst as $col => $val) {
                $this->{$col} = $val;
            }
        }
    }
    
}