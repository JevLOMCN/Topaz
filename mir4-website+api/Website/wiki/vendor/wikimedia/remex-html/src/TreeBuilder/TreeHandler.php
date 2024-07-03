<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\Tokenizer\Attributes;

interface TreeHandler {
	/**
	 * Called when parsing starts.
	 *
	 * @param string|null $fragmentNamespace The fragment namespace, or null
	 *   to run in document mode.
	 * @param string|null $fragmentName The fragment tag name, or null to run
	 *   in document mode.
	 */
	public function startDocument( $fragmentNamespace, $fragmentName );

	/**
	 * Called when parsing stops.
	 *
	 * @param int $pos The input string length, i.e. the past-the-end position.
	 */
	public function endDocument( $pos );

	/**
	 * Insert characters.
	 *
	 * @param int $preposition The placement of the new node with respect
	 *   to $ref. May be TreeBuilder::
	 *    - BEFORE: insert as a sibling before the reference element
	 *    - UNDER: append as the last child of the reference element
	 *    - ROOT: append as the last child of the document node
	 * @param Element|null $ref Insert before/below this element, or null if
	 *   $preposition is ROOT.
	 * @param string $text The text to insert is a substring of this string,
	 *   with the start and length of the substring given by $start and
	 *   $length. We do it this way to avoid unnecessary copying.
	 * @param int $start The start of the substring
	 * @param int $length The length of the substring
	 * @param int $sourceStart The input position. This is not necessarily
	 *   accurate, particularly when the tokenizer is run without ignoreEntities,
	 *   or in CDATA sections.
	 * @param int $sourceLength The length of the input which is consumed.
	 *   The same caveats apply as for $sourceStart.
	 */
	public function characters(
		$preposition, $ref, $text, $start, $length, $sourceStart, $sourceLength
	);

	/**
	 * Insert an element. The element name and attributes are given in the
	 * supplied Element object. Handlers for this event typically attach an
	 * identifier to the userData property of the Element object, to identify
	 * the element when it is used again in subsequent tree mutations.
	 *
	 * @param int $preposition The placement of the new node with respect
	 *   to $ref. May be TreeBuilder::
	 *    - BEFORE: insert as a sibling before the reference element
	 *    - UNDER: append as the last child of the reference element
	 *    - ROOT: append as the last child of the document node
	 * @param Element|null $ref Insert before/below this element, or null if
	 *   $preposition is ROOT.
	 * @param Element $element An object containing information about the new
	 *   element. The same object will be used for $parent and $refNode in
	 *   other calls as appropriate. The handler can set $element->userData to
	 *   attach a suitable DOM object to identify the mutation target in
	 *   subsequent calls.
	 * @param bool $void True if this is a void element which cannot
	 *   have any children appended to it. This is usually true if the element
	 *   is closed by the same token that opened it. No endTag() event will be
	 *   sent for such an element. This is only true if self-closing tags are
	 *   acknowledged for this tag name, so it is a hint to the serializer that
	 *   a self-closing tag is acceptable.
	 * @param int $sourceStart The input position
	 * @param int $sourceLength The length of the input which is consumed
	 */
	public function insertElement( $preposition, $ref, Element $element, $void,
		$sourceStart, $sourceLength );

	/**
	 * A hint that an element was closed and was removed from the stack
	 * of open elements. It probably won't be mutated again.
	 *
	 * @param Element $element The element being ended
	 * @param int $sourceStart The input position
	 * @param int $sourceLength The length of the input which is consumed
	 */
	public function endTag( Element $element, $sourceStart, $sourceLength );

	/**
	 * A valid DOCTYPE token was found.
	 *
	 * @param string $name The doctype name, usually "html"
	 * @param string $public The PUBLIC identifier
	 * @param string $system The SYSTEM identifier
	 * @param int $quirks The quirks mode implied from the doctype. One of:
	 *   - TreeBuilder::NO_QUIRKS : no quirks
	 *   - TreeBuilder::LIMITED_QUIRKS : limited quirks
	 *   - TreeBuilder::QUIRKS : full quirks
	 * @param int $sourceStart The input position
	 * @param int $sourceLength The length of the input which is consumed
	 */
	public function doctype( $name, $public, $system, $quirks, $sourceStart, $sourceLength );

	/**
	 * Insert a comment
	 *
	 * @param int $preposition The placement of the new node with respect
	 *   to $ref. May be TreeBuilder::
	 *    - BEFORE: insert as a sibling before the reference element
	 *    - UNDER: append as the last child of the reference element
	 *    - ROOT: append as the last child of the document node
	 * @param Element|null $ref Insert before/below this element, or null if
	 *   $preposition is ROOT.
	 * @param string $text The text of the comment
	 * @param int $sourceStart The input position
	 * @param int $sourceLength The length of the input which is consumed
	 */
	public function comment( $preposition, $ref, $text, $sourceStart, $sourceLength );

	/**
	 * A parse error
	 *
	 * @param string $text An error message explaining in English what the
	 *   author did wrong, and what the parser intends to do about the
	 *   situation.
	 * @param int $pos The input position at which the error occurred
	 */
	public function error( $text, $pos );

	/**
	 * Add attributes to an existing element. This is used to update the
	 * attributes of the <html> or <body> elements. The event receiver
	 * should add only those attributes which the original element does not
	 * already have. It should not overwrite existing attributes.
	 *
	 * @param Element $element The element to update
	 * @param Attributes $attrs The new attributes to add
	 * @param int $sourceStart The input position
	 */
	public function mergeAttributes( Element $element, Attributes $attrs, $sourceStart );

	/**
	 * Remove a node from the tree, and all its children. This is only done
	 * when a <frameset> element is found, which triggers removal of the
	 * partially-constructed body element.
	 *
	 * @param Element $element The element to remove
	 * @param int $sourceStart The location in the source at which this
	 *   action was triggered.
	 */
	public function removeNode( Element $element, $sourceStart );

	/**
	 * Take all children of a given parent $element, and insert them as
	 * children of $newParent, removing them from their original parent in the
	 * process. Insert $newParent as now the only child of $element.
	 *
	 * @param Element $element The old parent element
	 * @param Element $newParent The new parent element
	 * @param int $sourceStart The location in the source at which this
	 *   action was triggered.
	 */
	public function reparentChildren( Element $element, Element $newParent, $sourceStart );

}
