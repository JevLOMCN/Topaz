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
 * A node that represents an assignment expression.
 * For example: a = b
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class AssignmentExpression extends Node implements Expression
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
     * The assignment operator
     * 
     * @var string 
     */
    protected $operator;
    
    /**
     * The left node of the assignment
     * 
     * @var Pattern|Expression
     */
    protected $left;
    
    /**
     * The right node of the assignment
     * 
     * @var Expression 
     */
    protected $right;
    
    /**
     * Returns the assignment operator
     * 
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }
    
    /**
     * Sets the assignment operator
     * 
     * @param string $operator Assignment operator
     * 
     * @return $this
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;
        return $this;
    }
    
    /**
     * Returns the left node of the assignment
     * 
     * @return Pattern|Expression 
     */
    public function getLeft()
    {
        return $this->left;
    }
    
    /**
     * Sets the left node of the assignment
     * 
     * @param Pattern|Expression $left The node to set
     * 
     * @return $this
     */
    public function setLeft($left)
    {
        $this->assertType($left, array("Pattern", "Expression"));
        $this->left = $left;
        return $this;
    }
    
    /**
     * Returns the right node of the assignment
     * 
     * @return Expression 
     */
    public function getRight()
    {
        return $this->right;
    }
    
    /**
     * Sets the right node of the assignment
     * 
     * @param Expression $right The node to set
     * 
     * @return $this
     */
    public function setRight(Expression $right)
    {
        $this->right = $right;
        return $this;
    }
}