<?php

/**
 * TeaBasePager class file.
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package lib
 */
class TeaBasePager {

    /**
     * String key in $_REQUEST to get page index, defaults to 'page'.
     * @var string
     */
    private $_pageName = 'page';

    /**
     * Items number per page.
     * @var int
     */
    private $_itemsPerPage = 10;

    /**
     * Total number of items.
     * @var int
     */
    private $_itemsTotal = 0;

    /**
     * Constructor.
     * @param int $itemsTotal Total number of items.
     */
    public function __construct($itemsTotal) {
        $this->setItemsTotal($itemsTotal);
    }

    /**
     * Set page name.
     * @param string $pageName String key in $_REQUEST to get page index.
     * @return $this
     */
    public function setPageName($pageName) {
        $this->_pageName = $pageName;
        return $this;
    }

    /**
     * Get page name, defaults to 'page'.
     * @return string String key in $_REQUEST to get page index.
     */
    public function getPageName() {
        return $this->_pageName;
    }

    /**
     * Set items number per page.
     * @param int $itemsPerPage Items number per page.
     * @return $this
     */
    public function setItemsPerPage($itemsPerPage) {
        $this->_itemsPerPage = intval($itemsPerPage);
        return $this;
    }

    /**
     * Get items number per page, defaults to 10.
     * @return int Items number per page.
     */
    public function getItemsPerPage() {
        return $this->_itemsPerPage;
    }

    /**
     * Set total number of items.
     * @param int $itemsTotal Total number of items.
     * @return $this
     */
    public function setItemsTotal($itemsTotal) {
        $this->_itemsTotal = intval($itemsTotal);
        return $this;
    }

    /**
     * Get total number of items, defaults to 0.
     * @return int Total number of items.
     */
    public function getItemsTotal() {
        return $this->_itemsTotal;
    }

    /**
     * Get total number of pages.
     * @return int Total number of pages.
     */
    public function getPagesTotal() {
        return (int) (($this->getItemsTotal() + $this->getItemsPerPage() - 1) / $this->getItemsPerPage());
    }

    /**
     * Set zero-based page offset.
     * @return $this
     */
    public function setPageOffset($pageOffset) {
        $pageName = $this->getPageName();
        $_REQUEST[$pageName] = intval($pageOffset) + 1;
        return $this;
    }

    /**
     * Get zero-based page offset.
     * @return int Zero-based page offset.
     */
    public function getPageOffset() {
        $pageName = $this->getPageName();
        $pagesTotal = $this->getPagesTotal();
        $curPage = isset($_REQUEST[$pageName]) ? intval($_REQUEST[$pageName]) - 1 : 0;
        if ($curPage < 0) {
            $curPage = 0;
        } else if ($curPage > ($pagesTotal - 1)) {
            $curPage = $pagesTotal - 1;
        }
        return $curPage;
    }

    /**
     * Set page number value of $_REQUEST.
     * @return $this
     */
    public function setPageNum($pageNum) {
        $pageName = $this->getPageName();
        $_REQUEST[$pageName] = intval($pageNum);
        return $this;
    }

    /**
     * Get page number value of $_REQUEST.
     * @return int Page number value of $_REQUEST.
     */
    public function getPageNum() {
        return $this->getPageOffset() + 1;
    }

    /**
     * Get zero-based item offset.
     * @return int Zero-based item offset.
     */
    public function getItemOffset() {
        return $this->getPageOffset() * $this->getItemsPerPage();
    }

    /**
     * Get limit array.
     * @return array Limit array.
     */
    public function getLimitArr() {
        return array($this->getItemOffset(), $this->getItemsPerPage());
    }

    /**
     * Get limit criteria.
     * @param mixed $criteria TeaDbCriteria instance or criteria array.
     * @return mixed TeaDbCriteria instance or criteria array.
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

}