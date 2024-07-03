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
 * A node that represents a property in an object literal.
 * For example "b" in: a = {b: 1}
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class Property extends Node
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "key" => true,
        "value" => true,
        "kind" => false,
        "method" => false,
        "shorthand" => false,
        "computed" => false
    );
    
    //Kind constants
    /**
     * The default kind for properties
     */
    const KIND_INIT = "init";
    
    /**
     * Getter property
     */
    const KIND_GET = "get";
    
    /**
     * Setter property
     */
    const KIND_SET = "set";
    
    /**
     * Property key
     * 
     * @var Expression 
     */
    protected $key;
    
    /**
     * Property value
     * 
     * @var Expression 
     */
    protected $value;
    
    /**
     * Property kind that is one of the kind constants
     * 
     * @var string
     */
    protected $kind = self::KIND_INIT;
    
    /**
     * Property method flag that is true when the property is a method
     * 
     * @var bool 
     */
    protected $method = false;
    
    /**
     * Property shorthand flag that is true when the property is declared
     * using an identifier and without a value
     * 
     * @var bool 
     */
    protected $shorthand = false;
    
    /**
     * Property computed flag that is true when the property is declared using
     * the square brackets syntax
     * 
     * @var bool 
     */
    protected $computed = false;
    
    /**
     * Returns the property key
     * 
     * @return Expression
     */
    public function getKey()
    {
        return $this->key;
    }
    
    /**
     * Sets the property key
     * 
     * @param Expression $key Property key
     * 
     * @return $this
     */
    public function setKey(Expression $key)
    {
        $this->key = $key;
        return $this;
    }
    
    /**
     * Returns the property value
     * 
     * @return Expression
     */
    public function getValue()
    {
        return $this->value;
    }
    
    /**
     * Sets the property value
     * 
     * @param Expression $value Property value
     * 
     * @return $this
     */
    public function setValue($value)
    {
        $this->assertType($value, "Expression");
        $this->value = $value;
        return $this;
    }
    
    /**
     * Returns the property kind that is one of the kind constants
     * 
     * @return string
     */
    public function getKind()
    {
        return $this->kind;
    }
    
    /**
     * Sets the property kind that is one of the kind constants
     * 
     * @param string $kind Property kind
     * 
     * @return $this
     */
    public function setKind($kind)
    {
        $this->kind = $kind;
        return $this;
    }
    
    /**
     * Returns the property method flag that is true when the property is a
     * method
     * 
     * @return bool
     */
    public function getMethod()
    {
        return $this->method;
    }
    
    /**
     * Sets the property method flag that is true when the property is a method
     * 
     * @param bool $method Method flag
     * 
     * @return $this
     */
    public function setMethod($method)
    {
        $this->method = (bool) $method;
        return $this;
    }
    
    /**
     * Returns the property shorthand flag that is true when the property
     * is declared using an identifier and without a value
     * 
     * @return bool 
     */
    public function getShorthand()
    {
        return $this->shorthand;
    }
    
    /**
     * Sets the property shorthand flag that is true when the property
     * is declared using an identifier and without a value
     * 
     * @param bool $shorthand Property shorthand flag
     * 
     * @return $this
     */
    public function setShorthand($shorthand)
    {
        $this->shorthand = (bool) $shorthand;
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
}