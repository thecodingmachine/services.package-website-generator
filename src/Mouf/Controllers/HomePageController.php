<?php

namespace Mouf\Controllers;

use Mouf\Services\SectionBuilder;
use Mouf\Widgets\PageDisplayer;
use Mouf\Utils\Common\ConditionInterface\FalseCondition;
use Mouf\Services\PackageExplorer;
use Mouf\Html\Template\Menus\BootstrapNavBar;
use Mouf\Html\Widgets\Menu\Menu;
use Mouf\Html\Widgets\Menu\MenuItem;
use Mouf\Html\HtmlElement\HtmlBlock;
use Mouf\Html\Template\TemplateInterface;
use Mouf\Mvc\Splash\Controllers\Controller;

/**
 * This is the controller in charge of managing the home page.
 * 
 * @Component
 */
class HomePageController extends Controller
{
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
     * The Twig environment (used to render Twig templates).
     *
     * @var \Twig_Environment
     */
    public $twig;

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
     * @var PackageExplorer
     */
    public $packageExporer;

    /**
     * The path to the repository.
     * 
     * @var string
     */
    public $repositoryPath;

    /**
     * @var SectionBuilder
     */
    public $sectionBuilder;

    /**
     * @var PageDisplayer
     */
    public $pageDisplayer;

    protected $sections = array();
    protected $packages = array();
    protected $userName;

    /**
     * This is the home page of the website.
     * 
     * @URL /
     */
    public function index()
    {
        $packageExplorer = new PackageExplorer($this->repositoryPath);
        $packages = $packageExplorer->getPackages();

        // Let's fill the menu with the packages.
        foreach ($packages as $owner => $packageList) {
            $this->userName = $owner;
        }
        $this->versionsMenuItem->setDisplayCondition(new FalseCondition());
        $this->packagesMenuItem->setLabel('Packages');

        $this->sections = $this->sectionBuilder->buildSections($packageExplorer);
        $this->pageDisplayer->setElementsToDisplay($this->sections);
        $this->pageDisplayer->setContext('description');

        // Note: this is bad:
        \Mouf::getRootController()->addPackagesMenu();

        $title = $this->userName.'\'s packages';
        $this->template->setTitle($title);
        $this->navBar->title = $title;
        $this->navBar->titleLink = '';
        $this->content->addHtmlElement($this->pageDisplayer);
        $this->template->toHtml();
    }
}
