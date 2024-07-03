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

use Peast\Syntax\Node\Comment;

/**
 * Nodes renderer class
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class Renderer
{
    /**
     * Formatter to use for the rendering
     * 
     * @var Formatter\Base
     */
    protected $formatter;
    
    /**
     * Rendering options taken from the formatter
     * 
     * @var object
     */
    protected $renderOpts;
    
    /**
     * Node types that does not require semicolon insertion
     * 
     * @var array
     */
    protected $noSemicolon = array(
        "ClassDeclaration",
        "ExportDefaultDeclaration",
        "ForInStatement",
        "ForOfStatement",
        "ForStatement",
        "FunctionDeclaration",
        "IfStatement",
        "LabeledStatement",
        "StaticBlock",
        "SwitchStatement",
        "TryStatement",
        "WhileStatement",
        "WithStatement",
        "MethodDefinition"
    );
    
    /**
     * Sets the formatter to use for the rendering
     * 
     * @param Formatter\Base    $formatter  Formatter
     * 
     * @return $this
     */
    public function setFormatter(Formatter\Base $formatter)
    {
        $this->formatter = $formatter;
        
        $this->renderOpts = (object) array(
            "nl" => $this->formatter->getNewLine(),
            "ind" => $this->formatter->getIndentation(),
            "nlbc" => $this->formatter->getNewLineBeforeCurlyBracket(),
            "sao" =>  $this->formatter->getSpacesAroundOperator() ? " " : "",
            "sirb" => $this->formatter->getSpacesInsideRoundBrackets() ? " " : "",
            "awb" => $this->formatter->getAlwaysWrapBlocks(),
            "com" => $this->formatter->getRenderComments(),
            "rci" => $this->formatter->getRecalcCommentsIndent()
        );
        
        return $this;
    }
    
    /**
     * Returns the formatter to use for the rendering
     * 
     * @return Formatter\Base
     */
    public function getFormatter()
    {
        return $this->formatter;
    }
    
    /**
     * Renders the given node
     * 
     * @param Syntax\Node\Node  $node   Node to render
     * 
     * @return string
     * 
     * @throws \Exception
     */
    public function render(Syntax\Node\Node $node)
    {
        //Throw exception if no formatter has been specified
        if (!$this->formatter) {
            throw new \Exception("Formatter not set");
        }
        
        //Reset indentation level
        $this->renderOpts->indLevel = 0;
        
        //Start rendering
        return $this->renderNode($node);
    }
    
    /**
     * Renders a node
     * 
     * @param Syntax\Node\Node  $node           Node to render
     * @param bool              $addSemicolon   True to add semicolon after node
     *                                          rendered code
     * 
     * @return string
     */
    protected function renderNode(Syntax\Node\Node $node, $addSemicolon = false)
    {
        $code = "";
        if ($this->renderOpts->com) {
            $code .= $this->renderComments($node);
        }
        $type = $node->getType();
        switch ($type) {
            case "ArrayExpression":
            case "ArrayPattern":
                $code .= "[" .
                         $this->joinNodes(
                            $node->getElements(),
                            "," . $this->renderOpts->sao
                         ) .
                         "]";
            break;
            case "ArrowFunctionExpression":
                if ($node->getAsync()) {
                    $code .= "async" . $this->renderOpts->sao;
                }
                $code .= "(" .
                         $this->renderOpts->sirb .
                         $this->joinNodes(
                            $node->getParams(),
                            "," . $this->renderOpts->sao
                         ) .
                         $this->renderOpts->sirb .
                         ")" .
                         $this->renderOpts->sao .
                         "=>";
                $body = $node->getBody();
                if ($body->getType() !== "BlockStatement") {
                    $code .= $this->renderOpts->sao . $this->renderNode($body);
                } else {
                    $code .= $this->renderStatementBlock($node, $body, true);
                }
            break;
            case "AwaitExpression":
                $code .= "await " . $this->renderNode($node->getArgument());
            break;
            case "AssignmentExpression":
            case "AssignmentPattern":
            case "BinaryExpression":
            case "LogicalExpression":
                $operator = $type === "AssignmentPattern" ?
                            "=" :
                            $node->getOperator();
                $code .= $this->renderNode($node->getLeft());
                $codeRight = $this->renderNode($node->getRight());
                if (preg_match("#^[a-z]+$#i", $operator)) {
                    $code .= " " .
                             $operator .
                             " ";
                } else {
                    //If there's no space around the operator, additional checks must
                    //be performed to prevent errors when rendering unary and update
                    //expressions inside binary expressions
                    $checkSpace = !$this->renderOpts->sao && $type === "BinaryExpression";
                    
                    //The space is mandatory if the left part ends with the same
                    //character used as operator
                    if ($checkSpace && $code && substr($code, -1) === $operator) {
                        $code .= " ";
                    }
                    
                    $code .= $this->renderOpts->sao .
                             $operator .
                             $this->renderOpts->sao;
                    
                    //The space is mandatory if the right part begins with the same
                    //character used as operator
                    if ($checkSpace && $codeRight && $codeRight[0] === $operator) {
                        $code .= " ";
                    }
                }
                $code .= $codeRight;
            break;
            case "BlockStatement":
            case "ClassBody":
            case "Program":
                $code .= $this->renderStatementBlock(
                    $node, $node->getBody(), false, false, true, false
                );
            break;
            case "BreakStatement":
            case "ContinueStatement":
                $code .= $type === "BreakStatement" ? "break" : "continue";
                if ($label = $node->getLabel()) {
                    $code .= " " . $this->renderNode($label);
                }
            break;
            case "CallExpression":
            case "NewExpression":
                if ($type === "NewExpression") {
                    $code .= "new ";
                    $optional = false;
                } else {
                    $optional = $node->getOptional();
                }
                $code .= $this->renderNode($node->getCallee()) .
                         ($optional ? "?." : "") .
                         "(" .
                         $this->renderOpts->sirb .
                         $this->joinNodes(
                            $node->getArguments(),
                            "," . $this->renderOpts->sao
                         ) .
                         $this->renderOpts->sirb .
                         ")";
            break;
            case "CatchClause":
                $code .= "catch";
                if ($params = $node->getParam()) {
                    $code .= $this->renderOpts->sao .
                             "(" .
                             $this->renderOpts->sirb .
                             $this->renderNode($params) .
                             $this->renderOpts->sirb .
                             ")";
                }
                $code .= $this->renderStatementBlock($node, $node->getBody(), true);
            break;
            case "ChainExpression":
            case "ExpressionStatement":
                $code .= $this->renderNode($node->getExpression());
            break;
            case "ClassExpression":
            case "ClassDeclaration":
                $code .= "class";
                if ($id = $node->getId()) {
                    $code .= " " . $this->renderNode($id);
                }
                if ($superClass = $node->getSuperClass()) {
                    $code .= " extends " . $this->renderNode($superClass);
                }
                $code .= $this->renderStatementBlock(
                    $node, $node->getBody(), true
                );
            break;
            case "ConditionalExpression":
                $code .= $this->renderNode($node->getTest()) .
                         $this->renderOpts->sao .
                         "?" .
                         $this->renderOpts->sao .
                         $this->renderNode($node->getConsequent()) .
                         $this->renderOpts->sao .
                         ":" .
                         $this->renderOpts->sao .
                         $this->renderNode($node->getAlternate());
            break;
            case "DebuggerStatement":
                $code .= "debugger";
            break;
            case "DoWhileStatement":
                $code .= "do" .
                         $this->renderStatementBlock(
                            $node, $node->getBody(), null, true
                         ) .
                         $this->renderOpts->sao .
                         "while" .
                         $this->renderOpts->sao .
                         "(" .
                         $this->renderOpts->sirb .
                         $this->renderNode($node->getTest()) .
                         $this->renderOpts->sirb .
                         ")";
            break;
            case "JSXEmptyExpression":
            case "EmptyStatement":
            break;
            case "ExportAllDeclaration":
                $code .= "export *";
                $exported = $node->getExported();
                if ($exported) {
                    $code .= " as " . $this->renderNode($exported);
                }
                $code .= " from " . $this->renderNode($node->getSource());
            break;
            case "ExportDefaultDeclaration":
                $declaration = $node->getDeclaration();
                $code .= "export default " .
                         $this->renderNode($declaration);
                if ($this->requiresSemicolon($declaration)) {
                    $code .= ";";
                }
            break;
            case "ExportNamedDeclaration":
                $code .= "export";
                if ($dec = $node->getDeclaration()) {
                    $code .= " " .
                             $this->renderNode($dec);
                } else {
                    $code .= $this->renderOpts->sao .
                             "{" .
                             $this->joinNodes(
                                 $node->getSpecifiers(),
                                 "," . $this->renderOpts->sao
                             ) .
                             "}";
                    if ($source = $node->getSource()) {
                        $code .= $this->renderOpts->sao .
                                 "from " .
                                 $this->renderNode($source);
                    }
                }
            break;
            case "ExportSpecifier":
                $local = $this->renderNode($node->getLocal());
                $ref = $this->renderNode($node->getExported());
                $code .= $local === $ref ?
                         $local :
                         $local . " as " . $ref;
            break;
            case "ForInStatement":
            case "ForOfStatement":
                //Force single line mode for substatements
                $this->renderOpts->forceSingleLine = true;
                $code .= "for" .
                         ($type === "ForOfStatement" && $node->getAwait() ? " await" : "") .
                         $this->renderOpts->sao .
                         "(" .
                         $this->renderOpts->sirb .
                         $this->renderNode($node->getLeft()) .
                         " " . ($type === "ForInStatement" ? "in" : "of") . " " .
                         $this->renderNode($node->getRight()) .
                         $this->renderOpts->sirb .
                         ")" .
                         $this->renderStatementBlock($node, $node->getBody());
                unset($this->renderOpts->forceSingleLine);
            break;
            case "ForStatement":
                //Force single line mode for substatements
                $this->renderOpts->forceSingleLine = true;
                $code .= "for" .
                         $this->renderOpts->sao .
                         "(" .
                         $this->renderOpts->sirb;
                if ($init = $node->getInit()) {
                    $code .= $this->renderNode($init);
                }
                $code .= ";" . $this->renderOpts->sao;
                if ($test = $node->getTest()) {
                    $code .= $this->renderNode($test);
                }
                $code .= ";" . $this->renderOpts->sao;
                if ($update = $node->getUpdate()) {
                    $code .= $this->renderNode($update);
                }
                $code .= $this->renderOpts->sirb .
                         ")" .
                         $this->renderStatementBlock($node, $node->getBody());
                unset($this->renderOpts->forceSingleLine);
            break;
            case "FunctionDeclaration":
            case "FunctionExpression":
                $id = $node->getId();
                if ($node->getAsync()) {
                    $code .= "async ";
                }
                $code .= "function";
                if ($node->getGenerator()) {
                    $code .= $this->renderOpts->sao .
                             "*";
                } elseif ($id) {
                    $code .= " ";
                }
                if ($id) {
                    if ($node->getGenerator()) {
                        $code .= $this->renderOpts->sao;
                    }
                    $code .= $this->renderNode($id);
                }
                $code .= $this->renderOpts->sao .
                         "(" .
                         $this->renderOpts->sirb .
                         $this->joinNodes(
                            $node->getParams(),
                            "," . $this->renderOpts->sao
                         ) .
                         $this->renderOpts->sirb .
                         ")" .
                         $this->renderStatementBlock($node, $node->getBody(), true);
            break;
            case "ImportExpression":
                $code .= "import(" .
                         $this->renderOpts->sirb .
                         $this->renderNode($node->getSource()) .
                         $this->renderOpts->sirb .
                         ")";
            break;
            case "JSXIdentifier":
            case "Identifier":
                $code .= $node->getRawName();
            break;
            case "IfStatement":
                $code .= "if" .
                         $this->renderOpts->sao .
                         "(" .
                         $this->renderOpts->sirb .
                         $this->renderNode($node->getTest()) .
                         $this->renderOpts->sirb .
                         ")";
                
                $code .= $this->renderStatementBlock(
                    $node, $node->getConsequent()
                );
                if ($alternate = $node->getAlternate()) {
                    $code .= $this->renderOpts->sao .
                             "else" .
                             $this->renderStatementBlock(
                                 $node,
                                 $alternate,
                                 null,
                                 true
                             );
                }
            break;
            case "ImportDeclaration":
                $code .= "import ";
                $specifiers = $node->getSpecifiers();
                if (count($specifiers)) {
                    $sep = "," . $this->renderOpts->sao;
                    $groups = $parts = array();
                    foreach ($specifiers as $spec) {
                        $specType = $spec->getType();
                        if (!isset($groups[$specType])) {
                            $groups[$specType] = array();
                        }
                        $groups[$specType][] = $spec;
                    }
                    if (isset($groups["ImportDefaultSpecifier"])) {
                        foreach ($groups["ImportDefaultSpecifier"] as $s) {
                            $parts[] = $this->renderNode($s);
                        }
                    }
                    if (isset($groups["ImportNamespaceSpecifier"])) {
                        foreach ($groups["ImportNamespaceSpecifier"] as $s) {
                            $parts[] = $this->renderNode($s);
                        }
                    }
                    if (isset($groups["ImportSpecifier"])) {
                        $impSpec = array();
                        foreach ($groups["ImportSpecifier"] as $s) {
                            $impSpec[] = $this->renderNode($s);
                        }
                        $parts[] = "{" . implode($sep, $impSpec) . "}";
                    }
                    $code .= implode($sep, $parts) . " from ";
                }
                $code .= $this->renderNode($node->getSource());
            break;
            case "ImportDefaultSpecifier":
                $code .= $this->renderNode($node->getLocal());
            break;
            case "ImportNamespaceSpecifier":
                $code .= "* as " . $this->renderNode($node->getLocal());
            break;
            case "ImportSpecifier":
                $local = $this->renderNode($node->getLocal());
                $ref = $this->renderNode($node->getImported());
                $code .= $local === $ref ?
                         $local :
                         $ref . " as " . $local;
            break;
            case "JSXAttribute":
                $code .= $this->renderNode($node->getName());
                if ($value = $node->getValue()) {
                    $code .= "=" . $this->renderNode($value);
                }
            break;
            case "JSXClosingElement":
                $code .= "</" . $this->renderNode($node->getName()) . ">";
            break;
            case "JSXClosingFragment":
                $code .= "</>";
            break;
            case "JSXElement":
                $code .= $this->renderNode($node->getOpeningElement()) .
                         $this->joinNodes($node->getChildren(), "");
                if ($closing = $node->getClosingElement()) {
                    $code .= $this->renderNode($closing);
                }
            break;
            case "JSXExpressionContainer":
                $code .= "{" . $this->renderNode($node->getExpression()) . "}";
            break;
            case "JSXFragment":
                $code .= $this->renderNode($node->getOpeningFragment()) .
                         $this->joinNodes($node->getChildren(), "") .
                         $this->renderNode($node->getClosingFragment());
            break;
            case "JSXNamespacedName":
                $code .= $this->renderNode($node->getNamespace()) . ":" .
                         $this->renderNode($node->getName());
            break;
            case "JSXOpeningElement":
                $code .= "<" . $this->renderNode($node->getName());
                $attributes = $node->getAttributes();
                if (count($attributes)) {
                    $code .= " " . $this->joinNodes($attributes, " ");
                }
                if ($node->getSelfClosing()) {
                    $code .= "/";
                }
                $code .= ">";
            break;
            case "JSXOpeningFragment":
                $code .= "<>";
            break;
            case "JSXSpreadAttribute":
                $code .= "{..." . $this->renderNode($node->getArgument()) . "}";
            break;
            case "JSXSpreadChild":
                $code .= "{..." . $this->renderNode($node->getExpression()) . "}";
            break;
            case "LabeledStatement":
                $body = $node->getBody();
                $code .= $this->renderNode($node->getLabel()) .
                         ":";
                if ($body->getType() === "BlockStatement") {
                    $code .= $this->renderStatementBlock($node, $body, true);
                } else {
                    $code .= $this->renderOpts->nl .
                             $this->getIndentation() .
                             $this->renderNode($body);
                }
                if ($this->requiresSemicolon($body)) {
                    $code .= ";";
                }
            break;
            case "JSXText":
            case "Literal":
            case "RegExpLiteral":
                $code .= $node->getRaw();
            break;
            case "JSXMemberExpression":
            case "MemberExpression":
                $property = $node->getProperty();
                $compiledProperty = $this->renderNode($property);
                $code .= $this->renderNode($node->getObject());
                $optional = false;
                if ($type === "MemberExpression") {
                    $optional = $node->getOptional();
                }
                $propertyType = $property->getType();
                if ($type === "MemberExpression" &&
                    ($node->getComputed() ||
                    ($propertyType !== "Identifier" && $propertyType !== "PrivateIdentifier"))) {
                    $code .= ($optional ? "?." : "") . "[" . $compiledProperty . "]";
                } else {
                    $code .= ($optional ? "?." : ".") . $compiledProperty;
                }
            break;
            case "MetaProperty":
                $code .= $node->getMeta() . "." . $node->getProperty();
            break;
            case "MethodDefinition":
                if ($node->getStatic()) {
                    $code .= "static ";
                }
                $value = $node->getValue();
                $key = $node->getKey();
                $kind = $node->getKind();
                if ($kind === $node::KIND_GET || $kind === $node::KIND_SET) {
                    $code .= $kind . " ";
                } else {
                    if ($value->getAsync()) {
                        $code .= "async ";
                    }
                    if ($value->getGenerator()) {
                        $code .= "*" .
                                 $this->renderOpts->sao;
                    }
                }
                if ($node->getComputed()) {
                    $code .= "[" .
                             $this->renderNode($key) .
                             "]";
                } else {
                    $code .= $this->renderNode($key);
                }
                $code .= $this->renderOpts->sao .
                         preg_replace("/^[^(]+/", "", $this->renderNode($value));
            break;
            case "ObjectExpression":
                $currentIndentation = $this->getIndentation();
                $this->renderOpts->indLevel++;
                $indentation = $this->getIndentation();
                //Handle single line mode
                if (isset($this->renderOpts->forceSingleLine)) {
                    $start = $end = "";
                    $separator = "," . $this->renderOpts->sao;
                } else {
                    $end = $this->renderOpts->nl . $currentIndentation;
                    $start = $this->renderOpts->nl . $indentation;
                    $separator = "," . $this->renderOpts->nl . $indentation;
                }
                $code .= "{";
                $properties = $node->getProperties();
                if (count($properties)) {
                    $code .= $start .
                             $this->joinNodes(
                                $properties,
                                $separator
                             ) .
                             $end;
                }
                $code .= "}";
                $this->renderOpts->indLevel--;
            break;
            case "ObjectPattern":
                $code .= "{" .
                         $this->joinNodes(
                            $node->getProperties(),
                            "," . $this->renderOpts->sao
                         ) .
                         "}";
            break;
            case "ParenthesizedExpression":
                $code .= "(" .
                         $this->renderOpts->sirb .
                         $this->renderNode($node->getExpression()) .
                         $this->renderOpts->sirb .
                         ")";
            break;
            case "PrivateIdentifier":
                $code .= "#" . $node->getName();
            break;
            case "Property":
                $value = $node->getValue();
                $key = $node->getKey();
                $compiledKey = $this->renderNode($key);
                $compiledValue = $this->renderNode($value);
                $keyType = $key->getType();
                $valueType = $value->getType();
                if ($valueType === "AssignmentPattern" &&
                    $compiledKey === $this->renderNode($value->getLeft())) {
                    $code .= $compiledValue;
                } else {
                    $kind = $node->getKind();
                    $getterSetter = $kind === $node::KIND_GET ||
                                    $kind === $node::KIND_SET;
                    if ($getterSetter) {
                        $code .= $kind . " ";
                    } elseif ($value->getType() === "FunctionExpression" &&
                              $value->getGenerator()) {
                        $code .= "*" .
                                 $this->renderOpts->sao;
                    }
                    if ($node->getMethod() && $value->getAsync()) {
                        $code .= "async ";
                    }
                    if ($node->getComputed()) {
                        $code .= "[" . $compiledKey . "]";
                    } else {
                        $code .= $compiledKey;
                    }
                    if ($node->getMethod() || $getterSetter) {
                        $code .= $this->renderOpts->sao .
                                 preg_replace("/^[^(]+/", "", $compiledValue);
                    } elseif ($keyType !== "Identifier" ||
                              $valueType !== "Identifier" ||
                              $compiledKey !== $compiledValue
                    ) {
                        $code .= ($node->getShorthand() ? "=" : ":") .
                                 $this->renderOpts->sao .
                                 $compiledValue;
                    }
                }
            break;
            case "PropertyDefinition":
                if ($node->getStatic()) {
                    $code .= "static ";
                }
                $compiledKey = $this->renderNode($node->getKey());
                if ($node->getComputed()) {
                    $code .= "[" . $compiledKey . "]";
                } else {
                    $code .= $compiledKey;
                }
                if ($value = $node->getValue()) {
                    $code .= $this->renderOpts->sao .
                             "=" .
                             $this->renderOpts->sao .
                             $this->renderNode($value);
                }
            break;
            case "RestElement":
            case "SpreadElement":
                $code .= "..." . $this->renderNode($node->getArgument());
            break;
            case "ReturnStatement":
                $code .= "return";
                if ($argument = $node->getArgument()) {
                    $code .= " " . $this->renderNode($argument);
                }
            break;
            case "SequenceExpression":
                $code .= $this->joinNodes(
                            $node->getExpressions(),
                            "," . $this->renderOpts->sao
                         );
            break;
            case "StaticBlock":
                $code .= "static";
                $code .= $this->renderStatementBlock($node, $node->getBody(), true);
            break;
            case "Super":
                $code .= "super";
            break;
            case "SwitchCase":
                if ($test = $node->getTest()) {
                    $code .= "case " . $this->renderNode($test);
                } else {
                    $code .= "default";
                }
                $code .= ":";
                if (count($node->getConsequent())) {
                    $code .= $this->renderStatementBlock(
                                 $node,
                                 $node->getConsequent()
                             );
                }
            break;
            case "SwitchStatement":
                $code .= "switch" .
                         $this->renderOpts->sao .
                         "(" .
                         $this->renderOpts->sirb .
                         $this->renderNode($node->getDiscriminant()) .
                         $this->renderOpts->sirb .
                         ")" .
                         $this->renderStatementBlock(
                             $node, $node->getCases(), true, false, false
                         );
            break;
            case "TaggedTemplateExpression":
                $code .= $this->renderNode($node->getTag()) .
                         $this->renderNode($node->getQuasi());
            break;
            case "TemplateElement":
                $code .= $node->getRawValue();
            break;
            case "TemplateLiteral":
                $code .= "`";
                foreach ($node->getParts() as $part) {
                    if ($part->getType() === "TemplateElement") {
                        $code .= $this->renderNode($part);
                    } else {
                        $code .= "$" . "{" . $this->renderNode($part) . "}";
                    }
                }
                $code .= "`";
            break;
            case "ThisExpression":
                $code .= "this";
            break;
            case "ThrowStatement":
                $code .= "throw " . $this->renderNode($node->getArgument());
            break;
            case "TryStatement":
                $code .= "try" .
                         $this->renderStatementBlock($node, $node->getBlock(), true);
                if ($handler = $node->getHandler()) {
                    $code .= $this->renderOpts->sao .
                             $this->renderNode($handler);
                }
                if ($finalizer = $node->getFinalizer()) {
                    $code .= $this->renderOpts->sao .
                             "finally" .
                             $this->renderStatementBlock($node, $finalizer, true);
                }
            break;
            case "UnaryExpression":
            case "UpdateExpression":
                $prefix = $node->getPrefix();
                if ($prefix) {
                    $code .= $node->getOperator();
                    if (preg_match("#^[a-z]+$#i", $node->getOperator())) {
                        $code .= " ";
                    }
                }
                $code .= $this->renderNode($node->getArgument());
                if (!$prefix) {
                    $code .= $node->getOperator();
                }
            break;
            case "VariableDeclaration":
                $this->renderOpts->indLevel++;
                $indentation = $this->getIndentation();
                //Handle single line mode
                if (isset($this->renderOpts->forceSingleLine)) {
                    $separator = "," . $this->renderOpts->sao;
                } else {
                    $separator = "," . $this->renderOpts->nl . $indentation;
                }
                $code .= $node->getKind() .
                         " " .
                         $this->joinNodes(
                            $node->getDeclarations(),
                            $separator
                         );
                $this->renderOpts->indLevel--;
            break;
            case "VariableDeclarator":
                $code .= $this->renderNode($node->getId());
                if ($init = $node->getInit()) {
                    $code .= $this->renderOpts->sao .
                             "=" .
                             $this->renderOpts->sao .
                             $this->renderNode($init);
                }
            break;
            case "WhileStatement":
                $code .= "while" .
                         $this->renderOpts->sao .
                         "(" .
                         $this->renderOpts->sirb .
                         $this->renderNode($node->getTest()) .
                         $this->renderOpts->sirb .
                         ")" .
                         $this->renderStatementBlock($node, $node->getBody());
            break;
            case "WithStatement":
                $code .= "with" .
                         $this->renderOpts->sao .
                         "(" .
                         $this->renderOpts->sirb .
                         $this->renderNode($node->getObject()) .
                         $this->renderOpts->sirb .
                         ")" .
                         $this->renderStatementBlock($node, $node->getBody());
            break;
            case "YieldExpression":
                $code .= "yield";
                if ($node->getDelegate()) {
                    $code .= " *";
                }
                if ($argument = $node->getArgument()) {
                    $code .= " " . $this->renderNode($argument);
                }
            break;
        }
        if ($addSemicolon) {
            $code .= ";";
        }
        if ($this->renderOpts->com) {
            $code .= $this->renderComments($node, false);
        }
        return $code;
    }
    
    /**
     * Renders a node as a block statement
     *
     * @param Syntax\Node\Node          $parent             Parent node
     * @param Syntax\Node\Node|array    $node               Node or array of 
     *                                                      nodes to render
     * @param bool                      $forceBrackets      Overrides brackets
     *                                                      inserting rules
     * @param bool                      $mandatorySeparator True if a starting
     *                                                      separator is
     *                                                      mandatory
     * @param bool                      $addSemicolons      Semicolons are
     *                                                      inserted automatically
     *                                                      if this parameter is
     *                                                      not false
     * @param bool                      $incIndent          If false indentation
     *                                                      level won't be
     *                                                      incremented
     * 
     * @return string
     */
    protected function renderStatementBlock(
        $parent, $node, $forceBrackets = null, $mandatorySeparator = false,
        $addSemicolons = true, $incIndent = true
    ) {
        $code = "";
        
        //If node is an array with only one element, handle it as a single node
        if (is_array($node) && count($node) === 1) {
            $node = $node[0];
        }

        //Special handling of BlockStatement and ClassBody nodes by rendering
        //their child nodes
        $origNode = null;
        if (!is_array($node) &&
            in_array($node->getType(), array("BlockStatement", "ClassBody"))) {
            $origNode = $node;
            $node = $node->getBody();
        }
        
        //If $forceBrackets is not null use its value to override curly brackets
        //insertion rules
        if ($forceBrackets !== null) {
            $hasBrackets = $forceBrackets;
        } else {
            //Insert curly brackets if needed
            $hasBrackets = $this->needsBrackets($parent, $node);
        }
        $currentIndentation = $this->getIndentation();
        
        //If the node must be wrapped in curly braces a separator defined by formatter
        //must be inserted
        if ($hasBrackets) {
            if ($this->renderOpts->nlbc) {
                $code .= $this->renderOpts->nl . $currentIndentation;
            } else {
                $code .= $this->renderOpts->sao;
            }
        }
        //Move the content of the SwitchCase content to a new line if brackets won't
        //be inserted, in this way it's not rendered on the same line of the "case"
        elseif ($parent->getType() === "SwitchCase") {
            $code .= $this->renderOpts->nl;
        }

        $emptyBody = is_array($node) && !count($node);

        if ($this->renderOpts->com && $origNode) {
            $code .= $this->renderComments($origNode, true, !$emptyBody);
        }
        
        //Insert open curly bracket if required
        if ($hasBrackets) {
            $code .= "{" . $this->renderOpts->nl;
        } elseif ($mandatorySeparator) {
            //If bracket is not inserted but a separator is still required
            //a space is added
            $code .= " ";
        }
        
        //Increase indentation level
        if ($incIndent) {
            $this->renderOpts->indLevel++;
        }
        $subIndentation = $this->getIndentation();
        
        //Render the node or the array of nodes
        if (is_array($node)) {
            if (!$emptyBody) {
                $code .= $subIndentation .
                         $this->joinNodes(
                            $node,
                            $this->renderOpts->nl . $subIndentation,
                            $addSemicolons
                         );
            }
        } else {
            $code .= $subIndentation . $this->renderNode(
                $node,
                $addSemicolons && $this->requiresSemicolon($node)
            );
        }
        
        if ($this->renderOpts->com) {
            //Strip last new line and indentations added by comments rendering
            if (!$emptyBody) {
                $code = $this->trimEmptyLine($code);
            }
            if ($origNode) {
                $code .= $this->renderComments($origNode, false, !$emptyBody);
                if (!$emptyBody) {
                    $code = $this->trimEmptyLine($code);
                }
            }
        }
        
        //Reset the indentation level
        if ($incIndent) {
            $this->renderOpts->indLevel--;
        }
        
        //Insert closing curly bracket if required
        if ($hasBrackets) {
            //Add a new line if something was rendered
            if (!$emptyBody) {
                $code .= $this->renderOpts->nl;
            }
            $code .= $currentIndentation . "}";
        }
        
        return $code;
    }
    
    /**
     * Joins an array of nodes with the given separator
     * 
     * @param array     $nodes          Nodes
     * @param string    $separator      Separator
     * @param bool      $addSemicolons  True to add semicolons after each node
     * 
     * @return string
     */
    protected function joinNodes($nodes, $separator, $addSemicolons=false)
    {
        $parts = array();
        foreach ($nodes as $node) {
            if (!$node) {
                $code = "";
            } else {
                $code = $this->renderNode(
                    $node,
                    $addSemicolons && $this->requiresSemicolon($node)
                );
            }
            $parts[] = $code;
        }
        return implode($separator, $parts);
    }
    
    /**
     * Check if the node or the array of nodes need brackets to be rendered
     *
     * @param Syntax\Node\Node          $parent             Parent node
     * @param Syntax\Node\Node|array    $node               Node or array of
     *                                                      nodes to render
     * 
     * @return bool
     */
    protected function needsBrackets($parent, $node)
    {
        $parentType = $parent->getType();
        //The SwitchCase content needs brackets if it contains let or const declarations
        $inSwitchCase = $parentType === "SwitchCase";
        //Except for SwitchCase, brackets are needed if the formatter requires them or whenever
        //there are 0 or multiple nodes to render
        if (!$inSwitchCase && ($this->renderOpts->awb || (is_array($node) && count($node) !== 1))) {
            return true;
        }
        if (!is_array($node)) {
            $node = array($node);
        }
        $addBrackets = false;
        $optBracketNodes = array(
            "DoWhileStatement", "ForInStatement", "ForOfStatement",
            "ForStatement", "WhileStatement", "WithStatement"
        );
        //An IfStatement requires brackets if it contains let or const declarations and if it has the "else" part
        //and contains another IfStatement with "else"
        $inIfWithElse = $parentType === "IfStatement" && $parent->getAlternate();
        $checkFn = function ($n) use ($inIfWithElse, $optBracketNodes, &$addBrackets) {
            $type = $n->getType();
            if ($inIfWithElse && $type === "IfStatement") {
                if (!$n->getAlternate()) {
                    $addBrackets = true;
                }
                return Traverser::DONT_TRAVERSE_CHILD_NODES;
            } elseif ($type === "BlockStatement") {
                if (count($n->getBody()) !== 1) {
                    return Traverser::DONT_TRAVERSE_CHILD_NODES;
                }
            } elseif ($inIfWithElse && $type === "LabeledStatement") {
                if ($n->getBody()->getType() === "BlockStatement") {
                    $addBrackets = true;
                }
                return Traverser::DONT_TRAVERSE_CHILD_NODES;
            } elseif ($type === "VariableDeclaration") {
                if (in_array($n->getKind(), array($n::KIND_LET, $n::KIND_CONST))) {
                    $addBrackets = true;
                }
                return Traverser::DONT_TRAVERSE_CHILD_NODES;
            } elseif (!in_array($type, $optBracketNodes)) {
                return Traverser::DONT_TRAVERSE_CHILD_NODES;
            }
        };
        //Traverse every node but stop whenever the $addBrackets variable becomes true
        foreach ($node as $n) {
            $n->traverse($checkFn);
            if ($addBrackets) {
                break;
            }
        }
        return $addBrackets;
    }

    /**
     * Render node's comments
     * 
     * @param Syntax\Node\Node  $node             Node
     * @param bool              $leading          False to render trailing comments
     * @param bool|null         $blockContent     This paramater can have 3 values:
     *                                            - null: the node is not a block
     *                                            - false: the node is an empty block
     *                                            - true: the node is a block with content
     * 
     * @return string
     */
    protected function renderComments($node, $leading = true, $blockContent = null)
    {
        $code = "";
        $fn = $leading ? "getLeadingComments" : "getTrailingComments";
        $comments = $node ? $node->$fn() : array();
        $numComments = count($comments);
        if ($numComments) {
            $lastFormatted = $blockContent === false ? true : $leading;
            $refNode = $node;
            $refKey = $leading ? "end" : "start";
            $refNodeKey = $leading ? "start" : "end";
            $indent = $this->getIndentation();
            foreach ($comments as $k => $comment) {                
                $lastComment = $k === $numComments - 1;
                $isMultilineComment = $comment->getKind() === Comment::KIND_MULTILINE;
                // Check if the comment must be formatted with new line and indentations
                $format = true;
                if (
                    $refNode && $isMultilineComment &&
                    $comment->location && $refNode->location &&
                    $comment->location->$refKey->getLine() === $refNode->location->$refNodeKey->getLine()
                ) {
                    $format = false;
                }
                //If the last comment wasn't formatted but this one must be formatted, add the new
                //line and the indentation
                if ($format && !$lastFormatted) {
                    $code .= $this->renderOpts->nl . $indent;
                }
                //Leading comments on empty blocks must render the initial indentation if format
                //is enabled
                if ($format && $blockContent === false && !$k) {
                    $code .= $indent;
                }

                $commentRaw = $comment->getRawText();

                //Reindent multiline comments if necessary
                if ($isMultilineComment && $indent && $this->renderOpts->rci) {
                    $commentRaw = preg_replace("/^\s+\*/m", $indent . " *", $commentRaw);
                }

                $code .= $commentRaw;

                //If format is enabled, add the new line character and the indentation if the node
                //is not an empty block or the it's not the last comment
                if ($format && ($blockContent !== true || !$lastComment || !$isMultilineComment)) {
                    //For non multiline comments the new line is mandatory, even if the formatter
                    //disables it
                    $code .= !$isMultilineComment && !$this->renderOpts->nl ? "\n" : $this->renderOpts->nl;
                    //Last comment on blocks must not render indentation
                    if ($blockContent === null || !$lastComment) {
                        $code .= $indent;
                    }
                }
                $refNode = $comment;
                $lastFormatted = $format;
            }
        }
        return $code;
    }

    /**
     * Removes an empty line at the end of the given code, if present
     * 
     * @param string  $code   Code
     * 
     * @return string
     */
    protected function trimEmptyLine($code)
    {
        if ($this->renderOpts->nl) {
            $nl = preg_quote($this->renderOpts->nl, "/");
            $indent = preg_quote($this->getIndentation(), "/");
            $code = preg_replace("/$nl(?:$indent)?$/", "", $code);
        }
        return $code;
    }
    
    /**
     * Check if the given node requires semicolons insertion
     * 
     * @param Syntax\Node\Node  $node   Node
     * 
     * @return bool
     */
    protected function requiresSemicolon($node)
    {
        return !in_array($node->getType(), $this->noSemicolon);
    }
    
    /**
     * Returns the current indentation string
     * 
     * @return string
     */
    protected function getIndentation()
    {
        return str_repeat(
            $this->renderOpts->ind,
            $this->renderOpts->indLevel
        );
    }
}