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

    /**
     * Convert camel style string to underscore style string.
     * @param string $str String to be converted.
     * @return string
     */
    public static function camelToUnderscore($str) {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $str));
    }

    /**
     * Convert underscore style string to camel style string.
     * @param string $str String to be converted.
     * @return string
     */
    public static function underscoreToCamel($str) {
        return lcfirst(implode('', array_map('ucfirst', explode('_', $str))));
    }

}