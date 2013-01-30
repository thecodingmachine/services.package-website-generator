<?php 
namespace Mouf\Services;

use Composer\Command\CreateProjectCommand;

use Composer\Factory;

use Mouf\Composer\MoufJsComposerIO;

use Mouf\Composer\OnPackageFoundInterface;

use Composer\Package\PackageInterface;
use Mouf\Composer\ComposerService;


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
		$this->io = new MoufJsComposerIO();
		$this->composer = Factory::create($this->io);
	}
	
	/**
	 * Returns the list of minimal packages whose owner is "$owner"
	 *  
	 * @param string $owner
	 * @return array
	 */
	public function findPackagesListByOwner($owner) {
		$minimalPackages = $this->composer->getRepositoryManager()->getRepositories()[0]->getMinimalPackages();
		$result = array();
		foreach ($minimalPackages as $minimalPackage) {
			if (strpos($minimalPackage['name'], $owner.'/') === 0) {
				$result[] = $minimalPackage;
			}
		}
		return $result;
	}
	
	public function run($owner) {
		$minimalPackages = $this->findPackagesListByOwner($owner);
		
		foreach ($minimalPackages as $minimalPackage) {
			$this->installOrUpdate($minimalPackage['name'], $minimalPackage['version']);
		}
	}

	/**
	 * Installs or update a package in the repository.
	 * 
	 * @param string $name
	 * @param string $version
	 */
	public function installOrUpdate($name, $version) {
		$prettyVersion = str_replace(".9999999", "", $version);
		$packageDir = $this->packagesBaseDirectory.'/'.$name.'/'.$prettyVersion;
		if (file_exists($packageDir)) {
			// Ok, if this is not a tag, but a branch, there is a .git file that we can update.
			if (file_exists($packageDir.'/.git')) {
				echo "Running git pull on ".$name.' - '.$prettyVersion."\n";
				exec('cd '.escapeshellarg($packageDir).';pwd;git pull');
			} else {
				echo "Not updating tag ".$name.' - '.$prettyVersion."\n";
			}
		} else {
			if (!file_exists($this->packagesBaseDirectory.'/'.$name)) {
				mkdir($this->packagesBaseDirectory.'/'.$name, 0777, true);
			}
			
			try {
				$createProjectCommand = new CreateProjectCommand();
				$createProjectCommand->installProject($this->io, $name, $packageDir, $version, 
						'dev', false, false, false,
						null, false, false, true);
			} catch (\Exception $e) {
				echo "EXCEPTION RAISED! ".$e->getMessage()."\n";
				echo $e->getTraceAsString()."\n";
			}
		}
		
	}
}