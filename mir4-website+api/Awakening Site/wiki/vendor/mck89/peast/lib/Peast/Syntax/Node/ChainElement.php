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
 * A node that represents a chain element.
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
abstract class ChainElement extends Node implements Expression
{
    protected $propertiesMap = array(
        "optional" => false
    );

    /**
     * Optional flag that is true if the node is in the optional
     * part of a chain expression
     *
     * @var bool
     */
    protected $optional = false;

    /**
     * Returns the optional flag that is true if the node is in
     * the optional part of a chain expression
     *
     * @return bool
     */
    public function getOptional()
    {
        return $this->optional;
    }

    /**
     * Sets the optional flag that is true if the node is in
     * the optional part of a chain expression
     *
     * @param bool $optional Optional flag
     *
     * @return $this
     */
    public function setOptional($optional)
    {
        $this->optional = (bool) $optional;
        return $this;
    }
}