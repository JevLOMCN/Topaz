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
 * A node that represents a numeric literal.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class NumericLiteral extends Literal
{
    /**
     * Map of node properties
     * 
     * @var array 
     */
    protected $propertiesMap = array(
        "format" => false
    );
    
    //Format constants
    /**
     * Decimal number format
     */
    const DECIMAL = "decimal";
    
    /**
     * Hexadecimal number format
     */
    const HEXADECIMAL = "hexadecimal";
    
    /**
     * Octal number format
     */
    const OCTAL = "octal";
    
    /**
     * Binary number format
     */
    const BINARY = "binary";
    
    /**
     * Node's numeric format
     * 
     * @var string
     */
    protected $format = self::DECIMAL;
    
    /**
     * Numeric forms conversion rules
     * 
     * @var array
     */
    protected $forms = array(
        "b" => array(
            "check" => "/^0b[01]+[01_]*$/i",
            "conv" => "bindec",
            "format" => self::BINARY
        ),
        "o" => array(
            "check" => "/^0o[0-7]+[0-7_]*$/i",
            "conv" => "octdec",
            "format" => self::OCTAL
        ),
        "x" => array(
            "check" => "/^0x[0-9a-f]+[0-9a-f_]*$/i",
            "conv" => "hexdec",
            "format" => self::HEXADECIMAL
        ),
    );
    
    /**
     * Sets node's value
     * 
     * @param float $value Value
     * 
     * @return $this
     */
    public function setValue($value)
    {
        $value = (float) $value;
        $intValue = (int) $value;
        if ($value == $intValue) {
            $value = $intValue;
        }
        $this->value = $value;
        //Force recalculation of the raw value
        return $this->setFormat($this->format);
    }
    
    /**
     * Sets node's raw value
     * 
     * @param mixed $raw Raw value
     * 
     * @return $this
     * 
     * @throws \Exception
     */
    public function setRaw($raw)
    {
        $value = $raw;
        $format = self::DECIMAL;
        if (is_string($value) && $value !== "") {
            //Hexadecimal, binary or octal
            $startZero = $value[0] === "0";
            $form = $startZero && isset($value[1]) ? strtolower($value[1]) : null;
            //Numeric separator cannot appear at the beginning or at the end of the number
            if (preg_match("/^_|_$/", $value)) {
                throw new \Exception("Invalid numeric value");
            } elseif (isset($this->forms[$form])) {
                $formDef = $this->forms[$form];
                if (!preg_match($formDef["check"], $value)) {
                    throw new \Exception("Invalid " . $formDef["format"]);
                }
                $value = str_replace("_", "", $value);
                $value = $formDef["conv"]($value);
                $format = $formDef["format"];
            } elseif ($startZero && preg_match("/^0[0-7_]+$/", $value)) {
                //Legacy octal form
                $value = str_replace("_", "", $value);
                $value = octdec($value);
                $format = self::OCTAL;
            } elseif (
                preg_match("/^([\d_]*\.?[\d_]*)(?:e[+\-]?[\d_]+)?$/i", $value, $match) &&
                $match[1] !== "" &&
                $match[1] !== "." &&
                !preg_match("/_e|e[+-]?_|_$/", $value)
            ) {
                $value = str_replace("_", "", $value);
            } else {
                throw new \Exception("Invalid numeric value");
            }
        } elseif (!is_int($value) && !is_float($value)) {
            throw new \Exception("Invalid numeric value");
        }
        $value = (float) $value;
        $intValue = (int) $value;
        if ($value == $intValue) {
            $value = $intValue;
        }
        $this->format = $format;
        $this->value = $value;
        $this->raw = $raw;
        return $this;
    }
    
    /**
     * Returns node's numeric format
     * 
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }
    
    /**
     * Sets node's numeric format
     * 
     * @param string $format Format, one of the format constants
     * 
     * @return $this
     */
    public function setFormat($format)
    {
        $this->format = $format;
        switch ($format) {
            case self::BINARY:
                $this->raw = "0b" . decbin($this->value);
            break;
            case self::OCTAL:
                $this->raw = "0o" . decoct($this->value);
            break;
            case self::HEXADECIMAL:
                $this->raw = "0x" . dechex($this->value);
            break;
            default:
                $this->raw = (string) $this->value;
            break;
        }
        return $this;
    }
}