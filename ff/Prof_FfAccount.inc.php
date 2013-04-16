<?php
/**
 * @package feedFilter
 * @author  me@savreen.com
 * @license http://opensource.org/licenses/gpl-license GPL
 */

/**
 * Profile of feedFilter Account
 */
class Prof_FfAccount extends Jsonable implements IProfile
{
    /**
     * @var string - account password
     */
    protected $sPassword = '';

    /**
     * @var array - set of subscription numbers
     */
    protected $aSubscrIds = array();

    /**
     * @var array - set of subscription titles
     */
    protected $aSubscrTitles = array();

    /**
     * @var int - last visit timestamp
     */
    protected $nLastVisit = 0;


    function resetProfile() {
        $this->sPassword = '';
        $this->aSubscrIds = array();
        $this->aSubscrTitles = array();
        $this->nLastVisit = 0;
    }

    function copyProfile($obj) {
        if (!$obj || !($obj instanceof Prof_FfAccount)) return false;
        $this->sPassword = $obj->sPassword;
        $this->aSubscrIds = $obj->aSubscrIds;
        $this->aSubscrTitles = $obj->aSubscrTitles;
        $this->nLastVisit = $obj->nLastVisit;
        return true;
    }
}
