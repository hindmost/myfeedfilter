<?php
/**
 * @package feedFilter
 * @author  me@savreen.com
 * @license http://opensource.org/licenses/gpl-license GPL
 */

function __autoload($sClassName) {
    require_once dirname(__FILE__). '/'. $sClassName. '.inc.php';
}


/**
 * feedFilter Manager Base
 */
class FfManagerBase extends Prof_FfManager
{
    const FILE_PROF = 'manager-profile.dat';
    const FILE_PROF_JSON = 'manager-profile.json';
    const FILE_ACCTS = 'manager-accounts.dat';
    const FILE_ACCTS_JSON = 'manager-accounts.json';
    const FILE_SUBSCRS = 'manager-subscrs.dat';
    const FILE_SUBSCRS_JSON = 'manager-subscrs.json';

    static protected $DIR_JSON = '';

    protected $aAccts = array();
    protected $aSubscrs = array();


    /**
     * Constructor
     */
    function __construct() {
        $this->loadProfile();
        FfAccount::setSharedData($this);
        FfSubscr::setSharedData($this);
        if ($arr = FfShared::loadVarArr(self::FILE_ACCTS))
            $this->aAccts = $arr;
        if ($arr = FfShared::loadVarArr(self::FILE_SUBSCRS))
            $this->aSubscrs = $arr;
    }

    /**
     * Set shared directories
     * @param string $sDirData - 
     * @param string $sDirJson - 
     * @param string $sUrlJson - 
     */
    static function setDirs($sDirData, $sDirJson, $sUrlJson) {
        FfShared::setDataDir($sDirData);
        FfShared::setJsonDir($sDirJson);
        if ($s = trim($sUrlJson, '/')) self::$DIR_JSON = $s. '/';
    }

    /**
     * Get cache resource references (admin case)
     * @return string
     */
    static function getCacheRefsAdmin() {
        $arr = array(
            self::FILE_PROF_JSON, self::FILE_ACCTS_JSON, self::FILE_SUBSCRS_JSON
        );
        foreach ($arr as &$url) $url = self::$DIR_JSON. $url;
        return '["'. implode('","', $arr). '"]';
    }

    /**
     * Get cache resource references (usual case)
     * @return string
     */
    static function getCacheRefs() {
        $arr = array_merge(
            array(self::FILE_PROF_JSON, FfAccount::getCacheRef()),
            FfSubscr::getCacheRefs()
        );
        foreach ($arr as &$url) $url = self::$DIR_JSON. $url;
        return '["'. implode('","', $arr). '"]';
    }

    /**
     * Save cache references to file
     * @param string $sFile - 
     * @return bool
     */
    static function saveCacheRefs($sFile) {
        if (!$sFile) return false;
        FfShared::saveJson($sFile, self::getCacheRefs());
        return true;
    }

    /**
     * Get names of manager profile properties
     * @return array
     */
    static function getProfileProps() {
        return array(self::$A_PROPS, self::$N_ARRPROPS);
    }

    /**
     * Set manager profile
     * @param array $sFeeds - 
     * @param int $nFeedExpMin - 
     * ... (plus up to 13 params expected)
     * @return bool
     */
    function setManagerProfile() {
        $n_args = func_num_args();
        if ($n_args < 1) return false;
        $a_args = func_get_args();
        $sFeeds = $a_args[0];
        if (!($sFeeds = trim($sFeeds)))
            return false;
        $a_urls = array(); $a_quots = array(); $a_zones = array();
        foreach (FfShared::strToArr($sFeeds, true) as $s) {
            $arr = FfShared::strToArr($s);
            $a_urls[] = $arr[0];
            $a_zones[] = (count($arr) > 1)? $arr[1] : 0;
            $a_quots[] = (count($arr) > 2)? $arr[2] : 0;
        }
        array_splice($a_args, 0, 1, array($a_urls, $a_zones, $a_quots));
        $b_ok = call_user_func_array(array($this, 'setProfile'), $a_args);
        if (!$b_ok) return false;
        $this->saveProfile();
        $o_scraper = new FfScraper($this, 0, true);
        return true;
    }

    /**
     * Reset scraper's run stats
     * @return bool
     */
    function resetScraperRunStats() {
        $o_scraper = new FfScraper($this, 0);
        return $o_scraper->resetRunStats();
    }

    /**
     * Run feed scraping
     * @return bool
     */
    function runScraper() {
        if (!count($this->aFeedUrls))
            return false;
        $o_scraper = new FfScraper($this,
            array(new FfParser_Rss(), new FfParser_Atom())
        );
        $a_titles = $o_scraper->run();
        if (!is_array($a_titles))
            return false;
        $n = count($this->aFeedTitles); $b_upd = false;
        foreach ($a_titles as $i => $s) {
            if ($s && ($i >= $n || !$this->aFeedTitles[$i])) {
                $this->aFeedTitles[$i] = $s; $b_upd = true;
            }
        }
        if ($b_upd) $this->saveProfile();
        $this->processSubscr('', $o_scraper);
        foreach ($this->aSubscrs as $i => $i_acct) {
            $this->processSubscr($i, $o_scraper);
        }
        return true;
    }

    /**
     * Delete all accounts
     * @return bool
     */
    function resetAccounts() {
        if (!count($this->aAccts)) return false;
        foreach ($this->aAccts as $i => $s_id) {
            $obj = new FfAccount($i);
            $obj->delete();
        }
        $this->aAccts = array();
        $this->saveAccts();
        $this->resetSubscrs(true);
        return true;
    }

    /**
     * Delete all subscriptions
     * @param bool $bUntouchAccts - 
     * @return bool
     */
    function resetSubscrs($bUntouchAccts = false) {
        if (!count($this->aSubscrs)) return false;
        foreach ($this->aSubscrs as $i => $i_acct) {
            $obj = new FfSubscr($i, true);
            $obj->delete();
        }
        $this->aSubscrs = array();
        $this->saveSubscrs();
        if ($bUntouchAccts) return true;
        foreach ($this->aAccts as $i => $s_id) {
            $obj = new FfAccount($i);
            $obj->resetSubscrs();
        }
        return true;
    }


    /**
     * Save manager profile
     */
    protected function saveProfile() {
        $obj = new Prof_FfManager();
        $obj->copyProfile($this);
        FfShared::saveVar(self::FILE_PROF, $obj);
        FfShared::saveJson(self::FILE_PROF_JSON, $obj->toJson());
    }

    /**
     * Load manager profile
     */
    protected function loadProfile() {
        $obj = FfShared::loadVar(self::FILE_PROF);
        return $this->copyProfile($obj);
    }

    /**
     * Save account ids
     */
    protected function saveAccts() {
        FfShared::saveVar(self::FILE_ACCTS, $this->aAccts);
        FfShared::saveJson(self::FILE_ACCTS_JSON,
            count($this->aAccts)? ('["'. implode('","', $this->aAccts). '"]') : '[]'
        );
    }

    /**
     * Save subscription ids
     */
    protected function saveSubscrs() {
        FfShared::saveVar(self::FILE_SUBSCRS, $this->aSubscrs);
        FfShared::saveJson(self::FILE_SUBSCRS_JSON,
            count($this->aSubscrs)? ('['. implode(',', $this->aSubscrs). ']') : '[]'
        );
    }

    /**
     * Apply subscription to the newly scraped results
     * @param int $i - subscription number
     * @param FfScraper $oScraper - scraper object
     */
    protected function processSubscr($i, FfScraper $oScraper) {
        $obj = new FfSubscr($i);
        $obj->process($oScraper);
    }
}


/**
 * feedFilter Manager
 */
class FfManager extends FfManagerBase
{
    const N_ACCTS = 15;
    const N_SUBSCRS = 20;
    const LEN_ID = 10;
    const LEN_PWD = 10;


    protected $sAcctId = '';
    protected $sAcctPwd = '';
    protected $iAcct = false;

    /**
     * Constructor
     * @param string $sAcctId - object's property value
     * @param string $sAcctPwd - object's property value
     */
    function __construct($sAcctId = '', $sAcctPwd = '') {
        parent::__construct();
        if (!($this->nAccountsMax && $this->nAccountsMax < self::N_ACCTS))
            $this->nAccountsMax = self::N_ACCTS;
        if (!($this->nSubscrsMax && $this->nSubscrsMax < self::N_SUBSCRS))
            $this->nSubscrsMax = self::N_SUBSCRS;
        if (!($sAcctId && $sAcctPwd)) return;
        $this->sAcctId = $sAcctId;
        $this->sAcctPwd = $sAcctPwd;
        $i = array_search($this->sAcctId, $this->aAccts);
        if ($i === false) return;
        $this->iAcct = $i;
    }


    /**
     * Authenticate account
     * @return string|false - account id / false
     */
    function authAccount() {
        if ($this->iAcct === false) return false;
        $obj = new FfAccount($this->iAcct);
        return $this->validateAcct($obj)? $this->getAuthOk() : false;
    }

    /**
     * Create a new account
     * @return string|int|false - account id / error code / false
     */
    function createAccount() {
        $sId = $this->sAcctId; $sPwd = $this->sAcctPwd;
        $b_ok = $sId && $sPwd &&
            strlen($sId) <= self::LEN_ID && strlen($sPwd) <= self::LEN_PWD &&
            ctype_alnum($sId) && ctype_alnum($sPwd);
        if (!$b_ok)
            return $this->getAuthError(1);
        if ($this->iAcct !== false)
            return $this->getAuthError(2);
        $i_avail = count($this->aAccts);
        foreach ($this->aAccts as $i => $s_id) {
            $obj = new FfAccount($i);
            if (!$obj->isExpired()) continue;
            $i_avail = $i; break;
        }
        if ($i_avail > $this->nAccountsMax)
            return $this->getAuthError(3);
        $obj = new FfAccount($i_avail, true);
        if (!$obj->setPassword($sPwd))
            return false;
        $this->aAccts[$i_avail] = $sId;
        $this->iAcct = $i_avail;
        $this->saveAccts();
        return $this->getAuthOk();
    }

    /**
     * Create a new subscription
     * @return bool|int - response / error code
     */
    function createSubscr() {
        if ($this->iAcct === false)
            return false;
        $o_to = new FfAccount($this->iAcct);
        if (!$this->validateAcct($o_to))
            return false;
        $i_sub = count($this->aSubscrs); $i_from = false;
        foreach ($this->aSubscrs as $i => $n_id) {
            $obj = new FfSubscr($i);
            if (!$obj->isExpired()) continue;
            $i_sub = $i; $i_from = $n_id; break;
        }
        if ($i_sub > $this->nSubscrsMax)
            return 2;
        if (!$o_to->attachSubscr($i_sub)
            )
            return 3;
        if ($i_from !== false && $i_from !== $this->iAcct) {
            $o_from = new FfAccount($i_from);
            $o_from->detachSubscr($i_sub);
        }
        $this->aSubscrs[$i_sub] = $this->iAcct;
        $this->saveSubscrs();
        $obj = new FfSubscr($i_sub, true);
        return true;
    }

    /**
     * Set subscription profile data
     * @param int $nId - subscription number
     * @param string $sTitle - subscription title
     * @param string $sKeywords - 
     * @param string $sFeeds - 
     * @return bool - response
     */
    function setSubscrProfile($nId, $sTitle, $sKeywords = '', $sFeeds = '') {
        if ($this->iAcct === false)
            return false;
        $obj = new FfAccount($this->iAcct);
        if (!$this->validateAcct($obj))
            return false;
        if (!$this->validateSubscr($nId))
            return false;
        if (!$sTitle || !($sKeywords || $sFeeds !== ''))
            return false;
        $a_k = ($s = trim($sKeywords))? FfShared::strToArr($s, true) : 0;
        $a_f = ($s = trim($sFeeds)) !== ''? FfShared::strToArr($s) : 0;
        $o_sub = new FfSubscr($nId);
        $b_ok = $o_sub->setProfile($sTitle, $a_k, $a_f);
        if ($b_ok) $obj->renameSubscr($nId, $o_sub->getTitle());
        return $b_ok;
    }

    /**
     * Check the last processed account for validity
     * @return bool
     */
    function checkLastAccount() {
        return $this->iAcct !== false;
    }


    /**
     * Validate account password
     * @param FfAccount $obj - account
     * @return bool
     */
    protected function validateAcct(FfAccount $obj) {
        if ($obj->checkPassword($this->sAcctPwd)) return true;
        $this->iAcct = false;
        return false;
    }

    /**
     * Validate subscription
     * @param int $nId - subscription number
     * @return bool
     */
    protected function validateSubscr($nId) {
        return ($nId = intval($nId)) >= 0 && $nId < count($this->aSubscrs) &&
            $this->aSubscrs[$nId] == $this->iAcct;
    }

    /**
     * Get auth response
     * @return string - response JSON
     */
    protected function getAuthOk() {
        return '{"i":"'. $this->iAcct. '","id":"'. $this->sAcctId. '"}';
    }

    /**
     * Get auth error response
     * @param int $sId - account id
     * @return string - response JSON
     */
    protected function getAuthError($i) {
        return '{"error":'. $i. '}';
    }

}
