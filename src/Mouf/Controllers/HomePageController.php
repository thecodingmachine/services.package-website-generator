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
 * This is the controller in charge of managing the home page.
 * 
 * @Component
 */
class HomePageController extends Controller {
	
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
	
	protected $packages = array();
	protected $userName;
	
	/**
	 * This is the home page of the website
	 * 
	 * @URL /
	 */
	public function index() {
		
		$packageExplorer = new PackageExplorer($this->repositoryPath);
		$packages = $packageExplorer->getPackages();
		
		// Let's fill the menu with the packages.
		foreach ($packages as $owner=>$packageList) {
					
			$this->userName = $owner;
			
			foreach ($packageList as $packageName) {
				$this->packages[] = $this->packageExporer->getPackage($owner.'/'.$packageName);
			}
			
		}
		
		$this->versionsMenuItem->setDisplayCondition(new FalseCondition());
		$this->packagesMenuItem->setLabel('Packages');
		// Note: this is bad:
		\Mouf::getRootController()->addPackagesMenu();
		
		$title = $this->userName.'\'s packages';
		$this->template->setTitle($title);
		$this->navBar->title = $title;
		$this->navBar->titleLink = ROOT_URL;
		
		$this->content->addFile(__DIR__."/../../views/homepage.php", $this);
		$this->template->toHtml();
	}
	
}
