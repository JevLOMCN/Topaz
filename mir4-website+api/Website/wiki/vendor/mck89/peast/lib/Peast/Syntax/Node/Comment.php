<?php
/**
 * This file is part of the Peast package
 *
 * (c) Marco Marchiò <marco.mm89@gmail.com>
 *
 * For the full copyright and license information refer to the LICENSE file
 * distributed with this source code
 */
namespace Peast\Syntax\Node;

/**
 * A node that represents a comment.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class Comment extends Node
{
    //Comment kind constants
    /**
     * Inline comment
     */
    const KIND_INLINE = "inline";
    
    /**
     * Multiline comment
     */
    const KIND_MULTILINE = "multiline";
    
    /**
     * Html open comment
     */
    const KIND_HTML_OPEN = "html-open";
    
    /**
     * Html close comment
     */
    const KIND_HTML_CLOSE = "html-close";
    
    /**
     * Hashbang comment
     */
    const KIND_HASHBANG = "hashbang";
    
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "kind" => false,
        "text" => false
    );
    
    /**
     * The comment kind
     * 
     * @var string 
     */
    protected $kind;
    
    /**
     * The comment text
     * 
     * @var string 
     */
    protected $text;
    
    /**
     * Returns the comment kind
     * 
     * @return string
     */
    public function getKind()
    {
        return $this->kind;
    }
    
    /**
     * Sets the comment kind
     * 
     * @param string $kind Comment kind
     * 
     * @return $this
     */
    public function setKind($kind)
    {
        $this->kind = $kind;
        return $this;
    }
    
    /**
     * Returns the comment text
     * 
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }
    
    /**
     * Sets the comment text
     * 
     * @param string $text Comment text
     * 
     * @return $this
     */
    public function setText($text)
    {
        $this->text = $text;
        return $this;
    }
    
    /**
     * Returns the comment raw text
     * 
     * @return string
     */
    public function getRawText()
    {
        $text = $this->getText();
        $kind = $this->getKind();

        if ($kind === self::KIND_MULTILINE) {
            $sanitize = "*/";
        } else {
            $sanitize = array("\n", "\r");
        }
        $text = str_replace($sanitize, "", $text);
        
        if ($kind === self::KIND_INLINE) {
            return "//" . $text;
        } elseif ($kind === self::KIND_HASHBANG) {
            return "#!" . $text;
        } elseif ($kind === self::KIND_HTML_OPEN) {
            return "<!--" . $text;
        } elseif ($kind === self::KIND_HTML_CLOSE) {
            return "-->" . $text;
        } else {
            return "/*" . $text . "*/";
        }
    }
    
    /**
     * Sets the comment raw text
     * 
     * @param string $rawText Comment raw text
     * 
     * @return $this
     */
    public function setRawText($rawText)
    {
        $start = substr($rawText, 0, 2);
        if ($start === "//") {
            $kind = self::KIND_INLINE;
            $text = substr($rawText, 2);
        } elseif ($start === "/*" && substr($rawText, -2) === "*/") {
            $kind = self::KIND_MULTILINE;
            $text = substr($rawText, 2, -2);
        } elseif ($start === "#!") {
            $kind = self::KIND_HASHBANG;
            $text = substr($rawText, 2);
        } elseif ($start === "<!" && substr($rawText, 2, 2) === "--") {
            $kind = self::KIND_HTML_OPEN;
            $text = substr($rawText, 4);
        } elseif ($start === "--" && substr($rawText, 2, 1) === ">") {
            $kind = self::KIND_HTML_CLOSE;
            $text = substr($rawText, 3);
        } else {
            throw new \Exception("Invalid comment");
        }
        return $this->setKind($kind)->setText($text);
    }
    
    /**
     * Sets leading comments array
     * 
     * @param Comment[] $comments Comments array
     * 
     * @return $this
     */
    public function setLeadingComments($comments)
    {
        //Comments cannot be attached to other comments
        return $this;
    }
    
    /**
     * Sets trailing comments array
     * 
     * @param Comment[] $comments Comments array
     * 
     * @return $this
     */
    public function setTrailingComments($comments)
    {
        //Comments cannot be attached to other comments
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
        $ret = parent::jsonSerialize();
        unset($ret["leadingComments"]);
        unset($ret["trailingComments"]);
        return $ret;
    }
}