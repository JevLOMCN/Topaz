<?php
/**
 * This file is part of the Peast package
 *
 * (c) Marco Marchiò <marco.mm89@gmail.com>
 *
 * For the full copyright and license information refer to the LICENSE file
 * distributed with this source code
 */
namespace Peast\Syntax\ES2022;

/**
 * ES2022 features class
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 * 
 * @codeCoverageIgnore
 */
class Features extends \Peast\Syntax\ES2021\Features
{
    /**
     * Private methods and fields
     *
     * @var bool
     */
    public $privateMethodsAndFields = true;

    /**
     * Class fields
     *
     * @var bool
     */
    public $classFields = true;

    /**
     * "in" operator for private fields
     *
     * @var bool
     */
    public $classFieldsPrivateIn = true;

    /**
     * Top level await
     *
     * @var bool
     */
    public $topLevelAwait = true;

    /**
     * Class static block
     *
     * @var bool
     */
    public $classStaticBlock = true;

    /**
     * Arbitrary module namespace identifier names
     *
     * @var bool
     */
    public $arbitraryModuleNSNames = true;
}