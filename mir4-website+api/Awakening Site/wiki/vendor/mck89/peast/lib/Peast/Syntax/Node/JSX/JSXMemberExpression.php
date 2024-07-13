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
 * A node that represents a JSX member expression.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class JSXMemberExpression extends Node implements Expression
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "object" => true,
        "property" => true
    );
    
    /**
     * Expression's object
     * 
     * @var JSXMemberExpression|JSXIdentifier 
     */
    protected $object;
    
    /**
     * Expression's property
     * 
     * @var JSXIdentifier
     */
    protected $property;
    
    /**
     * Returns the expression's object
     * 
     * @return JSXMemberExpression|JSXIdentifier
     */
    public function getObject()
    {
        return $this->object;
    }
    
    /**
     * Sets the expression's object
     * 
     * @param JSXMemberExpression|JSXIdentifier $object Object
     * 
     * @return $this
     */
    public function setObject($object)
    {
        $this->assertType($object, array("JSX\\JSXMemberExpression", "JSX\\JSXIdentifier"));
        $this->object = $object;
        return $this;
    }
    
    /**
     * Returns the expression's property
     * 
     * @return JSXIdentifier
     */
    public function getProperty()
    {
        return $this->property;
    }
    
    /**
     * Sets the expression's property
     * 
     * @param JSXIdentifier $property Property
     * 
     * @return $this
     */
    public function setProperty(JSXIdentifier $property)
    {
        $this->property = $property;
        return $this;
    }
}