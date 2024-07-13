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
 * Abstract class for literals.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
abstract class Literal extends Node implements Expression
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "value" => false,
        "raw" => false
    );
    
    /**
     * Node's value
     * 
     * @var mixed
     */
    protected $value;
    
    /**
     * Node's raw value
     * 
     * @var string
     */
    protected $raw;
    
    /**
     * Returns node's type
     * 
     * @return string
     */
    public function getType()
    {
        return "Literal";
    }
    
    /**
     * Returns node's value
     * 
     * @return mixed
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
    abstract public function setValue($value);
    
    /**
     * Return node's raw value
     * 
     * @return string
     */
    public function getRaw()
    {
        return $this->raw;
    }
    
    /**
     * Sets node's raw value
     * 
     * @param mixed $raw Raw value
     * 
     * @return $this
     */
    public function setRaw($raw)
    {
        return $this->setValue($raw);
    }
}