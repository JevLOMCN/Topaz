<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wikitext;

use Wikimedia\Parsoid\Utils\PHPUtils;

class Consts {
	public static $Media;
	public static $Sanitizer;
	public static $WikitextTagsWithTrimmableWS;
	public static $HTMLTagsRequiringSOLContext;
	public static $WTQuoteTags;
	public static $SolSpaceSensitiveTags;
	public static $HTML;
	public static $WTTagsWithNoClosingTags;
	public static $Output;
	public static $WtTagWidths;
	public static $ZeroWidthWikitextTags;
	public static $LCFlagMap;
	public static $LCNameMap;
	public static $blockElems;
	public static $antiBlockElems;
	public static $alwaysBlockElems;
	public static $neverBlockElems;
	public static $wikitextBlockElems;
	public static $strippedUrlCharacters;

	public static function init() {
		/*
		 * Valid media options:
		 * - Prefix options are of the form "alt=foo"
		 * - Simple options are of the form "center"
		 *
		 * See http:#en.wikipedia.org/wiki/Wikipedia:Extended_image_syntax
		 * for more information about how they are used.
		 */
		self::$Media = [
			'PrefixOptions' => [
				'img_link' => 'link',
				'img_alt' => 'alt',
				'img_page' => 'page',
				'img_lang' => 'lang', # see T34987
				'img_upright' => 'upright',
				'img_width' => 'width',
				'img_class' => 'class',
				'img_manualthumb' => 'manualthumb',

				'timedmedia_thumbtime' => 'thumbtime',
				'timedmedia_starttime' => 'start',
				'timedmedia_endtime' => 'end',
				'timedmedia_disablecontrols' => 'disablecontrols'  # See T135537
			],
			'SimpleOptions' => [
				# halign
				'img_left' => 'halign',
				'img_right' => 'halign',
				'img_center' => 'halign',
				'img_none' => 'halign',

				# valign
				'img_baseline' => 'valign',
				'img_sub' => 'valign',
				'img_super' => 'valign',
				'img_top' => 'valign',
				'img_text_top' => 'valign',
				'img_middle' => 'valign',
				'img_bottom' => 'valign',
				'img_text_bottom' => 'valign',

				# format
				# 'border' can be given in addition to *one of*
				# frameless, framed, or thumbnail
				'img_border' => 'border',
				'img_frameless' => 'format',
				'img_framed' => 'format',
				'img_thumbnail' => 'format',

				# Ha! Upright can be either one! Try parsing THAT!
				'img_upright' => 'upright',

				'timedmedia_loop' => 'loop', # T308230
				'timedmedia_muted' => 'muted', # T308230
			]
		];

		self::$Sanitizer = [
			# List of allowed tags that can be used as raw HTML in wikitext.
			# All other html/html-like tags will be spit out as text.
			'AllowedLiteralTags' => PHPUtils::makeSet( [
				# In case you were wondering, explicit <a .. > HTML is NOT allowed in wikitext.
				# That is why the <a> tag is missing from the allowed list.
				'abbr',
				'b', 'bdi', 'bdo', 'big', 'blockquote', 'br',
				'caption', 'center', 'cite', 'code',
				'data', 'dd', 'del', 'dfn', 'div', 'dl', 'dt',
				'em',
				'font',
				'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr',
				'i', 'ins',
				'kbd',
				'li',
				'mark',
				'ol',
				'p', 'pre',
				'q',
				'rb', 'rp', 'rt', 'rtc', 'ruby',
				's', 'samp', 'small', 'span', 'strike', 'strong', 'sub', 'sup',
				'table', 'td', 'th', 'time', 'tr', 'tt',
				'u', 'ul',
				'var',
				'wbr',
			] ),
		];

		/**
		 * These HTML tags come from native wikitext markup and
		 * (as long as they are not literal HTML tags in the wikitext source)
		 * should have whitespace trimmed from their content.
		 */
		self::$WikitextTagsWithTrimmableWS = PHPUtils::makeSet( [
			"h1", "h2", "h3", "h4", "h5", "h6",
			"ol", "li", "ul", "dd", "dl", "dt",
			"td", "th", "caption"
		] );

		# These HTML tags will be generated only if
		# the corresponding wikitext occurs in a SOL context.
		self::$HTMLTagsRequiringSOLContext = PHPUtils::makeSet( [
			"pre",
			"h1", "h2", "h3", "h4", "h5", "h6",
			"ol", "li", "ul", "dd", "dl", "dt",
		] );

		# These wikitext tags are composed with quote-chars.
		self::$WTQuoteTags = PHPUtils::makeSet( [ 'i', 'b' ] );

		// These are defined in the legacy parser's `BlockLevelPass`

		// Opens block scope when entering, closes when exiting
		self::$blockElems = PHPUtils::makeSet( [
			'table', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'pre', 'p', 'ul',
			'ol', 'dl'
		] );
		// Closes block scope when entering, opens when exiting
		self::$antiBlockElems = PHPUtils::makeSet( [ 'td', 'th' ] );
		// Opens block scope when entering, opens when exiting too
		self::$alwaysBlockElems = PHPUtils::makeSet( [
			'tr', 'caption', 'dt', 'dd', 'li'
		] );
		// Closes block scope when entering, closes when exiting too
		self::$neverBlockElems = PHPUtils::makeSet( [
			'center', 'blockquote', 'div', 'hr', 'figure', 'aside', // T278565
		] );

		self::$wikitextBlockElems = PHPUtils::makeSet( array_merge(
			array_keys( self::$blockElems ),
			array_keys( self::$antiBlockElems ),
			array_keys( self::$alwaysBlockElems ),
			array_keys( self::$neverBlockElems )
		) );

		self::$HTML = [
			# The list of HTML5 tags, mainly used for the identification of *non*-html tags.
			# Non-html tags terminate otherwise tag-eating rules in the tokenizer
			# to support potential extension tags.
			'HTML5Tags' => PHPUtils::makeSet( [
				"a", "abbr", "address", "area", "article",
				"aside", "audio", "b", "base", "bdi", "bdo", "blockquote",
				"body", "br", "button", "canvas", "caption", "cite", "code",
				"col", "colgroup", "data", "datalist", "dd", "del",
				"details", "dfn", "div", "dl", "dt", "em", "embed", "fieldset",
				"figcaption", "figure", "footer", "form",
				"h1", "h2", "h3", "h4", "h5", "h6", "head", "header", "hgroup",
				"hr", "html", "i", "iframe", "img", "input", "ins", "kbd", "keygen",
				"label", "legend", "li", "link", "map", "mark", "menu", "meta",
				"meter", "nav", "noscript", "object", "ol", "optgroup", "option",
				"output", "p", "param", "pre", "progress", "q", "rb", "rp", "rt",
				"rtc", "ruby", "s", "samp", "script", "section", "select", "small",
				"source", "span", "strong", "style", "sub", "summary", "sup",
				"table", "tbody", "td", "textarea", "tfoot", "th", "thead", "time",
				"title", "tr", "track", "u", "ul", "var", "video", "wbr",
			] ),

			/**
			 * https://html.spec.whatwg.org/multipage/dom.html#metadata-content-2
			 * @type {Set}
			 */
			'MetaDataTags' => PHPUtils::makeSet( [
				"base", "link", "meta", "noscript", "script", "style", "template", "title"
			] ),

			# From http://www.w3.org/TR/html5-diff/#obsolete-elements
			# SSS FIXME: basefont is missing here, but looks like the PHP parser
			# does not support it anyway and treats it as plain text.  So, skipping
			# this one in Parsoid as well.
			'OlderHTMLTags' => PHPUtils::makeSet( [
				"strike", "big", "center", "font", "tt",
			] ),

			# See http://www.w3.org/html/wg/drafts/html/master/syntax.html#formatting
			'FormattingTags' => PHPUtils::makeSet( [
				'a', 'b', 'big', 'code', 'em', 'font', 'i', 'nobr',
				's', 'small', 'strike', 'strong', 'tt', 'u',
			] ),

			/**
			 * From \\MediaWiki\Tidy\RemexCompatMunger::$onlyInlineElements
			 */
			'OnlyInlineElements' => PHPUtils::makeSet( [
				'a', 'abbr', 'acronym', 'applet', 'b', 'basefont', 'bdo',
				'big', 'br', 'button', 'cite', 'code', 'del', 'dfn', 'em',
				'font', 'i', 'iframe', 'img', 'input', 'ins', 'kbd', 'label',
				'legend', 'map', 'object', 'param', 'q', 'rb', 'rbc', 'rp',
				'rt', 'rtc', 'ruby', 's', 'samp', 'select', 'small', 'span',
				'strike', 'strong', 'sub', 'sup', 'textarea', 'tt', 'u', 'var',
				// Those defined in tidy.conf
				'video', 'audio', 'bdi', 'data', 'time', 'mark',
			] ),

			'ListTags' => PHPUtils::makeSet( [ 'ul', 'ol', 'dl' ] ),

			'ListItemTags' => PHPUtils::makeSet( [ 'li', 'dd', 'dt' ] ),

			'FosterablePosition' => PHPUtils::makeSet( [ 'table', 'thead', 'tbody', 'tfoot', 'tr' ] ),

			'TableContentModels' => [
				'table' => [ 'caption', 'colgroup', 'thead', 'tbody', 'tr', 'tfoot' ],
				'thead' => [ 'tr' ],
				'tbody' => [ 'tr' ],
				'tfoot' => [ 'tr' ],
				'tr'    => [ 'td', 'th' ]
			],

			'TableTags' => PHPUtils::makeSet( [
				'table', 'tbody', 'thead', 'tfoot', 'caption', 'th', 'tr', 'td',
			] ),

			# Table tags that can be children
			'ChildTableTags' => PHPUtils::makeSet( [
				"tbody", "thead", "tfoot", "tr", "caption", "th", "td",
			] ),

			# See https://html.spec.whatwg.org/#void-elements
			'VoidTags' => PHPUtils::makeSet( [
				'area', 'base', 'br', 'col', 'embed', 'hr', 'img',
				'input', 'link', 'meta', 'param', 'source',
				'track', 'wbr',
			] ),

			# HTML5 elements with raw (unescaped) content
			'RawTextElements' => PHPUtils::makeSet( [
				'style', 'script', 'xmp', 'iframe', 'noembed', 'noframes',
				'plaintext', 'noscript',
			] ),
		];

		/**
		 * These HTML tags have native wikitext representations.
		 * The wikitext equivalents do not have closing tags.
		 * @type {Set}
		 */
		self::$WTTagsWithNoClosingTags = PHPUtils::makeSet( [
			"pre", "li", "dt", "dd", "hr", "tr", "td", "th"
		] );

		self::$Output = [
			'FlaggedEmptyElts' => PHPUtils::makeSet( [
				'li', 'tr', 'p',
			] ),
		];

		# Known wikitext tag widths -- these are known statically
		# but other widths are computed or updated based on actual wikitext usage
		self::$WtTagWidths = [
			"body" => [ 0, 0 ],
			"html" => [ 0, 0 ],
			"head" => [ 0, 0 ],
			"p" => [ 0, 0 ],
			"meta" => [ 0, 0 ],
			// @see PreHandler::newIndentPreWS() for why opening width is 0, not 1
			"pre" => [ 0, 0 ],
			"ol" => [ 0, 0 ],
			"ul" => [ 0, 0 ],
			"dl" => [ 0, 0 ],
			"li" => [ 1, 0 ],
			"dt" => [ 1, 0 ],
			"dd" => [ 1, 0 ],
			"h1" => [ 1, 1 ],
			"h2" => [ 2, 2 ],
			"h3" => [ 3, 3 ],
			"h4" => [ 4, 4 ],
			"h5" => [ 5, 5 ],
			"h6" => [ 6, 6 ],
			"hr" => [ 4, 0 ],
			"table" => [ 2, 2 ],
			"tbody" => [ 0, 0 ],
			"thead" => [ 0, 0 ],
			"tfoot" => [ 0, 0 ],
			"tr" => [ null, 0 ],
			"td" => [ null, 0 ],
			"th" => [ null, 0 ],
			"b" => [ 3, 3 ],
			"i" => [ 2, 2 ],
			"br" => [ 0, 0 ],
			"figure" => [ 2, 2 ],
			"figcaption" => [ 0, 0 ],
		];

		# HTML tags whose wikitext equivalents are zero-width.
		# This information is derived from WtTagWidths and set below.
		self::$ZeroWidthWikitextTags = PHPUtils::makeSet( [] );

		# Map LanguageConverter wikitext flags to readable JSON field names.
		self::$LCFlagMap = [
			# These first three flags are used internally during flag processing,
			# but should never appear in the output wikitext, so we prepend them
			# with '$'.

			# 'S': Show converted text
			'$S' => 'show',
			# '+': Add conversion rule
			'$+' => 'add',
			# 'E': Error in the given flags
			'$E' => 'error',

			# These rest of these are valid flags in wikitext.

			# 'A': add conversion rule *and show converted text* (implies S)
			'A' => 'add',
			# 'T': Convert and override page title
			'T' => 'title',
			# 'R': Disable language conversion (exclusive flag)
			'R' => 'disabled',
			# 'D': Describe conversion rule (without adding to table)
			'D' => 'describe',
			# '-': Remove existing conversion rule (exclusive flag)
			'-' => 'remove',
			# 'H': add rule for convert code (but no display in placed code )
			'H' => '', # this is handled implicitly as a lack of 'show'
			# 'N': Output current variant name (exclusive flag)
			'N' => 'name',
		];

		# Map JSON field names to LanguageConverter wikitext flags.
		# This information is derived from LCFlagMap and set below.
		self::$LCNameMap = [];

		# Derived information from 'WtTagWidths'
		foreach ( self::$WtTagWidths as $tag => $widths ) {
			# This special case can be fixed by maybe removing them WtTagWidths.
			# They may no longer be necessary -- to be investigated in another patch.
			if ( $tag !== 'html' && $tag !== 'head' && $tag !== 'body' ) {
				if ( $widths[0] === 0 && $widths[1] === 0 ) {
					// @see explanation in PreHandler::newIndentPreWS()
					// to understand this special case
					if ( $tag !== 'pre' ) {
						self::$ZeroWidthWikitextTags[$tag] = true;
					}
				}
			}
		}

		# Derived information from `LCFlagMap`
		foreach ( self::$LCFlagMap as $k => $v ) {
			if ( $v ) {
				self::$LCNameMap[$v] = $k;
			}
		}

		# Handle ambiguity in inverse mapping.
		self::$LCNameMap['add'] = 'A';

		/*
		 * These characters are not considered to be part of a URL if they are the last
		 * character of a raw URL when converting it to an HTML link
		 * Right bracket would also be in that set, but only if there's no left bracket in the URL;
		 * see TokenizerUtils::getAutoUrlTerminatingChars.
		 */
		self::$strippedUrlCharacters = ',;\.:!?';
	}
}

Consts::init();
