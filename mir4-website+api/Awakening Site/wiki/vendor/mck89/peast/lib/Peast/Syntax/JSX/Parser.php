<?php
/**
 * This file is part of the Peast package
 *
 * (c) Marco Marchiò <marco.mm89@gmail.com>
 *
 * For the full copyright and license information refer to the LICENSE file
 * distributed with this source code
 */
namespace Peast\Syntax\JSX;

use Peast\Syntax\Token;

/**
 * JSX parser trait
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
trait Parser
{
    /**
     * Creates a JSX node
     * 
     * @param string $nodeType Node's type
     * @param mixed  $position Node's start position
     * 
     * @return \Peast\Syntax\Node\Node
     */
    protected function createJSXNode($nodeType, $position)
    {
        return $this->createNode("JSX\\$nodeType", $position);
    }
    
    /**
     * Parses a jsx fragment
     * 
     * @return \Peast\Syntax\Node\JSX\JSXFragment|null
     */
    protected function parseJSXFragment()
    {
        $startOpeningToken = $this->scanner->getToken();
        if (!$startOpeningToken || $startOpeningToken->value !== "<") {
            return null;
        }
        
        $endOpeningToken = $this->scanner->getNextToken();
        if (!$endOpeningToken || $endOpeningToken->value !== ">") {
            return null;
        }
        
        $this->scanner->consumeToken();
        $this->scanner->consumeToken();
        
        $children = $this->parseJSXChildren();
        
        if (!($startClosingToken = $this->scanner->consume("<")) ||
            !$this->scanner->consume("/") ||
            !$this->scanner->reconsumeCurrentTokenInJSXMode() ||
            $endOpeningToken->value !== ">") {
            $this->error();
        }
        $this->scanner->consumeToken();
        
        //Opening tag
        $openingNode = $this->createJSXNode(
            "JSXOpeningFragment",
            $startOpeningToken
        );
        $this->completeNode(
            $openingNode,
            $endOpeningToken->location->end
        );
        
        //Closing tag
        $closingNode = $this->createJSXNode(
            "JSXClosingFragment",
            $startClosingToken
        );
        $this->completeNode($closingNode);
        
        //Fragment
        $node = $this->createJSXNode("JSXFragment", $startOpeningToken);
        $node->setOpeningFragment($openingNode);
        $node->setClosingFragment($closingNode);
        if ($children) {
            $node->setChildren($children);
        }
        return $this->completeNode($node);
    }
    
    /**
     * Parses a group of jsx children
     * 
     * @return \Peast\Syntax\Node\Node[]|null
     */
    protected function parseJSXChildren()
    {
        $children = array();
        while ($child = $this->parseJSXChild()) {
            $children[] = $child;
        }
        return count($children) ? $children : null;
    }
    
    /**
     * Parses a jsx child
     * 
     * @return \Peast\Syntax\Node\Node|null
     */
    protected function parseJSXChild()
    {
        if ($node = $this->parseJSXText()) {
            return $node;
        } elseif ($node = $this->parseJSXFragment()) {
            return $node;
        } elseif($node = $this->parseJSXElement()) {
            return $node;
        } elseif ($startToken = $this->scanner->consume("{")) {
            $spread = $this->scanner->consume("...");
            $exp = $this->parseAssignmentExpression();
            $midPos = $this->scanner->getPosition();
            if (($spread && !$exp) || !$this->scanner->consume("}")) {
                $this->error();
            }
            $node = $this->createJSXNode(
                $spread ? "JSXSpreadChild" : "JSXExpressionContainer",
                $startToken
            );
            if (!$exp) {
                $exp = $this->createJSXNode("JSXEmptyExpression", $midPos);
                $this->completeNode($exp, $midPos);
            }
            $node->setExpression($exp);
            return $this->completeNode($node);
        }
        return null;
    }
    
    /**
     * Parses a jsx text
     * 
     * @return \Peast\Syntax\Node\JSX\JSXText|null
     */
    protected function parseJSXText()
    {
        if (!($token = $this->scanner->reconsumeCurrentTokenAsJSXText())) {
            return null;
        }
        $this->scanner->consumeToken();
        $node = $this->createJSXNode("JSXText", $token);
        $node->setRaw($token->value);
        return $this->completeNode($node, $token->location->end);
    }
    
    /**
     * Parses a jsx element
     * 
     * @return \Peast\Syntax\Node\JSX\JSXElement|null
     */
    protected function parseJSXElement()
    {
        $startOpeningToken = $this->scanner->getToken();
        if (!$startOpeningToken || $startOpeningToken->value !== "<") {
            return null;
        }
        
        $nextToken = $this->scanner->getNextToken();
        if ($nextToken && $nextToken->value === "/") {
            return null;
        }
        
        $this->scanner->consumeToken();
        
        if (!($name = $this->parseJSXIdentifierOrMemberExpression())) {
            $this->error();
        }
        
        $attributes = $this->parseJSXAttributes();
        
        $selfClosing = $this->scanner->consume("/");
        
        $endOpeningToken = $this->scanner->reconsumeCurrentTokenInJSXMode();
        if (!$endOpeningToken || $endOpeningToken->value !== ">") {
            $this->error();
        }
        $this->scanner->consumeToken();
        
        if (!$selfClosing) {
            
            $children = $this->parseJSXChildren();
            
            if (
                ($startClosingToken = $this->scanner->consume("<")) &&
                $this->scanner->consume("/") &&
                ($closingName = $this->parseJSXIdentifierOrMemberExpression()) &&
                ($endClosingToken = $this->scanner->reconsumeCurrentTokenInJSXMode()) &&
                ($endClosingToken->value === ">")
            ) {
                $this->scanner->consumeToken();
                if (!$this->isSameJSXElementName($name, $closingName)) {
                    $this->error("Closing tag does not match opening tag");
                }
            } else {
                $this->error();
            }
            
        }
        
        //Opening tag
        $openingNode = $this->createJSXNode(
            "JSXOpeningElement",
            $startOpeningToken
        );
        $openingNode->setName($name);
        $openingNode->setSelfClosing($selfClosing);
        if ($attributes) {
            $openingNode->setAttributes($attributes);
        }
        $this->completeNode(
            $openingNode,
            $endOpeningToken->location->end
        );
        
        //Closing tag
        $closingNode = null;
        if (!$selfClosing) {
            $closingNode = $this->createJSXNode(
                "JSXClosingElement",
                $startClosingToken
            );
            $closingNode->setName($closingName);
            $this->completeNode($closingNode);
        }
        
        //Element
        $node = $this->createJSXNode("JSXElement", $startOpeningToken);
        $node->setOpeningElement($openingNode);
        if ($closingNode) {
            $node->setClosingElement($closingNode);
            if ($children) {
                $node->setChildren($children);
            }
        }
        return $this->completeNode($node);
    }
    
    /**
     * Parses a jsx identifier, namespaced identifier or member expression
     * 
     * @param bool $allowMember True to allow member expressions
     * 
     * @return \Peast\Syntax\Node\Node|null
     */
    protected function parseJSXIdentifierOrMemberExpression($allowMember = true)
    {
        $idToken = $this->scanner->reconsumeCurrentTokenInJSXMode();
        if (!$idToken || $idToken->type !== Token::TYPE_JSX_IDENTIFIER) {
            return null;
        }
        $this->scanner->consumeToken();
        
        $idNode = $this->createJSXNode("JSXIdentifier", $idToken);
        $idNode->setName($idToken->value);
        $idNode = $this->completeNode($idNode);
        
        //Namespaced identifier
        if ($this->scanner->consume(":")) {
            
            $idToken2 = $this->scanner->reconsumeCurrentTokenInJSXMode();
            if (!$idToken2 || $idToken2->type !== Token::TYPE_JSX_IDENTIFIER) {
                $this->error();
            }
            $this->scanner->consumeToken();
            
            $idNode2 = $this->createJSXNode("JSXIdentifier", $idToken2);
            $idNode2->setName($idToken2->value);
            $idNode2 = $this->completeNode($idNode2);
            
            $node = $this->createJSXNode("JSXNamespacedName", $idToken);
            $node->setNamespace($idNode);
            $node->setName($idNode2);
            return $this->completeNode($node);
            
        }
        
        //Get following identifiers
        $nextIds = array();
        if ($allowMember) {
            while ($this->scanner->consume(".")) {
                $nextId = $this->scanner->reconsumeCurrentTokenInJSXMode();
                if (!$nextId || $nextId->type !== Token::TYPE_JSX_IDENTIFIER) {
                    $this->error();
                }
                $this->scanner->consumeToken();
                $nextIds[] = $nextId;
            }
        }
        
        //Create the member expression if required
        $objectNode = $idNode;
        foreach ($nextIds as $nid) {
            $propEnd = $nid->location->end;
            $propNode = $this->createJSXNode("JSXIdentifier", $nid);
            $propNode->setName($nid->value);
            $propNode = $this->completeNode($propNode, $propEnd);
            
            $node = $this->createJSXNode("JSXMemberExpression", $objectNode);
            $node->setObject($objectNode);
            $node->setProperty($propNode);
            $objectNode = $this->completeNode($node, $propEnd);
        }
        
        return $objectNode;
    }
    
    /**
     * Parses a jsx attributes list
     * 
     * @return \Peast\Syntax\Node\Node[]|null
     */
    protected function parseJSXAttributes()
    {
        $attributes = array();
        while (
            ($attr = $this->parseJSXSpreadAttribute()) ||
            ($attr = $this->parseJSXAttribute())
        ) {
            $attributes[] = $attr;
        }
        return count($attributes) ? $attributes : null;
    }
    
    /**
     * Parses a jsx spread attribute
     * 
     * @return \Peast\Syntax\Node\JSX\JSXSpreadAttribute|null
     */
    protected function parseJSXSpreadAttribute()
    {
        if (!($openToken = $this->scanner->consume("{"))) {
            return null;
        }
        
        if (
            $this->scanner->consume("...") &&
            ($exp = $this->parseAssignmentExpression()) &&
            $this->scanner->consume("}")
        ) {
            $node = $this->createJSXNode("JSXSpreadAttribute", $openToken);
            $node->setArgument($exp);
            return $this->completeNode($node);
        }
        
        $this->error();
    }
    
    /**
     * Parses a jsx spread attribute
     * 
     * @return \Peast\Syntax\Node\JSX\JSXSpreadAttribute|null
     */
    protected function parseJSXAttribute()
    {
        if (!($name = $this->parseJSXIdentifierOrMemberExpression(false))) {
            return null;
        }
        
        $value = null;
        if ($this->scanner->consume("=")) {
            $strToken = $this->scanner->reconsumeCurrentTokenInJSXMode();
            if ($strToken && $strToken->type === Token::TYPE_STRING_LITERAL) {
                $this->scanner->consumeToken();
                $value = $this->createNode("StringLiteral", $strToken);
                $value->setRaw($strToken->value);
                $value = $this->completeNode($value);
            } elseif ($startExp = $this->scanner->consume("{")) {
                
                if (
                    ($exp = $this->parseAssignmentExpression()) &&
                    $this->scanner->consume("}")
                ) {
                    
                    $value = $this->createJSXNode(
                        "JSXExpressionContainer",
                        $startExp
                    );
                    $value->setExpression($exp);
                    $value = $this->completeNode($value);
                    
                } else {
                    $this->error();
                }
                
            } elseif (
                !($value = $this->parseJSXFragment()) &&
                !($value = $this->parseJSXElement())
            ) {
                $this->error();
            }
        }
        
        $node = $this->createJSXNode("JSXAttribute", $name);
        $node->setName($name);
        if ($value) {
            $node->setValue($value);
        }
        return $this->completeNode($node);
    }
    
    /**
     * Checks that 2 tag names are equal
     * 
     * @param \Peast\Syntax\Node\Node   $n1 First name
     * @param \Peast\Syntax\Node\Node   $n2 Second name
     * 
     * @return bool
     */
    protected function isSameJSXElementName($n1, $n2)
    {
        $type = $n1->getType();
        if ($type !== $n2->getType()) {
            return false;
        } elseif ($type === "JSXNamespacedName") {
            return $this->isSameJSXElementName(
                $n1->getNamespace(), $n2->getNamespace()
            ) && $this->isSameJSXElementName(
                $n1->getName(), $n2->getName()
            );
        } elseif ($type === "JSXMemberExpression") {
            return $this->isSameJSXElementName(
                $n1->getObject(), $n2->getObject()
            ) && $this->isSameJSXElementName(
                $n1->getProperty(), $n2->getProperty()
            );
        }
        return $type === "JSXIdentifier" && $n1->getName() === $n2->getName();
    }
}
