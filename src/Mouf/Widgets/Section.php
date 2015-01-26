<?php 
namespace Mouf\Widgets;

use Mouf\Html\HtmlElement\HtmlElementInterface;
use Mouf\Html\Renderer\Renderable;

/**
 * Represents a section (which contains packages)
 * 
 * @author Xavier HUBERTY
 */
class Section implements HtmlElementInterface{

    use Renderable{
        Renderable::setContext as setRenderableContext;
    }

	private $packages;

    private $name;

    private $description;

    private $weight;

    /**
     * @param $name
     * @param $description
     * @param $weight
     */
    public function __construct($name, $description = null, $weight = 100) {
		$this->name = $name;
        $this->description = $description;
        $this->weight = $weight;
        $this->packages = array();
	}

    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $weight
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;
    }

    /**
     * @return mixed
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @return array
     */
    public function getPackages()
    {
        return $this->packages;
    }

    public function addPackage(Package $package){
        $this->packages[] = $package;
    }

    public function setContext($context){
        $this->setRenderableContext($context);
        foreach($this->packages as $package){
            $package->setContext($context);
        }
    }
}

?>