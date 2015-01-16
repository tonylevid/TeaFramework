<?php

/**
 * 分页基础类。
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.teaframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.teaframework.com/license/
 * @package lib.pager
 */
class TeaBasePager {

    /**
     * 页码索引值在$_REQUEST数组中的键名，默认为'page'。
     * @var string
     */
    private $_pageName = 'page';

    /**
     * 每页数据条数，默认为10。
     * @var int
     */
    private $_itemsPerPage = 10;

    /**
     * 总数据条数。
     * @var int
     */
    private $_itemsTotal = 0;

    /**
     * Tea::createUrl()的参数数组用于TeaBasePager::createPageUrl()。
     * @var array
     */
    private $_createUrlArgs = array();

    /**
     * 构造函数，设置总数据条数。
     * @param int $itemsTotal 总数据条数。
     * @param string $route 路由字符串，如果为空，则默认为当前url的pathinfo。
     * @param array $queries $_GET数组，如果为空，则默认为当前$_GET数组。
     * @param string $anchor url后的锚点。
     */
    public function __construct($itemsTotal, $route = '', $queries = array(), $anchor = null) {
        $this->setItemsTotal($itemsTotal);
        $this->setCreateUrlArgs($route, $queries, $anchor);
    }

    /**
     * 设置页码索引键名。
     * @param string $pageName 页码索引值在$_REQUEST数组中的键名。
     * @return $this
     */
    public function setPageName($pageName) {
        $this->_pageName = $pageName;
        return $this;
    }

    /**
     * 获取页码索引键名。
     * @return string 页码索引值在$_REQUEST数组中的键名。
     */
    public function getPageName() {
        return $this->_pageName;
    }

    /**
     * 设置每页数据条数。
     * @param int $itemsPerPage 每页数据条数。
     * @return $this
     */
    public function setItemsPerPage($itemsPerPage) {
        $this->_itemsPerPage = intval($itemsPerPage);
        return $this;
    }

    /**
     * 获取每页数据条数。
     * @return int 每页数据条数。
     */
    public function getItemsPerPage() {
        return $this->_itemsPerPage;
    }

    /**
     * 设置总数据条数。
     * @param int $itemsTotal 总数据条数。
     * @return $this
     */
    public function setItemsTotal($itemsTotal) {
        $this->_itemsTotal = intval($itemsTotal);
        return $this;
    }

    /**
     * 获取总数据条数。
     * @return int 总数据条数。
     */
    public function getItemsTotal() {
        return $this->_itemsTotal;
    }

    /**
     * 设置Tea::createUrl()的参数数组用于TeaBasePager::createPageUrl()。
     * @param string $route 路由字符串，如果为空，则默认为当前url的pathinfo。
     * @param array $queries $_GET数组，如果为空，则默认为当前$_GET数组。
     * @param string $anchor url后的锚点。
     * @return $this
     */
    public function setCreateUrlArgs($route = '', $queries = array(), $anchor = null) {
        if (empty($route)) {
            $pathinfo = Tea::$request->getPathinfo();
            $route = preg_replace('/' . preg_quote(Tea::getConfig('TeaRouter.urlSuffix'), '/') . '$/', '', $pathinfo);
            $route = ltrim(rtrim($route, '/'), '/');
        }
        if (empty($queries)) {
            $queries = Tea::$request->getQuery();
        }
        $this->_createUrlArgs = array($route, $queries, $anchor);
        return $this;
    }

    /**
     * 获取Tea::createUrl()的参数数组用于TeaBasePager::createPageUrl()。
     * @return array Tea::createUrl()的参数数组用于TeaBasePager::createPageUrl()。
     */
    public function getCreateUrlArgs() {
        return $this->_createUrlArgs;
    }

    /**
     * 获取总页码数。
     * @return int 总页码数。
     */
    public function getPagesTotal() {
        return (int) (($this->getItemsTotal() + $this->getItemsPerPage() - 1) / $this->getItemsPerPage());
    }

    /**
     * 设置从零开始的页码索引。
     * @param int $pageOffset 从零开始的页码索引。
     * @return $this
     */
    public function setPageOffset($pageOffset) {
        $pageName = $this->getPageName();
        $_REQUEST[$pageName] = intval($pageOffset) + 1;
        return $this;
    }

    /**
     * 获取从零开始的页码索引。
     * @return int 从零开始的页码索引。
     */
    public function getPageOffset() {
        $pageName = $this->getPageName();
        $pagesTotal = $this->getPagesTotal();
        $pagesTotalOffset = $pagesTotal > 0 ? $pagesTotal - 1 : 0;
        $curPage = isset($_REQUEST[$pageName]) ? intval($_REQUEST[$pageName]) - 1 : 0;
        if ($curPage < 0) {
            $curPage = 0;
        } else if ($curPage > $pagesTotalOffset) {
            $curPage = $pagesTotalOffset;
        }
        return $curPage;
    }

    /**
     * 设置从一开始的页码索引。
     * @param int $pageNum 从一开始的页码索引。
     * @return $this
     */
    public function setPageNum($pageNum) {
        $pageName = $this->getPageName();
        $_REQUEST[$pageName] = intval($pageNum);
        return $this;
    }

    /**
     * 获取从一开始的页码索引。
     * @return int 从一开始的页码索引。
     */
    public function getPageNum() {
        return $this->getPageOffset() + 1;
    }

    /**
     * 获取从零开始数据条数索引。
     * @return int 从零开始数据条数索引。
     */
    public function getItemOffset() {
        return $this->getPageOffset() * $this->getItemsPerPage();
    }

    /**
     * 获取sql limit参数数组，如 array(0, 10)。
     * @return array sql limit参数数组。
     */
    public function getLimitArr() {
        return array($this->getItemOffset(), $this->getItemsPerPage());
    }

    /**
     * 获取limit criteria。
     * @param mixed $criteria TeaDbCriteria子类实例或者criteria数组。
     * @return mixed TeaDbCriteria子类实例或者criteria数组。
     */
    public function getLimitCriteria($criteria = array()) {
        $limitArr = $this->getLimitArr();
        if ($criteria instanceof TeaDbCriteria) {
            $criteria->limit($limitArr);
        } else if (is_array($criteria) && !empty($criteria)) {
            $criteria['limit'] = $limitArr;
        } else {
            $criteria = array(
                'limit' => $limitArr
            );
        }
        return $criteria;
    }

    /**
     * 根据从零开始的页码索引获取分页url。
     * @param int $pageOffset 从零开始的页码索引。
     * @return string 生成的分页url。
     */
    public function createPageUrl($pageOffset) {
        $pageName = $this->getPageName();
        $pagesTotal = $this->getPagesTotal();
        $createUrlArgs = $this->getCreateUrlArgs();
        $pageOffset = intval($pageOffset);
        $pagesTotalOffset = $pagesTotal > 0 ? $pagesTotal - 1 : 0;
        if ($pageOffset < 0) {
            $pageOffset = 0;
        } else if ($pageOffset > $pagesTotalOffset) {
            $pageOffset = $pagesTotalOffset;
        }
        if (isset($createUrlArgs[1]) && is_array($createUrlArgs[1])) {
            $createUrlArgs[1][$pageName] = $pageOffset + 1;
        } else {
            $createUrlArgs[1] = array($pageName => $pageOffset + 1);
        }
        $url = call_user_func_array('Tea::createUrl', $createUrlArgs);
        return $url;
    }

}