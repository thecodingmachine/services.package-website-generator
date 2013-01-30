<?php 
namespace Mouf\Services;

/**
 * Represents a specific version (tag or branch) from a package.
 * 
 * @author David Negrier
 */
class PackageVersion {
	
	private $package;
	private $directory;
	private $version;
	
	/**
	 * 
	 * @param string $packageDir
	 * @param string $version
	 * @param bool $isStable
	 */
	public function __construct(Package $package, $version) {
		$this->directory = $package->getPackageDir().DIRECTORY_SEPARATOR.$version;
		$this->package = $package;
		$this->version = $version;
	}
	

	public function getVersionDisplayName() {
		return $this->version;
	}
	
	public function getDirectory() {
		return $this->directory;
	}
	
	public function getPackage() {
		return $this->package;
	}
}

?>