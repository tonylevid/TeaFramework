<?php

/**
 * TeaTempModel class file.
 * This class is used for Tea::getModel().
 * 
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package base
 */
class TeaTempModel extends TeaModel {

    private $_tableName;

    public function __construct($tableName) {
        parent::__construct();
        $this->_tableName = $tableName;
    }

    public function tableName() {
        return $this->_tableName;
    }

}