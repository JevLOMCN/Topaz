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
 * A node that represents the "break" statement inside loops.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class BreakStatement extends Node implements Statement
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "label" => true
    );
    
    /**
     * The optional label of the break statement
     * 
     * @var Identifier 
     */
    protected $label;
    
    /**
     * Returns the node's label
     * 
     * @return Identifier
     */
    public function getLabel()
    {
        return $this->label;
    }
    
    /**
     * Sets the node's label
     * 
     * @param Identifier $label Node's label
     * 
     * @return $this
     */
    public function setLabel($label)
    {
        $this->assertType($label, "Identifier", true);
        $this->label = $label;
        return $this;
    }
}