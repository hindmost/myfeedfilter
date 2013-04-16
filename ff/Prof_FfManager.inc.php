<?php
/**
 * @package feedFilter
 * @author  me@savreen.com
 * @license http://opensource.org/licenses/gpl-license GPL
 */

/**
 * Profile of feedFilter Manager
 */
class Prof_FfManager extends Jsonable implements IProfile
{
    /**
     * @var array 
     */
    protected $aFeedUrls = array();

    /**
     * @var array 
     */
    protected $aFeedTitles = array();

    /**
     * @var array - timezones by feed country (UTC offsets in hours)
     */
    protected $aFeedTimezones = array();

    /**
     * @var array - items quota values by feed
     */
    protected $aFeedQuotas = array();

    /**
     * @var int - feed expiration time - min. value (secs)
     */
    protected $nFeedExpMin = 0;

    /**
     * @var int - feed expiration time - default value (secs)
     */
    protected $nFeedExpDeft = 0;

    /**
     * @var int - feed expiration extra time at weekend (%)
     */
    protected $nFeedExpAddWeek = 0;

    /**
     * @var int - feed expiration extra time at night (%)
     */
    protected $nFeedExpAddNite = 0;

    /**
     * @var int - start of night (offset hours from 0:00)
     */
    protected $nFeedNiteStart = 0;

    /**
     * @var int - end of night (offset hours from 0:00)
     */
    protected $nFeedNiteEnd = 0;

    /**
     * @var int - max. number of accounts
     */
    protected $nAccountsMax = 0;

    /**
     * @var int - max. number of subscriptions
     */
    protected $nSubscrsMax = 0;

    /**
     * @var int - max. number of subscriptions per account
     */
    protected $nSubscrsPerAcct = 0;

    /**
     * @var int - account expiration time (hours)
     */
    protected $nAccountExp = 0;

    /**
     * @var int - subscription expiration time (hours)
     */
    protected $nSubscrExp = 0;

    /**
     * @var int - feed item expiration time (mins)
     */
    protected $nSubscrItemsExp = 0;

    /**
     * @var int - feed items buffer size
     */
    protected $nSubscrItemsBuff = 0;

    /**
     * @var int - new feed items quota (lower limit)
     */
    protected $nSubscrItemsNew = 0;


    /**
     * @static array - all property names
     */
    static protected $A_PROPS = array(
        'aFeedUrls', 'aFeedTitles', 'aFeedTimezones', 'aFeedQuotas',
        'nFeedExpMin', 'nFeedExpDeft',
        'nFeedExpAddWeek', 'nFeedExpAddNite', 'nFeedNiteStart', 'nFeedNiteEnd',
        'nAccountsMax', 'nSubscrsMax', 'nSubscrsPerAcct',
        'nAccountExp', 'nSubscrExp', 'nSubscrItemsExp',
        'nSubscrItemsBuff', 'nSubscrItemsNew',
    );

    /**
     * @static int - array-properties count
     */
    static protected $N_ARRPROPS = 4;


    function resetProfile() {
        foreach (self::$A_PROPS as $i => $name)
            $this->$name = ($i < self::$N_ARRPROPS)? array() : 0;
    }

    function copyProfile($obj) {
        if (!($obj && ($obj instanceof Prof_FfManager))) return false;
        foreach (self::$A_PROPS as $name)
            $this->$name = $obj->$name;
        return true;
    }

    /**
     * Set profile properties
     * @param array $aFeedUrls
     * @param array $aFeedTimezones
     * @param array $aFeedQuotas
     * @param int $nFeedExpMin
     * @param int $nFeedExpDeft
     * @param int $nFeedExpAddNite
     * @param int $nFeedExpAddWeek
     * @param int $nFeedNiteStart
     * @param int $nFeedNiteEnd
     * @param int $nAccountsMax
     * @param int $nSubscrsMax
     * @param int $nSubscrsPerAcct
     * @param int $nAccountExp
     * @param int $nSubscrExp
     * @param int $nSubscrItemsExp
     * @param int $nSubscrItemsBuff
     * @param int $nSubscrItemsNew
     * @return bool
     */
    function setProfile() {
        $n_args = func_num_args();
        if ($n_args < 3) return false;
        $a_args = func_get_args();
        $b_ok = false;
        if (is_array($arr = $a_args[0]) && count($arr)) {
            $this->aFeedUrls =
                array_map(array('FfShared','strip4Json'), $arr);
            $this->aFeedTitles = array_fill(0, count($this->aFeedUrls), '');
            $b_ok = true;
        }
        if (is_array($arr = $a_args[1]) && count($arr)) {
            $this->aFeedTimezones = array_map('intval', $arr);
            $b_ok = true;
        }
        if (is_array($arr = $a_args[2]) && count($arr)) {
            $this->aFeedQuotas = array_map(array(self,'fixIntValue'), $arr);
            $b_ok = true;
        }
        $n = min($n_args, count(self::$A_PROPS));
        for ($i = 3, $k = self::$N_ARRPROPS; $i < $n; $i++, $k++) {
            if (!($v = self::fixIntValue($a_args[$i]))) continue;
            $name = self::$A_PROPS[$k];
            $this->$name = $v;
            $b_ok = true;
        }
        return $b_ok;
    }

    /**
     * Fix integer value
     * @param int $n - expire value
     * @return int
     */
    static protected function fixIntValue($n) {
        return (is_numeric($n) && $n > 0)? intval($n) : 0;
    }
}
