<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\ParserTests;

use Error;
use Exception;
use Wikimedia\Parsoid\DOM\Comment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\Html2Wt\DOMNormalizer;
use Wikimedia\Parsoid\Html2Wt\SerializerState;
use Wikimedia\Parsoid\Html2Wt\WikitextSerializer;
use Wikimedia\Parsoid\Mocks\MockEnv;
use Wikimedia\Parsoid\Utils\ContentUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\Utils;
use Wikimedia\Parsoid\Utils\WTUtils;

class TestUtils {
	/** @var mixed */
	private static $consoleColor;

	/**
	 * Little helper function for encoding XML entities.
	 *
	 * @param string $str
	 * @return string
	 */
	public static function encodeXml( string $str ): string {
		// PORT-FIXME: Find replacement
		// return entities::encodeXML( $str );
		return $str;
	}

	/**
	 * Strip the actual about id from the string
	 * @param string $str
	 * @return string
	 */
	public static function normalizeAbout( string $str ): string {
		return preg_replace( "/(about=\\\\?[\"']#mwt)\d+/", '$1', $str );
	}

	/**
	 * Specialized normalization of the PHP parser & Parsoid output, to ignore
	 * a few known-ok differences in parser test runs.
	 *
	 * This code is also used by the Parsoid round-trip testing code.
	 *
	 * If parsoidOnly is true-ish, we allow more markup through (like property
	 * and typeof attributes), for better checking of parsoid-only test cases.
	 *
	 * @param Element|string $domBody
	 * @param array $options
	 *  - parsoidOnly (bool) Is this test Parsoid Only? Optional. Default: false
	 *  - preserveIEW (bool) Should inter-element WS be preserved? Optional. Default: false
	 *  - hackyNormalize (bool) Apply the normalizer to the html. Optional. Default: false
	 * @return string
	 */
	public static function normalizeOut( $domBody, array $options = [] ): string {
		$parsoidOnly = !empty( $options['parsoidOnly'] );
		$preserveIEW = !empty( $options['preserveIEW'] );

		if ( !empty( $options['hackyNormalize'] ) ) {
			// Mock env obj
			//
			// FIXME: This is ugly.
			// (a) The normalizer shouldn't need the full env.
			//     Pass options and a logger instead?
			// (b) DOM diff code is using page-id for some reason.
			//     That feels like a carryover of 2013 era code.
			//     If possible, get rid of it and diff-mark dependency
			//     on the env object.
			$mockEnv = new MockEnv( [] );
			$mockSerializer = new WikitextSerializer( $mockEnv, [] );
			$mockState = new SerializerState( $mockSerializer, [ 'selserMode' => false ] );
			if ( is_string( $domBody ) ) {
				// Careful about the lifetime of this document
				$doc = ContentUtils::createDocument( $domBody );
				$domBody = DOMCompat::getBody( $doc );
			}
			DOMDataUtils::visitAndLoadDataAttribs( $domBody, [ 'markNew' => true ] );
			( new DOMNormalizer( $mockState ) )->normalize( $domBody );
			DOMDataUtils::visitAndStoreDataAttribs( $domBody );
		} elseif ( is_string( $domBody ) ) {
			$domBody = DOMCompat::getBody( DOMUtils::parseHTML( $domBody ) );
		}

		$stripTypeof = $parsoidOnly ?
			'/^mw:Placeholder$/' :
			'/^mw:(?:DisplaySpace|Placeholder|Nowiki|Transclusion|Entity)$/';
		$domBody = self::unwrapSpansAndNormalizeIEW( $domBody, $stripTypeof, $parsoidOnly, $preserveIEW );
		$out = ContentUtils::toXML( $domBody, [ 'innerXML' => true ] );
		// NOTE that we use a slightly restricted regexp for "attribute"
		//  which works for the output of DOM serialization.  For example,
		//  we know that attribute values will be surrounded with double quotes,
		//  not unquoted or quoted with single quotes.  The serialization
		//  algorithm is given by:
		//  http://www.whatwg.org/specs/web-apps/current-work/multipage/the-end.html#serializing-html-fragments
		if ( !preg_match( '#[^<]*(<\w+(\s+[^\0-\cZ\s"\'>/=]+(="[^"]*")?)*/?>[^<]*)*#u', $out ) ) {
			throw new Error( 'normalizeOut input is not in standard serialized form' );
		}

		// Eliminate a source of indeterminacy from leaked strip markers
		$out = preg_replace( '/UNIQ-.*?-QINU/u', '', $out );

		// Normalize COINS ids -- they aren't stable
		$out = preg_replace( '/\s?id=[\'"]coins_\d+[\'"]/iu', '', $out );

		// maplink extension
		$out = preg_replace( '/\s?data-overlays=\'[^\']*\'/u', '', $out );

		// unnecessary attributes, we don't need to check these.
		$unnecessaryAttribs = 'data-parsoid|prefix|about|rev|datatype|inlist|usemap|vocab';
		if ( $parsoidOnly ) {
			$unnecessaryAttribs = "/ ($unnecessaryAttribs)=";
			$out = preg_replace( $unnecessaryAttribs . '\\\\?"[^\"]*\\\\?"/u', '', $out );
			$out = preg_replace( $unnecessaryAttribs . "\\\\?'[^\']*\\\\?'/u", '', $out ); // single-quoted variant
			$out = preg_replace( $unnecessaryAttribs . '&apos;.*?&apos;/u', '', $out ); // apos variant
			if ( !$options['externallinktarget'] ) {
				$out = preg_replace( '/ nofollow/', '', $out );
				$out = str_replace( ' rel="nofollow"', '', $out );
				$out = preg_replace( '/ noreferrer noopener/', '', $out );
			}

			// strip self-closed <nowiki /> because we frequently test WTS
			// <nowiki> insertion by providing an html/parsoid section with the
			// <meta> tags stripped out, allowing the html2wt test to verify that
			// the <nowiki> is correctly added during WTS, while still allowing
			// the html2html and wt2html versions of the test to pass as a
			// validity check.  If <meta>s were not stripped, these tests would all
			// have to be modified and split up.  Not worth it at this time.
			// (see commit 689b22431ad690302420d049b10e689de6b7d426)
			$out = preg_replace( '#<span typeof="mw:Nowiki"></span>#', '', $out );

			return $out;
		}

		// Normalize headings by stripping out Parsoid-added ids so that we don't
		// have to add these ids to every parser test that uses headings.
		// We will test the id generation scheme separately via mocha tests.
		$out = preg_replace( '/(<h[1-6].*?) id="[^\"]*"([^>]*>)/u', '$1$2', $out );
		// strip meta/link elements
		$out = preg_replace(
			'#</?(?:meta|link)(?: [^\0-\cZ\s"\'>/=]+(?:=(?:"[^"]*"|\'[^\']*\'))?)*/?>#u',
			'', $out );
		// Ignore troublesome attributes.
		// In addition to attributes listed above, strip other Parsoid-inserted attributes
		// since these won't be present in legacay parser output.
		$attribTroubleRE = "/ ($unnecessaryAttribs|data-mw|resource|rel|property|class)=\\\\?";
		$out = preg_replace( $attribTroubleRE . '"[^"]*\\\\?"/u', '', $out );
		$out = preg_replace( $attribTroubleRE . "'[^']*\\\\?'/u", '', $out ); // single-quoted variant
		// strip typeof last
		$out = preg_replace( '/ typeof="[^\"]*"/u', '', $out );
		// replace mwt ids
		$out = preg_replace( '/ id="mw((t\d+)|([\w-]{2,}))"/u', '', $out );
		$out = preg_replace( '/<span[^>]+about="[^"]*"[^>]*>/u', '', $out );
		$out = preg_replace( '#(\s)<span>\s*</span>\s*#u', '$1', $out );
		$out = preg_replace( '#<span>\s*</span>#u', '', $out );
		$out = preg_replace( '#(href=")(?:\.?\./)+#u', '$1', $out );
		// replace unnecessary URL escaping
		$out = preg_replace_callback( '/ href="[^"]*"/u', static function ( $m ) {
			return Utils::decodeURI( $m[0] );
		}, $out );
		// strip thumbnail size prefixes
		return preg_replace(
			'#(src="[^"]*?)/thumb(/[0-9a-f]/[0-9a-f]{2}/[^/]+)/[0-9]+px-[^"/]+(?=")#u', '$1$2',
			$out
		);
	}

	private static function cleanSpans(
		Node $node, ?string $stripSpanTypeof
	): void {
		if ( !$stripSpanTypeof ) {
			return;
		}

		$child = null;
		$next = null;
		for ( $child = $node->firstChild; $child; $child = $next ) {
			$next = $child->nextSibling;
			if ( $child instanceof Element && DOMCompat::nodeName( $child ) === 'span' &&
				preg_match( $stripSpanTypeof, DOMCompat::getAttribute( $child, 'typeof' ) ?? '' )
			) {
				self::unwrapSpan( $node, $child, $stripSpanTypeof );
			}
		}
	}

	private static function unwrapSpan(
		Node $parent, Node $node, ?string $stripSpanTypeof
	): void {
		// first recurse to unwrap any spans in the immediate children.
		self::cleanSpans( $node, $stripSpanTypeof );
		// now unwrap this span.
		DOMUtils::migrateChildren( $node, $parent, $node );
		$parent->removeChild( $node );
	}

	private static function newlineAround( ?Node $node ): bool {
		return $node && preg_match(
			'/^(body|caption|div|dd|dt|li|p|table|tr|td|th|tbody|dl|ol|ul|h[1-6])$/D',
			DOMCompat::nodeName( $node )
		);
	}

	private static function normalizeIEWVisitor(
		Node $node, array $opts
	): Node {
		$child = null;
		$next = null;
		$prev = null;
		if ( DOMCompat::nodeName( $node ) === 'pre' ) {
			// Preserve newlines in <pre> tags
			$opts['inPRE'] = true;
		}
		if ( !$opts['preserveIEW'] && $node instanceof Text ) {
			if ( !$opts['inPRE'] ) {
				$node->data = preg_replace( '/\s+/u', ' ', $node->data );
			}
			if ( $opts['stripLeadingWS'] ) {
				$node->data = preg_replace( '/^\s+/u', '', $node->data, 1 );
			}
			if ( $opts['stripTrailingWS'] ) {
				$node->data = preg_replace( '/\s+$/uD', '', $node->data, 1 );
			}
		}
		// unwrap certain SPAN nodes
		self::cleanSpans( $node, $opts['stripSpanTypeof'] );
		// now remove comment nodes
		if ( !$opts['parsoidOnly'] ) {
			for ( $child = $node->firstChild;  $child;  $child = $next ) {
				$next = $child->nextSibling;
				if ( $child instanceof Comment ) {
					$node->removeChild( $child );
				}
			}
		}
		// reassemble text nodes split by a comment or span, if necessary
		if ( $node instanceof Element ) {
			DOMCompat::normalize( $node );
		}
		// now recurse.
		if ( DOMCompat::nodeName( $node ) === 'pre' ) {
			// hack, since PHP adds a newline before </pre>
			$opts['stripLeadingWS'] = false;
			$opts['stripTrailingWS'] = true;
		} elseif (
			DOMCompat::nodeName( $node ) === 'span' &&
			DOMUtils::matchTypeOf( $node, '/^mw:/' )
		) {
			// SPAN is transparent; pass the strip parameters down to kids
		} else {
			$opts['stripLeadingWS'] = $opts['stripTrailingWS'] = self::newlineAround( $node );
		}
		$child = $node->firstChild;
		// Skip over the empty mw:FallbackId <span> and strip leading WS
		// on the other side of it.
		if ( $child && DOMUtils::isHeading( $node ) && WTUtils::isFallbackIdSpan( $child ) ) {
			$child = $child->nextSibling;
		}
		for ( ; $child; $child = $next ) {
			$next = $child->nextSibling;
			$newOpts = $opts;
			$newOpts['stripTrailingWS'] = $opts['stripTrailingWS'] && !$child->nextSibling;
			self::normalizeIEWVisitor( $child, $newOpts );
			$opts['stripLeadingWS'] = false;
		}

		if ( $opts['inPRE'] || $opts['preserveIEW'] ) {
			return $node;
		}

		// now add newlines around appropriate nodes.
		for ( $child = $node->firstChild;  $child; $child = $next ) {
			$prev = $child->previousSibling;
			$next = $child->nextSibling;
			if ( self::newlineAround( $child ) ) {
				if ( $prev instanceof Text ) {
					$prev->data = preg_replace( '/\s*$/uD', "\n", $prev->data, 1 );
				} else {
					$prev = $node->ownerDocument->createTextNode( "\n" );
					$node->insertBefore( $prev, $child );
				}
				if ( $next instanceof Text ) {
					$next->data = preg_replace( '/^\s*/u', "\n", $next->data, 1 );
				} else {
					$next = $node->ownerDocument->createTextNode( "\n" );
					$node->insertBefore( $next, $child->nextSibling );
				}
			}
		}
		return $node;
	}

	/**
	 * Normalize newlines in IEW to spaces instead.
	 *
	 * @param Element $body The document body node to normalize.
	 * @param ?string $stripSpanTypeof Regular expression to strip typeof attributes
	 * @param bool $parsoidOnly
	 * @param bool $preserveIEW
	 * @return Element
	 */
	public static function unwrapSpansAndNormalizeIEW(
		Element $body, ?string $stripSpanTypeof = null, bool $parsoidOnly = false, bool $preserveIEW = false
	): Element {
		$opts = [
			'preserveIEW' => $preserveIEW,
			'parsoidOnly' => $parsoidOnly,
			'stripSpanTypeof' => $stripSpanTypeof,
			'stripLeadingWS' => true,
			'stripTrailingWS' => true,
			'inPRE' => false
		];
		// clone body first, since we're going to destructively mutate it.
		// @phan-suppress-next-line PhanTypeMismatchReturnSuperType
		return self::normalizeIEWVisitor( $body->cloneNode( true ), $opts );
	}

	/**
	 * Strip some php output we aren't generating.
	 *
	 * @param string $html
	 * @return string
	 */
	public static function normalizePhpOutput( string $html ): string {
		return preg_replace(
			// do not expect section editing for now
			'/<span[^>]+class="mw-headline"[^>]*>(.*?)<\/span> '
			. '*(<span class="mw-editsection"><span class="mw-editsection-bracket">'
			. '\[<\/span>.*?<span class="mw-editsection-bracket">\]<\/span><\/span>)?/u',
			'$1',
			$html
		);
	}

	/**
	 * Normalize the expected parser output by parsing it using a HTML5 parser and
	 * re-serializing it to HTML. Ideally, the parser would normalize inter-tag
	 * whitespace for us. For now, we fake that by simply stripping all newlines.
	 *
	 * @param string $source
	 * @return string
	 */
	public static function normalizeHTML( string $source ): string {
		try {
			$body = self::unwrapSpansAndNormalizeIEW( DOMCompat::getBody( DOMUtils::parseHTML( $source ) ) );
			$html = ContentUtils::toXML( $body, [ 'innerXML' => true ] );

			// a few things we ignore for now..
			//  .replace(/\/wiki\/Main_Page/g, 'Main Page')
			// do not expect a toc for now
			$html = preg_replace(
				'/<div[^>]+?id="toc"[^>]*>\s*<div id="toctitle"[^>]*>[\s\S]+?<\/div>[\s\S]+?<\/div>\s*/u',
				'',
				$html );
			$html = self::normalizePhpOutput( $html );
			// remove empty span tags
			$html = preg_replace( '/(\s)<span>\s*<\/span>\s*/u', '$1', $html );
			$html = preg_replace( '/<span>\s*<\/span>/u', '', $html );
			// general class and titles, typically on links
			$html = preg_replace( '/ (class|rel|about|typeof)="[^"]*"/', '', $html );
			// strip red link markup, we do not check if a page exists yet
			$html = preg_replace(
				"#/index.php\\?title=([^']+?)&amp;action=edit&amp;redlink=1#", '/wiki/$1', $html );
			// strip red link title info
			$html = preg_replace(
				"/ \\((?:page does not exist|encara no existeix|bet ele jaratılmaǵan|lonkásá  ezalí tɛ̂)\\)/",
				'', $html );
			// the expected html has some extra space in tags, strip it
			$html = preg_replace( '/<a +href/', '<a href', $html );
			$html = preg_replace( '#href="/wiki/#', 'href="', $html );
			$html = preg_replace( '/" +>/', '">', $html );
			// parsoid always add a page name to lonely fragments
			$html = preg_replace( '/href="#/', 'href="Main Page#', $html );
			// replace unnecessary URL escaping
			$html = preg_replace_callback( '/ href="[^"]*"/',
				static function ( $m ) {
					return Utils::decodeURI( $m[0] );
				},
				$html );
			// strip empty spans
			$html = preg_replace( '#(\s)<span>\s*</span>\s*#u', '$1', $html );
			return preg_replace( '#<span>\s*</span>#u', '', $html );
		} catch ( Exception $e ) {
			error_log( 'normalizeHTML failed on' . $source . ' with the following error: ' . $e );
			return $source;
		}
	}

	/**
	 * @param string $string
	 * @param string $color
	 * @param bool $inverse
	 * @return string
	 * @suppress PhanUndeclaredClassMethod
	 * @suppress UnusedSuppression
	 */
	public static function colorString(
		string $string, string $color, bool $inverse = false
	): string {
		if ( $inverse ) {
			$color = [ $color, 'reverse' ];
		}

		if ( !self::$consoleColor ) {
			// Attempt to instantiate this class to determine if the
			// (optional) php-console-color library is installed.
			try {
				self::$consoleColor = new \JakubOnderka\PhpConsoleColor\ConsoleColor();
			} catch ( Error $e ) {
				/* fall back to no-color mode */
			}
		}

		if ( self::$consoleColor && self::$consoleColor->isSupported() ) {
			return self::$consoleColor->apply( $color, $string );
		} else {
			return $string;
		}
	}
}
