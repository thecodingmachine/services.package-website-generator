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
	private $isStable;
	
	/**
	 * 
	 * @param string $packageDir
	 * @param string $version
	 * @param bool $isStable
	 */
	public function __construct(Package $package, $version, $isStable) {
		$this->directory = $package->getPackageDir().DIRECTORY_SEPARATOR.($isStable?"tags":"branches").DIRECTORY_SEPARATOR.$version;
		$this->package = $package;
		$this->version = $version;
		$this->isStable = $isStable;
	}
	

	public function getVersionDisplayName() {
		return $this->version.($this->isStable?'':'-dev');
	}
	
	public function getDirectory() {
		return $this->directory;
	}
	
	public function getPackage() {
		return $this->package;
	}
}

?>