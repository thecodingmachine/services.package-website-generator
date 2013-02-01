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
	
	/**
	 * 
	 * @var array<$fullName, Package>
	 */
	private $packagesByFullName;
	
	
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
	
	/**
	 * @return Package
	 */
	public function getPackage($fullName) {
		if (!isset($this->packagesByFullName[$fullName])) {
			$this->packagesByFullName[$fullName] = new Package($this->repositoryDir.'/'.$fullName);
		}
		return $this->packagesByFullName[$fullName];
	}
	
	/**
	 * Returns the list of Package objects that are a direct dependency of the $packageVersion and are
	 * also present in the repository.
	 * 
	 * TODO: improve and return PackageVersions instead of just packages.
	 * 
	 * @param PackageVersion $packageVersion
	 * @return Package[]
	 */
	public function getRequires(PackageVersion $packageVersion) {
		$composerJson = $packageVersion->getComposerJson();
		$dependencies = array();
		if (isset($composerJson['require'])) {
			foreach ($composerJson['require'] as $packageName=>$version) {
				if (file_exists($this->repositoryDir.'/'.$packageName)) {
					$dependencies[$packageName] = $this->getPackage($packageName);
				}
			}
		}
		return $dependencies;
	}
}

?>