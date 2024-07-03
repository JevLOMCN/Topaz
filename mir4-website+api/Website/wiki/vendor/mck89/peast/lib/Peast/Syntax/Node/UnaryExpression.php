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
 * A node that represents a unary expression.
 * For example: !a
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class UnaryExpression extends Node implements Expression
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "argument" => true,
        "operator" => false,
        "prefix" => false
    );
    
    /**
     * Expression's operator
     * 
     * @var string
     */
    protected $operator;
    
    /**
     * Prefix flag that is always true since the operator precedes the argument
     * 
     * @var bool
     */
    protected $prefix = true;
    
    /**
     * Expression's argument
     * 
     * @var Expression 
     */
    protected $argument;
    
    /**
     * Returns the expression's operator
     * 
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }
    
    /**
     * Sets the expression's operator
     * 
     * @param string $operator Operator
     * 
     * @return $this
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;
        return $this;
    }
    
    /**
     * Returns the prefix flag that is always true since the operator precedes
     * the argument
     * 
     * @return bool
     */
    public function getPrefix()
    {
        return $this->prefix;
    }
    
    /**
     * This method does nothing since the prefix flag is always true for
     * unary expressions
     * 
     * @param bool $prefix Prefix flag
     * 
     * @return $this
     * 
     * @codeCoverageIgnore
     */
    public function setPrefix($prefix)
    {
        return $this;
    }
    
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