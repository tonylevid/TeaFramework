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
    
    /**
     * Convert tag string like 'foo, bar,   barz，boom,，,' to 'foo,bar,barz,boom'.
     * @param string $str Input string.
     * @param mixed $search String or array indicates separator(s), defaults to array('，', ',').
     * @param string $glue Imploded glue.
     * @return string
     */
    public static function trimTags($str, $search = array('，', ','), $glue = ',') {
        $replacedStr = str_replace($search, $glue, $str);
        $strParts = array_filter(array_map('trim', explode($glue, $replacedStr)));
        return implode($glue, $strParts);
    }
    
    /**
     * Xml转换成数组
     * @param string $xmlStr xml字符串
     * @return array
     */
    public static function xmlToArray($xmlStr) {
        $xml = simplexml_load_string($xmlStr);
        return json_decode(json_encode($xml), true);
    }

}