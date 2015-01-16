<?php

/**
 * 文件缓存类。
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.teaframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.teaframework.com/license/
 * @package lib.cache
 */
class TeaCacheFile implements TeaICache {
    
    /**
     * 缓存目录路径。
     * @var string
     */
    public $cacheBasePath = null;
    
    /**
     * 缓存过期的键名。
     * @var string
     */
    public $cacheExpireKey = '_tea_cache_expire_';
    
    /**
     * 缓存键名的键名。
     * @var string
     */
    public $cacheKeyKey = '_tea_cache_key_';
    
    /**
     * 缓存数据的键名。
     * @var string
     */
    public $cacheDataKey = '_tea_cache_data_';
    
    /**
     * 构造函数。
     * @param string $cacheBasePath 缓存目录路径。
     */
    public function __construct($cacheBasePath = null) {
        if (empty($cacheBasePath)) {
            $this->cacheBasePath = Tea::aliasToPath('protected.cache');
        } else {
            $this->cacheBasePath = $cacheBasePath;
        }
    }
    
    /**
     * 缓存数据。
     * @param string $key 缓存数据名，圆点标记法别名。
     * @param mixed $val 缓存数据。
     * @param int $expire 过期时间，单位为秒。
     * @return bool
     */
    public function cache($key, $val, $expire = 0) {
        $file = $this->cacheBasePath . DIRECTORY_SEPARATOR . Tea::aliasToPath($key) . '.php';
        $fileFolder = dirname($file);
        if (!is_dir($fileFolder)) {
            DirectoryHelper::mkdirs($fileFolder);
        }
        $data = array(
            $this->cacheExpireKey => intval($expire) !== 0 ? time() + intval($expire) : 0,
            $this->cacheKeyKey => $key,
            $this->cacheDataKey => $val
        );
        $dataStr = var_export($data, true);
        $fileStr = <<<FILESTR
<?php
return $dataStr;
FILESTR;
        $writtenBytes = file_put_contents($file, $fileStr);
        return $writtenBytes === false ? false : true;
    }
    
    /**
     * 获取缓存数据。
     * @param string $key 缓存数据名，圆点标记法别名。
     * @return mixed 返回缓存数据，如果失败则返回false。
     */
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