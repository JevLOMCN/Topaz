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
 * Utilities class.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class Utils
{
    /**
     * Converts a string to an array of UTF-8 characters
     * 
     * @param string $str            String to convert
     * @param bool   $strictEncoding If false and the string contains invalid
     *                               UTF-8 characters, it will replace those
     *                               characters with the one defined in the
     *                               mbstring.substitute_character setting
     * 
     * @return array
     * 
     * @throws EncodingException
     */
    static public function stringToUTF8Array($str, $strictEncoding = true)
    {
        if ($str === "") {
            return array();
        }
        $ret = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
        if (preg_last_error() === PREG_BAD_UTF8_ERROR) {
            if (!$strictEncoding) {
                $str = mb_convert_encoding($str, 'UTF-8', 'UTF-8');
                $ret = self::stringToUTF8Array($str, false);
            } else {
                throw new EncodingException("String contains invalid UTF-8");
            }
        }
        return $ret;
    }
    
    /**
     * Converts an unicode code point to UTF-8
     * 
     * @param int $num Unicode code point
     * 
     * @return string
     * 
     * @codeCoverageIgnore
     */
    static public function unicodeToUtf8($num)
    {
        //From: http://stackoverflow.com/questions/1805802/php-convert-unicode-codepoint-to-utf-8#answer-7153133
        if ($num <= 0x7F) {
            return chr($num);
        } elseif ($num <= 0x7FF) {
            return chr(($num >> 6) + 192) .
                   chr(($num & 63) + 128);
        } elseif ($num <= 0xFFFF) {
            return chr(($num >> 12) + 224) .
                   chr((($num >> 6) & 63) + 128) .
                   chr(($num & 63) + 128);
        } elseif ($num <= 0x1FFFFF) {
            return chr(($num >> 18) + 240) .
                   chr((($num >> 12) & 63) + 128) .
                   chr((($num >> 6) & 63) + 128) .
                   chr(($num & 63) + 128);
        }
        return '';
    }
    
    /**
     * Compiled line terminators cache
     * 
     * @var array 
     */
    protected static $lineTerminatorsCache;
    
    /**
     * Returns line terminators array
     * 
     * @return array
     */
    protected static function getLineTerminators()
    {
        if (!self::$lineTerminatorsCache) {
            self::$lineTerminatorsCache = array();
            foreach (Scanner::$lineTerminatorsChars as $char) {
                self::$lineTerminatorsCache[] = is_int($char) ?
                                                self::unicodeToUtf8($char) :
                                                $char;
            }
        }
        return self::$lineTerminatorsCache;
    }

    /**
     * Converts a surrogate pair of Unicode code points to UTF-8
     * 
     * @param string $first  First Unicode code point
     * @param string $second Second Unicode code point
     * 
     * @return string
     * 
     * @codeCoverageIgnore
     */
    static public function surrogatePairToUtf8($first, $second)
    {
        //From: https://stackoverflow.com/questions/39226593/how-to-convert-utf16-surrogate-pairs-to-equivalent-hex-codepoint-in-php
        $value = ((hexdec($first) & 0x3ff) << 10) | (hexdec($second) & 0x3ff);
        return self::unicodeToUtf8($value + 0x10000);
    }
    
    /**
     * This function takes a string as it appears in the source code and returns
     * an unquoted version of it
     * 
     * @param string $str The string to unquote
     * 
     * @return string
     */
    static public function unquoteLiteralString($str)
    {
        //Remove quotes
        $str = substr($str, 1, -1);
        
        //Return immediately if the escape character is missing
        if (strpos($str, "\\") === false) {
            return $str;
        }
        
        $lineTerminators = self::getLineTerminators();
        
        //Surrogate pairs regex
        $surrogatePairsReg = sprintf(
            'u(?:%1$s|\{%1$s\})\\\\u(?:%2$s|\{%2$s\})',
            "[dD][89abAB][0-9a-fA-F]{2}", "[dD][c-fC-F][0-9a-fA-F]{2}"
        );
        
        //Handle escapes
        $patterns = array(
            $surrogatePairsReg,
            "u\{[a-fA-F0-9]+\}",
            "u[a-fA-F0-9]{4}",
            "x[a-fA-F0-9]{2}",
            "0[0-7]{2}",
            "[1-7][0-7]",
            "."
        );
        $reg = "/\\\\(" . implode("|", $patterns) . ")/s";
        $simpleSequence = array(
            "n" => "\n",
            "f" => "\f",
            "r" => "\r",
            "t" => "\t",
            "v" => "\v",
            "b" => "\x8"
        );
        $replacement = function ($m) use ($simpleSequence, $lineTerminators) {
            $type = $m[1][0];
            if (isset($simpleSequence[$type])) {
                // \n, \r, \t ...
                return $simpleSequence[$type];
            } elseif ($type === "u" || $type === "x") {
                //Invalid unicode or hexadecimal sequences
                if (strlen($m[1]) === 1) {
                    return "\\$type";
                }
                // Surrogate pair
                if ($type === "u" && strpos($m[1], "\\") !== false) {
                    $points = explode("\\", $m[1]);
                    return Utils::surrogatePairToUtf8(
                        str_replace(array("{", "}", "u"), "", $points[0]),
                        str_replace(array("{", "}", "u"), "", $points[1])
                    );
                }
                // \uFFFF, \u{FFFF}, \xFF
                $code = substr($m[1], 1);
                $code = str_replace(array("{", "}"), "", $code);
                return Utils::unicodeToUtf8(hexdec($code));
            } elseif ($type >= "0" && $type <= "7") {
                //Invalid octal sequences
                if (strlen($m[1]) === 1) {
                    return "\\$type";
                }
                // \123
                return Utils::unicodeToUtf8(octdec($m[1]));
            } elseif (in_array($m[1], $lineTerminators)) {
                // Escaped line terminators
                return "";
            } else {
                // Escaped characters
                return $m[1];
            }
        };
        return preg_replace_callback($reg, $replacement, $str);
    }
    
    /**
     * This function converts a string to a quoted javascript string
     * 
     * @param string $str   String to quote
     * @param string $quote Quote character
     * 
     * @return string
     */
    static public function quoteLiteralString($str, $quote)
    {
        $escape = self::getLineTerminators();
        $escape[] = $quote;
        $escape[] = "\\\\";
        $reg = "/(" . implode("|", $escape) . ")/";
        $str = preg_replace($reg, "\\\\$1", $str);
        return $quote . $str . $quote;
    }
    
    /**
     * Returns the properties map for the given node
     * 
     * @param mixed $node Node or class to consider
     * 
     * @return array
     */
    static protected function getPropertiesMap($node)
    {
        static $cache = array();
        
        if ($node instanceof \ReflectionClass) {
            $className = $node->getName();
        } else {
            $className = get_class($node);
        }
        
        if (!isset($cache[$className])) {
            $class = new \ReflectionClass($className);
            $parent = $class->getParentClass();
            $props = $parent ? self::getPropertiesMap($parent) : array();
            $defaults = $class->getDefaultProperties();
            if (isset($defaults["propertiesMap"])) {
                $props = array_merge($props, $defaults["propertiesMap"]);
            }
            $cache[$className] = $props;
        }
        return $cache[$className];
    }
    
    /**
     * Returns the properties list for the given node
     * 
     * @param Node\Node $node        Node to consider
     * @param bool      $traversable If true it returns only traversable properties
     * 
     * @return array
     */
    static public function getNodeProperties(Node\Node $node, $traversable = false)
    {
        $props = self::getPropertiesMap($node);
        return array_map(
            function ($prop) {
                $ucProp = ucfirst($prop);
                return array(
                    "name" => $prop,
                    "getter" => "get$ucProp",
                    "setter" => "set$ucProp"
                );
            },
            array_keys($traversable ? array_filter($props) : $props)
        );
    }

    /**
     * Returns an expanded version of the traversable node properties.
     * The return of the function is an array of node properties
     * values with arrays flattened
     *
     * @param Node\Node $node Node
     *
     * @return array
     */
    static public function getExpandedNodeProperties(Node\Node $node)
    {
        $ret = array();
        $props = self::getNodeProperties($node, true);
        foreach ($props as $prop) {
            $val = $node->{$prop["getter"]}();
            if (is_array($val)) {
                $ret = array_merge($ret, $val);
            } else {
                $ret[] = $val;
            }
        }
        return $ret;
    }

    /**
     * Delete an array element by value
     * 
     * @param array $array Array
     * @param mixed $val   Value to remove
     * 
     * @return void
     */
    static public function removeArrayValue(&$array, $val)
    {
        array_splice($array, array_search($val, $array), 1);
    }
}