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

use Peast\Query;
use Peast\Selector;

/**
 * Root node for scripts and modules.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class Program extends Node
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "body" => true,
        "sourceType" => false
    );
    
    /**
     * Source type that is one of the source type constants in the Peast class
     * 
     * @var string 
     */
    protected $sourceType = \Peast\Peast::SOURCE_TYPE_SCRIPT;
    
    /**
     * Program's body
     * 
     * @var Statement[]|ModuleDeclaration[]
     */
    protected $body = array();
    
    /**
     * Returns the source type that is one of the source type constants in the
     * Peast class
     * 
     * @return string
     */
    public function getSourceType()
    {
        return $this->sourceType;
    }
    
    /**
     * Sets the source type that is one of the source type constants in the
     * Peast class
     * 
     * @param string $sourceType Source type
     * 
     * @return $this
     */
    public function setSourceType($sourceType)
    {
        $this->sourceType = $sourceType;
        return $this;
    }
    
    /**
     * Returns the program's body
     * 
     * @return Statement[]|ModuleDeclaration[]
     */
    public function getBody()
    {
        return $this->body;
    }
    
    /**
     * Sets the program's body
     * 
     * @param Statement[]|ModuleDeclaration[] $body Program's body
     * 
     * @return $this
     */
    public function setBody($body)
    {
        $this->assertArrayOf($body, array("Statement", "ModuleDeclaration"));
        $this->body = $body;
        return $this;
    }

    /**
     * Finds nodes matching the given selector.
     *
     * @param string    $selector   Selector
     * @param array     $options    Options array. See Query class
     *                              documentation for available options
     *
     * @return Query
     *
     * @throws Selector\Exception
     */
    public function query($selector, $options = array())
    {
        $query = new Query($this, $options);
        return $query->find($selector);
    }
}