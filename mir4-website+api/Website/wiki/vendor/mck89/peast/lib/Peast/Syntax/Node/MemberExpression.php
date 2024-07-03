<?php
/**
 * This file is part of the Peast package
 *
 * (c) Marco Marchiò <marco.mm89@gmail.com>
 *
 * For the full copyright and license information refer to the LICENSE file
 * distributed with this source code
 */
namespace Peast\Syntax\Node;

/**
 * A node that represents a member expression.
 * For example: foo.bar
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class MemberExpression extends ChainElement implements Pattern
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "object" => true,
        "property" => true,
        "computed" => false
    );
    
    /**
     * Expression's object
     * 
     * @var Expression|Super 
     */
    protected $object;
    
    /**
     * Expression's property
     * 
     * @var Expression|PrivateIdentifier
     */
    protected $property;
    
    /**
     * Computed flag that is true if the property is declared using square
     * brackets syntax
     * 
     * @var bool 
     */
    protected $computed = false;
    
    /**
     * Returns the expression's object
     * 
     * @return Expression|Super 
     */
    public function getObject()
    {
        return $this->object;
    }
    
    /**
     * Sets the expression's object
     * 
     * @param Expression|Super $object Object
     * 
     * @return $this
     */
    public function setObject($object)
    {
        $this->assertType($object, array("Expression", "Super"));
        $this->object = $object;
        return $this;
    }
    
    /**
     * Returns the expression's property
     * 
     * @return Expression|PrivateIdentifier
     */
    public function getProperty()
    {
        return $this->property;
    }
    
    /**
     * Sets the expression's property
     * 
     * @param Expression|PrivateIdentifier $property Property
     * 
     * @return $this
     */
    public function setProperty($property)
    {
        $this->assertType($property, array("Expression", "PrivateIdentifier"));
        $this->property = $property;
        return $this;
    }
    
    /**
     * Returns the computed flag that is true if the property is declared
     * using square brackets syntax
     * 
     * @return bool
     */
    public function getComputed()
    {
        return $this->computed;
    }
    
    /**
     * Sets the computed flag that is true if the property is declared
     * using square brackets syntax
     * 
     * @param bool $computed Computed flag
     * 
     * @return $this
     */
    public function setComputed($computed)
    {
        $this->computed = (bool) $computed;
        return $this;
    }
}