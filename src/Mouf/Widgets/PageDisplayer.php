<?php

namespace Mouf\Widgets;

use Mouf\Html\HtmlElement\HtmlElementInterface;
use Mouf\Html\Renderer\Renderable;

/**
 * Represents a whole package (with all branches and tags it contains).
 * 
 * @author David Negrier
 */
class PageDisplayer implements HtmlElementInterface
{
    use Renderable{
        Renderable::setContext as setRenderableContext;
    }

    /**
     * @var Array[HtmlElementInterface]
     */
    private $elementsToDisplay;

    /**
     * @param array $elementsToDisplay
     */
    public function setElementsToDisplay($elementsToDisplay)
    {
        $this->elementsToDisplay = $elementsToDisplay;
    }

    /**
     * @return array
     */
    public function getElementsToDisplay()
    {
        return $this->elementsToDisplay;
    }

    public function setContext($context)
    {
        $pageName = $context;
        $this->setRenderableContext($context);
        foreach ($this->elementsToDisplay as $element) {
            $context = $pageName;
            if ($element->getName()) {
                $context .= '__'.$element->getName();
            }
            $element->setContext($context);
        }
    }
}
