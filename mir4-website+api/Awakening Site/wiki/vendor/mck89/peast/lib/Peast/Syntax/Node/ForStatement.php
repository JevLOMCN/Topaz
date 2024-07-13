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
 * A node that represents a for statement.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class ForStatement extends Node implements Statement
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "init" => true,
        "test" => true,
        "update" => true,
        "body" => true
    );
    
    /**
     * Initializer
     * 
     * @var VariableDeclaration|Expression
     */
    protected $init;
    
    /**
     * Test expression
     * 
     * @var Expression 
     */
    protected $test;
    
    /**
     * Update expression
     * 
     * @var Expression 
     */
    protected $update;
    
    /**
     * Loop body
     * 
     * @var Statement 
     */
    protected $body;
    
    /**
     * Returns the initializer
     * 
     * @return VariableDeclaration|Expression
     */
    public function getInit()
    {
        return $this->init;
    }
    
    /**
     * Sets the initializer
     * 
     * @param VariableDeclaration|Expression $init Initializer
     * 
     * @return $this
     */
    public function setInit($init)
    {
        $this->assertType(
            $init,
            array("VariableDeclaration", "Expression"),
            true
        );
        $this->init = $init;
        return $this;
    }
    
    /**
     * Returns the test expression
     * 
     * @return Expression
     */
    public function getTest()
    {
        return $this->test;
    }
    
    /**
     * Sets the test expression
     * 
     * @param Expression $test Test expression
     * 
     * @return $this
     */
    public function setTest($test)
    {
        $this->assertType($test, "Expression", true);
        $this->test = $test;
        return $this;
    }
    
    /**
     * Returns the update expression
     * 
     * @return Expression
     */
    public function getUpdate()
    {
        return $this->update;
    }
    
    /**
     * Sets the update expression
     * 
     * @param Expression $update Update expression
     * 
     * @return $this
     */
    public function setUpdate($update)
    {
        $this->assertType($update, "Expression", true);
        $this->update = $update;
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