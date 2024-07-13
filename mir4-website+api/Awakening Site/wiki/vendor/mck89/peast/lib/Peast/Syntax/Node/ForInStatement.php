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
 * A node that represents a for-in statement.
 * For example: for (var a in b) {}
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class ForInStatement extends Node implements Statement
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "left" => true,
        "right" => true,
        "body" => true
    );
    
    /**
     * Iteration variable
     * 
     * @var VariableDeclaration|Expression|Pattern
     */
    protected $left;
    
    /**
     * Iterated object
     * 
     * @var Expression
     */
    protected $right;
    
    /**
     * Loop body
     * 
     * @var Statement 
     */
    protected $body;
    
    /**
     * Returns the iteration variable
     * 
     * @return VariableDeclaration|Expression|Pattern
     */
    public function getLeft()
    {
        return $this->left;
    }
    
    /**
     * Sets the iteration variable
     * 
     * @param VariableDeclaration|Expression|Pattern $left Iteration variable
     * 
     * @return $this
     */
    public function setLeft($left)
    {
        $this->assertType(
            $left, array("VariableDeclaration", "Expression", "Pattern")
        );
        $this->left = $left;
        return $this;
    }
    
    /**
     * Returns the iterated object
     * 
     * @return Expression
     */
    public function getRight()
    {
        return $this->right;
    }
    
    /**
     * Sets the iterated object
     * 
     * @param Expression $right Iterated object
     * 
     * @return $this
     */
    public function setRight(Expression $right)
    {
        $this->right = $right;
        return $this;
    }
    
    /**
     * Returns the loop body
     * 
     * @return Statement
     */
    public function getBody()
    {
        return $this->body;
    }
    
    /**
     * Sets the loop body
     * 
     * @param Statement $body Loop body
     * 
     * @return $this
     */
    public function setBody(Statement $body)
    {
        $this->body = $body;
        return $this;
    }
}