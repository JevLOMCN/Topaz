<?php
/**
 * This file is part of the Peast package
 *
 * (c) Marco Marchiò <marco.mm89@gmail.com>
 *
 * For the full copyright and license information refer to the LICENSE file
 * distributed with this source code
 */
namespace Peast\Selector\Node\Part;

use Peast\Syntax\Node\Node;
use Peast\Syntax\Utils;

/**
 * Selector part attribute class
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class Attribute extends Part
{
    /**
     * Priority
     *
     * @var int
     */
    protected $priority = 4;

    /**
     * Attribute names
     *
     * @var array
     */
    protected $names = array();

    /**
     * Attribute match operator
     *
     * @var array
     */
    protected $operator = null;

    /**
     * Attribute value
     *
     * @var mixed
     */
    protected $value = null;

    /**
     * Case insensitive flag
     *
     * @var bool
     */
    protected $caseInsensitive = false;

    /**
     * Regex flag
     *
     * @var bool
     */
    protected $regex = false;

    /**
     * Adds a name
     *
     * @param string $name Name
     *
     * @return $this
     */
    public function addName($name)
    {
        $this->names[] = $name;
        return $this;
    }

    /**
     * Sets the operator
     *
     * @param string $operator Operator
     *
     * @return $this
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;
        return $this;
    }

    /**
     * Sets the value
     *
     * @param mixed $value Value
     *
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Sets the case insensitive flag
     *
     * @param bool $caseInsensitive Case insensitive flag
     *
     * @return $this
     */
    public function setCaseInsensitive($caseInsensitive)
    {
        $this->caseInsensitive = $caseInsensitive;
        return $this;
    }

    /**
     * Sets the regex flag
     *
     * @param bool $regex Regex flag
     *
     * @return $this
     */
    public function setRegex($regex)
    {
        $this->regex = $regex;
        return $this;
    }

    /**
     * Returns true if the selector part matches the given node,
     * false otherwise
     *
     * @param Node $node    Node
     * @param Node $parent  Parent node
     *
     * @return bool
     */
    public function check(Node $node, Node $parent = null)
    {
        $attr = $node;
        foreach ($this->names as $name) {
            $attrFound = false;
            if ($attr instanceof Node) {
                $props = Utils::getNodeProperties($attr);
                foreach ($props as $prop) {
                    if ($prop["name"] === $name) {
                        $attrFound = true;
                        $attr = $attr->{$prop["getter"]}();
                        break;
                    }
                }
            }
            if (!$attrFound) {
                return false;
            }
        }
        $bothStrings = is_string($attr) && is_string($this->value);
        switch ($this->operator) {
            case "=":
                if ($bothStrings) {
                    if ($this->regex) {
                        return preg_match($this->value, $attr);
                    }
                    return $this->compareStr(
                        $this->value, $attr, $this->caseInsensitive, true, true
                    );
                }
                if (is_int($attr) && is_float($this->value)) {
                    return (float) $attr === $this->value;
                }
                return $attr === $this->value;
            case "<":
                if (is_float($this->value) && !is_float($attr) && !is_int($attr) && !is_string($attr)) {
                    return false;
                }
                return $attr < $this->value;
            case ">":
                if (is_float($this->value) && !is_float($attr) && !is_int($attr) && !is_string($attr)) {
                    return false;
                }
                return $attr > $this->value;
            case "<=":
                if (is_float($this->value) && !is_float($attr) && !is_int($attr) && !is_string($attr)) {
                    return false;
                }
                return $attr <= $this->value;
            case ">=":
                if (is_float($this->value) && !is_float($attr) && !is_int($attr) && !is_string($attr)) {
                    return false;
                }
                return $attr >= $this->value;
            case "^=":
            case "$=":
            case "*=":
                return $this->compareStr(
                    $this->value, $attr, $this->caseInsensitive,
                    $this->operator === "^=",
                    $this->operator === "$="
                );
            default:
                return true;
        }
    }

    /**
     * Compares two strings
     *
     * @param string $v1                Search value
     * @param string $v2                Compare value
     * @param bool   $caseInsensitive   True if the search must be case insensitive
     * @param bool   $matchStart        True if the search must be executed from the
     *                                  beginning of the string
     * @param bool   $matchEnd          True if the search must be executed from the
     *                                  end of the string
     *
     * @return bool
     */
    protected function compareStr($v1, $v2, $caseInsensitive, $matchStart, $matchEnd)
    {
        $regex = "#" .
                 ($matchStart ? "^" : "") .
                 preg_quote($v1) .
                 ($matchEnd ? "$" : "") .
                 "#u" .
                 ($caseInsensitive ? "i" : "");
        return (bool) preg_match($regex, $v2);
    }
}