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
 * A node that represents the catch clause in a try-catch statement.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class CatchClause extends Node
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "param" => true,
        "body" => true
    );
    
    /**
     * The catch clause parameter
     * 
     * @var Pattern 
     */
    protected $param;
    
    /**
     * The body of the catch clause
     * 
     * @var BlockStatement 
     */
    protected $body;
    
    /**
     * Returns the catch clause parameter
     * 
     * @return Pattern
     */
    public function getParam()
    {
        return $this->param;
    }
    
    /**
     * Sets the catch clause parameter
     * 
     * @param Pattern $param Catch clause parameter
     * 
     * @return $this
     */
    public function setParam($param)
    {
        $this->assertType($param, "Pattern", true);
        $this->param = $param;
        return $this;
    }
    
    /**
     * Returns the body of the catch clause
     * 
     * @return BlockStatement
     */
    public function getBody()
    {
        return $this->body;
    }
    
    /**
     * Sets the body of the catch clause
     * 
     * @param BlockStatement $body The block of code inside the catch clause
     * 
     * @return $this
     */
    public function setBody(BlockStatement $body)
    {
        $this->body = $body;
        return $this;
    }
}