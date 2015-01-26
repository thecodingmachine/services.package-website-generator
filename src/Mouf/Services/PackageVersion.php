<?php 
namespace Mouf\Services;

use Mouf\Widgets\Package;

/**
 * Represents a specific version (tag or branch) from a package.
 * 
 * @author David Negrier
 */
class PackageVersion {
	
	private $package;
	private $directory;
	private $version;
	private $composerJson;
	
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
	
	/**
	 * Returns the composer.json file for this version of the package, as an array.
	 * @return array.
	 */
	public function getComposerJson() {
		if (!$this->composerJson) {
			$this->composerJson = json_decode(file_get_contents($this->getDirectory().'/composer.json'), true);
		}
		return $this->composerJson;
	}
}

?>