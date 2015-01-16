<?php

/**
 * 链接分页类。
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.teaframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.teaframework.com/license/
 * @package lib.pager
 */
class TeaLinkPager extends TeaBaseStyledPager {

    /**
     * 分页内容html。
     * @param string $content 自定义分页内容html，默认为null。
     * @return string 分页内容html。
     */
    public function content($content = null) {
        if (!empty($content)) {
            return $content;
        }
        $content = <<<CONTENT
<div class="{$this->containerCssClass}">
    <span class="{$this->totalCssClass}">共 <span class="{$this->totalNumCssClass}">{$this->getPagesTotal()}</span> 页</span>
    <span class="{$this->firstCssClass}"><a href="{$this->getFirstLink()}">{$this->firstText}</a></span>
    <span class="{$this->prevCssClass}"><a href="{$this->getPrevLink()}">{$this->prevText}</a></span>
    <span class="{$this->nextCssClass}"><a href="{$this->getNextLink()}">{$this->nextText}</a></span>
    <span class="{$this->lastCssClass}"><a href="{$this->getLastLink()}">{$this->lastText}</a></span>
    <span class="{$this->currentCssClass}">第 <span class="{$this->currentNumCssClass}">{$this->getPageNum()}</span> 页</span>
</div>
CONTENT;
        return $content;
    }

}