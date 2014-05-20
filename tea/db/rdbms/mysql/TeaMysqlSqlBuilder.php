<?php

/**
 * TeaMysqlSqlBuilder class file
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package db.mysql
 */
class TeaMysqlSqlBuilder extends TeaDbSqlBuilder {

    const INDEX_NORMAL = 'INDEX';
    const INDEX_UNIQUE = 'UNIQUE';
    const INDEX_FULLTEXT = 'FULLTEXT';
    const INDEX_SPATIAL = 'SPATIAL';
    const FK_DELETE_RESTRICT = 'ON DELETE RESTRICT';
    const FK_DELETE_CASCADE = 'ON DELETE CASCADE';
    const FK_DELETE_SET_NULL = 'ON DELETE SET NULL';
    const FK_DELETE_NO_ACTION = 'ON DELETE NO ACTION';
    const FK_UPDATE_RESTRICT = 'ON UPDATE RESTRICT';
    const FK_UPDATE_CASCADE = 'ON UPDATE CASCADE';
    const FK_UPDATE_SET_NULL = 'ON UPDATE SET NULL';
    const FK_UPDATE_NO_ACTION = 'ON UPDATE NO ACTION';

    /**
     * Allowed criteria name map.
     * The value must be in right order.
     * @var array
     */
    public $allowCriteriaMap = array(
        'insertOne' => array('duplicateUpdate'),
        'insertMany' => array('duplicateUpdate'),
        'insertSelect' => array('duplicateUpdate'),
        'select' => array('join', 'where', 'groupBy', 'having', 'orderBy', 'limit'),
        'update' => array('where', 'orderBy', 'limit'),
        'delete' => array('where', 'orderBy', 'limit')
    );

    // Data Definition Statements

    /**
     * Create a table.
     * @param string $tblName Table name.
     * @param array $colDefinitions Column definitions, like array('columnName' => "INT NOT NULL DEFAULT 0").
     * @param array $indexDefinitions Table index definitions, like array("INDEX `test` (`col1`, `col2`)").
     * @param string $tableOption Table options string, like "ENGINE=MyISAM DEFAULT CHARACTER SET utf8".
     * @return string Generated sql string.
     */
    public function createTable($tblName, $colDefinitions = array(), $indexDefinitions = array(), $tableOption = null) {
        $createDefinition = array();
        foreach ($colDefinitions as $colName => $colDef) {
            $createDefinition[] = "\t" . $this->quoteColumn($colName) . ' ' . $colDef;
        }
        foreach ($indexDefinitions as $tblDef) {
            $createDefinition[] = "\t" . $tblDef;
        }
        $sql = "CREATE TABLE " . $this->quoteTable($tblName) . " (\n" . implode(",\n", $createDefinition) . "\n";
        !empty($tableOption) && ($sql .= ' ' . $tableOption);
        return $sql;
    }

    /**
     * Rename a table.
     * @param string $tblName Table name.
     * @param string $newTblName New table name.
     * @return string Generated sql string.
     */
    public function renameTable($tblName, $newTblName) {
        return "ALTER TABLE " . $this->quoteTable($tblName) . " RENAME TO " . $this->quoteTable($newTblName);
    }

    /**
     * Truncate a table.
     * @param string $tblName Table name.
     * @return string Generated sql string.
     */
    public function truncateTable($tblName) {
        return "TRUNCATE TABLE " . $this->quoteTable($tblName);
    }

    /**
     * Drop a table.
     * @param string $tblName Table name.
     * @return string Generated sql string.
     */
    public function dropTable($tblName) {
        return "DROP TABLE " . $this->quoteTable($tblName);
    }

    /**
     * Add an index to a table
     * @param string $tblName Table name.
     * @param string $indexName Index name.
     * @param array $cols Names of columns.
     * @param string $indexConst Const in this class that start with 'INDEX_'.
     * @return string Generated sql string.
     */
    public function addIndex($tblName, $indexName, $cols, $indexConst = self::INDEX_NORMAL) {
        return "ALTER TABLE " . $this->quoteTable($tblName) . " ADD " . $indexConst . " " . $this->normalQuote($indexName) . " (" . $this->quoteColumns($cols) . ")";
    }

    /**
     * Drop an index of a table.
     * @param string $tblName Table name.
     * @param string $indexName Index name.
     * @return string Generated sql string.
     */
    public function dropIndex($tblName, $indexName) {
        return "ALTER TABLE " . $this->quoteTable($tblName) . " DROP INDEX " . $this->normalQuote($indexName);
    }

    /**
     * Add the primary key to a table.
     * @param string $tblName Table name.
     * @param array $cols Names of columns.
     * @return string Generated sql string.
     */
    public function addPrimary($tblName, $cols) {
        return "ALTER TABLE " . $this->quoteTable($tblName) . " ADD PRIMARY KEY (" . $this->quoteColumns($cols) . ")";
    }

    /**
     * Drop the primary key of a table.
     * @param string $tblName Table name.
     * @return string Generated sql string.
     */
    public function dropPrimary($tblName) {
        return "ALTER TABLE " . $this->quoteTable($tblName) . " DROP PRIMARY KEY";
    }

    /**
     * Add a foreign key constraint on a table.
     * @param string $tblName Table name.
     * @param string $symbol Constraint symbol name.
     * @param array $cols Names of columns.
     * @param string $refTblName Name of the reference table.
     * @param array $refCols Names of the reference table's columns.
     * @param string $refDeleteConst Const in this class that starts with 'FK_DELETE_'.
     * @param string $refUpdateConst Const in this class that starts with 'FK_UPDATE_'.
     * @return string Generated sql string.
     */
    public function addForeign($tblName, $symbol, $cols, $refTblName, $refCols, $refDeleteConst = null, $refUpdateConst = null) {
        $refDeleteSql = !empty($refDeleteConst) ? ' ' . $refDeleteConst : '';
        $refUpdateSql = !empty($refUpdateConst) ? ' ' . $refUpdateConst : '';
        return "ALTER TABLE " . $this->quoteTable($tblName) . " ADD CONSTRAINT " . $this->normalQuote($symbol) . " FOREIGN KEY (" . $this->quoteColumns($cols) . ") REFERENCES " . $this->quoteTable($refTblName) . " (" . $this->quoteColumns($refCols) . ")" . $refDeleteSql . $refUpdateSql;
    }

    /**
     * Drop a foreign key constraint of a table.
     * @param string $tblName Table name.
     * @param string $symbol Constraint symbol name.
     * @return string Generated sql string.
     */
    public function dropForeign($tblName, $symbol) {
        return "ALTER TABLE " . $this->quoteTable($tblName) . " DROP FOREIGN KEY " . $this->normalQuote($symbol);
    }

    /**
     * Add a column to a table.
     * @param string $tblName Table name.
     * @param string $colName Column name.
     * @param string $colDefinition Column definition, like "INT NOT NULL DEFAULT 0".
     * @param string $after Column name that you want to add after. If empty, it will be the first.
     * @return string Generated sql string.
     */
    public function addColumn($tblName, $colName, $colDefinition, $after = null) {
        $afterSql = empty($after) ? ' FIRST' : ' AFTER ' . $this->quoteColumn($after);
        return "ALTER TABLE " . $this->quoteTable($tblName) . " ADD COLUMN " . $this->quoteColumn($colName) . ' ' . $colDefinition . $afterSql;
    }

    /**
     * Rename a column of a table.
     * @param string $tblName Table name.
     * @param string $colName Column name.
     * @param string $newColName New column name.
     * @return string Generated sql string.
     */
    public function renameColumn($tblName, $colName, $newColName) {
        $colDef = $this->getSchema()->getCreateColumn($tblName, $colName);
        return "ALTER TABLE " . $this->quoteTable($tblName) . " CHANGE " . $this->quoteColumn($colName) . " " . $this->quoteColumn($newColName) . " " . $colDef;
    }

    /**
     * Drop a column of a table.
     * @param string $tblName Table name.
     * @param string $colName Column name.
     * @return string Generated sql string.
     */
    public function dropColumn($tblName, $colName) {
        return "ALTER TABLE " . $this->quoteTable($tblName) . " DROP COLUMN " . $this->quoteColumn($colName);
    }

    // Database Administration Statements

    /**
     * Show table status of a database.
     * @param string $dbname Database name. If empty, it will be the current database.
     * @return string Generated sql string.
     */
    public function showTableStatus($dbname = null) {
        $fromSql = !empty($dbname) ? ' FROM ' . $this->normalQuote($dbname) : '';
        return "SHOW TABLE STATUS" . $fromSql;
    }

    /**
     * Show create table.
     * @param string $tblName Table name.
     * @return string Generated sql string.
     */
    public function showCreateTable($tblName) {
        return "SHOW CREATE TABLE " . $this->quoteTable($tblName);
    }

    /**
     * Show table columns.
     * @param string $tblName Table name.
     * @return string Generated sql string.
     */
    public function showTableColumns($tblName) {
        return "SHOW FULL COLUMNS FROM " . $this->quoteTable($tblName);
    }

    /**
     * Show table index.
     * @param string $tblName Table name.
     * @return string Generated sql string.
     */
    public function showTableIndex($tblName) {
        return "SHOW INDEX FROM " . $this->quoteTable($tblName);
    }

    // Data Manipulation Statements

    /**
     * Insert data into table.
     * @param string $tblName Table name.
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
     * Select sql string, could be generated by TeaMysqlSqlBuilder::select():
     * 'SELECT * FROM `table`'
     * </pre>
     * @param mixed $criteria TeaMysqlCriteriaBuilder instance or criteria array.
     * @return string Generated sql string.
     */
    public function insert($tblName, $vals, $criteria = null) {
        $sql = 'INSERT INTO ' . $this->quoteTable($tblName);
        if (is_array($vals)) {
            if (ArrayHelper::isMulti($vals)) {
                $colNames = null;
                $cols = array_keys($vals[0]);
                if ($cols === array_filter($cols, 'is_string')) {
                    $colNames = $cols;
                }
                $sql = $this->insertMany($tblName, $vals, $colNames, $criteria);
            } else {
                $sql = $this->insertOne($tblName, $vals, $criteria);
            }
        } else if (is_string($vals) && preg_match('/^SELECT/i', $vals)) {
            $sql = $this->insertSelect($tblName, $vals, $criteria);
        }
        return $sql;
    }

    /**
     * Select data.
     * @param string $tblName Table name.
     * @param mixed $criteria TeaMysqlCriteriaBuilder instance or criteria array.
     * @param mixed $exprs Select exprs, string or array. If empty, it will be '*'.
     * @return string Generated sql string.
     */
    public function select($tblName, $criteria = null, $exprs = null) {
        empty($exprs) && ($exprs = '*');
        !is_array($exprs) && ($exprs = array($exprs));
        $exprSqls = array();
        foreach ($exprs as $expr) {
            $exprAlias = $this->getTableAlias($expr);
            if ($expr === '*' || $expr instanceof TeaDbExpr) {
                $exprSqls[] = $expr;
            } else if (!empty($exprAlias)) {
                $exprSqls[] = $this->quoteColumn($expr) . " AS " . $this->normalQuote($exprAlias);
            } else {
                $exprSqls[] = $this->quoteColumn($expr);
            }
        }
        $exprSql = implode(', ', $exprSqls);
        $tblAlias = $this->getTableAlias($tblName);
        $criteriaArrHasJoin = is_array($criteria) && isset($criteria['join']);
        $criteriaObjHasJoin = $criteria instanceof TeaMysqlCriteriaBuilder && isset($criteria->criteriaArr['join']);
        $asSql = !empty($tblAlias) && ($criteriaArrHasJoin || $criteriaObjHasJoin) ? " AS " . $this->normalQuote($tblAlias) : '';
        return "SELECT {$exprSql} FROM " . $this->quoteTable($tblName) . $asSql . $this->getCriteriaSql($criteria, __FUNCTION__);
    }

    /**
     * Select exists.
     * @param string $tblName Table name.
     * @param mixed $criteria TeaMysqlCriteriaBuilder instance or criteria array.
     * @param string $alias Exists result column alias.
     * @return string Generated sql string.
     */
    public function exists($tblName, $criteria = null, $alias = 'exists') {
        $selectSql = $this->select($tblName, $criteria);
        return "SELECT EXISTS({$selectSql}) AS " . $this->quoteColumn($alias);
    }

    /**
     * Update data.
     * @param string $tblName Table name.
     * @param array $vals An array indicates update data.
     * <pre>
     * It will be an array like this:
     * array(
     *     'colName1' => colVal1
     *     'colName2' => colVal2,
     *     ...
     * )
     * </pre>
     * @param mixed $criteria TeaMysqlCriteriaBuilder instance or criteria array.
     * For multiple-table syntax, 'orderBy' and 'limit' cannot be used.
     * @return string Generated sql string.
     */
    public function update($tblName, $vals, $criteria = null) {
        $colVals = array();
        foreach ($vals as $colName => $val) {
            $colVals[] = $this->quoteColumn($colName) . " = " . Tea::getDbQuery()->escape($val);
        }
        $colValsSql = implode(', ', $colVals);
        $tblAlias = $this->getTableAlias($tblName);
        $asSql = !empty($tblAlias) ? " AS " . $this->normalQuote($tblAlias) : '';
        return "UPDATE " . $this->quoteTable($tblName) . $asSql . " SET " . $colValsSql . $this->getCriteriaSql($criteria, __FUNCTION__);
    }

    /**
     * Delete data.
     * @param string $tblName Table name.
     * @param mixed $criteria TeaMysqlCriteriaBuilder instance or criteria array.
     * For multiple-table syntax, 'orderBy' and 'limit' cannot be used.
     * @return string Generated sql string.
     */
    public function delete($tblName, $criteria = null) {
        $tblAlias = $this->getTableAlias($tblName);
        $criteriaSql = $this->getCriteriaSql($criteria, __FUNCTION__);
        if (!empty($tblAlias)) {
            return "DELETE FROM {$tblAlias} USING " . $this->quoteTable($tblName) . " AS " . $this->normalQuote($tblAlias) . $criteriaSql;
        }
        return "DELETE FROM " . $this->quoteTable($tblName) . $criteriaSql;
    }

    /**
     * Get real table name. 
     * If $tblName is '{{table_name}}', and table prefix is 'tbl_'. This will return 'tbl_table_name'.
     * @param string $tblName Table name.
     * @return string Real table name
     */
    public function getTableName($tblName) {
        $tblName = preg_replace('/^{{(.+)}}$/', '$1', $tblName);
        if (strpos($tblName, Tea::getDbConnection()->tableAliasMark) !== false) {
            $aliasParts = explode(Tea::getDbConnection()->tableAliasMark, $tblName);
            $tblParts = explode('.', $aliasParts[0]);
        } else {
            $tblParts = explode('.', $tblName);
        }
        $partsKeys = array_keys($tblParts);
        $lastKey = array_pop($partsKeys);
        foreach ($tblParts as $key => &$part) {
            if ($key === $lastKey) {
                $part = Tea::getDbConnection()->tablePrefix . $part;
            }
        }
        return implode('.', $tblParts);
    }

    /**
     * Get table alias name.
     * If $tblName is '{{table_name->A}}', This will return 'A'.
     * @param string $tblName Table name.
     * @return string Table alias name.
     */
    public function getTableAlias($tblName) {
        $tblName = preg_replace('/^{{(.+)}}$/', '$1', $tblName);
        if (strpos($tblName, Tea::getDbConnection()->tableAliasMark) !== false) {
            $parts = explode(Tea::getDbConnection()->tableAliasMark, $tblName);
            return $parts[1];
        }
    }

    // Sql Quote Functions

    /**
     * Quote a string normally.
     * @param string $str String to be quoted.
     * @return string Quoted string.
     */
    public function normalQuote($str) {
        return '`' . $str . '`';
    }

    /**
     * Quote a table name, table alias is available.
     * @param string $tblName Table name to be quoted.
     * @return string Quoted table name.
     */
    public function quoteTable($tblName) {
        $tblName = $this->getTableName($tblName);
        if (strpos($tblName, '.') === false) {
            return $this->normalQuote($tblName);
        }
        $parts = explode('.', $tblName);
        $partsKeys = array_keys($parts);
        $lastKey = array_pop($partsKeys);
        foreach ($parts as $key => &$part) {
            if ($key === $lastKey) {
                $part = Tea::getDbConnection()->tablePrefix . $part;
            }
            $part = $this->normalQuote($part);
        }
        unset($part);
        return implode('.', $parts);
    }

    /**
     * Quote a column name.
     * @param string $colName Column name to be quoted.
     * @return string Quoted column name.
     */
    public function quoteColumn($colName) {
        $colName = $this->getTableName($colName);
        if (strpos($colName, '.') === false) {
            return $this->normalQuote($colName);
        }
        $parts = explode('.', $colName);
        foreach ($parts as &$part) {
            $part !== '*' && ($part = $this->normalQuote($part));
        }
        unset($part);
        return implode('.', $parts);
    }

    /**
     * Quote table index columns.
     * @param array $columns Table columns, supports index columns, like array('col1' => 10) or array('col1').
     * @return string Quoted columns string.
     */
    public function quoteColumns($columns) {
        $quotedCols = array();
        foreach ($columns as $key => $val) {
            if (is_string($key) && is_int($val)) {
                $quotedCols[] = $this->quoteColumn($key) . "($val)";
            } else {
                $quotedCols[] = $this->quoteColumn($val);
            }
        }
        return implode(', ', $quotedCols);
    }

    /**
     * Insert one row into table.
     * @param string $tblName Table name.
     * @param array $val An array indicates an row.
     * <pre>
     * There are two types of the array:
     * Type one:
     * array(col1Val, col2Val, colNVal, ...)
     * Type two:
     * array(
     *     'col1Name' => col1Val,
     *     'col2Name' => col2Val,
     *     'colNName' => colNVal,
     *     ...
     * )
     * </pre>
     * @param mixed $criteria TeaMysqlCriteriaBuilder instance or criteria array.
     * @return string Generated sql string.
     */
    protected function insertOne($tblName, $val, $criteria = null) {
        $colNames = $colVals = array();
        foreach ($val as $colName => $colVal) {
            is_string($colName) && ($colNames[] = $this->quoteColumn($colName));
            $colVals[] = Tea::getDbQuery()->escape($colVal);
        }
        $colNamesSql = implode(', ', $colNames);
        $colValsSql = implode(', ', $colVals);
        return "INSERT INTO " . $this->quoteTable($tblName) . " ({$colNamesSql}) VALUES ({$colValsSql})" . $this->getCriteriaSql($criteria, __FUNCTION__);
    }

    /**
     * Insert multiple rows into table.
     * @param string $tblName Table name.
     * @param array $vals An array indicates multiple rows.
     * <pre>
     * It will be an array like this:
     * array(
     *     array(col1Val, col2Val, colNVal),
     *     array(col1Val, col2Val, colNVal),
     *     ...
     * )
     * </pre>
     * @param mixed $colNames If it is an array, it should be the column names corresponding to param $vals. If it is null, each element of param $vals should contain all columns.
     * @param mixed $criteria TeaMysqlCriteriaBuilder instance or criteria array.
     * @return string Generated sql string.
     */
    protected function insertMany($tblName, $vals, $colNames = null, $criteria = null) {
        $multiColVals = array();
        foreach ($vals as $k => $val) {
            $colVals = array();
            foreach ($val as $colVal) {
                $colVals[] = Tea::getDbQuery()->escape($colVal);
            }
            $multiColVals[$k] = "(" . implode(', ', $colVals) . ")";
        }
        $colNamesSql = is_array($colNames) && !empty($colNames) ? $this->quoteColumns($colNames) : '';
        $multiColValsSql = implode(', ', $multiColVals);
        return "INSERT INTO " . $this->quoteTable($tblName) . " ({$colNamesSql}) VALUES {$multiColValsSql}" . $this->getCriteriaSql($criteria, __FUNCTION__);
    }

    /**
     * Insert data queried by select sql.
     * @param string $tblName Table name.
     * @param string $selectSql Select sql string, could be generated by TeaMysqlSqlBuilder::select().
     * @param mixed $criteria TeaMysqlCriteriaBuilder instance or criteria array.
     * @return string Generated sql string.
     */
    protected function insertSelect($tblName, $selectSql, $criteria = null) {
        return "INSERT INTO " . $this->quoteTable($tblName) . " {$selectSql}" . $this->getCriteriaSql($criteria, __FUNCTION__);
    }

    /**
     * Get criteria sql.
     * @param mixed $criteria TeaMysqlCriteriaBuilder instance or criteria array.
     * @param string $method Key in TeaMysqlSqlBuilder::$allowCriteriaMap.
     * @return mixed Return criteria sql on success, false on failure.
     */
    protected function getCriteriaSql($criteria = null, $method = null) {
        $allowCriteria = array_key_exists($method, $this->allowCriteriaMap) ? $this->allowCriteriaMap[$method] : array();
        if ($criteria instanceof TeaMysqlCriteriaBuilder) {
            return " " . $criteria->build($allowCriteria);
        } else if (is_array($criteria) && !empty($criteria)) {
            return " " . Tea::getDbCriteriaBuilder()->parseCriteriaArr($criteria)->build($allowCriteria);
        } else {
            return '';
        }
    }

}