Composer packages documentation generator
=========================================

This project is used to **generate a simple documentation website for your composer packages**.

Demo
----

Want to see a sample? We use it at TheCodingMachine to generate the documentation of the [Mouf framework](http://mouf-php.com/packages/).
You can download and use this webapp for your own project too.

How does it work?
-----------------

You configure the webapp with your packagist user name. A "cron" task runs every night. 
It will check on [packagist.org](http://packagist.org) to see if there are new packages
or new versions of some packages available. If so, it will download the package and generate a mini website
containing the README, plus any other documentation files from your package if they have been declared in _composer.json_.

Documentation files must be declared in _composer.json_ according to the **Mouf's documentation syntax**:

	{
	    ...
	    "extra": {
	        "mouf": {
	        	"logo": "logo64x64.png",
	            "doc": [
	                {
	                    "title": "Using my package",
	                    "url": "doc/using_package.md"
	                },
	                {
	                    "title": "Date functions",
	                    "url": "doc/date_functions.md"
	                },
	                {
	                    "title": "Currency functions",
	                    "url": "doc/currency_functions.html"
	                }
	            ]
	             
	        }
	    }
	}

As you can see, you can also provide a logo (as a 64x64 image).
The documentation can be provided in HTML or Markdown format.

Installation
------------

Installation is done like any Mouf project:
- **clone the repository** in your web directory
- run **php composer.phar install** to install the environment
- from your browser, **browse to**: http://[yourserver]/[apppath]/vendor/mouf/mouf
- follow the **Mouf install procedure**
- log into Mouf, and **edit the configuration**
- at the root of your project, edit the .htaccess file and comment this line:

```
#RewriteCond %{REQUEST_FILENAME} !-d
```

A few things you might like to note:
- in the configuration file, you specify your packagist's username. All your packages will be available in the application.
- in the configuration file, you can provide a list of "starred" projects. On the homepage of your site, the starred projects will be highlighted.
- you don't need a database (at least in the current version!) The website directly runs from the files.

Finally, **install a cron task** that will periodically check packagist to see if there are new modules to install.
The command to run is:
	php [path_to_app]/scripts/getPackagistProjects.php
	
Of course, you will need to **run this script at least once** before your packages' documentation is available.

Work in progress
----------------

You are currently looking at a very early release of Composer Package Website Generator.
There are a number of things remaining to do:
- add a cache system (the current version might not support a very high load)
- add a search engine
- and much more...

If you are interested in the development or want to help, do not hesitate to send me a mail: d.negrier at thecodingmachine.com
