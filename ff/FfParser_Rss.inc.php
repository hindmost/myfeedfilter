<?php
/**
 * @package feedFilter
 * @author  me@savreen.com
 * @license http://opensource.org/licenses/gpl-license GPL
 */

/**
 * Feed Parsing Tool - RSS format
 */
class FfParser_Rss extends FfParserShared implements IFfParser
{
    function process($sCont, $nLastItem) {
        $sCont = self::checkXml($sCont);
        if (!$sCont) return false;
        self::prepareXml();
        $o_xml = simplexml_load_string($sCont);
        if (!$o_xml) return false;
        $o_scrap = self::getScraper();
        $o_feed = $o_xml->channel;
        if (!$o_scrap || !$o_feed) return false;
        if ($o_feed->title) $o_scrap->processFeed($o_feed->title);
        $n_d0 = $o_feed->pubDate? self::getTimestamp($o_feed->pubDate) : 0;
        $n = 0;
        foreach ($o_feed->item as $o_item) {
            if (!$o_item->title || !$o_item->description) continue;
            $n_d = $o_item->pubDate? self::getTimestamp($o_item->pubDate) : $n_d0;
            if (!$n_d) return false;
            if ($n_d <= $nLastItem) continue;
            $n++;
            $s_xtras = '';
            if ($o_item->author)
                $s_xtras = '<b>Author</b>: '. (string)$o_item->author;
            if ($o_item->category) {
                $s = '';
                foreach ($o_item->category as $o_cate)
                    $s .= ', '. (string)$o_cate;
                if ($s) $s_xtras .= ($s_xtras? '<br>' : '').
                    '<b>Categories</b>: '. substr($s, 2);
            }
            $o_scrap->processItem(
                new FfItem($n_d,
                    (string)$o_item->title, (string)$o_item->description,
                    $o_item->link? (string)$o_item->link : '', $s_xtras
                )
            );
        }
        return $n;
    }
}
