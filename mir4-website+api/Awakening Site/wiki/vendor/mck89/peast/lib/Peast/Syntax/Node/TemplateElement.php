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

use Peast\Syntax\Utils;

/**
 * A node that represents a template element.
 * For example `foo` and `bar` in: `foo${exp}bar`
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class TemplateElement extends Node
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "value" => false,
        "tail" => false,
        "rawValue" => false
    );
    
    /**
     * Node's value
     * 
     * @var string 
     */
    protected $value;
    
    /**
     * Tail flag that is true when the element is the tail element in a template
     * 
     * @var bool 
     */
    protected $tail = false;
    
    /**
     * Node's raw value
     * 
     * @var string
     */
    protected $rawValue;
    
    /**
     * Return node's value
     * 
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
    
    /**
     * Sets node's value
     * 
     * @param mixed $value Value
     * 
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        $this->rawValue = Utils::quoteLiteralString($value, "`");
        return $this;
    }
    
    /**
     * Returns the tail flag that is true when the element is the tail element
     * in a template
     * 
     * @return bool
     */
    public function getTail()
    {
        return $this->tail;
    }
    
    /**
     * Sets the tail flag that is true when the element is the tail element
     * in a template
     * 
     * @param bool $tail Tail flag
     * 
     * @return $this
     */
    public function setTail($tail)
    {
        $this->tail = (bool) $tail;
        return $this;
    }
    
    /**
     * Returns node's raw value
     * 
     * @return string
     */
    public function getRawValue()
    {
        return $this->rawValue;
    }
    
    /**
     * Sets node's raw value that must be wrapped in templates quotes.
     * 
     * @param string $rawValue Raw value
     * 
     * @return $this
     */
    public function setRawValue($rawValue)
    {
        $rawValue = preg_replace("#^[`}]|(?:`|\\\$\{)$#", "", $rawValue);
        $this->setValue(Utils::unquoteLiteralString("`$rawValue`"));
        $this->rawValue = $rawValue;
        return $this;
    }
}