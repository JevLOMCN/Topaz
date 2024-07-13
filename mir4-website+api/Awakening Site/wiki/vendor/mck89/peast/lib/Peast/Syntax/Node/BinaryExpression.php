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
 * A node that represents a binary expression.
 * For example: a + b
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class BinaryExpression extends Node implements Expression
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "left" => true,
        "operator" => false,
        "right" => true
    );
    
    /**
     * Operator
     * 
     * @var string
     */
    protected $operator;
    
    /**
     * Left expression
     * 
     * @var Expression|PrivateIdentifier
     */
    protected $left;
    
    /**
     * Right expression
     * 
     * @var Expression
     */
    protected $right;
    
    /**
     * Returns the operator
     * 
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }
    
    /**
     * Sets the operator
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
     * Returns the left expression
     * 
     * @return Expression|PrivateIdentifier
     */
    public function getLeft()
    {
        return $this->left;
    }
    
    /**
     * Sets the left expression
     * 
     * @param Expression|PrivateIdentifier $left Left expression
     * 
     * @return $this
     */
    public function setLeft($left)
    {
        $this->assertType($left, array("Expression", "PrivateIdentifier"));
        $this->left = $left;
        return $this;
    }
    
    /**
     * Returns the right expression
     * 
     * @return Expression
     */
    public function getRight()
    {
        return $this->right;
    }
    
    /**
     * Sets the right expression
     * 
     * @param Expression $right Right expression
     * 
     * @return $this
     */
    public function setRight(Expression $right)
    {
        $this->right = $right;
        return $this;
    }
}