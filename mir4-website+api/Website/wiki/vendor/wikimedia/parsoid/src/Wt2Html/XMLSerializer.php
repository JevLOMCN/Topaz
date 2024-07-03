<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html;

use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\DOM\Comment;
use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\WTUtils;
use Wikimedia\Parsoid\Wikitext\Consts;

/**
 * Stand-alone XMLSerializer for DOM3 documents.
 *
 * The output is identical to standard XHTML5 DOM serialization, as given by
 * http://www.w3.org/TR/html-polyglot/
 * and
 * https://html.spec.whatwg.org/multipage/syntax.html#serialising-html-fragments
 * except that we may quote attributes with single quotes, *only* where that would
 * result in more compact output than the standard double-quoted serialization.
 */
class XMLSerializer {

	// https://html.spec.whatwg.org/#serialising-html-fragments
	private static $alsoSerializeAsVoid = [
		'basefont' => true,
		'bgsound' => true,
		'frame' => true,
		'keygen' => true
	];

	/**
	 * Elements that strip leading newlines
	 * http://www.whatwg.org/specs/web-apps/current-work/multipage/the-end.html#html-fragment-serialization-algorithm
	 */
	private const NEWLINE_STRIPPING_ELEMENTS = [
		'pre' => true,
		'textarea' => true,
		'listing' => true
	];

	private const ENTITY_ENCODINGS = [
		'single' => [ '<' => '&lt;', '&' => '&amp;', "'" => '&apos;' ],
		'double' => [ '<' => '&lt;', '&' => '&amp;', '"' => '&quot;' ],
		'xml' => [ '<' => '&lt;', '&' => '&amp;' ],
	];

	/**
	 * HTML entity encoder helper.
	 * Only supports the few entities we'll actually need: <&'"
	 * @param string $raw Input string
	 * @param string $encodeChars Set of characters to encode, "single", "double", or "xml"
	 * @return string
	 */
	private static function encodeHtmlEntities( string $raw, string $encodeChars ): string {
		return strtr( $raw, self::ENTITY_ENCODINGS[$encodeChars] );
	}

	/**
	 * Modify the attribute array, replacing data-object-id with JSON
	 * encoded data.  This is just a debugging hack, not to be confused with
	 * DOMDataUtils::storeDataAttribs()
	 *
	 * @param Element $node
	 * @param array &$attrs
	 * @param bool $keepTmp
	 * @param bool $storeDiffMark
	 */
	private static function dumpDataAttribs(
		Element $node, array &$attrs, bool $keepTmp, bool $storeDiffMark
	) {
		if ( !isset( $attrs[DOMDataUtils::DATA_OBJECT_ATTR_NAME] ) ) {
			return;
		}
		$nd = DOMDataUtils::getNodeData( $node );
		$pd = $nd->parsoid_diff ?? null;
		if ( $pd && $storeDiffMark ) {
			$attrs['data-parsoid-diff'] = PHPUtils::jsonEncode( $pd );
		}
		$dp = $nd->parsoid;
		if ( $dp ) {
			if ( !$keepTmp ) {
				$dp = clone $dp;
				// @phan-suppress-next-line PhanTypeObjectUnsetDeclaredProperty
				unset( $dp->tmp );
			}
			$attrs['data-parsoid'] = PHPUtils::jsonEncode( $dp );
		}
		$dmw = $nd->mw;
		if ( $dmw ) {
			$attrs['data-mw'] = PHPUtils::jsonEncode( $dmw );
		}
		unset( $attrs[DOMDataUtils::DATA_OBJECT_ATTR_NAME] );
	}

	/**
	 * Serialize an HTML DOM3 node to XHTML. The XHTML and associated information will be fed
	 * step-by-step to the callback given in $accum.
	 * @param Node $node
	 * @param array $options See {@link XMLSerializer::serialize()}
	 * @param callable $accum function( $bit, $node, $flag )
	 *   - $bit: (string) piece of HTML code
	 *   - $node: (Node) ??
	 *   - $flag: (string|null) 'start' or 'end' (??)
	 */
	private static function serializeToString( Node $node, array $options, callable $accum ): void {
		$smartQuote = $options['smartQuote'];
		$saveData = $options['saveData'];
		switch ( $node->nodeType ) {
			case XML_ELEMENT_NODE:
				DOMUtils::assertElt( $node );
				$child = $node->firstChild;
				$nodeName = DOMCompat::nodeName( $node );
				$localName = $node->localName;
				$accum( '<' . $localName, $node );
				$attrs = DOMUtils::attributes( $node );
				if ( $saveData ) {
					self::dumpDataAttribs( $node, $attrs, $options['keepTmp'], $options['storeDiffMark'] );
				}
				foreach ( $attrs as $an => $av ) {
					if ( $smartQuote
						// More double quotes than single quotes in value?
						&& substr_count( $av, '"' ) > substr_count( $av, "'" )
					) {
						// use single quotes
						$accum( ' ' . $an . "='"
							. self::encodeHtmlEntities( $av, 'single' ) . "'",
							$node );
					} else {
						// use double quotes
						$accum( ' ' . $an . '="'
							. self::encodeHtmlEntities( $av, 'double' ) . '"',
							$node );
					}
				}
				if ( $child || (
					!isset( Consts::$HTML['VoidTags'][$nodeName] ) &&
					!isset( self::$alsoSerializeAsVoid[$nodeName] )
				) ) {
					$accum( '>', $node, 'start' );
					// if is cdata child node
					if ( DOMUtils::isRawTextElement( $node ) ) {
						// TODO: perform context-sensitive escaping?
						// Currently this content is not normally part of our DOM, so
						// no problem. If it was, we'd probably have to do some
						// tag-specific escaping. Examples:
						// * < to \u003c in <script>
						// * < to \3c in <style>
						// ...
						if ( $child ) {
							$accum( $child->nodeValue, $node );
						}
					} else {
						if ( $child && isset( self::NEWLINE_STRIPPING_ELEMENTS[$localName] )
							&& $child->nodeType === XML_TEXT_NODE && str_starts_with( $child->nodeValue, "\n" )
						) {
							/* If current node is a pre, textarea, or listing element,
							 * and the first child node of the element, if any, is a
							 * Text node whose character data has as its first
							 * character a U+000A LINE FEED (LF) character, then
							 * append a U+000A LINE FEED (LF) character. */
							$accum( "\n", $node );
						}
						while ( $child ) {
							self::serializeToString( $child, $options, $accum );
							$child = $child->nextSibling;
						}
					}
					$accum( '</' . $localName . '>', $node, 'end' );
				} else {
					$accum( '/>', $node, 'end' );
				}
				return;

			case XML_DOCUMENT_NODE:
			case XML_DOCUMENT_FRAG_NODE:
				'@phan-var Document|DocumentFragment $node';
				// @var Document|DocumentFragment $node
				$child = $node->firstChild;
				while ( $child ) {
					self::serializeToString( $child, $options, $accum );
					$child = $child->nextSibling;
				}
				return;

			case XML_TEXT_NODE:
				'@phan-var Text $node'; // @var Text $node
				$accum( self::encodeHtmlEntities( $node->nodeValue, 'xml' ), $node );
				return;

			case XML_COMMENT_NODE:
				// According to
				// http://www.w3.org/TR/DOM-Parsing/#dfn-concept-serialize-xml
				// we could throw an exception here if node.data would not create
				// a "well-formed" XML comment.  But we use entity encoding when
				// we create the comment node to ensure that node.data will always
				// be okay; see DOMUtils.encodeComment().
				'@phan-var Comment $node'; // @var Comment $node
				$accum( '<!--' . $node->nodeValue . '-->', $node );
				return;

			default:
				$accum( '??' . DOMCompat::nodeName( $node ), $node );
		}
	}

	/**
	 * Add data to an output/memory array (used when serialize() was called with the
	 * captureOffsets flag).
	 * @param array &$out Output array, see {@link self::serialize()} for details on the
	 *   'html' and 'offset' fields. The other fields (positions are 0-based
	 *   and refer to UTF-8 byte indices):
	 *   - start: position in the HTML of the end of the opening tag of <body>
	 *   - last: (Node) last "about sibling" of the currently processed element
	 *     (see {@link WTUtils::getAboutSiblings()}
	 *   - uid: the ID of the element
	 * @param string $bit A piece of the HTML string
	 * @param Node $node The DOM node $bit is a part of
	 * @param ?string $flag 'start' when receiving the final part of the opening tag
	 *   of an element, 'end' when receiving the final part of the closing tag of an element
	 *   or the final part of a self-closing element.
	 */
	private static function accumOffsets(
		array &$out, string $bit, Node $node, ?string $flag = null
	): void {
		if ( DOMUtils::atTheTop( $node ) ) {
			$out['html'] .= $bit;
			if ( $flag === 'start' ) {
				$out['start'] = strlen( $out['html'] );
			} elseif ( $flag === 'end' ) {
				$out['start'] = null;
				$out['uid'] = null;
			}
		} elseif (
			!( $node instanceof Element ) || $out['start'] === null ||
			!DOMUtils::atTheTop( $node->parentNode )
		) {
			// In case you're wondering, out.start may never be set if body
			// isn't a child of the node passed to serializeToString, or if it
			// is the node itself but options.innerXML is true.
			$out['html'] .= $bit;
			if ( $out['uid'] !== null ) {
				$out['offsets'][$out['uid']]['html'][1] += strlen( $bit );
			}
		} else {
			$newUid = DOMCompat::getAttribute( $node, 'id' );
			// Encapsulated siblings don't have generated ids (but may have an id),
			// so associate them with preceding content.
			if ( $newUid && $newUid !== $out['uid'] && !$out['last'] ) {
				if ( !WTUtils::isEncapsulationWrapper( $node ) ) {
					$out['uid'] = $newUid;
				} elseif ( WTUtils::isFirstEncapsulationWrapperNode( $node ) ) {
					$about = DOMCompat::getAttribute( $node, 'about' );
					$aboutSiblings = WTUtils::getAboutSiblings( $node, $about );
					$out['last'] = end( $aboutSiblings );
					$out['uid'] = $newUid;
				}
			}
			if ( $out['last'] === $node && $flag === 'end' ) {
				$out['last'] = null;
			}
			Assert::invariant( $out['uid'] !== null, 'uid cannot be null' );
			if ( !isset( $out['offsets'][$out['uid']] ) ) {
				$dt = strlen( $out['html'] ) - $out['start'];
				$out['offsets'][$out['uid']] = [ 'html' => [ $dt, $dt ] ];
			}
			$out['html'] .= $bit;
			$out['offsets'][$out['uid']]['html'][1] += strlen( $bit );
		}
	}

	/**
	 * Serialize an HTML DOM3 node to an XHTML string.
	 *
	 * @param Node $node
	 * @param array $options
	 *   - smartQuote (bool, default true): use single quotes for attributes when that's less escaping
	 *   - innerXML (bool, default false): only serialize the contents of $node, exclude $node itself
	 *   - captureOffsets (bool, default false): return tag position data (see below)
	 *   - addDoctype (bool, default true): prepend a DOCTYPE when a full HTML document is serialized
	 *   - saveData (bool, default false): Copy the NodeData into JSON attributes. This is for
	 *     debugging purposes only, the normal code path is to use DOMDataUtils::storeDataAttribs().
	 *   - keepTmp (bool, default false): When saving data, include DataParsoid::$tmp.
	 * @return array An array with the following data:
	 *   - html: the serialized HTML
	 *   - offsets: the start and end position of each element in the HTML, in a
	 *     [ $uid => [ 'html' => [ $start, $end ] ], ... ] format where $uid is the element's
	 *     Parsoid ID, $start is the 0-based index of the first character of the element and
	 *     $end is the index of the first character of the opening tag of the next sibling element,
	 *     or the index of the last character of the element's closing tag if there is no next
	 *     sibling. The positions are relative to the end of the opening <body> tag
	 *     (the DOCTYPE header is not counted), and only present when the captureOffsets flag is set.
	 */
	public static function serialize( Node $node, array $options = [] ): array {
		$options += [
			'smartQuote' => true,
			'innerXML' => false,
			'captureOffsets' => false,
			'addDoctype' => true,
			'saveData' => false,
			'keepTmp' => false,
			'storeDiffMark' => false,
		];
		if ( $node instanceof Document ) {
			$node = $node->documentElement;
		}
		$out = [ 'html' => '', 'offsets' => [], 'start' => null, 'uid' => null, 'last' => null ];
		$accum = $options['captureOffsets']
			? function ( string $bit, Node $node, ?string $flag = null ) use ( &$out ): void {
				self::accumOffsets( $out, $bit, $node, $flag );
			}
			: static function ( string $bit ) use ( &$out ): void {
				$out['html'] .= $bit;
			};

		if ( $options['innerXML'] ) {
			for ( $child = $node->firstChild; $child; $child = $child->nextSibling ) {
				self::serializeToString( $child, $options, $accum );
			}
		} else {
			self::serializeToString( $node, $options, $accum );
		}
		// Ensure there's a doctype for documents.
		if ( !$options['innerXML'] && DOMCompat::nodeName( $node ) === 'html' && $options['addDoctype'] ) {
			$out['html'] = "<!DOCTYPE html>\n" . $out['html'];
		}
		// Verify UTF-8 soundness (transitional check for PHP port)
		PHPUtils::assertValidUTF8( $out['html'] );
		// Drop the bookkeeping
		unset( $out['start'], $out['uid'], $out['last'] );
		if ( !$options['captureOffsets'] ) {
			unset( $out['offsets'] );
		}
		return $out;
	}

}
