<?php
/**
 * This file is part of the Peast package
 *
 * (c) Marco Marchiò <marco.mm89@gmail.com>
 *
 * For the full copyright and license information refer to the LICENSE file
 * distributed with this source code
 */
namespace Peast\Syntax\Node\JSX;

use Peast\Syntax\Node\Node;
use Peast\Syntax\Node\Expression;

/**
 * A node that represents a spread child expression in JSX.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class JSXSpreadChild extends Node
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
     * The wrapped expression
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