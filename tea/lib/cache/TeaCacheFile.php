<?php

class TeaCacheFile implements TeaICache {
    
    public $cacheBasePathAlias = 'protected.cache';
    
    public $cacheExpireKey = '_tea_cache_expire_';
    
    public $cacheKeyKey = '_tea_cache_key_';
    
    public $cacheDataKey = '_tea_cache_data_';

    public function cache($key, $val, $expire = 0) {
        $file = Tea::aliasToPath($this->cacheBasePathAlias . '.' . $key) . '.php';
        $fileFolder = dirname($file);
        if (!is_dir($fileFolder)) {
            DirectoryHelper::mkdirs($fileFolder);
        }
        $data = array(
            $this->cacheExpireKey => intval($expire) > 0 ? time() + intval($expire) : 0,
            $this->cacheKeyKey => $key,
            $this->cacheDataKey => $val
        );
        $dataStr = var_export($data, true);
        $fileStr = <<<FILESTR
<?php
return $dataStr;
FILESTR;
        return file_put_contents($file, $fileStr);
    }
    
    public function getCache($key) {
        $file = Tea::aliasToPath($this->cacheBasePathAlias . '.' . $key) . '.php';
        if (!file_exists($file)) {
            return false;
        }
        $fileContent = include $file;
        if (is_array($fileContent) && isset($fileContent[$this->cacheExpireKey]) && isset($fileContent[$this->cacheDataKey])) {
            $expireTime = intval($fileContent[$this->cacheExpireKey]);
            if ($expireTime > 0 && time() >= $expireTime) {
                return false;
            }
            return $fileContent[$this->cacheDataKey];
        }
        return false;
    }

}