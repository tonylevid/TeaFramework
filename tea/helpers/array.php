<?php

/**
 * Check array is associative or not.
 * @param array $arr Array to be checked.
 * @return bool
 */
function isAssoc($arr) {
    return (is_array($arr) && (count($arr) === 0 || 0 !== count(array_diff_key($arr, array_keys(array_keys($arr))))));
}

/**
 * Check array is multi-dimensional or not.
 * @param array $arr Array to be checked.
 * @return bool
 */
function isMulti($arr) {
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
function flatten($arr, $preserveKeys = true) {
    return iterator_to_array(new RecursiveIteratorIterator(new RecursiveArrayIterator($arr)), $preserveKeys);
}