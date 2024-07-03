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
 * A node that represents a property definition in class body.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class PropertyDefinition extends Node
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "key" => true,
        "value" => true,
        "computed" => false,
        "static" => false
    );
    
    /**
     * Property key
     * 
     * @var Expression|PrivateIdentifier
     */
    protected $key;
    
    /**
     * Optional property value
     * 
     * @var Expression|null
     */
    protected $value;
    
    /**
     * Property computed flag that is true when the property is declared using
     * the square brackets syntax
     * 
     * @var bool 
     */
    protected $computed = false;
    
    /**
     * Property static flag that is true when the property is declared static
     * 
     * @var bool 
     */
    protected $static = false;
    
    /**
     * Returns the property key
     * 
     * @return Expression|PrivateIdentifier
     */
    public function getKey()
    {
        return $this->key;
    }
    
    /**
     * Sets the property key
     * 
     * @param Expression|PrivateIdentifier $key Property key
     * 
     * @return $this
     */
    public function setKey($key)
    {
        $this->assertType($key, array("Expression", "PrivateIdentifier"));
        $this->key = $key;
        return $this;
    }
    
    /**
     * Returns the property value
     * 
     * @return Expression|null
     */
    public function getValue()
    {
        return $this->value;
    }
    
    /**
     * Sets the property value
     * 
     * @param Expression|null $value Property value
     * 
     * @return $this
     */
    public function setValue($value)
    {
        $this->assertType($value, "Expression", true);
        $this->value = $value;
        return $this;
    }
    
    /**
     * Returns the property computed flag that is true when the property is
     * declared using the square brackets syntax
     * 
     * @return bool
     */
    public function getComputed()
    {
        return $this->computed;
    }
    
    /**
     * Sets the property computed flag that is true when the property is
     * declared using the square brackets syntax
     * 
     * @param bool $computed Property computed flag
     * 
     * @return $this
     */
    public function setComputed($computed)
    {
        $this->computed = (bool) $computed;
        return $this;
    }
    
    /**
     * Returns the property static flag that is true when the property is
     * declared static
     * 
     * @return bool
     */
    public function getStatic()
    {
        return $this->static;
    }
    
    /**
     * Sets the property static flag that is true when the property is
     * declared static
     * 
     * @param bool $static Property static flag
     * 
     * @return $this
     */
    public function setStatic($static)
    {
        $this->static = (bool) $static;
        return $this;
    }
}