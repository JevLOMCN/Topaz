<?php
/**
 * This file is part of the Peast package
 *
 * (c) Marco Marchiò <marco.mm89@gmail.com>
 *
 * For the full copyright and license information refer to the LICENSE file
 * distributed with this source code
 */
namespace Peast\Syntax\ES2017;

/**
 * ES2017 features class
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 * 
 * @codeCoverageIgnore
 */
class Features extends \Peast\Syntax\ES2016\Features
{
    /**
     * Async/await
     *
     * @var bool
     */
    public $asyncAwait = true;

    /**
     * Trailing comma in function calls and declarations
     *
     * @var bool
     */
    public $trailingCommaFunctionCallDeclaration = true;

    /**
     * For-in initializer
     *
     * @var bool
     */
    public $forInInitializer = true;
}