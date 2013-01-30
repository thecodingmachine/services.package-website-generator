<?php 
namespace Mouf\Services;

/**
 * Represents a whole package (with all branches and tags it contains)
 * 
 * @author David Negrier
 */
class Package {
	
	private $packageDir;
	
	private $versions;
	
	public function __construct($packageDir) {
		$this->packageDir = $packageDir;
	}
	
	/**
	 * Returns the package directory.
	 */
	public function getPackageDir() {
		return $this->packageDir;
	}
	

	/**
	 * Returns the list of all branch versions for the package in directory $dir
	 */
	public function getVersions() {
		if ($this->versions) {
			return $this->versions;
		}
		$versionsDir = glob($this->packageDir.DIRECTORY_SEPARATOR.'*');
		$versions = array();
		foreach ($versionsDir as $versionDir) {
			$versions[] = basename($versionDir);
		}
		$this->versions = $versions;
		return $versions;
	}
	
	/**
	 * Returns the latest branch version for this package (based on PHP's version_compare function).
	 * 
	 * @return NULL|string
	 */
	public function getLatest() {
		return $this->getLatestFromArray($this->getVersions());
	}
	
	private function getLatestFromArray($versions) {
		if (empty($versions)) {
			return null;
		}
		if (count($versions) == 1) {
			return $versions[0];
		}
		$latest = $versions[0];
		for ($i=1, $cnt = count($versions); $i<$cnt; $i++) {
			if (version_compare($latest, $versions[$i]) < 0) {
				$latest = $versions[$i];
			}
		}
		
		return $latest;
	}
	
	/**
	 * Returns an object representing the requested version.
	 * 
	 * @param string $version
	 * @return \Mouf\Services\PackageVersion
	 */
	public function getPackageVersion($version) {
		return new PackageVersion($this, $version);
	}
	
	/**
	 * Returns an array of all versions, from the most recent to the oldest
	 * The key is the version number and the value the relative path to the version from package dir. 
	 */
	public function getVersionsMap() {
		
		$versions = array();
		
		$tmpVersions = $this->getVersions();
		foreach ($tmpVersions as $ver) {
			$versions[$ver] = "version/".$ver."/";
		}
		
		uksort($versions, "version_compare");
		
		$versions = array_reverse($versions, true);
		
		return $versions;
	}
}

?>