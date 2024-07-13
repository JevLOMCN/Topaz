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
 * A node that represents an await expression.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class AwaitExpression extends Node implements Expression
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
     * Expression's argument
     * 
     * @var Expression 
     */
    protected $argument;
    
    /**
     * Returns the expression's argument
     * 
     * @return Expression
     */
    public function getArgument()
    {
        return $this->argument;
    }
    
    /**
     * Sets the expression's argument
     * 
     * @param Expression $argument Argument
     * 
     * @return $this
     */
    public function setArgument(Expression $argument)
    {
        $this->argument = $argument;
        return $this;
    }
}