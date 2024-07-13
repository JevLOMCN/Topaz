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
 * A node that represents a with statement.
 * For example: with (test) {}
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class WithStatement extends Node implements Statement
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "object" => true,
        "body" => true
    );
    
    /**
     * The statement subject
     * 
     * @var Expression 
     */
    protected $object;
    
    /**
     * The statement body
     * 
     * @var Expression
     */
    protected $body;
    
    /**
     * Returns the statement subject
     * 
     * @return Expression
     */
    public function getObject()
    {
        return $this->object;
    }
    
    /**
     * Sets the statement subject
     * 
     * @param Expression $object Statement subject
     * 
     * @return $this
     */
    public function setObject(Expression $object)
    {
        $this->object = $object;
        return $this;
    }
    
    /**
     * Returns the statement body
     * 
     * @return Expression
     */
    public function getBody()
    {
        return $this->body;
    }
    
    /**
     * Sets the statement body
     * 
     * @param Statement $body Statement body
     * 
     * @return $this
     */
    public function setBody(Statement $body)
    {
        $this->body = $body;
        return $this;
    }
}