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
 * A base class for boundary elements.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 * 
 * @abstract
 */
abstract class JSXBoundaryElement extends Node
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "name" => true
    );
    
    /**
     * Element name
     * 
     * @var JSXIdentifier|JSXMemberExpression|JSXNamespacedName
     */
    protected $name;
    
    /**
     * Returns the element name
     * 
     * @return JSXIdentifier|JSXMemberExpression|JSXNamespacedName
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Sets the element name
     * 
     * @param JSXIdentifier|JSXMemberExpression|JSXNamespacedName $name Element
     *                                                                  name
     * 
     * @return $this
     */
    public function setName($name)
    {
        $this->assertType(
            $name,
            array("JSX\\JSXIdentifier", "JSX\\JSXMemberExpression", "JSX\\JSXNamespacedName")
        );
        $this->name = $name;
        return $this;
    }
}