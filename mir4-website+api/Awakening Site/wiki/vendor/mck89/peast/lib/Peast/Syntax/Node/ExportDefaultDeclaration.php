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
 * A node that represents the export default declaration.
 * For example: export default a
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class ExportDefaultDeclaration extends Node implements ModuleDeclaration
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "declaration" => true
    );
    
    /**
     * The exported declaration
     * 
     * @var Declaration|Expression
     */
    protected $declaration;
    
    /**
     * Returns the exported declaration
     * 
     * @return Declaration|Expression
     */
    public function getDeclaration()
    {
        return $this->declaration;
    }
    
    /**
     * Sets the exported declaration
     * 
     * @param Declaration|Expression $declaration The exported declaration
     * 
     * @return $this
     */
    public function setDeclaration($declaration)
    {
        $this->assertType($declaration, array("Declaration", "Expression"));
        $this->declaration = $declaration;
        return $this;
    }
}