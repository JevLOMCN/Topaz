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
 * A node that represents in import declaration.
 * For example: import a from "mod"
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class ImportDeclaration extends Node implements ModuleDeclaration
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "specifiers" => true,
        "source" => true
    );
    
    /**
     * Import specifiers array
     * 
     * @var array
     */
    protected $specifiers = array();
    
    /**
     * Import source
     * 
     * @var Literal 
     */
    protected $source;
    
    /**
     * Returns the import specifiers array
     * 
     * @return array
     */
    public function getSpecifiers()
    {
        return $this->specifiers;
    }
    
    /**
     * Sets the import specifiers array
     * 
     * @param array $specifiers Import specifiers array
     * 
     * @return $this
     */
    public function setSpecifiers($specifiers)
    {
        $this->assertArrayOf(
            $specifiers,
            array(
                "ImportSpecifier",
                "ImportDefaultSpecifier",
                "ImportNamespaceSpecifier"
            )
        );
        $this->specifiers = $specifiers;
        return $this;
    }
    
    /**
     * Returns the import source
     * 
     * @return Literal
     */
    public function getSource()
    {
        return $this->source;
    }
    
    /**
     * Sets the import source
     * 
     * @param Literal $source Import source
     * 
     * @return $this
     */
    public function setSource(Literal $source)
    {
        $this->source = $source;
        return $this;
    }
}