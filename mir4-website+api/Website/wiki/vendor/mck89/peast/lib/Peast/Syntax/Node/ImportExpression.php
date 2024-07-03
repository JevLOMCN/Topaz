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
 * A node that represents an import expression (dynamic import).
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class ImportExpression extends Node implements Expression
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "source" => true
    );
    
    /**
     * The catch clause parameter
     * 
     * @var Expression
     */
    protected $source;
    
    /**
     * Returns the import source
     * 
     * @return Expression
     */
    public function getSource()
    {
        return $this->source;
    }
    
    /**
     * Sets the import source
     * 
     * @param Expression $source Import source
     * 
     * @return $this
     */
    public function setSource(Expression $source)
    {
        $this->source = $source;
        return $this;
    }
}