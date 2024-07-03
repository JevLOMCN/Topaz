<?php
/**
 * This file is part of the Peast package
 *
 * (c) Marco Marchiò <marco.mm89@gmail.com>
 *
 * For the full copyright and license information refer to the LICENSE file
 * distributed with this source code
 */
namespace Peast\Selector;

use Peast\Syntax\Node\Node;

/**
 * Selector matches class
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class Matches
{
    /**
     * Matches array
     *
     * @var array
     */
    protected $matches;

    /**
     * Class constructor
     *
     * @param array $matches Matches
     */
    public function __construct($matches = array())
    {
        $this->matches = $matches;
    }

    /**
     * Adds a new match
     *
     * @param Node $node
     * @param Node|null $parent
     */
    public function addMatch(Node $node, Node $parent = null)
    {
        $this->matches[] = array($node, $parent);
    }

    /**
     * Returns the matches
     *
     * @returns array
     */
    public function getMatches()
    {
        return $this->matches;
    }

    /**
     * Returns the matched nodes
     *
     * @return array
     */
    public function getNodes() {
        return array_map(function ($m) {
            return $m[0];
        }, $this->matches);
    }

    /**
     * Filters the matches using the given function, if it returns
     * a false value the match will be removed. The function will
     * receive the node and its parent as arguments.
     *
     * @param callable $fn Filter function
     * @return $this
     */
    public function filter(callable $fn)
    {
        $newMatches = array();
        foreach ($this->matches as $match) {
            if ($fn($match[0], $match[1])) {
                $newMatches[] = $match;
            }
        }
        $this->matches = $newMatches;
        return $this;
    }

    /**
     * Replaces all the matches with the result of the given function.
     * The function will receive the node and its parent as arguments
     * and must return an array of matches
     *
     * @param callable $fn Map function
     *
     * @return $this
     */
    public function map(callable $fn)
    {
        $newMatches = array();
        foreach ($this->matches as $match) {
            $res = $fn($match[0], $match[1]);
            if ($res) {
                $newMatches = array_merge($newMatches, $res);
            }
        }
        $this->matches = $newMatches;
        return $this->unique();
    }

    /**
     * Merges the current object with the other given Matches objects
     *
     * @param Matches[] $matchesArr Array of Matches to merge
     *
     * @return $this
     */
    public function merge($matchesArr)
    {
        foreach ($matchesArr as $matches) {
            foreach ($matches->getMatches() as $match) {
                $this->addMatch($match[0], $match[1]);
            }
        }
        return $this->unique();
    }

    /**
     * Remove all duplicated matches
     *
     * @return $this
     */
    public function unique()
    {
        $newMatches = array();
        $newNodes = array();
        foreach ($this->matches as $match) {
            if (!in_array($match[0], $newNodes, true)) {
                $newMatches[] = $match;
                $newNodes[] = $match[0];
            }
        }
        $this->matches = $newMatches;
        return $this;
    }

    /**
     * Returns a clone of the current object
     *
     * @return Matches
     */
    public function createClone()
    {
        return new self($this->matches);
    }

    /**
     * Returns the number of matches
     *
     * @return int
     */
    public function count()
    {
        return count($this->matches);
    }

    /**
     * Returns the match at the given index
     *
     * @param int $index Index
     *
     * @return array
     *
     * @throws \Exception
     */
    public function get($index)
    {
        $index = (int) $index;
        if (!isset($this->matches[$index])) {
            throw new \Exception("Invalid index $index");
        }
        return $this->matches[$index];
    }
}