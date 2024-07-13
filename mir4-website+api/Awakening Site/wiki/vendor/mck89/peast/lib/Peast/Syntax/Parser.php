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
 * Parser class
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class Parser extends ParserAbstract
{
    use JSX\Parser;
    
    //Identifier parsing mode constants
    /**
     * Everything is allowed as identifier, including keywords, null and booleans
     */
    const ID_ALLOW_ALL = 1;
    
    /**
     * Keywords, null and booleans are not allowed in any situation
     */
    const ID_ALLOW_NOTHING = 2;
    
    /**
     * Keywords, null and booleans are not allowed in any situation, future
     * reserved words are allowed if not in strict mode. Keywords that depend on
     * parser context are evaluated only if the parser context allows them.
     */
    const ID_MIXED = 3;
    
    /**
     * Binding identifier parsing rule
     * 
     * @var int 
     */
    protected static $bindingIdentifier = self::ID_MIXED;
    
    /**
     * Labelled identifier parsing rule
     * 
     * @var int 
     */
    protected static $labelledIdentifier = self::ID_MIXED;
    
    /**
     * Identifier reference parsing rule
     * 
     * @var int 
     */
    protected static $identifierReference = self::ID_MIXED;
    
    /**
     * Identifier name parsing rule
     * 
     * @var int 
     */
    protected static $identifierName = self::ID_ALLOW_ALL;
    
    /**
     * Imported binding parsing rule
     * 
     * @var int 
     */
    protected static $importedBinding = self::ID_ALLOW_NOTHING;
    
    /**
     * Assignment operators
     * 
     * @var array 
     */
    protected $assignmentOperators = array(
        "=", "+=", "-=", "*=", "/=", "%=", "<<=", ">>=", ">>>=", "&=", "^=",
        "|=", "**=", "&&=", "||=", "??="
    );
    
    /**
     * Logical and binary operators
     * 
     * @var array 
     */
    protected $logicalBinaryOperators = array(
        "??" => 0,
        "||" => 0,
        "&&" => 1,
        "|" => 2,
        "^" => 3,
        "&" => 4,
        "===" => 5, "!==" => 5, "==" => 5, "!=" => 5,
        "<=" => 6, ">=" => 6, "<" => 6, ">" => 6,
        "instanceof" => 6, "in" => 6,
        ">>>" => 7, "<<" => 7, ">>" => 7,
        "+" => 8, "-" => 8,
        "*" => 9, "/" => 9, "%" => 9,
        "**" => 10
    );
    
    /**
     * Unary operators
     * 
     * @var array 
     */
    protected $unaryOperators = array(
        "delete", "void", "typeof", "++", "--", "+", "-", "~", "!"
    );
    
    /**
     * Postfix operators
     * 
     * @var array 
     */
    protected $postfixOperators = array("--", "++");
    
    /**
     * Array of keywords that depends on a context property
     * 
     * @var array 
     */
    protected $contextKeywords = array(
        "yield" => "allowYield",
        "await" => "allowAwait"
    );
    
    /**
     * Initializes parser context
     * 
     * @return void
     */
    protected function initContext()
    {
        $context = array(
            "allowReturn" => false,
            "allowIn" => false,
            "allowYield" => false,
            "allowAwait" => false
        );
        //If async/await is not enabled remove the
        //relative context properties
        if (!$this->features->asyncAwait) {
            unset($context["allowAwait"]);
            unset($this->contextKeywords["await"]);
        }
        $this->context = (object) $context;
    }
    
    /**
     * Post initialize operations
     * 
     * @return void
     */
    protected function postInit()
    {
        //Remove exponentiation operator if the feature
        //is not enabled
        if (!$this->features->exponentiationOperator) {
            Utils::removeArrayValue(
                $this->assignmentOperators,
                "**="
            );
            unset($this->logicalBinaryOperators["**"]);
        }

        //Remove coalescing operator if the feature
        //is not enabled
        if (!$this->features->coalescingOperator) {
            unset($this->logicalBinaryOperators["??"]);
        }

        //Remove logical assignment operators if the
        //feature is not enabled
        if (!$this->features->logicalAssignmentOperators) {
            foreach (array("&&=", "||=", "??=") as $op) {
                Utils::removeArrayValue(
                    $this->assignmentOperators,
                    $op
                );
            }
        }
    }
    
    /**
     * Parses the source
     * 
     * @return Node\Program
     */
    public function parse()
    {
        if ($this->sourceType === \Peast\Peast::SOURCE_TYPE_MODULE) {
            $this->scanner->setStrictMode(true);
            $body = $this->parseModuleItemList();
        } else {
            $body = $this->parseStatementList(true);
        }
        
        $node = $this->createNode(
            "Program", $body ?: $this->scanner->getPosition()
        );
        $node->setSourceType($this->sourceType);
        if ($body) {
            $node->setBody($body);
        }
        $program = $this->completeNode($node);
        
        if ($this->scanner->getToken()) {
            $this->error();
        }
        
        //Execute scanner end operations
        $this->scanner->consumeEnd();
        
        //Emit the EndParsing event and pass the resulting program node as
        //event data
        $this->eventsEmitter && $this->eventsEmitter->fire(
            "EndParsing", array($program)
        );
        
        return $program;
    }
    
    /**
     * Converts an expression node to a pattern node
     * 
     * @param Node\Node $node The node to convert
     * 
     * @return Node\Node
     */
    protected function expressionToPattern($node)
    {
        if ($node instanceof Node\ArrayExpression) {
            
            $loc = $node->location;
            $elems = array();
            foreach ($node->getElements() as $elem) {
                $elems[] = $this->expressionToPattern($elem);
            }
                
            $retNode = $this->createNode("ArrayPattern", $loc->start);
            $retNode->setElements($elems);
            $this->completeNode($retNode, $loc->end);
            
        } elseif ($node instanceof Node\ObjectExpression) {
            
            $loc = $node->location;
            $props = array();
            foreach ($node->getProperties() as $prop) {
                $props[] = $this->expressionToPattern($prop);
            }
                
            $retNode = $this->createNode("ObjectPattern", $loc->start);
            $retNode->setProperties($props);
            $this->completeNode($retNode, $loc->end);
            
        } elseif ($node instanceof Node\Property) {
            
            $loc = $node->location;
            $retNode = $this->createNode(
                "AssignmentProperty", $loc->start
            );
            // If it's a shorthand property convert the value to an assignment
            // pattern if necessary
            $value = $node->getValue();
            $key = $node->getKey();
            if ($value && $node->getShorthand() &&
                !$value instanceof Node\AssignmentExpression &&
                (!$value instanceof Node\Identifier || (
                $key instanceof Node\Identifier && $key->getName() !== $value->getName()
                ))) {
                $loc = $node->location;
                $valNode = $this->createNode("AssignmentPattern", $loc->start);
                $valNode->setLeft($key);
                $valNode->setRight($value);
                $this->completeNode($valNode, $loc->end);
                $value = $valNode;
            } else {
                $value = $this->expressionToPattern($value);
            }
            $retNode->setValue($value);
            $retNode->setKey($key);
            $retNode->setMethod($node->getMethod());
            $retNode->setShorthand($node->getShorthand());
            $retNode->setComputed($node->getComputed());
            $this->completeNode($retNode, $loc->end);
            
        } elseif ($node instanceof Node\SpreadElement) {
            
            $loc = $node->location;
            $retNode = $this->createNode("RestElement", $loc->start);
            $retNode->setArgument(
                $this->expressionToPattern($node->getArgument())
            );
            $this->completeNode($retNode, $loc->end);
            
        } elseif ($node instanceof Node\AssignmentExpression) {
            
            $loc = $node->location;
            $retNode = $this->createNode("AssignmentPattern", $loc->start);
            $retNode->setLeft($this->expressionToPattern($node->getLeft()));
            $retNode->setRight($node->getRight());
            $this->completeNode($retNode, $loc->end);
            
        } else {
            $retNode = $node;
        }
        return $retNode;
    }
    
    /**
     * Parses a statement list
     * 
     * @param bool $parseDirectivePrologues True to parse directive prologues
     * 
     * @return Node\Node[]|null
     */
    protected function parseStatementList(
        $parseDirectivePrologues = false
    ) {
        $items = array();
        
        //Get directive prologues and check if strict mode is present
        if ($parseDirectivePrologues) {
            $oldStrictMode = $this->scanner->getStrictMode();
            if ($directives = $this->parseDirectivePrologues()) {
                $items = array_merge($items, $directives[0]);
                //If "use strict" is present enable scanner strict mode
                if (in_array("use strict", $directives[1])) {
                    $this->scanner->setStrictMode(true);
                }
            }
        }
        
        while ($item = $this->parseStatementListItem()) {
            $items[] = $item;
        }
        
        //Apply previous strict mode
        if ($parseDirectivePrologues) {
            $this->scanner->setStrictMode($oldStrictMode);
        }
        
        return count($items) ? $items : null;
    }
    
    /**
     * Parses a statement list item
     * 
     * @return Node\Statement|Node\Declaration|null
     */
    protected function parseStatementListItem()
    {
        if ($declaration = $this->parseDeclaration()) {
            return $declaration;
        } elseif ($statement = $this->parseStatement()) {
            return $statement;
        }
        return null;
    }
    
    /**
     * Parses a statement
     * 
     * @return Node\Statement|null
     */
    protected function parseStatement()
    {
        //Here the token value is checked for performance so that functions won't be
        //called if not necessary
        $token = $this->scanner->getToken();
        if (!$token) {
            return null;
        }
        $val = $token->value;
        if ($val === "{" && $statement = $this->parseBlock()) {
            return $statement;
        } elseif ($val === "var" && $statement = $this->parseVariableStatement()) {
            return $statement;
        } elseif ($val === ";" && $statement = $this->parseEmptyStatement()) {
            return $statement;
        } elseif ($val === "if" && $statement = $this->parseIfStatement()) {
            return $statement;
        } elseif (
            ($val === "for" || $val === "while" || $val === "do" || $val === "switch") &&
            $statement = $this->parseBreakableStatement()
        ) {
            return $statement;
        } elseif ($val == "continue" && $statement = $this->parseContinueStatement()) {
            return $statement;
        } elseif ($val === "break" && $statement = $this->parseBreakStatement()) {
            return $statement;
        } elseif (
            $this->context->allowReturn && $val === "return" &&
            $statement = $this->parseReturnStatement()
        ) {
            return $statement;
        } elseif ($val === "with" && $statement = $this->parseWithStatement()) {
            return $statement;
        } elseif ($val === "throw" && $statement = $this->parseThrowStatement()) {
            return $statement;
        } elseif ($val === "try" && $statement = $this->parseTryStatement()) {
            return $statement;
        } elseif ($val === "debugger" && $statement = $this->parseDebuggerStatement()) {
            return $statement;
        } elseif ($statement = $this->parseLabelledStatement()) {
            return $statement;
        } elseif ($statement = $this->parseExpressionStatement()) {
            return $statement;
        }
        return null;
    }
    
    /**
     * Parses a declaration
     * 
     * @return Node\Declaration|null
     */
    protected function parseDeclaration()
    {
        //Here the token value is checked for performance so that functions won't be
        //called if not necessary
        $token = $this->scanner->getToken();
        if (!$token) {
            return null;
        }
        $val = $token->value;
        if ($declaration = $this->parseFunctionOrGeneratorDeclaration()) {
            return $declaration;
        } elseif ($val === "class" && $declaration = $this->parseClassDeclaration()) {
            return $declaration;
        } elseif (
            ($val === "let" || $val === "const") &&
            $declaration = $this->isolateContext(
                array("allowIn" => true), "parseLexicalDeclaration"
            )
        ) {
            return $declaration;
        }
        return null;
    }
    
    /**
     * Parses a breakable statement
     * 
     * @return Node\Node|null
     */
    protected function parseBreakableStatement()
    {
        if ($statement = $this->parseIterationStatement()) {
            return $statement;
        } elseif ($statement = $this->parseSwitchStatement()) {
            return $statement;
        }
        return null;
    }
    
    /**
     * Parses a block statement
     * 
     * @return Node\BlockStatement|null
     */
    protected function parseBlock()
    {
        if ($token = $this->scanner->consume("{")) {
            
            $statements = $this->parseStatementList();
            if ($this->scanner->consume("}")) {
                $node = $this->createNode("BlockStatement", $token);
                if ($statements) {
                    $node->setBody($statements);
                }
                return $this->completeNode($node);
            }
            $this->error();
        }
        return null;
    }
    
    /**
     * Parses a module item list
     * 
     * @return Node\Node[]|null
     */
    protected function parseModuleItemList()
    {
        $items = array();
        while ($item = $this->parseModuleItem()) {
            $items[] = $item;
        }
        return count($items) ? $items : null;
    }
    
    /**
     * Parses an empty statement
     * 
     * @return Node\EmptyStatement|null
     */
    protected function parseEmptyStatement()
    {
        if ($token = $this->scanner->consume(";")) {
            $node = $this->createNode("EmptyStatement", $token);
            return $this->completeNode($node);
        }
        return null;
    }
    
    /**
     * Parses a debugger statement
     * 
     * @return Node\DebuggerStatement|null
     */
    protected function parseDebuggerStatement()
    {
        if ($token = $this->scanner->consume("debugger")) {
            $node = $this->createNode("DebuggerStatement", $token);
            $this->assertEndOfStatement();
            return $this->completeNode($node);
        }
        return null;
    }
    
    /**
     * Parses an if statement
     * 
     * @return Node\IfStatement|null
     */
    protected function parseIfStatement()
    {
        if ($token = $this->scanner->consume("if")) {
            
            if ($this->scanner->consume("(") &&
                ($test = $this->isolateContext(
                    array("allowIn" => true), "parseExpression"
                )) &&
                $this->scanner->consume(")") &&
                (
                    ($consequent = $this->parseStatement()) ||
                    (!$this->scanner->getStrictMode() &&
                    $consequent = $this->parseFunctionOrGeneratorDeclaration(
                        false, false
                    ))
                )
            ) {
                
                $node = $this->createNode("IfStatement", $token);
                $node->setTest($test);
                $node->setConsequent($consequent);
                
                if ($this->scanner->consume("else")) {
                    if (($alternate = $this->parseStatement()) ||
                        (!$this->scanner->getStrictMode() &&
                        $alternate = $this->parseFunctionOrGeneratorDeclaration(
                            false, false
                        ))
                    ) {
                        $node->setAlternate($alternate);
                        return $this->completeNode($node);
                    }
                } else {
                    return $this->completeNode($node);
                }
            }
            
            $this->error();
        }
        return null;
    }
    
    /**
     * Parses a try-catch statement
     * 
     * @return Node\TryStatement|null
     */
    protected function parseTryStatement()
    {
        if ($token = $this->scanner->consume("try")) {
            
            if ($block = $this->parseBlock()) {
                
                $node = $this->createNode("TryStatement", $token);
                $node->setBlock($block);

                if ($handler = $this->parseCatch()) {
                    $node->setHandler($handler);
                }

                if ($finalizer = $this->parseFinally()) {
                    $node->setFinalizer($finalizer);
                }

                if ($handler || $finalizer) {
                    return $this->completeNode($node);
                }
            }
            
            $this->error();
        }
        return null;
    }
    
    /**
     * Parses the catch block of a try-catch statement
     * 
     * @return Node\CatchClause|null
     */
    protected function parseCatch()
    {
        if ($token = $this->scanner->consume("catch")) {

            $node = $this->createNode("CatchClause", $token);

            if ($this->scanner->consume("(")) {
                if (!($param = $this->parseCatchParameter()) ||
                    !$this->scanner->consume(")")) {
                    $this->error();
                }
                $node->setParam($param);
            } elseif (!$this->features->optionalCatchBinding) {
                $this->error();
            }

            if (!($body = $this->parseBlock())) {
                $this->error();
            }

            $node->setBody($body);

            return $this->completeNode($node);
        }
        return null;
    }
    
    /**
     * Parses the catch parameter of a catch block in a try-catch statement
     * 
     * @return Node\Node|null
     */
    protected function parseCatchParameter()
    {
        if ($param = $this->parseIdentifier(static::$bindingIdentifier)) {
            return $param;
        } elseif ($param = $this->parseBindingPattern()) {
            return $param;
        }
        return null;
    }
    
    /**
     * Parses a finally block in a try-catch statement
     * 
     * @return Node\BlockStatement|null
     */
    protected function parseFinally()
    {
        if ($this->scanner->consume("finally")) {
            
            if ($block = $this->parseBlock()) {
                return $block;
            }
            
            $this->error();
        }
        return null;
    }
    
    /**
     * Parses a continue statement
     * 
     * @return Node\ContinueStatement|null
     */
    protected function parseContinueStatement()
    {
        if ($token = $this->scanner->consume("continue")) {
            
            $node = $this->createNode("ContinueStatement", $token);
            
            if ($this->scanner->noLineTerminators() &&
                ($label = $this->parseIdentifier(static::$labelledIdentifier))
            ) {
                $node->setLabel($label);
                $this->assertEndOfStatement();
            } else {
                $this->scanner->consume(";");
            }
            
            return $this->completeNode($node);
        }
        return null;
    }
    
    /**
     * Parses a break statement
     * 
     * @return Node\BreakStatement|null
     */
    protected function parseBreakStatement()
    {
        if ($token = $this->scanner->consume("break")) {
            
            $node = $this->createNode("BreakStatement", $token);
            
            if ($this->scanner->noLineTerminators() &&
                ($label = $this->parseIdentifier(static::$labelledIdentifier))) {
                $node->setLabel($label);
                $this->assertEndOfStatement();
            } else {
                $this->scanner->consume(";");
            }
            
            return $this->completeNode($node);
        }
        return null;
    }
    
    /**
     * Parses a return statement
     * 
     * @return Node\ReturnStatement|null
     */
    protected function parseReturnStatement()
    {
        if ($token = $this->scanner->consume("return")) {
            
            $node = $this->createNode("ReturnStatement", $token);
            
            if ($this->scanner->noLineTerminators()) {
                $argument = $this->isolateContext(
                    array("allowIn" => true), "parseExpression"
                );
                if ($argument) {
                    $node->setArgument($argument);
                }
            }
            
            $this->assertEndOfStatement();
            
            return $this->completeNode($node);
        }
        return null;
    }
    
    /**
     * Parses a labelled statement
     * 
     * @return Node\LabeledStatement|null
     */
    protected function parseLabelledStatement()
    {
        if ($label = $this->parseIdentifier(static::$labelledIdentifier, ":")) {
            
            $this->scanner->consume(":");
                
            if (($body = $this->parseStatement()) ||
                ($body = $this->parseFunctionOrGeneratorDeclaration(
                    false, false
                ))
            ) {
                
                //Labelled functions are not allowed in strict mode 
                if ($body instanceof Node\FunctionDeclaration &&
                    $this->scanner->getStrictMode()) {
                    $this->error(
                        "Labelled functions are not allowed in strict mode"
                    );
                }

                $node = $this->createNode("LabeledStatement", $label);
                $node->setLabel($label);
                $node->setBody($body);
                return $this->completeNode($node);

            }

            $this->error();
        }
        return null;
    }
    
    /**
     * Parses a throw statement
     * 
     * @return Node\ThrowStatement|null
     */
    protected function parseThrowStatement()
    {
        if ($token = $this->scanner->consume("throw")) {
            
            if ($this->scanner->noLineTerminators() &&
                ($argument = $this->isolateContext(
                    array("allowIn" => true), "parseExpression"
                ))
            ) {
                
                $this->assertEndOfStatement();
                $node = $this->createNode("ThrowStatement", $token);
                $node->setArgument($argument);
                return $this->completeNode($node);
            }
            
            $this->error();
        }
        return null;
    }
    
    /**
     * Parses a with statement
     * 
     * @return Node\WithStatement|null
     */
    protected function parseWithStatement()
    {
        if ($token = $this->scanner->consume("with")) {
            
            if ($this->scanner->consume("(") &&
                ($object = $this->isolateContext(
                    array("allowIn" => true), "parseExpression"
                )) &&
                $this->scanner->consume(")") &&
                $body = $this->parseStatement()
            ) {
            
                $node = $this->createNode("WithStatement", $token);
                $node->setObject($object);
                $node->setBody($body);
                return $this->completeNode($node);
            }
            
            $this->error();
        }
        return null;
    }
    
    /**
     * Parses a switch statement
     * 
     * @return Node\SwitchStatement|null
     */
    protected function parseSwitchStatement()
    {
        if ($token = $this->scanner->consume("switch")) {
            
            if ($this->scanner->consume("(") &&
                ($discriminant = $this->isolateContext(
                    array("allowIn" => true), "parseExpression"
                )) &&
                $this->scanner->consume(")") &&
                ($cases = $this->parseCaseBlock()) !== null
            ) {
            
                $node = $this->createNode("SwitchStatement", $token);
                $node->setDiscriminant($discriminant);
                $node->setCases($cases);
                return $this->completeNode($node);
            }
            
            $this->error();
        }
        return null;
    }
    
    /**
     * Parses the content of a switch statement
     * 
     * @return Node\SwitchCase[]|null
     */
    protected function parseCaseBlock()
    {
        if ($this->scanner->consume("{")) {
            
            $parsedCasesAll = array(
                $this->parseCaseClauses(),
                $this->parseDefaultClause(),
                $this->parseCaseClauses()
            );
            
            if ($this->scanner->consume("}")) {
                $cases = array();
                foreach ($parsedCasesAll as $parsedCases) {
                    if ($parsedCases) {
                        if (is_array($parsedCases)) {
                            $cases = array_merge($cases, $parsedCases);
                        } else {
                            $cases[] = $parsedCases;
                        }
                    }
                }
                return $cases;
            } elseif ($this->parseDefaultClause()) {
                $this->error(
                    "Multiple default clause in switch statement"
                );
            } else {
                $this->error();
            }
        }
        return null;
    }
    
    /**
     * Parses cases in a switch statement
     * 
     * @return Node\SwitchCase[]|null
     */
    protected function parseCaseClauses()
    {
        $cases = array();
        while ($case = $this->parseCaseClause()) {
            $cases[] = $case;
        }
        return count($cases) ? $cases : null;
    }
    
    /**
     * Parses a case in a switch statement
     * 
     * @return Node\SwitchCase|null
     */
    protected function parseCaseClause()
    {
        if ($token = $this->scanner->consume("case")) {
            
            if (($test = $this->isolateContext(
                    array("allowIn" => true), "parseExpression"
                )) &&
                $this->scanner->consume(":")
            ) {

                $node = $this->createNode("SwitchCase", $token);
                $node->setTest($test);

                if ($consequent = $this->parseStatementList()) {
                    $node->setConsequent($consequent);
                }

                return $this->completeNode($node);
            }
            
            $this->error();
        }
        return null;
    }
    
    /**
     * Parses default case in a switch statement
     * 
     * @return Node\SwitchCase|null
     */
    protected function parseDefaultClause()
    {
        if ($token = $this->scanner->consume("default")) {
            
            if ($this->scanner->consume(":")) {

                $node = $this->createNode("SwitchCase", $token);
            
                if ($consequent = $this->parseStatementList()) {
                    $node->setConsequent($consequent);
                }

                return $this->completeNode($node);
            }
            
            $this->error();
        }
        return null;
    }
    
    /**
     * Parses an expression statement
     * 
     * @return Node\ExpressionStatement|null
     */
    protected function parseExpressionStatement()
    {
        $lookaheadTokens = array("{", "function", "class", array("let", "["));
        if ($this->features->asyncAwait) {
            array_splice(
                $lookaheadTokens, 3, 0,
                array(array("async", true))
            );
        }
        if (!$this->scanner->isBefore($lookaheadTokens, true) &&
            $expression = $this->isolateContext(
                array("allowIn" => true), "parseExpression"
            )
        ) {
            
            $this->assertEndOfStatement();
            $node = $this->createNode("ExpressionStatement", $expression);
            $node->setExpression($expression);
            return $this->completeNode($node);
        }
        return null;
    }
    
    /**
     * Parses a do-while statement
     * 
     * @return Node\DoWhileStatement|null
     */
    protected function parseDoWhileStatement()
    {
        if ($token = $this->scanner->consume("do")) {
            
            if (($body = $this->parseStatement()) &&
                $this->scanner->consume("while") &&
                $this->scanner->consume("(") &&
                ($test = $this->isolateContext(
                    array("allowIn" => true), "parseExpression"
                )) &&
                $this->scanner->consume(")")
            ) {
                    
                $node = $this->createNode("DoWhileStatement", $token);
                $node->setBody($body);
                $node->setTest($test);
                $node = $this->completeNode($node);
                $this->scanner->consume(";");
                return $node;
            }
            
            $this->error();
        }
        return null;
    }
    
    /**
     * Parses a while statement
     * 
     * @return Node\WhileStatement|null
     */
    protected function parseWhileStatement()
    {
        if ($token = $this->scanner->consume("while")) {
            
            if ($this->scanner->consume("(") &&
                ($test = $this->isolateContext(
                    array("allowIn" => true), "parseExpression"
                )) &&
                $this->scanner->consume(")") &&
                $body = $this->parseStatement()
            ) {
                    
                $node = $this->createNode("WhileStatement", $token);
                $node->setTest($test);
                $node->setBody($body);
                return $this->completeNode($node);
            }
            
            $this->error();
        }
        return null;
    }
    
    /**
     * Parses a for(var ...) statement
     * 
     * @param Token $forToken Token that corresponds to the "for" keyword
     * 
     * @return Node\Node|null
     */
    protected function parseForVarStatement($forToken)
    {
        if (!($varToken = $this->scanner->consume("var"))) {
            return null;
        }

        $state = $this->scanner->getState();

        if (($decl = $this->isolateContext(
                array("allowIn" => false), "parseVariableDeclarationList"
            )) &&
            ($varEndPosition = $this->scanner->getPosition()) &&
            $this->scanner->consume(";")
        ) {

            $init = $this->createNode(
                "VariableDeclaration", $varToken
            );
            $init->setKind($init::KIND_VAR);
            $init->setDeclarations($decl);
            $init = $this->completeNode($init, $varEndPosition);

            $test = $this->isolateContext(
                array("allowIn" => true), "parseExpression"
            );

            if ($this->scanner->consume(";")) {

                $update = $this->isolateContext(
                    array("allowIn" => true), "parseExpression"
                );

                if ($this->scanner->consume(")") &&
                    $body = $this->parseStatement()
                ) {

                    $node = $this->createNode("ForStatement", $forToken);
                    $node->setInit($init);
                    $node->setTest($test);
                    $node->setUpdate($update);
                    $node->setBody($body);
                    return $this->completeNode($node);
                }
            }
        } else {

            $this->scanner->setState($state);

            if ($decl = $this->parseForBinding()) {

                $init = null;
                if ($this->features->forInInitializer &&
                    $decl->getId()->getType() === "Identifier") {
                    $init = $this->parseInitializer();
                }

                if ($init) {
                    $decl->setInit($init);
                    $decl->location->end = $init->location->end;
                }

                $left = $this->createNode("VariableDeclaration", $varToken);
                $left->setKind($left::KIND_VAR);
                $left->setDeclarations(array($decl));
                $left = $this->completeNode($left);

                if ($this->scanner->consume("in")) {

                    if ($init && $this->scanner->getStrictMode()) {
                        $this->error(
                            "For-in variable initializer not allowed in " .
                            "strict mode"
                        );
                    }

                    if (($right = $this->isolateContext(
                            array("allowIn" => true), "parseExpression"
                        )) &&
                        $this->scanner->consume(")") &&
                        $body = $this->parseStatement()
                    ) {

                        $node = $this->createNode(
                            "ForInStatement", $forToken
                        );
                        $node->setLeft($left);
                        $node->setRight($right);
                        $node->setBody($body);
                        return $this->completeNode($node);
                    }
                } elseif (!$init && $this->scanner->consume("of")) {

                    if (($right = $this->isolateContext(
                            array("allowIn" => true), "parseAssignmentExpression"
                        )) &&
                        $this->scanner->consume(")") &&
                        $body = $this->parseStatement()
                    ) {

                        $node = $this->createNode(
                            "ForOfStatement", $forToken
                        );
                        $node->setLeft($left);
                        $node->setRight($right);
                        $node->setBody($body);
                        return $this->completeNode($node);
                    }
                }
            }
        }

        $this->error();
    }
    
    /**
     * Parses a for(let ...) or for(const ...) statement
     * 
     * @param Token $forToken Token that corresponds to the "for" keyword
     * 
     * @return Node\Node|null
     */
    protected function parseForLetConstStatement($forToken)
    {
        $afterBracketState = $this->scanner->getState();
        if (!($init = $this->parseForDeclaration())) {
            return null;
        }
            
        if ($this->scanner->consume("in")) {
            if (($right = $this->isolateContext(
                    array("allowIn" => true), "parseExpression"
                )) &&
                $this->scanner->consume(")") &&
                $body = $this->parseStatement()
            ) {
                
                $node = $this->createNode("ForInStatement", $forToken);
                $node->setLeft($init);
                $node->setRight($right);
                $node->setBody($body);
                return $this->completeNode($node);
            }
        } elseif ($this->scanner->consume("of")) {
            if (($right = $this->isolateContext(
                    array("allowIn" => true), "parseAssignmentExpression"
                )) &&
                $this->scanner->consume(")") &&
                $body = $this->parseStatement()
            ) {
                
                $node = $this->createNode("ForOfStatement", $forToken);
                $node->setLeft($init);
                $node->setRight($right);
                $node->setBody($body);
                return $this->completeNode($node);
            }
        } else {
            
            $this->scanner->setState($afterBracketState);
            if ($init = $this->isolateContext(
                    array("allowIn" => false), "parseLexicalDeclaration"
                )
            ) {
                
                $test = $this->isolateContext(
                    array("allowIn" => true), "parseExpression"
                );
                if ($this->scanner->consume(";")) {
                        
                    $update = $this->isolateContext(
                        array("allowIn" => true), "parseExpression"
                    );
                    
                    if ($this->scanner->consume(")") &&
                        $body = $this->parseStatement()
                    ) {
                        
                        $node = $this->createNode("ForStatement", $forToken);
                        $node->setInit($init);
                        $node->setTest($test);
                        $node->setUpdate($update);
                        $node->setBody($body);
                        return $this->completeNode($node);
                    }
                }
            }
        }
        
        $this->error();
    }
    
    /**
     * Parses a for statement that does not start with var, let or const
     * 
     * @param Token $forToken Token that corresponds to the "for" keyword
     * @param bool  $hasAwait True if "for" is followed by "await"
     * 
     * @return Node\Node|null
     */
    protected function parseForNotVarLetConstStatement($forToken, $hasAwait)
    {
        $state = $this->scanner->getState();
        $notBeforeSB = !$this->scanner->isBefore(array(array("let", "[")), true);
        
        if ($notBeforeSB &&
            (($init = $this->isolateContext(
                array("allowIn" => false), "parseExpression"
            )) || true) &&
            $this->scanner->consume(";")
        ) {
        
            $test = $this->isolateContext(
                array("allowIn" => true), "parseExpression"
            );
            
            if ($this->scanner->consume(";")) {
                    
                $update = $this->isolateContext(
                    array("allowIn" => true), "parseExpression"
                );
                
                if ($this->scanner->consume(")") &&
                    $body = $this->parseStatement()
                ) {
                    
                    $node = $this->createNode("ForStatement", $forToken);
                    $node->setInit($init);
                    $node->setTest($test);
                    $node->setUpdate($update);
                    $node->setBody($body);
                    return $this->completeNode($node);
                }
            }
        } else {
            
            $this->scanner->setState($state);
            $beforeLetAsyncOf = $this->scanner->isBefore(array("let", array("async", "of")), true);
            $left = $this->parseLeftHandSideExpression();

            if ($left && $left->getType() === "ChainExpression") {
                $this->error(
                    "Optional chain can't appear in left-hand side"
                );
            }

            $left = $this->expressionToPattern($left);
            
            if ($notBeforeSB && $left && $this->scanner->consume("in")) {
                
                if (($right = $this->isolateContext(
                        array("allowIn" => true), "parseExpression"
                    )) &&
                    $this->scanner->consume(")") &&
                    $body = $this->parseStatement()
                ) {
                    
                    $node = $this->createNode("ForInStatement", $forToken);
                    $node->setLeft($left);
                    $node->setRight($right);
                    $node->setBody($body);
                    return $this->completeNode($node);
                }
            } elseif (($hasAwait || !$beforeLetAsyncOf) &&
                $left && $this->scanner->consume("of")
            ) {
                
                if (($right = $this->isolateContext(
                        array("allowIn" => true),
                        "parseAssignmentExpression"
                    )) &&
                    $this->scanner->consume(")") &&
                    $body = $this->parseStatement()
                ) {
                    
                    $node = $this->createNode("ForOfStatement", $forToken);
                    $node->setLeft($left);
                    $node->setRight($right);
                    $node->setBody($body);
                    return $this->completeNode($node);
                }
            }
        }
        
        $this->error();
    }
    
    /**
     * Parses do-while, while, for, for-in and for-of statements
     * 
     * @return Node\Node|null
     */
    protected function parseIterationStatement()
    {
        if ($node = $this->parseWhileStatement()) {
            return $node;
        } elseif ($node = $this->parseDoWhileStatement()) {
            return $node;
        } elseif ($startForToken = $this->scanner->consume("for")) {

            $forAwait = false;
            if ($this->features->asyncIterationGenerators &&
                $this->context->allowAwait &&
                $this->scanner->consume("await")
            ) {
                $forAwait = true;
            }

            if ($this->scanner->consume("(") && (
                ($node = $this->parseForVarStatement($startForToken)) ||
                ($node = $this->parseForLetConstStatement($startForToken)) ||
                ($node = $this->parseForNotVarLetConstStatement($startForToken, $forAwait)))
            ) {
                if ($forAwait) {
                    if (!$node instanceof Node\ForOfStatement) {
                        $this->error(
                            "Async iteration is allowed only with for-of statements",
                            $startForToken->location->start
                        );
                    }
                    $node->setAwait(true);
                }
                return $node;
            }

            $this->error();
        }

        return null;
    }

    /**
     * Checks if an async function can start from the current position. Returns
     * the async token or null if not found
     *
     * @param bool $checkFn If false it won't check if the async keyword is
     *                      followed by "function"
     *
     * @return Token
     */
    protected function checkAsyncFunctionStart($checkFn = true)
    {
        return ($asyncToken = $this->scanner->getToken()) &&
        $asyncToken->value === "async" &&
        (
            !$checkFn ||
            (($nextToken = $this->scanner->getNextToken()) &&
                $nextToken->value === "function")
        ) &&
        $this->scanner->noLineTerminators(true) ?
            $asyncToken :
            null;
    }
    
    /**
     * Parses function or generator declaration
     * 
     * @param bool $default        Default mode
     * @param bool $allowGenerator True to allow parsing of generators
     * 
     * @return Node\FunctionDeclaration|null
     */
    protected function parseFunctionOrGeneratorDeclaration(
        $default = false, $allowGenerator = true
    ) {
        $async = null;
        if ($this->features->asyncAwait &&
            ($async = $this->checkAsyncFunctionStart())) {
            $this->scanner->consumeToken();
            if (!$this->features->asyncIterationGenerators) {
                $allowGenerator = false;
            }
        }
        if ($token = $this->scanner->consume("function")) {

            $generator = $allowGenerator && $this->scanner->consume("*");
            $id = $this->parseIdentifier(static::$bindingIdentifier);

            if ($generator || $async) {
                $flags = array(null);
                if ($generator) {
                    $flags["allowYield"] = true;
                }
                if ($async) {
                    $flags["allowAwait"] = true;
                }
            } else {
                $flags = null;
            }

            if (($default || $id) &&
                $this->scanner->consume("(") &&
                ($params = $this->isolateContext(
                    $flags,
                    "parseFormalParameterList"
                )) !== null &&
                $this->scanner->consume(")") &&
                ($tokenBodyStart = $this->scanner->consume("{")) &&
                (($body = $this->isolateContext(
                        $flags,
                        "parseFunctionBody"
                    )) || true) &&
                $this->scanner->consume("}")
            ) {

                $body->location->start = $tokenBodyStart->location->start;
                $body->location->end = $this->scanner->getPosition();
                $node = $this->createNode(
                    "FunctionDeclaration",
                    $async ?: $token
                );
                if ($id) {
                    $node->setId($id);
                }
                $node->setParams($params);
                $node->setBody($body);
                $node->setGenerator($generator);
                $node->setAsync((bool) $async);
                return $this->completeNode($node);
            }

            $this->error();
        }
        return null;
    }
    
    /**
     * Parses function or generator expression
     * 
     * @return Node\FunctionExpression|null
     */
    protected function parseFunctionOrGeneratorExpression()
    {
        $allowGenerator = true;
        $async = false;
        if ($this->features->asyncAwait &&
            ($async = $this->checkAsyncFunctionStart())) {
            $this->scanner->consumeToken();
            if (!$this->features->asyncIterationGenerators) {
                $allowGenerator = false;
            }
        }
        if ($token = $this->scanner->consume("function")) {

            $generator = $allowGenerator && $this->scanner->consume("*");

            if ($generator || $async) {
                $flags = array(null);
                if ($generator) {
                    $flags["allowYield"] = true;
                }
                if ($async) {
                    $flags["allowAwait"] = true;
                }
            } else {
                $flags = null;
            }

            $id = $this->isolateContext(
                $flags,
                "parseIdentifier",
                array(static::$bindingIdentifier)
            );

            if ($this->scanner->consume("(") &&
                ($params = $this->isolateContext(
                    $flags,
                    "parseFormalParameterList"
                )) !== null &&
                $this->scanner->consume(")") &&
                ($tokenBodyStart = $this->scanner->consume("{")) &&
                (($body = $this->isolateContext(
                        $flags,
                        "parseFunctionBody"
                    )) || true) &&
                $this->scanner->consume("}")
            ) {

                $body->location->start = $tokenBodyStart->location->start;
                $body->location->end = $this->scanner->getPosition();
                $node = $this->createNode(
                    "FunctionExpression",
                    $async ?: $token
                );
                $node->setId($id);
                $node->setParams($params);
                $node->setBody($body);
                $node->setGenerator($generator);
                $node->setAsync((bool) $async);
                return $this->completeNode($node);
            }

            $this->error();
        }
        return null;
    }
    
    /**
     * Parses yield statement
     * 
     * @return Node\YieldExpression|null
     */
    protected function parseYieldExpression()
    {
        if ($token = $this->scanner->consume("yield")) {
            
            $node = $this->createNode("YieldExpression", $token);
            if ($this->scanner->noLineTerminators()) {
                
                $delegate = $this->scanner->consume("*");
                $argument = $this->isolateContext(
                    array("allowYield" => true), "parseAssignmentExpression"
                );
                if ($argument) {
                    $node->setArgument($argument);
                    $node->setDelegate($delegate);
                }
            }
            
            return $this->completeNode($node);
        }
        return null;
    }
    
    /**
     * Parses a parameter list
     * 
     * @return Node\Node[]|null
     */
    protected function parseFormalParameterList()
    {
        $hasComma = false;
        $list = array();
        while (
            ($param = $this->parseBindingRestElement()) ||
            $param = $this->parseBindingElement()
        ) {
            $hasComma = false;
            $list[] = $param;
            if ($param->getType() === "RestElement") {
                break;
            } elseif ($this->scanner->consume(",")) {
                $hasComma = true;
            } else {
                break;
            }
        }
        //Check if it ends with a comma, then check if the comma is a trailing comma,
        //in that case throw an error if the trailing comma feature is not enabled
        if ($hasComma &&
            !$this->features->trailingCommaFunctionCallDeclaration) {
            $token = $this->scanner->getToken();
            if ($token && $token->value === ")") {
                $this->error();
            }
        }
        return $list;
    }
    
    /**
     * Parses a function body
     * 
     * @return Node\BlockStatement[]|null
     */
    protected function parseFunctionBody()
    {
        $body = $this->isolateContext(
            array("allowReturn" => true),
            "parseStatementList",
            array(true)
        );
        $node = $this->createNode(
            "BlockStatement", $body ?: $this->scanner->getPosition()
        );
        if ($body) {
            $node->setBody($body);
        }
        return $this->completeNode($node);
    }
    
    /**
     * Parses a class declaration
     * 
     * @param bool $default Default mode
     * 
     * @return Node\ClassDeclaration|null
     */
    protected function parseClassDeclaration($default = false)
    {
        if ($token = $this->scanner->consume("class")) {
            
            //Class declarations are strict mode by default
            $prevStrict = $this->scanner->getStrictMode();
            $this->scanner->setStrictMode(true);
            
            $id = $this->parseIdentifier(static::$bindingIdentifier);
            if (($default || $id) &&
                $tail = $this->parseClassTail()
            ) {
                
                $node = $this->createNode("ClassDeclaration", $token);
                if ($id) {
                    $node->setId($id);
                }
                if ($tail[0]) {
                    $node->setSuperClass($tail[0]);
                }
                $node->setBody($tail[1]);
                $this->scanner->setStrictMode($prevStrict);
                return $this->completeNode($node);
            }
            
            $this->error();
        }
        return null;
    }
    
    /**
     * Parses a class expression
     * 
     * @return Node\ClassExpression|null
     */
    protected function parseClassExpression()
    {
        if ($token = $this->scanner->consume("class")) {
            
            //Class expressions are strict mode by default
            $prevStrict = $this->scanner->getStrictMode();
            $this->scanner->setStrictMode(true);
            
            $id = $this->parseIdentifier(static::$bindingIdentifier);
            $tail = $this->parseClassTail();
            $node = $this->createNode("ClassExpression", $token);
            if ($id) {
                $node->setId($id);
            }
            if ($tail[0]) {
                $node->setSuperClass($tail[0]);
            }
            $node->setBody($tail[1]);
            $this->scanner->setStrictMode($prevStrict);
            return $this->completeNode($node);
        }
        return null;
    }
    
    /**
     * Parses the code that comes after the class keyword and class name. The
     * return value is an array where the first item is the extended class, if
     * any, and the second value is the class body
     * 
     * @return array|null
     */
    protected function parseClassTail()
    {
        $heritage = $this->parseClassHeritage();
        if ($token = $this->scanner->consume("{")) {
            
            $body = $this->parseClassBody();
            if ($this->scanner->consume("}")) {
                $body->location->start = $token->location->start;
                $body->location->end = $this->scanner->getPosition();
                return array($heritage, $body);
            }
        }
        $this->error();
    }
    
    /**
     * Parses the class extends part
     * 
     * @return Node\Node|null
     */
    protected function parseClassHeritage()
    {
        if ($this->scanner->consume("extends")) {
            
            if ($superClass = $this->parseLeftHandSideExpression()) {
                return $superClass;
            }
            
            $this->error();
        }
        return null;
    }
    
    /**
     * Parses the class body
     * 
     * @return Node\ClassBody|null
     */
    protected function parseClassBody()
    {
        $body = $this->parseClassElementList();
        $node = $this->createNode(
            "ClassBody", $body ?: $this->scanner->getPosition()
        );
        if ($body) {
            $node->setBody($body);
        }
        return $this->completeNode($node);
    }
    
    /**
     * Parses class elements list
     * 
     * @return Node\MethodDefinition[]|null
     */
    protected function parseClassElementList()
    {
        $items = array();
        while ($item = $this->parseClassElement()) {
            if ($item !== true) {
                $items[] = $item;
            }
        }
        return count($items) ? $items : null;
    }
    
    /**
     * Parses a class elements
     * 
     * @return Node\MethodDefinition|Node\PropertyDefinition|Node\StaticBlock|bool|null
     */
    protected function parseClassElement()
    {
        if ($this->scanner->consume(";")) {
            return true;
        }
        if ($this->features->classStaticBlock &&
            $this->scanner->isBefore(array(array("static", "{")), true)
        ) {
            return $this->parseClassStaticBlock();
        }
        $staticToken = null;
        $state = $this->scanner->getState();
        //This code handles the case where "static" is the method name
        if (!$this->scanner->isBefore(array(array("static", "(")), true)) {
            $staticToken = $this->scanner->consume("static");
        }
        if ($def = $this->parseMethodDefinition()) {
            if ($staticToken) {
                $def->setStatic(true);
                $def->location->start = $staticToken->location->start;
            }
            return $def;
        } else {
            if ($this->features->classFields) {
                if ($field = $this->parseFieldDefinition()) {
                    if ($staticToken) {
                        $field->setStatic(true);
                        $field->location->start = $staticToken->location->start;
                    }
                } elseif ($staticToken) {
                    //Handle the case when "static" is the field name
                    $this->scanner->setState($state);
                    $field = $this->parseFieldDefinition();
                }
                return $field;
            } elseif ($staticToken) {
                $this->error();
            }
        }
        
        return null;
    }
    
    /**
     * Parses a let or const declaration
     * 
     * @return Node\VariableDeclaration|null
     */
    protected function parseLexicalDeclaration()
    {
        $state = $this->scanner->getState();
        if ($token = $this->scanner->consumeOneOf(array("let", "const"))) {
            
            $declarations = $this->charSeparatedListOf(
                "parseVariableDeclaration"
            );
            
            if ($declarations) {
                $this->assertEndOfStatement();
                $node = $this->createNode("VariableDeclaration", $token);
                $node->setKind($token->value);
                $node->setDeclarations($declarations);
                return $this->completeNode($node);
            }
            
            // "let" can be used as variable name in non-strict mode
            if ($this->scanner->getStrictMode() || $token->value !== "let") {
                $this->error();
            } else {
                $this->scanner->setState($state);
            }
        }
        return null;
    }
    
    /**
     * Parses a var declaration
     * 
     * @return Node\VariableDeclaration|null
     */
    protected function parseVariableStatement()
    {
        if ($token = $this->scanner->consume("var")) {
            
            $declarations = $this->isolateContext(
                array("allowIn" => true), "parseVariableDeclarationList"
            );
            if ($declarations) {
                $this->assertEndOfStatement();
                $node = $this->createNode("VariableDeclaration", $token);
                $node->setKind($node::KIND_VAR);
                $node->setDeclarations($declarations);
                return $this->completeNode($node);
            }
            
            $this->error();
        }
        return null;
    }
    
    /**
     * Parses an variable declarations
     * 
     * @return Node\VariableDeclarator[]|null
     */
    protected function parseVariableDeclarationList()
    {
        return $this->charSeparatedListOf(
            "parseVariableDeclaration"
        );
    }
    
    /**
     * Parses a variable declarations
     * 
     * @return Node\VariableDeclarator|null
     */
    protected function parseVariableDeclaration()
    {
        if ($id = $this->parseIdentifier(static::$bindingIdentifier)) {
            
            $node = $this->createNode("VariableDeclarator", $id);
            $node->setId($id);
            if ($init = $this->parseInitializer()) {
                $node->setInit($init);
            }
            return $this->completeNode($node);
            
        } elseif ($id = $this->parseBindingPattern()) {
            
            if ($init = $this->parseInitializer()) {
                $node = $this->createNode("VariableDeclarator", $id);
                $node->setId($id);
                $node->setInit($init);
                return $this->completeNode($node);
            }
            
        }
        return null;
    }
    
    /**
     * Parses a let or const declaration in a for statement definition
     * 
     * @return Node\VariableDeclaration|null
     */
    protected function parseForDeclaration()
    {
        $state = $this->scanner->getState();
        if ($token = $this->scanner->consumeOneOf(array("let", "const"))) {
            
            if ($declaration = $this->parseForBinding()) {

                $node = $this->createNode("VariableDeclaration", $token);
                $node->setKind($token->value);
                $node->setDeclarations(array($declaration));
                return $this->completeNode($node);
            }
            
            // "let" can be used as variable name in non-strict mode
            if ($this->scanner->getStrictMode() || $token->value !== "let") {
                $this->error();
            } else {
                $this->scanner->setState($state);
            }
        }
        return null;
    }
    
    /**
     * Parses a binding pattern or an identifier that come after a const or let
     * declaration in a for statement definition
     * 
     * @return Node\VariableDeclarator|null
     */
    protected function parseForBinding()
    {
        if (($id = $this->parseIdentifier(static::$bindingIdentifier)) ||
            ($id = $this->parseBindingPattern())
        ) {
            
            $node = $this->createNode("VariableDeclarator", $id);
            $node->setId($id);
            return $this->completeNode($node);
        }
        return null;
    }
    
    /**
     * Parses a module item
     * 
     * @return Node\Node|null
     */
    protected function parseModuleItem()
    {
        if ($item = $this->parseImportDeclaration()) {
            return $item;
        } elseif ($item = $this->parseExportDeclaration()) {
            return $item;
        } elseif (
            $item = $this->isolateContext(
                array(
                    "allowYield" => false,
                    "allowReturn" => false,
                    "allowAwait" => $this->features->topLevelAwait
                ),
                "parseStatementListItem"
            )
        ) {
            return $item;
        }
        return null;
    }
    
    /**
     * Parses the from keyword and the following string in import and export
     * declarations
     * 
     * @return Node\StringLiteral|null
     */
    protected function parseFromClause()
    {
        if ($this->scanner->consume("from")) {
            if ($spec = $this->parseStringLiteral()) {
                return $spec;
            }
            $this->error();
        }
        return null;
    }
    
    /**
     * Parses an export declaration
     * 
     * @return Node\ModuleDeclaration|null
     */
    protected function parseExportDeclaration()
    {
        if ($token = $this->scanner->consume("export")) {
            
            if ($this->scanner->consume("*")) {

                $exported = null;
                if ($this->features->exportedNameInExportAll &&
                    $this->scanner->consume("as")) {
                    $exported = $this->parseModuleExportName();
                    if (!$exported) {
                        $this->error();
                    }
                }
                
                if ($source = $this->parseFromClause()) {
                    $this->assertEndOfStatement();
                    $node = $this->createNode("ExportAllDeclaration", $token);
                    $node->setSource($source);
                    $node->setExported($exported);
                    return $this->completeNode($node);
                }
                
            } elseif ($this->scanner->consume("default")) {
                $lookaheadTokens = array("function", "class");
                if ($this->features->asyncAwait) {
                    $lookaheadTokens[] = array("async", true);
                }
                if (($declaration = $this->isolateContext(
                        array("allowAwait" => $this->features->topLevelAwait),
                        "parseFunctionOrGeneratorDeclaration",
                        array(true)
                    )) ||
                    ($declaration = $this->isolateContext(
                        array("allowAwait" => $this->features->topLevelAwait),
                        "parseClassDeclaration",
                        array(true)
                    ))
                ) {
                    
                    $node = $this->createNode("ExportDefaultDeclaration", $token);
                    $node->setDeclaration($declaration);
                    return $this->completeNode($node);
                    
                } elseif (!$this->scanner->isBefore(
                        $lookaheadTokens,
                        $this->features->asyncAwait
                    ) &&
                    ($declaration = $this->isolateContext(
                        array("allowIn" => true, "allowAwait" => $this->features->topLevelAwait),
                        "parseAssignmentExpression"
                    ))
                ) {
                    
                    $this->assertEndOfStatement();
                    $node = $this->createNode(
                        "ExportDefaultDeclaration", $token
                    );
                    $node->setDeclaration($declaration);
                    return $this->completeNode($node);
                }
                
            } elseif (($specifiers = $this->parseExportClause()) !== null) {
                
                $node = $this->createNode("ExportNamedDeclaration", $token);
                $node->setSpecifiers($specifiers);
                if ($source = $this->parseFromClause()) {
                    $node->setSource($source);
                }
                $this->assertEndOfStatement();
                return $this->completeNode($node);

            } elseif (
                ($dec = $this->isolateContext(
                    array("allowAwait" => $this->features->topLevelAwait),
                    "parseVariableStatement"
                )) ||
                $dec = $this->isolateContext(
                    array("allowAwait" => $this->features->topLevelAwait),
                    "parseDeclaration"
                )
            ) {

                $node = $this->createNode("ExportNamedDeclaration", $token);
                $node->setDeclaration($dec);
                return $this->completeNode($node);
            }
            
            $this->error();
        }
        return null;
    }
    
    /**
     * Parses an export clause
     * 
     * @return Node\ExportSpecifier[]|null
     */
    protected function parseExportClause()
    {
        if ($this->scanner->consume("{")) {
            
            $list = array();
            while ($spec = $this->parseExportSpecifier()) {
                $list[] = $spec;
                if (!$this->scanner->consume(",")) {
                    break;
                }
            }
            
            if ($this->scanner->consume("}")) {
                return $list;
            }
            
            $this->error();
        }
        return null;
    }
    
    /**
     * Parses an export specifier
     * 
     * @return Node\ExportSpecifier|null
     */
    protected function parseExportSpecifier()
    {
        if ($local = $this->parseModuleExportName()) {
            
            $node = $this->createNode("ExportSpecifier", $local);
            $node->setLocal($local);
            
            if ($this->scanner->consume("as")) {
                
                if ($exported = $this->parseModuleExportName()) {
                    $node->setExported($exported);
                    return $this->completeNode($node);
                }
                
                $this->error();
            } else {
                $node->setExported($local);
                return $this->completeNode($node);
            }
        }
        return null;
    }

    /**
     * Parses an export name
     * 
     * @return Node\Identifier|Node\StringLiteral|null
     */
    protected function parseModuleExportName()
    {
        if ($name = $this->parseIdentifier(static::$identifierName)) {
            return $name;
        } elseif ($this->features->arbitraryModuleNSNames &&
            ($name = $this->parseStringLiteral())
        ) {
            return $name;
        }
        return null;
    }
    
    /**
     * Parses an import declaration
     * 
     * @return Node\ModuleDeclaration|null
     */
    protected function parseImportDeclaration()
    {
        //Delay parsing of dynamic import so that it is handled
        //by the relative method
        if ($this->features->dynamicImport &&
            $this->scanner->isBefore(array(array("import", "(")), true)) {
            return null;
        }
        //Delay parsing of import.meta so that it is handled
        //by the relative method
        if ($this->features->importMeta &&
            $this->scanner->isBefore(array(array("import", ".")), true)) {
            return null;
        }
        if ($token = $this->scanner->consume("import")) {
            
            if ($source = $this->parseStringLiteral()) {
                
                $this->assertEndOfStatement();
                $node = $this->createNode("ImportDeclaration", $token);
                $node->setSource($source);
                return $this->completeNode($node);
                
            } elseif (($specifiers = $this->parseImportClause()) !== null &&
                $source = $this->parseFromClause()
            ) {
                
                $this->assertEndOfStatement();
                $node = $this->createNode("ImportDeclaration", $token);
                $node->setSpecifiers($specifiers);
                $node->setSource($source);
                
                return $this->completeNode($node);
            }
            
            $this->error();
        }
        return null;
    }
    
    /**
     * Parses an import clause
     * 
     * @return array|null
     */
    protected function parseImportClause()
    {
        if ($spec = $this->parseNameSpaceImport()) {
            return array($spec);
        } elseif (($specs = $this->parseNamedImports()) !== null) {
            return $specs;
        } elseif ($spec = $this->parseIdentifier(static::$importedBinding)) {
            
            $node = $this->createNode("ImportDefaultSpecifier", $spec);
            $node->setLocal($spec);
            $ret = array($this->completeNode($node));
            
            if ($this->scanner->consume(",")) {
                
                if ($spec = $this->parseNameSpaceImport()) {
                    $ret[] = $spec;
                    return $ret;
                } elseif (($specs = $this->parseNamedImports()) !== null) {
                    return array_merge($ret, $specs);
                }
                
                $this->error();
            } else {
                return $ret;
            }
        }
        return null;
    }
    
    /**
     * Parses a namespace import
     * 
     * @return Node\ImportNamespaceSpecifier|null
     */
    protected function parseNameSpaceImport()
    {
        if ($token = $this->scanner->consume("*")) {
            
            if ($this->scanner->consume("as") &&
                $local = $this->parseIdentifier(static::$identifierReference)
            ) {
                $node = $this->createNode("ImportNamespaceSpecifier", $token);
                $node->setLocal($local);
                return $this->completeNode($node);  
            }
            
            $this->error();
        }
        return null;
    }
    
    /**
     * Parses a named imports
     * 
     * @return Node\ImportSpecifier[]|null
     */
    protected function parseNamedImports()
    {
        if ($this->scanner->consume("{")) {
            
            $list = array();
            while ($spec = $this->parseImportSpecifier()) {
                $list[] = $spec;
                if (!$this->scanner->consume(",")) {
                    break;
                }
            }
            
            if ($this->scanner->consume("}")) {
                return $list;
            }
            
            $this->error();
        }
        return null;
    }
    
    /**
     * Parses an import specifier
     * 
     * @return Node\ImportSpecifier|null
     */
    protected function parseImportSpecifier()
    {
        $requiredAs = false;
        $imported = $this->parseIdentifier(static::$importedBinding);
        if (!$imported) {
            $imported = $this->parseModuleExportName();
            if (!$imported) {
                return null;
            }
            $requiredAs = true;
        }
        
        $node = $this->createNode("ImportSpecifier", $imported);
        $node->setImported($imported);
        
        if ($this->scanner->consume("as")) {
            
            if (!($local = $this->parseIdentifier(static::$importedBinding))) {
                $this->error();
            }
            
            $node->setLocal($local);
            
        } elseif ($requiredAs) {
            $this->error();
        } else {
            $node->setLocal($imported);
        }
        
        return $this->completeNode($node);
    }
    
    /**
     * Parses a binding pattern
     * 
     * @return Node\ArrayPattern|Node\ObjectPattern|null
     */
    protected function parseBindingPattern()
    {
        if ($pattern = $this->parseObjectBindingPattern()) {
            return $pattern;
        } elseif ($pattern = $this->parseArrayBindingPattern()) {
            return $pattern;
        }
        return null;
    }
    
    /**
     * Parses an elisions sequence. It returns the number of elisions or null
     * if no elision has been found
     * 
     * @return int
     */
    protected function parseElision()
    {
        $count = 0;
        while ($this->scanner->consume(",")) {
            $count ++;
        }
        return $count ?: null;
    }
    
    /**
     * Parses an array binding pattern
     * 
     * @return Node\ArrayPattern|null
     */
    protected function parseArrayBindingPattern()
    {
        if ($token = $this->scanner->consume("[")) {
            
            $elements = array();
            while (true) {
                if ($elision = $this->parseElision()) {
                    $elements = array_merge(
                        $elements, array_fill(0, $elision, null)
                    );
                }
                if ($element = $this->parseBindingElement()) {
                    $elements[] = $element;
                    if (!$this->scanner->consume(",")) {
                        break;
                    }
                } elseif ($rest = $this->parseBindingRestElement()) {
                    $elements[] = $rest;
                    break;
                } else {
                    break;
                }
            }
            
            if ($this->scanner->consume("]")) {
                $node = $this->createNode("ArrayPattern", $token);
                $node->setElements($elements);
                return $this->completeNode($node);
            }
        }
        return null;
    }
    
    /**
     * Parses a rest element
     * 
     * @return Node\RestElement|null
     */
    protected function parseBindingRestElement()
    {
        if ($token = $this->scanner->consume("...")) {
            
            if (($argument = $this->parseIdentifier(static::$bindingIdentifier)) ||
                ($argument = $this->parseBindingPattern())) {
                $node = $this->createNode("RestElement", $token);
                $node->setArgument($argument);
                return $this->completeNode($node);
            }
            
            $this->error();
        }
        return null;
    }
    
    /**
     * Parses a binding element
     * 
     * @return Node\AssignmentPattern|Node\Identifier|null
     */
    protected function parseBindingElement()
    {
        if ($el = $this->parseSingleNameBinding()) {
            return $el;
        } elseif ($left = $this->parseBindingPattern()) {
            
            $right = $this->isolateContext(
                array("allowIn" => true), "parseInitializer"
            );
            if ($right) {
                $node = $this->createNode("AssignmentPattern", $left);
                $node->setLeft($left);
                $node->setRight($right);
                return $this->completeNode($node);
            } else {
                return $left;
            }
        }
        return null;
    }
    
    /**
     * Parses single name binding
     * 
     * @return Node\AssignmentPattern|Node\Identifier|null
     */
    protected function parseSingleNameBinding()
    {
        if ($left = $this->parseIdentifier(static::$bindingIdentifier)) {
            $right = $this->isolateContext(
                array("allowIn" => true), "parseInitializer"
            );
            if ($right) {
                $node = $this->createNode("AssignmentPattern", $left);
                $node->setLeft($left);
                $node->setRight($right);
                return $this->completeNode($node);
            } else {
                return $left;
            }
        }
        return null;
    }
    
    /**
     * Parses a property name. The returned value is an array where there first
     * element is the property name and the second element is a boolean
     * indicating if it's a computed property
     * 
     * @return array|null
     */
    protected function parsePropertyName()
    {
        if ($token = $this->scanner->consume("[")) {
            
            if (($name = $this->isolateContext(
                    array("allowIn" => true), "parseAssignmentExpression"
                )) &&
                $this->scanner->consume("]")
            ) {
                return array($name, true, $token);
            }
            
            $this->error();
        } elseif ($name = $this->parseIdentifier(static::$identifierName)) {
            return array($name, false);
        } elseif ($name = $this->parseStringLiteral()) {
            return array($name, false);
        } elseif ($name = $this->parseNumericLiteral()) {
            return array($name, false);
        }
        return null;
    }

    /**
     * Parses a property name. The returned value is an array where there first
     * element is the property name and the second element is a boolean
     * indicating if it's a computed property
     * 
     * @return array|null
     */
    protected function parseClassElementName()
    {
        if (
            $this->features->privateMethodsAndFields &&
            ($name = $this->parsePrivateIdentifier())
        ) {
            return array($name, false);
        }
        return $this->parsePropertyName();
    }

    /**
     * Parses a field definition
     * 
     * @return Node\StaticBlock
     */
    protected function parseClassStaticBlock()
    {
        $staticToken = $this->scanner->consume("static");
        $this->scanner->consume("{");
        $statements = $this->isolateContext(
            array("allowAwait" => true), "parseStatementList"
        );
        if ($this->scanner->consume("}")) {
            $node = $this->createNode("StaticBlock", $staticToken);
            if ($statements) {
                $node->setBody($statements);
            }
            return $this->completeNode($node);
        }
        $this->error();
    }

    /**
     * Parses a field definition
     * 
     * @return Node\PropertyDefinition|null
     */
    protected function parseFieldDefinition()
    {
        $state = $this->scanner->getState();
        if ($prop = $this->parseClassElementName()) {
            $value = $this->isolateContext(
                array("allowIn" => true), "parseInitializer"
            );
            $this->assertEndOfStatement();
            $node = $this->createNode("PropertyDefinition", $prop);
            $node->setKey($prop[0]);
            if ($value) {
                $node->setValue($value);
            }
            $node->setComputed($prop[1]);
            return $this->completeNode($node);
        }
        $this->scanner->setState($state);
        return null;
    }
    
    /**
     * Parses a method definition
     * 
     * @return Node\MethodDefinition|null
     */
    protected function parseMethodDefinition()
    {
        $state = $this->scanner->getState();
        $generator = $error = $async = false;
        $position = null;
        $kind = Node\MethodDefinition::KIND_METHOD;
        if ($token = $this->scanner->consume("get")) {
            $position = $token;
            $kind = Node\MethodDefinition::KIND_GET;
        } elseif ($token = $this->scanner->consume("set")) {
            $position = $token;
            $kind = Node\MethodDefinition::KIND_SET;
        } elseif ($token = $this->scanner->consume("*")) {
            $position = $token;
            $error = true;
            $generator = true;
        } elseif ($this->features->asyncAwait &&
                 ($token = $this->checkAsyncFunctionStart(false))) {
            $this->scanner->consumeToken();
            $position = $token;
            $error = true;
            $async = true;
            if ($this->features->asyncIterationGenerators &&
                ($this->scanner->consume("*"))) {
                $generator = true;
            }
        }

        //Handle the case where get, set and async are methods name and not the
        //definition of a getter/setter or the start of an async function
        if (($kind !== Node\MethodDefinition::KIND_METHOD || ($async && !$generator)) &&
            $this->scanner->consume("(")
        ) {
            $this->scanner->setState($state);
            $kind = Node\MethodDefinition::KIND_METHOD;
            $error = $async = false;
        }

        if ($prop = $this->parseClassElementName()) {

            if (!$position) {
                $position = isset($prop[2]) ? $prop[2] : $prop[0];
            }
            if ($tokenFn = $this->scanner->consume("(")) {

                if ($generator || $async) {
                    $flags = array(null);
                    if ($generator) {
                        $flags["allowYield"] = true;
                    }
                    if ($async) {
                        $flags["allowAwait"] = true;
                    }
                } else {
                    $flags = null;
                }

                $error = true;
                $params = array();
                if ($kind === Node\MethodDefinition::KIND_SET) {
                    $params = $this->isolateContext(
                        null, "parseBindingElement"
                    );
                    if ($params) {
                        $params = array($params);
                    }
                } elseif ($kind === Node\MethodDefinition::KIND_METHOD) {
                    $params = $this->isolateContext(
                        $flags, "parseFormalParameterList"
                    );
                }

                if ($params !== null &&
                    $this->scanner->consume(")") &&
                    ($tokenBodyStart = $this->scanner->consume("{")) &&
                    (($body = $this->isolateContext(
                        $flags, "parseFunctionBody"
                    )) || true) &&
                    $this->scanner->consume("}")
                ) {

                    if ($prop[0] instanceof Node\Identifier &&
                        $prop[0]->getName() === "constructor"
                    ) {
                        $kind = Node\MethodDefinition::KIND_CONSTRUCTOR;
                    }

                    $body->location->start = $tokenBodyStart->location->start;
                    $body->location->end = $this->scanner->getPosition();

                    $nodeFn = $this->createNode("FunctionExpression", $tokenFn);
                    $nodeFn->setParams($params);
                    $nodeFn->setBody($body);
                    $nodeFn->setGenerator($generator);
                    $nodeFn->setAsync($async);

                    $node = $this->createNode("MethodDefinition", $position);
                    $node->setKey($prop[0]);
                    $node->setValue($this->completeNode($nodeFn));
                    $node->setKind($kind);
                    $node->setComputed($prop[1]);
                    return $this->completeNode($node);
                }
            }
        }
        //Handle the case where "async" is a class field name
        elseif ($this->features->classFields && $async && !$generator) {
            $this->scanner->setState($state);
            $error = $async = false;
        }

        if ($error) {
            $this->error();
        } else {
            $this->scanner->setState($state);
        }
        return null;
    }
    
    /**
     * Parses parameters in an arrow function. If the parameters are wrapped in
     * round brackets, the returned value is an array where the first element
     * is the parameters list and the second element is the open round brackets,
     * this is needed to know the start position
     * 
     * @return Node\Identifier|array|null
     */
    protected function parseArrowParameters()
    {
        if ($param = $this->parseIdentifier(static::$bindingIdentifier, "=>")) {
            return $param;
        } elseif ($token = $this->scanner->consume("(")) {
            
            $params = $this->parseFormalParameterList();
            
            if ($params !== null && $this->scanner->consume(")")) {
                return array($params, $token);
            }
        }
        return null;
    }

    /**
     * Parses the body of an arrow function. The returned value is an array
     * where the first element is the function body and the second element is
     * a boolean indicating if the body is wrapped in curly braces
     *
     * @param bool  $async  Async body mode
     *
     * @return array|null
     */
    protected function parseConciseBody($async = false)
    {
        if ($token = $this->scanner->consume("{")) {

            if (($body = $this->isolateContext(
                    $async ? array(null, "allowAwait" => true) : null,
                    "parseFunctionBody"
                )) &&
                $this->scanner->consume("}")
            ) {
                $body->location->start = $token->location->start;
                $body->location->end = $this->scanner->getPosition();
                return array($body, false);
            }

            $this->error();
        } elseif (!$this->scanner->isBefore(array("{")) &&
            $body = $this->isolateContext(
                $this->features->asyncAwait ?
                array("allowYield" => false, "allowAwait" => $async) :
                array("allowYield" => false),
                "parseAssignmentExpression"
            )
        ) {
            return array($body, true);
        }
        return null;
    }
    
    /**
     * Parses an arrow function
     * 
     * @return Node\ArrowFunctionExpression|null
     */
    protected function parseArrowFunction()
    {
        $state = $this->scanner->getState();
        $async = false;
        if ($this->features->asyncAwait &&
            ($async = $this->checkAsyncFunctionStart(false))) {
            $this->scanner->consumeToken();
        }
        if (($params = $this->parseArrowParameters()) !== null) {

            if ($this->scanner->noLineTerminators() &&
                $this->scanner->consume("=>")
            ) {

                if ($body = $this->parseConciseBody((bool) $async)) {
                    if (is_array($params)) {
                        $pos = $params[1];
                        $params = $params[0];
                    } else {
                        $pos = $params;
                        $params = array($params);
                    }
                    if ($async) {
                        $pos = $async;
                    }
                    $node = $this->createNode("ArrowFunctionExpression", $pos);
                    $node->setParams($params);
                    $node->setBody($body[0]);
                    $node->setExpression($body[1]);
                    $node->setAsync((bool) $async);
                    return $this->completeNode($node);
                }

                $this->error();
            }
        }
        $this->scanner->setState($state);
        return null;
    }
    
    /**
     * Parses an object literal
     * 
     * @return Node\ObjectExpression|null
     */
    protected function parseObjectLiteral()
    {
        if ($token = $this->scanner->consume("{")) {
            
            $properties = array();
            while ($prop = $this->parsePropertyDefinition()) {
                $properties[] = $prop;
                if (!$this->scanner->consume(",")) {
                    break;
                }
            }
            
            if ($this->scanner->consume("}")) {
                
                $node = $this->createNode("ObjectExpression", $token);
                if ($properties) {
                    $node->setProperties($properties);
                }
                return $this->completeNode($node);
            }
            
            $this->error();
        }
        return null;
    }
    
    /**
     * Parses a property in an object literal
     * 
     * @return Node\Property|null
     */
    protected function parsePropertyDefinition()
    {
        if ($this->features->restSpreadProperties &&
            ($prop = $this->parseSpreadElement())) {
            return $prop;
        }

        $state = $this->scanner->getState();
        if (($property = $this->parsePropertyName()) &&
            $this->scanner->consume(":")
        ) {
            $value = $this->isolateContext(
                array("allowIn" => true), "parseAssignmentExpression"
            );
            if ($value) {
                $startPos = isset($property[2]) ? $property[2] : $property[0];
                $node = $this->createNode("Property", $startPos);
                $node->setKey($property[0]);
                $node->setValue($value);
                $node->setComputed($property[1]);
                return $this->completeNode($node);
            }

            $this->error();
            
        }
        
        $this->scanner->setState($state);
        if ($property = $this->parseMethodDefinition()) {

            $node = $this->createNode("Property", $property);
            $node->setKey($property->getKey());
            $node->setValue($property->getValue());
            $node->setComputed($property->getComputed());
            $kind = $property->getKind();
            if ($kind !== Node\MethodDefinition::KIND_GET &&
                $kind !== Node\MethodDefinition::KIND_SET
            ) {
                $node->setMethod(true);
                $node->setKind(Node\Property::KIND_INIT);
            } else {
                $node->setKind($kind);
            }
            return $this->completeNode($node);
            
        } elseif ($key = $this->parseIdentifier(static::$identifierReference)) {
            
            $node = $this->createNode("Property", $key);
            $node->setShorthand(true);
            $node->setKey($key);
            $value = $this->isolateContext(
                array("allowIn" => true), "parseInitializer"
            );
            $node->setValue($value ?: $key);
            return $this->completeNode($node);
            
        }
        return null;
    }
    
    /**
     * Parses an initializer
     * 
     * @return Node\Node|null
     */
    protected function parseInitializer()
    {
        if ($this->scanner->consume("=")) {
            
            if ($value = $this->parseAssignmentExpression()) {
                return $value;
            }
            
            $this->error();
        }
        return null;
    }
    
    /**
     * Parses an object binding pattern
     * 
     * @return Node\ObjectPattern|null
     */
    protected function parseObjectBindingPattern()
    {
        $state = $this->scanner->getState();
        if ($token = $this->scanner->consume("{")) {

            $properties = array();
            while ($prop = $this->parseBindingProperty()) {
                $properties[] = $prop;
                if (!$this->scanner->consume(",")) {
                    break;
                }
            }

            if ($this->features->restSpreadProperties &&
                ($rest = $this->parseRestProperty())) {
                $properties[] = $rest;
            }

            if ($this->scanner->consume("}")) {
                $node = $this->createNode("ObjectPattern", $token);
                if ($properties) {
                    $node->setProperties($properties);
                }
                return $this->completeNode($node);
            }

            $this->scanner->setState($state);
        }
        return null;
    }

    /**
     * Parses a rest property
     *
     * @return Node\RestElement|null
     */
    protected function parseRestProperty()
    {
        $state = $this->scanner->getState();
        if ($token = $this->scanner->consume("...")) {

            if ($argument = $this->parseIdentifier(static::$bindingIdentifier)) {
                $node = $this->createNode("RestElement", $token);
                $node->setArgument($argument);
                return $this->completeNode($node);
            }

            $this->scanner->setState($state);
        }
        return null;
    }
    
    /**
     * Parses a property in an object binding pattern
     * 
     * @return Node\AssignmentProperty|null
     */
    protected function parseBindingProperty()
    {
        $state = $this->scanner->getState();
        if (($key = $this->parsePropertyName()) &&
            $this->scanner->consume(":")
        ) {
            
            if ($value = $this->parseBindingElement()) {
                $startPos = isset($key[2]) ? $key[2] : $key[0];
                $node = $this->createNode("AssignmentProperty", $startPos);
                $node->setKey($key[0]);
                $node->setComputed($key[1]);
                $node->setValue($value);
                return $this->completeNode($node);
            }
            
            $this->scanner->setState($state);
            return null;
        }
            
        $this->scanner->setState($state);
        if ($property = $this->parseSingleNameBinding()) {
            
            $node = $this->createNode("AssignmentProperty", $property);
            $node->setShorthand(true);
            if ($property instanceof Node\AssignmentPattern) {
                $node->setKey($property->getLeft());
            } else {
                $node->setKey($property);
            }
            $node->setValue($property);
            return $this->completeNode($node);
        }
        return null;
    }
    
    /**
     * Parses an expression
     * 
     * @return Node\Node|null
     */
    protected function parseExpression()
    {
        $list = $this->charSeparatedListOf("parseAssignmentExpression");
        
        if (!$list) {
            return null;
        } elseif (count($list) === 1) {
            return $list[0];
        } else {
            $node = $this->createNode("SequenceExpression", $list);
            $node->setExpressions($list);
            return $this->completeNode($node);
        }
    }
    
    /**
     * Parses an assignment expression
     * 
     * @return Node\Node|null
     */
    protected function parseAssignmentExpression()
    {
        if ($expr = $this->parseArrowFunction()) {
            return $expr;
        } elseif ($this->context->allowYield && $expr = $this->parseYieldExpression()) {
            return $expr;
        } elseif ($expr = $this->parseConditionalExpression()) {
            
            $exprTypes = array(
                "ConditionalExpression", "LogicalExpression",
                "BinaryExpression", "UpdateExpression", "UnaryExpression"
            );
            
            if (!in_array($expr->getType(), $exprTypes)) {
                
                $operators = $this->assignmentOperators;
                if ($operator = $this->scanner->consumeOneOf($operators)) {

                    if ($expr->getType() === "ChainExpression") {
                        $this->error(
                            "Optional chain can't appear in left-hand side"
                        );
                    }

                    $right = $this->parseAssignmentExpression();
                    
                    if ($right) {
                        $node = $this->createNode(
                            "AssignmentExpression", $expr
                        );
                        $node->setLeft($this->expressionToPattern($expr));
                        $node->setOperator($operator->value);
                        $node->setRight($right);
                        return $this->completeNode($node);
                    }
                    $this->error();
                }
            }
            return $expr;
        }
        return null;
    }
    
    /**
     * Parses a conditional expression
     * 
     * @return Node\Node|null
     */
    protected function parseConditionalExpression()
    {
        if ($test = $this->parseLogicalBinaryExpression()) {
            
            if ($this->scanner->consume("?")) {
                
                $consequent = $this->isolateContext(
                    array("allowIn" => true), "parseAssignmentExpression"
                );
                if ($consequent && $this->scanner->consume(":") &&
                    $alternate = $this->parseAssignmentExpression()
                ) {
                
                    $node = $this->createNode("ConditionalExpression", $test);
                    $node->setTest($test);
                    $node->setConsequent($consequent);
                    $node->setAlternate($alternate);
                    return $this->completeNode($node);
                }
                
                $this->error();
            } else {
                return $test;
            }
        }
        return null;
    }
    
    /**
     * Parses a logical or a binary expression
     * 
     * @return Node\Node|null
     */
    protected function parseLogicalBinaryExpression()
    {
        $operators = $this->logicalBinaryOperators;
        if (!$this->context->allowIn) {
            unset($operators["in"]);
        }
        
        if (!($exp = $this->parseUnaryExpression())) {
            if (
                !$this->features->classFieldsPrivateIn ||
                !$this->context->allowIn
            ) {
                return null;
            }
            //Support "#private in x" syntax
            $state = $this->scanner->getState();
            if (
                !($exp = $this->parsePrivateIdentifier()) ||
                !$this->scanner->isBefore(array("in"))
            ) {
                if ($exp) {
                    $this->scanner->setState($state);
                }
                return null;
            }
        }
        
        $list = array($exp);
        $coalescingFound = $andOrFound = false;
        while ($token = $this->scanner->consumeOneOf(array_keys($operators))) {
            $op = $token->value;
            // Coalescing and logical expressions can't be used together
            if ($op === "??") {
                $coalescingFound = true;
            } elseif ($op === "&&" || $op === "||") {
                $andOrFound = true;
            }
            if ($coalescingFound && $andOrFound) {
                $this->error(
                    "Logical expressions must be wrapped in parentheses when " .
                    "inside coalesce expressions"
                );
            }
            if (!($exp = $this->parseUnaryExpression())) {
                $this->error();
            }
            $list[] = $op;
            $list[] = $exp;
        }
        
        $len = count($list);
        if ($len > 1) {
            $maxGrade = max($operators);
            for ($grade = $maxGrade; $grade >= 0; $grade--) {
                $class = $grade < 2 ? "LogicalExpression" : "BinaryExpression";
                $r2l = $grade === 10;
                //Exponentiation operator must be parsed right to left
                if ($r2l) {
                    $i = $len - 2;
                    $step = -2;
                } else {
                    $i = 1;
                    $step = 2;
                }
                for (; ($r2l && $i > 0) || (!$r2l && $i < $len); $i += $step) {
                    if ($operators[$list[$i]] === $grade) {
                        $node = $this->createNode($class, $list[$i - 1]);
                        $node->setLeft($list[$i - 1]);
                        $node->setOperator($list[$i]);
                        $node->setRight($list[$i + 1]);
                        $node = $this->completeNode(
                            $node, $list[$i + 1]->location->end
                        );
                        array_splice($list, $i - 1, 3, array($node));
                        if (!$r2l) {
                            $i -= $step;
                        }
                        $len = count($list);
                    }
                }
            }
        }
        return $list[0];
    }
    
    /**
     * Parses a unary expression
     * 
     * @return Node\Node|null
     */
    protected function parseUnaryExpression()
    {
        $operators = $this->unaryOperators;
        if ($this->features->asyncAwait && $this->context->allowAwait) {
            $operators[] = "await";
        }
        if ($expr = $this->parsePostfixExpression()) {
            return $expr;
        } elseif ($token = $this->scanner->consumeOneOf($operators)) {
            if ($argument = $this->parseUnaryExpression()) {

                $op = $token->value;

                //Deleting a variable without accessing its properties is a
                //syntax error in strict mode
                if ($op === "delete" &&
                    $this->scanner->getStrictMode() &&
                    $argument instanceof Node\Identifier) {
                    $this->error(
                        "Deleting an unqualified identifier is not allowed in strict mode"
                    );
                }

                if ($this->features->asyncAwait && $op === "await") {
                    $node = $this->createNode("AwaitExpression", $token);
                } else {
                    if ($op === "++" || $op === "--") {
                        if ($argument->getType() === "ChainExpression") {
                            $this->error(
                                "Optional chain can't appear in left-hand side"
                            );
                        }
                        $node = $this->createNode("UpdateExpression", $token);
                        $node->setPrefix(true);
                    } else {
                        $node = $this->createNode("UnaryExpression", $token);
                    }
                    $node->setOperator($op);
                }
                $node->setArgument($argument);
                return $this->completeNode($node);
            }

            $this->error();
        }
        return null;
    }
    
    /**
     * Parses a postfix expression
     * 
     * @return Node\Node|null
     */
    protected function parsePostfixExpression()
    {
        if ($argument = $this->parseLeftHandSideExpression()) {

            if ($this->scanner->noLineTerminators() &&
                $token = $this->scanner->consumeOneOf($this->postfixOperators)
            ) {

                if ($argument->getType() === "ChainExpression") {
                    $this->error(
                        "Optional chain can't appear in left-hand side"
                    );
                }
                
                $node = $this->createNode("UpdateExpression", $argument);
                $node->setOperator($token->value);
                $node->setArgument($argument);
                return $this->completeNode($node);
            }
            
            return $argument;
        }
        return null;
    }
    
    /**
     * Parses a left hand side expression
     * 
     * @return Node\Node|null
     */
    protected function parseLeftHandSideExpression()
    {
        $object = null;
        $newTokens = array();
        
        //Parse all occurrences of "new"
        if ($this->scanner->isBefore(array("new"))) {
            while ($newToken = $this->scanner->consume("new")) {
                if ($this->scanner->consume(".")) {
                    //new.target
                    if (!$this->scanner->consume("target")) {
                        $this->error();
                    }
                    $node = $this->createNode("MetaProperty", $newToken);
                    $node->setMeta("new");
                    $node->setProperty("target");
                    $object = $this->completeNode($node);
                    break;
                }
                $newTokens[] = $newToken;
            }
        } elseif ($this->features->importMeta &&
            $this->sourceType === \Peast\Peast::SOURCE_TYPE_MODULE &&
            $this->scanner->isBefore(array(array("import", ".")), true)
        ) {
            //import.meta
            $importToken = $this->scanner->consume("import");
            $this->scanner->consume(".");
            if (!$this->scanner->consume("meta")) {
                $this->error();
            }
            $node = $this->createNode("MetaProperty", $importToken);
            $node->setMeta("import");
            $node->setProperty("meta");
            $object = $this->completeNode($node);
        }
        
        $newTokensCount = count($newTokens);
        
        if (!$object &&
            !($object = $this->parseSuperPropertyOrCall()) &&
            !($this->features->dynamicImport &&
                ($object = $this->parseImportCall())
            ) &&
            !($object = $this->parsePrimaryExpression())
        ) {
            
            if ($newTokensCount) {
                $this->error();
            }
            return null;
        }
        
        $valid = true;
        $optionalChain = false;
        $properties = array();
        while (true) {
            $optional = false;
            if ($opToken = $this->scanner->consumeOneOf(array("?.", "."))) {
                $isOptChain = $opToken->value == "?.";
                if ($isOptChain) {
                    $optionalChain = $optional = true;
                }
                if (
                    ($this->features->privateMethodsAndFields && ($property = $this->parsePrivateIdentifier())) ||
                    ($property = $this->parseIdentifier(static::$identifierName))
                ) {
                    $valid = true;
                    $properties[] = array(
                        "type"=> "id",
                        "info" => $property,
                        "optional" => $optional
                    );
                    continue;
                } else {
                    $valid = false;
                    if (!$isOptChain) {
                        break;
                    }
                }
            }
            if ($this->scanner->consume("[")) {
                if (($property = $this->isolateContext(
                        array("allowIn" => true), "parseExpression"
                    )) &&
                    $this->scanner->consume("]")
                ) {
                    $valid = true;
                    $properties[] = array(
                        "type" => "computed",
                        "info" => array(
                            $property, $this->scanner->getPosition()
                        ),
                        "optional" => $optional
                    );
                } else {
                    $valid = false;
                    break;
                }
            } elseif ($property = $this->parseTemplateLiteral(true)) {
                if ($optionalChain) {
                    $this->error(
                        "Optional chain can't appear in tagged template expressions"
                    );
                }
                $valid = true;
                $properties[] = array(
                    "type"=> "template",
                    "info" => $property,
                    "optional" => $optional
                );
            } elseif (($args = $this->parseArguments()) !== null) {
                $valid = true;
                $properties[] = array(
                    "type"=> "args",
                    "info" => array($args, $this->scanner->getPosition()),
                    "optional" => $optional
                );
            } else {
                break;
            }
        }
        
        $propCount = count($properties);
        
        if (!$valid) {
            $this->error();
        } elseif (!$propCount && !$newTokensCount) {
            return $object;
        }
        
        $node = null;
        $endPos = $object->location->end;
        $optionalChainStarted = false;
        foreach ($properties as $i => $property) {
            $lastNode = $node ?: $object;
            if ($property["optional"]) {
                $optionalChainStarted = true;
            }
            if ($property["type"] === "args") {
                if ($newTokensCount) {
                    if ($optionalChainStarted) {
                        $this->error(
                            "Optional chain can't appear in new expressions"
                        );
                    }
                    $node = $this->createNode(
                        "NewExpression", array_pop($newTokens)
                    );
                    $newTokensCount--;
                } else {
                    $node = $this->createNode("CallExpression", $lastNode);
                    $node->setOptional($property["optional"]);
                }
                $node->setCallee($lastNode);
                $node->setArguments($property["info"][0]);
                $endPos = $property["info"][1];
            } elseif ($property["type"] === "id") {
                $node = $this->createNode("MemberExpression", $lastNode);
                $node->setObject($lastNode);
                $node->setOptional($property["optional"]);
                $node->setProperty($property["info"]);
                $endPos = $property["info"]->location->end;
            } elseif ($property["type"] === "computed") {
                $node = $this->createNode("MemberExpression", $lastNode);
                $node->setObject($lastNode);
                $node->setProperty($property["info"][0]);
                $node->setOptional($property["optional"]);
                $node->setComputed(true);
                $endPos = $property["info"][1];
            } elseif ($property["type"] === "template") {
                $node = $this->createNode("TaggedTemplateExpression", $object);
                $node->setTag($lastNode);
                $node->setQuasi($property["info"]);
                $endPos = $property["info"]->location->end;
            }
            $node = $this->completeNode($node, $endPos);
        }
        
        //Wrap the result in multiple NewExpression if there are "new" tokens
        if ($newTokensCount) {
            for ($i = $newTokensCount - 1; $i >= 0; $i--) {
                $lastNode = $node ?: $object;
                $node = $this->createNode("NewExpression", $newTokens[$i]);
                $node->setCallee($lastNode);
                $node = $this->completeNode($node);
            }
        }

        //Wrap the result in a chain expression if required
        if ($optionalChain) {
            $prevNode = $node;
            $node = $this->createNode("ChainExpression", $prevNode);
            $node->setExpression($prevNode);
            $node = $this->completeNode($node);
        }
        
        return $node;
    }
    
    /**
     * Parses a spread element
     * 
     * @return Node\SpreadElement|null
     */
    protected function parseSpreadElement()
    {
        if ($token = $this->scanner->consume("...")) {
            
            $argument = $this->isolateContext(
                array("allowIn" => true), "parseAssignmentExpression"
            );
            if ($argument) {
                $node = $this->createNode("SpreadElement", $token);
                $node->setArgument($argument);
                return $this->completeNode($node);
            }
            
            $this->error();
        }
        return null;
    }
    
    /**
     * Parses an array literal
     * 
     * @return Node\ArrayExpression|null
     */
    protected function parseArrayLiteral()
    {
        if ($token = $this->scanner->consume("[")) {
            
            $elements = array();
            while (true) {
                if ($elision = $this->parseElision()) {
                    $elements = array_merge(
                        $elements, array_fill(0, $elision, null)
                    );
                }
                if (($element = $this->parseSpreadElement()) ||
                    ($element = $this->isolateContext(
                        array("allowIn" => true), "parseAssignmentExpression"
                    ))
                ) {
                    $elements[] = $element;
                    if (!$this->scanner->consume(",")) {
                        break;
                    }
                } else {
                    break;
                }
            }
            
            if ($this->scanner->consume("]")) {
                $node = $this->createNode("ArrayExpression", $token);
                $node->setElements($elements);
                return $this->completeNode($node);
            }
            
            $this->error();
        }
        return null;
    }
    
    /**
     * Parses an arguments list wrapped in round brackets
     * 
     * @return array|null
     */
    protected function parseArguments()
    {
        if ($this->scanner->consume("(")) {
            
            if (($args = $this->parseArgumentList()) !== null &&
                $this->scanner->consume(")")
            ) {
                return $args;
            }
            
            $this->error();
        }
        return null;
    }
    
    /**
     * Parses an arguments list
     * 
     * @return array|null
     */
    protected function parseArgumentList()
    {
        $list = array();
        $hasComma = false;
        while (true) {
            $spread = $this->scanner->consume("...");
            $exp = $this->isolateContext(
                array("allowIn" => true), "parseAssignmentExpression"
            );

            if (!$exp) {
                //If there's no expression and the spread dots have been found
                //or there is a trailing comma that is not allowed, throw an
                //error
                if ($spread ||
                    ($hasComma &&
                    !$this->features->trailingCommaFunctionCallDeclaration)) {
                    $this->error();
                }
                break;
            }

            if ($spread) {
                $node = $this->createNode("SpreadElement", $spread);
                $node->setArgument($exp);
                $list[] = $this->completeNode($node);
            } else {
                $list[] = $exp;
            }

            if (!$this->scanner->consume(",")) {
                break;
            }
            $hasComma = true;
        }
        return $list;
    }
    
    /**
     * Parses a super call or a super property
     * 
     * @return Node\Node|null
     */
    protected function parseSuperPropertyOrCall()
    {
        if ($token = $this->scanner->consume("super")) {
            
            $super = $this->completeNode($this->createNode("Super", $token));
            
            if (($args = $this->parseArguments()) !== null) {
                $node = $this->createNode("CallExpression", $token);
                $node->setArguments($args);
                $node->setCallee($super);
                return $this->completeNode($node);
            }
            
            $node = $this->createNode("MemberExpression", $token);
            $node->setObject($super);
            
            if ($this->scanner->consume(".")) {
                
                if ($property = $this->parseIdentifier(static::$identifierName)) {
                    $node->setProperty($property);
                    return $this->completeNode($node);
                }
            } elseif ($this->scanner->consume("[") &&
                ($property = $this->isolateContext(
                    array("allowIn" => true), "parseExpression"
                )) &&
                $this->scanner->consume("]")
            ) {
                
                $node->setProperty($property);
                $node->setComputed(true);
                return $this->completeNode($node);
            }
            
            $this->error();
        }
        return null;
    }
    
    /**
     * Parses a primary expression
     * 
     * @return Node\Node|null
     */
    protected function parsePrimaryExpression()
    {
        if ($token = $this->scanner->consume("this")) {
            $node = $this->createNode("ThisExpression", $token);
            return $this->completeNode($node);
        } elseif ($exp = $this->parseFunctionOrGeneratorExpression()) {
            return $exp;
        } elseif ($exp = $this->parseClassExpression()) {
            return $exp;
        } elseif ($exp = $this->parseIdentifier(static::$identifierReference)) {
            return $exp;
        } elseif ($exp = $this->parseLiteral()) {
            return $exp;
        } elseif ($exp = $this->parseArrayLiteral()) {
            return $exp;
        } elseif ($exp = $this->parseObjectLiteral()) {
            return $exp;
        } elseif ($exp = $this->parseRegularExpressionLiteral()) {
            return $exp;
        } elseif ($exp = $this->parseTemplateLiteral()) {
            return $exp;
        } elseif ($this->jsx && ($exp = $this->parseJSXFragment())) {
            return $exp;
        } elseif ($this->jsx && ($exp = $this->parseJSXElement())) {
            return $exp;
        } elseif ($token = $this->scanner->consume("(")) {
            
            if (($exp = $this->isolateContext(
                    array("allowIn" => true), "parseExpression"
                )) &&
                $this->scanner->consume(")")
            ) {
                
                $node = $this->createNode("ParenthesizedExpression", $token);
                $node->setExpression($exp);
                return $this->completeNode($node);
            }
            
            $this->error();
        }
        return null;
    }

    /**
     * Parses a private identifier
     * 
     * @return Node\PrivateIdentifier|null
     */
    protected function parsePrivateIdentifier()
    {
        $token = $this->scanner->getToken();
        if (!$token || $token->type !== Token::TYPE_PRIVATE_IDENTIFIER) {
            return null;
        }
        $this->scanner->consumeToken();
        $node = $this->createNode("PrivateIdentifier", $token);
        $node->setName(substr($token->value, 1));
        return $this->completeNode($node);
    }
    
    /**
     * Parses an identifier
     * 
     * @param int   $mode       Parsing mode, one of the id parsing mode
     *                          constants
     * @param string $after     If a string is passed in this parameter, the
     *                          identifier is parsed only if precedes this string
     * 
     * @return Node\Identifier|null
     */
    protected function parseIdentifier($mode, $after = null)
    {
        $token = $this->scanner->getToken();
        if (!$token) {
            return null;
        }
        if ($after !== null) {
            $next = $this->scanner->getNextToken();
            if (!$next || $next->value !== $after) {
                return null;
            }
        }
        $type = $token->type;
        switch ($type) {
            case Token::TYPE_BOOLEAN_LITERAL:
            case Token::TYPE_NULL_LITERAL:
                if ($mode !== self::ID_ALLOW_ALL) {
                    return null;
                }
            break;
            case Token::TYPE_KEYWORD:
                if ($mode === self::ID_ALLOW_NOTHING) {
                    return null;
                } elseif ($mode === self::ID_MIXED &&
                    $this->scanner->isStrictModeKeyword($token)
                ) {
                    return null;
                }
            break;
            default:
                if ($type !== Token::TYPE_IDENTIFIER) {
                    return null;
                }
            break;
        }
        
        //Exclude keywords that depend on parser context
        $value = $token->value;
        if ($mode === self::ID_MIXED &&
            isset($this->contextKeywords[$value]) &&
            $this->context->{$this->contextKeywords[$value]}
        ) {
            return null;
        }
        
        $this->scanner->consumeToken();
        $node = $this->createNode("Identifier", $token);
        $node->setRawName($value);
        return $this->completeNode($node);
    }
    
    /**
     * Parses a literal
     * 
     * @return Node\Literal|null
     */
    protected function parseLiteral()
    {
        if ($token = $this->scanner->getToken()) {
            if ($token->type === Token::TYPE_NULL_LITERAL) {
                $this->scanner->consumeToken();
                $node = $this->createNode("NullLiteral", $token);
                return $this->completeNode($node);
            } elseif ($token->type === Token::TYPE_BOOLEAN_LITERAL) {
                $this->scanner->consumeToken();
                $node = $this->createNode("BooleanLiteral", $token);
                $node->setRaw($token->value);
                return $this->completeNode($node);
            } elseif ($literal = $this->parseStringLiteral()) {
                return $literal;
            } elseif ($literal = $this->parseNumericLiteral()) {
                return $literal;
            }
        }
        return null;
    }
    
    /**
     * Parses a string literal
     * 
     * @return Node\StringLiteral|null
     */
    protected function parseStringLiteral()
    {
        $token = $this->scanner->getToken();
        if ($token && $token->type === Token::TYPE_STRING_LITERAL) {
            $val = $token->value;
            $this->checkInvalidEscapeSequences($val);
            $this->scanner->consumeToken();
            $node = $this->createNode("StringLiteral", $token);
            $node->setRaw($val);
            return $this->completeNode($node);
        }
        return null;
    }
    
    /**
     * Parses a numeric literal
     * 
     * @return Node\NumericLiteral|Node\BigIntLiteral|null
     */
    protected function parseNumericLiteral()
    {
        $token = $this->scanner->getToken();
        if ($token && $token->type === Token::TYPE_NUMERIC_LITERAL) {
            $val = $token->value;
            $this->checkInvalidEscapeSequences($val, true);
            $this->scanner->consumeToken();
            $node = $this->createNode("NumericLiteral", $token);
            $node->setRaw($val);
            return $this->completeNode($node);
        } elseif ($token && $token->type === Token::TYPE_BIGINT_LITERAL) {
            $val = $token->value;
            $this->checkInvalidEscapeSequences($val, true);
            $this->scanner->consumeToken();
            $node = $this->createNode("BigIntLiteral", $token);
            $node->setRaw($val);
            return $this->completeNode($node);
        }
        return null;
    }
    
    /**
     * Parses a template literal
     * 
     * @param bool $tagged True if the template is tagged
     * 
     * @return Node\Literal|null
     */
    protected function parseTemplateLiteral($tagged = false)
    {
        $token = $this->scanner->getToken();
        
        if (!$token || $token->type !== Token::TYPE_TEMPLATE) {
            return null;
        }
        
        //Do not parse templates parts
        $val = $token->value;
        if ($val[0] !== "`") {
            return null;
        }
        
        $quasis = $expressions = array();
        $valid = false;
        do {
            $this->scanner->consumeToken();
            $val = $token->value;
            $this->checkInvalidEscapeSequences($val, false, true, $tagged);
            $lastChar = substr($val, -1);
            
            $quasi = $this->createNode("TemplateElement", $token);
            $quasi->setRawValue($val);
            if ($lastChar === "`") {
                $quasi->setTail(true);
                $quasis[] = $this->completeNode($quasi);
                $valid = true;
                break;
            } else {
                $quasis[] = $this->completeNode($quasi);
                $exp = $this->isolateContext(
                    array("allowIn" => true), "parseExpression"
                );
                if ($exp) {
                    $expressions[] = $exp;
                } else {
                    $valid = false;
                    break;
                }
            }
            
            $token = $this->scanner->getToken();
        } while ($token && $token->type === Token::TYPE_TEMPLATE);
        
        if ($valid) {
            $node = $this->createNode("TemplateLiteral", $quasis[0]);
            $node->setQuasis($quasis);
            $node->setExpressions($expressions);
            return $this->completeNode($node);
        }
        
        $this->error();
    }
    
    /**
     * Parses a regular expression literal
     * 
     * @return Node\Literal|null
     */
    protected function parseRegularExpressionLiteral()
    {
        if ($token = $this->scanner->reconsumeCurrentTokenAsRegexp()) {
            $this->scanner->consumeToken();
            $node = $this->createNode("RegExpLiteral", $token);
            $node->setRaw($token->value);
            return $this->completeNode($node);
        }
        return null;
    }
    
    /**
     * Parse directive prologues. The result is an array where the first element
     * is the array of parsed nodes and the second element is the array of
     * directive prologues values
     * 
     * @return array|null
     */
    protected function parseDirectivePrologues()
    {
        $directives = $nodes = array();
        while (($token = $this->scanner->getToken()) &&
            $token->type === Token::TYPE_STRING_LITERAL
        ) {
            $directive = substr($token->value, 1, -1);
            if ($directive === "use strict") {
                $directives[] = $directive;
                $directiveNode = $this->parseStringLiteral();
                $this->assertEndOfStatement();
                $node = $this->createNode("ExpressionStatement", $directiveNode);
                $node->setExpression($directiveNode);
                $nodes[] = $this->completeNode($node);
            } else {
                break;
            }
        }
        return count($nodes) ? array($nodes, $directives) : null;
    }

    /**
     * Parses an import call
     *
     * @return Node\Node|null
     */
    protected function parseImportCall()
    {
        if (($token = $this->scanner->consume("import")) &&
            $this->scanner->consume("(")) {

            if (($source = $this->isolateContext(
                    array("allowIn" => true), "parseAssignmentExpression"
                )) &&
                $this->scanner->consume(")")
            ) {
                $node = $this->createNode("ImportExpression", $token);
                $node->setSource($source);
                return $this->completeNode($node);
            }

            $this->error();
        }
        return null;
    }
    
    /**
     * Checks if the given string or number contains invalid escape sequences
     * 
     * @param string  $val                      Value to check
     * @param bool    $number                   True if the value is a number
     * @param bool    $forceLegacyOctalCheck    True to force legacy octal
     *                                          form check
     * @param bool    $taggedTemplate           True if the value is a tagged
     *                                          template
     * 
     * @return void
     */
    protected function checkInvalidEscapeSequences(
        $val, $number = false, $forceLegacyOctalCheck = false,
        $taggedTemplate = false
    ) {
        if ($this->features->skipEscapeSeqCheckInTaggedTemplates &&
            $taggedTemplate) {
            return;
        }
        $checkLegacyOctal = $forceLegacyOctalCheck || $this->scanner->getStrictMode();
        if ($number) {
            if ($val && $val[0] === "0" && preg_match("#^0[0-9_]+$#", $val)) {
                if ($checkLegacyOctal) {
                    $this->error(
                        "Octal literals are not allowed in strict mode"
                    );
                }
                if ($this->features->numericLiteralSeparator &&
                    strpos($val, '_') !== false
                ) {
                    $this->error(
                        "Numeric separators are not allowed in legacy octal numbers"
                    );
                }
            }
        } elseif (strpos($val, "\\") !== false) {
            $hex = "0-9a-fA-F";
            $invalidSyntax = array(
                "x[$hex]?[^$hex]",
                "x[$hex]?$",
                "u\{\}",
                "u\{(?:[$hex]*[^$hex\}]+)+[$hex]*\}",
                "u\{[^\}]*$",
                "u(?!{)[$hex]{0,3}[^$hex\{]",
                "u[$hex]{0,3}$"
            );
            if ($checkLegacyOctal) {
                $invalidSyntax[] = "\d{2}";
                $invalidSyntax[] = "[1-7]";
                $invalidSyntax[] = "0[89]";
            }
            $reg = "#(\\\\+)(" . implode("|", $invalidSyntax) . ")#";
            if (preg_match_all($reg, $val, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    if (strlen($match[1]) % 2) {
                        $first = $match[2][0];
                        if ($first === "u") {
                            $err = "Malformed unicode escape sequence";
                        } elseif ($first === "x") {
                            $err = "Malformed hexadecimal escape sequence";
                        } else {
                            $err = "Octal literals are not allowed in strict mode";
                        }
                        $this->error($err);
                    }
                }
            }
        }
    }
}