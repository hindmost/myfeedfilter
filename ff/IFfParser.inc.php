<?php
/**
 * @package feedFilter
 * @author  me@savreen.com
 * @license http://opensource.org/licenses/gpl-license GPL
 */

/**
 * Interface of Feed Parsing Tool
 */
interface IFfParser
{
    /**
     * Process feed content
     * @param string $sCont - feed content
     * @param int $nLastItem - timestamp of last scraped item
     * @return int
     */
    function process($sCont, $nLastItem);
}
