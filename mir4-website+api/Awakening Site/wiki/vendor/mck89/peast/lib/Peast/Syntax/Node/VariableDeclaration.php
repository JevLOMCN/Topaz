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
 * A node that represents a "var", "const" or "let" declaration.
 * For example: var a = 1
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class VariableDeclaration extends Node implements Declaration
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "declarations" => true,
        "kind" => false
    );
    
    //Kind constants
    /**
     * "var" kind
     */
    const KIND_VAR = "var";
    
    /**
     * "let" kind
     */
    const KIND_LET = "let";
    
    /**
     * "const" kind
     */
    const KIND_CONST = "const";
    
    /**
     * Declarations array
     * 
     * @var VariableDeclarator[] 
     */
    protected $declarations = array();
    
    /**
     * Declaration kind that is one of the kind constants
     * 
     * @var string 
     */
    protected $kind = self::KIND_VAR;
    
    /**
     * Returns the declarations array
     * 
     * @return VariableDeclarator[]
     */
    public function getDeclarations()
    {
        return $this->declarations;
    }
    
    /**
     * Sets the declarations array
     * 
     * @param VariableDeclarator[] $declarations Declarations array
     * 
     * @return $this
     */
    public function setDeclarations($declarations)
    {
        $this->assertArrayOf($declarations, "VariableDeclarator");
        $this->declarations = $declarations;
        return $this;
    }
    
    /**
     * Returns the declaration kind that is one of the kind constants
     * 
     * @return string
     */
    public function getKind()
    {
        return $this->kind;
    }
    
    /**
     * Sets the declaration kind that is one of the kind constants
     * 
     * @param string $kind Declaration kind
     * 
     * @return $this
     */
    public function setKind($kind)
    {
        $this->kind = $kind;
        return $this;
    }
}