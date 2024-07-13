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
 * A node that represents an identifier.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class Identifier extends Node implements Expression, Pattern
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "name" => false,
        "rawName" => false,
    );
    
    /**
     * The identifier's name
     * 
     * @var string
     */
    protected $name;
    
    /**
     * The identifier's raw name
     * 
     * @var string
     */
    protected $rawName;
    
    /**
     * Returns the identifier's name
     * 
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Sets the identifier's name
     * 
     * @param string $name The name to set
     * 
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $this->rawName = $name;
        return $this;
    }
    
    /**
     * Returns the identifier's raw name
     * 
     * @return string
     */
    public function getRawName()
    {
        return $this->rawName;
    }
    
    /**
     * Sets the identifier's raw name
     * 
     * @param string $name The raw name to set
     * 
     * @return $this
     */
    public function setRawName($name)
    {
        $this->rawName = $name;
        if (strpos($name, "\\") !== false) {
            $this->name = preg_replace_callback(
                "#\\\\u(?:\{([a-fA-F0-9]+)\}|([a-fA-F0-9]{4}))#",
                function ($match) {
                    return Utils::unicodeToUtf8(hexdec($match[1] ? : $match[2]));
                },
                $name
            );
        } else {
            $this->name = $name;
        }
        return $this;
    }
}