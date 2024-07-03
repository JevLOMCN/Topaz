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
 * A node that represents the throw statement.
 * For example: throw err
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class ThrowStatement extends Node implements Statement
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "argument" => true
    );
    
    /**
     * The thrown expression
     * 
     * @var Expression 
     */
    protected $argument;
    
    /**
     * Returns the thrown expression
     * 
     * @return Expression
     */
    public function getArgument()
    {
        return $this->argument;
    }
    
    /**
     * Sets the thrown expression
     * 
     * @param Expression $argument The node to set
     * 
     * @return $this
     */
    public function setArgument(Expression $argument)
    {
        $this->argument = $argument;
        return $this;
    }
}