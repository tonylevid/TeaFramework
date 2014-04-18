<?php

/**
 * ArrayHelper class file
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package helper
 */
class ArrayHelper {

    /**
     * Check array is associative or not.
     * @param array $arr Array to be checked.
     * @return bool
     */
    public static function isAssoc($arr) {
        return (is_array($arr) && (count($arr) === 0 || 0 !== count(array_diff_key($arr, array_keys(array_keys($arr))))));
    }
    
    /**
     * Check array is multi-dimensional or not.
     * @param array $arr Array to be checked.
     * @return bool
     */
    public static function isMulti($arr) {
        foreach ($arr as $v) {
            if (is_array($v)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Flatten multi-dimensional array to one-dimensional.
     * @param array $arr Array to be flattened.
     * @param bool $preserveKeys Preserve keys or not.
     * @return array
     */
    public static function flatten($arr, $preserveKeys = true) {
        return iterator_to_array(new RecursiveIteratorIterator(new RecursiveArrayIterator($arr)), $preserveKeys);
    }

    /**
     * Merge two arrays recursively with overwriting
     * @param array $arr
     * @param array $userArr
     * @return array
     */
    public static function mergeArray($arr, $userArr) {
        foreach ($userArr as $key => $val) {
            if (array_key_exists($key, $arr) && is_array($val)) {
                $arr[$key] = self::mergeArray($arr[$key], $userArr[$key]);
            } else {
                $arr[$key] = $val;
            }
        }
        return $arr;
    }

}