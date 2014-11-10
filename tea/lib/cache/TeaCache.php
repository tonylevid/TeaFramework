<?php

class TeaCache implements TeaICache {
    
    const CACHE_TYPE_FILE = 'TeaCacheFile';
    
    protected $_cacheInstance;

    public function __construct($constCacheType = self::CACHE_TYPE_FILE, $cacheInstCntrArgs = array()) {
        $rfc = new ReflectionClass($constCacheType);
        $this->_cacheInstance = $rfc->newInstanceArgs($cacheInstCntrArgs);
    }
    
    public function cache($key, $val, $expire = 0) {
        return $this->_cacheInstance->cache($key, $val, $expire);
    }
    
    public function getCache($key) {
        return $this->_cacheInstance->getCache($key);
    }

}