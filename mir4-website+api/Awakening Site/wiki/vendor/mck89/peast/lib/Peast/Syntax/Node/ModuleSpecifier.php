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
 * Abstract class that export and import specifiers nodes must extend.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 * 
 * @abstract
 */
abstract class ModuleSpecifier extends Node
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "local" => true
    );
    
    /**
     * Local identifier
     * 
     * @var Identifier|StringLiteral
     */
    protected $local;
    
    /**
     * Returns the local identifier
     * 
     * @return Identifier|StringLiteral
     */
    public function getLocal()
    {
        return $this->local;
    }
    
    /**
     * Sets the local identifier
     * 
     * @param Identifier|StringLiteral $local Local identifier
     * 
     * @return $this
     */
    public function setLocal($local)
    {
        $this->assertType($local, array("Identifier", "StringLiteral"));
        $this->local = $local;
        return $this;
    }
}