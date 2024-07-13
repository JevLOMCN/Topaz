<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Ext;

use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Utils\DOMUtils as DU;

/**
 * This class provides DOM helpers useful for extensions.
 */
class DOMUtils {
	/**
	 * Test if a node matches a given typeof.
	 * @param Node $node node
	 * @param string $type type
	 * @return bool
	 */
	public static function hasTypeOf( Node $node, string $type ): bool {
		return DU::hasTypeOf( $node, $type );
	}

	/**
	 * @param Element $element
	 * @param string $regex Partial regular expression, e.g. "foo|bar"
	 * @return bool
	 */
	public static function hasClass( Element $element, string $regex ): bool {
		return DU::hasClass( $element, $regex );
	}

	/**
	 * Determine whether the node matches the given `typeof` attribute value.
	 *
	 * @param Node $n The node to test
	 * @param string $typeRe Regular expression matching the expected value of
	 *   the `typeof` attribute.
	 * @return ?string The matching `typeof` value, or `null` if there is
	 *   no match.
	 */
	public static function matchTypeOf( Node $n, string $typeRe ): ?string {
		return DU::matchTypeOf( $n, $typeRe );
	}

	/**
	 * Add a type to the typeof attribute. If the elt already has an existing typeof,
	 * it makes that attribute a string of space separated types.
	 * @param Element $elt
	 * @param string $type type
	 */
	public static function addTypeOf( Element $elt, string $type ): void {
		DU::addTypeOf( $elt, $type );
	}

	/**
	 * Remove a type from the typeof attribute.
	 * @param Element $elt
	 * @param string $type type
	 */
	public static function removeTypeOf( Element $elt, string $type ): void {
		DU::removeTypeOf( $elt, $type );
	}

	/**
	 * Determine whether the node matches the given rel attribute value.
	 *
	 * @param Node $n
	 * @param string $rel Expected value of "rel" attribute, as a literal string.
	 * @return ?string The match if there is one, null otherwise
	 */
	public static function matchRel( Node $n, string $rel ): ?string {
		return DU::matchRel( $n, $rel );
	}

	/**
	 * Add a type to the rel attribute.  This method should almost always
	 * be used instead of `setAttribute`, to ensure we don't overwrite existing
	 * rel information.
	 *
	 * @param Element $node node
	 * @param string $rel type
	 */
	public static function addRel( Element $node, string $rel ): void {
		DU::addRel( $node, $rel );
	}

	/**
	 * Assert that this is a DOM element node.
	 * This is primarily to help phan analyze variable types.
	 * @phan-assert Element $node
	 * @param ?Node $node
	 * @return bool Always returns true
	 */
	public static function assertElt( ?Node $node ): bool {
		return DU::assertElt( $node );
	}

	/**
	 * Move 'from'.childNodes to 'to' adding them before 'beforeNode'
	 * If 'beforeNode' is null, the nodes are appended at the end.
	 * @param Node $from Source node. Children will be removed.
	 * @param Node $to Destination node. Children of $from will be added here
	 * @param ?Node $beforeNode Add the children before this node.
	 */
	public static function migrateChildren(
		Node $from, Node $to, ?Node $beforeNode = null
	): void {
		DU::migrateChildren( $from, $to, $beforeNode );
	}

	/**
	 * Add attributes to a node element.
	 *
	 * @param Element $elt element
	 * @param array $attrs attributes
	 */
	public static function addAttributes( Element $elt, array $attrs ): void {
		DU::addAttributes( $elt, $attrs );
	}

	/**
	 * Find an ancestor of $node with nodeName $name.
	 *
	 * @param Node $node
	 * @param string $name
	 * @return ?Element
	 */
	public static function findAncestorOfName( Node $node, string $name ): ?Element {
		return DU::findAncestorOfName( $node, $name );
	}
}
