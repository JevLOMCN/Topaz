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
 * A node that represents the rest element in array binding patterns or function
 * parameters.
 * For example "...rest" in: [a, ...rest] = b
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class RestElement extends Node implements Pattern
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
     * The node's argument
     * 
     * @var Pattern
     */
    protected $argument;
    
    /**
     * Returns the node's argument
     * 
     * @return Pattern
     */
    public function getArgument()
    {
        return $this->argument;
    }
    
    /**
     * Sets the node's argument
     * 
     * @param Pattern $argument Node's argument
     * 
     * @return $this
     */
    public function setArgument(Pattern $argument)
    {
        $this->argument = $argument;
        return $this;
    }
}