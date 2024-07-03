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
 * A node that represents a case in a switch statement.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class SwitchCase extends Node
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "test" => true,
        "consequent" => true
    );
    
    /**
     * Test expression that is null in the "default" case
     * 
     * @var Expression 
     */
    protected $test;
    
    /**
     * Consequent statements array
     * 
     * @var Statement[] 
     */
    protected $consequent = array();
    
    /**
     * Returns the test expression that is null in the "default" case
     * 
     * @return Expression
     */
    public function getTest()
    {
        return $this->test;
    }
    
    /**
     * Sets the test expression that is null in the "default" case
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
     * Returns the consequent statements array
     * 
     * @return Statement[]
     */
    public function getConsequent()
    {
        return $this->consequent;
    }
    
    /**
     * Sets the consequent statements array
     * 
     * @param Expression[] $consequent Consequent statements array
     * 
     * @return $this
     */
    public function setConsequent($consequent)
    {
        $this->assertArrayOf($consequent, "Statement");
        $this->consequent = $consequent;
        return $this;
    }
}