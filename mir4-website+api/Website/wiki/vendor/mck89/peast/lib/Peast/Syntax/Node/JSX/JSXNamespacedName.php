<?php
/**
 * This file is part of the Peast package
 *
 * (c) Marco Marchiò <marco.mm89@gmail.com>
 *
 * For the full copyright and license information refer to the LICENSE file
 * distributed with this source code
 */
namespace Peast\Syntax\Node\JSX;

use Peast\Syntax\Node\Node;
use Peast\Syntax\Node\Expression;

/**
 * A node that represents a JSX namespaced name.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class JSXNamespacedName extends Node implements Expression
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "namespace" => false,
        "name" => false
    );
    
    /**
     * Node's namespace
     * 
     * @var JSXIdentifier
     */
    protected $namespace;
    
    /**
     * Node's name
     * 
     * @var JSXIdentifier
     */
    protected $name;
    
    /**
     * Returns node's namespace
     * 
     * @return JSXIdentifier
     */
    public function getNamespace()
    {
        return $this->namespace;
    }
    
    /**
     * Sets node's namespace
     * 
     * @param JSXIdentifier $namespace Namespace
     * 
     * @return $this
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }
    
    /**
     * Return node's name
     * 
     * @return JSXIdentifier
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Sets node's name
     * 
     * @param JSXIdentifier $name Name
     * 
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
}