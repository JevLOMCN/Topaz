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
 * A node that represents a JSX fragment.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class JSXFragment extends Node implements Expression
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "openingFragment" => true,
        "children" => true,
        "closingFragment" => true
    );
    
    /**
     * Opening fragment node
     * 
     * @var JSXOpeningFragment
     */
    protected $openingFragment;
    
    /**
     * Children nodes array
     * 
     * @var Node[]
     */
    protected $children = array();
    
    /**
     * Closing fragment node
     * 
     * @var JSXClosingFragment
     */
    protected $closingFragment;
    
    /**
     * Returns the opening fragment node
     * 
     * @return JSXOpeningFragment
     */
    public function getOpeningFragment()
    {
        return $this->openingFragment;
    }
    
    /**
     * Sets the opening fragment node
     * 
     * @param JSXOpeningFragment $openingFragment Opening fragment node
     * 
     * @return $this
     */
    public function setOpeningFragment(JSXOpeningFragment $openingFragment)
    {
        $this->openingFragment = $openingFragment;
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
     * Returns the closing fragment node
     * 
     * @return JSXClosingFragment
     */
    public function getClosingFragment()
    {
        return $this->closingFragment;
    }
    
    /**
     * Sets the closing fragment node
     * 
     * @param JSXClosingFragment $closingFragment Closing fragment node
     * 
     * @return $this
     */
    public function setClosingFragment(JSXClosingFragment $closingFragment)
    {
        $this->closingFragment = $closingFragment;
        return $this;
    }
}