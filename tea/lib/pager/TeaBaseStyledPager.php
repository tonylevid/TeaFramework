<?php

/**
 * TeaBaseStyledPager class file.
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package lib.pager
 */
abstract class TeaBaseStyledPager extends TeaBasePager {

    /**
     * Pager container css class.
     * @var string
     */
    public $containerCssClass = 'tea-pager';

    /**
     * Pager total css class.
     * @var string
     */
    public $totalCssClass = 'tea-total';

    /**
     * Pager total number css class.
     * @var string
     */
    public $totalNumCssClass = 'tea-total-num';

    /**
     * Pager previous css class.
     * @var string
     */
    public $prevCssClass = 'tea-prev';

    /**
     * Pager previous text.
     * @var string
     */
    public $prevText = '上一页';

    /**
     * Pager next css class.
     * @var string
     */
    public $nextCssClass = 'tea-next';

    /**
     * Pager next text.
     * @var string
     */
    public $nextText = '下一页';

    /**
     * Pager first css class.
     * @var string
     */
    public $firstCssClass = 'tea-first';

    /**
     * Pager first text.
     * @var string
     */
    public $firstText = '首页';

    /**
     * Pager last css class.
     * @var string
     */
    public $lastCssClass = 'tea-last';

    /**
     * Pager last text.
     * @var string
     */
    public $lastText = '尾页';

    /**
     * Pager current css class.
     * @var string
     */
    public $currentCssClass = 'tea-current';

    /**
     * Pager current number css class.
     * @var string
     */
    public $currentNumCssClass = 'tea-current-num';

    /**
     * Get pager previous link address.
     * @return string
     */
    public function getPrevLink() {
        $prevOffset = $this->getPageOffset() - 1;
        return $prevOffset < 0 ? '' : $this->createPageUrl($prevOffset);
    }

    /**
     * Get pager next link address.
     * @return string
     */
    public function getNextLink() {
        $nextOffset = $this->getPageOffset() + 1;
        return $nextOffset > ($this->getPagesTotal() - 1) ? '' : $this->createPageUrl($nextOffset);
    }

    /**
     * Get pager first link address.
     * @return string
     */
    public function getFirstLink() {
        return $this->createPageUrl(0);
    }

    /**
     * Get pager last link address.
     * @return string
     */
    public function getLastLink() {
        return $this->createPageUrl($this->getPagesTotal() - 1);
    }

    /**
     * Pager content.
     * @param string $content User defined pager content, defaults to null.
     * @return string Pager content.
     */
    abstract public function content($content = null);

    /**
     * Pager ajax content.
     * @param string $content User defined pager content, defaults to null.
     * @return string Pager ajax content.
     */
    abstract public function ajaxContent($content = null);
}