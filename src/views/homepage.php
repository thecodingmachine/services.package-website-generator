<?php /* @var $this Mouf\Controllers\HomePageController */ ?>

<h1><em><?php echo $this->userName; ?>'s</em> packages</h1>
<?php 
foreach ($this->packages as $package) {
	/* @var $package Mouf\Services\Package */
	$packageName = $package->getName();
	$packageVersion = $package->getPackageVersion($package->getLatest());
	
?>
	<div class="media">
    	<a class="pull-left" href="#">
    		<img class="media-object" data-src="holder.js/64x64">
    	</a>
    	<div class="media-body">
    		<h4 class="media-heading">
    			<?php echo "<a href='".ROOT_URL.$this->userName.'/'.$packageName."/' style='margin-right:5px; margin-bottom:5px'>".$packageName." (".$packageVersion->getVersionDisplayName().")</a>"; ?>		
    		</h4>
    		<?php
    		$composerJson = $packageVersion->getComposerJson(); 
    		if (isset($composerJson['description'])) {
    			echo htmlentities($composerJson['description'], ENT_QUOTES, 'UTF-8'); 
			} ?>
     
    
    	</div>
    </div>
<?php 
	
	
}
?>