<?php 
namespace Mouf\Services;

/**
 * A service to find all the packages installed.
 * 
 * @author David Negrier
 */
class PackageExplorer {
	
	private $repositoryDir;
	
	/**
	 * 
	 * @var array<owner, array<>>
	 */
	private $packages;
	
	public function __construct($repositoryDir) {
		$this->repositoryDir = $repositoryDir;
	}

	/**
	 * Returns the list of all branch versions for the package in directory $dir
	 */
	public function getPackages() {
		if (!$this->packages) {
			foreach (glob($this->repositoryDir.'/*', GLOB_ONLYDIR) as $ownerDir) {
				$owner = basename($ownerDir);
				foreach (glob($ownerDir.'/*', GLOB_ONLYDIR) as $packageDir) {
					$this->packages[$owner][] = basename($packageDir);
				}	
			}
		}
		return $this->packages;
	}
	
}

?>