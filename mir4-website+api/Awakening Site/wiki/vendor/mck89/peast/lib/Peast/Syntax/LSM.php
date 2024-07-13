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
 * Longest Sequence Matcher. Utility class used by the scanner to consume
 * the longest sequence of character given a set of allowed characters sequences.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class LSM
{
    /**
     * Internal sequences map
     * 
     * @var array 
     */
    protected $map = array();
    
    /**
     * Encoding handle flag
     * 
     * @var bool 
     */
    protected $handleEncoding = false;
    
    /**
     * Class constructor
     *
     * @param array $sequences      Allowed characters sequences
     * @param bool  $handleEncoding True to handle encoding when matching
     */
    function __construct($sequences, $handleEncoding = false)
    {
        $this->handleEncoding = $handleEncoding;
        foreach ($sequences as $s) {
            $this->add($s);
        }
    }
    
    /**
     * Adds a sequence
     * 
     * @param string $sequence Sequence to add
     * 
     * @return $this
     */
    public function add($sequence)
    {
        if ($this->handleEncoding) {
            $s = Utils::stringToUTF8Array($sequence);
            $first = $s[0];
            $len = count($s);
        } else {    
            $first = $sequence[0];
            $len = strlen($sequence);
        }
        if (!isset($this->map[$first])) {
            $this->map[$first] = array(
                "maxLen" => $len,
                "map" => array($sequence)
            );
        } else {
            $this->map[$first]["map"][] = $sequence;
            $this->map[$first]["maxLen"] = max($this->map[$first]["maxLen"], $len);
        }
        return $this;
    }
    
    /**
     * Removes a sequence
     * 
     * @param string $sequence Sequence to remove
     * 
     * @return $this
     */
    public function remove($sequence)
    {
        if ($this->handleEncoding) {
            $s = Utils::stringToUTF8Array($sequence);
            $first = $s[0];
        } else {
            $first = $sequence[0];
        }
        if (isset($this->map[$first])) {
            $len = $this->handleEncoding ? count($s) : strlen($sequence);
            $this->map[$first]["map"] = array_diff(
                $this->map[$first]["map"], array($sequence)
            );
            if (!count($this->map[$first]["map"])) {
                unset($this->map[$first]);
            } elseif ($this->map[$first]["maxLen"] === $len) {
                // Recalculate the max length if necessary
                foreach ($this->map[$first]["map"] as $m) {
                    $this->map[$first]["maxLen"] = max(
                        $this->map[$first]["maxLen"],
                        strlen($m)
                    );
                }
            }
        }
        return $this;
    }
    
    /**
     * Executes the match. It returns an array where the first element is the
     * number of consumed characters and the second element is the match. If
     * no match is found it returns null.
     * 
     * @param Scanner   $scanner    Scanner instance
     * @param int       $index      Current index
     * @param string    $char       Current character
     * 
     * @return array|null
     */
    public function match($scanner, $index, $char)
    {
        $consumed = 1;
        $bestMatch = null;
        if (isset($this->map[$char])) {
            //If the character is present in the map and it has a max length of
            //1, match immediately
            if ($this->map[$char]["maxLen"] === 1) {
                $bestMatch = array($consumed, $char);
            } else {
                //Otherwise consume a number of characters equal to the max
                //length and find the longest match
                $buffer = $char;
                $map = $this->map[$char]["map"];
                $maxLen = $this->map[$char]["maxLen"];
                do {
                    if (in_array($buffer, $map)) {
                        $bestMatch = array($consumed, $buffer);
                    }
                    $nextChar = $scanner->charAt($index + $consumed);
                    if ($nextChar === null) {
                        break;
                    }
                    $buffer .= $nextChar;
                    $consumed++;
                } while ($consumed <= $maxLen);
            }
        }
        return $bestMatch;
    }
}