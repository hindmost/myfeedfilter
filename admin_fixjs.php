<?php
$dir_pre = dirname(__FILE__). '/';
require $dir_pre. 'inc/config.inc.php';

require $dir_pre. 'ff/FfManager.inc.php';
require $dir_pre. 'inc/admin_fixjs.inc.php';

FfManager::setDirs(DIR_RELDATA, DIR_RELCACHE, DIR_CACHE);
FfManager::saveCacheRefs(FILE_CACHEREFS);

$a_vars_index = array(
    'URL_CACHEREFS' => DIR_CACHE. '/'. FILE_CACHEREFS,
    'URL_CMD' => URL_CMD,
    'A_CMD' => $A_CMD,
    'MARK_KW_NEG' => FfSubscr::MARK_KW_NEG,
    'MARK_KW_XTRA' => FfSubscr::MARK_KW_XTRA,
    'B_AJAXCACHE' => B_AJAXCACHE,
    'B_STAMP_ETAG' => B_STAMP_ETAG,
    'INT_POLL_MIN' => INT_POLL_MIN,
    'INT_POLL_LONG' => INT_POLL_LONG,
    'INT_POLL_SHORT' => INT_POLL_SHORT,
    'INT_SHARED' => INT_SHARED,
    'N_ITEMS_PRECUT' => N_ITEMS_PRECUT,
    'NAME_COOKIE' => NAME_COOKIE,
    'EXP_COOKIE' => EXP_COOKIE,
);

list($a_props, $n_arrprops) = FfManager::getProfileProps();
$a_vars_admin = array(
    'URL_CMD' => URL_CMD_ADM,
    'A_CMD' => $A_CMD_ADM,
    'A_ALLPROPS' => $a_props,
    'N_ARRPROPS' => $n_arrprops,
    'B_AJAXCACHE' => B_AJAXCACHE,
    'B_STAMP_ETAG' => B_STAMP_ETAG,
    'INT_UPD' => INT_ADMINUPD,
);

fixJsFile(FILE_JS_INDEX, $a_vars_index);
fixJsFile(FILE_JS_ADMIN, $a_vars_admin);

