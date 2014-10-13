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
                if (is_string($key)) {
                    $arr[$key] = self::mergeArray($arr[$key], $userArr[$key]);
                } else {
                    $arr[] = self::mergeArray($arr[$key], $userArr[$key]);
                }
            } else {
                if (is_string($key)) {
                    $arr[$key] = $val;
                } else if (is_int($key)) {
                    $arr[] = $val;
                }
            }
        }
        return $arr;
    }

    /**
     * Get the keys of continuous values of an array. For example:
     * Change this array:
     * <pre>
     * array(
     *      2 => 11,
     *      3 => 11,
     *      4 => 11,
     *      6 => 12,
     *      7 => 13,
     *      8 => 13,
     *      10 => 11,
     *      11 => 11,
     *      12 => 14
     * )
     * </pre>
     * to this one:
     * <pre>
     * array(
     *     array(2, 3, 4),
     *     array(6),
     *     array(7, 8),
     *     array(10, 11),
     *     array(12)
     * )
     * </pre>
     * @param array $arr Input array.
     * @return array Keys of continuous values.
     */
    public static function getArrKeysOfCV($arr) {
        $rst = array();
        array_walk($arr, function($value, $key) use (&$rst) {
            static $v;
            if ($value == $v) {
                $rst[max(array_keys($rst))][] = $key;
            } else {
                $rst[] = array($key);
            }
            $v = $value;
        });
        return $rst;
    }
    
    /**
     * Filter data with callback.
     * @param mixed $filter String callback or callback.
     * @param array $arr Data to be filtered.
     * @return array Return filtered data on success, false on failure.
     */
    public static function filterData($filter, $arr) {
        if (is_array($arr)) {
            foreach ($arr as $key => $val) {
                if (is_array($val)) {
                    $arr[$key] = self::filterData($filter, $val);
                } else {
                    $arr[$key] = call_user_func($filter, $val);
                }
            }
            return $arr;
        }
        return false;
    }

}