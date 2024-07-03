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
 * A node that represents an export named declaration.
 * For example: export {foo} from "bar"
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class ExportNamedDeclaration extends Node implements ModuleDeclaration
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "declaration" => true,
        "specifiers" => true,
        "source" => true
    );
    
    /**
     * Exported declaration
     * 
     * @var Declaration 
     */
    protected $declaration;
    
    /**
     * Exported specifiers
     * 
     * @var ExportSpecifier[] 
     */
    protected $specifiers = array();
    
    /**
     * The export source
     * 
     * @var Literal 
     */
    protected $source;
    
    /**
     * Returns the exported declaration
     * 
     * @return Declaration
     */
    public function getDeclaration()
    {
        return $this->declaration;
    }
    
    /**
     * Sets the exported declaration
     * 
     * @param Declaration $declaration Exported declaration
     * 
     * @return $this
     */
    public function setDeclaration($declaration)
    {
        $this->assertType($declaration, "Declaration", true);
        $this->declaration = $declaration;
        return $this;
    }
    
    /**
     * Return the exported specifiers
     * 
     * @return ExportSpecifier[]
     */
    public function getSpecifiers()
    {
        return $this->specifiers;
    }
    
    /**
     * Sets the exported specifiers
     * 
     * @param ExportSpecifier[] $specifiers Exported specifiers
     * 
     * @return $this
     */
    public function setSpecifiers($specifiers)
    {
        $this->assertArrayOf($specifiers, "ExportSpecifier");
        $this->specifiers = $specifiers;
        return $this;
    }
    
    /**
     * Returns the export source
     * 
     * @return Literal
     */
    public function getSource()
    {
        return $this->source;
    }
    
    /**
     * Sets the export source
     * 
     * @param Literal $source Export source
     * 
     * @return $this
     */
    public function setSource($source)
    {
        $this->assertType($source, "Literal", true);
        $this->source = $source;
        return $this;
    }
}