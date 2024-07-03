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
 * A node that represents a meta property.
 * For example: new.target
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class MetaProperty extends Node implements Expression
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "meta" => false,
        "property" => false
    );
    
    /**
     * Subject
     * 
     * @var string 
     */
    protected $meta;
    
    /**
     * Property
     * 
     * @var string 
     */
    protected $property;
    
    /**
     * Returns the subject
     * 
     * @return string
     */
    public function getMeta()
    {
        return $this->meta;
    }
    
    /**
     * Sets the subject
     * 
     * @param string $meta Subject
     * 
     * @return $this
     */
    public function setMeta($meta)
    {
        $this->meta = $meta;
        return $this;
    }
    
    /**
     * Returns the property
     * 
     * @return string
     */
    public function getProperty()
    {
        return $this->property;
    }
    
    /**
     * Sets the property
     * 
     * @param string $property Property
     * 
     * @return $this
     */
    public function setProperty($property)
    {
        $this->property = $property;
        return $this;
    }
}