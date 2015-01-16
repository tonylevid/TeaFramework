<?php

/**
 * 样式分页基础抽象类。
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.teaframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.teaframework.com/license/
 * @package lib.pager
 */
abstract class TeaBaseStyledPager extends TeaBasePager {

    /**
     * 分页容器css类名。
     * @var string
     */
    public $containerCssClass = 'tea-pager';

    /**
     * 总页码容器css类名。
     * @var string
     */
    public $totalCssClass = 'tea-total';

    /**
     * 总页码span css类名。
     * @var string
     */
    public $totalNumCssClass = 'tea-total-num';

    /**
     * 上一页容器css类名。
     * @var string
     */
    public $prevCssClass = 'tea-prev';

    /**
     * 上一页文字。
     * @var string
     */
    public $prevText = '上一页';

    /**
     * 下一页容器css类名。
     * @var string
     */
    public $nextCssClass = 'tea-next';

    /**
     * 下一页文字。
     * @var string
     */
    public $nextText = '下一页';

    /**
     * 首页容器css类名。
     * @var string
     */
    public $firstCssClass = 'tea-first';

    /**
     * 首页文字。
     * @var string
     */
    public $firstText = '首页';

    /**
     * 尾页容器css类名。
     * @var string
     */
    public $lastCssClass = 'tea-last';

    /**
     * 尾页文字。
     * @var string
     */
    public $lastText = '尾页';

    /**
     * 当前页容器css类名。
     * @var string
     */
    public $currentCssClass = 'tea-current';

    /**
     * 当前页码span css类名。
     * @var string
     */
    public $currentNumCssClass = 'tea-current-num';

    /**
     * 获取上一页链接。
     * @return string
     */
    public function getPrevLink() {
        $prevOffset = $this->getPageOffset() - 1;
        return $prevOffset < 0 ? '' : $this->createPageUrl($prevOffset);
    }

    /**
     * 获取下一页链接。
     * @return string
     */
    public function getNextLink() {
        $nextOffset = $this->getPageOffset() + 1;
        return $nextOffset > ($this->getPagesTotal() - 1) ? '' : $this->createPageUrl($nextOffset);
    }

    /**
     * 获取首页链接。
     * @return string
     */
    public function getFirstLink() {
        return $this->createPageUrl(0);
    }

    /**
     * 获取尾页链接。
     * @return string
     */
    public function getLastLink() {
        return $this->createPageUrl($this->getPagesTotal() - 1);
    }

    /**
     * 分页内容html。
     * @param string $content 自定义分页内容html，默认为null。
     * @return string 分页内容html。
     */
    abstract public function content($content = null);
}