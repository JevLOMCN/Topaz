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
 * A node that represents a method declaration in classes and object literals.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class MethodDefinition extends Node
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
        "computed" => false,
        "static" => false
    );
    
    //Kind constants
    /**
     * Constructor method
     */
    const KIND_CONSTRUCTOR = "constructor";
    
    /**
     * Standard method
     */
    const KIND_METHOD = "method";
    
    /**
     * Getter method
     */
    const KIND_GET = "get";
    
    /**
     * Setter method
     */
    const KIND_SET = "set";
    
    /**
     * Method's key
     * 
     * @var Expression|PrivateIdentifier
     */
    protected $key;
    
    /**
     * Method's value
     * 
     * @var FunctionExpression 
     */
    protected $value;
    
    /**
     * Method's kind that is one of the kind constants
     * 
     * @var string 
     */
    protected $kind = self::KIND_METHOD;
    
    /**
     * Computed flag that is true if method's key is declared using square
     * brackets syntax
     * 
     * @var bool 
     */
    protected $computed = false;
    
    /**
     * Static flag that is true if the method is static
     * 
     * @var bool 
     */
    protected $static = false;
    
    /**
     * Returns the method's key
     * 
     * @return Expression|PrivateIdentifier
     */
    public function getKey()
    {
        return $this->key;
    }
    
    /**
     * Sets the method's key
     * 
     * @param Expression|PrivateIdentifier $key Method's key
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
     * Returns the method's value
     * 
     * @return FunctionExpression
     */
    public function getValue()
    {
        return $this->value;
    }
    
    /**
     * Sets the method's value
     * 
     * @param FunctionExpression $value Method's value
     * 
     * @return $this
     */
    public function setValue(FunctionExpression $value)
    {
        $this->value = $value;
        return $this;
    }
    
    /**
     * Returns the method's kind that is one of the kind constants
     * 
     * @return string
     */
    public function getKind()
    {
        return $this->kind;
    }
    
    /**
     * Sets the method's kind that is one of the kind constants
     * 
     * @param string $kind Method's kind
     * 
     * @return $this
     */
    public function setKind($kind)
    {
        $this->kind = $kind;
        return $this;
    }
    
    /**
     * Returns the computed flag that is true if method's key is declared using
     * square brackets syntax
     * 
     * @return bool
     */
    public function getComputed()
    {
        return $this->computed;
    }
    
    /**
     * Sets the computed flag that is true if method's key is declared using
     * square brackets syntax
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
    
    /**
     * Returns the static flag that is true if the method is static
     * 
     * @return bool
     */
    public function getStatic()
    {
        return $this->{"static"};
    }
    
    /**
     * Sets the static flag that is true if the method is static
     * 
     * @param bool $static Static flag
     * 
     * @return $this
     */
    public function setStatic($static)
    {
        $this->{"static"} = (bool) $static;
        return $this;
    }
}