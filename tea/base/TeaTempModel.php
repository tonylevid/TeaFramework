<?php

/**
 * TeaTempModel类文件。
 * 此类为了创建临时的模型类。
 * 
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package base
 */
class TeaTempModel extends TeaModel {

    /**
     * 真实表名。
     * @var string
     */
    private $_tableName;

    /**
     * 构造函数。
     * @param string $tableName 真实表名。
     */
    public function __construct($tableName) {
        parent::__construct();
        $this->_tableName = $tableName;
    }

    /**
     * @return string 真实表名。
     */
    public function tableName() {
        return $this->_tableName;
    }

}