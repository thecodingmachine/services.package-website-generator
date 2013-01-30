Composer packages website generator
===================================

This project is used to generate websites for each of the composer packages of the Mouf framework.
You can download and use this webapp for your own project too.

Basically, a "cron" task runs every night. It will check on packagist.org to see if there are new packages
or new versions of some packages available. If so, it will download the pages and generate a mini website
containing the README, plus any other documentation file found in composer.json.

Documentation files must be declared in _composer.json_ according to the Mouf's documentation syntax:

	{
	    ...
	    "extra": {
	        "mouf": {
	            "doc": [
	                {
	                    "title": "Using FINE",
	                    "url": "doc/using_fine.html"
	                },
	                {
	                    "title": "Date functions",
	                    "url": "doc/date_functions.html"
	                },
	                {
	                    "title": "Currency functions",
	                    "url": "doc/currency_functions.html"
	                }
	            ]
	             
	        }
	    }
	}


Installation
============

Installation is done like any Mouf project:
- clone the repository in your web directory
- run "php composer.phar install" to install the environment
- from your browser, browse to: http://[yourserver]/[apppath]/vendor/mouf/mouf
- follow the Mouf install procedure
- log into Mouf, and edit the configuration (note: the database configuration is useless right now, the application does not need a database to run)

Note: in the configuration file, you specify your packagist's username. All your packages will be available in the application.

Finally, install a cron task that will periodically check packagist to see if there are new modules to install.
The command to run is:
	php [path_to_app]/scripts/getPackagistProjects.php
	
Of course, you will need to run this script at least once before your packages' documentation is available.
