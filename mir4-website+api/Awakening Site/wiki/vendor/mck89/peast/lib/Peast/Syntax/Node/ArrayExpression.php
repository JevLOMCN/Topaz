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
 * A node that represents an array literal.
 * For example: [a, b, c]
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class ArrayExpression extends Node implements Expression
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "elements" => true
    );
    
    /**
     * Array elements
     * 
     * @var Expression[]|SpreadElement[]
     */
    protected $elements = array();
    
    /**
     * Returns array elements
     * 
     * @return Expression[]|SpreadElement[]
     */
    public function getElements()
    {
        return $this->elements;
    }
    
    /**
     * Sets array elements
     * 
     * @param Expression[]|SpreadElement[] $elements Array elements to set
     * 
     * @return $this
     */
    public function setElements($elements)
    {
        $this->assertArrayOf(
            $elements,
            array("Expression", "SpreadElement"),
            true
        );
        $this->elements = $elements;
        return $this;
    }
}