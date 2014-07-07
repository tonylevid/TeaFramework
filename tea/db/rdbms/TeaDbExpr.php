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
        if (empty($this->params)) {
            return (string) ($this->expr);
        }
        $replacedExpr = str_replace(array_merge(array('?'), array_keys($this->params)), '%s', $this->expr);
        return vsprintf($replacedExpr, array_map(array(Tea::getDbQuery(), 'escape'), $this->params));
    }

}