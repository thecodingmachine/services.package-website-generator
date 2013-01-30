<?php
namespace Mouf\Controllers;
				
use Mouf\Services\PackageExplorer;

use Mouf\Html\Utils\WebLibraryManager\InlineWebLibrary;

use Michelf\MarkdownExtra;

use Mouf\Html\Template\Menus\BootstrapNavBar;

use Mouf\Html\Widgets\Menu\Menu;

use Mouf\Html\Widgets\Menu\MenuItem;

use Mouf\Mvc\Splash\Controllers\Http404HandlerInterface;

use Mouf\Mvc\Splash\Controllers\HttpErrorsController;

use Mouf\Html\HtmlElement\HtmlBlock;
use Mouf\Html\Template\TemplateInterface;
use Mouf\Mvc\Splash\Controllers\Controller;
use Mouf\Services\Package;
use Mouf\Services\PackageVersion;				

/**
 * This is the controller in charge of managing the first page of the application.
 * 
 * @Component
 */
class RootController extends Controller {
	
	/**
	 * The template used by the controller.
	 *
	 * @var TemplateInterface
	 */
	public $template;
		
	/**
	 * This object represents the block of main content of the web page.
	 *
	 * @var HtmlBlock
	 */
	public $content;
	
	/**
	 * The 404 errors handler.
	 * 
	 * @var Http404HandlerInterface
	 */
	public $http404Handler;
	
	/**
	 * The documentation menu.
	 *
	 * @var Menu
	 */
	public $documentationMenu;
	
	/**
	 * The navbar.
	 *
	 * @var BootstrapNavBar
	 */
	public $navBar;
	
	/**
	 * The versions menu item.
	 *
	 * @var MenuItem
	 */
	public $versionsMenuItem;
	
	/**
	 * The other packages menu item.
	 *
	 * @var MenuItem
	 */
	public $packagesMenuItem;
	
	/**
	 * The path to the repository
	 * 
	 * @var string
	 */
	public $repositoryPath;
	
	private $forbiddenExtension=array("php", "PHP");
	
	private $readMeFiles=array('README.md', 'readme.md', 'README.html', 'README.txt', 'README', 'index.html', 'index.htm');
	
	/**
	 * There is only one action and it captures all URLs.
	 * The URLs are analyzed by the controller and matched to documents.
	 * 
	 * @URL /{owner}/{projectname}/*
	 */
	public function index($owner, $projectname) {
		$parsedUrl = parse_url($_SERVER['REQUEST_URI']);
		$fullPath = $parsedUrl['path'];
		// If the URL is at the root of the project, but without a trailing slash, let's add one.
		if (ROOT_URL.$owner.'/'.$projectname == $fullPath) {
			header('Location: '.ROOT_URL.$owner.'/'.$projectname.'/');
			return;
		}
		
		$packageDir = $this->getPackageDir($owner, $projectname);
		
		$package = new Package($packageDir);
		$latestVersion = $package->getLatest();
		
		$rootUrl = ROOT_URL.$owner.'/'.$projectname.'/';
		
		$packageVersion = $package->getPackageVersion($latestVersion, false);
		
		$this->printPage($packageVersion, $rootUrl, $owner, $projectname);
	}
	
	/**
	 * Action for branches whose version is known.
	 * The URLs are analyzed by the controller and matched to documents.
	 *
	 * @URL /{owner}/{projectname}/version/{version}/*
	 */
	public function indexVersion($owner, $projectname, $version) {
		$packageDir = $this->getPackageDir($owner, $projectname);
		
		if (empty($version) || !file_exists($packageDir.'/'.$version)) {
			$this->http404Handler->pageNotFound("Invalid version");
			return;
		}
		
		$package = new Package($packageDir);
		$packageVersion = $package->getPackageVersion($version, false);
		

		$rootUrl = ROOT_URL.$owner.'/'.$projectname.'/version/'.$version.'/';
		$path = $this->getPath($rootUrl);

		$inlineWebLibrary = new InlineWebLibrary();
		$inlineWebLibrary->setAdditionalElementFromText('<link rel="canonical" href="http://'.$_SERVER['HTTP_HOST'].ROOT_URL.$path.'"/>');
		$this->template->getWebLibraryManager()->addLibrary($inlineWebLibrary);
		
		$this->printPage($packageVersion, $rootUrl, $owner, $projectname);
	}
	
	/**
	 * Prints the page.
	 * 
	 * @param PackageVersion $packageVersion
	 * @param string $rootUrl The ROOT_URL (including the version/{version}/ part)
	 * @return void
	 */
	private function printPage(PackageVersion $packageVersion, $rootUrl, $owner, $projectname) {
		
		$targetDir = $packageVersion->getDirectory();
		
		if (!file_exists($targetDir)) {
			$this->http404Handler->pageNotFound("The project $owner/$projectname does not exist.");
			return;
		}
		
		$path = $this->getPath($rootUrl);
		
		$fileName = $targetDir.DIRECTORY_SEPARATOR.$path;
		if (!file_exists($fileName)) {
			$this->http404Handler->pageNotFound("");
			return;
		}
		
		$versions = $packageVersion->getPackage()->getVersions();
		uksort($versions, "version_compare");
		$versions = array_reverse($versions, true);
		
		$menuItem = new MenuItem();
		$menuItem->setLabel('Latest');
		$menuItem->setUrl(ROOT_URL.$owner.'/'.$projectname.'/');
		$this->versionsMenuItem->addMenuItem($menuItem);
		
		// Let's fill the menu with the versions.
		foreach ($versions as $version) {
			$menuItem = new MenuItem();
			$menuItem->setLabel($version);
			$menuItem->setUrl(ROOT_URL.$owner.'/'.$projectname.'/version/'.$version.'/');
			$this->versionsMenuItem->addMenuItem($menuItem);
		}
		
		$this->addPackagesMenu();
		
		$composerFile = $targetDir.'/composer.json';
		$parsedComposerJson = json_decode(file_get_contents($composerFile), true);
		
		$packageName = $parsedComposerJson['name'];
		$this->template->setTitle($packageName);
		$this->navBar->title = $packageName.' ('.$packageVersion->getVersionDisplayName().')';
		
		if (is_dir($fileName)) {
			// This is not a file but a directory.
			// Let's look for a README in it.
				
			$dir = rtrim($fileName, '/\\');
				
			// Let's try to find a README
			foreach ($this->readMeFiles as $readme) {
				if (file_exists($dir.DIRECTORY_SEPARATOR.$readme)) {
					header('Location: '.$rootUrl.$readme);
					return;
				}
			}
			// If no readme found, let's go on a 404.
			$this->http404Handler->pageNotFound("Sorry, this project does not seem to have documentation");
			return;
		}
		
		
		
		$pathinfo = pathinfo($fileName);
		$extension = isset($pathinfo['extension'])?$pathinfo['extension']:null;
		if (in_array($extension, $this->forbiddenExtension)) {
			$this->http404Handler->pageNotFound("Cannot view files with this extension.");
			return;
		}
		
		if ($extension == "html" || $extension == "md") {
			$this->addMenu($parsedComposerJson, $targetDir, $rootUrl);
		
			$fileStr = file_get_contents($fileName);
		
			if ($extension = "md") {
				// The line below is a workaround around a bug in markdown implementation.
				$forceautoload = new \ReflectionClass('\\Michelf\\Markdown');
				
				$markdownParser = new MarkdownExtra();
				//$markdownParser = new MarkdownParser();

				$fileStr = str_replace('```', '~~~', $fileStr);
				
				// Let's parse and transform markdown format in HTML
				$fileStr = $markdownParser->transform($fileStr);
				
				$this->content->addText('<div class="staticwebsite">'.$fileStr.'</div>');
				$this->template->toHtml();
			} else {
				$bodyStart = strpos($fileStr, "<body");
				if ($bodyStart === false) {
					$this->content->addText('<div class="staticwebsite">'.$fileStr.'</div>');
					$this->template->toHtml();
				} else {
					$bodyOpenTagEnd = strpos($fileStr, ">", $bodyStart);
			
					$partBody = substr($fileStr, $bodyOpenTagEnd+1);
			
					$bodyEndTag = strpos($partBody, "</body>");
					if ($bodyEndTag === false) {
						return '<div class="staticwebsite">'.$partBody.'</div>';
					}
					$body = substr($partBody, 0, $bodyEndTag);
			
					$this->content->addText('<div class="staticwebsite">'.$body.'</div>');
					$this->template->toHtml();
			}
			}
		} else {
			readfile($fileName);
			exit;
		}
		
	}
	
	/**
	 * Returns the path part of the URL, after the root URL.
	 * Returns false if we are at the root of the app.
	 */
	private function getPath($rootUrl) {
		$parsedUrl = parse_url($_SERVER['REQUEST_URI']);
		
		$fullPath = $parsedUrl['path'];
		if (strpos($fullPath, $rootUrl) !== 0) {
			throw new \Exception("Error: the path does not match the root URL. This should never happen.");
		}
		
		return substr($fullPath, strlen($rootUrl));
	}
	
	
	
	/**
	 * Creates the menu element on the left that contains all the documentation items.
	 */
	protected function addMenu($composerJson, $targetDir, $rootUrl) {
		
		$docPages = $this->getDocPages($composerJson, $targetDir);

		/*$documentationMenuMainItem = new MenuItem("Documentation");
		$this->fillMenu($documentationMenuMainItem, $docPages);
		$this->documentationMenu->addChild($documentationMenuMainItem);*/
		$this->fillMenu($this->documentationMenu, $docPages, $rootUrl);
	}
	
	/**
	 * Returns an array of doc pages with the format:
	 * 	[
	 *   		{
	 *   			"title": "Using FINE",
	 *   			"url": "using_fine.html"
	 *   		},
	 *   		{
	 *   			"title": "Date functions",
	 *   			"url": "date_functions.html"
	 *   		},
	 *   		{
	 *   			"title": "Currency functions",
	 *   			"url": "currency_functions.html"
	 *   		}
	 *   	]
	 *
	 */
	protected function getDocPages($composerJson, $targetDir) {
	
		$docArray = array();
	
		// Let's find if there is a README file.
		$packagePath = $targetDir."/";
		if (file_exists($packagePath."README.md")) {
			$docArray[] = array("title"=> "Read me",
					"url"=>"README.md"
			);
		}
		if (file_exists($packagePath."README")) {
			$docArray[] = array("title"=> "Read me",
					"url"=>"README"
			);
		}
		if (file_exists($packagePath."README.html")) {
			$docArray[] = array("title"=> "Read me",
					"url"=>"README.html"
			);
		}
		if (file_exists($packagePath."README.txt")) {
			$docArray[] = array("title"=> "Read me",
					"url"=>"README.txt"
			);
		}
		
		if (isset($composerJson['extra']['mouf']['doc']) && is_array($composerJson['extra']['mouf']['doc'])) {
			$docArray = array_merge($docArray, $composerJson['extra']['mouf']['doc']);
		}
		return $docArray;
	}
	
	private function fillMenu($menu, array $docPages, $rootUrl) {
		$children = array();
		foreach ($docPages as $docPage) {
			/* @var $docPage MoufDocumentationPageDescriptor */
				
			if (!isset($docPage['title'])) {
				continue;
			}
				
			$menuItem = new MenuItem();
			$menuItem->setLabel($docPage['title']);
			if (isset($docPage['url'])) {
				$menuItem->setUrl($rootUrl.$docPage['url']);
			}
			$children[] = $menuItem;
				
			if (isset($docPage['children'])) {
				$this->fillMenu($menuItem, $docPage['children'], $rootUrl);
			}
		}
		$menu->setChildren($children);
	}
	
	/**
	 * Returns the directory for the main package.
	 */
	private function getPackageDir($owner, $projectName) {
		return $this->repositoryPath.'/'.$owner.'/'.$projectName;
	}
	
	/**
	 * Adds the packages menu to the menu.
	 */
	private function addPackagesMenu() {
		$packageExplorer = new PackageExplorer($this->repositoryPath);
		$packages = $packageExplorer->getPackages();
		
		$tree = array();
		
		// Let's fill the menu with the versions.
		foreach ($packages as $owner=>$packageList) {
			// Let's ignore the owner (because it's always the same)
			foreach ($packageList as $package) {
				$items = explode('.', $package);
				$node =& $tree;
				foreach ($items as $str) {
					if (!isset($node[$str])) {
						$node[$str] = array();
					}
					$node =& $node[$str];
				}
				
				/*
				$menuItem = new MenuItem();
				$menuItem->setLabel($owner.'/'.$package);
				$menuItem->setUrl(ROOT_URL.$owner.'/'.$package.'/');
				$this->packagesMenuItem->addMenuItem($menuItem);*/
			}
		}
		
		$this->walkMenuTree($tree, $owner.'/', $this->packagesMenuItem);
	}
	
	private function walkMenuTree($node, $path, MenuItem $parentMenuItem) {
		if (!empty($node)) {
			foreach ($node as $key=>$array) {
				$menuItem = new MenuItem();
				$menuItem->setLabel($key);
				$parentMenuItem->addMenuItem($menuItem);
				$pathTmp = $path.'.'.$key;
				$pathTmp = str_replace('/.', '/', $pathTmp);
				$this->walkMenuTree($array, $pathTmp, $menuItem);
			}
		} else {
			$parentMenuItem->setUrl(ROOT_URL.$path);
		}
	}
	
}