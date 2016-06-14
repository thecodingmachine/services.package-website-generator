<?php 
namespace Mouf\Services;

use Composer\Command\CreateProjectCommand;

use Composer\Factory;

use Composer\IO\IOInterface;
use Mouf\Composer\MoufErrorLogComposerIO;

use Mouf\Composer\OnPackageFoundInterface;

use Composer\Package\PackageInterface;
use Mouf\Composer\ComposerService;
use Composer\Repository\RepositoryInterface;
use Packagist\Api\Result\Package\Source;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Mouf\Widgets\Package;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

/**
 * A service that downloads/updates packages from Packagist.
 *
 * @author David Negrier
 */
class PackagesInstaller {
	
	private $packagesBaseDirectory;

	/**
	 * 
	 * @param string $packagesBaseDirectory The base directory, with no trailing slash.
	 */
	public function __construct($packagesBaseDirectory) {
		$this->packagesBaseDirectory = $packagesBaseDirectory;
		$this->packagistClient = new \Packagist\Api\Client();
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
	}
	
	public function run($owner, IOInterface $io) {
		
		$packagesNames = $this->findPackagesListByOwner($owner, $io->isVerbose());
		
		
		if ($io->isVerbose()) {
			$io->write("Found ".count($packagesNames)." packages.");
		}
		
		foreach ($packagesNames as $packageName) {
			try {
				$versions = $this->packagistClient->get($packageName)->getVersions();
			} catch (ClientErrorResponseException $e) {
				if ($e->getCode() == 404) {
					// Let's ignore, it might only be a problem of virtual package that is returned but does not exist.
					if ($io->isVerbose()) {
						$io->write("Could not find '".$packageName."'. It might be a virtual package.");
						continue;
					} else {
						throw $e;
					}
				}
			}
			foreach ($versions as $key=>$version) {
				/* @var $version \Packagist\Api\Result\Package\Version */
				if ($io->isVerbose()) {
					$io->write("Installing or updating ".$packageName." - ".$version->getVersionNormalized());
				}
				$this->installOrUpdate($packageName, $version->getVersionNormalized(), $version->getSource(), $io);
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
	public function installOrUpdate($name, $version, Source $source, IOInterface $io) {
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
				$options = [
					'--prefer-source' => false,
					'--prefer-dist' => false,
					'--keep-vcs' => true,
				];
				$inputDefinition = new InputDefinition(array(
					new InputOption('prefer-source', 's', InputOption::VALUE_REQUIRED),
					new InputOption('prefer-dist', 'd', InputOption::VALUE_REQUIRED),
					new InputOption('keep-vcs', 'k', InputOption::VALUE_REQUIRED),
				));
				$createProjectCommand->installProject($io, $config, new ArrayInput($options, $inputDefinition), $name, $packageDir, $version,
					'dev', false, false, false,
					null, false, false, true, true, true);
			} catch (\InvalidArgumentException $e) {
				// Typically thrown by virtual package.
				if ($io->isVerbose()) {
					$io->writeError("Probable error due to an installation attempt of a virtual package\n");
					$io->writeError($e->getMessage()."\n");
					$io->writeError($e->getTraceAsString()."\n");
				}
			} catch (\Exception $e) {
				$io->writeError("EXCEPTION RAISED! ".get_class($e)." ".$e->getMessage()."\n");
				$io->writeError($e->getTraceAsString()."\n");
				//error_log("EXCEPTION RAISED! ".$e->getMessage()."\n");
				//error_log($e->getTraceAsString()."\n");
			}
		}
		if (file_exists($packageDir)) {
			file_put_contents($packageDir.'/.sourceUrl', $source->getUrl());
		}
		/*echo "SOURCE URL: ".$source->getUrl()."\n";
		echo "SOURCE TYPE: ".$source->getType()."\n";
		echo "SOURCE REFERENCE: ".$source->getReference()."\n";
		exit;*/
	}
}
