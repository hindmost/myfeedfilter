<?php
require 'inc/config.inc.php';

session_cache_expire(12* 60);
session_start();
if (isset($_REQUEST['admin_login']) && is_array($arr = $_REQUEST['admin_login'])) {
    $_SESSION['admin_login']= $arr;
}
if (!isset($_SESSION['admin_login'])) {
    return;
}
if (isset($_REQUEST['logout']) && $_REQUEST['logout']) {
    unset($_SESSION['admin_login']);
    return;
}
$a_login = $_SESSION['admin_login'];
if (!(is_array($a_login) && count($a_login) == 2 && $a_login[0] == ADMIN_ID && $a_login[1] == ADMIN_PWD)) {
    unset($_SESSION['admin_login']);
    return;
}
if (!isset($_REQUEST['name']))
    return;
$name = $_REQUEST['name'];
if (!in_array($name, $A_CMD_ADM))
    return;
$a_params = isset($_REQUEST['params']) ? $_REQUEST['params'] : array();
session_regenerate_id(true);

require 'ff/FfManager.inc.php';

FfManager::setDirs(DIR_RELDATA, DIR_RELCACHE, DIR_CACHE);
$obj = new FfManager();
if (method_exists($obj, $name)) {
    echo call_user_func_array(array($obj, $name), $a_params);
}
else {
    echo FfManager::getCacheRefsAdmin();
}

