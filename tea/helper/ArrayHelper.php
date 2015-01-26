<?php

/**
 * 数组帮助类
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.teaframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.teaframework.com/license/
 * @package helper
 */
class ArrayHelper {

    /**
     * 检查数组是否为关联数组。
     * @param array $arr 需要检查的数组。
     * @return bool
     */
    public static function isAssoc($arr) {
        return (is_array($arr) && (count($arr) === 0 || 0 !== count(array_diff_key($arr, array_keys(array_keys($arr))))));
    }

    /**
     * 检查数组是否为多维数组。
     * @param array $arr 需要检查的数组。
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
     * 把多维数组扁平化为一维数组。
     * @param array $arr 需要扁平化的数组。
     * @param bool $preserveKeys 是否保留键名，默认为true。
     * @return array
     */
    public static function flatten($arr, $preserveKeys = true) {
        return iterator_to_array(new RecursiveIteratorIterator(new RecursiveArrayIterator($arr)), $preserveKeys);
    }

    /**
     * 覆盖合并两个数组。此方法在合并配置时非常有用。
     * @param array $arr 被覆盖的数组。
     * @param array $userArr 用于覆盖$arr的数组。
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
     * 获取连续相同键值的键名数组。例如：
     * 可以把此数组：
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
     * 转换为如下数组：
     * <pre>
     * array(
     *     array(2, 3, 4),
     *     array(6),
     *     array(7, 8),
     *     array(10, 11),
     *     array(12)
     * )
     * </pre>
     * @param array $arr 需要转换的数组。
     * @return array
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
     * 把类似数据库结果集的数组转换成唯一值索引为键名的数组。
     * @param array $arr 类似数据库结果集的数组。
     * @param string $pkColName 唯一值的键名。
     * @return array
     */
    public static function pkIndex($arr, $pkColName = 'id') {
        $ids = array_map(function($row) use ($pkColName) {return $row[$pkColName];}, $arr);
        $arr = array_combine($ids, $arr);
        return $arr;
    }
    
    /**
     * 获取树形数组。
     * @param array $arr 类似数据库结果集的数组。
     * @param array $options 树形数组选项，默认选项请查看方法内的$options变量。
     * @return array
     */
    public static function getTree($arr, $options = array()) {
        $options = array_merge(array(
            'idKey' => 'id',
            'pidKey' => 'pid',
            'childrenKey' => 'children'
        ), $options);
        list($idKey, $pidKey, $childrenKey) = array($options['idKey'], $options['pidKey'], $options['childrenKey']);
        $tree = array();
        if (is_array($arr) && !empty($arr)) {
            $ids = array_map(function($row) use ($idKey) {return $row[$idKey];}, $arr);
            $arr = array_combine($ids, $arr);
            foreach ($arr as $item) {
                if (isset($arr[$item[$pidKey]])) {
                    $arr[$item[$pidKey]][$childrenKey][] =& $arr[$item[$idKey]];
                } else {
                    $tree[] =& $arr[$item[$idKey]];
                }
            }
        }
        return $tree;
    }
    
    /**
     * 通过字段名排序类似数据库结果集的数组，请参看array_multisort()函数。
     * @param array $data1 需要排序的数组。
     * @param string $colName 排序字段名。
     * @param mixed $sortOrder SORT_*的排序常量。
     * @return array
     */
    public static function orderBy() {
        $args = func_get_args();
        $data = array_shift($args);
        foreach ($args as $n => $field) {
            if (is_string($field)) {
                $tmp = array();
                foreach ($data as $key => $row) {
                    $tmp[$key] = $row[$field];
                }
                $args[$n] = $tmp;
            }
        }
        $args[] = &$data;
        call_user_func_array('array_multisort', $args);
        return array_pop($args);
    }
    
    /**
     * Deep first sort array by id path.
     * @param array $arr Result array. The array index must be id(primary key), see self::pkIndex().
     * @param array $options Field map.
     * @param int $padZeroLen Number of padding zero.
     * @return array Sorted array.
     */
    public static function idPathDeepFirstSort($arr, $options = array(), $padZeroLen = 8) {
        $options = array_merge(array(
            'idKey' => 'id',
            'idPathKey' => 'id_path',
            'sortKey' => 'sort'
        ), $options);
        $idPathSortKey = '_id_path_sort_';
        list($idKey, $idPathKey, $sortKey) = array($options['idKey'], $options['idPathKey'], $options['sortKey']);
        if (is_array($arr) && !empty($arr)) {
            foreach ($arr as $key => &$val) {
                if (!isset($val[$idPathKey])) {
                    $val[$idPathKey] = '';
                }
                if (empty($val[$idPathKey])) {
                    $val[$idPathSortKey] = '';
                    continue;
                }
                $idParts = explode('.', $val[$idPathKey]);
                foreach ($idParts as $i => $v) {
                    $idParts[$i] = str_pad($v, $padZeroLen, '0', STR_PAD_LEFT);
                    if (isset($arr[$v])){
                       if (!isset($arr[$v][$sortKey])) {
                           $arr[$v][$sortKey] = 1;
                       }
                       $idParts[$i] = str_pad($arr[$v][$sortKey], $padZeroLen, '0', STR_PAD_LEFT) . $idParts[$i];
                    }
                }
                $val[$idPathSortKey] = implode('.', $idParts);
            }
            return self::orderBy($arr, $idPathSortKey, SORT_ASC);
        }
        return $arr;
    }
    
    /**
     * Return the values from a single column in the input array.
     * @param array $arr A multi-dimensional array (record set) from which to pull a column of values.
     * @param string $colName The column of values to return.
     * @return array
     */
    public static function arrayColumn($arr, $colName) {
        if (function_exists('array_column')) {
            return array_column($arr, $colName);
        }
        return array_map(function($row) use ($colName) {
            return $row[$colName];
        }, $arr);
    }
    
    /**
     * 二维化数组（处理类似$_POST的多维数组）。
     * 如可以把以下格式数组：
     * <pre>
     * array(
     *     'foo' => array(1, 2),
     *     'bar' => array(10, 20)
     * );
     * </pre>
     * 处理成以下格式的数组：
     * <pre>
     * array(
     *     0 => array(
     *         'foo' => 1,
     *         'bar' => 10
     *     ),
     *     1 => array(
     *         'foo' => 2,
     *         'bar' => 20
     *     )
     * )
     * </pre>
     * @param array $arr 待处理数组。
     * @param array $filterKeys 需要被处理的键名过滤。
     * @return array 二维化后的数组。
     */
    public static function normalize($arr, $filterKeys = array()) {
        $filterKeys = empty($filterKeys) ? array_keys($arr) : $filterKeys;
        $newArr = array();
        foreach ($arr as $key => $items) {
            if (in_array($key, $filterKeys)) {
                foreach ($items as $i => $v) {
                    $newArr[$i][$key] = $v;
                }
            }
        }
        return $newArr;
    }
    
    /**
     * 递归地删除数组中的空值。
     * @param array $arr 待处理数组。
     * @param array $emptyVals 表示空值的数组，默认采用php函数empty。
     * @return array 处理后的数组。
     */
    public static function removeEmptyItems($arr, $emptyVals = array()) {
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $arr[$key] = self::removeEmptyItems($arr[$key], $emptyVals);
            }
            if (is_array($emptyVals) && !empty($emptyVals)) {
                if (in_array($arr[$key], $emptyVals, true)) {
                    unset($arr[$key]);
                }
            } else {
                if (empty($arr[$key])) {
                    unset($arr[$key]);
                }
            }
        }
        return $arr;
    }

}