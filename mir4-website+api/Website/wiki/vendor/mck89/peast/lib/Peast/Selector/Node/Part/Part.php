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

/**
 * Selector part base class
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 *
 * @abstract
 */
abstract class Part
{
    /**
     * Priority
     *
     * @var int
     */
    protected $priority = 5;

    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Returns true if the selector part matches the given node,
     * false otherwise
     *
     * @param Node $node    Node
     * @param Node $parent  Parent node
     *
     * @return bool
     *
     * @abstract
     */
    abstract public function check(Node $node, Node $parent = null);
}