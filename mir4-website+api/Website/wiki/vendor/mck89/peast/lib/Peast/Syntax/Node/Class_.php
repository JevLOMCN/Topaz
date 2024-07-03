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
 * Abstract class for classes.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
abstract class Class_ extends Node
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "id" => true,
        "superClass" => true,
        "body" => true
    );
    
    /**
     * Class name
     * 
     * @var Identifier 
     */
    protected $id;
    
    /**
     * Extended class
     * 
     * @var Expression 
     */
    protected $superClass;
    
    /**
     * Class body
     * 
     * @var ClassBody 
     */
    protected $body;
    
    /**
     * Returns class name
     * 
     * @return Identifier
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Sets class name
     * 
     * @param Identifier $id Class name
     * 
     * @return $this
     */
    public function setId($id)
    {
        $this->assertType($id, "Identifier", true);
        $this->id = $id;
        return $this;
    }
    
    /**
     * Returns extended class
     * 
     * @return Expression
     */
    public function getSuperClass()
    {
        return $this->superClass;
    }
    
    /**
     * Sets extended class
     * 
     * @param Expression $superClass Extended class
     * 
     * @return $this
     */
    public function setSuperClass($superClass)
    {
        $this->assertType($superClass, "Expression", true);
        $this->superClass = $superClass;
        return $this;
    }
    
    /**
     * Returns class body
     * 
     * @return ClassBody
     */
    public function getBody()
    {
        return $this->body;
    }
    
    /**
     * Sets class body
     * 
     * @param ClassBody $body Class body
     * 
     * @return $this
     */
    public function setBody($body)
    {
        $this->assertType($body, "ClassBody");
        $this->body = $body;
        return $this;
    }
}