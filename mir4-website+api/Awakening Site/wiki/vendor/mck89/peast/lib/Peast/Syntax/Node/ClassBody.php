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
 * A node that represents a class body.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class ClassBody extends Node
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "body" => true
    );
    
    /**
     * Class methods and properties
     * 
     * @var MethodDefinition[]|PropertyDefinition[]|StaticBlock[]
     */
    protected $body = array();
    
    /**
     * Returns class methods and properties
     * 
     * @return MethodDefinition[]|PropertyDefinition[]|StaticBlock[]
     */
    public function getBody()
    {
        return $this->body;
    }
    
    /**
     * Sets class methods and properties
     * 
     * @param MethodDefinition[]|PropertyDefinition[]|StaticBlock[] $body Class methods array
     * 
     * @return $this
     */
    public function setBody($body)
    {
        $this->assertArrayOf(
            $body,
            array("MethodDefinition", "PropertyDefinition", "StaticBlock")
        );
        $this->body = $body;
        return $this;
    }
}