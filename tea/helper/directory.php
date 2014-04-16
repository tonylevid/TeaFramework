<?php

/**
 * Make directories recursively.
 * @param string $dir The directory path.
 * @param int $mode Directory mode number.
 * @return bool
 */
function mkdirs($dir, $mode = 0777) {
    if (!is_dir($dir)) {
        $this->mkdirs(dirname($dir), $mode);
        return @mkdir($dir, $mode);
    }
    return true;
}

/**
 * Get directory tree information array.
 * @param string $dir The directory path.
 * @param array $filters Directories or files to be filtered.
 * @return array Directory tree.
 */
function dirTree($dir, $filters = array()) {
    $dirs = array_diff(scandir($dir), array_merge(array('.', '..'), $filters));
    $dirArr = array();
    foreach ($dir as $d) {
        if (is_dir($dir . DIRECTORY_SEPARATOR. $d)) {
            $dirArr[$d] = $this->dirTree($dir . DIRECTORY_SEPARATOR . $d, $filters);
        } else {
            $dirArr[] = $d;
        }
    }
    return $dirArr;
}
