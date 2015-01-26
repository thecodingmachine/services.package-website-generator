<?php 
namespace Mouf\Services;

use Composer\Command\CreateProjectCommand;

use Composer\Factory;

use Mouf\Composer\MoufErrorLogComposerIO;

use Mouf\Composer\OnPackageFoundInterface;

use Composer\Package\PackageInterface;
use Mouf\Composer\ComposerService;
use Composer\Repository\RepositoryInterface;
use Packagist\Api\Result\Package\Source;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Mouf\Widgets\Package;

/**
 * A service that downloads/updates packages from Packagist.
 *
 * @author David Negrier
 */
class PackagesInstaller {
	
	private $packagesBaseDirectory;
	private $composer;
	private $io;
	
	/**
	 * 
	 * @param string $packagesBaseDirectory The base directory, with no trailing slash.
	 */
	public function __construct($packagesBaseDirectory) {
		$this->packagesBaseDirectory = $packagesBaseDirectory;
		$this->packagistClient = new \Packagist\Api\Client();
		$this->initComposer();
	}
	
	private function initComposer() {
		$loader = new \Composer\Autoload\ClassLoader();
		
		$map = require 'phar://'.__DIR__.'/../../../composer.phar/vendor/composer/autoload_namespaces.php';
		foreach ($map as $namespace => $path) {
			$loader->add($namespace, $path);
		}
		
		$classMap = require 'phar://'.__DIR__.'/../../../composer.phar/vendor/composer/autoload_classmap.php';
		if ($classMap) {
			$loader->addClassMap($classMap);
		}
		
		$loader->register();
		
		chdir(__DIR__."/../../..");
		$this->io = new MoufErrorLogComposerIO();
		$this->composer = Factory::create($this->io);
	}
	
	/**
	 * Returns the list of minimal packages whose owner is "$owner"
	 *  
	 * @param string $owner
	 * @return array<string> List of packages names (no version)
	 */
	public function findPackagesListByOwner($owner, $verbose) {
		
		$results = array();
		foreach ($this->packagistClient->search($owner.'/') as $result) {
			$results[] = $result->getName();
		}
		return $results;
		
		/*$repos = $this->composer->getRepositoryManager()->getRepositories();
		

		var_dump($repos[0]->search('mouf/', RepositoryInterface::SEARCH_FULLTEXT));exit;
		
		$foundPackagesNames = $repos[0]->search($owner.'/', RepositoryInterface::SEARCH_NAME);
		foreach ($foundPackagesNames as $package) {
			$name = $package['name'];
			
			if (strpos($minimalPackage['name'], $owner.'/') === 0) {
				$result[] = $minimalPackage;
			}
		}
		
		//var_dump($repos[0]->getMinimalPackages());
		var_dump($repos[0]->search('mouf/', RepositoryInterface::SEARCH_NAME));
		
		$minimalPackages = $repos[0]->getMinimalPackages();
		$result = array();
		foreach ($minimalPackages as $minimalPackage) {
			if (strpos($minimalPackage['name'], $owner.'/') === 0) {
				$result[] = $minimalPackage;
			}
		}
		return $result;*/
	}
	
	public function run($owner, $verbose) {
		
		$packagesNames = $this->findPackagesListByOwner($owner, $verbose);
		
		
		if ($verbose) {
			error_log("Found ".count($packagesNames)." packages.");
		}
		
		foreach ($packagesNames as $packageName) {
			try {
				$versions = $this->packagistClient->get($packageName)->getVersions();
			} catch (ClientErrorResponseException $e) {
				if ($e->getCode() == 404) {
					// Let's ignore, it might only be a problem of virtual package that is returned but does not exist.
					if ($verbose) {
						error_log("Could not find '".$packageName."'. It might be a virtual package.");
						continue;
					} else {
						throw $e;
					}
				}
			}
			foreach ($versions as $key=>$version) {
				/* @var $version \Packagist\Api\Result\Package\Version */
				if ($verbose) {
					error_log("Installing or updating ".$packageName." - ".$version->getVersionNormalized());
				}
				$this->installOrUpdate($packageName, $version->getVersionNormalized(), $version->getSource());
			}
			//$this->installOrUpdate($minimalPackage['name'], $minimalPackage['version']);
		}
	}

	/**
	 * Installs or update a package in the repository.
	 * 
	 * @param string $name
	 * @param string $version
	 */
	public function installOrUpdate($name, $version, Source $source) {
		$prettyVersion = str_replace(".9999999", "", $version);
		$packageDir = $this->packagesBaseDirectory.'/'.$name.'/'.$prettyVersion;
		if (file_exists($packageDir)) {
			// Ok, if this is not a tag, but a branch, there is a .git file that we can update.
			if (file_exists($packageDir.'/.git')) {
				//error_log("Running git pull on ".$name.' - '.$prettyVersion);
				exec('cd '.escapeshellarg($packageDir).';pwd;git pull');
			} else {
				//error_log("Not updating tag ".$name.' - '.$prettyVersion);
			}
		} else {
			if (!file_exists($this->packagesBaseDirectory.'/'.$name)) {
				mkdir($this->packagesBaseDirectory.'/'.$name, 0777, true);
			}
			
			try {
				$config = Factory::createConfig();
				$createProjectCommand = new CreateProjectCommand();
				$createProjectCommand->installProject($this->io, $config, $name, $packageDir, $version, 
						'dev', false, false, false,
						null, false, false, true);
			} catch (\Exception $e) {
				echo "EXCEPTION RAISED! ".$e->getMessage()."\n";
				echo $e->getTraceAsString()."\n";
				error_log("EXCEPTION RAISED! ".$e->getMessage()."\n");
				error_log($e->getTraceAsString()."\n");
			}
		}
		file_put_contents($packageDir.'/.sourceUrl', $source->getUrl());
		/*echo "SOURCE URL: ".$source->getUrl()."\n";
		echo "SOURCE TYPE: ".$source->getType()."\n";
		echo "SOURCE REFERENCE: ".$source->getReference()."\n";
		exit;*/
	}
}
