<?php
/**
 * @package feedFilter
 * @author  me@savreen.com
 * @license http://opensource.org/licenses/gpl-license GPL
 */

/**
 * Feed Parsing Tool - Shared methods
 */
class FfParserShared
{
    const CHSET = 'UTF-8';
    const CHSET_X = 'encoding="UTF-8"';
    const RX_XMLTAG = '/^<\?xml\s(?:[^>]+\s|)(encoding=["\']([^"\'>]+)["\'])[^>]*>/i';

    /**
     * @static IFfScraper - shared scraper object
     */
    static protected $O_SCRAPER = 0;

    /**
     * Set shared scraper
     * @param IFfScraper $oScraper - scraper object
     */
    static function setScraper(IFfScraper $oScraper) {
        self::$O_SCRAPER = $oScraper;
    }

    /**
     * Get shared scraper
     * @return IFfScraper
     */
    static function getScraper() {
        return (self::$O_SCRAPER instanceof IFfScraper)? self::$O_SCRAPER : 0;
    }

    /**
     * Get timestamp of date
     * @param string $sDate - date
     * @return int
     */
    static function getTimestamp($sDate) {
        $n = strtotime($sDate);
        return $n;
    }

    /**
     * Prepare feed XML parsing
     */
    static function prepareXml() {
        static $n_cnt = 0;
        if ($n_cnt++ || !function_exists('libxml_use_internal_errors')) return;
        libxml_use_internal_errors(true);
    }

    /**
     * Check XML for validity and fix its charset (if needed)
     * @param string $sText - XML content
     * @return string|false
     */
    static protected function checkXml($sText) {
        if (!$sText)
            return false;
        if (!($a_ret = self::parseXmlTag($sText)))
            return false;
        list($s_chs, $s_chsx) = $a_ret;
        if (strtoupper($s_chs) == self::CHSET)
            return $sText;
        $s_ret = self::convertCharset($sText, $s_chs);
        if (!$s_ret)
            return $sText;
        $i = strpos($s_ret, $s_chsx);
        return
            substr($s_ret, 0, $i). self::CHSET_X.
            substr($s_ret, $i+ strlen($s_chsx));
    }

    /**
     * Parse primary tag of XML
     * @param string $sText - XML content
     * @return array|false
     */
    static protected function parseXmlTag($sText) {
        return preg_match(self::RX_XMLTAG, $sText, $a_m)?
            array($a_m[2], $a_m[1]) : false;
    }

    /**
     * Convert charset of a text to standard
     * @param string $sText - input text
     * @return string|false
     */
    static function convertCharset($sText, $sCharset) {
	if (!defined('ICONV_VERSION'))
            return false;
        if (!iconv_set_encoding('internal_encoding', $sCharset))
            return false;
        iconv_set_encoding('output_encoding', self::CHSET);
        $ret = iconv($sCharset, self::CHSET. '//IGNORE', $sText);
        return $ret !== false? $ret : false;
    }
}
