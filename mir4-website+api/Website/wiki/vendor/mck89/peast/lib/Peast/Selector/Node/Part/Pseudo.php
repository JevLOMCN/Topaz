<?php
/**
 * This file is part of the Peast package
 *
 * (c) Marco Marchiò <marco.mm89@gmail.com>
 *
 * For the full copyright and license information refer to the LICENSE file
 * distributed with this source code
 */
namespace Peast\Selector\Node\Part;

/**
 * Selector pseudo part base class
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 *
 * @abstract
 */
abstract class Pseudo extends Part
{
    /**
     * Selector name
     *
     * @var string
     */
    protected $name;

    /**
     * Sets the name
     *
     * @param string $name Name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
}