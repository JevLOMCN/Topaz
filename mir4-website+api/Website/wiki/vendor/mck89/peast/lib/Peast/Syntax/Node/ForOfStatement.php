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
 * A node that represents the "for...of" statement.
 * For example: for (var a of b) {}
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class ForOfStatement extends ForInStatement
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "left" => true,
        "right" => true,
        "body" => true,
        "await" => false
    );
    
    /**
     * Async iteration flag
     * 
     * @var bool
     */
    protected $await = false;
    
    /**
     * Returns the async iteration flag
     * 
     * @return bool
     */
    public function getAwait()
    {
        return $this->await;
    }
    
    /**
     * Sets the async iteration flag
     * 
     * @param bool $await Async iteration flag
     * 
     * @return $this
     */
    public function setAwait($await)
    {
        $this->await = (bool) $await;
        return $this;
    }
}