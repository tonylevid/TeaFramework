<?php

class TeaCommon {

    public function __call($name, $args) {
        return call_user_func_array("Tea::{$name}", $args);
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
    
}