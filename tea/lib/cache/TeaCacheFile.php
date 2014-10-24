<?php

class TeaCacheFile implements TeaICache {
    
    public $cacheBasePathAlias = 'protected.cache';
    
    public $cacheExpireKey = '_tea_cache_expire_';

    public function cache($key, $val, $expire = 0) {
        $file = Tea::aliasToPath($this->cacheBasePathAlias . '.' . $key) . '.php';
        if (file_exists($file)) {
            $fileContent = include $file;
            if (is_array($fileContent) && isset($fileContent[$this->cacheExpireKey])) {
                $expireTime = intval($fileContent[$this->cacheExpireKey]);
                if (time() < $expireTime) {
                    return true;
                }
            }
        }
        $fileFolder = dirname($file);
        if (!is_dir($fileFolder)) {
            DirectoryHelper::mkdirs($fileFolder);
        }
    }
    
    public function getCache($key) {
        
    }

}