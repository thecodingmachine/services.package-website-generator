<?php 
namespace Mouf\Services;

/**
 * Represents a whole package (with all branches and tags it contains)
 * 
 * @author David Negrier
 */
class Package {
	
	private $packageDir;
	
	private $branchesVersions;
	private $tagsVersions;
	
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
	public function getBranchVersions() {
		if ($this->branchesVersions) {
			return $this->branchesVersions;
		}
		$versionsDir = glob($this->packageDir.DIRECTORY_SEPARATOR.'branches/*');
		$versions = array();
		foreach ($versionsDir as $versionDir) {
			$versions[] = basename($versionDir);
		}
		$this->branchesVersions = $versions;
		return $versions;
	}
	
	/**
	 * Returns the list of all tags versions for the package in directory $dir
	 */
	public function getTagVersions() {
		if ($this->tagsVersions) {
			return $this->tagsVersions;
		}
		$versionsDir = glob($this->packageDir.DIRECTORY_SEPARATOR.'tags/*');
		$versions = array();
		foreach ($versionsDir as $versionDir) {
			$versions[] = basename($versionDir);
		}
		$this->tagsVersions = $versions;
		return $versions;
	}
	
	/**
	 * Returns the latest branch version for this package (based on PHP's version_compare function).
	 * 
	 * @return NULL|string
	 */
	public function getLatest() {
		return $this->getLatestFromArray($this->getBranchVersions());
	}
	
	/**
	 * Returns the latest tag version for this package (based on PHP's version_compare function).
	 * 
	 * @return NULL|string
	 */
	public function getLatestStable() {
		return $this->getLatestFromArray($this->getTagVersions());
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
	 * @param bool $isStable
	 * @return \Mouf\Services\PackageVersion
	 */
	public function getPackageVersion($version, $isStable) {
		return new PackageVersion($this, $version, $isStable);
	}
	
	/**
	 * Returns an array of all versions, from the most recent to the oldest
	 * The key is the version number and the value the relative path to the version from package dir. 
	 */
	public function getAllVersions() {
		
		$versions = array();
		
		$tags = $this->getTagVersions();
		foreach ($tags as $tag) {
			$versions[$tag] = "tags/".$tag."/";
		}
		
		$branches = $this->getBranchVersions();
		foreach ($branches as $branch) {
			$versions[$branch.'.9999999'] = "branches/".$branch."/";
		}
		
		uksort($versions, "version_compare");
		
		$versions = array_reverse($versions, true);
		
		$finalVersions = array();
		foreach ($versions as $version=>$value) {
			$finalVersions[str_replace('.9999999', '-dev', $version)] = $value;
		}
		return $finalVersions;
	}
}

?>