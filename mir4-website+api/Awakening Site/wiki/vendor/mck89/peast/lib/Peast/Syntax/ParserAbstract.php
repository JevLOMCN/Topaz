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
 * Base class for parsers.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 * 
 * @abstract
 */
abstract class ParserAbstract
{
    /**
     * Associated scanner
     * 
     * @var Scanner 
     */
    protected $scanner;

    /**
     * Parser features
     * 
     * @var Features
     */
    protected $features;
    
    /**
     * Parser context
     * 
     * @var \stdClass
     */
    protected $context;
    
    /**
     * Source type
     * 
     * @var string 
     */
    protected $sourceType;
    
    /**
     * Comments handling
     *
     * @var bool
     */
    protected $comments;
    
    /**
     * JSX syntax handling
     *
     * @var bool
     */
    protected $jsx;
    
    /**
     * Events emitter
     *
     * @var EventsEmitter
     */
    protected $eventsEmitter;

    /**
     * Class constructor
     * 
     * @param string   $source   Source code
     * @param Features $features Parser features
     * @param array    $options  Parsing options
     */
    public function __construct(
        $source, Features $features, $options = array()
    ) {
        $this->features = $features;
        
        $this->sourceType = isset($options["sourceType"]) ?
                            $options["sourceType"] :
                            \Peast\Peast::SOURCE_TYPE_SCRIPT;
        
        //Create the scanner
        $this->scanner = new Scanner($source, $features, $options);
        
        //Enable module scanning if required
        if ($this->sourceType === \Peast\Peast::SOURCE_TYPE_MODULE) {
            $this->scanner->enableModuleMode();
        }
        
        //Enable comments scanning
        $this->comments = isset($options["comments"]) && $options["comments"];
        if ($this->comments) {
            $this->scanner->enableComments();
            //Create the comments registry
            new CommentsRegistry($this);
        }
        
        // Enable jsx syntax if required
        $this->jsx = isset($options["jsx"]) && $options["jsx"];
        
        $this->initContext();
        $this->postInit();
    }
    
    /**
     * Initializes parser context
     * 
     * @return void
     */
    abstract protected function initContext();
    
    /**
     * Post initialize operations
     * 
     * @return void
     */
    abstract protected function postInit();
    
    /**
     * Parses the source
     * 
     * @return Node\Node
     * 
     * @abstract
     */
    abstract public function parse();
    
    /**
     * Returns parsed tokens from the source code
     * 
     * @return Token[]
     */
    public function tokenize()
    {
        $this->scanner->enableTokenRegistration();
        $this->parse();
        return $this->scanner->getTokens();
    }
    
    /**
     * Returns the scanner associated with the parser
     * 
     * @return Scanner
     */
    public function getScanner()
    {
        return $this->scanner;
    }
    
    /**
     * Returns the parser features class
     * 
     * @return Features
     */
    public function getFeatures()
    {
        return $this->features;
    }
    
    /**
     * Returns the parser's events emitter
     * 
     * @return EventsEmitter
     */
    public function getEventsEmitter()
    {
        if (!$this->eventsEmitter) {
            //The event emitter is created here so that it won't exist if not
            //necessary
            $this->eventsEmitter = new EventsEmitter;
        }
        return $this->eventsEmitter;
    }
    
    /**
     * Calls a method with an isolated parser context, applying the given flags,
     * but restoring their values after the execution.
     * 
     * @param array|null  $flags  Key/value array of changes to apply to the
     *                            context flags. If it's null or the first
     *                            element of the array is null the context will
*                                 be reset before applying new values.
     * @param string      $fn     Method to call
     * @param array|null  $args   Method arguments
     * 
     * @return mixed
     */
    protected function isolateContext($flags, $fn, $args = null)
    {
        //Store the current context
        $oldContext = clone $this->context;
        
        //If flag argument is null reset the context
        if ($flags === null) {
            $this->initContext();
        } else {
            //Apply new values to the flags
            foreach ($flags as $k => $v) {
                // If null reset the context
                if ($v === null) {
                    $this->initContext();
                } else {
                    $this->context->$k = $v;
                }
            }
        }
        
        //Call the method with the given arguments
        $ret = $args ? call_user_func_array(array($this, $fn), $args) : $this->$fn();
        
        //Restore previous context
        $this->context = $oldContext;
        
        return $ret;
    }
    
    /**
     * Creates a node
     * 
     * @param string $nodeType Node's type
     * @param mixed  $position Node's start position
     * 
     * @return Node\Node
     * 
     * @codeCoverageIgnore
     */
    protected function createNode($nodeType, $position)
    {
        //Use the right class to get an instance of the node
        $nodeClass = "\\Peast\\Syntax\\Node\\" . $nodeType;
        $node = new $nodeClass;
        
        //Add the node start position
        if ($position instanceof Node\Node || $position instanceof Token) {
            $position = $position->location->start;
        } elseif (is_array($position)) {
            if (count($position)) {
                $position = $position[0]->location->start;
            } else {
                $position = $this->scanner->getPosition();
            }
        }
        $node->location->start = $position;
        
        //Emit the NodeCreated event for the node
        $this->eventsEmitter && $this->eventsEmitter->fire(
            "NodeCreated", array($node)
        );
        
        return $node;
    }
    
    /**
     * Completes a node by adding the end position
     * 
     * @param Node\Node   $node     Node to complete
     * @param Position    $position Node's end position
     * 
     * @return mixed    It actually returns a Node but mixed solves
     *                  a lot of PHPDoc problems
     * 
     * @codeCoverageIgnore
     */
    protected function completeNode(Node\Node $node, $position = null)
    {
        //Add the node end position
        $node->location->end = $position ?: $this->scanner->getPosition();
        
        //Emit the NodeCompleted event for the node
        $this->eventsEmitter && $this->eventsEmitter->fire(
            "NodeCompleted", array($node)
        );
        
        return $node;
    }
    
    /**
     * Throws a syntax error
     * 
     * @param string   $message  Error message
     * @param Position $position Error position
     * 
     * @return void
     * 
     * @throws Exception
     */
    protected function error($message = "", $position = null)
    {
        if (!$message) {
            $token = $this->scanner->getToken();
            if ($token === null) {
                $message = "Unexpected end of input";
            } else {
                $position = $token->location->start;
                $message = "Unexpected: " . $token->value;
            }
        }
        if (!$position) {
            $position = $this->scanner->getPosition();
        }
        throw new Exception($message, $position);
    }
    
    /**
     * Asserts that a valid end of statement follows the current position
     * 
     * @return boolean
     * 
     * @throws Exception
     */
    protected function assertEndOfStatement()
    {
        //The end of statement is reached when it is followed by line
        //terminators, end of source, "}" or ";". In the last case the token
        //must be consumed
        if (!$this->scanner->noLineTerminators()) {
            return true;
        } else {
            if ($this->scanner->consume(";")) {
                return true;
            }
            $token = $this->scanner->getToken();
            if (!$token || $token->value === "}") {
                return true;
            }
        }
        $this->error();
    }
    
    /**
     * Parses a character separated list of instructions or null if the
     * sequence is not valid
     * 
     * @param callable $fn   Parsing instruction function
     * @param string   $char Separator
     * 
     * @return array
     * 
     * @throws Exception
     */
    protected function charSeparatedListOf($fn, $char = ",")
    {
        $list = array();
        $valid = true;
        while ($param = $this->$fn()) {
            $list[] = $param;
            $valid = true;
            if (!$this->scanner->consume($char)) {
                break;
            } else {
                $valid = false;
            }
        }
        if (!$valid) {
            $this->error();
        }
        return $list;
    }
}