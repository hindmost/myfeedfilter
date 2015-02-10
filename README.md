my feedFilter
===============

my feedFilter is just a simple tool that allows you automatically filter RSS feeds from your favorite sources, so you only see what you really interested in.

[Screencast] (http://youtu.be/OKJ3lqLyupw)


Features
-------------
* keyword filtering
* filtering by author and category/tag
* regular expressions
* sound notification for a new results
* accounts and customizable subscriptions


Requirements
-------------
* Apache web server
* PHP 5.0+ with the following extensions installed/enabled:
* cURL
* SimpleXML
* iconv (optional)


Supported (tested) browsers
-------------
* Firefox
* Chrome
* Opera
* IE7+


Installation
-------------
* Copy (upload) content of this package to the desired location on your web server.
* Edit config parameters in the `inc/config.inc.php` file (optionally).
* Run the `admin_fixjs.php` script (you have to run this script whenever the config file changes).
* Set up the manager profile on the admin webpage by accessing `admin.html` in a browser.
* Set up a cron job on your site for regular running of the `admin_run.php` script. If you have a problems with cron service on your site, you may use one of the bunch of online cronjob services.


Thanks
-------------
* [Twitter Bootstrap] (http://twitter.github.com/bootstrap/index.html)


License
-------------
* [GNU General Public License] (http://opensource.org/licenses/gpl-license)
