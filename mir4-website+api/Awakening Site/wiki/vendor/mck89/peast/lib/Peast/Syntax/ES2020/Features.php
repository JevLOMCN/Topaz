<?php
/**
 * This file is part of the Peast package
 *
 * (c) Marco Marchiò <marco.mm89@gmail.com>
 *
 * For the full copyright and license information refer to the LICENSE file
 * distributed with this source code
 */
namespace Peast\Syntax\ES2020;

/**
 * ES2020 features class
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 * 
 * @codeCoverageIgnore
 */
class Features extends \Peast\Syntax\ES2019\Features
{
    /**
     * Dynamic import
     *
     * @var bool
     */
    public $dynamicImport = true;

    /**
     * BigInt literals
     *
     * @var bool
     */
    public $bigInt = true;

    /**
     * Exported name for export all declarations
     *
     * @var bool
     */
    public $exportedNameInExportAll = true;

    /**
     * Import.meta
     *
     * @var bool
     */
    public $importMeta = true;

    /**
     * Coalescing operator
     *
     * @var bool
     */
    public $coalescingOperator = true;

    /**
     * Optional chaining
     *
     * @var bool
     */
    public $optionalChaining = true;
}