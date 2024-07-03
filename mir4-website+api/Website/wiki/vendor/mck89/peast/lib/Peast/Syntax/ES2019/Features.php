<?php
/**
 * This file is part of the Peast package
 *
 * (c) Marco Marchiò <marco.mm89@gmail.com>
 *
 * For the full copyright and license information refer to the LICENSE file
 * distributed with this source code
 */
namespace Peast\Syntax\ES2019;

/**
 * ES2019 features class
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 * 
 * @codeCoverageIgnore
 */
class Features extends \Peast\Syntax\ES2018\Features
{
    /**
     * Optional catch binding
     *
     * @var bool
     */
    public $optionalCatchBinding = true;

    /**
     * Paragraph and line separator in strings
     *
     * @var bool
     */
    public $paragraphLineSepInStrings = true;
}