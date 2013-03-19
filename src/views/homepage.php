<?php use Mouf\Services\Package;

/* @var $this Mouf\Controllers\HomePageController */

if ($this->starredPackages) :?>
<h1><em><?php echo $this->userName; ?>'s</em> starred packages <img src="<?php echo ROOT_URL ?>src/views/images/star.png" alt="" /></h1>
<?php
 
foreach ($this->starredPackages as $package) {
	/* @var $package Mouf\Services\Package */
	$this->displayPackage($package);
}

endif; ?>
<h1><em><?php echo $this->userName; ?>'s</em> packages</h1>
<?php
 
foreach ($this->packages as $package) {
	/* @var $package Mouf\Services\Package */
	$this->displayPackage($package);
}

?>