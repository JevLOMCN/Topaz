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

use Peast\Selector\Matches;
use Peast\Selector\Node\Selector;
use Peast\Syntax\Node\Node;

/**
 * Selector part selector pseudo class
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class PseudoSelector extends Pseudo
{
    /**
     * Priority
     *
     * @var int
     */
    protected $priority = 1;

    /**
     * Selector
     *
     * @var Selector
     */
    protected $selector;

    /**
     * Sets the selector
     *
     * @param Selector $selector Selector
     *
     * @return $this
     */
    public function setSelector(Selector $selector)
    {
        $this->selector = $selector;
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
        $match = new Matches();
        $match->addMatch($node, $parent);
        $res = $this->selector->exec($match)->count();
        return $this->name === "not" ? $res === 0 : $res !== 0;
    }
}