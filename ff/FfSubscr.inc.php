<?php
/**
 * @package feedFilter
 * @author  me@savreen.com
 * @license http://opensource.org/licenses/gpl-license GPL
 */

/**
 * feedFilter Subscription
 */
class FfSubscr extends Prof_FfSubscr
{
    const FILE_PROF = 'subscr-%s-profile.dat';
    const FILE_RET = 'subscr-%s-result.dat';
    const FILE_JSON_PROF = 'subscr-%s-profile.json';
    const FILE_JSON_RET = 'subscr-%s-result.json';
    const FILE_JSON_RETSTAMP = 'subscr-%s-result.txt';
    const N_EXP_MAX = 36;
    const LEN_TITLE = 22;
    const MARK_KW_NEG = '~:';
    const MARK_KW_XTRA = 'extra:';

    /**
     * @static int - number of feeds
     */
    static protected $N_FEEDS = 0;

    /**
     * @static int - subscription expiration time (hours)
     */
    static protected $EXP_OBJ = 18;

    /**
     * @static int - feed item expiration time (mins)
     */
    static protected $EXP_ITEMS = 60;

    /**
     * @static int - items buffer max. size
     */
    static protected $N_BUFFMAX = 100;

    /**
     * @static int - items buffer min. size
     */
    static protected $N_BUFFMIN = 10;

    /**
     * @static bool - PCRE error flag
     */
    static protected $B_PCRE_ERR = false;


    /**
     * @var int - subscription number
     */
    protected $nId = false;

    /**
     * @var array - subscription results (items)
     */
    protected $aResult = array();


    /**
     * Constructor
     * @param int $nId - subscription number
     * @param bool $bReset - subscription reset flag
     */
    function __construct($nId = false, $bReset = false) {
        if ($nId === false) return;
        $this->nId = $nId;
        if ($bReset) {
            $this->resetProfile();
        }
        else {
            $this->loadProfile();
            $this->loadResult();
        }
    }

    /**
     * Delete subscription
     */
    function delete() {
        $this->resetProfile();
        $this->saveProfile();
    }

    /**
     * Set shared data
     * @param Prof_FfManager $oProf - manager profile object
     */
    static function setSharedData(Prof_FfManager $oProf) {
        if ($n = count($oProf->aFeedUrls))
            self::$N_FEEDS = $n;
        if (($n = $oProf->nSubscrExp) && $n < self::N_EXP_MAX)
            self::$EXP_OBJ = $n;
        if ($n = $oProf->nSubscrItemsExp)
            self::$EXP_ITEMS = $n;
        if ($n = $oProf->nSubscrItemsBuff)
            self::$N_BUFFMAX = $n;
        if (($n = $oProf->nSubscrItemsNew) && $n < self::$N_BUFFMAX)
            self::$N_BUFFMIN = $n;
    }

    /**
     * Get cache resource references
     * @return array
     */
    static function getCacheRefs() {
        return array(
            self::FILE_JSON_PROF, self::FILE_JSON_RET, self::FILE_JSON_RETSTAMP
        );
    }

    /**
     * Set subscription profile
     * @param string $sTitle - 
     * @param array $aKeywords - 
     * @param array $aFeedIds - 
     * @return bool
     */
    function setProfile($sTitle, $aKeywords = 0, $aFeedIds = 0) {
        if ($this->nId === false) return false;
        $sTitle = FfShared::strip4Json($sTitle);
        if (!$sTitle) return false;
        $this->sTitle = substr($sTitle, 0, self::LEN_TITLE);
        if (is_array($aKeywords) && count($aKeywords)) {
            $a_words = array(); $a_types = array();
            foreach ($aKeywords as $word) {
                $a_ret = $this->checkKeyword($word);
                if (!$a_ret) continue;
                $a_words[] = $a_ret[0];
                $a_types[] = $a_ret[1];
            }
            $this->aKeywords = $a_words;
            $this->aKeywordTypes = $a_types;
        }
        else {
            $this->aKeywords = array();
            $this->aKeywordTypes = array();
        }
        if (is_array($aFeedIds) && count($aFeedIds)) {
            $aFeedIds = array_filter($aFeedIds, array(self,'filterFeedId'));
            if (count($aFeedIds)) $this->aFeedIds = array_values($aFeedIds);
        }
        else
            $this->aFeedIds = 0;
        $this->setLastVisit();
        $this->saveProfile();
        return true;
    }

    /**
     * Check if the subscription's profile is setup
     * @return bool
     */
    function isSetup() {
        return $this->sTitle;
    }

    /**
     * Check if the subscription is expired
     * @return bool
     */
    function isExpired() {
        return $this->nId === false || !$this->isSetup() ||
            FfShared::isExpired($this->nLastVisit, self::$EXP_OBJ* 60* 24);
    }

    /**
     * Apply subscription parameters to the newly scraped results
     * @param FfScraper $oScraper - scraper object
     * @return bool
     */
    function process(FfScraper $oScraper) {
        if ($this->nId === false)
            return false;
        $a_items = $oScraper->getItems();
        $a_itemsrcs = $oScraper->getItemSources();
        if (!(count($a_items) && count($a_items) == count($a_itemsrcs)))
            return false;
        $a_ret = array();
        foreach ($a_items as $i => $o_item) {
            if ($this->aFeedIds && !in_array($a_itemsrcs[$i], $this->aFeedIds)) continue;
            if (!$this->filterItem($o_item)) continue;
            $a_ret[] = $o_item;
        }
        $n_curr = count($a_ret);
        if (!$n_curr)
            return false;
        $n_prev = count($this->aResult);
        $n_max = max(min($n_prev, self::$N_BUFFMAX - $n_curr), 0);
        if ($n_curr > self::$N_BUFFMIN) {
            $n_now = time(); $n_exp = self::$EXP_ITEMS * 60;
            $n = 0;
            foreach ($this->aResult as $obj) {
                if ($n >= $n_max || $n_now - $obj->date > $n_exp) break;
                $n++;
            }
        }
        else
            $n = $n_max;
        if ($n) {
            $this->aResult = array_slice(
                array_merge($a_ret, array_slice($this->aResult, 0, $n)),
                0, self::$N_BUFFMAX
            );
            usort($this->aResult, array(self,'compareItems'));
        }
        else {
            usort($a_ret, array(self,'compareItems'));
            $this->aResult = $a_ret;
        }
        $this->saveResult();
        $this->setLastVisit();
        $this->saveProfile();
        return true;
    }

    /**
     * Filter profile's feed id
     * @param mixed $id - feed id
     * @return bool
     */
    static protected function filterFeedId($id) {
        return ($id = intval($id)) >= 0 && $id < self::$N_FEEDS;
    }

    /**
     * Filter profile's keyword
     * @param string $word - keyword
     * @return bool
     */
    protected function checkKeyword($word) {
        static $SAMPLE = 'Lorem ipsum';
        $word = (string)$word;
        $i_neg = $i_xtra = 0;
        if (strpos($word, self::MARK_KW_NEG) === 0) {
            $word = substr($word, strlen(self::MARK_KW_NEG)); $i_neg = 1;
        }
        if (strpos($word, self::MARK_KW_XTRA) === 0) {
            $word = substr($word, strlen(self::MARK_KW_XTRA)); $i_xtra = 1;
        }
        $i_type = $i_neg | $i_xtra << 1;
        $b_rx = self::isRegex($word);
        $word = preg_replace('/[\'"'. ($b_rx? '' : '\\\\?*{}=|\^'). ']+/u', '',
            $word
        );
        set_error_handler(array(self, 'handlePcreErr'));
        self::testKeyword($word, $SAMPLE);
        restore_error_handler();
        return self::$B_PCRE_ERR? false : array(FfShared::strip4Json($word), $i_type);
    }

    /**
     * Filter profile's keyword
     * @param string $word - keyword
     * @return bool
     */
    protected function detectKeywordType($word) {
        if (strpos($word, '!:')) {return $word;}
    }

    /**
     * Custom error handler
     * @param int $iErr - 
     * @param string $sErr - 
     * @return bool
     */
    static protected function handlePcreErr($iErr, $sErr) {
        self::$B_PCRE_ERR = true;
        return true;
    }

    /**
     * Test profile's keyword
     * @param string $word - keyword
     * @param string $text - text
     * @return bool
     */
    static protected function testKeyword($word, $text) {
        self::$B_PCRE_ERR = false;
        $rx = self::isRegex($word)? $word :
            ('/(?:[\s.,;\-\/("\'&]|^)'. preg_quote($word, '/'). '/i');
        $b_ok = preg_match($rx. 'u', $text);
        return !self::$B_PCRE_ERR && $b_ok;
    }

    /**
     * Check if an input string is regex
     * @param string $s - 
     * @return bool
     */
    static protected function isRegex($s) {
        return $s{0} == '/' && substr($s, -1) == '/';
    }

    /**
     * Filter feed item by searching keywords in its content
     * @param FfItem $obj - feed item object
     * @return bool
     */
    protected function filterItem(FfItem $obj) {
        if (!count($this->aKeywords)) return true;
        if (!($obj && $obj->content)) return false;
        $mask_neg = 1; $mask_xtra = 1 << 1;
        $cont = $obj->content; $xtra = $obj->xtras;
        $b_pos = $b_neg = false;
        set_error_handler(array(self, 'handlePcreErr'));
        foreach ($this->aKeywords as $i => $word) {
            $i_type = $this->aKeywordTypes[$i];
            $i_neg = $i_type & $mask_neg; $i_xtra = $i_type & $mask_xtra;
            if (!self::testKeyword($word, $i_xtra? $xtra : $cont)) continue;
            $i_neg? ($b_neg = true) : ($b_pos = true);
        }
        restore_error_handler();
        return $b_pos && !$b_neg;
    }

    /**
     * Compare two feed items by timestamp
     * @param FfItem $obj1 - feed item
     * @param FfItem $obj2 - feed item
     * @return int
     */
    static protected function compareItems(FfItem $obj1, FfItem $obj2) {
        return ($obj1->date < $obj2->date)? 1 : -1;
    }

    protected function saveProfile() {
        $obj = new Prof_FfSubscr();
        $obj->copyProfile($this);
        FfShared::saveVar($this->getFilename(self::FILE_PROF), $obj);
        $obj->sPassword = '';
        FfShared::saveJson($this->getFilename(self::FILE_JSON_PROF),
            $obj->toJson()
        );
    }

    protected function loadProfile() {
        $obj = FfShared::loadVar($this->getFilename(self::FILE_PROF));
        return $this->copyProfile($obj);
    }

    protected function setLastVisit() {
        $this->nLastVisit = time();
    }

    protected function getResultJson() {
        $out = '';
        foreach ($this->aResult as $o_item) {
            $out .= ','. $o_item->toJson();
        }
        return '['. ($out? substr($out, 1) : ''). ']';
    }

    protected function saveResult() {
        if (!count($this->aResult)) return;
        FfShared::saveVar($this->getFilename(self::FILE_RET), $this->aResult);
        $out = '';
        foreach ($this->aResult as $o_item) {
            $out .= ','. $o_item->toJson();
        }
        FfShared::saveJson($this->getFilename(self::FILE_JSON_RET),
            $this->getResultJson()
        );
        FfShared::saveJson($this->getFilename(self::FILE_JSON_RETSTAMP),
            ''
        );
    }

    protected function loadResult() {
        $arr = FfShared::loadVar($this->getFilename(self::FILE_RET));
        if (!$arr) return false;
        $this->aResult = $arr;
        return true;
    }

    /**
     * Get filename
     * @return string
     */
    protected function getFilename($sFile) {
        return sprintf($sFile, $this->nId);
    }
}
