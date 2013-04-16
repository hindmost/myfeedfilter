<?php
/**
 * @package feedFilter
 * @author  me@savreen.com
 * @license http://opensource.org/licenses/gpl-license GPL
 */

/**
 * Profile of Feed Scraping Tool
 */
class Prof_FfScraper extends Jsonable implements IProfile
{
    /**
     * @var int - scraping run count
     */
    protected $nRuns = 0;

    /**
     * @var int - timestamp of last scraping run
     */
    protected $nLastRun = 0;

    /**
     * @var array - suitable parser IDs by feed
     */
    protected $aSuitParsers = array();

    /**
     * @var array - timestamps of last scraped items by feed
     */
    protected $aLastItems = array();

    /**
     * @var array - timestamps of last scraping runs by feed
     */
    protected $aLastRuns = array();

    /**
     * @var array - set of expiration time values by feed
     */
    protected $aExpires = array();

    /**
     * @var array - run stats by feed
     */
    protected $aRunStats = array();


    static protected $A_PROPS = array(
        'aSuitParsers', 'aLastItems', 'aLastRuns', 'aExpires', 'aRunStats',
        'nRuns', 'nLastRun',
    );
    static protected $N_ARRPROPS = 5;


    function resetProfile() {
        foreach (self::$A_PROPS as $i => $name)
            $this->$name = ($i < self::$N_ARRPROPS)? array() : 0;
    }

    function copyProfile($obj) {
        if (!($obj && ($obj instanceof Prof_FfScraper))) return false;
        foreach (self::$A_PROPS as $name)
            $this->$name = $obj->$name;
        return true;
    }

    /**
     * Init profile's array properties
     * @param int $nFeeds - feeds count
     */
    function initProfileArrProps($nFeeds) {
        if (!$nFeeds) return false;
        $this->aSuitParsers = array_fill(0, $nFeeds, false);
        $this->aLastItems = $this->aLastRuns =
        $this->aExpires = array_fill(0, $nFeeds, 0);
        $this->initRunStats($nFeeds);
    }

    /**
     * Init run stats property
     * @param int $nFeeds - feeds count
     */
    function initRunStats($nFeeds) {
        $this->aRunStats = array_fill(0, $nFeeds, array_fill(0, 5, 0));
    }
}
