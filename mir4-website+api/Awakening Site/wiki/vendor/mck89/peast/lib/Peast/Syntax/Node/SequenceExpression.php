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
 * A node that represents a sequence of expressions.
 * For example: a, b
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class SequenceExpression  extends Node implements Expression
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "expressions" => true
    );
    
    /**
     * Expressions array
     * 
     * @var Expression[]
     */
    protected $expressions = array();
    
    /**
     * Returns the expressions array
     * 
     * @return Expression[]
     */
    public function getExpressions()
    {
        return $this->expressions;
    }
    
    /**
     * Sets the expressions array
     * 
     * @param Expression[] $expressions Expressions array
     * 
     * @return $this
     */
    public function setExpressions($expressions)
    {
        $this->assertArrayOf($expressions, "Expression");
        $this->expressions = $expressions;
        return $this;
    }
}