<?php

/**
 * DirectoryHelper class file
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package helper
 */
class DirectoryHelper {

    /**
     * Make directories recursively.
     * @param string $dir The directory path.
     * @param int $mode Directory mode number.
     * @return bool
     */
    public static function mkdirs($dir, $mode = 0777) {
        return mkdir($dir, $mode, true);
    }

    /**
     * Get directory tree information array.
     * @param string $dir The directory path.
     * @param array $filters Directories or files to be filtered.
     * @return array Directory tree.
     */
    public static function dirTree($dir, $filters = array()) {
        $dirs = array_diff(scandir($dir), array_merge(array('.', '..'), $filters));
        $dirArr = array();
        foreach ($dirs as $d) {
            if (is_dir($dir . DIRECTORY_SEPARATOR. $d)) {
                $dirArr[$d] = self::dirTree($dir . DIRECTORY_SEPARATOR . $d, $filters);
            } else {
                $dirArr[] = $d;
            }
        }
        return $dirArr;
    }

}
