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

/**
 * Selector parser class
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class Parser
{
    /**
     * Selector string
     *
     * @var string
     */
    protected $selector;

    /**
     * Current parser index
     *
     * @var int
     */
    protected $index = 0;

    /**
     * Selector length
     *
     * @var int
     */
    protected $length;

    /**
     * Whitespaces
     *
     * @var array
     */
    protected $whitespaces = array(" ", "\t", "\n", "\r", "\f");

    /**
     * Combinators
     *
     * @var array
     */
    protected $combinators = array(">", "+", "~");

    /**
     * Attribute selector operator characters
     *
     * @var array
     */
    protected $attrOperatorChars = array("=", "<", ">", "^", "$", "*");

    /**
     * Attribute selector operators
     *
     * @var array
     */
    protected $attrOperators = array("=", "<", ">", "<=", ">=", "^=", "$=", "*=");

    /**
     * Valid pseudo selectors. The value indicates the argument type:
     * - 0: no arguments
     * - 1: index formula (An+B syntax)
     * - 2: selector
     * @var array
     */
    protected $validPseudo = array(
        "pattern" => 0, "statement" => 0, "expression" => 0, "declaration" => 0,
        "first-child" => 0, "last-child" => 0,
        "nth-child" => 1, "nth-last-child" => 1,
        "has" => 2, "is" => 2, "not" => 2
    );

    /**
     * Class constructor
     *
     * @param string $selector  Selector string
     * @param array  $options   Options array. See Query class
 *                              documentation for available options
     */
    public function __construct($selector, $options = array())
    {
        $encoding = isset($options["encoding"]) ? $options["encoding"] : null;
        if ($encoding && !preg_match("/UTF-?8/i", $encoding)) {
            $selector = mb_convert_encoding($selector, "UTF-8", $encoding);
        }
        $this->selector = $selector;
        $this->length = strlen($selector);
    }

    /**
     * Starts the parsing and returns the parsed selector
     *
     * @param bool $filter True if the selector must be used for a filter
     *
     * @return Node\Selector
     *
     * @throws Exception
     */
    public function parse($filter = false)
    {
        $selector = $this->parseSelector($filter);
        //Throw an exception if the end has not been reached
        if (($char = $this->getChar()) !== null) {
            throw new Exception("Invalid syntax '$char'");
        }
        return $selector;
    }

    /**
     * Parses a selector
     *
     * @param bool $filter True if the selector must be used for a filter
     *
     * @return Node\Selector
     *
     * @throws Exception
     */
    public function parseSelector($filter = false)
    {
        $selector = new Node\Selector;
        do {
            $first = true;
            $group = new Node\Group;
            while (true) {
                $combinator = $this->consumeCombinator();
                if (!$first && !$combinator) {
                    break;
                }
                $parts = $this->parseSelectorParts();
                if (!count($parts)) {
                    throw new Exception("Missing selector after combinator");
                }
                $first = false;
                $selCombinator = new Node\Combinator;
                $selCombinator->setOperator(
                    $combinator ?: ($filter ? null : " ")
                );
                foreach ($parts as $part) {
                    $selCombinator->addPart($part);
                }
                $group->addCombinator($selCombinator);
            }
            $selector->addGroup($group);
            $this->consumeWhitespaces();
        } while ($this->consume(","));
        return $selector;
    }

    /**
     * Parses a set of selector pats
     *
     * @return array
     *
     * @throws Exception
     */
    protected function parseSelectorParts()
    {
        $parts = array();
        while (true) {
            if (
                ($part = $this->parseSelectorPartType()) ||
                ($part = $this->parseSelectorPartAttribute()) ||
                ($part = $this->parseSelectorPartPseudo())
            ) {
                $parts[] = $part;
            } else {
                break;
            }
        }
        return $parts;
    }

    /**
     * Parses a type selector part
     *
     * @return Node\Part\Type|null
     */
    protected function parseSelectorPartType()
    {
        $type = $this->consumeWord();
        if ($type) {
            $part = new Node\Part\Type;
            $part->setType($type);
            return $part;
        }
        return null;
    }

    /**
     * Parses an attribute selector part
     *
     * @return Node\Part\Attribute|null
     *
     * @throws Exception
     */
    protected function parseSelectorPartAttribute()
    {
        if (!$this->consume("[")) {
            return null;
        }
        $this->consumeWhitespaces();
        $part = new Node\Part\Attribute;
        if (!($name = $this->consumeWord())) {
            throw new Exception("Missing attribute name");
        }
        $part->addName($name);
        while ($this->consume(".")) {
            if (!($name = $this->consumeWord())) {
                throw new Exception("Missing attribute name after dot");
            }
            $part->addName($name);
        }
        $this->consumeWhitespaces();
        $operator = $this->consumeAny($this->attrOperatorChars);
        if ($operator) {
            if (!in_array($operator, $this->attrOperators)) {
                throw new Exception("Invalid attribute operator '$operator'");
            }
            $part->setOperator($operator);
            $this->consumeWhitespaces();
            if (!($value = $this->parseLiteral())) {
                throw new Exception("Missing attribute value");
            }
            $part->setValue($value[0]);
            if ($value[1]) {
                if ($operator != "=") {
                    throw new Exception(
                        "Only '=' operator is valid for attribute regex match"
                    );
                }
                $part->setRegex(true);
            }
            $this->consumeWhitespaces();
            if ($this->consume("i")) {
                if (!is_string($value[0]) || $value[1]) {
                    throw new Exception(
                        "Case insensitive flag can be used only for string values"
                    );
                }
                $part->setCaseInsensitive(true);
                $this->consumeWhitespaces();
            }
        }
        if (!$this->consume("]")) {
            throw new Exception("Unterminated attribute selector");
        }
        return $part;
    }

    /**
     * Parses a pseudo selector part
     *
     * @return Node\Part\Pseudo|null
     *
     * @throws Exception
     */
    protected function parseSelectorPartPseudo()
    {
        if (!$this->consume(":")) {
            return null;
        }
        $name = $this->consumeWord("-");
        if (!isset($this->validPseudo[$name])) {
            throw new Exception("Unsupported pseudo selector '$name'");
        }
        $argsType = $this->validPseudo[$name];
        $error = false;
        if ($argsType === 1) {
            $part = new Node\Part\PseudoIndex;
            if (!$this->consume("(")) {
                $error = true;
            }
            if (!$error) {
                $this->consumeWhitespaces();
                if ($indices = $this->consumeRegex("-?\d*n(?:\+\d+)?|\d+")) {
                    $indices = explode("n", $indices);
                    if (count($indices) === 1) {
                        $part->setOffset((int) $indices[0]);
                    } else {
                        switch ($indices[0]) {
                            case "":
                                $part->setStep(1);
                            break;
                            case "-":
                                $part->setStep(-1);
                            break;
                            default:
                                $part->setStep((int) $indices[0]);
                            break;
                        }
                        if ($indices[1] !== "") {
                            $part->setOffset((int) $indices[1]);
                        }
                    }
                } elseif (
                    ($word = $this->consumeWord()) &&
                    ($word === "even" || $word === "odd")
                ) {
                    $part->setStep(2);
                    if ($word === "odd") {
                        $part->setOffset(1);
                    }
                } else {
                    $error = true;
                }
                $this->consumeWhitespaces();
                if (!$error && !$this->consume(")")) {
                    $error = true;
                }
            }
        } elseif ($argsType === 2) {
            $part = new Node\Part\PseudoSelector;
            if (
                $this->consume("(") &&
                ($selector = $this->parseSelector($name !== "has")) &&
                $this->consume(")")
            ) {
                $part->setSelector($selector);
            } else {
                $error = true;
            }
        } else {
            $part = new Node\Part\PseudoSimple;
        }
        if ($error) {
            throw new Exception(
                "Invalid argument for pseudo selector '$name'"
            );
        }
        $part->setName($name);
        return $part;
    }

    /**
     * Parses a literal value
     *
     * @return array|null
     *
     * @throws Exception
     */
    protected function parseLiteral()
    {
        if (
            ($literal = $this->parseLiteralBoolNull()) !== 0 ||
            ($literal = $this->parseLiteralString()) !== null ||
            ($literal = $this->parseLiteralNumber()) !== null
        ) {
            return array($literal, false);
        } elseif ($literal = $this->parseLiteralRegex()) {
            return array($literal, true);
        }
        return null;
    }

    /**
     * Parses a literal boolean or null value
     *
     * @return int|bool|null
     *
     * @throws Exception
     */
    protected function parseLiteralBoolNull()
    {
        $word = $this->consumeWord();
        if (!$word) {
            return 0;
        } elseif ($word === "true") {
            return true;
        } elseif ($word === "false") {
            return false;
        } elseif ($word === "null") {
            return null;
        }
        throw new Exception("Invalid attribute value '$word'");
    }

    /**
     * Parses a literal string
     *
     * @return string|null
     *
     * @throws Exception
     */
    protected function parseLiteralString()
    {
        if (!($quote = $this->consumeAny(array("'", '"'), true))) {
            return null;
        }
        if (($str = $this->consumeUntil($quote)) === null) {
            throw new Exception("Unterminated string in attribute value");
        }
        return $str;
    }

    /**
     * Parses a literal number
     *
     * @return int|float|null
     */
    protected function parseLiteralNumber()
    {
        if (
            $this->getChar() === "0" &&
            ($val = $this->consumeRegex("0[xX][a-fA-F]+|0[bB][01]+|0[oO][0-7]+"))
        ) {
            $form = strtolower($val[1]);
            $val = substr($val, 2);
            if ($form === "x") {
                return hexdec($val);
            } elseif ($form === "o") {
                return octdec($val);
            }
            return bindec($val);
        }
        $reg = "-?\d+(?:\.\d+)?(?:[eE][+-]?\d+)?|-?\.\d+(?:[eE][+-]?\d+)?";
        if (!($val = $this->consumeRegex($reg))) {
            return null;
        }
        return (float) $val;
    }

    /**
     * Parses a literal regex
     *
     * @return string|null
     *
     * @throws Exception
     */
    protected function parseLiteralRegex()
    {
        if (!($sep = $this->consume("/"))) {
            return null;
        }
        if (($reg = $this->consumeUntil($sep, false, true)) === null) {
            throw new Exception("Unterminated regex in attribute value");
        }
        $modifiers = $this->consumeWord();
        return $sep . $reg . ($modifiers ?: "");
    }

    /**
     * Consumes the given regex
     *
     * @param string $regex Regex to consume
     *
     * @return mixed|null
     */
    protected function consumeRegex($regex)
    {
        if ($this->getChar() === null) {
            return null;
        }
        if (!preg_match("#^($regex)#", substr($this->selector, $this->index), $matches)) {
            return null;
        }
        $this->index += strlen($matches[1]);
        return $matches[1];
    }

    /**
     * Consumes all the characters until the given one is reached
     *
     * @param string $stop          Stop character
     * @param bool   $removeEscapes If false escape characters won't be removed
     * @param false  $includeStop   If true stop character will be returned
     *
     * @return string|null
     */
    protected function consumeUntil($stop, $removeEscapes = true, $includeStop = false)
    {
        $buffer = "";
        $escaped = false;
        while (($char = $this->getChar()) !== null) {
            $this->index += 1;
            if (!$escaped) {
                if ($char === "\\") {
                    $escaped = true;
                    if (!$removeEscapes) {
                        $buffer .= $char;
                    }
                    continue;
                } elseif ($char === $stop) {
                    if ($includeStop) {
                        $buffer .= $char;
                    }
                    return $buffer;
                }
            }
            $buffer .= $char;
            $escaped = false;
        }
        return null;
    }

    /**
     * Consumes a word composed by characters a-z
     *
     * @param null|string $extraChar Extra character to match
     *
     * @return string
     */
    protected function consumeWord($extraChar = null)
    {
        $buffer = "";
        while ($char = $this->getChar()) {
            if (
                ($char >= "a" && $char <= "z") ||
                ($char >= "A" && $char <= "Z") ||
                ($extraChar !== null && $char === $extraChar)
            ) {
                $buffer .= $char;
                $this->index += 1;
            } else {
                break;
            }
        }
        return $buffer;
    }

    /**
     * Consumes a combinator
     *
     * @return string|null
     */
    protected function consumeCombinator()
    {
        //Initial ws can be trimmed if followed by another combinator
        $ws = $this->consumeWhitespaces();
        if ($combinator = $this->consumeAny($this->combinators, true)) {
            $this->consumeWhitespaces();
        } elseif ($ws) {
            //If there's no other combinators use the space
            $combinator = " ";
        } else {
            $combinator = null;
        }
        return $combinator;
    }

    /**
     * Consumes as much whitespaces as possible
     *
     * @return string
     */
    protected function consumeWhitespaces()
    {
        return $this->consumeAny($this->whitespaces);
    }

    /**
     * Consumes the given characters
     *
     * @param array $chars       Characters to consume
     * @param false $stopAtFirst If true only the first matching character
     *                           is consumed
     *
     * @return string
     */
    protected function consumeAny($chars, $stopAtFirst = false)
    {
        $buffer = "";
        while (($char = $this->getChar()) !== null) {
            if (in_array($char, $chars)) {
                $buffer .= $char;
                $this->index++;
                if ($stopAtFirst) {
                    break;
                }
            } else {
                break;
            }
        }
        return $buffer;
    }

    /**
     * Consumes the current character if it is equal to the
     * given one
     *
     * @param string $char Character to compare
     *
     * @return string|null
     */
    protected function consume($char)
    {
        if ($this->getChar() === $char) {
            $this->index++;
            return $char;
        }
        return null;
    }

    /**
     * Returns the current character or null if the end
     * have been reached
     *
     * @return string|null
     */
    protected function getChar()
    {
        if ($this->index < $this->length) {
            return $this->selector[$this->index];
        }
        return null;
    }
}