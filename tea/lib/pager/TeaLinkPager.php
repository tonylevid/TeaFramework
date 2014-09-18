<?php

/**
 * TeaLinkPager class file.
 *
 * @author tonylevid <tonylevid@gmail.com>
 * @link http://www.tframework.com/
 * @copyright http://tonylevid.com/
 * @license http://www.tframework.com/license/
 * @package lib.pager
 */
class TeaLinkPager extends TeaBaseStyledPager {

    /**
     * Pager content.
     * @param string $content User defined pager content, defaults to null.
     * @return string Pager content.
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