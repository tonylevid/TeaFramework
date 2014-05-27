<?php

/**
 * TeaMysqlSchema class file
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package db.mysql
 */
class TeaMysqlSchema extends TeaDbSchema {
    
    /**
     * Get status information of all tables. The return array will be like this:
     * array(
     *     'table1' => array(
     *         'Name' => 'table_name',
     *         'Engine' => 'MyISAM',
     *         'Version' => '10',
     *         'Row_format' => 'Dynamic',
     *         'Rows' => '194',
     *         'Avg_row_length' => '130',
     *         'Data_length' => '25304',
     *         'Max_data_length' => '281474976710655',
     *         'Index_length' => '12288',
     *         'Data_free' => '0',
     *         'Auto_increment' => '205',
     *         'Create_time' => '2013-10-17 10:48:44',
     *         'Update_time' => '2013-10-17 11:36:07',
     *         'Check_time' => '',
     *         'Collation' => 'utf8_general_ci',
     *         'Checksum' => '',
     *         'Create_options' => '',
     *         'Comment' => 'table comment'
     *     ),
     *     'table2' => array(blabla...same as above),
     * )
     * @param string $dbname Database name. If empty, it will be the the current database.
     * @return array Status information of all tables.
     */
    public function getDbTablesInfo($dbname = null) {
        $sql = Tea::getDbSqlBuilder()->showTableStatus($dbname);
        $rst = Tea::getDbQuery()->query($sql)->fetchRows();
        $r = array();
        foreach ($rst as $info) {
            $r[$info['Name']] = $info;
        }
        return $r;
    }
    
    /**
     * Get status information of one table. The return array will be like an element in TeaMysqlSchema::getDbTablesInfo() returning array.
     * @param string $tblName Table name.
     * @param string $dbname Database name. If empty, it will be the the current database.
     * @return mixed Return status information array of one table on success, false on failure.
     */
    public function getDbTableInfo($tblName, $dbname = null) {
        $tblsInfo = $this->getDbTablesInfo($dbname);
        return isset($tblsInfo[$tblName]) ? $tblsInfo[$tblName] : false;
    }
    
    /**
     * Get create table information. The return array will be like this:
     * array(
     *     'Table' => 'tableName',
     *     'Create Table' => 'CREATE TABLE `tableName`...create table full sql'
     * )
     * @param string $tblName Table name.
     * @return array Create table information.
     */
    public function getCreateTable($tblName) {
        $sql = Tea::getDbSqlBuilder()->showCreateTable($tblName);
        return Tea::getDbQuery()->query($sql)->fetchRow();
    }
    
    /**
     * Get all columns information of a table. The return array will be like this:
     * array(
     *     'comment_id' => array(
     *         'Field' => 'id',
     *         'Type' => 'bigint(20)',
     *         'Collation' => NULL,
     *         'Null' => 'No',
     *         'Key' => 'PRI',
     *         'Default' => NULL,
     *         'Extra' => 'auto_increment',
     *         'Privileges' => 'select,insert,update,references',
     *         'Comment' => 'table comment'
     *     )
     * )
     * @param string $tblName Table name.
     * @return array All columns information of a table.
     */
    public function getTableColumns($tblName) {
        $sql = Tea::getDbSqlBuilder()->showTableColumns($tblName);
        $rst = Tea::getDbQuery()->query($sql)->fetchRows();
        $r = array();
        foreach ($rst as $col) {
            $r[$col['Field']] = $col;
        }
        return $r;
    }
    
    /**
     * Get one column information of a table. The return array will be like an element in TeaMysqlSchema::getTableColumns() returning array.
     * @param string $tblName Table name.
     * @param string $colName Column name.
     * @return array One column information of a table.
     */
    public function getTableColumn($tblName, $colName) {
        $tblCols = $this->getTableColumns($tblName);
        return isset($tblCols[$colName]) ? $tblCols[$colName] : false;
    }
    
    /**
     * Get all indexes information of a table. The return array will be like this:
     * array(
     *     'PRIMARY' => array(
     *         1 => array(
     *             'Table' => 'tableName',
     *             'Non_unique' => '0',
     *             'Key_name' => 'PRIMARY',
     *             'Seq_in_index' => '1', // this is the same as the array index
     *             'Column_name' => 'id',
     *             'Collation' => 'A',
     *             'Cardinality' => '20440',
     *             'Sub_part' => NULL,
     *             'Packed' => NULL,
     *             'Null' => '',
     *             'Index_type' => 'BTREE',
     *             'Comment' => '',
     *             'Index_comment' => ''
     *         ),
     *         2 => array(...)
     *     ),
     *     'another' => array(...)
     * )
     * @param string $tblName Table name.
     * @return array All indexes information of a table.
     */
    public function getTableIndexes($tblName) {
        $sql = Tea::getDbSqlBuilder()->showTableIndex($tblName);
        $rst = Tea::getDbQuery()->query($sql)->fetchRows();
        $r = array();
        foreach ($rst as $index) {
            $r[$index['Key_name']][(int) $index['Seq_in_index']] = $index;
        }
        return $r;
    }
    
    /**
     * Get one index information of a table. The return array will be like an element in TeaMysqlSchema::getTableIndexes() returning array.
     * @param string $tblName Table name.
     * @param string $indexName Index name.
     * @return mixed Return one index information array of a table on success, false on failure.
     */
    public function getTableIndex($tblName, $indexName) {
        $tblIndexes = $this->getTableIndexes($tblName);
        return isset($tblIndexes[$indexName]) ? $tblIndexes[$indexName] : false;
    }
    
    /**
     * Get 'PRIMARY' index column names.
     * @param string $tblName Table name.
     * @return array 'PRIMARY' index column names.
     */
    public function getPkColumnNames($tblName) {
        $colsInfo = $this->getTableIndex($tblName, 'PRIMARY');
        $colNames = array();
        foreach ($colsInfo as $info) {
            if (isset($info['Column_name'])) {
                $colNames[] = $info['Column_name'];
            }
        }
        return $colNames;
    }
    
    /**
     * Get all create column definitions of a table. The return array will be like this:
     * array(
     *     'id' => "bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'comment id'",
     *     'username' => "varchar(32) NOT NULL DEFAULT '' COMMENT 'username'",
     *     ...
     * )
     * @param string $tblName Table name.
     * @return array All create column definitions of a table.
     */
    public function getCreateColumns($tblName) {
        $tblInfo = $this->getCreateTable($tblName);
        if (isset($tblInfo['Create Table'])) {
            $sql = $tblInfo['Create Table'];
        } else {
            $row = array_values($tblInfo);
            $sql = $row[1];
        }
        $createCols = array();
        if (preg_match_all('/^\s*`(.*?)`\s+(.*?),?$/m', $sql, $matches)) {
            foreach ($matches[1] as $k => $colName) {
                $createCols[$colName] = $matches[2][$k];
            }
        }
        return $createCols;
    }
    
    /**
     * Get one create column definition of a table. The return array will be like an element in TeaMysqlSchema::getCreateColumns() returning array.
     * @param string $tblName Table name.
     * @param string $colName Column name.
     * @return mixed Return one create column definition string of a table on success, false on failure.
     */
    public function getCreateColumn($tblName, $colName) {
        $createCols = $this->getCreateColumns($tblName);
        return isset($createCols[$colName]) ? $createCols[$colName] : false;
    }

}