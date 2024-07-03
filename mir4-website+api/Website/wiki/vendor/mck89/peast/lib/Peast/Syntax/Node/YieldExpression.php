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
 * A node that represents a yield statement.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class YieldExpression extends Node implements Expression
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "argument" => true,
        "delegate" => false
    );
    
    /**
     * Yield argument
     * 
     * @var Expression 
     */
    protected $argument;
    
    /**
     * Delegate flag that is true when the yield keyword is preceded by *
     * 
     * @var bool 
     */
    protected $delegate = false;
    
    /**
     * Returns yield argument
     * 
     * @return Expression
     */
    public function getArgument()
    {
        return $this->argument;
    }
    
    /**
     * Sets yield argument
     * 
     * @param Expression $argument Argument
     * 
     * @return $this
     */
    public function setArgument($argument)
    {
        $this->assertType($argument, "Expression", true);
        $this->argument = $argument;
        return $this;
    }
    
    /**
     * Returns the delegate flag that is true when the yield keyword is
     * preceded by *
     * 
     * @return bool
     */
    public function getDelegate()
    {
        return $this->delegate;
    }
    
    /**
     * Sets the delegate flag that is true when the yield keyword is
     * preceded by *
     * 
     * @param bool $delegate Delegate flag
     * 
     * @return $this
     */
    public function setDelegate($delegate)
    {
        $this->delegate = (bool) $delegate;
        return $this;
    }
}