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
 * Parser features class
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 * 
 * @codeCoverageIgnore
 */
class Features
{
    /**
     * Exponentiation operator
     *
     * @var bool
     */
    public $exponentiationOperator = false;

    /**
     * Async/await
     *
     * @var bool
     */
    public $asyncAwait = false;

    /**
     * Trailing comma in function calls and declarations
     *
     * @var bool
     */
    public $trailingCommaFunctionCallDeclaration = false;

    /**
     * For-in initializer
     *
     * @var bool
     */
    public $forInInitializer = false;

    /**
     * Async iteration and generators
     *
     * @var bool
     */
    public $asyncIterationGenerators = false;

    /**
     * Rest/spread properties
     *
     * @var bool
     */
    public $restSpreadProperties = false;

    /**
     * Skip escape sequences checks in tagged template
     *
     * @var bool
     */
    public $skipEscapeSeqCheckInTaggedTemplates = false;

    /**
     * Optional catch binding
     *
     * @var bool
     */
    public $optionalCatchBinding = false;

    /**
     * Paragraph and line separator in strings
     *
     * @var bool
     */
    public $paragraphLineSepInStrings = false;

    /**
     * Dynamic import
     *
     * @var bool
     */
    public $dynamicImport = false;

    /**
     * BigInt literals
     *
     * @var bool
     */
    public $bigInt = false;

    /**
     * Exported name for export all declarations
     *
     * @var bool
     */
    public $exportedNameInExportAll = false;

    /**
     * Import.meta
     *
     * @var bool
     */
    public $importMeta = false;

    /**
     * Coalescing operator
     *
     * @var bool
     */
    public $coalescingOperator = false;

    /**
     * Optional chaining
     *
     * @var bool
     */
    public $optionalChaining = false;

    /**
     * Logical assignment operators
     *
     * @var bool
     */
    public $logicalAssignmentOperators = false;

    /**
     * Numeric literal separator
     *
     * @var bool
     */
    public $numericLiteralSeparator = false;

    /**
     * Private methods and fields
     *
     * @var bool
     */
    public $privateMethodsAndFields = false;

    /**
     * Class fields
     *
     * @var bool
     */
    public $classFields = false;

    /**
     * "in" operator for private fields
     *
     * @var bool
     */
    public $classFieldsPrivateIn = false;

    /**
     * Top level await
     *
     * @var bool
     */
    public $topLevelAwait = false;

    /**
     * Class static block
     *
     * @var bool
     */
    public $classStaticBlock = false;

    /**
     * Arbitrary module namespace identifier names
     *
     * @var bool
     */
    public $arbitraryModuleNSNames = false;

    /**
     * Hashbang comments
     *
     * @var bool
     */
    public $hashbangComments = false;
}