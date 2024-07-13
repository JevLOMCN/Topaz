<?php
/**
 * This file is part of the Peast package
 *
 * (c) Marco Marchiò <marco.mm89@gmail.com>
 *
 * For the full copyright and license information refer to the LICENSE file
 * distributed with this source code
 */
namespace Peast\Syntax\Node\JSX;

use Peast\Syntax\Node\Node;
use Peast\Syntax\Node\Expression;

/**
 * A node that represents a JSX element.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class JSXElement extends Node implements Expression
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "openingElement" => true,
        "children" => true,
        "closingElement" => true
    );
    
    /**
     * Opening element node
     * 
     * @var JSXOpeningElement
     */
    protected $openingElement;
    
    /**
     * Children nodes array
     * 
     * @var Node[]
     */
    protected $children = array();
    
    /**
     * Closing element node
     * 
     * @var JSXClosingElement|null
     */
    protected $closingElement;
    
    /**
     * Returns the opening element node
     * 
     * @return JSXOpeningElement
     */
    public function getOpeningElement()
    {
        return $this->openingElement;
    }
    
    /**
     * Sets the opening element node
     * 
     * @param JSXOpeningElement $openingElement Opening element node
     * 
     * @return $this
     */
    public function setOpeningElement(JSXOpeningElement $openingElement)
    {
        $this->openingElement = $openingElement;
        return $this;
    }
    
    /**
     * Returns the children nodes array
     * 
     * @return Node[]
     */
    public function getChildren()
    {
        return $this->children;
    }
    
    /**
     * Sets the children nodes array
     * 
     * @param Node[] $children Children nodes array
     * 
     * @return $this
     */
    public function setChildren($children)
    {
        $this->assertArrayOf($children, array(
            "JSX\\JSXText", "JSX\\JSXExpressionContainer", "JSX\\JSXSpreadChild",
            "JSX\\JSXElement", "JSX\\JSXFragment"
        ));
        $this->children = $children;
        return $this;
    }
    
    /**
     * Returns the closing element node
     * 
     * @return JSXClosingElement|null
     */
    public function getClosingElement()
    {
        return $this->closingElement;
    }
    
    /**
     * Sets the closing element node
     * 
     * @param JSXClosingElement|null $closingElement Closing element node
     * 
     * @return $this
     */
    public function setClosingElement($closingElement)
    {
        $this->assertType($closingElement, "JSX\\JSXClosingElement", true);
        $this->closingElement = $closingElement;
        return $this;
    }
}