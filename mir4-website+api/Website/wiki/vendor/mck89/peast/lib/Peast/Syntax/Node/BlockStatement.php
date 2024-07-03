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
 * A node that represents a block of code wrapped in curly braces.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class BlockStatement extends Node implements Statement
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
     * Block's body
     * 
     * @var Statement[] 
     */
    protected $body = array();
    
    /**
     * Returns block's body
     * 
     * @return Statement[]
     */
    public function getBody()
    {
        return $this->body;
    }
    
    /**
     * Sets block's body
     * 
     * @param Statement[] $body Array of Statements that are the body of the
     *                          block
     * 
     * @return $this
     */
    public function setBody($body)
    {
        $this->assertArrayOf($body, "Statement");
        $this->body = $body;
        return $this;
    }
}