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
 * A node that represents a while loop.
 * For example: while (test) {}
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class WhileStatement extends Node implements Statement
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "test" => true,
        "body" => true
    );
    
    /**
     * The loop condition
     * 
     * @var Expression 
     */
    protected $test;
    
    /**
     * The loop body
     * 
     * @var Statement 
     */
    protected $body;
    
    /**
     * Returns the loop condition
     * 
     * @return Expression
     */
    public function getTest()
    {
        return $this->test;
    }
    
    /**
     * Sets the loop condition
     * 
     * @param Expression $test Loop
     * 
     * @return $this
     */
    public function setTest(Expression $test)
    {
        $this->test = $test;
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