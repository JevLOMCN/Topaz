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
 * Syntax exception class. Syntax errors in the source are thrown using this
 * using this exception class.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 * 
 * @codeCoverageIgnore
 */
class Exception extends \Exception
{
    /**
     * Error position
     * 
     * @var Position
     */
    protected $position;
    
    /**
     * Class constructor
     * 
     * @param string   $message  Error message
     * @param Position $position Error position
     */
    public function __construct($message, Position $position)
    {
        parent::__construct($message);
        $this->position = $position;
    }
    
    /**
     * Returns the error position
     * 
     * @return Position
     */
    public function getPosition()
    {
        return $this->position;
    }
}