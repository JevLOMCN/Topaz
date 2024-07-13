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
 * A node that represents a BigInt literal.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class BigIntLiteral extends Literal
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "bigint" => false
    );
    
    /**
     * Node's value
     * 
     * @var mixed
     */
    protected $bigint;
    
    /**
     * Sets node's value
     * 
     * @param float $value Value
     * 
     * @return $this
     */
    public function setValue($value)
    {
        //Value, Raw and Bigint are always the same value
        $this->value = $this->raw = $this->bigint = $value;
        return $this;
    }
    
    /**
     * Returns node's value
     * 
     * @return string
     */
    public function getBigint()
    {
        return $this->bigint;
    }
    
    /**
     * Sets node's value
     * 
     * @param string $bigint Value
     * 
     * @return $this
     */
    public function setBigint($bigint)
    {
        return $this->setValue($bigint);
    }
}