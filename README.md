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
	
Installation is done like any Mouf project.