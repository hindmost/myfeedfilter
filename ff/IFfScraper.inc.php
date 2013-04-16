<?php
/**
 * @package feedFilter
 * @author  me@savreen.com
 * @license http://opensource.org/licenses/gpl-license GPL
 */

/**
 * Interface of Feed Scraping Tool
 */
interface IFfScraper
{
    /**
     * Process feed title
     * @param string $sTitle - feed title
     */
    function processFeed($sTitle);

    /**
     * Process feed item
     * @param FfItem $oItem - feed item
     */
    function processItem(FfItem $oItem);
}
