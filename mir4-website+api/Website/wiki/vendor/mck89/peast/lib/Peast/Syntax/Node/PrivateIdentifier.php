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
 * A node that represents a private identifier.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class PrivateIdentifier extends Node
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "name" => false
    );
    
    /**
     * The identifier's name
     * 
     * @var string
     */
    protected $name;
    
    /**
     * Returns the identifier's name
     * 
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Sets the identifier's name
     * 
     * @param string $name The name to set
     * 
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
}