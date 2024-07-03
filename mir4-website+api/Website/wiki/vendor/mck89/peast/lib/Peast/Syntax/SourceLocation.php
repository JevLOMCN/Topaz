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
 * This class represents a location in the source code with start and end
 * position.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class SourceLocation implements \JSONSerializable
{
    /**
     * Start position
     * 
     * @var Position 
     */
    public $start;
    
    /**
     * End position
     * 
     * @var Position 
     */
    public $end;
    
    /**
     * Returns the start position
     * 
     * @return Position
     */
    public function getStart()
    {
        return $this->start;
    }
    
    /**
     * Sets the start position
     * 
     * @param Position $position Start position
     * 
     * @return $this
     */
    public function setStart(Position $position)
    {
        $this->start = $position;
        return $this;
    }
    
    /**
     * Returns the end position
     * 
     * @return Position
     */
    public function getEnd()
    {
        return $this->end;
    }
    
    /**
     * Sets the end position
     * 
     * @param Position $position End position
     * 
     * @return $this
     */
    public function setEnd(Position $position)
    {
        $this->end = $position;
        return $this;
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
            "start" => $this->start,
            "end" => $this->end
        );
    }
}