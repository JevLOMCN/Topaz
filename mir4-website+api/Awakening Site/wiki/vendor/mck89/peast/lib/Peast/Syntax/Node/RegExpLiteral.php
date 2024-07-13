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
 * A node that represents a regular expression literal.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class RegExpLiteral extends Literal
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "flags" => false,
        "pattern" => false
    );
    
    /**
     * Regex flags
     * 
     * @var string 
     */
    protected $flags = "";
    
    /**
     * Regex pattern
     * 
     * @var string 
     */
    protected $pattern = "";
    
    /**
     * Returns node's type
     * 
     * @return string
     */
    public function getType()
    {
        return "RegExpLiteral";
    }
    
    /**
     * Returns regex pattern
     * 
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }
    
    /**
     * Sets regex pattern
     * 
     * @param string $pattern Regex pattern
     * 
     * @return $this
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;
        return $this;
    }
    
    /**
     * Returns regex flags
     * 
     * @return string
     */
    public function getFlags()
    {
        return $this->flags;
    }
    
    /**
     * Sets regex flags
     * 
     * @param string $flags Regex flags
     * 
     * @return $this
     */
    public function setFlags($flags)
    {
        $this->flags = $flags;
        return $this;
    }
    
    /**
     * Returns node's raw value
     * 
     * @return string
     */
    public function getRaw()
    {
        return "/" . $this->getPattern() . "/" . $this->getFlags();
    }
    
    /**
     * Sets node's raw value that must include delimiters
     * 
     * @param string $raw Raw value
     * 
     * @return $this
     */
    public function setRaw($raw)
    {
        
        $parts = explode("/", substr($raw, 1));
        $flags = array_pop($parts);
        $this->setPattern(implode("/", $parts));
        $this->setFlags($flags);
        return $this;
    }
    
    /**
     * Returns node's value
     * 
     * @return string
     */
    public function getValue()
    {
        return $this->getRaw();
    }
    
    /**
     * Sets node's value
     * 
     * @param mixed $value Value
     * 
     * @return $this
     */
    public function setValue($value)
    {
        return $this->setRaw($value);
    }
}