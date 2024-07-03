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
 * A node that represents a declaration in a VariableDeclaration node.
 * For example "a=1" in: var a = 1
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class VariableDeclarator extends Node
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "id" => true,
        "init" => true
    );
    
    /**
     * Declaration identifier or pattern
     * 
     * @var Pattern 
     */
    protected $id;
    
    /**
     * Optional initializer
     * 
     * @var Expression 
     */
    protected $init;
    
    /**
     * Returns the declaration identifier or pattern
     * 
     * @return Pattern
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Sets the declaration identifier or pattern
     * 
     * @param Pattern $id Declaration identifier or pattern
     * 
     * @return $this
     */
    public function setId(Pattern $id)
    {
        $this->id = $id;
        return $this;
    }
    
    /**
     * Returns the initializer
     * 
     * @return Expression
     */
    public function getInit()
    {
        return $this->init;
    }
    
    /**
     * Sets the initializer
     * 
     * @param Expression $init Initializer
     * 
     * @return $this
     */
    public function setInit($init)
    {
        $this->assertType($init, "Expression", true);
        $this->init = $init;
        return $this;
    }
}