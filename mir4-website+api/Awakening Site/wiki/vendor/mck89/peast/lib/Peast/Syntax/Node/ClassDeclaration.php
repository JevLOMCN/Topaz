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
 * A node that represents a class declaration.
 * For example: class test {}
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class ClassDeclaration extends Class_ implements Declaration
{
    /**
     * Sets the class identifier
     * 
     * @param Identifier $id Class identifier
     * 
     * @return $this
     */
    public function setId($id)
    {
        $this->assertType($id, "Identifier");
        $this->id = $id;
        return $this;
    }
}