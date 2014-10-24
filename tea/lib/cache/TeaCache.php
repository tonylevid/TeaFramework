<?php

class TeaCache implements TeaICache {
    
    const CACHE_TYPE_FILE = 'TeaCacheFile';
    
    protected $_cacheInstance;

    public function __construct($constCacheType = self::CACHE_TYPE_FILE) {
        $this->_cacheInstance = new $constCacheType();
    }
    
    public function cache($key, $val, $expire = 0) {
        return $this->_cacheInstance->cache($key, $val, $expire);
    }
    
    public function getCache($key) {
        return $this->_cacheInstance->getCache($key);
    }

}