<?php

/**
 * CURL Functions Wrapper
 */
class CurlWrap
{
    static protected $A_OPTS = array(
        CURLOPT_NOBODY => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_HEADER => false,
        CURLINFO_HEADER_OUT => false,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 Gecko/20110920 Firefox/3.6.23',
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    );
    static protected $A_OPTS_BKUP = 0;
    static protected $B_CURL = false;
    static protected $B_GETHEADERS = false;
    static protected $N_RET_ERR = 0;
    static protected $A_RET_NFO = array();

    /**
     * Set/modify config
     * @param array $aOpts - array of CURL options
     * @param bool $bCurl - flag of using CURL
     * @param bool $bGetHeaders - flag of getting HTTP headers
     */
    static function config($aOpts = null, $bCurl = null, $bGetHeaders = null) {
        static $n_calls = 0, $b_curl_avail = false;
        if ($n_calls++ == 0)
            $b_curl_avail = function_exists('curl_init');
        if ($bCurl !== null)
            self::$B_CURL = $bCurl && $b_curl_avail;
        if ($bGetHeaders !== null)
            self::$B_GETHEADERS = (bool)$bGetHeaders;
        $aOpts[CURLOPT_HEADER] = $aOpts[CURLINFO_HEADER_OUT] = self::$B_GETHEADERS;
        if (is_array($aOpts) && self::$B_CURL) {
            foreach ($aOpts as $key => $val)
                if (is_int($key)) self::$A_OPTS[$key] = $val;
        }
    }

    /**
     * Backup config
     */
    static function backupConfig() {
        self::$A_OPTS_BKUP = self::$A_OPTS;
    }

    /**
     * Restore config
     */
    static function restoreConfig() {
        if (self::$A_OPTS_BKUP)
            self::$A_OPTS = self::$A_OPTS_BKUP;
    }

    /**
     * Get result error
     * @return int
     */
    static function getResultErr() {
        return self::$N_RET_ERR;
    }

    /**
     * Get result info
     * @return array
     */
    static function getResultInfo() {
        return self::$A_RET_NFO;
    }

    /**
     * Get last url
     * @return string
     */
    static function getLastUrl() {
        return isset(self::$A_RET_NFO['url'])? self::$A_RET_NFO['url'] : '';
    }

    /**
     * Get content of remote resource
     * @param string $url - URL of remote resource
     * @param array|string|false $post - POST data
     * @param string $urlRef - Referer URL
     * @return string|false
     */
    static function get($url, $post = '', $urlRef = '') {
        self::$N_RET_ERR = 0;
        self::$A_RET_NFO = array();
        if (!self::isUrl($url))
            return false;
        $s_post = is_array($post) ? http_build_query($post) : $post;
        if (!$urlRef)
            $urlRef = substr($url, 0, strrpos($url, '/') + 1);
        return self::$B_CURL ? self::getByCurl($url, $s_post, $urlRef) :
                self::getBySox($url, $s_post, $urlRef);
    }

    /**
     * getByCurl
     * @param string $url
     * @param string|false $post
     * @param string $urlRef
     * @return string|false
     */
    protected
    static function getByCurl($url, $sPost = '', $urlRef = '') {
        if (!self::$B_CURL)
            return false;
        $hc = curl_init();
        curl_setopt_array($hc, self::$A_OPTS);
        curl_setopt($hc, CURLOPT_URL, $url);
        curl_setopt($hc, CURLOPT_REFERER, $urlRef);
        if ($sPost) {
            curl_setopt($hc, CURLOPT_POST, 1);
            if (is_string($sPost)) curl_setopt($hc, CURLOPT_POSTFIELDS, $sPost);
        }
        $text = curl_exec($hc);
        $i_err = curl_errno($hc);
        $a_nfo = curl_getinfo($hc);
        curl_close($hc);
        if (self::$A_OPTS[CURLOPT_HEADER] && ($k = $a_nfo['header_size'])) {
            $a_nfo['httpheader'] = rtrim(substr($text, 0, $k));
            $text = substr($text, $k);
        }
        $i_code = intval($a_nfo['http_code']);
        $b_ok = $i_err == 0 &&
            ($i_code == 200 || !self::$A_OPTS[CURLOPT_FOLLOWLOCATION] &&
                $i_code > 300 && $i_code < 400);
        self::$N_RET_ERR = $i_err;
        self::$A_RET_NFO = $a_nfo;
        return $b_ok? ($text? $text : 1) : false;
    }

    /**
     * getBySox
     * @param string $url
     * @param string|false $post
     * @param string $urlRef
     * @return string|false
     */
    protected
    static function getBySox($url, $sPost = '', $urlRef = '') {
        $a_part = parse_url($url);
        $host = $a_part['host'];
        if ($url == '' || $host == '')
            return false;
        $port = isset($a_part['port']) ? $a_part['port'] : 80;
        $path = (isset($a_part['path']) ? $a_part['path'] : '/') .
                (isset($a_part['query']) ? '?' . $a_part['query'] : '');
        if ($sPost)
            $hdr_post =
                    "Content-Type: application/x-www-form-urlencoded\r\n" .
                    "Content-Length: " . strlen($sPost) . "\r\n";
        $req = ($sPost ? 'POST' : 'GET') . " $path HTTP/1.0\r\n" .
                "Host: $host\r\n" .
                "User-Agent: " . self::$A_OPTS[CURLOPT_USERAGENT] . "\r\n" .
                "Referer: " . $urlRef . "\r\n" .
                ($sPost ? $hdr_post : '') .
                "Connection: Close\r\n\r\n" .
                ($sPost ? "$sPost\r\n\r\n" : '');
        if (!($fp = fsockopen($host, $port, $i_err, $s_err, self::$A_OPTS[CURLOPT_CONNECTTIMEOUT])))
            return false;
        fwrite($fp, $req);
        stream_set_timeout($fp, self::$A_OPTS[CURLOPT_TIMEOUT]);
        $text = '';
        while (!feof($fp)) {
            $text.= fgets($fp, 1024);
        }
        $a_nfo = stream_get_meta_data($fp);
        fclose($fp);
        $b_ok = !$a_nfo['timed_out'];
        if (($k = strpos($text, "\n\n")) || ($k = strpos($text, "\r\n\r\n"))) {
            $a_nfo['httpheader'] = rtrim(substr($text, 0, $k));
            $text = substr($text, $k + 2);
        }
        self::$N_RET_ERR = $i_err;
        self::$A_RET_NFO = $a_nfo;
        self::$A_RET_NFO['url'] = $url;
        return $b_ok? $text : false;
    }

    /**
     * Validate url
     * @param string $url
     * @return bool
     */
    protected
    static function isUrl($url) {
        return $url && strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0;
    }

}

if (!function_exists('curl_setopt_array')) {
    function curl_setopt_array(&$hc, $a_opts) {
        foreach ($a_opts as $opt => $val) {
            if (!curl_setopt($hc, $opt, $val))
                return false;
        }
        return true;
    }
}

