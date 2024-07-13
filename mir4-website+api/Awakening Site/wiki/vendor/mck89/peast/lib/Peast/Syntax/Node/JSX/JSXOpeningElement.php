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

/**
 * A node that represents a JSX opening element tag.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class JSXOpeningElement extends JSXBoundaryElement
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "attributes" => true,
        "selfClosing" => false
    );
    
    /**
     * Children nodes array
     * 
     * @var JSXAttribute[]|JSXSpreadAttribute[]
     */
    protected $attributes = array();
    
    /**
     * Self closing tag mode
     * 
     * @var bool
     */
    protected $selfClosing = false;
    
    /**
     * Returns the children attributes array
     * 
     * @return JSXAttribute[]|JSXSpreadAttribute[]
     */
    public function getAttributes()
    {
        return $this->attributes;
    }
    
    /**
     * Sets the attributes nodes array
     * 
     * @param JSXAttribute[]|JSXSpreadAttribute[] $attributes Attributes nodes
     *                                                         array
     * 
     * @return $this
     */
    public function setAttributes($attributes)
    {
        $this->assertArrayOf($attributes, array(
            "JSX\\JSXAttribute", "JSX\\JSXSpreadAttribute"
        ));
        $this->attributes = $attributes;
        return $this;
    }
    
    /**
     * Returns the self closing tag mode
     * 
     * @return bool
     */
    public function getSelfClosing()
    {
        return $this->selfClosing;
    }
    
    /**
     * Sets the self closing tag mode
     * 
     * @param bool $selfClosing Self closing tag mode
     * 
     * @return $this
     */
    public function setSelfClosing($selfClosing)
    {
        $this->selfClosing = (bool) $selfClosing;
        return $this;
    }
}