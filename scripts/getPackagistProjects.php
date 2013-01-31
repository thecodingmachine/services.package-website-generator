<?php 
use Mouf\Services\PackagesInstaller;

require_once __DIR__.'/../mouf/Mouf.php';

$packagesInstaller = Mouf::getPackagesInstaller();
$packagesInstaller->run(PACKAGIST_USERNAME);

error_log("Packagist packages download done.");