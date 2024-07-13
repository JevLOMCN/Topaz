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
 * A node that represents a specifier in an import declaration.
 * For example "{a}" in: import {a} from "test"
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class ImportSpecifier extends ModuleSpecifier
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "imported" => true
    );
    
    /**
     * Imported identifier
     * 
     * @var Identifier|StringLiteral
     */
    protected $imported;
    
    /**
     * Returns the imported identifier
     * 
     * @return Identifier|StringLiteral
     */
    public function getImported()
    {
        return $this->imported;
    }
    
    /**
     * Sets the imported identifier
     * 
     * @param Identifier|StringLiteral $imported Imported identifier
     * 
     * @return $this
     */
    public function setImported($imported)
    {
        $this->assertType($imported, array("Identifier", "StringLiteral"));
        $this->imported = $imported;
        return $this;
    }
}