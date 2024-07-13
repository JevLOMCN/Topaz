<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt;

use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Tokens\EndTagTk;
use Wikimedia\Parsoid\Tokens\EOFTk;
use Wikimedia\Parsoid\Tokens\SourceRange;
use Wikimedia\Parsoid\Tokens\TagTk;
use Wikimedia\Parsoid\Tokens\Token;
use Wikimedia\Parsoid\Utils\DiffDOMUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\TokenUtils;
use Wikimedia\Parsoid\Utils\Utils;
use Wikimedia\Parsoid\Utils\WTUtils;
use Wikimedia\Parsoid\Wikitext\Consts;
use Wikimedia\Parsoid\Wt2Html\PegTokenizer;

class WikitextEscapeHandlers {

	private const LINKS_ESCAPE_RE = '/(\[\[)|(\]\])|(-\{)|(^[^\[]*\]$)/D';

	/** @var Env */
	private $env;

	/** @var ?string */
	private $extName;

	/**
	 * @var PegTokenizer
	 */
	private $tokenizer;

	public function __construct( Env $env, ?string $extName ) {
		$this->env = $env;
		$this->extName = $extName;
		$this->tokenizer = new PegTokenizer( $env );
	}

	/**
	 * Ignore the cases where the serializer adds newlines not present in the dom
	 * @param Node $node
	 * @return bool
	 */
	private static function startsOnANewLine( Node $node ): bool {
		$name = DOMCompat::nodeName( $node );
		return TokenUtils::tagOpensBlockScope( $name ) &&
			!WTUtils::isLiteralHTMLNode( $node );
	}

	/**
	 * Look ahead on current line for block content
	 *
	 * @param Node $node
	 * @param bool $first
	 * @return bool
	 */
	private static function hasBlocksOnLine( Node $node, bool $first ): bool {
		// special case for firstNode:
		// we're at sol so ignore possible \n at first char
		if ( $first ) {
			$textContent = $node->textContent;
			$offset = strlen( $textContent ) ? 1 : 0;
			if ( strpos( $textContent, "\n", $offset ) !== false ) {
				return false;
			}
			$node = $node->nextSibling;
		}

		while ( $node ) {
			if ( $node instanceof Element ) {
				if ( DOMUtils::isWikitextBlockNode( $node ) ) {
					return !self::startsOnANewLine( $node );
				}
				if ( $node->hasChildNodes() ) {
					if ( self::hasBlocksOnLine( $node->firstChild, false ) ) {
						return true;
					}
				}
			} else {
				if ( str_contains( $node->textContent, "\n" ) ) {
					return false;
				}
			}
			$node = $node->nextSibling;
		}
		return false;
	}

	/**
	 * @param string $text
	 * @param array $opts [ 'node' => Node ]
	 * @return bool
	 */
	private static function hasLeadingEscapableQuoteChar( string $text, array $opts ): bool {
		/** @var Node $node */
		$node = $opts['node'];
		// Use 'node.textContent' to do the tests since it hasn't had newlines
		// stripped out from it.
		// Ex: For this DOM: <i>x</i>\n'\n<i>y</i>
		// node.textContent = \n'\n and text = '
		// Those newline separators can prevent unnecessary <nowiki/> protection
		// if the string begins with one or more newlines before a leading quote.
		$origText = $node->textContent;
		if ( substr( $origText, 0, 1 ) === "'" ) {
			$prev = DiffDOMUtils::previousNonDeletedSibling( $node );
			if ( !$prev ) {
				$prev = $node->parentNode;
			}
			if ( DOMUtils::isQuoteElt( $prev ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $text
	 * @param array $opts [ 'node' => Node ]
	 * @return bool
	 */
	private static function hasTrailingEscapableQuoteChar( string $text, array $opts ): bool {
		$node = $opts['node'];
		// Use 'node.textContent' to do the tests since it hasn't had newlines
		// stripped out from it.
		// Ex: For this DOM: <i>x</i>\n'\n<i>y</i>
		// node.textContent = \n'\n and text = '
		// Those newline separators can prevent unnecessary <nowiki/> protection
		// if the string ends with a trailing quote and then one or more newlines.
		$origText = $node->textContent;
		if ( substr( $origText, -1 ) === "'" ) {
			$next = DiffDOMUtils::nextNonDeletedSibling( $node );
			if ( !$next ) {
				$next = $node->parentNode;
			}
			if ( DOMUtils::isQuoteElt( $next ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * SSS FIXME: By doing a DOM walkahead to identify what else is on the current line,
	 * these heuristics can be improved. Ex: '<i>foo</i> blah blah does not require a
	 * <nowiki/> after the single quote since we know that there are no other quotes on
	 * the rest of the line that will be emitted. Similarly, '' does not need a <nowiki>
	 * wrapper since there are on other quote chars on the line.
	 *
	 * This is checking text-node siblings of i/b tags.
	 *
	 * @param SerializerState $state
	 * @param string $text
	 * @param array $opts [ 'node' => Node ]
	 * @return string
	 */
	private static function escapedIBSiblingNodeText(
		SerializerState $state, string $text, array $opts
	): string {
		// For a sequence of 2+ quote chars, we have to
		// fully wrap the sequence in <nowiki>...</nowiki>
		// <nowiki/> at the start and end doesn't work.
		//
		// Ex: ''<i>foo</i> should serialize to <nowiki>''</nowiki>''foo''.
		//
		// Serializing it to ''<nowiki/>''foo'' breaks html2html semantics
		// since it will parse back to <i><meta../></i>foo<i></i>
		if ( preg_match( "/''+/", $text ) ) {
			// Minimize the length of the string that is wrapped in <nowiki>.
			$pieces = explode( "'", $text );
			$first = array_shift( $pieces );
			$last = array_pop( $pieces );
			return $first . "<nowiki>'" . implode( "'", $pieces ) . "'</nowiki>" . $last;
		}

		// Check whether the head and/or tail of the text needs <nowiki/> protection.
		$out = '';
		if ( self::hasTrailingEscapableQuoteChar( $text, $opts ) ) {
			$state->hasQuoteNowikis = true;
			$out = $text . '<nowiki/>';
		}

		if ( self::hasLeadingEscapableQuoteChar( $text, $opts ) ) {
			$state->hasQuoteNowikis = true;
			$out = '<nowiki/>' . ( $out ?: $text );
		}

		return $out;
	}

	public function isFirstContentNode( Node $node ): bool {
		// Skip deleted-node markers
		return DiffDOMUtils::previousNonDeletedSibling( $node ) === null;
	}

	/**
	 * @param Node $liNode
	 * @param SerializerState $state
	 * @param string $text
	 * @param array $opts [ 'node' => Node ]
	 * @return bool
	 */
	public function liHandler(
		Node $liNode, SerializerState $state, string $text, array $opts
	): bool {
		/** @var Node $node */
		$node = $opts['node'];
		if ( $node->parentNode !== $liNode ) {
			return false;
		}

		// For <dt> nodes, ":" trigger nowiki outside of elements
		// For first nodes of <li>'s, bullets in sol posn trigger escaping
		if ( DOMCompat::nodeName( $liNode ) === 'dt' && str_contains( $text, ':' ) ) {
			return true;
		} elseif ( preg_match( '/^[#*:;]*$/D', $state->currLine->text ) &&
			$this->isFirstContentNode( $node )
		) {
			// Wikitext styling might require whitespace insertion after list bullets.
			// In those scenarios, presence of bullet-wiktext in the text node is okay.
			// Hence the check for /^[#*:;]*$/ above.
			return (bool)strspn( $text, '#*:;', 0, 1 );
		} else {
			return false;
		}
	}

	/**
	 * @param Node $thNode
	 * @param SerializerState $state
	 * @param string $text
	 * @param array $opts [ 'node' => Node ]
	 * @return bool
	 */
	public function thHandler(
		Node $thNode, SerializerState $state, string $text, array $opts
	): bool {
		// {|
		// !a<div>!!b</div>
		// !c<div>||d</div>
		// |}
		//
		// The <div> will get split across two <th> tags because
		// the !! and | has higher precedence in the tokenizer.
		//
		// So, no matter where in the DOM subtree of the <th> node
		// that text shows up in, we have to unconditionally escape
		// the !! and | characters.
		//
		// That is, so long as it serializes to the same line as the
		// heading was started.
		return preg_match( '/^\s*!/', $state->currLine->text ) &&
			preg_match( '/^[^\n]*!!|\|/', $text );
	}

	public function mediaOptionHandler( SerializerState $state, string $text ): bool {
		return str_contains( $text, '|' ) || preg_match( self::LINKS_ESCAPE_RE, $text );
	}

	public function wikilinkHandler( SerializerState $state, string $text ): bool {
		return (bool)preg_match( self::LINKS_ESCAPE_RE, $text );
	}

	public function aHandler( SerializerState $state, string $text ): bool {
		return str_contains( $text, ']' );
	}

	/**
	 * @param Node $tdNode
	 * @param bool $inWideTD
	 * @param SerializerState $state
	 * @param string $text
	 * @param array $opts [ 'node' => ?Node ]
	 * @return bool
	 */
	public function tdHandler(
		Node $tdNode, bool $inWideTD, SerializerState $state, string $text, array $opts
	): bool {
		$node = $opts['node'] ?? null;
		/*
		 * "|" anywhere in a text node of the <td> subtree can be trouble!
		 * It is not sufficient to just look at immediate child of <td>
		 * Try parsing the following table:
		 *
		 * {|
		 * |a''b|c''
		 * |}
		 *
		 * Similarly, "-" or "+" when emitted after a "|" in sol position
		 * is trouble, but in addition to showing up as the immediate first
		 * child of tdNode, they can appear on the leftmost path from
		 * tdNode as long as the path only has nodes don't emit any wikitext.
		 * Ex: <td><p>-</p></td>, but not: <td><small>-</small></td>
		 */

		// If 'text' is on the same wikitext line as the "|" corresponding
		// to the <td>
		// * | in a td should be escaped
		// * +-} in SOL position (if they show up on the leftmost path with
		// only zero-wt-emitting nodes on that path)
		if ( !$node || $state->currLine->firstNode === $tdNode ) {
			if ( str_contains( $text, '|' ) ) {
				return true;
			}
			if ( !$inWideTD &&
				$state->currLine->text === '|' &&
				strspn( $text, '-+}', 0, 1 ) &&
				$node
			) {
				$n = $node;
				while ( $n && $n !== $tdNode ) {
					if ( !$this->isFirstContentNode( $n ) ||
						!( $n === $node || WTUtils::isZeroWidthWikitextElt( $n ) ) ) {
						return false;
					}
					$n = $n->parentNode;
				}
				return true;
			}
		}
		return false;
	}

	/**
	 * Tokenize string and pop EOFTk
	 *
	 * @param string $str
	 * @param bool $sol
	 * @return array
	 */
	public function tokenizeStr( string $str, bool $sol ): array {
		$tokens = $this->tokenizer->tokenizeSync( $str, [ 'sol' => $sol ] );
		Assert::invariant(
			array_pop( $tokens ) instanceof EOFTk,
			'Expected EOF token!'
		);
		return $tokens;
	}

	public function textCanParseAsLink( Node $node, SerializerState $state, string $text ): bool {
		$env = $state->getEnv();
		$env->log(
			'trace/wt-escape', 'link-test-text=',
			static function () use ( $text ) {
				return PHPUtils::jsonEncode( $text );
			}
		);

		// Strip away extraneous characters after a ]] or a ]
		// They are inessential to the test of whether the ]]/]
		// will get parsed into a wikilink and only complicate
		// the logic (needing to ignore entities, etc.).
		$text = preg_replace( '/\][^\]]*$/D', ']', $text, 1 );

		// text only contains ']' chars.
		// Since we stripped everything after ']' above, if a newline is
		// present, a link would have to straddle newlines which is not valid.
		if ( str_contains( $text, "\n" ) ) {
			return false;
		}

		$str = $state->currLine->text . $text;
		$tokens = $this->tokenizeStr( $str, false ); // sol state is irrelevant here
		$n = count( $tokens );
		$lastToken = $tokens[$n - 1];

		$env->log( 'trace/wt-escape', 'str=', $str, ';tokens=', $tokens );

		// If 'text' remained outside of any non-string tokens,
		// it does not need nowiking.
		if ( $lastToken === $text ||
			( is_string( $lastToken ) &&
				$text === substr( $lastToken, -strlen( $text ) )
			)
		) {
			return false;
		}

		// Verify that the tokenized links are valid links
		$buf = '';
		for ( $i = $n - 1;  $i >= 0;  $i-- ) {
			$t = $tokens[$i];
			if ( is_string( $t ) ) {
				$buf = $t . $buf;
			} elseif ( $t->getName() === 'wikilink' ) {
				$target = $t->getAttributeV( 'href' );
				if ( is_array( $target ) ) {
					// FIXME: in theory template expansion *could* make this a link.
					return false;
				}
				if ( $env->isValidLinkTarget( $target ) &&
					!$env->getSiteConfig()->hasValidProtocol( $target )
				) {
					return true;
				}

				// Assumes 'src' will always be present which it seems to be.
				// Tests will fail if anything changes in the tokenizer.
				$buf = $t->dataParsoid->src . $buf;
			} elseif ( $t->getName() === 'extlink' ) {
				// Check if the extlink came from a template which in the end
				// would not really parse as an extlink.

				$href = $t->getAttributeV( 'href' );
				if ( is_array( $href ) ) {
					$href = $href[0];
				}

				if ( !TokenUtils::isTemplateToken( $href ) ) {
					// Not a template and a real href => needs nowiking
					if ( is_string( $href ) && preg_match( '#https?://#', $href ) ) {
						return true;
					}
				} else {
					while ( $node ) {
						$node = DiffDOMUtils::previousNonSepSibling( $node );
						if ( $node && WTUtils::isFirstEncapsulationWrapperNode( $node ) ) {
							// FIXME: This is not entirely correct.
							// Assumes that extlink content doesn't have templates.
							// Solution: Count # of non-nested templates encountered
							// and skip over intermediate templates.
							// var content = t.getAttribute('mw:content');
							// var n = intermediateNonNestedTemplates(content);
							break;
						}
					}

					if ( $node instanceof Element && DOMCompat::nodeName( $node ) === 'a' &&
						$node->textContent === DOMCompat::getAttribute( $node, 'href' )
					) {
						// The template expands to an url link => needs nowiking
						return true;
					}
				}

				// Since this will not parse to a real extlink,
				// update buf with the wikitext src for this token.
				$tsr = $t->dataParsoid->tsr;
				$buf = $tsr->substr( $str ) . $buf;
			} else {
				// We have no other smarts => be conservative.
				return true;
			}

			if ( $text === substr( $buf, -strlen( $text ) ) ) {
				// 'text' emerged unscathed
				return false;
			}
		}

		// We couldn't prove safety of skipping nowiki-ing.
		return true;
	}

	private function hasWikitextTokens(
		SerializerState $state, bool $onNewline, string $text
	): bool {
		$env = $state->getEnv();
		$env->log(
			'trace/wt-escape', 'nl:', $onNewline, ':text=',
			static function () use ( $text ) {
				return PHPUtils::jsonEncode( $text );
			}
		);

		// tokenize the text
		$sol = $onNewline && !( $state->inIndentPre || $state->inPHPBlock );

		// If we're inside a <pre>, we need to add an extra space after every
		// newline so that the tokenizer correctly parses all tokens in a pre
		// instead of just the first one. See T95794.
		if ( $state->inIndentPre ) {
			$text = str_replace( "\n", "\n ", $text );
		}

		$tokens = $this->tokenizeStr( $text, $sol );

		// If the token stream has a TagTk, SelfclosingTagTk, EndTagTk or CommentTk
		// then this text needs escaping!
		$numEntities = 0;
		foreach ( $tokens as $t ) {
			$env->log(
				'trace/wt-escape', 'T:',
				static function () use ( $t ) {
					return PHPUtils::jsonEncode( $t );
				}
			);

			$tc = TokenUtils::getTokenType( $t );

			// Ignore html tags that aren't allowed as literals in wikitext
			if ( TokenUtils::isHTMLTag( $t ) ) {
				if (
					TokenUtils::matchTypeOf( $t, '#^mw:Extension(/|$)#' ) &&
					( $this->extName !== $t->getAttributeV( 'name' ) )
				) {
					return true;
				}

				// Always escape isolated extension tags (T59469). Consider this:
				// echo "&lt;ref&gt;foo<p>&lt;/ref&gt;</p>" | node parse --html2wt
				// The <ref> and </ref> tag-like text is spread across the DOM, and in
				// the worst case can be anywhere. So, we conservatively escape these
				// elements always (which can lead to excessive nowiki-escapes in some
				// cases, but is always safe).
				if ( ( $tc === 'TagTk' || $tc === 'EndTagTk' ) &&
					$env->getSiteConfig()->isExtensionTag( mb_strtolower( $t->getName() ) )
				) {
					return true;
				}

				// If the tag is one that's allowed in wikitext, we need to escape
				// it inside <nowiki>s, because a text node nodeValue always returns
				// non-escaped entities (that is, converts "&lt;h2&gt;" to "<h2>").
				// TODO: We should also do this for <a> tags because even if they
				// aren't allowed in wikitext and thus don't need to be escaped, the
				// result can be confusing for editors. However, doing it here in a
				// simple way interacts badly with normal link escaping, so it's
				// left for later.
				if ( isset( Consts::$Sanitizer['AllowedLiteralTags'][mb_strtolower( $t->getName() )] ) ) {
					return true;
				} else {
					continue;
				}
			}

			if ( $tc === 'SelfclosingTagTk' ) {
				// * Ignore RFC/ISBN/PMID tokens when those are encountered in the
				// context of another link's content -- those are not parsed to
				// ext-links in that context. (T109371)
				if ( ( $t->getName() === 'extlink' || $t->getName() === 'wikilink' ) &&
					( $t->dataParsoid->stx ?? null ) === 'magiclink' &&
					( $state->inAttribute || $state->inLink ) ) {
					continue;
				}

				// Ignore url links in attributes (href, mostly)
				// since they are not in danger of being autolink-ified there.
				if ( $t->getName() === 'urllink' && ( $state->inAttribute || $state->inLink ) ) {
					continue;
				}

				if ( $t->getName() === 'wikilink' ) {
					if ( $env->isValidLinkTarget( $t->getAttributeV( 'href' ) ?? '' ) ) {
						return true;
					} else {
						continue;
					}
				}

				return true;
			}

			if ( $state->inCaption && $tc === 'TagTk' && $t->getName() === 'listItem' ) {
				continue;
			}

			if ( $tc === 'TagTk' ) {
				// Ignore mw:Entity tokens
				if ( $t->getName() === 'span' && TokenUtils::hasTypeOf( $t, 'mw:Entity' ) ) {
					$numEntities++;
					continue;
				}

				// Ignore table tokens outside of tables
				if ( in_array( $t->getName(), [ 'caption', 'td', 'tr', 'th' ], true ) &&
					!TokenUtils::isHTMLTag( $t ) &&
					$state->wikiTableNesting === 0
				) {
					continue;
				}

				// Headings have both SOL and EOL requirements. This tokenization
				// here only verifies SOL requirements, not EOL requirements.
				// So, record this information so that we can strip unnecessary
				// nowikis after the fact.
				if ( preg_match( '/^h\d$/D', $t->getName() ) ) {
					$state->hasHeadingEscapes = true;
				}

				return true;
			}

			if ( $tc === 'EndTagTk' ) {
				// Ignore mw:Entity tokens
				if ( $numEntities > 0 && $t->getName() === 'span' ) {
					$numEntities--;
					continue;
				}
				// Ignore heading tokens
				if ( preg_match( '/^h\d$/D', $t->getName() ) ) {
					continue;
				}

				// Ignore table tokens outside of tables
				if ( isset( ( [ 'caption' => 1, 'table' => 1 ] )[$t->getName()] ) &&
					$state->wikiTableNesting === 0
				) {
					continue;
				}

				// </br>!
				if ( mb_strtolower( $t->getName() ) === 'br' ) {
					continue;
				}

				return true;
			}
		}

		return false;
	}

	private static function nowikiWrap(
		string $str, bool $close, bool &$inNowiki, bool &$nowikisAdded, string &$buf
	): void {
		if ( !$inNowiki ) {
			$buf .= '<nowiki>';
			$inNowiki = true;
			$nowikisAdded = true;
		}
		$buf .= $str;
		if ( $close ) {
			$buf .= '</nowiki>';
			$inNowiki = false;
		}
	}

	/**
	 * This function attempts to wrap smallest escapable units into
	 * nowikis (which can potentially add multiple nowiki pairs in a
	 * single string).  The idea here is that since this should all be
	 * text, anything that tokenizes to another construct needs to be
	 * wrapped.
	 *
	 * Full-wrapping is enabled if the string is being escaped within
	 * context-specific handlers where the tokenization context might
	 * be different from what we use in this code.
	 *
	 * @param SerializerState $state
	 * @param bool $sol
	 * @param string $origText
	 * @param bool $fullWrap
	 * @param bool $dontWrapIfUnnecessary
	 * @return string
	 */
	public function escapedText(
		SerializerState $state, bool $sol, string $origText,
		bool $fullWrap = false, bool $dontWrapIfUnnecessary = false
	): string {
		Assert::invariant(
			preg_match( '/^(.*?)((?:\r?\n)*)$/sD', $origText, $match ),
			"Escaped text matching failed: {$origText}"
		);

		$text = $match[1];
		$nls = $match[2];

		if ( $fullWrap ) {
			return '<nowiki>' . $text . '</nowiki>' . $nls;
		}

		$buf = '';
		$inNowiki = false;
		$nowikisAdded = false;
		// These token types don't come with a closing tag
		$tokensWithoutClosingTag = PHPUtils::makeSet( [ 'listItem', 'td', 'tr' ] );

		// reverse escaping nowiki tags
		// we do this so that they tokenize as nowikis
		// instead of entity enclosed text
		$text = preg_replace( '#&lt;(/?nowiki\s*/?\s*)&gt;#i', '<$1>', $text );

		$tokens = $this->tokenizeStr( $text, $sol );

		foreach ( $tokens as $t ) {
			if ( is_string( $t ) ) {
				if ( strlen( $t ) > 0 ) {
					$t = WTSUtils::escapeNowikiTags( $t );
					if ( !$inNowiki && ( ( $sol && $t[0] === ' ' ) || str_contains( $t, "\n " ) ) ) {
						$x = preg_split( '/(^|\n) /', $t, -1, PREG_SPLIT_DELIM_CAPTURE );
						$buf .= $x[0];
						$lastIndexX = count( $x ) - 1;
						for ( $k = 1;  $k < $lastIndexX;  $k += 2 ) {
							$buf .= $x[$k];
							if ( $k !== 1 || $x[$k] === "\n" || $sol ) {
								self::nowikiWrap( ' ', true, $inNowiki, $nowikisAdded, $buf );
							} else {
								$buf .= ' ';
							}
							$buf .= $x[$k + 1];
						}
					} else {
						$buf .= $t;
					}
					$sol = false;
				}
				continue;
			}

			$tsr = $t->dataParsoid->tsr ?? null;
			if ( !( $tsr instanceof SourceRange ) ) {
				$env = $state->getEnv();
				$env->log(
					'error/html2wt/escapeNowiki',
					'Missing tsr for token ',
					PHPUtils::jsonEncode( $t ),
					'while processing text ',
					$text
				);

				// Bail and wrap the whole thing in a nowiki
				// if we have missing information.
				// Use match[1] since text has been clobbered above.
				return '<nowiki>' . $match[1] . '</nowiki>' . $nls;
			}

			// Now put back the escaping we removed above
			$tSrc = WTSUtils::escapeNowikiTags( $tsr->substr( $text ) );
			switch ( TokenUtils::getTokenType( $t ) ) {
				case 'NlTk':
					$buf .= $tSrc;
					$sol = true;
					break;
				case 'CommentTk':
					// Comments are sol-transparent
					$buf .= $tSrc;
					break;
				case 'TagTk':
					// Treat tokens with missing tags as self-closing tokens
					// for the purpose of minimal nowiki escaping
					self::nowikiWrap(
						$tSrc,
						isset( $tokensWithoutClosingTag[$t->getName()] ),
						$inNowiki,
						$nowikisAdded,
						$buf
					);
					$sol = false;
					break;
				case 'EndTagTk':
					self::nowikiWrap( $tSrc, true, $inNowiki, $nowikisAdded, $buf );
					$sol = false;
					break;
				case 'SelfclosingTagTk':
					if ( $t->getName() !== 'meta' ||
						!TokenUtils::hasTypeOf( $t, 'mw:EmptyLine' )
					) {
						// Don't bother with marker or empty-line metas
						self::nowikiWrap( $tSrc, true, $inNowiki, $nowikisAdded, $buf );
					}
					$sol = false;
					break;
			}
		}

		// close any unclosed nowikis
		if ( $inNowiki ) {
			$buf .= '</nowiki>';
		}

		// Make sure nowiki is always added
		// Ex: "foo]]" won't tokenize into tags at all
		if ( !$nowikisAdded && !$dontWrapIfUnnecessary ) {
			$buf = '';
			self::nowikiWrap( $text, true, $inNowiki, $nowikisAdded, $buf );
		}

		$buf .= $nls;
		return $buf;
	}

	/**
	 * @param SerializerState $state
	 * @param string $text
	 * @param array $opts [ 'node' => Node, 'inMultilineMode' => ?bool, 'isLastChild' => ?bool ]
	 * @return string
	 */
	public function escapeWikitext( SerializerState $state, string $text, array $opts ): string {
		$env = $state->getEnv();
		$env->log(
			'trace/wt-escape', 'EWT:',
			static function () use ( $text ) {
				return PHPUtils::jsonEncode( $text );
			}
		);

		/* -----------------------------------------------------------------
		 * General strategy: If a substring requires escaping, we can escape
		 * the entire string without further analysis of the rest of the string.
		 * ----------------------------------------------------------------- */

		$hasMagicWord = preg_match( '/(^|\W)(RFC|ISBN|PMID)\s/', $text );
		$hasAutolink = $env->getSiteConfig()->findValidProtocol( $text );
		$fullCheckNeeded = !$state->inLink && ( $hasMagicWord || $hasAutolink );
		$hasQuoteChar = false;
		$indentPreUnsafe = false;
		$hasNonQuoteEscapableChars = false;
		$indentPreSafeMode = $state->inIndentPre || $state->inPHPBlock;
		$sol = $state->onSOL && !$indentPreSafeMode;

		// Fast path for special protected characters.
		if ( $state->protect && preg_match( $state->protect, $text ) ) {
			return $this->escapedText( $state, $sol, $text );
		}

		if ( !$fullCheckNeeded ) {
			$hasQuoteChar = str_contains( $text, "'" );
			$indentPreUnsafe = !$indentPreSafeMode && (
				preg_match( '/\n +[^\r\n]*?\S+/', $text ) ||
				$sol && preg_match( '/^ +[^\r\n]*?\S+/', $text )
			);
			$hasNonQuoteEscapableChars = preg_match( '/[<>\[\]\-\+\|!=#\*:;~{}]|__[^_]*__/', $text );
			$hasLanguageConverter = preg_match( '/-\{|\}-/', $text );
			if ( $hasLanguageConverter ) {
				$fullCheckNeeded = true;
			}
		}

		// Quick check for the common case (useful to kill a majority of requests)
		//
		// Pure white-space or text without wt-special chars need not be analyzed
		if ( !$fullCheckNeeded && !$hasQuoteChar && !$indentPreUnsafe && !$hasNonQuoteEscapableChars ) {
			$env->log( 'trace/wt-escape', '---No-checks needed---' );
			return $text;
		}

		// Context-specific escape handler
		$wteHandler = PHPUtils::lastItem( $state->wteHandlerStack );
		if ( $wteHandler && $wteHandler( $state, $text, $opts ) ) {
			$env->log( 'trace/wt-escape', '---Context-specific escape handler---' );
			return $this->escapedText( $state, false, $text, true );
		}

		// Quote-escape test
		if ( str_contains( $text, "''" ) ||
			self::hasLeadingEscapableQuoteChar( $text, $opts ) ||
			self::hasTrailingEscapableQuoteChar( $text, $opts )
		) {
			// Check if we need full-wrapping <nowiki>..</nowiki>
			// or selective <nowiki/> escaping for quotes.
			if ( $fullCheckNeeded ||
				$indentPreUnsafe ||
				( $hasNonQuoteEscapableChars &&
					$this->hasWikitextTokens( $state, $sol, $text )
				)
			) {
				$env->log( 'trace/wt-escape', '---quotes: escaping text---' );
				// If the reason for full wrap is that the text contains non-quote
				// escapable chars, it's still possible to minimize the contents
				// of the <nowiki> (T71950).
				return $this->escapedText( $state, $sol, $text );
			} else {
				$quoteEscapedText = self::escapedIBSiblingNodeText( $state, $text, $opts );
				if ( $quoteEscapedText ) {
					$env->log( 'trace/wt-escape', '---sibling of i/b tag---' );
					return $quoteEscapedText;
				}
			}
		}

		// Template and template-arg markers are escaped unconditionally!
		// Conditional escaping requires matching brace pairs and knowledge
		// of whether we are in template arg context or not.
		if ( preg_match( '/\{\{\{|\{\{|\}\}\}|\}\}/', $text ) ) {
			$env->log( 'trace/wt-escape', '---Unconditional: transclusion chars---' );
			return $this->escapedText( $state, false, $text );
		}

		// Once we eliminate the possibility of multi-line tokens, split the text
		// around newlines and escape each line separately.
		if ( preg_match( '/\n./', $text ) ) {
			$env->log( 'trace/wt-escape', '-- <multi-line-escaping-mode> --' );
			// We've already processed the full string in a context-specific handler.
			// No more additional processing required. So, push/pop a null handler.
			$state->wteHandlerStack[] = null;

			$tmp = [];
			foreach ( explode( "\n", $text ) as $i => $line ) {
				if ( $i > 0 ) {
					// Update state
					$state->onSOL = true;
					$state->currLine->text = '';
					$opts['inMultilineMode'] = true;
				}
				$tmp[] = $this->escapeWikitext( $state, $line, $opts );
			}
			$ret = implode( "\n", $tmp );

			array_pop( $state->wteHandlerStack );

			// If nothing changed, check if the original multiline string has
			// any wikitext tokens (ex: multi-line html tags <div\n>foo</div\n>).
			if ( $ret === $text && $this->hasWikitextTokens( $state, $sol, $text ) ) {
				$env->log( 'trace/wt-escape', '---Found multi-line wt tokens---' );
				$ret = $this->escapedText( $state, $sol, $text );
			}

			$env->log( 'trace/wt-escape', '-- </multi-line-escaping-mode> --' );
			return $ret;
		}

		$env->log(
			'trace/wt-escape', 'SOL:', $sol,
			static function () use ( $text ) {
				return PHPUtils::jsonEncode( $text );
			}
		);

		$hasTildes = preg_match( '/~{3,5}/', $text );
		if ( !$fullCheckNeeded && !$hasTildes ) {
			// {{, {{{, }}}, }} are handled above.
			// Test 1: '', [], <>, __FOO__ need escaping wherever they occur
			// = needs escaping in end-of-line context
			// Test 2: {|, |}, ||, |-, |+,  , *#:;, ----, =*= need escaping only in SOL context.
			if ( !$sol && !preg_match( "/''|[<>]|\\[.*\\]|\\]|(=[ ]*(\\n|$))|__[^_]*__/", $text ) ) {
				// It is not necessary to test for an unmatched opening bracket ([)
				// as long as we always escape an unmatched closing bracket (]).
				$env->log( 'trace/wt-escape', '---Not-SOL and safe---' );
				return $text;
			}

			// Quick checks when on a newline
			// + can only occur as "|+" and - can only occur as "|-" or ----
			if ( $sol && !preg_match( '/(^|\n)[ #*:;=]|[<\[\]>\|\'!]|\-\-\-\-|__[^_]*__/', $text ) ) {
				$env->log( 'trace/wt-escape', '---SOL and safe---' );
				return $text;
			}
		}

		// The front-end parser eliminated pre-tokens in the tokenizer
		// and moved them to a stream handler. So, we always conservatively
		// escape text with ' ' in sol posn with one caveat:
		// * and when the current line has block tokens
		if ( $indentPreUnsafe &&
			( !self::hasBlocksOnLine( $state->currLine->firstNode, true ) ||
				!empty( $opts['inMultilineMode'] )
			)
		) {
			$env->log( 'trace/wt-escape', '---SOL and pre---' );
			$state->hasIndentPreNowikis = true;
			return $this->escapedText( $state, $sol, $text );
		}

		// escape nowiki tags
		$text = WTSUtils::escapeNowikiTags( $text );

		// Use the tokenizer to see if we have any wikitext tokens
		//
		// Ignores entities
		if ( $hasTildes ) {
			$env->log( 'trace/wt-escape', '---Found tildes---' );
			return $this->escapedText( $state, $sol, $text );
		} elseif ( $this->hasWikitextTokens( $state, $sol, $text ) ) {
			$env->log( 'trace/wt-escape', '---Found WT tokens---' );
			return $this->escapedText( $state, $sol, $text );
		} elseif ( preg_match( '/[^\[]*\]/', $text ) &&
			$this->textCanParseAsLink( $opts['node'], $state, $text )
		) {
			// we have an closing bracket, and
			// - the text will get parsed as a link in
			$env->log( 'trace/wt-escape', '---Links: complex single-line test---' );
			return $this->escapedText( $state, $sol, $text );
		} elseif ( !empty( $opts['isLastChild'] ) && substr( $text, -1 ) === '=' ) {
			// 1. we have an open heading char, and
			// - text ends in a '='
			// - text comes from the last child
			preg_match( '/^h(\d)/', DOMCompat::nodeName( $state->currLine->firstNode ), $headingMatch );
			if ( $headingMatch ) {
				$n = intval( $headingMatch[1] );
				if ( ( $state->currLine->text . $text )[$n] === '=' ) {
					// The first character after the heading wikitext is/will be a '='.
					// So, the trailing '=' can change semantics if it is not nowikied.
					$env->log( 'trace/wt-escape', '---Heading: complex single-line test---' );
					return $this->escapedText( $state, $sol, $text );
				} else {
					return $text;
				}
			} elseif ( strlen( $state->currLine->text ) > 0 && $state->currLine->text[0] === '=' ) {
				$env->log( 'trace/wt-escape', '---Text-as-heading: complex single-line test---' );
				return $this->escapedText( $state, $sol, $text );
			} else {
				return $text;
			}
		} else {
			$env->log( 'trace/wt-escape', '---All good!---' );
			return $text;
		}
	}

	/**
	 * @param string $str
	 * @param bool $isLast
	 * @param bool $checkNowiki
	 * @param string &$buf
	 * @param bool &$openNowiki
	 * @param bool $isTemplate
	 * @param bool &$serializeAsNamed
	 * @param array $opts [ 'numPositionalArgs' => int, 'argPositionalIndex' => int, 'type' => string,
	 * 'numArgs' => int, 'argIndex' => int ]
	 */
	private static function appendStr(
		string $str, bool $isLast, bool $checkNowiki, string &$buf, bool &$openNowiki,
		bool $isTemplate, bool &$serializeAsNamed, array $opts
	): void {
		if ( !$checkNowiki ) {
			if ( $openNowiki ) {
				$buf .= '</nowiki>';
				$openNowiki = false;
			}
			$buf .= $str;
			return;
		}

		// '=' is not allowed in positional parameters.  We can either
		// nowiki escape it or convert the named parameter into a
		// positional param to avoid the escaping.
		if ( $isTemplate && !$serializeAsNamed && str_contains( $str, '=' ) ) {
			// In certain situations, it is better to add a nowiki escape
			// rather than convert this to a named param.
			//
			// Ex: Consider: {{funky-tpl|a|b|c|d|e|f|g|h}}
			//
			// If an editor changes 'a' to 'f=oo' and we convert it to
			// a named param 1=f=oo, we are effectively converting all
			// the later params into named params as well and we get
			// {{funky-tpl|1=f=oo|2=b|3=c|...|8=h}} instead of
			// {{funky-tpl|<nowiki>f=oo</nowiki>|b|c|...|h}}
			//
			// The latter is better in this case. This is a real problem
			// in production.
			//
			// For now, we use a simple heuristic below and can be
			// refined later, if necessary
			//
			// 1. Either there were no original positional args
			// 2. Or, only the last positional arg uses '='
			if ( $opts['numPositionalArgs'] === 0 ||
				$opts['numPositionalArgs'] === $opts['argPositionalIndex']
			) {
				$serializeAsNamed = true;
			}
		}

		// Count how many reasons for nowiki
		$needNowikiCount = 0;
		$neededSubstitution = null;
		// Protect against unmatched pairs of braces and brackets, as they
		// should never appear in template arguments.
		$bracketPairStrippedStr = preg_replace(
			'/\[\[([^\[\]]*)\]\]|\{\{([^\{\}]*)\}\}|-\{([^\{\}]*)\}-/',
			'_$1_',
			$str
		);
		if ( preg_match( '/\{\{|\}\}|\[\[|\]\]|-\{/', $bracketPairStrippedStr ) ) {
			$needNowikiCount++;
		}
		if ( $opts['type'] !== 'templatearg' && !$serializeAsNamed && str_contains( $str, '=' ) ) {
			$needNowikiCount++;
		}
		if ( $opts['argIndex'] === $opts['numArgs'] && $isLast && str_ends_with( $str, '}' ) ) {
			// If this is the last part of the last argument, we need to protect
			// against an ending }, as it would get confused with the template ending }}.
			$needNowikiCount++;
			$neededSubstitution = [ '/(\})$/D', '<nowiki>}</nowiki>' ];
		}
		if ( str_contains( $str, '|' ) ) {
			// If there's an unprotected |, guard it so it doesn't get confused
			// with the beginning of a different parameter.
			$needNowikiCount++;
			$neededSubstitution = [ '/\|/', '{{!}}' ];
		}

		// Now, if arent' already in a <nowiki> and there's only one reason to
		// protect, avoid guarding too much text by just substituting.
		if ( !$openNowiki && $needNowikiCount === 1 && $neededSubstitution ) {
			$str = preg_replace( $neededSubstitution[0], $neededSubstitution[1], $str );
			$needNowikiCount = false;
		}
		if ( !$openNowiki && $needNowikiCount ) {
			$buf .= '<nowiki>';
			$openNowiki = true;
		}
		if ( !$needNowikiCount && $openNowiki ) {
			$buf .= '</nowiki>';
			$openNowiki = false;
		}
		$buf .= $str;
	}

	/**
	 * General strategy:
	 *
	 * Tokenize the arg wikitext.  Anything that parses as tags
	 * are good and we need not bother with those. Check for harmful
	 * characters `[[]]{{}}` or additionally `=` in positional parameters and escape
	 * those fragments since these characters could change semantics of the entire
	 * template transclusion.
	 *
	 * This function makes a couple of assumptions:
	 *
	 * 1. The tokenizer sets tsr on all non-string tokens.
	 * 2. The tsr on TagTk and EndTagTk corresponds to the
	 *    width of the opening and closing wikitext tags and not
	 *    the entire DOM range they span in the end.
	 *
	 * @param string $arg
	 * @param array $opts [ 'serializeAsNamed' => bool,  'numPositionalArgs' => int,
	 * 'argPositionalIndex' => int, 'type' => string, 'numArgs' => int, 'argIndex' => int ]
	 * @return array
	 */
	public function escapeTplArgWT( string $arg, array $opts ): array {
		$env = $this->env;
		$serializeAsNamed = $opts['serializeAsNamed'];
		$buf = '';
		$openNowiki = false;
		$isTemplate = $opts['type'] === 'template';

		$tokens = $this->tokenizeStr( $arg, false );

		for ( $i = 0, $n = count( $tokens ); $i < $n; $i++ ) {
			$t = $tokens[$i];
			$last = $i === $n - 1;

			// For mw:Entity spans, the opening and closing tags have 0 width
			// and the enclosed content is the decoded entity. Hence the
			// special case to serialize back the entity's source.
			if ( $t instanceof TagTk ) {
				$da = $t->dataParsoid;
				if ( TokenUtils::matchTypeOf( $t, '#^mw:(Placeholder|Entity)(/|$)#' ) ) {
					$i += 2;
					$width = $tokens[$i]->dataParsoid->tsr->end - $da->tsr->start;
					self::appendStr(
						substr( $arg, $da->tsr->start, $width ),
						$last,
						false,
						$buf,
						$openNowiki,
						$isTemplate,
						$serializeAsNamed,
						$opts
					);
					continue;
				} elseif ( TokenUtils::hasTypeOf( $t, 'mw:Nowiki' ) ) {
					$i++;
					while ( $i < $n &&
						( !$tokens[$i] instanceof EndTagTk ||
							!TokenUtils::hasTypeOf( $tokens[$i], 'mw:Nowiki' )
						)
					) {
						$i++;
					}
					if ( $i < $n ) {
						// After tokenization, we can get here:
						// * Text explicitly protected by <nowiki> in the parameter.
						// * Other things that should be protected but weren't
						// according to the tokenizer.
						// In template argument, we only need to check for unmatched
						// braces and brackets pairs (which is done in appendStr),
						// but only if they weren't explicitly protected in the
						// passed wikitext.
						$width = $tokens[$i]->dataParsoid->tsr->end - $da->tsr->start;
						$substr = substr( $arg, $da->tsr->start, $width );
						self::appendStr(
							$substr,
							$last,
							!preg_match( '#<nowiki>[^<]*</nowiki>#', $substr ),
							$buf,
							$openNowiki,
							$isTemplate,
							$serializeAsNamed,
							$opts
						);
					}
					continue;
				}
			}

			switch ( TokenUtils::getTokenType( $t ) ) {
				case 'TagTk':
				case 'EndTagTk':
				case 'NlTk':
				case 'CommentTk':
					$da = $t->dataParsoid;
					if ( empty( $da->tsr ) ) {
						$errors = [ 'Missing tsr for: ' . PHPUtils::jsonEncode( $t ) ];
						$errors[] = 'Arg : ' . PHPUtils::jsonEncode( $arg );
						$errors[] = 'Toks: ' . PHPUtils::jsonEncode( $tokens );
						$env->log( 'error/html2wt/wtescape', implode( "\n", $errors ) );
						// FIXME $da->tsr will be undefined below.
						// Should we throw an explicit exception here?
					}
					self::appendStr(
						$da->tsr->substr( $arg ),
						$last,
						false,
						$buf,
						$openNowiki,
						$isTemplate,
						$serializeAsNamed,
						$opts
					);
					break;
				case 'SelfclosingTagTk':
					$da = $t->dataParsoid;
					if ( empty( $da->tsr ) ) {
						$errors = [ 'Missing tsr for: ' . PHPUtils::jsonEncode( $t ) ];
						$errors[] = 'Arg : ' . PHPUtils::jsonEncode( $arg );
						$errors[] = 'Toks: ' . PHPUtils::jsonEncode( $tokens );
						$env->log( 'error/html2wt/wtescape', implode( "\n", $errors ) );
						// FIXME $da->tsr will be undefined below.
						// Should we throw an explicit exception here?
					}
					$tkSrc = $da->tsr->substr( $arg );
					// Replace pipe by an entity. This is not completely safe.
					if ( $t->getName() === 'extlink' || $t->getName() === 'urllink' ) {
						$tkBits = $this->tokenizer->tokenizeSync( $tkSrc, [
								'startRule' => 'tplarg_or_template_or_bust'
							]
						);
						foreach ( $tkBits as $bit ) {
							if ( $bit instanceof Token ) {
								self::appendStr(
									$bit->dataParsoid->src,
									$last,
									false,
									$buf,
									$openNowiki,
									$isTemplate,
									$serializeAsNamed,
									$opts
								);
							} else {
								// Convert to a named param w/ the same reasoning
								// as above for escapeStr, however, here we replace
								// with an entity to avoid breaking up querystrings
								// with nowikis.
								if ( $isTemplate && !$serializeAsNamed && str_contains( $bit, '=' ) ) {
									if ( $opts['numPositionalArgs'] === 0
										|| $opts['numPositionalArgs'] === $opts['argIndex']
									) {
										$serializeAsNamed = true;
									} else {
										$bit = str_replace( '=', '&#61;', $bit );
									}
								}
								$buf .= str_replace( '|', '&#124;', $bit );
							}
						}
					} else {
						self::appendStr(
							$tkSrc,
							$last,
							false,
							$buf,
							$openNowiki,
							$isTemplate,
							$serializeAsNamed,
							$opts
						);
					}
					break;
				case 'string':
					self::appendStr(
						$t,
						$last,
						true,
						$buf,
						$openNowiki,
						$isTemplate,
						$serializeAsNamed,
						$opts
					);
					break;
				case 'EOFTk':
					break;
			}
		}

		// If nowiki still open, close it now.
		if ( $openNowiki ) {
			$buf .= '</nowiki>';
		}

		return [ 'serializeAsNamed' => $serializeAsNamed, 'v' => $buf ];
	}

	/**
	 * See also `escapeLinkTarget` in LinkHandler.php
	 *
	 * @param SerializerState $state
	 * @param string $str
	 * @param bool $solState
	 * @param Node $node
	 * @param bool $isMedia
	 * @return string
	 */
	public function escapeLinkContent(
		SerializerState $state, string $str, bool $solState, Node $node, bool $isMedia
	): string {
		// Entity-escape the content.
		$str = Utils::escapeWtEntities( $str );

		// Wikitext-escape content.
		$state->onSOL = $solState;
		$state->wteHandlerStack[] = $isMedia
			? [ $this, 'mediaOptionHandler' ]
			: [ $this, 'wikilinkHandler' ];
		$state->inLink = true;
		$res = $this->escapeWikitext( $state, $str, [ 'node' => $node ] );
		$state->inLink = false;
		array_pop( $state->wteHandlerStack );

		return $res;
	}
}
