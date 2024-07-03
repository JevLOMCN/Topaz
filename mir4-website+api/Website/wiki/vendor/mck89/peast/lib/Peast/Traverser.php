<?php
/**
 * This file is part of the Peast package
 *
 * (c) Marco Marchiò <marco.mm89@gmail.com>
 *
 * For the full copyright and license information refer to the LICENSE file
 * distributed with this source code
 */
namespace Peast;

/**
 * Nodes traverser class
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class Traverser
{
    /**
     * If a function return this value, the current node will be removed
     */
    const REMOVE_NODE = 1;
    
    /**
     * If a function return this value, the current node's children won't be
     * traversed
     */
    const DONT_TRAVERSE_CHILD_NODES = 2;
    
    /**
     * If a function return this value, the traverser will stop running
     */
    const STOP_TRAVERSING = 4;
    
    /**
     * Array of functions to call on each node
     * 
     * @var array
     */
    protected $functions = array();

    /**
     * Pass parent node flag
     *
     * @var bool
     */
    protected $passParentNode = false;

    /**
     * Skip starting node flag
     *
     * @var bool
     */
    protected $skipStartingNode = false;

    /**
     * Class constructor. Available options are:
     * - skipStartingNode: if true the starting node will be skipped
     * - passParentNode: if true the parent node of each node will be
     *   passed as second argument when the functions are called. Note
     *   that the parent node is calculated during traversing, so for
     *   the starting node it will always be null.
     *
     * @param array $options Options array
     */
    public function __construct($options = array())
    {
        if (isset($options["passParentNode"])) {
            $this->passParentNode = (bool) $options["passParentNode"];
        }
        if (isset($options["skipStartingNode"])) {
            $this->skipStartingNode = (bool) $options["skipStartingNode"];
        }
    }
    
    /**
     * Adds a function that will be called for each node in the tree. The
     * function will receive the current node as argument. The action that will
     * be executed on the node by the traverser depends on the returned value
     * of the function:
     * - a node: it will replace the node with the returned one
     * - a numeric value that is a combination of the constants defined in this
     *   class: it will execute the function related to each constant
     * - an array where the first element is a node and the second element is a
     *   numeric value that is a combination of the constants defined in this
     *   class: it will replace the node with the returned one and  it will
     *   execute the function related to each constant (REMOVE_NODE will be
     *   ignored since it does not make any sense in this case)
     * - other: nothing
     * 
     * @param callable $fn Function to add
     * 
     * @return $this
     */
    public function addFunction(callable $fn)
    {
        $this->functions[] = $fn;
        return $this;
    }
    
    /**
     * Starts the traversing
     * 
     * @param Syntax\Node\Node  $node   Starting node
     * 
     * @return Syntax\Node\Node
     */
    public function traverse(Syntax\Node\Node $node)
    {
        if ($this->skipStartingNode) {
            $this->traverseChildren($node);
        } else {
            $this->execFunctions($node);
        }
        return $node;
    }
    
    /**
     * Executes all functions on the given node and, if required, starts
     * traversing its children. The returned value is an array where the first
     * value is the node or null if it has been removed and the second value is
     * a boolean indicating if the traverser must continue the traversing or not
     * 
     * @param Syntax\Node\Node       $node     Node
     * @param Syntax\Node\Node|null  $parent   Parent node
     * 
     * @return array
     */
    protected function execFunctions($node, $parent = null)
    {
        $traverseChildren = true;
        $continueTraversing = true;
        
        foreach ($this->functions as $fn) {
            $ret = $this->passParentNode ? $fn($node, $parent) : $fn($node);
            if ($ret) {
                if (is_array($ret) && $ret[0] instanceof Syntax\Node\Node) {
                    $node = $ret[0];
                    if (isset($ret[1]) && is_numeric($ret[1])) {
                        if ($ret[1] & self::DONT_TRAVERSE_CHILD_NODES) {
                            $traverseChildren = false;
                        }
                        if ($ret[1] & self::STOP_TRAVERSING) {
                            $continueTraversing = false;
                        }
                    }
                } elseif ($ret instanceof Syntax\Node\Node) {
                    $node = $ret;
                } elseif (is_numeric($ret)) {
                    if ($ret & self::DONT_TRAVERSE_CHILD_NODES) {
                        $traverseChildren = false;
                    }
                    if ($ret & self::STOP_TRAVERSING) {
                        $continueTraversing = false;
                    }
                    if ($ret & self::REMOVE_NODE) {
                        $node = null;
                        $traverseChildren = false;
                        break;
                    }
                }
            }
        }
        
        if ($traverseChildren && $continueTraversing) {
            $continueTraversing = $this->traverseChildren($node);
        }
        
        return array($node, $continueTraversing);
    }
    
    /**
     * Traverses node children. It returns a boolean indicating if the
     * traversing must continue or not
     * 
     * @param Syntax\Node\Node  $node   Node
     * 
     * @return bool
     */
    protected function traverseChildren(Syntax\Node\Node $node)
    {
        $continue = true;
        
        foreach (Syntax\Utils::getNodeProperties($node, true) as $prop) {
            $getter = $prop["getter"];
            $setter = $prop["setter"];
            $child = $node->$getter();
            if (!$child) {
                continue;
            } elseif (is_array($child)) {
                $newChildren = array();
                foreach ($child as $c) {
                    if (!$c || !$continue) {
                        $newChildren[] = $c;
                    } else {
                        list($c, $continue) = $this->execFunctions($c, $node);
                        if ($c) {
                            $newChildren[] = $c;
                        }
                    }
                }
                $node->$setter($newChildren);
            } else {
                list($child, $continue) = $this->execFunctions($child, $node);
                $node->$setter($child);
            }
            
            if (!$continue) {
                break;
            }
        }
        
        return $continue;
    }
}