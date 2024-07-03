<?php
/**
 * This file is part of the Peast package
 *
 * (c) Marco Marchiò <marco.mm89@gmail.com>
 *
 * For the full copyright and license information refer to the LICENSE file
 * distributed with this source code
 */
namespace Peast\Syntax;

/**
 * Comments registry class. Internal class used to manage comments
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class CommentsRegistry
{
    /**
     * Map of the indices where nodes start
     * 
     * @var int 
     */
    protected $nodesStartMap = array();
    
    /**
     * Map of the indices where nodes end
     * 
     * @var int 
     */
    protected $nodesEndMap = array();
    
    /**
     * Comments buffer
     * 
     * @var array
     */
    protected $buffer = null;
    
    /**
     * Last token index
     * 
     * @var int
     */
    protected $lastTokenIndex = null;
    
    /**
     * Comments registry
     * 
     * @var array
     */
    protected $registry = array();
    
    /**
     * Class constructor
     * 
     * @param Parser    $parser     Parser
     */
    public function __construct(Parser $parser)
    {
        $parser->getEventsEmitter()
               ->addListener("NodeCompleted", array($this, "onNodeCompleted"))
               ->addListener("EndParsing", array($this, "onEndParsing"));
        
        $parser->getScanner()->getEventsEmitter()
               ->addListener("TokenConsumed", array($this, "onTokenConsumed"))
               ->addListener("EndReached", array($this, "onTokenConsumed"))
               ->addListener("FreezeState", array($this, "onScannerFreezeState"))
               ->addListener("ResetState", array($this, "onScannerResetState"));
    }
    
    /**
     * Listener called every time the scanner compose the array that represents
     * its current state
     * 
     * @param array   $state   State
     * 
     * @return void
     */
    public function onScannerFreezeState(&$state)
    {
        //Register the current last token index
        $state["commentsLastTokenIndex"] = $this->lastTokenIndex;
    }
    
    /**
     * Listener called every time the scanner reset its state using the given
     * array
     * 
     * @param array   $state   State
     * 
     * @return void
     */
    public function onScannerResetState(&$state)
    {
        //Reset the last token index and delete it from the state array
        $this->lastTokenIndex = $state["commentsLastTokenIndex"];
        unset($state["commentsLastTokenIndex"]);
    }
    
    /**
     * Listener called every time a token is consumed and when the scanner
     * reaches the end of the source
     * 
     * @param Token|null   $token   Consumed token or null if the end has
     *                              been reached
     * 
     * @return void
     */
    public function onTokenConsumed(Token $token = null)
    {
        //Check if it's a comment
        if ($token && $token->type === Token::TYPE_COMMENT) {
            //If there is not an open comments buffer, create it
            if (!$this->buffer) {
                $this->buffer = array(
                    "prev" => $this->lastTokenIndex,
                    "next" => null,
                    "comments" => array()
                );
            }
            //Add the comment token to the buffer
            $this->buffer["comments"][] = $token;
        } else {
            
            if ($token) {
                $loc = $token->location;
                //Store the token end position
                $this->lastTokenIndex = $loc->end->getIndex();
                if ($this->buffer) {
                    //Fill the "next" key on the comments buffer with the token
                    //start position
                    $this->buffer["next"] = $loc->start->getIndex();
                }
            }
            
            //If there is an open comment buffer, close it and move it to the
            //registry
            if ($buffer = $this->buffer) {
                //Use the location as key to add the group of comments to the
                //registry, in this way if comments are reprocessed they won't
                //be duplicated
                $key = implode("-", array(
                    $buffer["prev"] !== null ? $buffer["prev"] : "",
                    $buffer["next"] !== null ? $buffer["next"] : ""
                ));
                $this->registry[$key] = $this->buffer;
                $this->buffer = null;
            }
            
        }
    }
    
    /**
     * Listener called every time a node is completed by the parser
     * 
     * @param Node\Node   $node     Completed node
     * 
     * @return void
     */
    public function onNodeCompleted(Node\Node $node)
    {
        //Every time a node is completed, register its start and end indices
        //in the relative properties
        $loc = $node->location;
        foreach (array("Start", "End") as $pos) {
            $val = $loc->{"get$pos"}()->getIndex();
            $map = &$this->{"nodes{$pos}Map"};
            if (!isset($map[$val])) {
                $map[$val] = array();
            }
            $map[$val][] = $node;
        }
    }
    
    /**
     * Listener called when parsing process ends
     * 
     * @return void
     */
    public function onEndParsing()
    {
        //Return if there are no comments to process
        if ($this->registry) {
            
            //Make sure nodes start indices map is sorted
            ksort($this->nodesStartMap);
            
            //Loop all comment groups in the registry
            foreach ($this->registry as $group) {
                $this->findNodeForCommentsGroup($group);
            }
        }
    }
    
    /**
     * Finds the node to attach the given comments group
     * 
     * @param array    $group   Comments group
     * 
     * @return void
     */
    public function findNodeForCommentsGroup($group)
    {
        $next = $group["next"];
        $prev = $group["prev"];
        $comments = $group["comments"];
        $leading = true;
        
        //If the group of comments has a next token index that appears
        //in the map of start node indices, add the group to the
        //corresponding node's leading comments. This associates
        //comments that appear immediately before a node.
        //For example: /*comment*/ for (;;){}
        if (isset($this->nodesStartMap[$next])) {
            $nodes = $this->nodesStartMap[$next];
        }
        //If the group of comments has a previous token index that appears
        //in the map of end node indices, add the group to the
        //corresponding node's trailing comments. This associates
        //comments that appear immediately after a node.
        //For example: for (;;){} /*comment*/ 
        elseif (isset($this->nodesEndMap[$prev])) {
            $nodes = $this->nodesEndMap[$prev];
            $leading = false;
        }
        //Otherwise, find a node that wraps the comments position.
        //This associates inner comments:
        //For example: for /*comment*/ (;;){}
        else {
            //Calculate comments group boundaries
            $start = $comments[0]->location->start->getIndex();
            $end = $comments[count($comments) -1]->location->end->getIndex();
            $nodes = array();
            
            //Loop all the entries in the start index map
            foreach ($this->nodesStartMap as $idx => $ns) {
                //If the index is higher than the start index of the comments
                //group, stop
                if ($idx > $start) {
                    break;
                }
                foreach ($ns as $node) {
                    //Check if the comments group is inside node indices range
                    if ($node->location->end->getIndex() >= $end) {
                        $nodes[] = $node;
                    }
                }
            }
            
            //If comments can't be associated with any node, associate it as
            //leading comments of the program, this happens when the source is
            //empty
            if (!$nodes) {
                $firstNode = array_values($this->nodesStartMap);
                $nodes = array($firstNode[0][0]);
            }
        }
        
        //If there are multiple possible nodes to associate the comments to,
        //find the shortest one
        if (count($nodes) > 1) {
            usort($nodes, array($this, "compareNodesLength"));
        }
        $this->associateComments($nodes[0], $comments, $leading);
    }
    
    /**
     * Compares node length 
     * 
     * @param Node\Node  $node1     First node
     * @param Node\Node  $node2     Second node
     * 
     * @return int
     * 
     * @codeCoverageIgnore
     */
    public function compareNodesLength($node1, $node2)
    {
        $loc1 = $node1->location;
        $length1 = $loc1->end->getIndex() - $loc1->start->getIndex();
        $loc2 = $node2->location;
        $length2 = $loc2->end->getIndex() - $loc2->start->getIndex();
        //If the nodes have the same length make sure to choose nodes
        //different from Program nodes
        if ($length1 === $length2) {
            if ($node1 instanceof Node\Program) {
                $length1 += 1000;
            } elseif ($node2 instanceof Node\Program) {
                $length2 += 1000;
            }
        }
        return $length1 < $length2 ? -1 : 1;
    }
    
    /**
     * Adds comments to the given node
     * 
     * @param Node\Node     $node       Node
     * @param array         $comments   Array of comments to add
     * @param bool          $leading    True to add comments as leading comments
     *                                  or false to add them as trailing comments
     * 
     * @return void
     */
    public function associateComments($node, $comments, $leading)
    {
        $fn = ($leading ? "Leading" : "Trailing") . "Comments";
        $currentComments = $node->{"get$fn"}();
        foreach ($comments as $comment) {
            $loc = $comment->location;
            $commentNode = new Node\Comment;
            $commentNode->location->start = $loc->start;
            $commentNode->location->end = $loc->end;
            $commentNode->setRawText($comment->value);
            $currentComments[] = $commentNode;
        }
        $node->{"set$fn"}($currentComments);
    }
}