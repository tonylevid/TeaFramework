<?php

interface TeaICache {

    public function cache($key, $val, $expire = 0);

    public function getCache($key);

}