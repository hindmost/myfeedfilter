<?php
/**
 * @package feedFilter
 * @author  me@savreen.com
 * @license http://opensource.org/licenses/gpl-license GPL
 */

/**
 * Shared properties and methods
 */
class FfShared
{
    /**
     * @static string $DIR_JSON - work data directory path
     */
    static protected $DIR_DATA = '';

    /**
     * @static string $DIR_JSON - JSON directory path
     */
    static protected $DIR_JSON = '';


    /**
     * Set work data directory path
     * @param string $s - relative directory path
     */
    static function fixDir($s) {
        return ($s = rtrim($s, '/'))? realpath(dirname(__FILE__). '/'. $s) : false;
    }

    /**
     * Set work data directory path
     * @param string $sDir - relative directory path
     */
    static function setDataDir($sDir) {
        if (!($s= self::fixDir($sDir))) return false;
        self::$DIR_DATA = $s. '/';
        return true;
    }

    /**
     * Set JSON directory path
     * @param string $sDir - relative directory path
     */
    static function setJsonDir($sDir) {
        if (!($s= self::fixDir($sDir))) return false;
        self::$DIR_JSON = $s. '/';
        return true;
    }

    /**
     * Get work data directory path
     * @return string
     */
    static function getDataDir() {
        return self::$DIR_DATA;
    }

    /**
     * Get JSON directory path
     * @return string
     */
    static function getJsonDir() {
        return self::$DIR_JSON;
    }

    /**
     * Save variable to file
     * @param string $sFile - target file name
     * @param string $var - variable value
     */
    static function saveVar($sFile, $var) {
        file_put_contents(self::$DIR_DATA. $sFile, serialize($var));
    }

    /**
     * Load variable from file
     * @param string $sFile - source file name
     * @return mixed
     */
    static function loadVar($sFile) {
        if (!file_exists($sFile = self::$DIR_DATA. $sFile)) return false;
        return unserialize(file_get_contents($sFile));
    }

    /**
     * Load integer variable
     * @param string $sFile - 
     * @return int
     */
    static function loadVarInt($sFile) {
        return is_numeric($v = self::loadVar($sFile))? intval($v) : 0;
    }

    /**
     * Load array variable
     * @param string $sFile - 
     * @return array|false
     */
    static function loadVarArr($sFile) {
        $arr = self::loadVar($sFile);
        return (is_array($arr) && count($arr))? $arr : false;
    }

    /**
     * Save JSON to file
     * @param string $sFile - target file name
     * @param string $sCont - JSON string
     */
    static function saveJson($sFile, $sCont) {
        file_put_contents(self::$DIR_JSON. $sFile, $sCont);
    }

    /**
     * Check for expiration
     * @param int $nLast - timestamp of last event
     * @param int $nExp - expiration time (secs)
     * @return bool
     */
    static function isExpired($nLast, $nExp) {
        return time() - $nLast > $nExp;
    }

    /**
     * Split string to array
     * @param string $s - input string
     * @param bool $bByCr - use only 'CR' character as a separator
     * @return array
     */
    static function strToArr($s, $bByCr = false) {
        return is_array($a = preg_split('/'. ($bByCr? '[\n\r]':'\s'). '+/', $s))?
            $a : array();
    }

    /**
     * Strip illegal characters in JSON format from a string
     * @param string $s - input string
     * @return string
     */
    static function strip4Json($s) {
        return trim(preg_replace('/ {2,}/u', ' ',
            preg_replace('/[\x00-\x1F]+/u', ' ', (string)$s)
            ));
    }
}
