<?php

/**
 * TeaCommon class file.
 * You can call Tea::method() with $this->method() style after extends TeaCommon.
 * 
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package base
 */
class TeaCommon {

    /**
     * Magic method __call.
     * @param string $name Method name.
     * @param array $args Array of method parameters.
     * @return mixed
     */
    public function __call($name, $args) {
        if (in_array($name, get_class_methods('Tea'))) {
            return call_user_func_array("Tea::{$name}", $args);
        } else {
            $className = get_class($this);
            $trace = debug_backtrace();
            trigger_error("Call to undefined method {$className}::{$name}() in {$trace[0]['file']} on line {$trace[0]['line']}", E_USER_ERROR);
        }
    }
    
}