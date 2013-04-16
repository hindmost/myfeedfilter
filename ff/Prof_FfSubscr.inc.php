<?php
/**
 * @package feedFilter
 * @author  me@savreen.com
 * @license http://opensource.org/licenses/gpl-license GPL
 */

/**
 * Profile of feedFilter Subscription
 */
class Prof_FfSubscr extends Jsonable implements IProfile
{
    /**
     * @var string - subscription title
     */
    protected $sTitle = '';

    /**
     * @var array - subscription filter keywords
     */
    protected $aKeywords = array();

    /**
     * @var array - types of subscription filter keywords
     */
    protected $aKeywordTypes = array();

    /**
     * @var array - feeds selection (set of feed numbers)
     */
    protected $aFeedIds = 0;

    /**
     * @var int - last visit timestamp
     */
    protected $nLastVisit = 0;


    function resetProfile() {
        $this->sTitle = '';
        $this->aKeywords = array();
        $this->aKeywordTypes = array();
        $this->aFeedIds = 0;
        $this->nLastVisit = 0;
    }

    function copyProfile($obj) {
        if (!$obj || !($obj instanceof Prof_FfSubscr)) return false;
        $this->sTitle = $obj->sTitle;
        $this->aKeywords = $obj->aKeywords;
        $this->aKeywordTypes = $obj->aKeywordTypes;
        $this->aFeedIds = $obj->aFeedIds;
        $this->nLastVisit = $obj->nLastVisit;
        return true;
    }

    /**
     * Get title of subscription
     * @return string
     */
    function getTitle() {
        return $this->sTitle;
    }
}
