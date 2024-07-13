<?php
/**
 * Utilities used in the tokenizer.
 * @module wt2html/tokenizer_utils
 */

declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html;

use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\NodeData\DataParsoid;
use Wikimedia\Parsoid\NodeData\TempData;
use Wikimedia\Parsoid\Tokens\CommentTk;
use Wikimedia\Parsoid\Tokens\EndTagTk;
use Wikimedia\Parsoid\Tokens\SelfclosingTagTk;
use Wikimedia\Parsoid\Tokens\SourceRange;
use Wikimedia\Parsoid\Tokens\TagTk;
use Wikimedia\Parsoid\Tokens\Token;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Wikitext\Consts;

class TokenizerUtils {
	private static $protectAttrsRegExp;
	private static $inclAnnRegExp;

	/**
	 * @param mixed $e
	 * @param ?array &$res
	 * @return mixed (same type as $e)
	 * @throws \Exception
	 */
	private static function internalFlatten( $e, ?array &$res ) {
		// Don't bother flattening if we dont have an array
		if ( !is_array( $e ) ) {
			return $e;
		}

		for ( $i = 0;  $i < count( $e );  $i++ ) {
			$v = $e[$i];
			if ( is_array( $v ) ) {
				// Change in assumption from a shallow array to a nested array.
				if ( $res === null ) {
					$res = array_slice( $e, 0, $i );
				}
				self::internalFlatten( $v, $res );
			} elseif ( $v !== null ) {
				if ( $res !== null ) {
					$res[] = $v;
				}
			} else {
				throw new \RuntimeException( __METHOD__ . ": found falsy element $i" );
			}
		}

		if ( $res !== null ) {
			$e = $res;
		}
		return $e;
	}

	/**
	 * If $a is an array, this recursively flattens all nested arrays.
	 * @param mixed $a
	 * @return mixed
	 */
	public static function flattenIfArray( $a ) {
		return self::internalFlatten( $a, $res );
	}

	/**
	 * FIXME: document
	 * @param mixed $c
	 * @return mixed
	 */
	public static function flattenString( $c ) {
		$out = self::flattenStringlist( $c );
		if ( count( $out ) === 1 && is_string( $out[0] ) ) {
			return $out[0];
		} else {
			return $out;
		}
	}

	/**
	 * FIXME: document
	 * @param array $c
	 * @return array
	 */
	public static function flattenStringlist( array $c ): array {
		$out = [];
		$text = '';
		$c = self::flattenIfArray( $c );
		for ( $i = 0, $l = count( $c );  $i < $l;  $i++ ) {
			$ci = $c[$i];
			if ( is_string( $ci ) ) {
				if ( $ci !== '' ) {
					$text .= $ci;
				}
			} else {
				if ( $text !== '' ) {
					$out[] = $text;
					$text = '';
				}
				$out[] = $ci;
			}
		}
		if ( $text !== '' ) {
			$out[] = $text;
		}
		return $out;
	}

	/**
	 * @param mixed $value
	 * @param int $start start of TSR range
	 * @param int $end end of TSR range
	 * @return array
	 */
	public static function getAttrVal( $value, int $start, int $end ): array {
		return [ 'value' => $value, 'srcOffsets' => new SourceRange( $start, $end ) ];
	}

	/**
	 * Build a token array representing <tag>$content</tag> alongwith
	 * appropriate attributes and TSR info set on the tokens.
	 *
	 * @param string $tagName
	 * @param string $wtChar
	 * @param mixed $attrInfo
	 * @param SourceRange $tsr
	 * @param int $endPos
	 * @param mixed $content
	 * @param bool $addEndTag
	 * @return array (of tokens)
	 */
	public static function buildTableTokens(
		string $tagName, string $wtChar, $attrInfo, SourceRange $tsr,
		int $endPos, $content, bool $addEndTag = false
	): array {
		$a = null;
		$dp = new DataParsoid;
		$dp->tsr = $tsr;

		if ( !$attrInfo ) {
			$a = [];
			if ( $tagName === 'td' || $tagName === 'th' ) {
				// Add a flag that indicates that the tokenizer didn't
				// encounter a "|...|" attribute box. This is useful when
				// deciding which <td>/<th> cells need attribute fixups.
				$dp->setTempFlag( TempData::NO_ATTRS );
			}
		} else {
			$a = $attrInfo[0];
			if ( !$a ) {
				$dp->startTagSrc = $wtChar . $attrInfo[1];
			}
			if ( ( !$a && $attrInfo[2] ) || $attrInfo[2] !== '|' ) {
				// Variation from default
				// 1. Separator present with an empty attribute block
				// 2. Not "|"
				$dp->attrSepSrc = $attrInfo[2];
			}
		}

		$tokens = [ new TagTk( $tagName, $a, $dp ) ];
		PHPUtils::pushArray( $tokens, $content );

		if ( $addEndTag ) {
			$dataParsoid = new DataParsoid;
			$dataParsoid->tsr = new SourceRange( $endPos, $endPos );
			$tokens[] = new EndTagTk( $tagName, [], $dataParsoid );
		} else {
			// We rely on our tree builder to close the table cell (td/th) as needed.
			// We cannot close the cell here because cell content can come from
			// multiple parsing contexts and we cannot close the tag in the same
			// parsing context in which the td was opened:
			//   Ex: {{1x|{{!}}foo}}{{1x|bar}} has to output <td>foobar</td>
			//
			// Previously a meta marker was added here for DSR computation, but
			// that's complicated now that marker meta handling has been removed
			// from ComputeDSR.
		}

		return $tokens;
	}

	/**
	 * Build a token representing <tag>, <tag />, or </tag>
	 * with appropriate attributes set on the token.
	 *
	 * @param string $name
	 * @param string $lcName
	 * @param array $attribs
	 * @param mixed $endTag
	 * @param bool $selfClose
	 * @param SourceRange $tsr
	 * @return Token
	 */
	public static function buildXMLTag( string $name, string $lcName, array $attribs, $endTag,
		bool $selfClose, SourceRange $tsr
	): Token {
		$tok = null;
		$da = new DataParsoid;
		$da->tsr = $tsr;
		$da->stx = 'html';

		if ( $name !== $lcName ) {
			$da->srcTagName = $name;
		}

		if ( $endTag !== null ) {
			$tok = new EndTagTk( $lcName, $attribs, $da );
		} elseif ( $selfClose ) {
			$da->selfClose = true;
			$tok = new SelfclosingTagTk( $lcName, $attribs, $da );
		} else {
			$tok = new TagTk( $lcName, $attribs, $da );
		}

		return $tok;
	}

	/**
	 * Inline breaks, flag-enabled rule which detects end positions for
	 * active higher-level rules in inline and other nested rules.
	 * Those inner rules are then exited, so that the outer rule can
	 * handle the end marker.
	 * @param string $input
	 * @param int $pos
	 * @param array $stops
	 * @param Env $env
	 * @return bool
	 * @throws \Exception
	 */
	public static function inlineBreaks( string $input, int $pos, array $stops, Env $env ): bool {
		$c = $input[$pos];
		$c2 = $input[$pos + 1] ?? '';

		switch ( $c ) {
			case '=':
				if ( $stops['arrow'] && $c2 === '>' ) {
					return true;
				}
				if ( $stops['equal'] ) {
					return true;
				}
				if ( $stops['h'] ) {
					if ( self::$inclAnnRegExp === null ) {
						$tags = array_merge(
							[ 'noinclude', 'includeonly', 'onlyinclude' ],
							$env->getSiteConfig()->getAnnotationTags()
						);
						self::$inclAnnRegExp = '|<\/?(?:' . implode( '|', $tags ) . ')>';
					}
					return ( $pos === strlen( $input ) - 1
						// possibly more equals followed by spaces or comments
						|| preg_match( '/^=*(?:[ \t]|<\!--(?:(?!-->).)*-->'
								. self::$inclAnnRegExp . ')*(?:[\r\n]|$)/sD',
							substr( $input, $pos + 1 ) ) );
				}
				return false;

			case '|':
				return !$stops['annOrExtTag'] && (
					$stops['templateArg']
					|| $stops['tableCellArg']
					|| $stops['linkdesc']
					|| ( $stops['table']
						&& $pos < strlen( $input ) - 1
						&& preg_match( '/[}|]/', $input[$pos + 1] ) )
				);

			case '!':
				return $stops['th']
					&& !$stops['intemplate']
					&& $c2 === '!';

			case '{':
				// {{!}} pipe templates..
				// FIXME: Presumably these should mix with and match | above.
				// phpcs:ignore Squiz.WhiteSpace.LanguageConstructSpacing.IncorrectSingle
				return ( $stops['tableCellArg']
						&& substr( $input, $pos, 5 ) === '{{!}}' )
					|| ( $stops['table']
						&& substr( $input, $pos, 10 ) === '{{!}}{{!}}' );

			case '}':
				$preproc = $stops['preproc'];
				return ( $c2 === '}' && $preproc === '}}' )
					|| ( $c2 === '-' && $preproc === '}-' );

			case ':':
				return $stops['colon']
					&& !$stops['extlink']
					&& !$stops['intemplate']
					&& !$stops['linkdesc']
					&& !( $stops['preproc'] === '}-' );

			case ';':
				return $stops['semicolon'];

			case "\r":
				return $stops['table']
					&& preg_match( '/\r\n?\s*[!|]/', substr( $input, $pos ) );

			case "\n":
				// The code below is just a manual / efficient
				// version of this check.
				//
				// stops.table && /^\n\s*[!|]/.test(input.substr(pos));
				//
				// It eliminates a substr on the string and eliminates
				// a potential perf problem since "\n" and the inline_breaks
				// test is common during tokenization.
				if ( !$stops['table'] ) {
					return false;
				}

				// Allow leading whitespace in tables

				// Since we switched on 'c' which is input[pos],
				// we know that input[pos] is "\n".
				// So, the /^\n/ part of the regexp is already satisfied.
				// Look for /\s*[!|]/ below.
				$n = strlen( $input );
				for ( $i = $pos + 1;  $i < $n;  $i++ ) {
					$d = $input[$i];
					if ( preg_match( '/[!|]/', $d ) ) {
						return true;
					} elseif ( !( preg_match( '/\s/', $d ) ) ) {
						return false;
					}
				}
				return false;
			case '[':
				// This is a special case in php's doTableStuff, added in
				// response to T2553.  If it encounters a `[[`, it bails on
				// parsing attributes and interprets it all as content.
				return $stops['tableCellArg'] && $c2 === '[';

			case '-':
				// Same as above: a special case in doTableStuff, added
				// as part of T153140
				return $stops['tableCellArg'] && $c2 === '{';

			case ']':
				if ( $stops['extlink'] ) {
					return true;
				}
				return $stops['preproc'] === ']]'
					&& $c2 === ']';

			default:
				throw new \RuntimeException( 'Unhandled case!' );
		}
	}

	/**
	 * Pop off the end comments, if any.
	 * @param array &$attrs
	 * @return array|null
	 */
	public static function popComments( array &$attrs ): ?array {
		$buf = [];
		for ( $i = count( $attrs ) - 1;  $i > -1;  $i-- ) {
			$kv = $attrs[$i];
			if ( is_string( $kv->k ) && !$kv->v && preg_match( '/^\s*$/D', $kv->k ) ) {
				// permit whitespace
				array_unshift( $buf, $kv->k );
			} elseif ( is_array( $kv->k ) && !$kv->v ) {
				// all should be comments
				foreach ( $kv->k as $k ) {
					if ( !( $k instanceof CommentTk ) ) {
						break 2;
					}
				}
				array_splice( $buf, 0, 0, $kv->k );
			} else {
				break;
			}
		}
		// ensure we found a comment
		while ( $buf && !( $buf[0] instanceof CommentTk ) ) {
			array_shift( $buf );
		}
		if ( $buf ) {
			array_splice( $attrs, -count( $buf ), count( $buf ) );
			return [ 'buf' => $buf, 'commentStartPos' => $buf[0]->dataParsoid->tsr->start ];
		} else {
			return null;
		}
	}

	/** Get a string containing all the autourl terminating characters (as in legacy parser
	 * Parser.php::makeFreeExternalLink). This list is slightly context-dependent because the
	 * inclusion of the right parenthesis depends on whether the provided character array $arr
	 * contains a left parenthesis.
	 * @param bool $hasLeftParen should be true if the URL in question contains
	 *   a left parenthesis.
	 * @return string
	 */
	public static function getAutoUrlTerminatingChars( bool $hasLeftParen ): string {
		$chars = Consts::$strippedUrlCharacters;
		if ( !$hasLeftParen ) {
			$chars .= ')';
		}
		return $chars;
	}

	/**
	 * @param Env $env
	 * @param mixed $token
	 */
	public static function enforceParserResourceLimits( Env $env, $token ) {
		if ( $token instanceof TagTk || $token instanceof SelfclosingTagTk ) {
			$resource = null;
			switch ( $token->getName() ) {
				case 'listItem':
					$resource = 'listItem';
					break;
				case 'template':
					$resource = 'transclusion';
					break;
				case 'td':
				case 'th':
					$resource = 'tableCell';
					break;
			}
			if (
				$resource !== null &&
				$env->bumpWt2HtmlResourceUse( $resource ) === false
			) {
				// `false` indicates that this bump pushed us over the threshold
				// We don't want to log every token above that, which would be `null`
				$env->log( 'warn', "wt2html: $resource limit exceeded" );
			}
		}
	}

	/**
	 * Protect Parsoid-inserted attributes by escaping them to prevent
	 * Parsoid-HTML spoofing in wikitext.
	 *
	 * @param string $name
	 * @return string
	 */
	public static function protectAttrs( string $name ): string {
		if ( self::$protectAttrsRegExp === null ) {
			self::$protectAttrsRegExp = "/^(about|data-mw.*|data-parsoid.*|data-x.*|" .
				DOMDataUtils::DATA_OBJECT_ATTR_NAME .
				'|property|rel|typeof)$/i';
		}
		return preg_replace( self::$protectAttrsRegExp, 'data-x-$1', $name );
	}

	/**
	 * Resets $inclAnnRegExp to null to avoid test environment side effects
	 */
	public static function resetAnnotationIncludeRegex(): void {
		self::$inclAnnRegExp = null;
	}

}
