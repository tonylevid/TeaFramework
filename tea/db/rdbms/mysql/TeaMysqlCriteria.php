<?php

/**
 * TeaMysqlCriteria class file
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package db.mysql
 */
class TeaMysqlCriteria extends TeaDbCriteria {

    /**
     * Stored raw criteria array.
     * @var array
     */
    public $criteriaArr = array();

    /**
     * Stored generated criteria sql.
     * @var array
     */
    public $criteriaSqls = array();

    /**
     * To store flattened condition values.
     * @var array
     */
    private $_flattenedVals = array();

    /**
     * Parse criteria in array style.
     * @param array $criteriaArr Criteria in array style.
     * @return $this
     */
    public function parseCriteriaArr($criteriaArr = array()) {
        foreach ($criteriaArr as $methodName => $vals) {
            if (method_exists($this, $methodName)) {
                call_user_func_array(array($this, $methodName), array($vals));
            }
        }
        return $this;
    }

    /**
     * Parse array to generate sql "ON DUPLICATE KEY UPDATE ...".
     * @param array $vals Values to be parsed.
     * <pre>
     * The values will be like this:
     * array(
     *     'colName1' => colVal1,
     *     'colName2' => colVal2,
     *     ...
     * )
     * </pre>
     * @return $this
     */
    public function duplicateUpdate($vals = array()) {
        $updateVals = array();
        foreach ($vals as $colName => $colVal) {
            $updateVals[] = Tea::getDbSqlBuilder()->quoteColumn($colName) . ' = ' . Tea::getDbQuery()->escape($colVal);
        }
        $sql = "ON DUPLICATE KEY UPDATE " . implode(', ', $updateVals);
        $this->criteriaArr[__FUNCTION__] = $vals;
        $this->criteriaSqls[__FUNCTION__] = $sql;
        return $this;
    }

    /**
     * Parse array to generate sql "WHERE ...".
     * @param array $vals Values to be parsed.
     * </pre>
     * The values will be like this:
     * array(
     *     'colName' => 123,
     *     array(
     *         'or:colName:lt' => 456,
     *         'fooName:match' => 'haha',
     *         array(
     *             'bar:gt' => 10,
     *             'barz:between' => array(123, 456)
     *         )
     *     )
     *     ...
     * )
     * This array could be multi-dimensional to support nested "OR", "AND" in where condition.
     * </pre>
     * @return $this
     */
    public function where($vals = array()) {
        $this->_flattenedVals = array();
        $this->criteriaArr[__FUNCTION__] = $vals;
        $this->criteriaSqls[__FUNCTION__] = "WHERE " . $this->getCondValsSql($vals);
        return $this;
    }
    
    /**
     * Parse array to generate sql "GROUP BY ...".
     * @param array $vals Values to be parsed.
     * </pre>
     * The values will be like this:
     * array(
     *     'col1', 'col2', ...
     * )
     * </pre>
     * @return $this
     */
    public function groupBy($vals = array()) {
        $this->criteriaArr[__FUNCTION__] = $vals;
        $this->criteriaSqls[__FUNCTION__] = "GROUP BY " . Tea::getDbSqlBuilder()->quoteColumns($vals);
        return $this;
    }

    /**
     * Parse array to generate sql "HAVING ...".
     * @param array $vals Values to be parsed.
     * </pre>
     * The values will be like this:
     * array(
     *     'colName' => 123,
     *     array(
     *         'or:colName:lt' => 456,
     *         'fooName:match' => 'haha',
     *         array(
     *             'bar:gt' => 10,
     *             'barz:between' => array(123, 456)
     *         )
     *     )
     *     ...
     * )
     * This array could be multi-dimensional to support nested "OR", "AND" in having condition.
     * </pre>
     * @return $this
     */
    public function having($vals = array()) {
        $this->_flattenedVals = array();
        $this->criteriaArr[__FUNCTION__] = $vals;
        $this->criteriaSqls[__FUNCTION__] = "HAVING " . $this->getCondValsSql($vals);
        return $this;
    }
    
    /**
     * Parse array to generate sql "ORDER BY ...".
     * @param array $vals Values to be parsed.
     * <pre>
     * The values will be like this:
     * array(
     *     'col1,col2:desc', 
     *     'col3:asc', 
     *     'col4', // defaults to 'asc'
     *     ...
     * )
     * </pre>
     * @return $this
     */
    public function orderBy($vals = array()) {
        $orderSqls = array();
        foreach ($vals as $v) {
            $parts = array_map('trim', explode(TeaDbCriteria::OP_DELIMITER, $v));
            !array_key_exists($parts[count($parts) - 1], parent::$orderOps) && array_push($parts, 'asc');
            $opOrder = array_pop($parts);
            $colNames = array_map('trim', explode(TeaDbCriteria::COL_DELIMITER, implode(TeaDbCriteria::OP_DELIMITER, $parts)));
            $partsSqls = array();
            foreach ($colNames as $colName) {
                $partsSqls[] = Tea::getDbSqlBuilder()->quoteColumn($colName) . ' ' . parent::$orderOps[$opOrder];
            }
            $orderSqls[] = implode(', ', $partsSqls);
        }
        $this->criteriaArr[__FUNCTION__] = $vals;
        $this->criteriaSqls[__FUNCTION__] = "ORDER BY " . implode(', ', $orderSqls);
        return $this;
    }
    
    /**
     * Parse array to generate sql "LIMIT ...".
     * @param array $vals Values to be parsed.
     * The values will be like this: array(10) or array(10, 50)
     * @return $this
     */
    public function limit($vals = array()) {
        $count = count($vals);
        if ($count === 1) {
            $limitSql = Tea::getDbQuery()->escape($vals[0]);
        } else if ($count > 1) {
            $from = Tea::getDbQuery()->escape($vals[0]);
            $len = Tea::getDbQuery()->escape($vals[1]);
            $limitSql = "{$from}, {$len}";
        } else {
            $limitSql = '';
        }
        $this->criteriaArr[__FUNCTION__] = $vals;
        $this->criteriaSqls[__FUNCTION__] = "LIMIT {$limitSql}";
        return $this;
    }
    
    /**
     * Parse array to generate sql "JOIN ... ON ...".
     * @param array $vals Values to be parsed.
     * <pre>
     * The values will be like this: 
     * array(
     *     'foo' => array('foo.id' => 'tbl.id'),
     *     'left:bar->alias' => array('alias.name' => 'tbl.name'),
     *     'left:bla->alias' => array('alias.name' => 'tbl.name', ':condition' => array(
     *         'alias.id:gt' => 1 // For other conditions, you can use key ':condition' to declare.
     *     ))
     *     ...
     * )
     * Join types: 'inner', 'left', 'right', 'cross', 'natural', defaults to 'inner'.
     * </pre>
     * @return $this
     */
    public function join($vals = array()) {
        $sqlBuilder = Tea::getDbSqlBuilder();
        $joinSqls = array();
        foreach ($vals as $key => $cond) {
            $parts = array_map('trim', explode(TeaDbCriteria::OP_DELIMITER, $key));
            !array_key_exists($parts[0], parent::$joinTypeMap) && array_unshift($parts, 'inner');
            $joinType = array_shift($parts);
            $tblName = array_pop($parts);
            $tblAlias = $sqlBuilder->getTableAlias($tblName);
            $asSql = !empty($tblAlias) ? " AS " . $sqlBuilder->normalQuote($tblAlias) : '';
            $colSqls = array();
            foreach ($cond as $colA => $colB) {
                if ($colA !== ':condition') {
                    $colSqls[] = $sqlBuilder->quoteColumn($colA) . ' = ' . $sqlBuilder->quoteColumn($colB);
                }
            }
            $colSql = implode(' AND ', $colSqls);
            if (isset($cond[':condition'])) {
                $andSql = !empty($colSql) ? ' AND ' : '';
                $colSql .= $andSql . $this->getCondValsSql($cond[':condition']);
            }
            $joinSqls[] = parent::$joinTypeMap[$joinType] . " " . $sqlBuilder->quoteTable($tblName) . $asSql . " ON " . $colSql;
        }
        $this->criteriaArr[__FUNCTION__] = $vals;
        $this->criteriaSqls[__FUNCTION__] = implode(' ', $joinSqls);
        return $this;
    }

    /**
     * Build criteria sql.
     * @param array $buildNames An array of method names.
     * @return string Generated sql string.
     */
    public function build($buildNames = array()) {
        $sqls = array();
        foreach ($buildNames as $name) {
            array_key_exists($name, $this->criteriaSqls) && array_push($sqls, $this->criteriaSqls[$name]);
        }
        return implode(' ', $sqls);
    }
    
    /**
     * Get sql of condition.
     * @param array $vals Values to be parsed.
     * @return string Generated sql of the parsed values.
     */
    protected function getCondValsSql($vals) {
        $this->_flattenedVals = array();
        return $this->parseCondVals($this->flattenCondVals($vals));
    }

    /**
     * Flatten values of condition.
     * @param array $vals Values of where condition to be flattened.
     * @return array Flattened array.
     */
    private function flattenCondVals($vals) {
        foreach ($vals as $key => $v) {
            if (is_int($key) && is_array($v)) {
                array_push($this->_flattenedVals, '(');
                $this->flattenCondVals($v, $this->_flattenedVals);
                array_push($this->_flattenedVals, ')');
            } else {
                $parts = array_map('trim', explode(TeaDbCriteria::OP_DELIMITER, $key));
                !array_key_exists($parts[0], parent::$logicOps) && array_unshift($parts, 'and');
                !array_key_exists($parts[count($parts) - 1], parent::$commonOps) && array_push($parts, 'eq');
                $opLogic = array_shift($parts);
                $opCompare = array_pop($parts);
                $colNames = array_map('trim', explode(TeaDbCriteria::COL_DELIMITER, implode(TeaDbCriteria::OP_DELIMITER, $parts)));
                $last = end($this->_flattenedVals);
                reset($this->_flattenedVals);
                if ($last === '(') {
                    array_pop($this->_flattenedVals);
                    array_push($this->_flattenedVals, $opLogic, '(', array($colNames, $opCompare, $v));
                } else {
                    array_push($this->_flattenedVals, $opLogic, array($colNames, $opCompare, $v));
                }
            }
        }
        return $this->_flattenedVals;
    }

    /**
     * Parse the flattened values of condition to generate sql.
     * @param array $vals The flattened values of where condition.
     * @return string Generated sql of the parsed values.
     */
    private function parseCondVals($vals) {
        if (in_array($vals[0], array('and', 'or'))) {
            $vals = array_slice($vals, 1); // remove first element 'and' or 'or' of the flattened value.
        }
        foreach ($vals as &$val) {
            if (is_array($val)) {
                $val = call_user_func_array(array($this, 'parseCondVal'), $val);
            } else if (is_string($val) && array_key_exists($val, parent::$logicOps)) {
                $val = parent::$logicOps[$val];
            }
        }
        unset($val);
        $parsedStr = implode(' ', $vals);
        return $parsedStr;
    }

    /**
     * Parse one element of the flattened values of condition.
     * @param array $colNames Column names.
     * @param string $op Operator.
     * @param mixed $val Value.
     * @return string Generate sql of the parsed value.
     * @throws TeaDbException
     */
    private function parseCondVal($colNames, $op, $val) {
        $this->throwCondValException($op, $val);
        $parsedStrs = array();
        foreach ($colNames as $colName) {
            if (strpos($colName, '.') !== false && !isset($this->criteriaArr['join'])) {
                $parts = explode('.', $colName);
                $colName = array_pop($parts); // if criteria does not contain join, column name does not need alias.
            }
            $colStr = Tea::getDbSqlBuilder()->quoteColumn($colName);
            $parsedStrs[] = $this->parseCondOp($colStr, $op, $val);
        }
        return implode(' AND ', $parsedStrs);
    }

    /**
     * Parse an operator of one element of the flattened values of condition.
     * @param string $colStr Column name.
     * @param string $op Operator.
     * @param mixed $val Value.
     * @return string Generate sql of the parsed value.
     * @throws TeaDbException
     */
    private function parseCondOp($colStr, $op, $val) {
        $parsedStr = '';
        switch ($op) {
            case 'eq':
            case 'ne':
            case 'gt':
            case 'lt':
            case 'gte':
            case 'lte':
            case 'is':
            case 'is-not':
            case 'regexp':
            case 'not-regexp':
            case 'iregexp':
            case 'not-iregexp':
                $parsedStr = "{$colStr} " . parent::$commonOps[$op] . " " . Tea::getDbQuery()->escape($val);
                break;
            case 'between':
                $val = array_map(array(Tea::getDbQuery(), 'escape'), $val);
                $parsedStr = "{$colStr} BETWEEN {$val[0]} AND {$val[1]}";
                break;
            case 'not-between':
                $val = array_map(array(Tea::getDbQuery(), 'escape'), $val);
                $parsedStr = "{$colStr} NOT BETWEEN {$val[0]} AND {$val[1]}";
                break;
            case 'like':
                $parsedStr = "{$colStr} LIKE " . Tea::getDbQuery()->escape('%' . $val . '%');
                break;
            case 'not-like':
                $parsedStr = "{$colStr} NOT LIKE " . Tea::getDbQuery()->escape('%' . $val . '%');
                break;
            case 'llike':
                $parsedStr = "{$colStr} LIKE " . Tea::getDbQuery()->escape($val . '%');
                break;
            case 'not-llike':
                $parsedStr = "{$colStr} NOT LIKE " . Tea::getDbQuery()->escape($val . '%');
                break;
            case 'rlike':
                $parsedStr = "{$colStr} LIKE " . Tea::getDbQuery()->escape('%' . $val);
                break;
            case 'not-rlike':
                $parsedStr = "{$colStr} NOT LIKE " . Tea::getDbQuery()->escape('%' . $val);
                break;
            case 'lrlike':
                $parsedStr = "{$colStr} LIKE " . Tea::getDbQuery()->escape($val[0] . '%' . $val[1]);
                break;
            case 'not-lrlike':
                $parsedStr = "{$colStr} NOT LIKE " . Tea::getDbQuery()->escape($val[0] . '%' . $val[1]);
                break;
            case 'in':
                $val = array_map(array(Tea::getDbQuery(), 'escape'), $val);
                $parsedStr = "{$colStr} IN (" . implode(', ', $val) . ")";
                break;
            case 'not-in':
                $val = array_map(array(Tea::getDbQuery(), 'escape'), $val);
                $parsedStr = "{$colStr} NOT IN (" . implode(', ', $val) . ")";
                break;
            case 'match':
                $parsedStr = "MATCH ({$colStr}) AGAINST (" . Tea::getDbQuery()->escape($val) . ")";
                break;
            case 'match-bool':
                $parsedStr = "MATCH ({$colStr}) AGAINST (" . Tea::getDbQuery()->escape($val) . " IN BOOLEAN MODE)";
                break;
            case 'match-ex':
                $parsedStr = "MATCH ({$colStr}) AGAINST (" . Tea::getDbQuery()->escape($val) . " WITH QUERY EXPANSION)";
                break;
            default:
                throw new TeaDbException("Could not determine the operator {$op}");
        }
        return $parsedStr;
    }

    /**
     * To throw exception when value type does not match the type expected.
     * @param string $op Operator.
     * @param mixed $val Value.
     * @throws TeaDbException
     */
    private function throwCondValException($op, $val) {
        $opValIsArray = array('between', 'not-between', 'lrlike', 'not-lrlike', 'in', 'not-in');
        if (in_array($op, $opValIsArray) && !is_array($val)) {
            throw new TeaDbException("The value of criteria operator '{$op}' should be array.");
        } else if (!in_array($op, $opValIsArray) && is_array($val)) {
            throw new TeaDbException("The value of criteria operator '{$op}' should not be array.");
        }
    }

}