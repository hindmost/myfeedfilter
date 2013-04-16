<?php
$dir_pre = dirname(__FILE__). '/';
require $dir_pre. 'inc/config.inc.php';

require $dir_pre. 'ff/FfManager.inc.php';

FfManager::setDirs(DIR_RELDATA, DIR_RELCACHE, DIR_CACHE);
$obj = new FfManager();
$obj->runScraper();
lockRunsCmd($dir_pre. FILE_LOCKRUNS, 2);
