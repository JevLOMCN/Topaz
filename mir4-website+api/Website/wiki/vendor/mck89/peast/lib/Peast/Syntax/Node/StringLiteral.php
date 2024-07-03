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

use Peast\Syntax\Utils;

/**
 * A node that represents a string literal.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class StringLiteral extends Literal
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "format" => false
    );
    
    //Format constants
    /**
     * Double quoted string
     */
    const DOUBLE_QUOTED = "double";
    
    /**
     * Single quoted string
     */
    const SINGLE_QUOTED = "single";
    
    /**
     * String format
     * 
     * @var string
     */
    protected $format = self::DOUBLE_QUOTED;
    
    /**
     * Sets node's value
     * 
     * @param string $value Value
     * 
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = (string) $value;
        //Force recalculation of the raw value
        return $this->setFormat($this->format);
    }
    
    /**
     * Sets node's raw value
     * 
     * @param mixed $raw Raw value
     * 
     * @return $this
     * 
     * @throws \Exception
     */
    public function setRaw($raw)
    {
        if (!is_string($raw) || strlen($raw) < 2) {
            throw new \Exception("Invalid string");
        }
        $startQuote = $raw[0];
        $endQuote = substr($raw, -1);
        if (($startQuote !== "'" && $startQuote !== '"') ||
            $startQuote !== $endQuote
        ) {
            throw new \Exception("Invalid string");
        }
        $this->value = Utils::unquoteLiteralString($raw);
        $this->setFormat($raw[0] === "'" ?
            self::SINGLE_QUOTED :
            self::DOUBLE_QUOTED
        );
        $this->raw = $raw;
        return $this;
    }
    
    /**
     * Returns string format
     * 
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }
    
    /**
     * Sets string format
     * 
     * @param string $format Format, one of the format constants
     * 
     * @return $this
     */
    public function setFormat($format)
    {
        $this->format = $format;
        $quote = $format === self::SINGLE_QUOTED ? "'" : '"';
        $this->raw = Utils::quoteLiteralString($this->value, $quote);
        return $this;
    }
}