<?php

// phpcs:disable MediaWiki.Commenting.FunctionComment.MissingDocumentationPublic
// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
// phpcs:disable MediaWiki.Commenting.FunctionComment.WrongStyle
// phpcs:disable MediaWiki.Commenting.FunctionComment.MissingDocumentationPrivate

namespace Wikimedia\Parsoid\Wt2Html\TT;

use DateTime;
use DateTimeZone;
use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\Core\Sanitizer;
use Wikimedia\Parsoid\NodeData\DataParsoid;
use Wikimedia\Parsoid\Tokens\EndTagTk;
use Wikimedia\Parsoid\Tokens\KV;
use Wikimedia\Parsoid\Tokens\SelfclosingTagTk;
use Wikimedia\Parsoid\Tokens\TagTk;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\TokenUtils;
use Wikimedia\Parsoid\Utils\Utils;
use Wikimedia\Parsoid\Wt2Html\Frame;
use Wikimedia\Parsoid\Wt2Html\Params;

/**
 * Some parser functions, and quite a bunch of stubs of parser functions.
 *
 * IMPORTANT NOTE: These parser functions are only used by the Parsoid-native
 * template expansion pipeline, which is *not* the default or used in
 * production. Normally core provides us SiteConfig and DataAccess objects
 * that provide parser functions and other preprocessor functionality.
 *
 * There are still quite a few missing, see
 * {@link http://www.mediawiki.org/wiki/Help:Magic_words} and
 * {@link http://www.mediawiki.org/wiki/Help:Extension:ParserFunctions}.
 * Instantiated and called by the {@link TemplateHandler} extension.
 * Any `pf_<prefix>`
 * matching a lower-cased template name prefix up to the first colon will
 * override that template.
 *
 * The only use of this code is currently in parserTests and offline tests.
 * But, eventually as the two parsers are integrated, the core parser tests
 * implementation from $mw/includes/parser/CoreParserFunctions.php might
 * move over here.
 */
class ParserFunctions {
	/** @var Env */
	private $env;

	public function __construct( Env $env ) {
		$this->env = $env;
	}

	// Temporary helper.
	private function rejoinKV( bool $trim, $k, $v ) {
		if ( is_string( $k ) && strlen( $k ) > 0 ) {
			return array_merge( [ $k, '=' ], $v );
		} elseif ( is_array( $k ) && count( $k ) > 0 ) {
			$k[] = '=';
			return array_merge( $k, $v );
		} else {
			return $trim ? ( is_string( $v ) ? trim( $v ) : TokenUtils::tokenTrim( $v ) ) : $v;
		}
	}

	private function expandV( $v, Frame $frame ) {
		// FIXME: This hasn't been implemented on the JS side
		return $v;
	}

	// XXX: move to frame?
	private function expandKV(
		$kv, Frame $frame, $defaultValue = null, string $type = null, bool $trim = false
	): array {
		if ( $type === null ) {
			$type = 'tokens/x-mediawiki/expanded';
		}

		if ( $kv === null ) {
			return [ $defaultValue ?: '' ];
		} elseif ( is_string( $kv ) ) {
			return [ $kv ];
		} elseif ( is_string( $kv->k ) && is_string( $kv->v ) ) {
			if ( $kv->k ) {
				return [ $kv->k . '=' . $kv->v ];
			} else {
				return [ $trim ? trim( $kv->v ) : $kv->v ];
			}
		} else {
			$v = $this->expandV( $kv->v, $frame );
			return $this->rejoinKV( $trim, $kv->k, $v );
		}
	}

	private function prefixedTitleText(): string {
		return $this->env->getContextTitle()->getPrefixedText();
	}

	public function pf_if( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		if ( trim( $args[0]->k ) !== '' ) {
			return $this->expandKV( $args[1] ?? null, $frame );
		} else {
			return $this->expandKV( $args[2] ?? null, $frame );
		}
	}

	private function trimRes( $res ) {
		if ( is_string( $res ) ) {
			return [ trim( $res ) ];
		} elseif ( is_array( $res ) ) {
			return TokenUtils::tokenTrim( $res );
		} else {
			return $res;
		}
	}

	private function noTrimRes( $res ): array {
		if ( is_string( $res ) ) {
			return [ $res ];
		} elseif ( is_array( $res ) ) {
			return $res;
		} else {
			$this->env->log( 'error', 'Unprocessable res in ParserFunctions:noTrimRes', $res );
			return [];
		}
	}

	private function switchLookupFallback(
		Frame $frame, array $kvs, string $key, array $dict, $v = null
	): array {
		$kv = null;
		$l = count( $kvs );
		$this->env->log( 'debug', 'switchLookupFallback', $key, $v );
		// 'v' need not be a string in cases where it is the last fall-through case
		$vStr = $v ? TokenUtils::tokensToString( $v ) : null;
		if ( $vStr && $key === trim( $vStr ) ) {
			// This handles fall-through switch cases:
			//
			//   {{#switch:<key>
			//     | c1 | c2 | c3 = <res>
			//     ...
			//   }}
			//
			// So if <key> matched c1, we want to return <res>.
			// Hence, we are looking for the next entry with a non-empty key.
			$this->env->log( 'debug', 'switch found' );
			foreach ( $kvs as $kv ) {
				// XXX: make sure the key is always one of these!
				if ( count( $kv->k ) > 0 ) {
					return $this->trimRes( $this->expandV( $kv->v, $frame ) );
				}
			}

			// No value found, return empty string? XXX: check this
			return [];
		} elseif ( count( $kvs ) > 0 ) {
			// search for value-only entry which matches
			$i = 0;
			if ( $v ) {
				$i = 1;
			}
			for ( ;  $i < $l;  $i++ ) {
				$kv = $kvs[$i];
				if ( count( $kv->k ) || !count( $kv->v ) ) {
					// skip entries with keys or empty values
					continue;
				} else {
					// We found a value-only entry.  However, we have to verify
					// if we have any fall-through cases that this matches.
					//
					//   {{#switch:<key>
					//     | c1 | c2 | c3 = <res>
					//     ...
					//   }}
					//
					// In the switch example above, if we found 'c1', that is
					// not the fallback value -- we have to check for fall-through
					// cases.  Hence the recursive callback to switchLookupFallback.
					//
					//   {{#switch:<key>
					//     | c1 = <..>
					//     | c2 = <..>
					//     | [[Foo]]</div>
					//   }}
					//
					// 'val' may be an array of tokens rather than a string as in the
					// example above where 'val' is indeed the final return value.
					// Hence 'tokens/x-mediawiki/expanded' type below.
					$v = $this->expandV( $kv->v, $frame );
					return $this->switchLookupFallback( $frame, array_slice( $kvs, $i + 1 ), $key, $dict, $v );
				}
			}

			// value not found!
			if ( isset( $dict['#default'] ) ) {
				return $this->trimRes( $this->expandV( $dict['#default'], $frame ) );
			} elseif ( count( $kvs ) ) {
				$lastKV = $kvs[count( $kvs ) - 1];
				if ( $lastKV && !count( $lastKV->k ) ) {
					return $this->noTrimRes( $this->expandV( $lastKV->v, $frame ) );
				} else {
					return [];
				}
			} else {
				// nothing found at all.
				return [];
			}
		} elseif ( $v ) {
			return is_array( $v ) ? $v : [ $v ];
		} else {
			// nothing found at all.
			return [];
		}
	}

	public function pf_switch( $token, Frame $frame, Params $params ): array {
		// TODO: Implement http://www.mediawiki.org/wiki/Help:Extension:ParserFunctions#Grouping_results
		$args = $params->args;
		$target = trim( $args[0]->k );
		$this->env->log( 'debug', 'switch enter', $target, $token );
		// create a dict from the remaining args
		array_shift( $args );
		$dict = $params->dict();
		if ( $target && $dict[$target] !== null ) {
			$this->env->log( 'debug', 'switch found: ', $target, $dict, ' res=', $dict[$target] );
			$v = $this->expandV( $dict[$target], $frame );
			return $this->trimRes( $v );
		} else {
			return $this->switchLookupFallback( $frame, $args, $target, $dict );
		}
	}

	public function pf_ifeq( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		if ( count( $args ) < 3 ) {
			return [];
		} else {
			$v = $this->expandV( $args[1]->v, $frame );
			return $this->ifeq_worker( $frame, $args, $v );
		}
	}

	private function ifeq_worker( Frame $frame, array $args, $b ): array {
		if ( trim( $args[0]->k ) === trim( $b ) ) {
			return $this->expandKV( $args[2], $frame );
		} else {
			return $this->expandKV( $args[3], $frame );
		}
	}

	public function pf_expr( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		$target = $args[0]->k;
		if ( $target ) {
			try {
				// phpcs:ignore MediaWiki.Usage.ForbiddenFunctions.eval
				$res = eval( $target );
			} catch ( \Exception $e ) {
				$res = null;
			}
		} else {
			$res = '';
		}

		// Avoid crashes
		if ( $res === null ) {
			return [ 'class="error" in expression ' . $target ];
		}

		return [ (string)$res ];
	}

	public function pf_ifexpr( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		$this->env->log( 'debug', '#ifexp: ', $args );
		$target = $args[0]->k;
		$res = null;
		if ( $target ) {
			try {
				// phpcs:ignore MediaWiki.Usage.ForbiddenFunctions.eval
				$res = eval( $target );
			} catch ( \Exception $e ) {
				return [ 'class="error" in expression ' . $target ];
			}
		}
		if ( $res ) {
			return $this->expandKV( $args[1], $frame );
		} else {
			return $this->expandKV( $args[2], $frame );
		}
	}

	public function pf_iferror( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		$target = $args[0]->k;
		if ( in_array( 'class="error"', $target, true ) ) {
			return $this->expandKV( $args[1], $frame );
		} else {
			return $this->expandKV( $args[1], $frame, $target );
		}
	}

	public function pf_lc( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		return [ mb_strtolower( $args[0]->k ) ];
	}

	public function pf_uc( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		return [ mb_strtoupper( $args[0]->k ) ];
	}

	public function pf_ucfirst( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		$target = $args[0]->k;
		'@phan-var string $target';
		if ( $target ) {
			return [ mb_strtoupper( mb_substr( $target, 0, 1 ) ) . mb_substr( $target, 1 ) ];
		} else {
			return [];
		}
	}

	public function pf_lcfirst( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		$target = $args[0]->k;
		'@phan-var string $target';
		if ( $target ) {
			return [ mb_strtolower( mb_substr( $target, 0, 1 ) ) . mb_substr( $target, 1 ) ];
		} else {
			return [];
		}
	}

	public function pf_padleft( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		$target = $args[0]->k;
		$env = $this->env;
		if ( !$args[1] ) {
			return [];
		}
		// expand parameters 1 and 2
		$args = $params->getSlice( 1, 3 );
		$n = +( $args[0]->v );
		if ( $n > 0 ) {
			$pad = '0';
			if ( isset( $args[1] ) && $args[1]->v !== '' ) {
				$pad = $args[1]->v;
			}
			$padLength = mb_strlen( $pad );
			$extra = '';
			while ( ( mb_strlen( $target ) + mb_strlen( $extra ) + $padLength ) < $n ) {
				$extra .= $pad;
			}
			if ( mb_strlen( $target ) + mb_strlen( $extra ) < $n ) {
				$extra .= mb_substr( $pad, 0, $n - mb_strlen( $target ) - mb_strlen( $extra ) );
			}
			return [ $extra . $target ];
		} else {
			$env->log( 'debug', 'padleft no pad width', $args );
			return [];
		}
	}

	public function pf_padright( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		$target = $args[0]->k;
		$env = $this->env;
		if ( !$args[1] ) {
			return [];
		}

		// expand parameters 1 and 2
		$args = $params->getSlice( 1, 3 );
		$n = +( $args[0]->v );
		if ( $n > 0 ) {
			$pad = '0';
			if ( isset( $args[1] ) && $args[1]->v !== '' ) {
				$pad = $args[1]->v;
			}
			$padLength = mb_strlen( $pad );
			while ( ( mb_strlen( $target ) + $padLength ) < $n ) {
				$target .= $pad;
			}
			if ( mb_strlen( $target ) < $n ) {
				$target .= mb_substr( $pad, 0, $n - mb_strlen( $target ) );
			}
			return [ $target ];
		} else {
			$env->log( 'debug', 'padright no pad width', $args );
			return [];
		}
	}

	public function pf_tag( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		// Check http://www.mediawiki.org/wiki/Extension:TagParser for more info
		// about the #tag parser function.
		$target = $args[0]->k;
		if ( !$target ) {
			return [];
		} else {
			// remove tag-name
			array_shift( $args );
			$ret = $this->tag_worker( $target, $args );
			return $ret;
		}
	}

	private function tag_worker( $target, array $kvs ) {
		$tagTk = new TagTk( $target );
		$toks = [ $tagTk ];
		$tagAttribs = [];
		foreach ( $kvs as $kv ) {
			if ( $kv->k === '' ) {
				if ( is_array( $kv->v ) ) {
					PHPUtils::pushArray( $toks, $kv->v );
				} else {
					$toks[] = $kv->v;
				}
			} else {
				$tagAttribs[] = $kv;
			}
		}

		$tagTk->attribs = $tagAttribs;
		$toks[] = new EndTagTk( $target );
		return $toks;
	}

	public function pf_currentyear( $token, Frame $frame, Params $params ): array {
		return $this->pfTime_tokens( 'Y', [] );
	}

	public function pf_localyear( $token, Frame $frame, Params $params ): array {
		return $this->pfTimel_tokens( 'Y', [] );
	}

	public function pf_currentmonth( $token, Frame $frame, Params $params ): array {
		return $this->pfTime_tokens( 'm', [] );
	}

	public function pf_localmonth( $token, Frame $frame, Params $params ): array {
		return $this->pfTimel_tokens( 'm', [] );
	}

	public function pf_currentmonthname( $token, Frame $frame, Params $params ): array {
		return $this->pfTime_tokens( 'F', [] );
	}

	public function pf_localmonthname( $token, Frame $frame, Params $params ): array {
		return $this->pfTimel_tokens( 'F', [] );
	}

	public function pf_currentmonthabbrev( $token, Frame $frame, Params $params ): array {
		return $this->pfTime_tokens( 'M', [] );
	}

	public function pf_localmonthabbrev( $token, Frame $frame, Params $params ): array {
		return $this->pfTimel_tokens( 'M', [] );
	}

	public function pf_currentweek( $token, Frame $frame, Params $params ): array {
		$toks = $this->pfTime_tokens( 'W', [] );
		// Cast to int to remove padding, as in core
		$toks[0] = (string)(int)$toks[0];
		return $toks;
	}

	public function pf_localweek( $token, Frame $frame, Params $params ): array {
		$toks = $this->pfTimel_tokens( 'W', [] );
		// Cast to int to remove padding, as in core
		$toks[0] = (string)(int)$toks[0];
		return $toks;
	}

	public function pf_currentday( $token, Frame $frame, Params $params ): array {
		return $this->pfTime_tokens( 'j', [] );
	}

	public function pf_localday( $token, Frame $frame, Params $params ): array {
		return $this->pfTimel_tokens( 'j', [] );
	}

	public function pf_currentday2( $token, Frame $frame, Params $params ): array {
		return $this->pfTime_tokens( 'd', [] );
	}

	public function pf_localday2( $token, Frame $frame, Params $params ): array {
		return $this->pfTimel_tokens( 'd', [] );
	}

	public function pf_currentdow( $token, Frame $frame, Params $params ): array {
		return $this->pfTime_tokens( 'w', [] );
	}

	public function pf_localdow( $token, Frame $frame, Params $params ): array {
		return $this->pfTimel_tokens( 'w', [] );
	}

	public function pf_currentdayname( $token, Frame $frame, Params $params ): array {
		return $this->pfTime_tokens( 'l', [] );
	}

	public function pf_localdayname( $token, Frame $frame, Params $params ): array {
		return $this->pfTimel_tokens( 'l', [] );
	}

	public function pf_currenttime( $token, Frame $frame, Params $params ): array {
		return $this->pfTime_tokens( 'H:i', [] );
	}

	public function pf_localtime( $token, Frame $frame, Params $params ): array {
		return $this->pfTimel_tokens( 'H:i', [] );
	}

	public function pf_currenthour( $token, Frame $frame, Params $params ): array {
		return $this->pfTime_tokens( 'H', [] );
	}

	public function pf_localhour( $token, Frame $frame, Params $params ): array {
		return $this->pfTimel_tokens( 'H', [] );
	}

	public function pf_currenttimestamp( $token, Frame $frame, Params $params ): array {
		return $this->pfTime_tokens( 'YmdHis', [] );
	}

	public function pf_localtimestamp( $token, Frame $frame, Params $params ): array {
		return $this->pfTimel_tokens( 'YmdHis', [] );
	}

	public function pf_currentmonthnamegen( $token, Frame $frame, Params $params ): array {
		// XXX Actually use genitive form!
		$args = $params->args;
		return $this->pfTime_tokens( 'F', [] );
	}

	public function pf_localmonthnamegen( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		return $this->pfTimel_tokens( 'F', [] );
	}

	/*
	 * A first approximation of time stuff.
	 * TODO: Implement time spec (+ 1 day etc), check if formats are complete etc.
	 * See http://www.mediawiki.org/wiki/Help:Extension:ParserFunctions#.23time
	 * for the full list of requirements!
	 *
	 * First (very rough) approximation below based on
	 * http://jacwright.com/projects/javascript/date_format/, MIT licensed.
	 */
	public function pf_time( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		return $this->pfTime( $args[0]->k, array_slice( $args, 1 ) );
	}

	public function pf_timel( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		return $this->pfTime( $args[0]->k, array_slice( $args, 1 ), true );
	}

	private function pfTime_tokens( $target, $args ) {
		return $this->pfTime( $target, $args );
	}

	private function pfTimel_tokens( $target, $args ) {
		return $this->pfTime( $target, $args, true );
	}

	private function pfTime( $target, $args, $isLocal = false ) {
		$date = new DateTime( "now", new DateTimeZone( "UTC" ) );

		$timestamp = $this->env->getSiteConfig()->fakeTimestamp();
		if ( $timestamp ) {
			$date->setTimestamp( $timestamp );
		}
		if ( $isLocal ) {
			$date->setTimezone( new DateTimeZone( "-" . $this->env->getSiteConfig()->timezoneOffset() ) );
		}

		try {
			return [ $date->format( trim( $target ) ) ];
		} catch ( \Exception $e2 ) {
			$this->env->log( 'error', '#time ' . $e2 );
			return [ $date->format( 'D, d M Y H:i:s O' ) ];
		}
	}

	public function pf_localurl( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		$target = $args[0]->k;
		$env = $this->env;
		$args = array_slice( $args, 1 );
		$accum = [];
		foreach ( $args as $item ) {
			// FIXME: we are swallowing all errors
			$res = $this->expandKV( $item, $frame, '', 'text/x-mediawiki/expanded', false );
			PHPUtils::pushArray( $accum, $res );
		}

		return [
			$env->getSiteConfig()->script() . '?title=' .
			$env->normalizedTitleKey( $target ) . '&' .
			implode( '&', $accum )
		];
	}

	/* Stub section: Pick any of these and actually implement them!  */

	public function pf_formatnum( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		$target = $args[0]->k;
		return [ $target ];
	}

	public function pf_currentpage( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		$target = $args[0]->k;
		return [ $target ];
	}

	public function pf_pagenamee( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		$target = $args[0]->k;
		return [ explode( ':', $target, 2 )[1] ?? '' ];
	}

	public function pf_fullpagename( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		$target = $args[0]->k;
		return [ $target ?: ( $this->prefixedTitleText() ) ];
	}

	public function pf_fullpagenamee( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		$target = $args[0]->k;
		return [ $target ?: ( $this->prefixedTitleText() ) ];
	}

	public function pf_pagelanguage( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		// The language (code) of the current page.
		// Note: this is exposed as a mediawiki-internal code.
		$code = Utils::bcp47ToMwCode(
			$this->env->getPageConfig()->getPageLanguageBcp47()
		);
		return [ $code ];
	}

	public function pf_directionmark( $token, Frame $frame, Params $args ): array {
		// The directionality of the current page.
		$dir = $this->env->getPageConfig()->getPageLanguageDir();
		$mark = $dir === 'rtl' ? '&rlm;' : '&lrm;';
		// See Parser.php::getVariableValue()
		return [ Utils::decodeWtEntities( $mark ) ];
	}

	public function pf_dirmark( $token, Frame $frame, Params $args ): array {
		return $this->pf_directionmark( $token, $frame, $args );
	}

	public function pf_fullurl( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		$target = $args[0]->k;
		$target = str_replace( ' ', '_', $target ?: ( $this->prefixedTitleText() ) );
		$wikiConf = $this->env->getSiteConfig();
		$url = null;
		if ( $args[1] ) {
			$url = $wikiConf->server() .
				$wikiConf->script() .
				'?title=' . PHPUtils::encodeURIComponent( $target ) .
				'&' . $args[1]->k . '=' . $args[1]->v;
		} else {
			$url = $wikiConf->baseURI() .
				implode( '/',
					array_map( [ PHPUtils::class, 'encodeURIComponent' ],
						explode( '/', str_replace( ' ', '_', $target ) ) )
				);
		}

		return [ $url ];
	}

	public function pf_urlencode( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		$target = $args[0]->k;
		return [ PHPUtils::encodeURIComponent( trim( $target ) ) ];
	}

	/*
	 * The following items all depends on information from the Wiki, so are hard
	 * to implement independently. Some might require using action=parse in the
	 * API to get the value. See
	 * http://www.mediawiki.org/wiki/Parsoid#Token_stream_transforms,
	 * http://etherpad.wikimedia.org/ParserNotesExtensions and
	 * http://www.mediawiki.org/wiki/Wikitext_parser/Environment.
	 * There might be better solutions for some of these.
	 */
	public function pf_ifexist( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		return $this->expandKV( $args[1], $frame );
	}

	public function pf_pagesize( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		return [ '100' ];
	}

	public function pf_sitename( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		return [ 'MediaWiki' ];
	}

	private function encodeCharEntity( string $c, array &$tokens ) {
		$enc = Utils::entityEncodeAll( $c );
		$dp = new DataParsoid;
		$dp->src = $enc;
		$dp->srcContent = $c;
		$tokens[] = new TagTk( 'span',
			[ new KV( 'typeof', 'mw:Entity' ) ],
			$dp
		);
		$tokens[] = $c;
		$tokens[] = new EndTagTk( 'span', [], new DataParsoid );
	}

	public function pf_anchorencode( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		$target = $args[0]->k;

		// Parser::guessSectionNameFromWikiText, which invokes
		// Sanitizer::normalizeSectionNameWhitespace and
		// Sanitizer::escapeIdForLink, then calls
		// Sanitizer::safeEncodeAttribute on the result. See: T179544
		$target = trim( preg_replace( '/[ _]+/', ' ', $target ) );
		$target = Sanitizer::decodeCharReferences( $target );
		$target = Sanitizer::escapeIdForLink( $target );
		$pieces = preg_split(
			"/([\\{\\}\\[\\]|]|''|ISBN|RFC|PMID|__)/", $target, -1, PREG_SPLIT_DELIM_CAPTURE );

		$tokens = [];
		foreach ( $pieces as $i => $p ) {
			if ( ( $i % 2 ) === 0 ) {
				$tokens[] = $p;
			} elseif ( $p === "''" ) {
				$this->encodeCharEntity( $p[0], $tokens );
				$this->encodeCharEntity( $p[1], $tokens );
			} else {
				$this->encodeCharEntity( $p[0], $tokens );
				$tokens[] = substr( $p, 1 );
			}
		}

		return $tokens;
	}

	public function pf_protectionlevel( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		return [ '' ];
	}

	public function pf_ns( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		$nsid = null;
		$target = $args[0]->k;
		$env = $this->env;
		$normalizedTarget = str_replace( ' ', '_', mb_strtolower( $target ) );

		$siteConfig = $this->env->getSiteConfig();
		if ( $siteConfig->namespaceId( $normalizedTarget ) !== null ) {
			$nsid = $siteConfig->namespaceId( $normalizedTarget );
		} elseif ( $siteConfig->canonicalNamespaceId( $normalizedTarget ) ) {
			$nsid = $siteConfig->canonicalNamespaceId( $normalizedTarget );
		}

		if ( $nsid !== null && $siteConfig->namespaceName( $nsid ) ) {
			$target = $siteConfig->namespaceName( $nsid );
		}
		// FIXME: What happens in the else case above?
		return [ $target ];
	}

	public function pf_subjectspace( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		return [ 'Main' ];
	}

	public function pf_talkspace( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		return [ 'Talk' ];
	}

	public function pf_numberofarticles( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		return [ '1' ];
	}

	public function pf_language( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		return [ $args[0]->k ];
	}

	public function pf_contentlanguage( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		// Despite the name, this returns the wiki's default interface language
		// ($wgLanguageCode), *not* the language of the current page content.
		// Note: this is exposed as a mediawiki-internal code.
		$code = Utils::bcp47ToMwCode(
			$this->env->getSiteConfig()->langBcp47()
		);
		return [ $code ];
	}

	public function pf_contentlang( $token, Frame $frame, Params $params ): array {
		return $this->pf_contentlanguage( $token, $frame, $params );
	}

	public function pf_numberoffiles( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		return [ '2' ];
	}

	public function pf_namespace( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		$target = $args[0]->k;
		// The JS implementation is broken
		$pieces = explode( ':', $target );
		return [ count( $pieces ) > 1 ? $pieces[0] : 'Main' ];
	}

	public function pf_namespacee( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		$target = $args[0]->k;
		// The JS implementation is broken
		$pieces = explode( ':', $target );
		return [ count( $pieces ) > 1 ? $pieces[0] : 'Main' ];
	}

	public function pf_namespacenumber( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		$a = explode( ':', $args[0]->k );
		$target = array_pop( $a );
		return [ (string)$this->env->getSiteConfig()->namespaceId( $target ) ];
	}

	public function pf_pagename( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		return [ $this->prefixedTitleText() ];
	}

	public function pf_pagenamebase( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		return [ $this->prefixedTitleText() ];
	}

	public function pf_scriptpath( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		return [ $this->env->getSiteConfig()->scriptpath() ];
	}

	public function pf_server( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		$dataParsoid = $token->dataParsoid->clone();
		return [
			new TagTk( 'a', [
					new KV( 'rel', 'nofollow' ),
					new KV( 'class', 'external free' ),
					new KV( 'href', $this->env->getSiteConfig()->server() ),
					new KV( 'typeof', 'mw:ExtLink/URL' )
				], $dataParsoid
			),
			$this->env->getSiteConfig()->server(),
			new EndTagTk( 'a' )
		];
	}

	public function pf_servername( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		$server = $this->env->getSiteConfig()->server();
		return [ preg_replace( '#^https?://#', '', $server, 1 ) ];
	}

	public function pf_talkpagename( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		$title = $this->prefixedTitleText();
		return [ preg_replace( '/^[^:]:/', 'Talk:', $title, 1 ) ];
	}

	public function pf_defaultsort( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		$key = $args[0]->k;
		return [
			new SelfclosingTagTk( 'meta', [
					new KV( 'property', 'mw:PageProp/categorydefaultsort' ),
					new KV( 'content', trim( $key ) )
				]
			)
		];
	}

	public function pf_displaytitle( $token, Frame $frame, Params $params ): array {
		$args = $params->args;
		$key = $args[0]->k;
		return [
			new SelfclosingTagTk( 'meta', [
					new KV( 'property', 'mw:PageProp/displaytitle' ),
					new KV( 'content', trim( $key ) )
				]
			)
		];
	}

	public function pf_equal( $token, Frame $frame, Params $params ): array {
		return [ '=' ];
	}

	// TODO: #titleparts, SUBJECTPAGENAME, BASEPAGENAME. SUBPAGENAME, DEFAULTSORT
}
