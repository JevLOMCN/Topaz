<?php
/**
 * This file is part of the Peast package
 *
 * (c) Marco Marchiò <marco.mm89@gmail.com>
 *
 * For the full copyright and license information refer to the LICENSE file
 * distributed with this source code
 */
namespace Peast\Formatter;

/**
 * Compact formatter.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class Expanded extends Base
{
    /**
     * Boolean that indicates if open curly brackets in code blocks must be
     * on a new line
     * 
     * @var bool
     */
    protected $newLineBeforeCurlyBracket = true;
    
    /**
     * Boolean that indicates if content inside round brackets must be
     * surrounded by spaces
     * 
     * @var bool
     */
    protected $spacesInsideRoundBrackets = true;
}