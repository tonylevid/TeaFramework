<?php

/**
 * 目录帮助类
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.teaframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.teaframework.com/license/
 * @package helper
 */
class DirectoryHelper {

    /**
     * 新建文件夹。
     * @param string $dir 文件夹目录。
     * @param int $mode 权限值。
     * @return bool 成功返回true，失败返回false。
     */
    public static function mkdirs($dir, $mode = 0777) {
        if (is_dir($dir)) {
            return true;
        }
        return mkdir($dir, $mode, true);
    }

    /**
     * 获取目录树形数组。
     * @param string $dir 文件夹目录。
     * @param array $filters 过滤掉的文件夹。
     * @return array 目录树形数组。
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
