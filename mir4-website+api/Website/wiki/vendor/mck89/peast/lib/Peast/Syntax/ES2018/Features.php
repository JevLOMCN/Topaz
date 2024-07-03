<?php
/**
 * This file is part of the Peast package
 *
 * (c) Marco Marchiò <marco.mm89@gmail.com>
 *
 * For the full copyright and license information refer to the LICENSE file
 * distributed with this source code
 */
namespace Peast\Syntax\ES2018;

/**
 * ES2018 features class
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 * 
 * @codeCoverageIgnore
 */
class Features extends \Peast\Syntax\ES2017\Features
{
    /**
     * Async iteration and generators
     *
     * @var bool
     */
    public $asyncIterationGenerators = true;

    /**
     * Rest/spread properties
     *
     * @var bool
     */
    public $restSpreadProperties = true;

    /**
     * Skip escape sequences checks in tagged template
     *
     * @var bool
     */
    public $skipEscapeSeqCheckInTaggedTemplates = true;
}