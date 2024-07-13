<?php
declare( strict_types = 1 );

/**
 * General token sanitizer. Strips out (or encapsulates) unsafe and disallowed
 * tag types and attributes. Should run last in the third, synchronous
 * expansion stage.
 *
 * FIXME: This code was originally ported from PHP to JS in 2012
 * and periodically updated before being back to PHP. This code should be
 * (a) resynced with core sanitizer changes (b) updated to use HTML5 spec
 */

namespace Wikimedia\Parsoid\Core;

use InvalidArgumentException;
use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\Config\SiteConfig;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\Tokens\KV;
use Wikimedia\Parsoid\Tokens\Token;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\TokenUtils;
use Wikimedia\RemexHtml\HTMLData;

class Sanitizer {
	/**
	 * RDFa and microdata properties allow URLs, URIs and/or CURIs.
	 */
	private const MICRODATA = [
		'rel' => true,
		'rev' => true,
		'about' => true,
		'property' => true,
		'resource' => true,
		'datatype' => true,
		'typeof' => true, // RDFa
		'itemid' => true,
		'itemprop' => true,
		'itemref' => true,
		'itemscope' => true,
		'itemtype' => true,
	];

	private const UTF8_REPLACEMENT = "\u{FFFD}";

	/**
	 * Regular expression to match various types of character references in
	 * Sanitizer::normalizeCharReferences and Sanitizer::decodeCharReferences
	 */
	private const CHAR_REFS_REGEX =
		'/&([A-Za-z0-9\x80-\xff]+;)
		|&\#([0-9]+);
		|&\#[xX]([0-9A-Fa-f]+);
		|&/x';

	private const INSECURE_RE = '! expression
		| accelerator\s*:
		| -o-link\s*:
		| -o-link-source\s*:
		| -o-replace\s*:
		| url\s*\(
		| image\s*\(
		| image-set\s*\(
		| attr\s*\([^)]+[\s,]+url
	!ix';

	/**
	 * Pattern matching evil uris like javascript:
	 * WARNING: DO NOT use this in any place that actually requires denying
	 * certain URIs for security reasons. There are NUMEROUS[1] ways to bypass
	 * pattern-based deny lists; the only way to be secure from javascript:
	 * uri based xss vectors is to allow only things that you know are safe
	 * and deny everything else.
	 * [1]: http://ha.ckers.org/xss.html
	 */
	private const EVIL_URI_PATTERN = '!(^|\s|\*/\s*)(javascript|vbscript)(\W|$)!iD';
	private const XMLNS_ATTRIBUTE_PATTERN = "/^xmlns:[:A-Z_a-z-.0-9]+$/D";

	/**
	 * Tells escapeUrlForHtml() to encode the ID using the wiki's primary encoding.
	 *
	 * @since 1.30
	 */
	public const ID_PRIMARY = 0;

	/**
	 * Tells escapeUrlForHtml() to encode the ID using the fallback encoding, or return false
	 * if no fallback is configured.
	 *
	 * @since 1.30
	 */
	public const ID_FALLBACK = 1; // public because it is accessed in Headings handler

	/** Characters that will be ignored in IDNs.
	 * https://datatracker.ietf.org/doc/html/rfc8264#section-9.13
	 * https://www.unicode.org/Public/UCD/latest/ucd/DerivedCoreProperties.txt
	 * Strip them before further processing so deny lists and such work.
	 * Part of Sanitizer::cleanUrl in core.
	 */
	private const IDN_RE_G = "/
				\\s|      # general whitespace
				\u{00AD}|               # SOFT HYPHEN
				\u{034F}|               # COMBINING GRAPHEME JOINER
				\u{061C}|               # ARABIC LETTER MARK
				[\u{115F}-\u{1160}]|    # HANGUL CHOSEONG FILLER..
							# HANGUL JUNGSEONG FILLER
				[\u{17B4}-\u{17B5}]|    # KHMER VOWEL INHERENT AQ..
							# KHMER VOWEL INHERENT AA
				[\u{180B}-\u{180D}]|    # MONGOLIAN FREE VARIATION SELECTOR ONE..
							# MONGOLIAN FREE VARIATION SELECTOR THREE
				\u{180E}|               # MONGOLIAN VOWEL SEPARATOR
				[\u{200B}-\u{200F}]|    # ZERO WIDTH SPACE..
							# RIGHT-TO-LEFT MARK
				[\u{202A}-\u{202E}]|    # LEFT-TO-RIGHT EMBEDDING..
							# RIGHT-TO-LEFT OVERRIDE
				[\u{2060}-\u{2064}]|    # WORD JOINER..
							# INVISIBLE PLUS
				\u{2065}|               # <reserved-2065>
				[\u{2066}-\u{206F}]|    # LEFT-TO-RIGHT ISOLATE..
							# NOMINAL DIGIT SHAPES
				\u{3164}|               # HANGUL FILLER
				[\u{FE00}-\u{FE0F}]|    # VARIATION SELECTOR-1..
							# VARIATION SELECTOR-16
				\u{FEFF}|               # ZERO WIDTH NO-BREAK SPACE
				\u{FFA0}|               # HALFWIDTH HANGUL FILLER
				[\u{FFF0}-\u{FFF8}]|    # <reserved-FFF0>..
							# <reserved-FFF8>
				[\u{1BCA0}-\u{1BCA3}]|  # SHORTHAND FORMAT LETTER OVERLAP..
							# SHORTHAND FORMAT UP STEP
				[\u{1D173}-\u{1D17A}]|  # MUSICAL SYMBOL BEGIN BEAM..
							# MUSICAL SYMBOL END PHRASE
				\u{E0000}|              # <reserved-E0000>
				\u{E0001}|              # LANGUAGE TAG
				[\u{E0002}-\u{E001F}]|  # <reserved-E0002>..
							# <reserved-E001F>
				[\u{E0020}-\u{E007F}]|  # TAG SPACE..
							# CANCEL TAG
				[\u{E0080}-\u{E00FF}]|  # <reserved-E0080>..
							# <reserved-E00FF>
				[\u{E0100}-\u{E01EF}]|  # VARIATION SELECTOR-17..
							# VARIATION SELECTOR-256
				[\u{E01F0}-\u{E0FFF}]|  # <reserved-E01F0>..
							# <reserved-E0FFF>
				/xuD";

	private const GET_ATTRIBS_RE = '/^[:_\p{L}\p{N}][:_\.\-\p{L}\p{N}]*$/uD';

	/**
	 * Character entity aliases accepted by MediaWiki in wikitext.
	 * These are not part of the HTML standard.
	 */
	private const MW_ENTITY_ALIASES = [
		'רלמ;' => 'rlm;',
		'رلم;' => 'rlm;',
	];

	/**
	 * Fetch the list of acceptable attributes for a given element name.
	 *
	 * @param string $element
	 * @return array<string,int>
	 */
	public static function attributesAllowedInternal( string $element ): array {
		// PORT-FIXME: this method is private in core, but used by Gallery
		$lists = self::setupAttributesAllowedInternal();
		$list = $lists[$element] ?? [];
		return array_flip( $list );
	}

	/**
	 * Foreach array key (an allowed HTML element), return an array
	 * of allowed attributes
	 * @return array<string,string[]>
	 */
	private static function setupAttributesAllowedInternal(): array {
		static $allowed;

		if ( $allowed !== null ) {
			return $allowed;
		}

		$common = [
			# HTML
			'id',
			'class',
			'style',
			'lang',
			'dir',
			'title',
			'tabindex',

			# WAI-ARIA
			'aria-describedby',
			'aria-flowto',
			'aria-hidden',
			'aria-label',
			'aria-labelledby',
			'aria-level',
			'aria-owns',
			'role',

			# RDFa
			# These attributes are specified in section 9 of
			# https://www.w3.org/TR/2008/REC-rdfa-syntax-20081014
			'about',
			'property',
			'resource',
			'datatype',
			'typeof',

			# Microdata. These are specified by
			# https://html.spec.whatwg.org/multipage/microdata.html#the-microdata-model
			'itemid',
			'itemprop',
			'itemref',
			'itemscope',
			'itemtype',
		];

		$block = array_merge( $common, [ 'align' ] );
		$tablealign = [ 'align', 'valign' ];
		$tablecell = [
			'abbr',
			'axis',
			'headers',
			'scope',
			'rowspan',
			'colspan',
			'nowrap', # deprecated
			'width', # deprecated
			'height', # deprecated
			'bgcolor', # deprecated
		];

		# Numbers refer to sections in HTML 4.01 standard describing the element.
		# See: https://www.w3.org/TR/html4/
		$allowed = [
			# 7.5.4
			'div'        => $block,
			'center'     => $common, # deprecated
			'span'       => $common,

			# 7.5.5
			'h1'         => $block,
			'h2'         => $block,
			'h3'         => $block,
			'h4'         => $block,
			'h5'         => $block,
			'h6'         => $block,

			# 7.5.6
			# address

			# 8.2.4
			'bdo'        => $common,

			# 9.2.1
			'em'         => $common,
			'strong'     => $common,
			'cite'       => $common,
			'dfn'        => $common,
			'code'       => $common,
			'samp'       => $common,
			'kbd'        => $common,
			'var'        => $common,
			'abbr'       => $common,
			# acronym

			# 9.2.2
			'blockquote' => array_merge( $common, [ 'cite' ] ),
			'q'          => array_merge( $common, [ 'cite' ] ),

			# 9.2.3
			'sub'        => $common,
			'sup'        => $common,

			# 9.3.1
			'p'          => $block,

			# 9.3.2
			'br'         => array_merge( $common, [ 'clear' ] ),

			# https://www.w3.org/TR/html5/text-level-semantics.html#the-wbr-element
			'wbr'        => $common,

			# 9.3.4
			'pre'        => array_merge( $common, [ 'width' ] ),

			# 9.4
			'ins'        => array_merge( $common, [ 'cite', 'datetime' ] ),
			'del'        => array_merge( $common, [ 'cite', 'datetime' ] ),

			# 10.2
			'ul'         => array_merge( $common, [ 'type' ] ),
			'ol'         => array_merge( $common, [ 'type', 'start', 'reversed' ] ),
			'li'         => array_merge( $common, [ 'type', 'value' ] ),

			# 10.3
			'dl'         => $common,
			'dd'         => $common,
			'dt'         => $common,

			# 11.2.1
			'table'      => array_merge( $common,
								[ 'summary', 'width', 'border', 'frame',
										'rules', 'cellspacing', 'cellpadding',
										'align', 'bgcolor',
								] ),

			# 11.2.2
			'caption'    => $block,

			# 11.2.3
			'thead'      => $common,
			'tfoot'      => $common,
			'tbody'      => $common,

			# 11.2.4
			'colgroup'   => array_merge( $common, [ 'span' ] ),
			'col'        => array_merge( $common, [ 'span' ] ),

			# 11.2.5
			'tr'         => array_merge( $common, [ 'bgcolor' ], $tablealign ),

			# 11.2.6
			'td'         => array_merge( $common, $tablecell, $tablealign ),
			'th'         => array_merge( $common, $tablecell, $tablealign ),

			# 12.2
			# NOTE: <a> is not allowed directly, but this list of allowed
			# attributes is used from the Parser object
			'a'          => array_merge( $common, [ 'href', 'rel', 'rev' ] ), # rel/rev esp. for RDFa

			# 13.2
			# Not usually allowed, but may be used for extension-style hooks
			# such as <math> when it is rasterized, or if $wgAllowImageTag is
			# true
			'img'        => array_merge( $common, [ 'alt', 'src', 'width', 'height', 'srcset' ] ),
			# Attributes for A/V tags added in T163583 / T133673
			'audio'      => array_merge( $common, [ 'controls', 'preload', 'width', 'height' ] ),
			'video'      => array_merge( $common, [ 'poster', 'controls', 'preload', 'width', 'height' ] ),
			'source'     => array_merge( $common, [ 'type', 'src' ] ),
			'track'      => array_merge( $common, [ 'type', 'src', 'srclang', 'kind', 'label' ] ),

			# 15.2.1
			'tt'         => $common,
			'b'          => $common,
			'i'          => $common,
			'big'        => $common,
			'small'      => $common,
			'strike'     => $common,
			's'          => $common,
			'u'          => $common,

			# 15.2.2
			'font'       => array_merge( $common, [ 'size', 'color', 'face' ] ),
			# basefont

			# 15.3
			'hr'         => array_merge( $common, [ 'width' ] ),

			# HTML Ruby annotation text module, simple ruby only.
			# https://www.w3.org/TR/html5/text-level-semantics.html#the-ruby-element
			'ruby'       => $common,
			# rbc
			'rb'         => $common,
			'rp'         => $common,
			'rt'         => $common, # array_merge( $common, array( 'rbspan' ) ),
			'rtc'        => $common,

			# MathML root element, where used for extensions
			# 'title' may not be 100% valid here; it's XHTML
			# https://www.w3.org/TR/REC-MathML/
			'math'       => [ 'class', 'style', 'id', 'title' ],

			// HTML 5 section 4.5
			'figure'     => $common,
			'figcaption' => $common,

			# HTML 5 section 4.6
			'bdi' => $common,

			# HTML5 elements, defined by:
			# https://html.spec.whatwg.org/multipage/semantics.html#the-data-element
			'data' => array_merge( $common, [ 'value' ] ),
			'time' => array_merge( $common, [ 'datetime' ] ),
			'mark' => $common,

			// meta and link are only permitted by removeHTMLtags when Microdata
			// is enabled so we don't bother adding a conditional to hide these
			// Also meta and link are only valid in WikiText as Microdata elements
			// (ie: validateTag rejects tags missing the attributes needed for Microdata)
			// So we don't bother including $common attributes that have no purpose.
			'meta' => [ 'itemprop', 'content' ],
			'link' => [ 'itemprop', 'href', 'title' ],

			// HTML 5 section 4.3.5
			'aside' => $common,
		];

		return $allowed;
	}

	/**
	 * Ensure that any entities and character references are legal
	 * for XML and XHTML specifically. Any stray bits will be
	 * &amp;-escaped to result in a valid text fragment.
	 *
	 * a. named char refs can only be &lt; &gt; &amp; &quot;, others are
	 *   numericized (this way we're well-formed even without a DTD)
	 * b. any numeric char refs must be legal chars, not invalid or forbidden
	 * c. use lower cased "&#x", not "&#X"
	 * d. fix or reject non-valid attributes
	 *
	 * @param string $text
	 * @return string
	 * @internal
	 */
	public static function normalizeCharReferences( string $text ): string {
		return preg_replace_callback(
			self::CHAR_REFS_REGEX,
			[ self::class, 'normalizeCharReferencesCallback' ],
			$text, -1, $count, PREG_UNMATCHED_AS_NULL
		);
	}

	/**
	 * @param array $matches
	 * @return string
	 */
	private static function normalizeCharReferencesCallback( array $matches ): string {
		$ret = null;
		if ( isset( $matches[1] ) ) {
			$ret = self::normalizeEntity( $matches[1] );
		} elseif ( isset( $matches[2] ) ) {
			$ret = self::decCharReference( $matches[2] );
		} elseif ( isset( $matches[3] ) ) {
			$ret = self::hexCharReference( $matches[3] );
		}
		if ( $ret === null ) {
			return htmlspecialchars( $matches[0] );
		} else {
			return $ret;
		}
	}

	/**
	 * If the named entity is defined in HTML5
	 * return the equivalent numeric entity reference (except for the core &lt;
	 * &gt; &amp; &quot;). If the entity is a MediaWiki-specific alias, returns
	 * the HTML equivalent. Otherwise, returns HTML-escaped text of
	 * pseudo-entity source (eg &amp;foo;)
	 *
	 * @param string $name Semicolon-terminated name
	 * @return string
	 */
	private static function normalizeEntity( string $name ): string {
		if ( isset( self::MW_ENTITY_ALIASES[$name] ) ) {
			// Non-standard MediaWiki-specific entities
			return '&' . self::MW_ENTITY_ALIASES[$name];
		} elseif ( in_array( $name, [ 'lt;', 'gt;', 'amp;', 'quot;' ], true ) ) {
			// Keep these in word form
			return "&$name";
		} elseif ( isset( HTMLData::$namedEntityTranslations[$name] ) ) {
			// Beware: some entities expand to more than 1 codepoint
			return preg_replace_callback( '/./Ssu', function ( $m ) {
				return '&#' . self::utf8ToCodepoint( $m[0] ) . ';';
			}, HTMLData::$namedEntityTranslations[$name] );
		} else {
			return "&amp;$name";
		}
	}

	/**
	 * @param string $codepoint
	 * @return null|string
	 */
	private static function decCharReference( string $codepoint ): ?string {
		# intval() will (safely) saturate at the maximum signed integer
		# value if $codepoint is too many digits
		$point = intval( $codepoint );
		if ( self::validateCodepoint( $point ) ) {
			return "&#$point;";
		} else {
			return null;
		}
	}

	/**
	 * @param string $codepoint
	 * @return null|string
	 */
	private static function hexCharReference( string $codepoint ): ?string {
		$point = hexdec( $codepoint );
		// hexdec() might return a float if the string is too long
		if ( is_int( $point ) && self::validateCodepoint( $point ) ) {
			return sprintf( '&#x%x;', $point );
		} else {
			return null;
		}
	}

	/**
	 * Returns true if a given Unicode codepoint is a valid character in
	 * both HTML5 and XML.
	 * @param int $codepoint
	 * @return bool
	 */
	private static function validateCodepoint( int $codepoint ): bool {
		# U+000C is valid in HTML5 but not allowed in XML.
		# U+000D is valid in XML but not allowed in HTML5.
		# U+007F - U+009F are disallowed in HTML5 (control characters).
		return $codepoint == 0x09
			|| $codepoint == 0x0a
			|| ( $codepoint >= 0x20 && $codepoint <= 0x7e )
			|| ( $codepoint >= 0xa0 && $codepoint <= 0xd7ff )
			|| ( $codepoint >= 0xe000 && $codepoint <= 0xfffd )
			|| ( $codepoint >= 0x10000 && $codepoint <= 0x10ffff );
	}

	/**
	 * Returns a string from the provided code point.
	 *
	 * @param int $cp
	 * @return string
	 */
	private static function codepointToUtf8( int $cp ): string {
		$chr = mb_chr( $cp, 'UTF-8' );
		Assert::invariant( $chr !== false, "Getting char failed!" );
		return $chr;
	}

	/**
	 * Returns the code point at the first position of the string.
	 *
	 * @param string $str
	 * @return int
	 */
	private static function utf8ToCodepoint( string $str ): int {
		$ord = mb_ord( $str );
		Assert::invariant( $ord !== false, "Getting code point failed!" );
		return $ord;
	}

	/**
	 * @param string $host
	 * @return string
	 */
	private static function stripIDNs( string $host ): string {
		// This code is part of Sanitizer::cleanUrl in core
		return preg_replace( self::IDN_RE_G, '', $host );
	}

	/**
	 * @param SiteConfig $siteConfig
	 * @param string $href
	 * @param string $mode
	 * @return string|null
	 */
	public static function cleanUrl( SiteConfig $siteConfig, string $href, string $mode ): ?string {
		if ( $mode !== 'wikilink' ) {
			$href = preg_replace_callback(
				'/([\][<>"\x00-\x20\x7F\|])/', static function ( $matches ) {
					return urlencode( $matches[0] );
				}, $href
			);
		}

		$matched = preg_match( '#^((?:[a-zA-Z][^:/]*:)?(?://)?)([^/]+)(/?.*)#', $href, $bits );
		if ( $matched === 1 ) {
			$proto = $bits[1];
			if ( $proto && !$siteConfig->hasValidProtocol( $proto ) ) {
				// invalid proto, disallow URL
				return null;
			}
			$host = self::stripIDNs( $bits[2] );
			preg_match( '/^%5B([0-9A-Fa-f:.]+)%5D((:\d+)?)$/D', $host, $match );
			if ( $match ) {
				// IPv6 host names
				$host = '[' . $match[1] . ']' . $match[2];
			}
			$path = $bits[3];
		} else {
			$proto = '';
			$host = '';
			$path = $href;
		}
		return $proto . $host . $path;
	}

	/**
	 * If the named entity is defined in HTML5
	 * return the UTF-8 encoding of that character. Otherwise, returns
	 * pseudo-entity source (eg "&foo;")
	 *
	 * @param string $name Semicolon-terminated entity name
	 * @return string
	 */
	private static function decodeEntity( string $name ): string {
		// These are MediaWiki-specific entities, not in the HTML standard
		if ( isset( self::MW_ENTITY_ALIASES[$name] ) ) {
			$name = self::MW_ENTITY_ALIASES[$name];
		}
		$trans = HTMLData::$namedEntityTranslations[$name] ?? null;
		return $trans ?? "&$name";
	}

	/**
	 * Return UTF-8 string for a codepoint if that is a valid
	 * character reference, otherwise U+FFFD REPLACEMENT CHARACTER.
	 * @param int $codepoint
	 * @return string
	 */
	private static function decodeChar( int $codepoint ): string {
		if ( self::validateCodepoint( $codepoint ) ) {
			return self::codepointToUtf8( $codepoint );
		} else {
			return self::UTF8_REPLACEMENT;
		}
	}

	/**
	 * Decode any character references, numeric or named entities,
	 * in the text and return a UTF-8 string.
	 * @param string $text
	 * @return string
	 */
	public static function decodeCharReferences( string $text ): string {
		return preg_replace_callback(
			self::CHAR_REFS_REGEX,
			function ( $matches ) {
				if ( isset( $matches[1] ) ) {
					return self::decodeEntity( $matches[1] );
				} elseif ( isset( $matches[2] ) ) {
					return self::decodeChar( intval( $matches[2] ) );
				} elseif ( isset( $matches[3] ) ) {
					$point = hexdec( $matches[3] );
					// hexdec() might return a float if the string is too long
					if ( !is_int( $point ) ) {
						// Invalid character reference.
						return self::UTF8_REPLACEMENT;
					}
					return self::decodeChar( $point );
				}
				# Last case should be an ampersand by itself
				return $matches[0];
			},
			$text, -1, $count, PREG_UNMATCHED_AS_NULL
		);
	}

	/**
	 * Normalize CSS into a format we can easily search for hostile input
	 *  - decode character references
	 *  - decode escape sequences
	 *  - convert characters that IE6 interprets into ascii
	 *  - remove comments, unless the entire value is one single comment
	 * @param string $value the css string
	 * @return string normalized css
	 */
	public static function normalizeCss( string $value ): string {
		// Decode character references like &#123;
		$value = self::decodeCharReferences( $value );

		// Decode escape sequences and line continuation
		// See the grammar in the CSS 2 spec, appendix D.
		// This has to be done AFTER decoding character references.
		// This means it isn't possible for this function to return
		// unsanitized escape sequences. It is possible to manufacture
		// input that contains character references that decode to
		// escape sequences that decode to character references, but
		// it's OK for the return value to contain character references
		// because the caller is supposed to escape those anyway.
		static $decodeRegex;
		if ( !$decodeRegex ) {
			$space = '[\\x20\\t\\r\\n\\f]';
			$nl = '(?:\\n|\\r\\n|\\r|\\f)';
			$backslash = '\\\\';
			$decodeRegex = "/ $backslash
				(?:
					($nl) |  # 1. Line continuation
					([0-9A-Fa-f]{1,6})$space? |  # 2. character number
					(.) | # 3. backslash cancelling special meaning
					() | # 4. backslash at end of string
				)/xu";
		}
		$value = preg_replace_callback( $decodeRegex,
			[ self::class, 'cssDecodeCallback' ], $value );

		// Let the value through if it's nothing but a single comment, to
		// allow other functions which may reject it to pass some error
		// message through.
		if ( !preg_match( '! ^ \s* /\* [^*\\/]* \*/ \s* $ !xD', $value ) ) {
			// Remove any comments; IE gets token splitting wrong
			// This must be done AFTER decoding character references and
			// escape sequences, because those steps can introduce comments
			// This step cannot introduce character references or escape
			// sequences, because it replaces comments with spaces rather
			// than removing them completely.
			$value = self::delimiterReplace( '/*', '*/', ' ', $value );

			// Remove anything after a comment-start token, to guard against
			// incorrect client implementations.
			$commentPos = strpos( $value, '/*' );
			if ( $commentPos !== false ) {
				$value = substr( $value, 0, $commentPos );
			}
		}

		return $value;
	}

	// PORT_FIXME - The delimiterReplace code below is from StringUtils in core

	/**
	 * Perform an operation equivalent to `preg_replace_callback()`
	 *
	 * Matches this code:
	 *
	 *     preg_replace_callback( "!$startDelim(.*)$endDelim!s$flags", $callback, $subject );
	 *
	 * If the start delimiter ends with an initial substring of the end delimiter,
	 * e.g. in the case of C-style comments, the behavior differs from the model
	 * regex. In this implementation, the end must share no characters with the
	 * start, so e.g. `/*\/` is not considered to be both the start and end of a
	 * comment. `/*\/xy/*\/` is considered to be a single comment with contents `/xy/`.
	 *
	 * The implementation of delimiterReplaceCallback() is slower than hungryDelimiterReplace()
	 * but uses far less memory. The delimiters are literal strings, not regular expressions.
	 *
	 * @param string $startDelim Start delimiter
	 * @param string $endDelim End delimiter
	 * @param callable $callback Function to call on each match
	 * @param string $subject
	 * @param string $flags Regular expression flags
	 * @throws InvalidArgumentException
	 * @return string
	 */
	private static function delimiterReplaceCallback(
		string $startDelim, string $endDelim, callable $callback, string $subject, string $flags = ''
	): string {
		$inputPos = 0;
		$outputPos = 0;
		$contentPos = 0;
		$output = '';
		$foundStart = false;
		$encStart = preg_quote( $startDelim, '!' );
		$encEnd = preg_quote( $endDelim, '!' );
		$strcmp = strpos( $flags, 'i' ) === false ? 'strcmp' : 'strcasecmp';
		$endLength = strlen( $endDelim );
		$m = [];
		while ( $inputPos < strlen( $subject ) &&
			preg_match( "!($encStart)|($encEnd)!S$flags", $subject, $m, PREG_OFFSET_CAPTURE, $inputPos )
		) {
			$tokenOffset = $m[0][1];
			if ( $m[1][0] !== '' ) {
				if ( $foundStart &&
					$strcmp( $endDelim, substr( $subject, $tokenOffset, $endLength ) ) === 0
				) {
					# An end match is present at the same location
					$tokenType = 'end';
					$tokenLength = $endLength;
				} else {
					$tokenType = 'start';
					$tokenLength = strlen( $m[0][0] );
				}
			} elseif ( $m[2][0] !== '' ) {
				$tokenType = 'end';
				$tokenLength = strlen( $m[0][0] );
			} else {
				throw new InvalidArgumentException( 'Invalid delimiter given to ' . __METHOD__ );
			}
			if ( $tokenType === 'start' ) {
				# Only move the start position if we haven't already found a start
				# This means that START START END matches outer pair
				if ( !$foundStart ) {
					# Found start
					$inputPos = $tokenOffset + $tokenLength;
					# Write out the non-matching section
					$output .= substr( $subject, $outputPos, $tokenOffset - $outputPos );
					$outputPos = $tokenOffset;
					$contentPos = $inputPos;
					$foundStart = true;
				} else {
					# Move the input position past the *first character* of START,
					# to protect against missing END when it overlaps with START
					$inputPos = $tokenOffset + 1;
				}
			} elseif ( $tokenType === 'end' ) {
				if ( $foundStart ) {
					# Found match
					$output .= $callback( [
						substr( $subject, $outputPos, $tokenOffset + $tokenLength - $outputPos ),
						substr( $subject, $contentPos, $tokenOffset - $contentPos )
					] );
					$foundStart = false;
				} else {
					# Non-matching end, write it out
					$output .= substr( $subject, $inputPos, $tokenOffset + $tokenLength - $outputPos );
				}
				$inputPos = $outputPos = $tokenOffset + $tokenLength;
			} else {
				throw new InvalidArgumentException( 'Invalid delimiter given to ' . __METHOD__ );
			}
		}
		if ( $outputPos < strlen( $subject ) ) {
			$output .= substr( $subject, $outputPos );
		}
		return $output;
	}

	/**
	 * Perform an operation equivalent to `preg_replace()` with flags.
	 *
	 * Matches this code:
	 *
	 *     preg_replace( "!$startDelim(.*)$endDelim!$flags", $replace, $subject );
	 *
	 * @param string $startDelim Start delimiter regular expression
	 * @param string $endDelim End delimiter regular expression
	 * @param string $replace Replacement string. May contain $1, which will be
	 *  replaced by the text between the delimiters
	 * @param string $subject String to search
	 * @param string $flags Regular expression flags
	 * @return string The string with the matches replaced
	 */
	private static function delimiterReplace(
		string $startDelim, string $endDelim, string $replace, string $subject, string $flags = ''
	): string {
		return self::delimiterReplaceCallback(
			$startDelim, $endDelim,
			static function ( array $matches ) use ( $replace ) {
				return strtr( $replace, [ '$0' => $matches[0], '$1' => $matches[1] ] );
			},
			$subject, $flags
		);
	}

	/**
	 * SSS FIXME: There is a test in mediawiki.environment.js that doles out
	 * and tests about ids. There are probably some tests in Util.php as well.
	 * We should move all these kind of tests somewhere else.
	 * @param string $k
	 * @param string $v
	 * @param KV[] $attrs
	 * @return bool
	 */
	private static function isParsoidAttr( string $k, string $v, array $attrs ): bool {
		// NOTES:
		// 1. Currently the tokenizer unconditionally escapes typeof and about
		// attributes from wikitxt to data-x-typeof and data-x-about. So,
		// this check will only pass through Parsoid inserted attrs.
		// 2. But, if we fix the over-aggressive escaping in the tokenizer to
		// not escape non-Parsoid typeof and about, then this will return
		// true for something like typeof='mw:Foo evilScriptHere'. But, that
		// is safe since this check is only used to see if we should
		// unconditionally discard the entire attribute or process it further.
		// That further processing will catch and discard any dangerous
		// strings in the rest of the attribute
		return ( in_array( $k, [ 'typeof', 'property', 'rel' ], true )
				&& preg_match( '/(?:^|\s)mw:.+?(?=$|\s)/D', $v ) )
			|| ( $k === 'about' && preg_match( '/^#mwt\d+$/D', $v ) )
			|| ( $k === 'content'
				&& preg_match( '/(?:^|\s)mw:.+?(?=$|\s)/D', KV::lookup( $attrs, 'property' ) ?? '' ) );
	}

	/**
	 * Given an attribute name, checks whether it is a reserved data attribute
	 * (such as data-mw-foo) which is unavailable to user-generated HTML so MediaWiki
	 * core and extension code can safely use it to communicate with frontend code.
	 * @param string $attr Attribute name.
	 * @return bool
	 */
	public static function isReservedDataAttribute( string $attr ): bool {
		// data-ooui is reserved for ooui.
		// data-mw and data-parsoid are reserved for parsoid.
		// data-mw-<name here> is reserved for extensions (or core) if
		// they need to communicate some data to the client and want to be
		// sure that it isn't coming from an untrusted user.
		// We ignore the possibility of namespaces since user-generated HTML
		// can't use them anymore.
		if ( preg_match( '/^data-(mw|parsoid)/', $attr ) ) {
			return false; // PARSOID SPECIFIC
		}
		return (bool)preg_match( '/^data-(ooui|mw|parsoid)/i', $attr );
	}

	/**
	 * @param SiteConfig $siteConfig
	 * @param ?string $tagName
	 * @param ?Token $token
	 * @param array $attrs
	 * @return array
	 */
	public static function sanitizeTagAttrs(
		SiteConfig $siteConfig, ?string $tagName, ?Token $token, array $attrs
	): array {
		$tag = $tagName ?: $token->getName();

		$list = self::attributesAllowedInternal( $tag );
		$newAttrs = [];
		$n = count( $attrs );
		for ( $i = 0;  $i < $n;  $i++ ) {
			$a = $attrs[$i];
			$a->v ??= '';

			// Convert attributes to string, if necessary.
			$a->k = TokenUtils::tokensToString( $a->k );

			if ( is_array( $a->v ) ) {
				// Use the expanded attr instead of trying to unpackDOMFragments
				// since the fragment will have been released when expanding to DOM
				$expandedVal = $token ? $token->fetchExpandedAttrValue( $a->k ) : null;
				if ( $expandedVal === null ) {
					$a->v = TokenUtils::tokensToString( $a->v );
				} else {
					// See the comment in TokenUtils::tokensToString about
					// unpackDOMFragments for why we're just using the textContent
					$dom = DOMUtils::parseHTML( $expandedVal );
					$a->v = DOMCompat::getBody( $dom )->textContent;
				}
			}

			$origK = $a->ksrc ?? $a->k;
			// $a->k can be uppercase
			$k = mb_strtolower( $a->k );
			$v = $a->v;
			$origV = $a->vsrc ?? $v;
			$psdAttr = self::isParsoidAttr( $k, $v, $attrs );

			// Bypass RDFa/allowed attribute checks for Parsoid-inserted attrs
			// Safe to do since the tokenizer renames about/typeof attrs.
			// unconditionally. FIXME: The escaping solution in the tokenizer
			// may be aggressive. There is no need to escape typeof strings
			// that or about ids that don't resemble Parsoid tokens/about ids.
			if ( !$psdAttr ) {
				if ( !preg_match( self::GET_ATTRIBS_RE, $k ) ) {
					$newAttrs[$k] = [ null, $origV, $origK ];
					continue;
				}

				# Allow XML namespace declaration to allow RDFa
				if ( preg_match( self::XMLNS_ATTRIBUTE_PATTERN, $k ) ) {
					if ( !preg_match( self::EVIL_URI_PATTERN, $v ) ) {
						$newAttrs[$k] = [ $v, $origV, $origK ];
					} else {
						$newAttrs[$k] = [ null, $origV, $origK ];
					}
					continue;
				}

				# Allow any attribute beginning with "data-"
				# However:
				# * Disallow data attributes used by MediaWiki code
				# * Ensure that the attribute is not namespaced by banning
				#   colons.
				if ( ( !preg_match( '/^data-[^:]*$/iD', $k ) && !isset( $list[$k] ) )
					 || self::isReservedDataAttribute( $k )
				) {
					$newAttrs[$k] = [ null, $origV, $origK ];
					continue;
				}
			}

			# Strip javascript "expression" from stylesheets.
			# http://msdn.microsoft.com/workshop/author/dhtml/overview/recalc.asp
			if ( $k === 'style' ) {
				$v = self::checkCss( $v );
			}

			# Escape HTML id attributes
			if ( $k === 'id' ) {
				$v = self::escapeIdForAttribute( $v, self::ID_PRIMARY );
			}

			# Escape HTML id reference lists
			if ( $k === 'aria-describedby'
				|| $k === 'aria-flowto'
				|| $k === 'aria-labelledby'
				|| $k === 'aria-owns'
			) {
				$v = self::escapeIdReferenceList( $v );
			}

			// RDFa and microdata properties allow URLs, URIs and/or CURIs.
			// Check them for validity.
			if ( $k === 'rel' || $k === 'rev'
				# RDFa
				|| $k === 'about' || $k === 'property'
				|| $k === 'resource' || $k === 'datatype'
				|| $k === 'typeof'
				# HTML5 microdata
				|| $k === 'itemid' || $k === 'itemprop'
				|| $k === 'itemref' || $k === 'itemscope'
				|| $k === 'itemtype'
			) {
				// Paranoia. Allow "simple" values but suppress javascript
				if ( preg_match( self::EVIL_URI_PATTERN, $v ) ) {
					// Retain the Parsoid typeofs for Parsoid attrs
					$newV = $psdAttr ? trim( preg_replace( '/(?:^|\s)(?!mw:\w)\S*/', '', $origV ) ) : null;
					$newAttrs[$k] = [ $newV, $origV, $origK ];
					continue;
				}
			}

			# NOTE: even though elements using href/src are not allowed directly, supply
			#       validation code that can be used by tag hook handlers, etc
			if ( $token && ( $k === 'href' || $k === 'src' || $k === 'poster' ) ) { // T163583
				// `origV` will always be `v`, because `a.vsrc` isn't set, since
				// this attribute didn't come from source.  However, in the
				// LinkHandler, we may have already shadowed this value so use
				// that instead.
				$rel = $token->getAttributeShadowInfo( 'rel' );
				$mode = ( $k === 'href' &&
					isset( $rel['value'] ) &&
					preg_match( '#^mw:WikiLink(/Interwiki)?$#', $rel['value'] )
				) ? 'wikilink' : 'external';
				$origHref = $token->getAttributeShadowInfo( $k )['value'];
				$newHref = self::cleanUrl( $siteConfig, $v, $mode );
				if ( $newHref !== $v ) {
					$newAttrs[$k] = [ $newHref, $origHref, $origK ];
					continue;
				}
			}

			if ( $k === 'tabindex' && $v !== '0' ) {
				// Only allow tabindex of 0, which is useful for accessibility.
				continue;
			}

			// SSS FIXME: This logic is not RT-friendly.
			// If this attribute was previously set, override it.
			// Output should only have one attribute of each name.
			$newAttrs[$k] = [ $v, $origV, $origK ];
		}

		# itemtype, itemid, itemref don't make sense without itemscope
		if ( !array_key_exists( 'itemscope', $newAttrs ) ) {
			// SSS FIXME: This logic is not RT-friendly.
			unset( $newAttrs['itemtype'] );
			unset( $newAttrs['itemid'] );
			unset( $newAttrs['itemref'] );
		}
		# TODO: Strip itemprop if we aren't descendants of an itemscope or pointed to by an itemref.

		return $newAttrs;
	}

	/**
	 * Sanitize and apply attributes to a wrapper element.
	 *
	 * Used primarily when we're applying tokenized attributes directly to
	 * dom elements, which wouldn't have had a chance to be sanitized before
	 * tree building.
	 * @param SiteConfig $siteConfig
	 * @param Element $wrapper wrapper
	 * @param array $attrs attributes
	 */
	public static function applySanitizedArgs(
		SiteConfig $siteConfig, Element $wrapper, array $attrs
	): void {
		$nodeName = DOMCompat::nodeName( $wrapper );
		$sanitizedAttrs = self::sanitizeTagAttrs( $siteConfig, $nodeName, null, $attrs );
		foreach ( $sanitizedAttrs as $k => $v ) {
			if ( isset( $v[0] ) ) {
				$wrapper->setAttribute( $k, $v[0] );
			}
		}
	}

	/**
	 * @param string $text
	 * @return string
	 */
	public static function checkCss( string $text ): string {
		$text = self::normalizeCss( $text );
		// \000-\010\013\016-\037\177 are the octal escape sequences
		if ( preg_match( '/[\000-\010\013\016-\037\177]/', $text )
			|| strpos( $text, self::UTF8_REPLACEMENT ) !== false
		) {
			return '/* invalid control char */';
		} elseif ( preg_match( self::INSECURE_RE, $text ) ) {
			return '/* insecure input */';
		} else {
			return $text;
		}
	}

	/**
	 * @param array $matches
	 * @return string
	 */
	public static function cssDecodeCallback( array $matches ): string {
		if ( $matches[1] !== '' ) {
			// Line continuation
			return '';
		} elseif ( $matches[2] !== '' ) {
			# hexdec could return a float if the match is too long, but the
			# regexp in question limits the string length to 6.
			$char = self::codepointToUtf8( hexdec( $matches[2] ) );
		} elseif ( $matches[3] !== '' ) {
			$char = $matches[3];
		} else {
			$char = '\\';
		}
		if ( $char == "\n" || $char == '"' || $char == "'" || $char == '\\' ) {
			// These characters need to be escaped in strings
			// Clean up the escape sequence to avoid parsing errors by clients
			return '\\' . dechex( ord( $char ) ) . ' ';
		} else {
			// Decode unnecessary escape
			return $char;
		}
	}

	/**
	 * Sanitize a title to be used in a URI?
	 * @param string $title
	 * @param bool $isInterwiki
	 * @return string
	 */
	public static function sanitizeTitleURI( string $title, bool $isInterwiki = false ): string {
		$idx = strpos( $title, '#' );
		$anchor = null;
		if ( $idx !== false ) { // split at first '#'
			$anchor = substr( $title, $idx + 1 );
			$title = substr( $title, 0, $idx );
		}
		$title = preg_replace_callback(
			'/[%? \[\]#|<>]/', static function ( $matches ) {
				return PHPUtils::encodeURIComponent( $matches[0] );
			}, $title );
		if ( $anchor !== null ) {
			$title .= '#' . ( $isInterwiki
					? self::escapeIdForExternalInterwiki( $anchor )
					: self::escapeIdForLink( $anchor ) );
		}
		return $title;
	}

	public const FIXTAGS = [
		# French spaces, last one Guillemet-left
		# only if it isn't followed by a word character.
		'/ (?=[?:;!%»›](?!\w))/u' => "%s",
		# French spaces, Guillemet-right
		'/([«‹]) /u' => "\\1%s",
	];

	/**
	 * Armor French spaces with a replacement character
	 *
	 * @since 1.32
	 * @param string $text Text to armor
	 * @param string $space Space character for the French spaces, defaults to '&#160;'
	 * @return string Armored text
	 */
	public static function armorFrenchSpaces( string $text, string $space = '&#160;' ): string {
		// Replace $ with \$ and \ with \\
		$space = preg_replace( '#(?<!\\\\)(\\$|\\\\)#', '\\\\$1', $space );
		return preg_replace(
			array_keys( self::FIXTAGS ),
			array_map( static function ( string $replacement ) use ( $space ) {
				// @phan-suppress-next-line PhanPluginPrintfVariableFormatString
				return sprintf( $replacement, $space );
			}, array_values( self::FIXTAGS ) ),
			$text
		);
	}

	/**
	 * Given a section name or other user-generated or otherwise unsafe string, escapes it to be
	 * a valid HTML id attribute.
	 *
	 * WARNING: unlike escapeId(), the output of this function is not guaranteed to be HTML safe,
	 * be sure to use proper escaping.
	 *
	 * In Parsoid, proper escaping is usually handled for us by the HTML
	 * serialization algorithm, but be careful of corner cases (such as
	 * emitting attributes in wikitext).
	 *
	 * @param string $id String to escape
	 * @param int $mode One of ID_* constants, specifying whether the primary or fallback encoding
	 *     should be used.
	 * @return string Escaped ID
	 *
	 * @since 1.30
	 */
	public static function escapeIdForAttribute( string $id, int $mode = self::ID_PRIMARY ): string {
		// For consistency with PHP's API, we accept "primary" or "fallback" as
		// the mode in 'options'.  This (slightly) abstracts the actual details
		// of the id encoding from the Parsoid code which handles ids; we could
		// swap primary and fallback here, or even transition to a new HTML6
		// encoding (!), without touching all the call sites.
		$internalMode = $mode === self::ID_FALLBACK ? 'legacy' : 'html5';
		return self::escapeIdInternal( $id, $internalMode );
	}

	/**
	 * Given a section name or other user-generated or otherwise unsafe string, escapes it to be
	 * a valid URL fragment.
	 *
	 * WARNING: unlike escapeId(), the output of this function is not guaranteed to be HTML safe,
	 * be sure to use proper escaping.
	 *
	 * @param string $id String to escape
	 * @return string Escaped ID
	 *
	 * @since 1.30
	 */
	public static function escapeIdForLink( string $id ): string {
		return self::escapeIdInternalUrl( $id, 'html5' );
	}

	/**
	 * Given a section name or other user-generated or otherwise unsafe string, escapes it to be
	 * a valid URL fragment for external interwikis.
	 *
	 * @param string $id String to escape
	 * @return string Escaped ID
	 *
	 * @since 1.30
	 */
	private static function escapeIdForExternalInterwiki( string $id ): string {
		// Assume $wgExternalInterwikiFragmentMode = 'legacy'
		return self::escapeIdInternalUrl( $id, 'legacy' );
	}

	/**
	 * Do percent encoding of percent signs for href (but not id) attributes
	 *
	 * @see https://phabricator.wikimedia.org/T238385
	 * @param string $id String to escape
	 * @param string $mode One of modes from $wgFragmentMode
	 * @return string
	 */
	private static function escapeIdInternalUrl( string $id, string $mode ): string {
		$id = self::escapeIdInternal( $id, $mode );
		if ( $mode === 'html5' ) {
			$id = preg_replace( '/%([a-fA-F0-9]{2})/', '%25$1', $id );
		}
		return $id;
	}

	/**
	 * Helper for escapeIdFor*() functions. Performs most of the actual escaping.
	 *
	 * @param string $id String to escape
	 * @param string $mode One of modes from $wgFragmentMode ('html5' or 'legacy')
	 * @return string
	 */
	private static function escapeIdInternal( string $id, string $mode ): string {
		switch ( $mode ) {
			case 'html5':
				// html5 spec says ids must not have any of the following:
				// U+0009 TAB, U+000A LF, U+000C FF, U+000D CR, or U+0020 SPACE
				// In practice, in wikitext, only tab, LF, CR (and SPACE) are
				// possible using either Lua or html entities.
				$id = str_replace( [ "\t", "\n", "\f", "\r", " " ], '_', $id );
				break;

			case 'legacy':
				// This corresponds to 'noninitial' mode of the old escapeId
				static $replace = [
					'%3A' => ':',
					'%' => '.'
				];

				$id = urlencode( str_replace( ' ', '_', $id ) );
				$id = strtr( $id, $replace );
				break;

			default:
				throw new InvalidArgumentException( "Invalid mode '$mode' passed to '" . __METHOD__ );
		}

		return $id;
	}

	/**
	 * Given a string containing a space delimited list of ids, escape each id
	 * to match ids escaped by the escapeIdForAttribute() function.
	 *
	 * @since 1.27
	 *
	 * @param string $referenceString Space delimited list of ids
	 * @return string
	 */
	public static function escapeIdReferenceList( string $referenceString ): string {
		# Explode the space delimited list string into an array of tokens
		$references = preg_split( '/\s+/', "{$referenceString}", -1, PREG_SPLIT_NO_EMPTY );

		# Escape each token as an id
		foreach ( $references as &$ref ) {
			$ref = self::escapeIdForAttribute( $ref );
		}

		# Merge the array back to a space delimited list string
		# If the array is empty, the result will be an empty string ('')
		$referenceString = implode( ' ', $references );

		return $referenceString;
	}

	/**
	 * Normalizes whitespace in a section name, such as might be returned
	 * by Parser::stripSectionName(), for use in the ids that are used for
	 * section links.
	 *
	 * @param string $section
	 * @return string
	 */
	public static function normalizeSectionNameWhiteSpace( string $section ): string {
		return trim( preg_replace( '/[ _]+/', ' ', $section ) );
	}
}
