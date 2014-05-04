<?php

/**
 * TeaMysqlConnection class file
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package db.mysql
 */
class TeaMysqlConnection extends TeaDbConnection {

    /**
     * TeaMysqlSqlBuilder instance.
     * @var TeaMysqlSqlBuilder
     */
    protected $_sqlBuilder;

    /**
     * TeaMysqlCriteriaBuilder new instance.
     * @var TeaMysqlCriteriaBuilder
     */
    protected $_criteriaBuilder;

    /**
     * TeaMysqlSchema instance.
     * @var TeaMysqlSchema
     */
    protected $_schema;

    /**
     * TeaMysqlQuery instance.
     * @var TeaMysqlQuery
     */
    protected $_query;

    /**
     * Connection options.
     * @return array Options array.
     */
    public function connOptions() {
        $connOptions = parent::connOptions();
        $connOptions[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES '{$this->charset}'";
        return $connOptions;
    }

    /**
     * Get TeaMysqlCriteriaBuilder instance.
     * @return TeaMysqlCriteriaBuilder TeaMysqlCriteriaBuilder instance.
     */
    public function getCriteriaBuilder() {
        $this->_criteriaBuilder = new TeaMysqlCriteriaBuilder();
        return $this->_criteriaBuilder;
    }

    /**
     * Get TeaMysqlQuery instance.
     * @return TeaMysqlQuery TeaMysqlQuery instance.
     */
    public function getQuery() {
        if (!$this->_query instanceof TeaMysqlQuery) {
            $this->_query = new TeaMysqlQuery();
        }
        return $this->_query;
    }

    /**
     * Get TeaMysqlSchema instance.
     * @return TeaMysqlSchema TeaMysqlSchema instance.
     */
    public function getSchema() {
        if (!$this->_schema instanceof TeaMysqlSchema) {
            $this->_schema = new TeaMysqlSchema();
        }
        return $this->_schema;
    }
    
    /**
     * Get TeaMysqlSqlBuilder instance.
     * @return TeaMysqlSqlBuilder TeaMysqlSqlBuilder instance.
     */
    public function getSqlBuilder() {
        if (!$this->_sqlBuilder instanceof TeaMysqlSqlBuilder) {
            $this->_sqlBuilder = new TeaMysqlSqlBuilder();
        }
        return $this->_sqlBuilder;
    }

}