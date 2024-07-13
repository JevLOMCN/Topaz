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
 * A node that represents a default import specifier.
 * For example "test" in: import test from "test.js".
 * 
 * @author Marco Marchiò <marco.mm89@gmail.com>
 */
class ImportNamespaceSpecifier extends ModuleSpecifier
{
}