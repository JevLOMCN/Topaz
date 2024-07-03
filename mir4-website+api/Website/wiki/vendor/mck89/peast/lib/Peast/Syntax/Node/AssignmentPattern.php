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
 * A node that represents an assignment in a binding context.
 * For example "a = b" in: var {a = b} = c
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class AssignmentPattern extends Node implements Pattern
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "left" => true,
        "right" => true
    );
    
    /**
     * The left node of the assignment
     * 
     * @var Pattern
     */
    protected $left;
    
    /**
     * The right node of the assignment
     * 
     * @var Expression
     */
    protected $right;
    
    /**
     * Returns the left node of the assignment
     * 
     * @return Pattern
     */
    public function getLeft()
    {
        return $this->left;
    }
    
    /**
     * Sets the left node of the assignment
     * 
     * @param Pattern $left Left node
     * 
     * @return $this
     */
    public function setLeft(Pattern $left)
    {
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
     * @param Expression $right Right node
     * 
     * @return $this
     */
    public function setRight(Expression $right)
    {
        $this->right = $right;
        return $this;
    }
}