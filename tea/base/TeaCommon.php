<?php

class TeaCommon {

    public function __call($name, $args) {
        return call_user_func_array("Tea::{$name}", $args);
    }
    
}