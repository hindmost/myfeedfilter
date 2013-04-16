<?php
/**
 * feedFilter application - Config data:
 * Note: Whenever either parameter (except admin credentials) in this file changes,
 *  the "admin_fixjs.php" script must be run to apply a changes
 */

/**
 * feedFilter's data directory (path relative to feedFilter lib directory):
 */
define('DIR_RELDATA', 'data');

/**
 * Cache directory (path relative to feedFilter lib directory):
 */
define('DIR_RELCACHE', '../cache');

/**
 * Cache directory (path relative to the app directory):
 */
define('DIR_CACHE', 'cache');

/**
 * File of cache resource references - filename:
 */
define('FILE_CACHEREFS', 'cache-urls.json');

/**
 * Relative paths to javascript files:
 */
define('FILE_JS_INDEX', 'index.js');
define('FILE_JS_ADMIN', 'admin.html');

/**
 * Admin user credentials:
 */
define('ADMIN_ID', 'admin');
define('ADMIN_PWD', 'pass');


/**
 * (index.js) url of the frontend command script:
 */
define('URL_CMD', 'cmd.php?name=');

/**
 * (admin.js) url of the backend command script:
 */
define('URL_CMD_ADM', 'admin.php?name=');

/**
 * (index.js) array of used frontend commands:
 */
$A_CMD = array(
    'authAccount', 'createAccount', 'createSubscr', 'setSubscrProfile'
);

/**
 * (admin.js) array of used backend commands:
 */
$A_CMD_ADM = array(
    'auth', 'setManagerProfile', 'resetScraperRunStats', 'resetAccounts', 'resetSubscrs'
);

/**
 * (index.js) flag of using jQuery ajax cache option:
 * (set it to true if you use custom Expires HTTP headers for cache resources)
 */
define('B_AJAXCACHE', false);

/**
 * (index.js) flag of using ETag header as file timestamp:
 * (set it to true if your server includes ETag in the response HTTP headers)
 */
define('B_STAMP_ETAG', true);

/**
 * (index.js) interval between timestamp polling queries - minimal value (secs):
 */
define('INT_POLL_MIN', 60);

/**
 * (index.js) interval between timestamp polling queries - long value (secs):
 */
define('INT_POLL_LONG', 10* 60);

/**
 * (index.js) interval between timestamp polling queries - short value (secs):
 */
define('INT_POLL_SHORT', 2* 60);

/**
 * (index.js) interval between shared info queries (times of timestamp queries):
 */
define('INT_SHARED', 30);

/**
 * (index.js) number of feed items to be removed from the top before adding new ones:
 */
define('N_ITEMS_PRECUT', 5);

/**
 * (index.js) cookie parameter name:
 */
define('NAME_COOKIE', 'ff_state');

/**
 * (index.js) cookie parameter expire value (days):
 */
define('EXP_COOKIE', 30);

/**
 * (admin.js) interval between admin update queries (msecs):
 */
define('INT_ADMINUPD', 5* 60* 1000);

