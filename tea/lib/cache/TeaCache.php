<?php

/**
 * 缓存工厂类。
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.teaframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.teaframework.com/license/
 * @package lib.cache
 */
class TeaCache implements TeaICache {
    
    /**
     * 适配的缓存类实例。
     * @var $this
     */
    protected $_cacheInstance;
    
    /**
     * 构造函数。
     * @param string $constCacheType CACHE_TYPE_*的类常量，默认为CACHE_TYPE_FILE。
     * @param array $cacheInstCntrArgs CACHE_TYPE_*的类常量对应类的构造函数参数。
     */
    public function __construct($constCacheType = self::CACHE_TYPE_FILE, $cacheInstCntrArgs = array()) {
        $rfc = new ReflectionClass($constCacheType);
        $this->_cacheInstance = $rfc->newInstanceArgs($cacheInstCntrArgs);
    }
    
    /**
     * 缓存数据。
     * @param string $key 缓存数据名，圆点标记法别名。
     * @param mixed $val 缓存数据。
     * @param int $expire 过期时间，单位为秒。
     * @return bool
     */
    public function cache($key, $val, $expire = 0) {
        return $this->_cacheInstance->cache($key, $val, $expire);
    }
    
    /**
     * 获取缓存数据。
     * @param string $key 缓存数据名，圆点标记法别名。
     * @return mixed 返回缓存数据，如果失败则返回false。
     */
    public function getCache($key) {
        return $this->_cacheInstance->getCache($key);
    }

}