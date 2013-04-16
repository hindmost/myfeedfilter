<?php
/**
 * @package feedFilter
 * @author  me@savreen.com
 * @license http://opensource.org/licenses/gpl-license GPL
 */

/**
 * feedFilter Account
 */
class FfAccount extends Prof_FfAccount
{
    const FILE_PROF = 'account-%s-profile.dat';
    const FILE_JSON_PROF = 'account-%s-profile.json';
    const N_SUBSCRS_MAX = 10;
    const N_EXP_MAX = 52;


    /**
     * @static int - account expiration time (hours)
     */
    static protected $EXP_OBJ = 36;

    /**
     * @static int - max. number of subscriptions per account
     */
    static protected $N_SUBSCRS = 5;

    /**
     * @var int - account number
     */
    protected $nId = false;


    /**
     * Constructor
     * @param int $nId - account number
     * @param bool $bReset - account reset flag
     */
    function __construct($nId = false, $bReset = false) {
        if ($nId === false) return;
        $this->nId = $nId;
        if ($bReset)
            $this->resetProfile();
        else
            $this->loadProfile();
    }

    /**
     * Delete account
     * @return array - set of subscription numbers
     */
    function delete() {
        foreach ($this->aSubscrIds as $id) {
            $obj = new FfSubscr($id, true);
            $obj->delete();
        }
        $a_ret = $this->aSubscrIds;
        $this->resetProfile();
        $this->saveProfile();
        return $a_ret;
    }

    /**
     * Set shared data
     * @param Prof_FfManager $oProf - manager profile object
     */
    static function setSharedData(Prof_FfManager $oProf) {
        if (($n = $oProf->nAccountExp) && $n < self::N_EXP_MAX)
            self::$EXP_OBJ = $n;
        if (($n = $oProf->nSubscrsPerAcct) && $n < self::N_SUBSCRS_MAX)
            self::$N_SUBSCRS = $n;
    }

    /**
     * Get cache resource reference
     * @return string
     */
    static function getCacheRef() {
        return self::FILE_JSON_PROF;
    }

    /**
     * Set account password
     * @param string $sPwd - new value
     * @param string $sPwdOld - old value
     */
    function setPassword($sPwd, $sPwdOld = '') {
        if (!($sPwdOld == $this->sPassword && $sPwd)) return false;
        $this->sPassword = $sPwd;
        $this->setLastVisit();
        $this->saveProfile();
        return true;
    }

    /**
     * Validate account password
     * @param string $sPwd - password value
     * @return bool
     */
    function checkPassword($sPwd) {
        return $sPwd == $this->sPassword;
    }

    /**
     * Reset subscriptions
     */
    function resetSubscrs() {
        $this->aSubscrIds = array();
        $this->aSubscrTitles = array();
        $this->saveProfile();
    }

    /**
     * Attach subscription
     * @param int $nId - subscription number
     * @return bool
     */
    function attachSubscr($nId) {
        if ($this->nId === false || count($this->aSubscrIds) >= self::$N_SUBSCRS)
            return false;
        if (in_array($nId, $this->aSubscrIds))
            return false;
        array_push($this->aSubscrIds, $nId);
        array_push($this->aSubscrTitles, '');
        $this->setLastVisit();
        $this->saveProfile();
        return true;
    }

    /**
     * Rename subscription
     * @param int $nId - subscription number
     * @return bool
     */
    function renameSubscr($nId, $sTitle) {
        if ($this->nId === false) return false;
        $i = array_search($nId, $this->aSubscrIds);
        if ($i === false || $i >= count($this->aSubscrIds)) return false;
        $this->aSubscrTitles[$i] = $sTitle;
        $this->setLastVisit();
        $this->saveProfile();
        return true;
    }

    /**
     * Detach subscription
     * @param int $nId - subscription number
     * @return bool
     */
    function detachSubscr($nId) {
        if ($this->nId === false) return false;
        $i = array_search($nId, $this->aSubscrIds);
        if ($i === false || $i >= count($this->aSubscrIds)) return false;
        array_splice($this->aSubscrIds, $i, 1);
        array_splice($this->aSubscrTitles, $i, 1);
        $this->setLastVisit();
        $this->saveProfile();
        return true;
    }

    /**
     * Check if the account is expired
     * @return bool
     */
    function isExpired() {
        return $this->nId === false ||
            FfShared::isExpired($this->nLastVisit, self::$EXP_OBJ* 60* 24);
    }

    protected function setLastVisit() {
        $this->nLastVisit = time();
    }

    protected function saveProfile() {
        $obj = new Prof_FfAccount();
        if (!$obj->copyProfile($this)) return;
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

    /**
     * Get filename
     * @return string
     */
    protected function getFilename($sFile) {
        return sprintf($sFile, $this->nId);
    }
}
