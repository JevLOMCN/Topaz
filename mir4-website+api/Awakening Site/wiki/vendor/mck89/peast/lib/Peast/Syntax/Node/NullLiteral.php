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
 * A node that represents a null literal.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class NullLiteral extends Literal
{
    /**
     * Node's value
     * 
     * @var mixed
     */
    protected $value = null;
    
    /**
     * Node's raw value
     * 
     * @var string
     */
    protected $raw = "null";
    
    /**
     * Sets node's value
     * 
     * @param mixed $value Value
     * 
     * @return $this
     */
    public function setValue($value)
    {
        return $this;
    }
}