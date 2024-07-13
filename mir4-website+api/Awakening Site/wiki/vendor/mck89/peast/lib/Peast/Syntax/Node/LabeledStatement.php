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
 * A node that represents a labeled statement.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class LabeledStatement extends Node implements Statement
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "label" => true,
        "body" => true
    );
    
    /**
     * Label
     * 
     * @var Identifier 
     */
    protected $label;
    
    /**
     * Body
     * 
     * @var Statement 
     */
    protected $body;
    
    /**
     * Returns the label
     * 
     * @return Identifier
     */
    public function getLabel()
    {
        return $this->label;
    }
    
    /**
     * Sets the label
     * 
     * @param Identifier $label Label
     * 
     * @return $this
     */
    public function setLabel(Identifier $label)
    {
        $this->label = $label;
        return $this;
    }
    
    /**
     * Returns the body
     * 
     * @return Statement
     */
    public function getBody()
    {
        return $this->body;
    }
    
    /**
     * Sets the body
     * 
     * @param Statement $body Body
     * 
     * @return $this
     */
    public function setBody(Statement $body)
    {
        $this->body = $body;
        return $this;
    }
}