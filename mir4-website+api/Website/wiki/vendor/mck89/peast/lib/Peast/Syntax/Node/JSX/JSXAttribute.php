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

/**
 * A node that represents a JSX attribute.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class JSXAttribute extends Node
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "name" => true,
        "value" => true
    );
    
    /**
     * Attribute name
     * 
     * @var JSXIdentifier|JSXNamespacedName
     */
    protected $name;
    
    /**
     * Attribute value
     * 
     * @var Node|null
     */
    protected $value;
    
    /**
     * Returns the attribute name
     * 
     * @return Node
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Sets the attribute name
     * 
     * @param JSXIdentifier|JSXNamespacedName $name Attribute name
     * 
     * @return $this
     */
    public function setName($name)
    {
        $this->assertType($name, array("JSX\\JSXIdentifier", "JSX\\JSXNamespacedName"));
        $this->name = $name;
        return $this;
    }
    
    /**
     * Returns the attribute value
     * 
     * @return Node|null
     */
    public function getValue()
    {
        return $this->value;
    }
    
    /**
     * Sets the attribute value
     * 
     * @param Node|null $value Attribute value
     * 
     * @return $this
     */
    public function setValue($value)
    {
        $this->assertType(
            $value,
            array(
                "Literal", "JSX\\JSXExpressionContainer",
                "JSX\\JSXElement", "JSX\\JSXFragment"
            ),
            true
        );
        $this->value = $value;
        return $this;
    }
}