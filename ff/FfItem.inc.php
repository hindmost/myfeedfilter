<?php
/**
 * @package feedFilter
 * @author  me@savreen.com
 * @license http://opensource.org/licenses/gpl-license GPL
 */

/**
 * Feed Item Storage
 */
class FfItem extends Jsonable
{
    /**
     * @var int - timestamp of item publication
     */
    protected $date = 0;

    /**
     * @var string - title of item
     */
    protected $title = '';

    /**
     * @var string - content of item
     */
    protected $content = '';

    /**
     * @var string - URL of item
     */
    protected $link = '';

    /**
     * @var string - combined extra elements of item (e.g. categories)
     */
    protected $xtras = '';


    /**
     * Constructor
     * @param int $nDate - publication timestamp
     * @param string $sTitle - title
     * @param string $sCont - content
     * @param string $sLink - URL
     * @param string $sXtras - extra elements
     */
    function __construct($nDate, $sTitle, $sCont, $sLink = '', $sXtras = '') {
        if (!is_int($nDate)) return;
        $this->date = $nDate;
        $this->title = FfShared::strip4Json($sTitle);
        $this->content = FfShared::strip4Json($sCont);
        $this->link = FfShared::strip4Json($sLink);
        $this->xtras = FfShared::strip4Json($sXtras);
    }

    /**
     * Check item for validity
     * @return bool
     */
    function isValid() {
        return is_int($this->date) && $this->date > 0;
    }
}
