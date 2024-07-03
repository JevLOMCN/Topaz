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
 * A node that represents an expression statement and wraps another expression.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class ExpressionStatement extends Node implements Statement
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "expression" => true
    );
    
    /**
     * Wrapped expression
     * 
     * @var Expression 
     */
    protected $expression;
    
    /**
     * Returns the wrapped expression
     * 
     * @return Expression
     */
    public function getExpression()
    {
        return $this->expression;
    }
    
    /**
     * Sets the wrapped expression
     * 
     * @param Expression $expression Wrapped expression
     * 
     * @return $this
     */
    public function setExpression(Expression $expression)
    {
        $this->expression = $expression;
        return $this;
    }
}