<?php

class TeaCommon {

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