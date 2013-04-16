<?php
require 'inc/config.inc.php';

session_cache_expire(12* 60);
session_start();
if (isset($_REQUEST['login']) && is_array($arr = $_REQUEST['login']))
    $_SESSION['login']= $arr;
if (!isset($_SESSION['login']))
    return;
if (isset($_REQUEST['logout']) && $_REQUEST['logout']) {
    unset($_SESSION['login']);
    return;
}
$a_login = $_SESSION['login'];
if (!(is_array($a_login) && count($a_login) == 2))
    return;
if (!isset($_REQUEST['name']))
    return;
$name = $_REQUEST['name'];
if (!in_array($name, $A_CMD))
    return;
$a_params = isset($_REQUEST['params']) ? $_REQUEST['params'] : false;
if (!is_array($a_params)) $a_params = array();
session_regenerate_id(true);

require 'ff/FfManager.inc.php';

FfManager::setDirs(DIR_RELDATA, DIR_RELCACHE, DIR_CACHE);
$obj = new FfManager($a_login[0], $a_login[1]);
$ret = false;
if (method_exists($obj, $name)) {
    $ret = call_user_func_array(array($obj, $name), $a_params);
}
if ($ret)
    echo $ret;
if (!$obj->checkLastAccount()) {
    unset($_SESSION['login']);
}

