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
 * A node that represents a template literal.
 * For example: `this is a ${test()} template`
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class TemplateLiteral extends Node implements Expression
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "parts" => true,
        "quasis" => false,
        "expressions" => false
    );
    
    /**
     * Array of quasis that are the literal parts of the template
     * 
     * @var TemplateElement[] 
     */
    protected $quasis = array();
    
    /**
     * Array of expressions inside the template
     * 
     * @var Expression[] 
     */
    protected $expressions = array();
    
    /**
     * Returns the array of quasis that are the literal parts of the template
     * 
     * @return TemplateElement[] 
     */
    public function getQuasis()
    {
        return $this->quasis;
    }
    
    /**
     * Sets the array of quasis that are the literal parts of the template
     * 
     * @param TemplateElement[] $quasis Quasis
     * 
     * @return $this
     */
    public function setQuasis($quasis)
    {
        $this->assertArrayOf($quasis, "TemplateElement");
        $this->quasis = $quasis;
        return $this;
    }
    
    /**
     * Returns the array of expressions inside the template
     * 
     * @return Expression[]
     */
    public function getExpressions()
    {
        return $this->expressions;
    }
    
    /**
     * Sets the array of expressions inside the template
     * 
     * @param Expression[] $expressions Expressions
     * 
     * @return $this
     */
    public function setExpressions($expressions)
    {
        $this->assertArrayOf($expressions, "Expression");
        $this->expressions = $expressions;
        return $this;
    }
    
    /**
     * Returns an array of the template parts (quasis and expressions)
     * 
     * @return array
     */
    public function getParts()
    {
        // It must be a list of quasis and expressions alternated
        $parts = array();
        foreach ($this->quasis as $k => $val) {
            $parts[] = $val;
            if (isset($this->expressions[$k])) {
                $parts[] = $this->expressions[$k];
            }
        }
        return $parts;
    }
    
    /**
     * Sets the array of the template parts (quasis and expressions)
     * 
     * @param array Template parts
     * 
     * @return $this
     */
    public function setParts($parts)
    {
        $this->assertArrayOf($parts, array("Expression", "TemplateElement"));
        $quasis = $expressions = array();
        foreach ($parts as $part) {
            if ($part instanceof TemplateElement) {
                $quasis[] = $part;
            } else {
                $expressions[] = $part;
            }
        }
        return $this->setQuasis($quasis)->setExpressions($expressions);
    }
    
    /**
     * Returns a serializable version of the node
     * 
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $ret = parent::jsonSerialize();
        unset($ret["parts"]);
        return $ret;
    }
}