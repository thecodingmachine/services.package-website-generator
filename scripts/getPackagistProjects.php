<?php 
use Mouf\Services\PackagesInstaller;

require_once __DIR__.'/../mouf/Mouf.php';

$verbose = false;
foreach ($argv as $arg) {
	if ($arg == '-v' || $arg == '--verbose') {
		$verbose = true;
	}
}

$packagesInstaller = Mouf::getPackagesInstaller();
$packagesInstaller->run(PACKAGIST_USERNAME, $verbose);

error_log("Packagist packages download done.");