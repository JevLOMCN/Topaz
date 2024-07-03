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
 * A node that represents an arrow function.
 * For example: var fn = (a, b) => console.log(a, b)
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class ArrowFunctionExpression extends Function_ implements Expression
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "expression" => false
    );
    
    /**
     * This flag is true when function body is wrapped in curly braces
     * 
     * @var bool
     */
    protected $expression = false;
    
    /**
     * Sets the function body
     * 
     * @param BlockStatement|Expression $body Function body
     * 
     * @return $this
     */
    public function setBody($body)
    {
        $this->assertType($body, array("BlockStatement", "Expression"));
        $this->body = $body;
        return $this;
    }
    
    /**
     * Returns the expression flag
     * 
     * @return bool
     */
    public function getExpression()
    {
        return $this->expression;
    }
    
    /**
     * Sets the expression flag
     * 
     * @param bool $expression Expression flag
     * 
     * @return $this
     */
    public function setExpression($expression)
    {
        $this->expression = (bool) $expression;
        return $this;
    }
}