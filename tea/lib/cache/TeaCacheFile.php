<?php

class TeaCacheFile implements TeaICache {

    public $cacheBasePath = null;

    public $cacheExpireKey = '_tea_cache_expire_';

    public $cacheKeyKey = '_tea_cache_key_';

    public $cacheDataKey = '_tea_cache_data_';

    public function __construct($cacheBasePath = null) {
        if (empty($cacheBasePath)) {
            $this->cacheBasePath = Tea::aliasToPath('protected.cache');
        } else {
            $this->cacheBasePath = $cacheBasePath;
        }
    }

    public function cache($key, $val, $expire = 0) {
        $file = $this->cacheBasePath . DIRECTORY_SEPARATOR . Tea::aliasToPath($key) . '.php';
        $fileFolder = dirname($file);
        if (!is_dir($fileFolder)) {
            DirectoryHelper::mkdirs($fileFolder);
        }
        $data = array(
            $this->cacheExpireKey => time() + intval($expire),
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
        $file = $this->cacheBasePath . DIRECTORY_SEPARATOR . Tea::aliasToPath($key) . '.php';
        if (!file_exists($file)) {
            return false;
        }
        $fileContent = include $file;
        if (is_array($fileContent) && isset($fileContent[$this->cacheExpireKey]) && isset($fileContent[$this->cacheDataKey])) {
            $expireTime = intval($fileContent[$this->cacheExpireKey]);
            if ($expireTime !== 0 && time() >= $expireTime) {
                @unlink($file);
                return false;
            }
            return $fileContent[$this->cacheDataKey];
        }
        return false;
    }

}