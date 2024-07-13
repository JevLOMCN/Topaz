<?php

namespace Wikimedia\RemexHtml\Serializer;

/**
 * The interface for classes that help Serializer to convert nodes to strings.
 * Serializer assumes that the return values of these functions can be
 * concatenated to make a document.
 *
 * It is not safe to assume that the methods will be called in any particular
 * order, or that the return values will actually be retained in the final
 * Serializer result.
 */
interface Formatter {
	/**
	 * Get a string which starts the document
	 *
	 * @param string|null $fragmentNamespace
	 * @param string|null $fragmentName
	 * @return string
	 */
	public function startDocument( $fragmentNamespace, $fragmentName );

	/**
	 * Encode the given character substring
	 *
	 * @param SerializerNode $parent The parent of the text node (at creation time)
	 * @param string $text
	 * @param int $start The offset within $text
	 * @param int $length The number of bytes within $text
	 * @return string
	 */
	public function characters( SerializerNode $parent, $text, $start, $length );

	/**
	 * Encode the given element
	 *
	 * @param SerializerNode $parent The parent of the node (when it is closed)
	 * @param SerializerNode $node The element to encode
	 * @param string|null $contents The previously-encoded contents, or null
	 *   for a void element. Void elements can be serialized as self-closing
	 *   tags.
	 * @return string
	 */
	public function element( SerializerNode $parent, SerializerNode $node, $contents );

	/**
	 * Encode a comment
	 * @param SerializerNode $parent The parent of the node (at creation time)
	 * @param string $text The inner text of the comment
	 * @return string
	 */
	public function comment( SerializerNode $parent, $text );

	/**
	 * Encode a doctype. This event occurs when the source document has a doctype,
	 * it can return an empty string if the formatter wants to use its own doctype.
	 *
	 * @param string $name The doctype name, usually "html"
	 * @param string $public The PUBLIC identifier
	 * @param string $system The SYSTEM identifier
	 * @return string
	 */
	public function doctype( $name, $public, $system );
}
