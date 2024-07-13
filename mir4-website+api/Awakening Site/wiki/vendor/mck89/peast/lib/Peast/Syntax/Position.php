<?php
/**
 * This file is part of the Peast package
 *
 * (c) Marco Marchiò <marco.mm89@gmail.com>
 *
 * For the full copyright and license information refer to the LICENSE file
 * distributed with this source code
 */
namespace Peast\Syntax;

/**
 * This class represents the position in the source code.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class Position implements \JSONSerializable
{
    /**
     * Source line
     * 
     * @var int 
     */
    protected $line;
    
    /**
     * Source column
     * 
     * @var int 
     */
    protected $column;
    
    /**
     * Source index
     * 
     * @var int 
     */
    protected $index;
    
    /**
     * Class constructor
     * 
     * @param int $line   Source line
     * @param int $column Source column
     * @param int $index  Source index
     */
    function __construct($line, $column, $index)
    {
        $this->line = $line;
        $this->column = $column;
        $this->index = $index;
    }
    
    /**
     * Returns the source line
     * 
     * @return int
     */
    public function getLine()
    {
        return $this->line;
    }
    
    /**
     * Returns the source column
     * 
     * @return int
     */
    public function getColumn()
    {
        return $this->column;
    }
    
    /**
     * Returns the source index
     * 
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }
    
    /**
     * Returns a serializable version of the object
     * 
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return array(
            "line" => $this->getLine(),
            "column" => $this->getColumn(),
            "index" => $this->getIndex()
        );
    }
}