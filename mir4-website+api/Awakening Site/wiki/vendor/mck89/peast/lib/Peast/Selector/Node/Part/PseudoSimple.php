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
use Peast\Syntax\Node\Pattern;
use Peast\Syntax\Node\Statement;
use Peast\Syntax\Node\Expression;
use Peast\Syntax\Node\Declaration;
use Peast\Syntax\Utils;

/**
 * Selector part simple pseudo class
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class PseudoSimple extends Pseudo
{
    /**
     * Priority
     *
     * @var int
     */
    protected $priority = 3;

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
        switch ($this->name) {
            case "pattern":
                return $node instanceof Pattern;
            case "statement":
                return $node instanceof Statement;
            case "expression":
                return $node instanceof Expression;
            case "declaration":
                return $node instanceof Declaration;
            case "last-child":
            case "first-child":
                $first = $this->name === "first-child";
                $props = Utils::getExpandedNodeProperties($parent);
                return count($props) > 0 && (
                    $first ? $props[0] === $node : array_pop($props) === $node
                );
        }
    }
}