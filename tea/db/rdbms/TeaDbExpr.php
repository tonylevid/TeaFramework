<?php

/**
 * TeaDbExpr class file
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package db
 */
class TeaDbExpr {

    public $expr;
    public $params = array();

    public function __construct($expr, $params = array()) {
        $this->expr = $expr;
        $this->params = $params;
    }

    public function __toString() {
        return $this->expr;
    }

}