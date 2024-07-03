<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Utils;

use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\Core\ClientError;
use Wikimedia\Parsoid\DOM\Comment;
use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\Wikitext\Consts;
use Wikimedia\Parsoid\Wt2Html\XMLSerializer;
use Wikimedia\RemexHtml\DOM\DOMBuilder;
use Wikimedia\RemexHtml\Tokenizer\Tokenizer;
use Wikimedia\RemexHtml\TreeBuilder\Dispatcher;
use Wikimedia\RemexHtml\TreeBuilder\TreeBuilder;

/**
 * DOM utilities for querying the DOM. This is largely independent of Parsoid
 * although some Parsoid details (TokenUtils, inline content version)
 * have snuck in.
 */
class DOMUtils {

	/**
	 * Parse HTML, return the tree.
	 *
	 * @param string $html
	 * @param bool $validateXMLNames
	 * @return Document
	 */
	public static function parseHTML(
		string $html, bool $validateXMLNames = false
	): Document {
		if ( !preg_match( '/^<(?:!doctype|html|body)/i', $html ) ) {
			// Make sure that we parse fragments in the body. Otherwise comments,
			// link and meta tags end up outside the html element or in the head
			// elements.
			$html = '<body>' . $html;
		}

		$domBuilder = new class( [
			'suppressHtmlNamespace' => true,
		] ) extends DOMBuilder
		{
			/** @inheritDoc */
			protected function createDocument(
				string $doctypeName = null,
				string $public = null,
				string $system = null
			) {
				// @phan-suppress-next-line PhanTypeMismatchReturn
				return DOMCompat::newDocument( false );
			}
		};
		$treeBuilder = new TreeBuilder( $domBuilder, [ 'ignoreErrors' => true ] );
		$dispatcher = new Dispatcher( $treeBuilder );
		$tokenizer = new Tokenizer( $dispatcher, $html, [ 'ignoreErrors' => true ] );
		$tokenizer->execute( [] );
		if ( $validateXMLNames && $domBuilder->isCoerced() ) {
			throw new ClientError( 'Encountered a name invalid in XML.' );
		}
		$frag = $domBuilder->getFragment();
		'@phan-var Document $frag'; // @var Document $frag
		return $frag;
	}

	/**
	 * This is a simplified version of the DOMTraverser.
	 * Consider using that before making this more complex.
	 *
	 * FIXME: Move to DOMTraverser OR create a new class?
	 * @param Node $node
	 * @param callable $handler
	 * @param mixed ...$args
	 */
	public static function visitDOM( Node $node, callable $handler, ...$args ): void {
		$handler( $node, ...$args );
		$node = $node->firstChild;
		while ( $node ) {
			$next = $node->nextSibling;
			self::visitDOM( $node, $handler, ...$args );
			$node = $next;
		}
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
		while ( $from->firstChild ) {
			$to->insertBefore( $from->firstChild, $beforeNode );
		}
	}

	/**
	 * Copy 'from'.childNodes to 'to' adding them before 'beforeNode'
	 * 'from' and 'to' belong to different documents.
	 *
	 * If 'beforeNode' is null, the nodes are appended at the end.
	 * @param Node $from
	 * @param Node $to
	 * @param ?Node $beforeNode
	 */
	public static function migrateChildrenBetweenDocs(
		Node $from, Node $to, ?Node $beforeNode = null
	): void {
		$n = $from->firstChild;
		$destDoc = $to->ownerDocument;
		while ( $n ) {
			$to->insertBefore( $destDoc->importNode( $n, true ), $beforeNode );
			$n = $n->nextSibling;
		}
	}

	// phpcs doesn't like @phan-assert...
	// phpcs:disable MediaWiki.Commenting.FunctionAnnotations.UnrecognizedAnnotation

	/**
	 * Assert that this is a DOM element node.
	 * This is primarily to help phan analyze variable types.
	 * @phan-assert Element $node
	 * @param ?Node $node
	 * @return bool Always returns true
	 * @phan-assert Element $node
	 */
	public static function assertElt( ?Node $node ): bool {
		Assert::invariant( $node instanceof Element, "Expected an element" );
		return true;
	}

	/**
	 * @param ?Node $node
	 * @return bool
	 */
	public static function isRemexBlockNode( ?Node $node ): bool {
		return $node instanceof Element &&
			!isset( Consts::$HTML['OnlyInlineElements'][DOMCompat::nodeName( $node )] ) &&
			// This is a superset of \\MediaWiki\Tidy\RemexCompatMunger::$metadataElements
			!self::isMetaDataTag( $node );
	}

	/**
	 * @param ?Node $node
	 * @return bool
	 */
	public static function isWikitextBlockNode( ?Node $node ): bool {
		return $node && TokenUtils::isWikitextBlockTag( DOMCompat::nodeName( $node ) );
	}

	/**
	 * Determine whether this is a formatting DOM element.
	 * @param ?Node $node
	 * @return bool
	 */
	public static function isFormattingElt( ?Node $node ): bool {
		return $node && isset( Consts::$HTML['FormattingTags'][DOMCompat::nodeName( $node )] );
	}

	/**
	 * Determine whether this is a quote DOM element.
	 * @param ?Node $node
	 * @return bool
	 */
	public static function isQuoteElt( ?Node $node ): bool {
		return $node && isset( Consts::$WTQuoteTags[DOMCompat::nodeName( $node )] );
	}

	/**
	 * Determine whether this is the <body> DOM element.
	 * @param ?Node $node
	 * @return bool
	 */
	public static function isBody( ?Node $node ): bool {
		return $node && DOMCompat::nodeName( $node ) === 'body';
	}

	/**
	 * Determine whether this is a removed DOM node but Node object yet
	 * @param ?Node $node
	 * @return bool
	 */
	public static function isRemoved( ?Node $node ): bool {
		return !$node || !isset( $node->nodeType );
	}

	/**
	 * Build path from a node to the root of the document.
	 *
	 * @param Node $node
	 * @return Node[] Path including all nodes from $node to the root of the document
	 */
	public static function pathToRoot( Node $node ): array {
		$path = [];
		do {
			$path[] = $node;
		// phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
		} while ( $node = $node->parentNode );
		return $path;
	}

	/**
	 * Compute the edge length of the path from $node to the root.
	 * Root document is at depth 0, <html> at 1, <body> at 2.
	 * @param Node $node
	 * @return int
	 */
	public static function nodeDepth( Node $node ): int {
		$edges = 0;
		// phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
		while ( $node = $node->parentNode ) {
			$edges++;
		}
		return $edges;
	}

	/**
	 * Build path from a node to its passed-in sibling.
	 * Return will not include the passed-in sibling.
	 *
	 * @param Node $node
	 * @param Node $sibling
	 * @param bool $left indicates whether to go backwards, use previousSibling instead of nextSibling.
	 * @return Node[]
	 */
	public static function pathToSibling( Node $node, Node $sibling, bool $left ): array {
		$path = [];
		while ( $node && $node !== $sibling ) {
			$path[] = $node;
			$node = $left ? $node->previousSibling : $node->nextSibling;
		}
		return $path;
	}

	/**
	 * Check whether a node `n1` comes before another node `n2` in
	 * their parent's children list.
	 *
	 * @param Node $n1 The node you expect to come first.
	 * @param Node $n2 Expected later sibling.
	 * @return bool
	 */
	public static function inSiblingOrder( Node $n1, Node $n2 ): bool {
		while ( $n1 && $n1 !== $n2 ) {
			$n1 = $n1->nextSibling;
		}
		return $n1 !== null;
	}

	/**
	 * Check that a node 'n1' is an ancestor of another node 'n2' in
	 * the DOM. Returns true if n1 === n2.
	 * $n1 is the suspected ancestor.
	 * $n2 The suspected descendant.
	 *
	 * @param Node $n1
	 * @param Node $n2
	 * @return bool
	 */
	public static function isAncestorOf( Node $n1, Node $n2 ): bool {
		while ( $n2 && $n2 !== $n1 ) {
			$n2 = $n2->parentNode;
		}
		return $n2 !== null;
	}

	/**
	 * Find an ancestor of $node with nodeName $name.
	 *
	 * @param Node $node
	 * @param string $name
	 * @return ?Element
	 */
	public static function findAncestorOfName( Node $node, string $name ): ?Element {
		$node = $node->parentNode;
		while ( $node && DOMCompat::nodeName( $node ) !== $name ) {
			$node = $node->parentNode;
		}
		'@phan-var Element $node'; // @var Element $node
		return $node;
	}

	/**
	 * Check whether $node has $name or has an ancestor named $name.
	 *
	 * @param Node $node
	 * @param string $name
	 * @return bool
	 */
	public static function hasNameOrHasAncestorOfName( Node $node, string $name ): bool {
		return DOMCompat::nodeName( $node ) === $name || self::findAncestorOfName( $node, $name ) !== null;
	}

	/**
	 * Determine whether the node matches the given nodeName and attribute value.
	 * Returns true if node name matches and the attribute equals "typeof"
	 *
	 * @param Node $n The node to test
	 * @param string $name The expected nodeName of $n
	 * @param string $typeRe Regular expression matching the expected value of
	 *   `typeof` attribute.
	 * @return ?string The matching `typeof` value, or `null` if there is
	 *   no match.
	 */
	public static function matchNameAndTypeOf( Node $n, string $name, string $typeRe ): ?string {
		return DOMCompat::nodeName( $n ) === $name ? self::matchTypeOf( $n, $typeRe ) : null;
	}

	/**
	 * Determine whether the node matches the given nodeName and typeof
	 * attribute value; the typeof is given as string.
	 *
	 * @param Node $n
	 * @param string $name node name to test for
	 * @param string $type Expected value of "typeof" attribute (literal string)
	 * @return bool True if the node matches.
	 */
	public static function hasNameAndTypeOf( Node $n, string $name, string $type ): bool {
		return self::matchNameAndTypeOf(
			$n, $name, '/^' . preg_quote( $type, '/' ) . '$/'
		) !== null;
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
		return self::matchMultivalAttr( $n, 'typeof', $typeRe );
	}

	/**
	 * Determine whether the node matches the given `rel` attribute value.
	 *
	 * @param Node $n The node to test
	 * @param string $relRe Regular expression matching the expected value of
	 *   the `rel` attribute.
	 * @return ?string The matching `rel` value, or `null` if there is
	 *   no match.
	 */
	public static function matchRel( Node $n, string $relRe ): ?string {
		return self::matchMultivalAttr( $n, 'rel', $relRe );
	}

	/**
	 * Determine whether the node matches the given multivalue attribute value.
	 *
	 * @param Node $n The node to test
	 * @param string $attrName the attribute to test (typically 'rel' or 'typeof')
	 * @param string $valueRe Regular expression matching the expected value of
	 *   the attribute.
	 * @return ?string The matching attribute value, or `null` if there is
	 *   no match.
	 */
	private static function matchMultivalAttr( Node $n, string $attrName, string $valueRe ): ?string {
		if ( !( $n instanceof Element ) ) {
			return null;
		}
		$attrValue = DOMCompat::getAttribute( $n, $attrName );
		if ( $attrValue === null || $attrValue === '' ) {
			return null;
		}
		foreach ( explode( ' ', $attrValue ) as $ty ) {
			if ( $ty === '' ) {
				continue;
			}
			$count = preg_match( $valueRe, $ty );
			Assert::invariant( $count !== false, "Bad regexp" );
			if ( $count ) {
				return $ty;
			}
		}
		return null;
	}

	/**
	 * Determine whether the node matches the given typeof attribute value.
	 *
	 * @param Node $n
	 * @param string $type Expected value of "typeof" attribute, as a literal
	 *   string.
	 * @return bool True if the node matches.
	 */
	public static function hasTypeOf( Node $n, string $type ): bool {
		return self::hasValueInMultivalAttr( $n, 'typeof', $type );
	}

	/**
	 * Determine whether the node matches the given rel attribute value.
	 *
	 * @param Node $n
	 * @param string $rel Expected value of "rel" attribute, as a literal string.
	 * @return bool True if the node matches.
	 */
	public static function hasRel( Node $n, string $rel ): bool {
		return self::hasValueInMultivalAttr( $n, 'rel', $rel );
	}

	/**
	 * @param Element $element
	 * @param string $regex Partial regular expression, e.g. "foo|bar"
	 * @return bool
	 */
	public static function hasClass( Element $element, string $regex ): bool {
		$value = DOMCompat::getAttribute( $element, 'class' );
		return (bool)preg_match( '{(?<=^|\s)' . $regex . '(?=\s|$)}', $value ?? '' );
	}

	/**
	 * Determine whether the node matches the given attribute value for a multivalued attribute
	 * @param Node $n
	 * @param string $attrName name of the attribute to check (typically 'typeof', 'rel')
	 * @param string $value Expected value of $attrName" attribute, as a literal string.
	 * @return bool True if the node matches
	 */
	private static function hasValueInMultivalAttr( Node $n, string $attrName, string $value ): bool {
		// fast path
		if ( !( $n instanceof Element ) ) {
			return false;
		}
		$attrValue = DOMCompat::getAttribute( $n, $attrName );
		if ( $attrValue === null || $attrValue === '' ) {
			return false;
		}
		if ( $attrValue === $value ) {
			return true;
		}
		// fallback
		return in_array( $value, explode( ' ', $attrValue ), true );
	}

	/**
	 * Add a type to the typeof attribute.  This method should almost always
	 * be used instead of `setAttribute`, to ensure we don't overwrite existing
	 * typeof information.
	 *
	 * @param Element $node node
	 * @param string $type type
	 * @param bool $prepend If true, adds value to start, rather than end.
	 *    Use of this option in new code is discouraged.
	 */
	public static function addTypeOf( Element $node, string $type, bool $prepend = false ): void {
		self::addValueToMultivalAttr( $node, 'typeof', $type, $prepend );
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
		self::addValueToMultivalAttr( $node, 'rel', $rel );
	}

	/**
	 * Add an element to a multivalue attribute (typeof, rel).  This method should almost always
	 * be used instead of `setAttribute`, to ensure we don't overwrite existing
	 * multivalue information.
	 *
	 * @param Element $node
	 * @param string $attr
	 * @param string $value
	 * @param bool $prepend If true, adds value to start, rather than end
	 */
	private static function addValueToMultivalAttr(
		Element $node, string $attr, string $value, bool $prepend = false
	): void {
		$value = trim( $value );
		if ( $value === '' ) {
			return;
		}
		$oldValue = DOMCompat::getAttribute( $node, $attr );
		if ( $oldValue !== null && trim( $oldValue ) !== '' ) {
			$values = explode( ' ', trim( $oldValue ) );
			if ( in_array( $value, $values, true ) ) {
				return;
			}
			$value = $prepend ? "$value $oldValue" : "$oldValue $value";
		}
		$node->setAttribute( $attr, $value );
	}

	/**
	 * Remove a value from a multiple-valued attribute.
	 *
	 * @param Element $node node
	 * @param string $attr The attribute name
	 * @param string $value The value to remove
	 */
	private static function removeValueFromMultivalAttr(
		Element $node, string $attr, string $value
	): void {
		$oldValue = DOMCompat::getAttribute( $node, $attr );
		if ( $oldValue !== null && $oldValue !== '' ) {
			$value = trim( $value );
			$types = array_diff( explode( ' ', $oldValue ), [ $value ] );
			if ( count( $types ) > 0 ) {
				$node->setAttribute( $attr, implode( ' ', $types ) );
			} else {
				$node->removeAttribute( $attr );
			}
		}
	}

	/**
	 * Remove a type from the typeof attribute.
	 *
	 * @param Element $node node
	 * @param string $type type
	 */
	public static function removeTypeOf( Element $node, string $type ): void {
		self::removeValueFromMultivalAttr( $node, 'typeof', $type );
	}

	/**
	 * Remove a type from the rel attribute.
	 *
	 * @param Element $node node
	 * @param string $rel rel
	 */
	public static function removeRel( Element $node, string $rel ): void {
		self::removeValueFromMultivalAttr( $node, 'rel', $rel );
	}

	/**
	 * Check whether `node` is in a fosterable position.
	 *
	 * @param ?Node $n
	 * @return bool
	 */
	public static function isFosterablePosition( ?Node $n ): bool {
		return $n && isset( Consts::$HTML['FosterablePosition'][DOMCompat::nodeName( $n->parentNode )] );
	}

	/**
	 * Check whether `node` is a heading.
	 *
	 * @param ?Node $n
	 * @return bool
	 */
	public static function isHeading( ?Node $n ): bool {
		return $n && preg_match( '/^h[1-6]$/D', DOMCompat::nodeName( $n ) );
	}

	/**
	 * Check whether `node` is a list.
	 *
	 * @param ?Node $n
	 * @return bool
	 */
	public static function isList( ?Node $n ): bool {
		return $n && isset( Consts::$HTML['ListTags'][DOMCompat::nodeName( $n )] );
	}

	/**
	 * Check whether `node` is a list item.
	 *
	 * @param ?Node $n
	 * @return bool
	 */
	public static function isListItem( ?Node $n ): bool {
		return $n && isset( Consts::$HTML['ListItemTags'][DOMCompat::nodeName( $n )] );
	}

	/**
	 * Check whether `node` is a list or list item.
	 *
	 * @param ?Node $n
	 * @return bool
	 */
	public static function isListOrListItem( ?Node $n ): bool {
		return self::isList( $n ) || self::isListItem( $n );
	}

	/**
	 * Check whether `node` is nestee in a list item.
	 *
	 * @param ?Node $n
	 * @return bool
	 */
	public static function isNestedInListItem( ?Node $n ): bool {
		$parentNode = $n->parentNode;
		while ( $parentNode ) {
			if ( self::isListItem( $parentNode ) ) {
				return true;
			}
			$parentNode = $parentNode->parentNode;
		}
		return false;
	}

	/**
	 * Check whether `node` is a nested list or a list item.
	 *
	 * @param ?Node $n
	 * @return bool
	 */
	public static function isNestedListOrListItem( ?Node $n ): bool {
		return self::isListOrListItem( $n ) && self::isNestedInListItem( $n );
	}

	/**
	 * Check a node to see whether it's a meta with some typeof.
	 *
	 * @param Node $n
	 * @param string $type
	 * @return bool
	 */
	public static function isMarkerMeta( Node $n, string $type ): bool {
		return self::hasNameAndTypeOf( $n, 'meta', $type );
	}

	/**
	 * Check whether a node has any children that are elements.
	 *
	 * @param Node $node
	 * @return bool
	 */
	public static function hasElementChild( Node $node ): bool {
		for ( $child = $node->firstChild; $child; $child = $child->nextSibling ) {
			if ( $child instanceof Element ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if a node has a block-level element descendant.
	 *
	 * @param Node $node
	 * @return bool
	 */
	public static function hasBlockElementDescendant( Node $node ): bool {
		for ( $child = $node->firstChild; $child; $child = $child->nextSibling ) {
			if ( $child instanceof Element &&
				( self::isWikitextBlockNode( $child ) || // Is a block-level node
				self::hasBlockElementDescendant( $child ) ) // or has a block-level child or grandchild or..
			) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Is a node representing inter-element whitespace?
	 *
	 * @param ?Node $node
	 * @return bool
	 */
	public static function isIEW( ?Node $node ): bool {
		// ws-only
		return $node instanceof Text && preg_match( '/^\s*$/D', $node->nodeValue );
	}

	/**
	 * Is a node a document fragment?
	 *
	 * @param ?Node $node
	 * @return bool
	 */
	public static function isDocumentFragment( ?Node $node ): bool {
		return $node && $node->nodeType === XML_DOCUMENT_FRAG_NODE;
	}

	/**
	 * Is a node at the top?
	 *
	 * @param ?Node $node
	 * @return bool
	 */
	public static function atTheTop( ?Node $node ): bool {
		return self::isDocumentFragment( $node ) || self::isBody( $node );
	}

	/**
	 * Are all children of this node text or comment nodes?
	 *
	 * @param Node $node
	 * @return bool
	 */
	public static function allChildrenAreTextOrComments( Node $node ): bool {
		$child = $node->firstChild;
		while ( $child ) {
			if ( !( $child instanceof Text || $child instanceof Comment ) ) {
				return false;
			}
			$child = $child->nextSibling;
		}
		return true;
	}

	/**
	 * Check if the dom-subtree rooted at node has an element with tag name 'tagName'
	 * By default, the root node is not checked.
	 *
	 * @param Node $node The DOM node whose tree should be checked
	 * @param string $tagName Tag name to look for
	 * @param bool $checkRoot Should the root be checked?
	 * @return bool
	 */
	public static function treeHasElement( Node $node, string $tagName, bool $checkRoot = false ): bool {
		if ( $checkRoot && DOMCompat::nodeName( $node ) === $tagName ) {
			return true;
		}

		$node = $node->firstChild;
		while ( $node ) {
			if ( $node instanceof Element ) {
				if ( self::treeHasElement( $node, $tagName, true ) ) {
					return true;
				}
			}
			$node = $node->nextSibling;
		}
		return false;
	}

	/**
	 * Is node a table tag (table, tbody, td, tr, etc.)?
	 *
	 * @param Node $node
	 * @return bool
	 */
	public static function isTableTag( Node $node ): bool {
		return isset( Consts::$HTML['TableTags'][DOMCompat::nodeName( $node )] );
	}

	/**
	 * Returns a media element nested in `node`
	 *
	 * @param Element $node
	 * @return Element|null
	 */
	public static function selectMediaElt( Element $node ): ?Element {
		return DOMCompat::querySelector( $node, 'img, video, audio' );
	}

	/**
	 * Extract http-equiv headers from the HTML, including content-language and
	 * vary headers, if present
	 *
	 * @param Document $doc
	 * @return array<string,string>
	 */
	public static function findHttpEquivHeaders( Document $doc ): array {
		$elts = DOMCompat::querySelectorAll( $doc, 'meta[http-equiv][content]' );
		$r = [];
		foreach ( $elts as $el ) {
			$r[strtolower(
				DOMCompat::getAttribute( $el, 'http-equiv' )
			)] = DOMCompat::getAttribute( $el, 'content' );
		}
		return $r;
	}

	/**
	 * Add or replace http-equiv headers in the HTML <head>.
	 * This is used for content-language and vary headers, among possible
	 * others.
	 * @param Document $doc The HTML document to update
	 * @param array<string,string|string[]> $headers An array mapping HTTP
	 *   header names (which are case-insensitive) to new values.  If an
	 *   array of values is provided, they will be joined with commas.
	 */
	public static function addHttpEquivHeaders( Document $doc, array $headers ): void {
		foreach ( $headers as $key => $value ) {
			if ( is_array( $value ) ) {
				$value = implode( ',', $value );
			}
			// HTTP header names are case-insensitive; hence the "i" suffix
			// on this selector query.
			$el = DOMCompat::querySelector( $doc, "meta[http-equiv=\"{$key}\"i]" );
			if ( !$el ) {
				// This also ensures there is a <head> element.
				$el = self::appendToHead( $doc, 'meta', [ 'http-equiv' => $key ] );
			}
			$el->setAttribute( 'content', $value );

		}
	}

	/**
	 * @param Document $doc
	 * @return string|null
	 */
	public static function extractInlinedContentVersion( Document $doc ): ?string {
		$el = DOMCompat::querySelector( $doc,
			'meta[property="mw:htmlVersion"], meta[property="mw:html:version"]' );
		return $el ? DOMCompat::getAttribute( $el, 'content' ) : null;
	}

	/**
	 * Add attributes to a node element.
	 *
	 * @param Element $elt element
	 * @param array $attrs attributes
	 */
	public static function addAttributes( Element $elt, array $attrs ): void {
		foreach ( $attrs as $key => $value ) {
			if ( $value !== null ) {
				if ( $key === 'id' ) {
					DOMCompat::setIdAttribute( $elt, $value );
				} else {
					$elt->setAttribute( $key, $value );
				}
			}
		}
	}

	/**
	 * Create an element in the document head with the given attrs.
	 * Creates the head element in the document if needed.
	 *
	 * @param Document $document
	 * @param string $tagName
	 * @param array $attrs
	 * @return Element The newly-appended Element
	 */
	public static function appendToHead( Document $document, string $tagName, array $attrs = [] ): Element {
		$elt = $document->createElement( $tagName );
		self::addAttributes( $elt, $attrs );
		$head = DOMCompat::getHead( $document );
		if ( !$head ) {
			$head = $document->createElement( 'head' );
			$document->documentElement->insertBefore(
				$head, DOMCompat::getBody( $document )
			);
		}
		$head->appendChild( $elt );
		return $elt;
	}

	/**
	 * innerHTML and outerHTML are not defined on DocumentFragment.
	 *
	 * Defined similarly to DOMCompat::getInnerHTML()
	 *
	 * @param DocumentFragment $frag
	 * @return string
	 */
	public static function getFragmentInnerHTML(
		DocumentFragment $frag
	): string {
		return XMLSerializer::serialize(
			$frag, [ 'innerXML' => true ]
		)['html'];
	}

	/**
	 * innerHTML and outerHTML are not defined on DocumentFragment.
	 * @see DOMCompat::setInnerHTML() for the Element version
	 *
	 * @param DocumentFragment $frag
	 * @param string $html
	 */
	public static function setFragmentInnerHTML(
		DocumentFragment $frag, string $html
	): void {
		// FIXME: This should be an HTML5 template element
		$body = $frag->ownerDocument->createElement( 'body' );
		DOMCompat::setInnerHTML( $body, $html );
		self::migrateChildren( $body, $frag );
	}

	/**
	 * @param Document $doc
	 * @param string $html
	 * @return DocumentFragment
	 */
	public static function parseHTMLToFragment(
		Document $doc, string $html
	): DocumentFragment {
		$frag = $doc->createDocumentFragment();
		self::setFragmentInnerHTML( $frag, $html );
		return $frag;
	}

	/**
	 * @param Node $node
	 * @return bool
	 */
	public static function isRawTextElement( Node $node ): bool {
		return isset( Consts::$HTML['RawTextElements'][DOMCompat::nodeName( $node )] );
	}

	/**
	 * Is 'n' a block tag, or does the subtree rooted at 'n' have a block tag
	 * in it?
	 *
	 * @param Node $n
	 * @return bool
	 */
	public static function hasBlockTag( Node $n ): bool {
		if ( self::isRemexBlockNode( $n ) ) {
			return true;
		}
		$c = $n->firstChild;
		while ( $c ) {
			if ( self::hasBlockTag( $c ) ) {
				return true;
			}
			$c = $c->nextSibling;
		}
		return false;
	}

	/**
	 * Get an associative array of attributes, suitable for serialization.
	 *
	 * Add the xmlns attribute if available, to workaround PHP's surprising
	 * behavior with the xmlns attribute: HTML is *not* an XML document,
	 * but various parts of PHP (including our misnamed XMLSerializer) pretend
	 * that it is, sort of.
	 *
	 * @param Element $element
	 * @return array<string,string>
	 * @see https://phabricator.wikimedia.org/T235295
	 */
	public static function attributes( Element $element ): array {
		$result = [];
		// The 'xmlns' attribute is "invisible" T235295
		$xmlns = DOMCompat::getAttribute( $element, 'xmlns' );
		if ( $xmlns !== null ) {
			$result['xmlns'] = $xmlns;
		}
		foreach ( $element->attributes as $attr ) {
			$result[$attr->name] = $attr->value;
		}
		return $result;
	}

	/**
	 * @param Element $node
	 * @return bool
	 */
	public static function isMetaDataTag( Element $node ): bool {
		return isset( Consts::$HTML['MetaDataTags'][DOMCompat::nodeName( $node )] );
	}

	/**
	 * Strip a paragraph wrapper, if any, before parsing HTML to DOM
	 */
	public static function stripPWrapper( string $ret ): string {
		return preg_replace( '#(^<p>)|(\n</p>(' . Utils::COMMENT_REGEXP_FRAGMENT . '|\s)*$)#D', '', $ret );
	}
}
