<?php /* @var $this Mouf\Controllers\HomePageController */ ?>

<h1><em><?php echo $this->userName; ?>'s</em> packages</h1>
<?php 
foreach ($this->packages as $package) {
	/* @var $package Mouf\Services\Package */
	$packageName = $package->getName();
	$packageVersion = $package->getPackageVersion($package->getLatest());
	echo "<a class='btn' href='".ROOT_URL.$this->userName.'/'.$packageName."/' style='margin-right:5px; margin-bottom:5px'>".$packageName." (".$packageVersion->getVersionDisplayName().")</a>";
}
?>