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
	
	protected $starredPackages = array();
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
		
		$starredPackagesList = explode(";", STARRED_PACKAGES);
		
		// Let's fill the menu with the packages.
		foreach ($packages as $owner=>$packageList) {
					
			$this->userName = $owner;
			
			foreach ($packageList as $packageName) {
				$package = $this->packageExporer->getPackage($owner.'/'.$packageName);
				$pos = array_search($packageName, $starredPackagesList);
				if ($pos !== false) {
					$this->starredPackages[$pos] = $package;
				} else {
					$this->packages[] = $package;
				}
			}
		}
		
		ksort($this->starredPackages);
		
		$this->versionsMenuItem->setDisplayCondition(new FalseCondition());
		$this->packagesMenuItem->setLabel('Packages');
		// Note: this is bad:
		\Mouf::getRootController()->addPackagesMenu();
		
		$title = $this->userName.'\'s packages';
		$this->template->setTitle($title);
		$this->navBar->title = $title;
		$this->navBar->titleLink = "";
		
		$this->content->addFile(__DIR__."/../../views/homepage.php", $this);
		$this->template->toHtml();
	}
	
	
	protected function displayPackage(Package $package) {
		$packageName = $package->getName();
		$packageVersion = $package->getPackageVersion($package->getLatest());
		$composerJson = $packageVersion->getComposerJson();
		if (isset($composerJson['extra']['mouf']['logo'])) {
			$imgUrl = ROOT_URL.$this->userName.'/'.$packageName."/".$composerJson['extra']['mouf']['logo'];
		} else {
			$imgUrl = ROOT_URL.'src/views/images/package.png';
		}
		?>
		<div class="media">
	    	<a class="pull-left" href="#">
	    		<img class="media-object" src="<?php echo $imgUrl ?>">
	    	</a>
	    	<div class="media-body">
	    		<h4 class="media-heading">
	    			<?php echo "<a href='".ROOT_URL.$this->userName.'/'.$packageName."/' style='margin-right:5px; margin-bottom:5px'>".$packageName." <small>".$packageVersion->getVersionDisplayName()."</small></a>"; ?>		
	    		</h4>
	    		<?php
	    		
	    		if (isset($composerJson['description'])) {
	    			echo htmlentities($composerJson['description'], ENT_QUOTES, 'UTF-8'); 
				} ?>
	     
	    
	    	</div>
	    </div>
	<?php 
	}
}
