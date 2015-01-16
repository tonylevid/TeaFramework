<?php

/**
 * 缓存接口。
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.teaframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.teaframework.com/license/
 * @package lib.cache
 */
interface TeaICache {
    
    /**
     * 缓存类型：文件。
     */
    const CACHE_TYPE_FILE = 'TeaCacheFile';

    /**
     * 缓存数据。
     * @param string $key 缓存数据名，圆点标记法别名。
     * @param mixed $val 缓存数据。
     * @param int $expire 过期时间，单位为秒。
     * @return bool
     */
    public function cache($key, $val, $expire = 0);
    
    /**
     * 获取缓存数据。
     * @param string $key 缓存数据名，圆点标记法别名。
     * @return mixed 返回缓存数据，如果失败则返回false。
     */
    public function getCache($key);

}