<?php
namespace Mouf\Controllers;
				
use Mouf\Utils\Common\ConditionInterface\FalseCondition;

use Mouf\Html\Utils\WebLibraryManager\WebLibrary;

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
 * This is the controller in charge of managing most pages of the application.
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
	 * This object represents the block containing the logo of the package.
	 *
	 * @var HtmlBlock
	 */
	public $logoHolder;
	
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
	 * 
	 * @var PackageExplorer
	 */
	public $packageExporer;
	
	/**
	 * The path to the repository
	 * 
	 * @var string
	 */
	public $repositoryPath;
	
	private $forbiddenExtension=array("php", "PHP");
	
	private $readMeFiles=array('index.md', 'README.md', 'readme.md', 'README.html', 'README.txt', 'README', 'index.html', 'index.htm');
	
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
		
		$this->addPackagesMenu();
		
		if (!file_exists($targetDir)) {
			$this->versionsMenuItem->setDisplayCondition(new FalseCondition());
			$this->http404Handler->pageNotFound("The project $owner/$projectname does not exist.");
			return;
		}
		
		$path = $this->getPath($rootUrl);
		
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
		
		
		$parsedComposerJson = $packageVersion->getComposerJson();
		
		$packageName = $parsedComposerJson['name'];
		$this->template->setTitle($packageName);
		$this->navBar->title = $packageName.' ('.$packageVersion->getVersionDisplayName().')';
		$this->navBar->titleLink = $packageName; 
		
		$fileName = $targetDir.DIRECTORY_SEPARATOR.$path;
		if (!file_exists($fileName)) {
			$this->addMenu($parsedComposerJson, $targetDir, $rootUrl, $packageVersion);
			$this->http404Handler->pageNotFound("");
			return;
		}
		
		// Let's add the icon, if any
		if (isset($parsedComposerJson['extra']['mouf']['logo'])) {
			$logoUrl = $parsedComposerJson['extra']['mouf']['logo'];
			$imgUrl = ROOT_URL.$owner.'/'.$projectname.'/'.$logoUrl;
			
			$this->logoHolder->addText('<img src="'.htmlentities($imgUrl, ENT_QUOTES, 'UTF-8').'" alt="" />');
		}
		
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
			$this->addMenu($parsedComposerJson, $targetDir, $rootUrl, $packageVersion);
			if ($path) {
				$this->http404Handler->pageNotFound("Sorry, this project does not seem to have documentation");
				return;
			} else {
				$this->content->addText("<h4>".$parsedComposerJson['name']."</h4>");
				if (isset($parsedComposerJson['description'])) {
					$this->content->addText("<p>".htmlentities($parsedComposerJson['description'], ENT_QUOTES, 'UTF-8')."</p>");
				}
				$this->content->addText('<div class="alert">Sorry, this project does not seem to have any documentation. Please bang the head of the developers until a proper README is added to this package!</div>');
				$this->template->toHtml();
				return;
			}
		}
		
		
		
		$pathinfo = pathinfo($fileName);
		$extension = isset($pathinfo['extension'])?$pathinfo['extension']:null;
		if (in_array($extension, $this->forbiddenExtension)) {
			$this->http404Handler->pageNotFound("Cannot view files with this extension.");
			return;
		}
		
		if ($extension == "html" || $extension == "md") {
			$previousNextButtonsHtml = $this->getPreviousNextButtons($path, $parsedComposerJson, $rootUrl, $targetDir);
			$this->addMenu($parsedComposerJson, $targetDir, $rootUrl, $packageVersion);
		
			$fileStr = file_get_contents($fileName);
		
			if ($extension = "md") {
				// The line below is a workaround around a bug in markdown implementation.
				$forceautoload = new \ReflectionClass('\\Michelf\\Markdown');
				
				$markdownParser = new MarkdownExtra();
				//$markdownParser = new MarkdownParser();

				$fileStr = str_replace('```', '~~~', $fileStr);
				
				// Let's parse and transform markdown format in HTML
				$fileStr = $markdownParser->transform($fileStr);
				
				$fileStr = $previousNextButtonsHtml.$fileStr.$previousNextButtonsHtml;
				
				$this->content->addText('<div class="staticwebsite">'.$fileStr.'</div>');
				$this->template->toHtml();
			} else {
				$bodyStart = strpos($fileStr, "<body");
				if ($bodyStart === false) {
					$fileStr = $previousNextButtonsHtml.$fileStr.$previousNextButtonsHtml;
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
			
					$body = $previousNextButtonsHtml.$body.$previousNextButtonsHtml;
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
	protected function addMenu($composerJson, $targetDir, $rootUrl, $packageVersion) {
		
		$docPages = $this->getDocPages($composerJson, $targetDir);

		$documentationMenuMainItem = new MenuItem("Documentation");
		$documentationMenuMainItem->setCssClass('nav-header');
		$this->documentationMenu->addChild($documentationMenuMainItem);
		
		$this->fillMenu($this->documentationMenu, $docPages, $rootUrl);
		
		$aboutMenuItem = new MenuItem("About");
		$aboutMenuItem->setCssClass('nav-header');
		$this->documentationMenu->addChild($aboutMenuItem);
		
		if (isset($composerJson['homepage'])) {
			$homePageMenuItem = new MenuItem("Home page");
			$homePageMenuItem->setUrl($composerJson['homepage']);
			$this->documentationMenu->addChild($homePageMenuItem);
			
			//if (strpos($composerJson['homepage'], "github.com/") !== false) {
			//	\Mouf::getBlock_header()->addText('<a href="https://github.com/you"><img style="position: absolute; top: 42px; right: 0; border: 0;" src="https://s3.amazonaws.com/github/ribbons/forkme_right_darkblue_121621.png" alt="Fork me on GitHub"></a>');
			//}
		}
		
		if (isset($composerJson['support']['issues'])) {
			$menuItem = new MenuItem("Issues");
			$menuItem->setUrl($composerJson['support']['issues']);
			$this->documentationMenu->addChild($menuItem);
		}
		
		if (isset($composerJson['support']['forum'])) {
			$menuItem = new MenuItem("Forum");
			$menuItem->setUrl($composerJson['support']['forum']);
			$this->documentationMenu->addChild($menuItem);
		}
		
		if (isset($composerJson['support']['wiki'])) {
			$menuItem = new MenuItem("Wiki");
			$menuItem->setUrl($composerJson['support']['wiki']);
			$this->documentationMenu->addChild($menuItem);
		}
		
		if (isset($composerJson['support']['source'])) {
			$menuItem = new MenuItem("Sources");
			$menuItem->setUrl($composerJson['support']['source']);
			$this->documentationMenu->addChild($menuItem);
		}
		
		$menuItem = new MenuItem("Packagist");
		$menuItem->setUrl("https://packagist.org/packages/".$composerJson['name']);
		$this->documentationMenu->addChild($menuItem);
		
		$requires = $this->packageExporer->getRequires($packageVersion);
		
		if (!empty($requires)) {
			$menuItem = new MenuItem("Depends on");
			$menuItem->setCssClass('nav-header');
			$this->documentationMenu->addChild($menuItem);
			
			foreach ($requires as $fullName=>$require) {
				$menuItem = new MenuItem($fullName);
				$menuItem->setUrl(ROOT_URL.$fullName.'/');
				$this->documentationMenu->addChild($menuItem);
			}
		}
		
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
		
		
		foreach ($this->readMeFiles as $readme) {
			if (file_exists($packagePath.$readme)) {
				$docArray[] = array("title"=> "Read me",
					"url"=>$readme
				);
				break;
			}
		}
		
		if (isset($composerJson['extra']['mouf']['doc']) && is_array($composerJson['extra']['mouf']['doc'])) {
			$docArray = array_merge($docArray, $composerJson['extra']['mouf']['doc']);
		}
		return $docArray;
	}
	
	/**
	 * Fills the menu with the documentation.
	 * This function can be called recursively.
	 * 
	 * @param Menu|MenuItem $menu
	 * @param array $docPages
	 * @param unknown $rootUrl
	 */
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
			
				
			if (isset($docPage['children'])) {
				$this->fillMenu($menuItem, $docPage['children'], $rootUrl);
			}
			$children[] = $menuItem;
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
	public function addPackagesMenu() {
		$packageExplorer = new PackageExplorer($this->repositoryPath);
		$packages = $packageExplorer->getPackages();
		
		$tree = array();
		
		// Let's fill the menu with the packages.
		foreach ($packages as $owner=>$packageList) {
			
			// Let's ignore the owner (because it's always the same)
			foreach ($packageList as $package) {
				$items = explode('.', $package);
				
				// If the package does not contain a ".", let's store it in the "others" category
				if (count($items) == 1) {
					$items = array("Misc", $items[0]);
				}
								
				$node =& $tree;
				foreach ($items as $str) {
					if (!isset($node["children"][$str])) {
						$node["children"][$str] = array();
					}
					$node =& $node["children"][$str];
				}
				$node['package'] = $package;
			}
		}
		
		$this->walkMenuTree($tree, $owner.'/', $this->packagesMenuItem);
	}
	
	private function walkMenuTree($node, $path, MenuItem $parentMenuItem) {
		if (isset($node["children"]) && !empty($node["children"])) {
			// If there is a package and there are subpackages in the same name...
			if (isset($node["package"])) {
				$menuItem = new MenuItem();
				$menuItem->setLabel("<em>Main package</em>");
				$menuItem->setUrl(ROOT_URL.$path);
				$parentMenuItem->addMenuItem($menuItem);
			}

			foreach ($node['children'] as $key=>$array) {
				$menuItem = new MenuItem();
				$menuItem->setLabel($key);
				$parentMenuItem->addMenuItem($menuItem);
				$pathTmp = $path.'.'.$key;
				$pathTmp = str_replace(array('/.', '/Misc'), '/', $pathTmp);
				$this->walkMenuTree($array, $pathTmp, $menuItem);
			}
		} else {
			$parentMenuItem->setUrl(ROOT_URL.$path);
		}
	}
	
	private function getPreviousNextButtons($path, $parsedComposerJson, $rootUrl, $targetDir) {
		if (isset($parsedComposerJson['extra']['mouf']['doc'])) {
			// TODO: suboptimal, getDocPages is called twice. We should pass $docPages directly in parameter.
			$docPages = $this->getDocPages($parsedComposerJson, $targetDir);
			
			// Let's flatten the doc array (to find previous and next in children or parents.
			$flatDocArray = $this->flattenDocArray($docPages);
			for ($i = 0; $i<count($flatDocArray); $i++) {
				if ($flatDocArray[$i]['url'] == $path) {
					$html = '<div>';
					if ($i > 0) {
						$html .= '<a href="'.$rootUrl.$flatDocArray[$i-1]['url'].'" class="btn btn-small"><i class="icon-chevron-left"></i> '.$flatDocArray[$i-1]['title'].'</a>';
					}
					if ($i < count($flatDocArray) - 1) {
						$html .= '<a href="'.$rootUrl.$flatDocArray[$i+1]['url'].'" class="btn btn-small pull-right">'.$flatDocArray[$i+1]['title'].' <i class="icon-chevron-right"></i></a>';
					}
					$html .= '</div>';
					return $html;
				}
			}
		}
		return "";
	}
	
	private function flattenDocArray(array $docArray) {
		$docs = array();
		foreach ($docArray as $doc) {
			$docs[] = $doc;
			if (isset($doc['children']))
			$docs = array_merge($docs, $this->flattenDocArray($doc['children']));
		}
		return $docs;
	}
}
