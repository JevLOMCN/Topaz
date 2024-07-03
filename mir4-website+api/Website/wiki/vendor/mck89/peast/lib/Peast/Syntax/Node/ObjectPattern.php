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
 * A node that represents an object binding pattern.
 * For example: var {a, b, c} = d
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class ObjectPattern extends Node implements Pattern
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "properties" => true
    );
    
    /**
     * Object properties
     * 
     * @var Property[] 
     */
    protected $properties = array();
    
    /**
     * Returns object properties
     * 
     * @return Property[] 
     */
    public function getProperties()
    {
        return $this->properties;
    }
    
    /**
     * Sets object properties
     * 
     * @param Property[] $properties Object properties
     * 
     * @return $this
     */
    public function setProperties($properties)
    {
        $this->assertArrayOf($properties, array("AssignmentProperty", "RestElement"));
        $this->properties = $properties;
        return $this;
    }
}