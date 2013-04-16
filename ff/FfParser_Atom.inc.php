<?php
/**
 * @package feedFilter
 * @author  me@savreen.com
 * @license http://opensource.org/licenses/gpl-license GPL
 */

/**
 * Feed Parsing Tool - Atom format
 */
class FfParser_Atom extends FfParserShared implements IFfParser
{
    function process($sCont, $nLastItem) {
        $sCont = self::checkXml($sCont);
        if (!$sCont) return false;
        self::prepareXml();
        $o_xml = simplexml_load_string($sCont);
        if (!$o_xml) return false;
        $o_scrap = self::getScraper();
        $o_feed = $o_xml->feed;
        if (!$o_scrap || !$o_feed) return false;
        if ($o_feed->title) $o_scrap->processFeed($o_feed->title);
        $n_d0 = $o_feed->updated? self::getTimestamp($o_feed->updated) : 0;
        $n = 0;
        foreach ($o_feed->entry as $o_item) {
            if (!$o_item->title || !$o_item->content) continue;
            $n_d = $o_item->updated? self::getTimestamp($o_item->updated) : $n_d0;
            if (!$n_d) return false;
            if ($n_d <= $nLastItem) continue;
            $n++;
            $s_link = '';
            if ($o_item->link) {
                foreach ($o_item->link as $o_link)
                    if ($o_link['rel'] == 'alternate') {
                        $s_link = (string)$o_link; break;
                    }
            }
            $s_xtras = '';
            if ($o_item->author) {
                $s = '';
                foreach ($o_item->author as $o_elem)
                    $s .= ', '. (string)$o_elem;
                if ($s) $s_xtras .= '<b>Author</b>: '. substr($s, 2);
            }
            if ($o_item->category) {
                $s = '';
                foreach ($o_item->category as $o_cate)
                    $s .= ', '. (string)$o_cate;
                if ($s) $s_xtras .= ($s_xtras? '<br>' : '').
                    '<b>Categories</b>: '. substr($s, 2);
            }
            $o_scrap->processItem(
                new FfItem($n_d,
                    (string)$o_item->title, (string)$o_item->content,
                    $s_link, $s_xtras
                )
            );
        }
        return $n;
    }
}
