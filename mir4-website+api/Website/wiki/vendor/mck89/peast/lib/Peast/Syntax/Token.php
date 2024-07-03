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
 * A token emitted by the tokenizer.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class Token implements \JSONSerializable
{
    //Type constants
    /**
     * Boolean literal
     */
    const TYPE_BOOLEAN_LITERAL = "Boolean";
    
    /**
     * Identifier
     */
    const TYPE_IDENTIFIER = "Identifier";
    
    /**
     * Private identifier
     */
    const TYPE_PRIVATE_IDENTIFIER = "PrivateIdentifier";
    
    /**
     * Keyword
     */
    const TYPE_KEYWORD = "Keyword";
    
    /**
     * Null literal
     */
    const TYPE_NULL_LITERAL = "Null";
    
    /**
     * Numeric literal
     */
    const TYPE_NUMERIC_LITERAL = "Numeric";
    
    /**
     * BigInt literal
     */
    const TYPE_BIGINT_LITERAL = "BigInt";
    
    /**
     * Punctuator
     */
    const TYPE_PUNCTUATOR = "Punctuator";

    //This constant is kept only for backward compatibility since it was
    //first written with a typo
    const TYPE_PUNCTUTATOR = "Punctuator";
    
    /**
     * String literal
     */
    const TYPE_STRING_LITERAL = "String";
    
    /**
     * Regular expression
     */
    const TYPE_REGULAR_EXPRESSION = "RegularExpression";
    
    /**
     * Template
     */
    const TYPE_TEMPLATE = "Template";
    
    /**
     * Comment
     */
    const TYPE_COMMENT = "Comment";
    
    /**
     * JSX text
     */
    const TYPE_JSX_TEXT = "JSXText";
    
    /**
     * JSX identifier
     */
    const TYPE_JSX_IDENTIFIER = "JSXIdentifier";
    
    /**
     * Tokens' type that is one of the type constants
     * 
     * @var string 
     */
    public $type;
    
    /**
     * Token's value
     * 
     * @var string 
     */
    public $value;
    
    /**
     * Token's location in the source code
     * 
     * @var SourceLocation 
     */
    public $location;
    
    /**
     * Class constructor
     * 
     * @param string $type  Token's type
     * @param string $value Token's value
     */
    public function __construct($type, $value)
    {
        $this->type = $type;
        $this->value = $value;
        $this->location = new SourceLocation();
    }

    /**
     * Returns the token's type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns the token's value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }
    
    /**
     * Returns the token's location in the source code
     * 
     * @return SourceLocation
     */
    public function getLocation()
    {
        return $this->location;
    }
    
    /**
     * Sets the start position of the token in the source code
     * 
     * @param Position $position Start position
     * 
     * @return $this
     */
    public function setStartPosition(Position $position)
    {
        $this->location->start = $position;
        return $this;
    }
    
    /**
     * Sets the end position of the token in the source code
     * 
     * @param Position $position End position
     * 
     * @return $this
     */
    public function setEndPosition(Position $position)
    {
        $this->location->end = $position;
        return $this;
    }
    
    /**
     * Returns a serializable version of the node
     * 
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return array(
            "type" => $this->type,
            "value" => $this->value,
            "location" => $this->location
        );
    }
}