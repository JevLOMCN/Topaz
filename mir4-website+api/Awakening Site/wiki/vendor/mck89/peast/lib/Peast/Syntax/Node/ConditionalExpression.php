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
 * A node that represents a conditional expression.
 * For example: test() ? ok() : fail()
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class ConditionalExpression extends Node implements Expression
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "test" => true,
        "consequent" => true,
        "alternate" => true
    );
    
    /**
     * The test expression
     * 
     * @var Expression 
     */
    protected $test;
    
    /**
     * The consequent expression
     * 
     * @var Expression 
     */
    protected $consequent;
    
    /**
     * The alternate expression
     * 
     * @var Expression 
     */
    protected $alternate;
    
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
    public function setTest(Expression $test)
    {
        $this->test = $test;
        return $this;
    }
    
    /**
     * Returns the consequent expression
     * 
     * @return Expression
     */
    public function getConsequent()
    {
        return $this->consequent;
    }
    
    /**
     * Sets the consequent expression
     * 
     * @param Expression $consequent Consequent expression
     * 
     * @return $this
     */
    public function setConsequent(Expression $consequent)
    {
        $this->consequent = $consequent;
        return $this;
    }
    
    /**
     * Returns the alternate expression
     * 
     * @return Expression
     */
    public function getAlternate()
    {
        return $this->alternate;
    }
    
    /**
     * Sets the alternate expression
     * 
     * @param Expression $alternate Alternate expression
     * 
     * @return $this
     */
    public function setAlternate(Expression $alternate)
    {
        $this->alternate = $alternate;
        return $this;
    }
}