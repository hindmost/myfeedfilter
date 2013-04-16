<?php
/**
 * @package feedFilter
 * @author  me@savreen.com
 * @license http://opensource.org/licenses/gpl-license GPL
 */

/**
 * Feed Scraping (Aggregation) Tool
 */
class FfScraper extends Prof_FfScraper implements IFfScraper
{
    const FILE_PROF = 'scraper-profile.dat';
    const FILE_CSV = 'expirestrend-feed%d.csv';
    const N_QUOTA = 5;
    const N_EXP_MIN = 60;
    const N_EXP_MINMAX = 240;
    const N_EXP_ADDPC = 200;
    const F_EXP_DEFT_RATIO = 3;
    const B_CURLHEADERS = true;

    static protected $A_CURL = array(
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_AUTOREFERER => true,
        CURLOPT_MAXREDIRS => 2,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0',
        CURLOPT_HTTPHEADER => array('DNT: 1', 'Connection: keep-alive'),
        CURLOPT_COOKIEFILE => 'cookies.txt',
    );

    /**
     * @var array - parser objects
     */
    protected $aoParsers = array();

    protected $aUrls = array();
    protected $aQuotas = array();
    protected $aTzones = array();
    protected $nExpMin = 0;
    protected $nExpDeft = 0;
    protected $nExpAddNite = 0;
    protected $nExpAddWeek = 0;
    protected $nNiteStart = 0;
    protected $nNiteEnd = 0;

    /**
     * @var int - current feed number
     */
    protected $iFeed = 0;

    /**
     * @var array - feed titles
     */
    protected $aTitles = array();

    /**
     * @var array - scraped feed items 
     */
    protected $aoItems = array();

    /**
     * @var array - sources of scraped feed items
     */
    protected $aItemSrcs = array();

    /**
     * @var int - timestamp of last scraped feed item
     */
    protected $nLastItem = 0;


    /**
     * Constructor
     * @param Prof_FfManager $oProf - manager profile object
     * @param array $aoParsers - set of parser objects
     * @param bool $bReset - scraper reset flag
     * @param array $aCurl - CurlWrap config method's 1st parameter value
     * @param array $bCurlHeaders - CurlWrap config method's 3rd parameter value
     */
    function __construct(Prof_FfManager $oProf, $aoParsers, $bReset = false,
            $aCurl = 0, $bCurlHeaders = 0) {
        $this->loadProfile();
        if ($bReset) {
            if (count($oProf->aFeedUrls) != count($this->aSuitParsers)) {
                $this->resetProfile(); $this->saveProfile();
            }
            return;
        }
        $this->aUrls = $oProf->aFeedUrls;
        $nFeeds = count($this->aUrls);
        $this->aQuotas = (count($oProf->aFeedQuotas) == $nFeeds)?
            $oProf->aFeedQuotas : array_fill(0, $nFeeds, 0);
        $this->aTzones = (count($oProf->aFeedTimezones) == $nFeeds)?
            $oProf->aFeedTimezones : array_fill(0, $nFeeds, 0);
        $this->nExpMin =
            ($v = $oProf->nFeedExpMin) > self::N_EXP_MIN && $v <= self::N_EXP_MINMAX?
            $v : self::N_EXP_MIN;
        $this->nExpDeft =
            ($v = $oProf->nFeedExpDeft) >= self::N_EXP_MIN?
            $v : intval($this->nExpMin* self::F_EXP_DEFT_RATIO);
        $this->nExpAddWeek =
            ($v = $oProf->nFeedExpAddWeek) && $v <= self::N_EXP_ADDPC? $v : 0;
        $this->nExpAddNite =
            ($v = $oProf->nFeedExpAddNite) && $v <= self::N_EXP_ADDPC? $v : 0;
        $this->nNiteStart = $oProf->nFeedNiteStart;
        $this->nNiteEnd = $oProf->nFeedNiteEnd;
        if (!$this->nRuns && $nFeeds)
            $this->initProfileArrProps($nFeeds);
        if (!is_array($aoParsers) || !count($aoParsers))
            return;
        foreach ($aoParsers as $obj)
            $this->addParser($obj);
        $a_curl = is_array($aCurl)?
            array_merge(self::$A_CURL, $aCurl) : self::$A_CURL;
        $a_curl[CURLOPT_COOKIEJAR] = $a_curl[CURLOPT_COOKIEFILE] =
            FfShared::getDataDir(). basename($a_curl[CURLOPT_COOKIEFILE]);
        $b_hdrs = is_bool($bCurlHeaders)? $bCurlHeaders : self::B_CURLHEADERS;
        CurlWrap::config($a_curl, true, $b_hdrs);
    }

    function __destruct() {
        $this->deleteParsers();
    }

    function processFeed($sTitle) {
        $sTitle = FfShared::strip4Json($sTitle);
        if ($sTitle) $this->aTitles[$this->iFeed] = $sTitle;
    }

    function processItem(FfItem $oItem) {
        if (!$oItem->isValid()) return;
        $this->aoItems[] = $oItem;
        $this->aItemSrcs[] = $this->iFeed;
        $n_d = $oItem->date;
        if ($n_d > $this->nLastItem)
            $this->nLastItem = $n_d;
    }

    /**
     * Reset run stats property
     * @return bool
     */
    function resetRunStats() {
        if (!count($this->aUrls)) return false;
        $this->initRunStats(count($this->aUrls));
        return true;
    }

    /**
     * Run feeds scraping (aggregation) process
     * @return bool
     */
    function run() {
        $nParsers = count($this->aoParsers);
        if (!(count($this->aUrls) && $nParsers))
            return false;
        if (!FfShared::isExpired($this->nLastRun, $this->nExpMin))
            return false;
        $this->aoItems = array(); $this->aItemSrcs = array();
        foreach ($this->aUrls as $i => $url) {
            $this->iFeed = $i;
            $this->nLastItem = $n_last = $this->aLastItems[$i];
            $n_exp = $this->aExpires[$i];
            if ($n_exp && !FfShared::isExpired($this->aLastRuns[$i], $n_exp))
                continue;
            $cont = CurlWrap::get($url);
            if (!$cont)
                continue;
            $n = false;
            if (($j0 = $this->aSuitParsers[$i]) !== false && $j0 < $nParsers)
                $n = $this->aoParsers[$j0]->process($cont, $n_last);
            if ($n === false)
                foreach ($this->aoParsers as $j => $obj) {
                    if ($j === $j0 || !($n = $obj->process($cont, $n_last)))
                        continue;
                    $this->aSuitParsers[$i] = $j; break;
                }
            if ($n === false)
                continue;
            $this->aLastItems[$i] = $this->nLastItem;
            $n_last = $this->aLastRuns[$i];
            $n_now = time();
            $this->aLastRuns[$i] = $n_now;
            $this->aExpires[$i] = $this->calcExpire($i, $n, $n_now, $n_last);
        }
        $this->nRuns++;
        $this->nLastRun = time();
        $this->saveProfile();
        return $this->aTitles;
    }

    /**
     * Get result item set
     * @return array
     */
    function getItems() {
        return $this->aoItems;
    }

    /**
     * Get result item sources
     * @return array
     */
    function getItemSources() {
        return $this->aItemSrcs;
    }

    /**
     * Add parser
     * @param IFfParser $obj - parser object
     */
    protected function addParser(IFfParser $obj) {
        $this->aoParsers[] = $obj;
        FfParserShared::setScraper($this);
    }

    /**
     * Delete all parsers
     */
    protected function deleteParsers() {
        for ($i = count($this->aoParsers)- 1; $i >= 0; $i--)
            unset($this->aoParsers[$i]);
    }

    /**
     * Save scraper profile
     */
    protected function saveProfile() {
        $obj = new Prof_FfScraper();
        $obj->copyProfile($this);
        FfShared::saveVar(self::FILE_PROF, $obj);
    }

    /**
     * Load scraper profile
     */
    protected function loadProfile() {
        $obj = FfShared::loadVar(self::FILE_PROF);
        return $this->copyProfile($obj);
    }

    /**
     * Calculate feed expiration time
     * @param int $iFeed - current feed no.
     * @param int $nItems - current feed items count
     * @param int $nNow - timestamp of current moment
     * @param int $nLast - timestamp of last feed run
     * @return bool
     */
    protected function calcExpire($iFeed, $nItems, $nNow, $nLast) {
        $ra_stats = &$this->aRunStats[$iFeed];
        $nRuns = $ra_stats[0]++;
        if (!$nRuns)
            return $this->nExpDeft;
        $nLen = $nNow - $nLast;
        $nQuota = ($v = $this->aQuotas[$iFeed])? $v : self::N_QUOTA;
        $nTzone = ($v = $this->aTzones[$iFeed]) <= 12 && $v >= 12? $v : 0;
        $ra_stats[1] += $nItems;
        $ra_stats[2] += $nLen;
        $ra_stats[3] += $nItems* $nLen;
        $ra_stats[4] += $nItems* $nItems;
        $fAvgX = $ra_stats[1]/ $nRuns;
        $fAvgY = $ra_stats[2]/ $nRuns;
        $fAvgXy = $ra_stats[3]/ $nRuns;
        $fAvgXx = $ra_stats[4]/ $nRuns;
        $fRegrB = $nRuns > 1 && ($fQ = $fAvgXx - $fAvgX* $fAvgX)?
            ($fAvgXy - $fAvgX* $fAvgY)/ $fQ : 0;
        $fRegrE = $fAvgY - $fRegrB* $fAvgX;
        $fExp = abs($fRegrB* $nQuota + $fRegrE);
        $n_now = $nNow+ $nTzone* 60* 60;
        $n_hs = gmdate('G', $n_now);
        $b_nite = $this->nExpAddNite &&
            $n_hs >= $this->nNiteStart && $n_hs <= $this->nNiteEnd;
        $b_week = $this->nExpAddWeek &&
            ($n_w = gmdate('w', $n_now)) == 6 && $n_hs > $this->nNiteEnd ||
            $n_w == 1 && $n_hs < $this->nNiteStart;
        if ($fExp < $this->nExpMin) $fExp = $this->nExpDeft;
        if ($b_nite)
            $fExp *= (1 + $this->nExpAddNite/100);
        if ($b_week)
            $fExp *= (1 + $this->nExpAddWeek/100);
        return intval(round($fExp));
    }
}
