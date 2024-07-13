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
 * A node that represents a boolean literal.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class BooleanLiteral extends Literal
{
    /**
     * Sets node's value
     * 
     * @param mixed $value Value
     * 
     * @return $this
     */
    public function setValue($value)
    {
        if ($value === "true") {
            $this->value = true;
        } elseif ($value === "false") {
            $this->value = false;
        } else {
            $this->value = (bool) $value;
        }
        $this->raw = $this->value ? "true" : "false";
        return $this;
    }
}