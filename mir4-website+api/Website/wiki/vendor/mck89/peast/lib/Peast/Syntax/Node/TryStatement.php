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
 * A node that represents a try-catch statement.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class TryStatement extends Node implements Statement
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "block" => true,
        "handler" => true,
        "finalizer" => true
    );
    
    /**
     * Wrapped block
     * 
     * @var BlockStatement
     */
    protected $block;
    
    /**
     * Catch clause
     * 
     * @var CatchClause 
     */
    protected $handler;
    
    /**
     * "finally" block
     * 
     * @var BlockStatement 
     */
    protected $finalizer;
    
    /**
     * Returns the wrapped block
     * 
     * @return BlockStatement
     */
    public function getBlock()
    {
        return $this->block;
    }
    
    /**
     * Sets the wrapped block
     * 
     * @param BlockStatement $block Wrapped block
     * 
     * @return $this
     */
    public function setBlock(BlockStatement $block)
    {
        $this->block = $block;
        return $this;
    }
    
    /**
     * Returns the catch clause
     * 
     * @return CatchClause
     */
    public function getHandler()
    {
        return $this->handler;
    }
    
    /**
     * Sets the catch clause
     * 
     * @param CatchClause $handler Catch clause
     * 
     * @return $this
     */
    public function setHandler($handler)
    {
        $this->assertType($handler, "CatchClause", true);
        $this->handler = $handler;
        return $this;
    }
    
    /**
     * Returns the "finally" block
     * 
     * @return BlockStatement
     */
    public function getFinalizer()
    {
        return $this->finalizer;
    }
    
    /**
     * Sets the "finally" block
     * 
     * @param BlockStatement $finalizer The "finally" block
     * 
     * @return $this
     */
    public function setFinalizer($finalizer)
    {
        $this->assertType($finalizer, "BlockStatement", true);
        $this->finalizer = $finalizer;
        return $this;
    }
}
