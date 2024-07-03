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
class Compact extends Base
{
    /**
     * New line character
     * 
     * @var string
     */
    protected $newLine = "";
    
    /**
     * Indentation character
     * 
     * @var string
     */
    protected $indentation = "";
    
    /**
     * Boolean that indicates if operators must be surrounded by spaces
     * 
     * @var bool
     */
    protected $spacesAroundOperators = false;
    
    /**
     * Boolean that indicates if blocks of code must be wrapped in curly
     * brackets also if they contain only one instruction
     * 
     * @var bool
     */
    protected $alwaysWrapBlocks = false;
    
    /**
     * Boolean that indicates if comments must be rendered
     * 
     * @var bool
     */
    protected $renderComments = false;
}