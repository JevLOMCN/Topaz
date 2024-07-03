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
 * A node that represents a class expression
 * For example: test = class {}
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class ClassExpression extends Class_ implements Expression
{
}