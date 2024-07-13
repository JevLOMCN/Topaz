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
 * A node that represents a property in an object binding pattern.
 * For example "a" in: var {a} = b
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class AssignmentProperty extends Property
{
    /**
     * Returns node's type
     * 
     * @return string
     */
    public function getType()
    {
        return "Property";
    }
    
    /**
     * Sets the property value
     * 
     * @param Pattern $value Property value
     * 
     * @return $this
     */
    public function setValue($value)
    {
        $this->assertType($value, "Pattern");
        $this->value = $value;
        return $this;
    }
    
    /**
     * Sets the property kind that is one of the kind constants
     * 
     * @param string $kind Property kind
     * 
     * @return $this
     * 
     * @codeCoverageIgnore
     */
    public function setKind($kind)
    {
        return $this;
    }
    
    /**
     * Sets the property method flag that is true when the property is a method
     * 
     * @param bool $method Method flag
     * 
     * @return $this
     * 
     * @codeCoverageIgnore
     */
    public function setMethod($method)
    {
        return $this;
    }
}