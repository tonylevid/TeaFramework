<?php

/**
 * StringHelper class file
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package helper
 */
class StringHelper {

    public static function camelToUnderscore($str) {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $str));
    }

}