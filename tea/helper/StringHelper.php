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
     * 把驼峰命名字符串转换成下划线命名字符串。
     * @param string $str 待转换字符串。
     * @return string
     */
    public static function camelToUnderscore($str) {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $str));
    }

    /**
     * 把下划线命名字符串转换成驼峰命名字符串。
     * @param string $str 待转换字符串。
     * @return string
     */
    public static function underscoreToCamel($str) {
        return lcfirst(implode('', array_map('ucfirst', explode('_', $str))));
    }
    
    /**
     * 把标签字符串如'foo, bar,   barz，boom,，,'转换成'foo,bar,barz,boom'这样的字符串。
     * @param string $str 待转换字符串。
     * @param mixed $search 表示分隔符的字符串或者数组，默认为array('，', ',')。
     * @param string $glue 粘黏的字符串，默认为','。
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
    
    /**
     * 省略字符串。
     * @param string $str 待省略字符串。
     * @param int $length 字符最大长度。
     * @param string $suffix 超过字符最大长度省略后缀字符串，默认为'...'。
     * @return string
     */
    public static function omit($str, $length = 30, $suffix = '...') {
        $strLen = mb_strlen($str);
        if ($strLen <= $length) {
            return $str;
        }
        return mb_substr($str, 0, $length) . $suffix;
    }
    
    /**
     * 省略字符串，字符串长度将计算html_entity_decode后的字符串。
     * @param string $str 待省略字符串。
     * @param int $length 字符最大长度。
     * @param string $suffix 超过字符最大长度省略后缀字符串，默认为'...'。
     * @return string
     */
    public static function omitWithHtmlEntities($str, $length = 30, $suffix = '...') {
        $str = html_entity_decode($str);
        return htmlentities(self::omit($str));
    }

}