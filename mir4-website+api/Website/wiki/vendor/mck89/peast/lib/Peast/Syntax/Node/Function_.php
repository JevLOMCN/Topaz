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
 * Abstract class for functions.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
abstract class Function_ extends Node
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "id" => true,
        "params" => true,
        "body" => true,
        "generator" => false,
        "async" => false
    );
    
    /**
     * Function name
     * 
     * @var Identifier 
     */
    protected $id;
    
    /**
     * Function parameters array
     * 
     * @var Pattern[] 
     */
    protected $params = array();
    
    /**
     * Function body
     * 
     * @var BlockStatement 
     */
    protected $body;
    
    /**
     * Generator flag that is true when the function is a generator
     * 
     * @var bool 
     */
    protected $generator = false;
    
    /**
     * Async flag that is true when it is an async function
     * 
     * @var bool 
     */
    protected $async = false;
    
    /**
     * Returns function name
     * 
     * @return Identifier
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Sets function name
     * 
     * @param Identifier $id Function name
     * 
     * @return $this
     */
    public function setId($id)
    {
        $this->assertType($id, "Identifier", true);
        $this->id = $id;
        return $this;
    }
    
    /**
     * Returns function parameters array
     * 
     * @return Pattern[]
     */
    public function getParams()
    {
        return $this->params;
    }
    
    /**
     * Sets function parameters array
     * 
     * @param Pattern[] $params Function parameters array
     * 
     * @return $this
     */
    public function setParams($params)
    {
        $this->assertArrayOf($params, "Pattern");
        $this->params = $params;
        return $this;
    }
    
    /**
     * Returns function body
     * 
     * @return BlockStatement
     */
    public function getBody()
    {
        return $this->body;
    }
    
    /**
     * Sets function body
     * 
     * @param BlockStatement $body Function body
     * 
     * @return $this
     */
    public function setBody($body)
    {
        $this->assertType($body, "BlockStatement");
        $this->body = $body;
        return $this;
    }
    
    /**
     * Returns the generator flag that is true when the function is a generator
     * 
     * @return bool
     */
    public function getGenerator()
    {
        return $this->generator;
    }
    
    /**
     * Sets the generator flag that is true when the function is a generator
     * 
     * @param bool $generator Generator flag
     * 
     * @return $this
     */
    public function setGenerator($generator)
    {
        $this->generator = (bool) $generator;
        return $this;
    }
    
    /**
     * Returns the async flag that is true when it is an async function
     * 
     * @return bool
     */
    public function getAsync()
    {
        return $this->async;
    }
    
    /**
     * Sets the async flag that is true when it is an async function
     * 
     * @param bool $async Async flag
     * 
     * @return $this
     */
    public function setAsync($async)
    {
        $this->async = (bool) $async;
        return $this;
    }
}