<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt;

use Closure;
use Exception;
use stdClass;
use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\Core\InternalException;
use Wikimedia\Parsoid\DOM\Comment;
use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\Html2Wt\ConstrainedText\ConstrainedText;
use Wikimedia\Parsoid\Html2Wt\DOMHandlers\DOMHandler;
use Wikimedia\Parsoid\Html2Wt\DOMHandlers\DOMHandlerFactory;
use Wikimedia\Parsoid\Tokens\KV;
use Wikimedia\Parsoid\Tokens\TagTk;
use Wikimedia\Parsoid\Tokens\Token;
use Wikimedia\Parsoid\Utils\ContentUtils;
use Wikimedia\Parsoid\Utils\DiffDOMUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\Title;
use Wikimedia\Parsoid\Utils\TokenUtils;
use Wikimedia\Parsoid\Utils\Utils;
use Wikimedia\Parsoid\Utils\WTUtils;
use Wikimedia\Parsoid\Wikitext\Consts;

/**
 * Wikitext to HTML serializer.
 * Serializes a chunk of tokens or an HTML DOM to MediaWiki's wikitext flavor.
 *
 * This serializer is designed to eventually
 * - accept arbitrary HTML and
 * - serialize that to wikitext in a way that round-trips back to the same
 *   HTML DOM as far as possible within the limitations of wikitext.
 *
 * Not much effort has been invested so far on supporting
 * non-Parsoid/VE-generated HTML. Some of this involves adaptively switching
 * between wikitext and HTML representations based on the values of attributes
 * and DOM context. A few special cases are already handled adaptively
 * (multi-paragraph list item contents are serialized as HTML tags for
 * example, generic A elements are serialized to HTML A tags), but in general
 * support for this is mostly missing.
 *
 * Example issue:
 * ```
 * <h1><p>foo</p></h1> will serialize to =\nfoo\n= whereas the
 *        correct serialized output would be: =<p>foo</p>=
 * ```
 *
 * What to do about this?
 * - add a generic 'can this HTML node be serialized to wikitext in this
 *   context' detection method and use that to adaptively switch between
 *   wikitext and HTML serialization.
 *
 */
class WikitextSerializer {

	/** @var string[] */
	private const IGNORED_ATTRIBUTES = [
		'data-parsoid' => true,
		'data-ve-changed' => true,
		'data-parsoid-changed' => true,
		'data-parsoid-diff' => true,
		'data-parsoid-serialize' => true,
		DOMDataUtils::DATA_OBJECT_ATTR_NAME => true,
	];

	/** @var string[] attribute name => value regexp */
	private const PARSOID_ATTRIBUTES = [
		'about' => '/^#mwt\d+$/D',
		'typeof' => '/(^|\s)mw:\S+/',
	];

	/** @var string Regexp */
	private const TRAILING_COMMENT_OR_WS_AFTER_NL_REGEXP
		= '/\n(\s|' . Utils::COMMENT_REGEXP_FRAGMENT . ')*$/D';

	/** @var string Regexp */
	private const FORMATSTRING_REGEXP =
		'/^(\n)?(\{\{ *_+)(\n? *\|\n? *_+ *= *)(_+)(\n? *\}\})(\n)?$/D';

	/** @var string Regexp for testing whether nowiki added around heading-like wikitext is needed */
	private const COMMENT_OR_WS_REGEXP = '/^(\s|' . Utils::COMMENT_REGEXP_FRAGMENT . ')*$/D';

	/** @var string Regexp for testing whether nowiki added around heading-like wikitext is needed */
	private const HEADING_NOWIKI_REGEXP = '/^(?:' . Utils::COMMENT_REGEXP_FRAGMENT . ')*'
		. '<nowiki>(=+[^=]+=+)<\/nowiki>(.+)$/D';

	/** @var array string[] */
	private static $separatorREs = [
		'pureSepRE' => '/^[ \t\r\n]*$/D',
		'sepPrefixWithNlsRE' => '/^[ \t]*\n+[ \t\r\n]*/',
		'sepSuffixWithNlsRE' => '/\n[ \t\r\n]*$/D',
	];

	/** @var WikitextEscapeHandlers */
	public $wteHandlers;

	/** @var Env */
	public $env;

	/** @var SerializerState */
	private $state;

	/** @var string Log type for trace() */
	private $logType;

	/**
	 * @param Env $env
	 * @param array $options List of options for serialization:
	 *   - logType: (string)
	 *   - extName: (string)
	 */
	public function __construct( Env $env, $options ) {
		$this->env = $env;
		$this->logType = $options['logType'] ?? 'trace/wts';
		$this->state = new SerializerState( $this, $options );
		$this->wteHandlers = new WikitextEscapeHandlers( $env, $options['extName'] ?? null );
	}

	/**
	 * Main link handler.
	 * @param Element $node
	 * Used in multiple tag handlers (<a> and <link>), and hence added as top-level method
	 */
	public function linkHandler( Element $node ): void {
		LinkHandlerUtils::linkHandler( $this->state, $node );
	}

	/**
	 * @param Element $node
	 */
	public function languageVariantHandler( Node $node ): void {
		LanguageVariantHandler::handleLanguageVariant( $this->state, $node );
	}

	/**
	 * Escape wikitext-like strings in '$text' so that $text renders as a plain string
	 * when rendered as HTML. The escaping is done based on the context in which $text
	 * is present (ex: start-of-line, in a link, etc.)
	 *
	 * @param SerializerState $state
	 * @param string $text
	 * @param array $opts
	 *   - node: (Node)
	 *   - isLastChild: (bool)
	 * @return string
	 */
	public function escapeWikitext( SerializerState $state, string $text, array $opts ): string {
		return $this->wteHandlers->escapeWikitext( $state, $text, $opts );
	}

	public function domToWikitext(
		array $opts, DocumentFragment $node
	): string {
		$opts['logType'] = $this->logType;
		$serializer = new WikitextSerializer( $this->env, $opts );
		return $serializer->serializeDOM( $node );
	}

	public function htmlToWikitext( array $opts, string $html ): string {
		$domFragment = ContentUtils::createAndLoadDocumentFragment(
			$this->env->topLevelDoc, $html, [ 'markNew' => true ]
		);
		return $this->domToWikitext( $opts, $domFragment );
	}

	public function getAttributeKey( Element $node, string $key ): string {
		$tplAttrs = DOMDataUtils::getDataMw( $node )->attribs ?? [];
		foreach ( $tplAttrs as $attr ) {
			// If this attribute's key is generated content,
			// serialize HTML back to generator wikitext.
			// PORT-FIXME: bool check might not be safe. Need documentation on attrib format.
			if ( ( $attr[0]->txt ?? null ) === $key && isset( $attr[0]->html ) ) {
				return $this->htmlToWikitext( [
					'env' => $this->env,
					'onSOL' => false,
				], $attr[0]->html );
			}
		}
		return $key;
	}

	/**
	 * @param Element $node
	 * @param string $key Attribute name.
	 * @return ?string The wikitext value, or null if the attribute is not present.
	 */
	public function getAttributeValue( Element $node, string $key ): ?string {
		$tplAttrs = DOMDataUtils::getDataMw( $node )->attribs ?? [];
		foreach ( $tplAttrs as $attr ) {
			// If this attribute's value is generated content,
			// serialize HTML back to generator wikitext.
			// PORT-FIXME: not type safe. Need documentation on attrib format.
			if ( ( $attr[0] === $key || ( $attr[0]->txt ?? null ) === $key )
				 // Only return here if the value is generated (ie. .html),
				 // it may just be in .txt form.
				 && isset( $attr[1]->html )
				 // !== null is required. html:"" will serialize to "" and
				 // will be returned here. This is used to suppress the =".."
				 // string in the attribute in scenarios where the template
				 // generates a "k=v" string.
				 // Ex: <div {{1x|1=style='color:red'}}>foo</div>
				 && $attr[1]->html !== null
			) {
				return $this->htmlToWikitext( [
					'env' => $this->env,
					'onSOL' => false,
					'inAttribute' => true,
				], $attr[1]->html );
			}
		}
		return null;
	}

	/**
	 * @param Element $node
	 * @param string $key
	 * @return array|null A tuple in {@link WTSUtils::getShadowInfo()} format,
	 *   with an extra 'fromDataMW' flag.
	 */
	public function getAttributeValueAsShadowInfo( Element $node, string $key ): ?array {
		$v = $this->getAttributeValue( $node, $key );
		if ( $v === null ) {
			return $v;
		}
		return [
			'value' => $v,
			'modified' => false,
			'fromsrc' => true,
			'fromDataMW' => true,
		];
	}

	/**
	 * @param Element $dataMWnode
	 * @param Element $htmlAttrNode
	 * @param string $key
	 * @return array A tuple in {@link WTSUtils::getShadowInfo()} format,
	 *   possibly with an extra 'fromDataMW' flag.
	 */
	public function serializedImageAttrVal(
		Element $dataMWnode, Element $htmlAttrNode, string $key
	): array {
		$v = $this->getAttributeValueAsShadowInfo( $dataMWnode, $key );
		return $v ?: WTSUtils::getAttributeShadowInfo( $htmlAttrNode, $key );
	}

	public function serializedAttrVal( Element $node, string $name ): array {
		return $this->serializedImageAttrVal( $node, $node, $name );
	}

	/**
	 * Check if token needs escaping
	 *
	 * @param string $name
	 * @return bool
	 */
	public function tagNeedsEscaping( string $name ): bool {
		return WTUtils::isAnnOrExtTag( $this->env, $name );
	}

	public function wrapAngleBracket( Token $token, string $inner ): string {
		if (
			$this->tagNeedsEscaping( $token->getName() ) &&
			!(
				// Allow for html tags that shadow extension tags found in source
				// to roundtrip.  They only parse as html tags if they are unclosed,
				// since extension tags bail on parsing without closing tags.
				//
				// This only applies when wrapAngleBracket() is being called for
				// start tags, but we wouldn't be here if it was autoInsertedEnd
				// anyways.
				isset( Consts::$Sanitizer['AllowedLiteralTags'][$token->getName()] ) &&
				!empty( $token->dataParsoid->autoInsertedEnd )
			)
		) {
			return "&lt;{$inner}&gt;";
		}
		return "<$inner>";
	}

	public function serializeHTMLTag( Element $node, bool $wrapperUnmodified ): string {
		// TODO(arlolra): As of 1.3.0, html pre is considered an extension
		// and wrapped in encapsulation.  When that version is no longer
		// accepted for serialization, we can remove this backwards
		// compatibility code.
		//
		// 'inHTMLPre' flag has to be updated always,
		// even when we are selsering in the wrapperUnmodified case.
		$token = WTSUtils::mkTagTk( $node );
		if ( $token->getName() === 'pre' ) {
			// html-syntax pre is very similar to nowiki
			$this->state->inHTMLPre = true;
		}

		if ( $wrapperUnmodified ) {
			$dsr = DOMDataUtils::getDataParsoid( $node )->dsr;
			return $this->state->getOrigSrc( $dsr->start, $dsr->innerStart() ) ?? '';
		}

		$da = $token->dataParsoid;
		if ( !empty( $da->autoInsertedStart ) ) {
			return '';
		}

		$close = '';
		if ( ( Utils::isVoidElement( $token->getName() ) && empty( $da->noClose ) ) ||
			!empty( $da->selfClose )
		) {
			$close = ' /';
		}

		$sAttribs = $this->serializeAttributes( $node, $token );
		if ( strlen( $sAttribs ) > 0 ) {
			$sAttribs = ' ' . $sAttribs;
		}

		// srcTagName cannot be '' so, it is okay to use ?? operator
		$tokenName = $da->srcTagName ?? $token->getName();
		$inner = "{$tokenName}{$sAttribs}{$close}";
		return $this->wrapAngleBracket( $token, $inner );
	}

	/**
	 * @param Element $node
	 * @param bool $wrapperUnmodified
	 * @return string
	 */
	public function serializeHTMLEndTag( Element $node, $wrapperUnmodified ): string {
		if ( $wrapperUnmodified ) {
			$dsr = DOMDataUtils::getDataParsoid( $node )->dsr;
			return $this->state->getOrigSrc( $dsr->innerEnd(), $dsr->end ) ?? '';
		}

		$token = WTSUtils::mkEndTagTk( $node );
		if ( $token->getName() === 'pre' ) {
			$this->state->inHTMLPre = false;
		}

		// srcTagName cannot be '' so, it is okay to use ?? operator
		$tokenName = $token->dataParsoid->srcTagName ?? $token->getName();
		$ret = '';

		if ( empty( $token->dataParsoid->autoInsertedEnd )
			&& !Utils::isVoidElement( $token->getName() )
			&& empty( $token->dataParsoid->selfClose )
		) {
			$ret = $this->wrapAngleBracket( $token, "/{$tokenName}" );
		}

		return $ret;
	}

	public function serializeAttributes( Element $node, Token $token, bool $isWt = false ): string {
		$attribs = $token->attribs;

		$out = [];
		foreach ( $attribs as $kv ) {
			// Tokens created during html2wt don't have nested tokens for keys.
			// But, they could be integers but we want strings below.
			$k = (string)$kv->k;
			$v = null;
			$vInfo = null;

			// Unconditionally ignore
			// (all of the IGNORED_ATTRIBUTES should be filtered out earlier,
			// but ignore them here too just to make sure.)
			if ( isset( self::IGNORED_ATTRIBUTES[$k] ) || $k === 'data-mw' ) {
				continue;
			}

			// Ignore parsoid-like ids. They may have been left behind
			// by clients and shouldn't be serialized. This can also happen
			// in v2/v3 API when there is no matching data-parsoid entry found
			// for this id.
			if ( $k === 'id' && preg_match( '/^mw[\w-]{2,}$/D', $kv->v ) ) {
				if ( WTUtils::isNewElt( $node ) ) {
					// Parsoid id found on element without a matching data-parsoid. Drop it!
				} else {
					$vInfo = $token->getAttributeShadowInfo( $k );
					if ( !$vInfo['modified'] && $vInfo['fromsrc'] ) {
						$out[] = $k . '=' . '"' . str_replace( '"', '&quot;', $vInfo['value'] ) . '"';
					}
				}
				continue;
			}

			// Parsoid auto-generates ids for headings and they should
			// be stripped out, except if this is not auto-generated id.
			if ( $k === 'id' && DOMUtils::isHeading( $node ) ) {
				if ( !empty( DOMDataUtils::getDataParsoid( $node )->reusedId ) ) {
					$vInfo = $token->getAttributeShadowInfo( $k );
					// PORT-FIXME: is this safe? value could be a token or token array
					$out[] = $k . '="' . str_replace( '"', '&quot;', $vInfo['value'] ) . '"';
				}
				continue;
			}

			// Strip Parsoid-inserted class="mw-empty-elt" attributes
			if ( $k === 'class'
				 && isset( Consts::$Output['FlaggedEmptyElts'][DOMCompat::nodeName( $node )] )
			) {
				$kv->v = preg_replace( '/\bmw-empty-elt\b/', '', $kv->v, 1 );
				if ( !$kv->v ) {
					continue;
				}
			}

			// Strip other Parsoid-generated values
			//
			// FIXME: Given that we are currently escaping about/typeof keys
			// that show up in wikitext, we could unconditionally strip these
			// away right now.
			$parsoidValueRegExp = self::PARSOID_ATTRIBUTES[$k] ?? null;
			if ( $parsoidValueRegExp && preg_match( $parsoidValueRegExp, $kv->v ) ) {
				$v = preg_replace( $parsoidValueRegExp, '', $kv->v );
				if ( $v ) {
					$out[] = $k . '="' . $v . '"';
				}
				continue;
			}

			if ( strlen( $k ) > 0 ) {
				$vInfo = $token->getAttributeShadowInfo( $k );
				$v = $vInfo['value'];
				// Deal with k/v's that were template-generated
				$kk = $this->getAttributeKey( $node, $k );
				// Pass in $k, not $kk since $kk can potentially
				// be original wikitext source for 'k' rather than
				// the string value of the key.
				$vv = $this->getAttributeValue( $node, $k ) ?? $v;
				// Remove encapsulation from protected attributes
				// in pegTokenizer.pegjs:generic_newline_attribute
				$kk = preg_replace( '/^data-x-/i', '', $kk, 1 );
				// PORT-FIXME: is this type safe? $vv could be a ConstrainedText
				if ( $vv !== null && strlen( $vv ) > 0 ) {
					if ( !$vInfo['fromsrc'] && !$isWt ) {
						// Escape wikitext entities
						$vv = str_replace( '>', '&gt;', Utils::escapeWtEntities( $vv ) );
					}
					$out[] = $kk . '="' . str_replace( '"', '&quot;', $vv ) . '"';
				} elseif ( preg_match( '/[{<]/', $kk ) ) {
					// Templated, <*include*>, or <ext-tag> generated
					$out[] = $kk;
				} else {
					$out[] = $kk . '=""';
				}
				continue;
			// PORT-FIXME: is this type safe? $k->v could be a Token or Token array
			} elseif ( strlen( $kv->v ) ) {
				// not very likely..
				$out[] = $kv->v;
			}
		}

		// SSS FIXME: It can be reasonably argued that we can permanently delete
		// dangerous and unacceptable attributes in the interest of safety/security
		// and the resultant dirty diffs should be acceptable.  But, this is
		// something to do in the future once we have passed the initial tests
		// of parsoid acceptance.
		//
		// 'a' data attribs -- look for attributes that were removed
		// as part of sanitization and add them back
		$dataParsoid = $token->dataParsoid;
		if ( isset( $dataParsoid->a ) && isset( $dataParsoid->sa ) ) {
			$aKeys = array_keys( $dataParsoid->a );
			foreach ( $aKeys as $k ) {
				// Attrib not present -- sanitized away!
				if ( !KV::lookupKV( $attribs, (string)$k ) ) {
					$v = $dataParsoid->sa[$k] ?? null;
					// PORT-FIXME check type
					if ( $v !== null && $v !== '' ) {
						$out[] = $k . '="' . str_replace( '"', '&quot;', $v ) . '"';
					} else {
						// at least preserve the key
						$out[] = $k;
					}
				}
			}
		}
		// XXX: round-trip optional whitespace / line breaks etc
		return implode( ' ', $out );
	}

	/**
	 * FIXME: Get rid of this function after content version 2.2.0 has expired from caches.
	 *
	 * @param Element $node
	 */
	public function handleLIHackIfApplicable( Element $node ): void {
		$liHackSrc = DOMDataUtils::getDataParsoid( $node )->liHackSrc ?? null;
		$prev = DiffDOMUtils::previousNonSepSibling( $node );

		// If we are dealing with an LI hack, then we must ensure that
		// we are dealing with either
		//
		//   1. A node with no previous sibling inside of a list.
		//
		//   2. A node whose previous sibling is a list element.
		if ( $liHackSrc !== null
			// Case 1
			&& ( ( $prev === null && DOMUtils::isList( $node->parentNode ) )
				// Case 2
				|| ( $prev !== null && DOMUtils::isListItem( $prev ) ) )
		) {
			$this->state->emitChunk( $liHackSrc, $node );
		}
	}

	private function formatStringSubst( string $format, string $value, bool $forceTrim ): string {
		// PORT-FIXME: JS is more agressive and removes various unicode whitespaces
		// (most notably nbsp). Does that matter?
		if ( $forceTrim ) {
			$value = trim( $value );
		}
		return preg_replace_callback( '/_+/', static function ( $m ) use ( $value ) {
			if ( $value === '' ) {
				return $value;
			}
			$hole = $m[0];
			$holeLen = strlen( $hole );
			$valueLen = mb_strlen( $value );
			return $holeLen <= $valueLen ? $value : $value . str_repeat( ' ', $holeLen - $valueLen );
		}, $format, 1 );
	}

	/**
	 * Generates a template parameter sort function that tries to preserve existing ordering
	 * but also to follow the order prescribed by the templatedata.
	 * @param array $dpArgInfo
	 * @param ?array $tplData
	 * @param array $dataMwKeys
	 * @return Closure
	 */
	private function createParamComparator(
		array $dpArgInfo, ?array $tplData, array $dataMwKeys
	): Closure {
		// Record order of parameters in new data-mw
		$newOrder = [];
		foreach ( $dataMwKeys as $i => $key ) {
			$newOrder[$key] = [ 'order' => $i ];
		}
		// Record order of parameters in templatedata (if present)
		$tplDataOrder = [];
		$aliasMap = [];
		$keys = [];
		if ( $tplData && isset( $tplData['paramOrder'] ) ) {
			foreach ( $tplData['paramOrder'] as $i => $key ) {
				$tplDataOrder[$key] = [ 'order' => $i ];
				$aliasMap[$key] = [ 'key' => $key, 'order' => -1 ];
				$keys[] = $key;
				// Aliases have the same sort order as the main name.
				$aliases = $tplData['params'][$key]['aliases'] ?? [];
				foreach ( $aliases as $j => $alias ) {
					$aliasMap[$alias] = [ 'key' => $key, 'order' => $j ];
				}
			}
		}
		// Record order of parameters in original wikitext (from data-parsoid)
		$origOrder = [];
		foreach ( $dpArgInfo as $i => $argInfo ) {
			$origOrder[$argInfo->k] = [ 'order' => $i, 'dist' => 0 ];
		}
		// Canonical parameter key gets the same order as an alias parameter
		// found in the original wikitext.
		foreach ( $dpArgInfo as $i => $argInfo ) {
			$canon = $aliasMap[$argInfo->k] ?? null;
			if ( $canon !== null && !array_key_exists( $canon['key'], $origOrder ) ) {
				$origOrder[$canon['key']] = $origOrder[$argInfo->k];
			}
		}
		// Find the closest "original parameter" for each templatedata parameter,
		// so that newly-added parameters are placed near the parameters which
		// templatedata says they should be adjacent to.
		$nearestOrder = $origOrder;
		$reduceF = static function ( $acc, $val ) use ( &$origOrder, &$nearestOrder ) {
			if ( isset( $origOrder[$val] ) ) {
				$acc = $origOrder[$val];
			}
			if ( !( isset( $nearestOrder[$val] ) && $nearestOrder[$val]['dist'] < $acc['dist'] ) ) {
				$nearestOrder[$val] = $acc;
			}
			return [ 'order' => $acc['order'], 'dist' => $acc['dist'] + 1 ];
		};
		// Find closest original parameter before the key.
		// @phan-suppress-next-line PhanPluginUseReturnValueInternalKnown
		array_reduce( $keys, $reduceF, [ 'order' => -1, 'dist' => 2 * count( $keys ) ] );
		// Find closest original parameter after the key.
		// @phan-suppress-next-line PhanPluginUseReturnValueInternalKnown
		array_reduce( array_reverse( $keys ), $reduceF,
			[ 'order' => count( $origOrder ), 'dist' => count( $keys ) ] );

		// Helper function to return a large number if the given key isn't
		// in the sort order map
		$big = max( count( $nearestOrder ), count( $newOrder ) );
		$defaultGet = static function ( $map, $key1, $key2 = null ) use ( &$big ) {
			$key = ( !$key2 || isset( $map[$key1] ) ) ? $key1 : $key2;
			return $map[$key]['order'] ?? $big;
		};

		return static function ( $a, $b ) use (
			&$aliasMap, &$defaultGet, &$nearestOrder, &$tplDataOrder, &$newOrder
		) {
			$aCanon = $aliasMap[$a] ?? [ 'key' => $a, 'order' => -1 ];
			$bCanon = $aliasMap[$b] ?? [ 'key' => $b, 'order' => -1 ];
			// primary key is `nearestOrder` (nearest original parameter)
			$aOrder = $defaultGet( $nearestOrder, $a, $aCanon['key'] );
			$bOrder = $defaultGet( $nearestOrder, $b, $bCanon['key'] );
			if ( $aOrder !== $bOrder ) {
				return $aOrder - $bOrder;
			}
			// secondary key is templatedata order
			if ( $aCanon['key'] === $bCanon['key'] ) {
				return $aCanon['order'] - $bCanon['order'];
			}
			$aOrder = $defaultGet( $tplDataOrder, $aCanon['key'] );
			$bOrder = $defaultGet( $tplDataOrder, $bCanon['key'] );
			if ( $aOrder !== $bOrder ) {
				return $aOrder - $bOrder;
			}
			// tertiary key is original input order (makes sort stable)
			$aOrder = $defaultGet( $newOrder, $a );
			$bOrder = $defaultGet( $newOrder, $b );
			return $aOrder - $bOrder;
		};
	}

	/**
	 * Serialize part of a templatelike expression.
	 * @param SerializerState $state
	 * @param string $buf
	 * @param Element $node
	 * @param string $type The type of the part to be serialized. One of template, templatearg,
	 *   parserfunction.
	 * @param stdClass $part The expression fragment to serialize. See $srcParts
	 *   in serializeFromParts() for format.
	 * @param ?array $tplData Templatedata, see
	 *   https://github.com/wikimedia/mediawiki-extensions-TemplateData/blob/master/Specification.md
	 * @param mixed $prevPart Previous part. See $srcParts in serializeFromParts().
	 * @param mixed $nextPart Next part. See $srcParts in serializeFromParts().
	 * @return string
	 */
	private function serializePart(
		SerializerState $state, string $buf, Element $node, string $type, stdClass $part,
		?array $tplData, $prevPart, $nextPart
	): string {
		// Parse custom format specification, if present.
		$defaultBlockSpc = "{{_\n| _ = _\n}}"; // "block"
		$defaultInlineSpc = '{{_|_=_}}'; // "inline"

		$format = isset( $tplData['format'] ) ? strtolower( $tplData['format'] ) : null;
		if ( $format === 'block' ) {
			$format = $defaultBlockSpc;
		} elseif ( $format === 'inline' ) {
			$format = $defaultInlineSpc;
		}
		// Check format string for validity.
		preg_match( self::FORMATSTRING_REGEXP, $format ?? '', $parsedFormat );
		if ( !$parsedFormat ) {
			preg_match( self::FORMATSTRING_REGEXP, $defaultInlineSpc, $parsedFormat );
			$format = null; // Indicates that no valid custom format was present.
		}
		$formatSOL = $parsedFormat[1] ?? '';
		$formatStart = $parsedFormat[2] ?? '';
		$formatParamName = $parsedFormat[3] ?? '';
		$formatParamValue = $parsedFormat[4] ?? '';
		$formatEnd = $parsedFormat[5] ?? '';
		$formatEOL = $parsedFormat[6] ?? '';
		$forceTrim = ( $format !== null ) || WTUtils::isNewElt( $node );

		// Shoehorn formatting of top-level templatearg wikitext into this code.
		if ( $type === 'templatearg' ) {
			$formatStart = preg_replace( '/{{/', '{{{', $formatStart, 1 );
			$formatEnd = preg_replace( '/}}/', '}}}', $formatEnd, 1 );
		}

		// handle SOL newline requirement
		if ( $formatSOL && !str_ends_with( ( $prevPart !== null ) ? $buf : ( $state->sep->src ?? '' ), "\n" ) ) {
			$buf .= "\n";
		}

		// open the transclusion
		$tgt = $part->target;
		'@phan-var stdClass $tgt';
		$buf .= $this->formatStringSubst( $formatStart, $tgt->wt, $forceTrim );

		// Short-circuit transclusions without params
		$paramKeys = array_keys( get_object_vars( $part->params ) );
		if ( !$paramKeys ) {
			if ( substr( $formatEnd, 0, 1 ) === "\n" ) {
				$formatEnd = substr( $formatEnd, 1 );
			}
			return $buf . $formatEnd;
		}

		// Trim whitespace from data-mw keys to deal with non-compliant
		// clients. Make sure param info is accessible for the stripped key
		// since later code will be using the stripped key always.
		$tplKeysFromDataMw = array_map( static function ( $key ) use ( $part ) {
			$strippedKey = trim( (string)$key );
			if ( $key !== $strippedKey ) {
				$part->params->{$strippedKey} = $part->params->{$key};
			}
			return $strippedKey;
		}, $paramKeys );

		// Per-parameter info from data-parsoid for pre-existing parameters
		$dp = DOMDataUtils::getDataParsoid( $node );
		// Account for clients not setting the `i`, see T238721
		$dpArgInfo = isset( $part->i ) ? ( $dp->pi[$part->i] ?? [] ) : [];

		// Build a key -> arg info map
		$dpArgInfoMap = [];
		foreach ( $dpArgInfo as $info ) {
			$dpArgInfoMap[$info->k] = $info;
		}

		// 1. Process all parameters and build a map of
		//    arg-name -> [serializeAsNamed, name, value]
		//
		// 2. Serialize tpl args in required order
		//
		// 3. Format them according to formatParamName/formatParamValue

		$kvMap = [];
		foreach ( $tplKeysFromDataMw as $key ) {
			$param = $part->params->{$key};
			$argInfo = $dpArgInfoMap[$key] ?? [];

			// TODO: Other formats?
			// Only consider the html parameter if the wikitext one
			// isn't present at all. If it's present but empty,
			// that's still considered a valid parameter.
			if ( property_exists( $param, 'wt' ) ) {
				$value = $param->wt;
			} elseif ( property_exists( $param, 'html' ) ) {
				$value = $this->htmlToWikitext( [ 'env' => $this->env ], $param->html );
			} else {
				$this->env->log(
					'error',
					"params in data-mw part is missing wt/html for $key. " .
						"Serializing as empty string.",
					"data-mw part: " . json_encode( $part )
				);
				$value = "";
			}

			Assert::invariant( is_string( $value ), "For param: $key, wt property should be a string '
				. 'but got: $value" );

			$serializeAsNamed = !empty( $argInfo->named );

			// The name is usually equal to the parameter key, but
			// if there's a key->wt attribute, use that.
			$name = null;
			if ( isset( $param->key->wt ) ) {
				$name = $param->key->wt;
				// And make it appear even if there wasn't any data-parsoid information.
				$serializeAsNamed = true;
			} else {
				$name = $key;
			}

			// Use 'k' as the key, not 'name'.
			//
			// The normalized form of 'k' is used as the key in both
			// data-parsoid and data-mw. The full non-normalized form
			// is present in '$param->key->wt'
			$kvMap[$key] = [ 'serializeAsNamed' => $serializeAsNamed, 'name' => $name, 'value' => $value ];
		}

		$argOrder = array_keys( $kvMap );
		usort( $argOrder, $this->createParamComparator( $dpArgInfo, $tplData, $argOrder ) );

		$argIndex = 1;
		$numericIndex = 1;

		$numPositionalArgs = 0;
		foreach ( $dpArgInfo as $pi ) {
			if ( isset( $part->params->{$pi->k} ) && empty( $pi->named ) ) {
				$numPositionalArgs++;
			}
		}

		$argBuf = [];
		foreach ( $argOrder as $param ) {
			$kv = $kvMap[$param];
			// Add nowiki escapes for the arg value, as required
			$escapedValue = $this->wteHandlers->escapeTplArgWT( $kv['value'], [
				'serializeAsNamed' => $kv['serializeAsNamed'] || $param !== $numericIndex,
				'type' => $type,
				'argPositionalIndex' => $numericIndex,
				'numPositionalArgs' => $numPositionalArgs,
				'argIndex' => $argIndex++,
				'numArgs' => count( $tplKeysFromDataMw ),
			] );
			if ( $escapedValue['serializeAsNamed'] ) {
				// WS trimming for values of named args
				$argBuf[] = [ 'dpKey' => $param, 'name' => $kv['name'], 'value' => trim( $escapedValue['v'] ) ];
			} else {
				$numericIndex++;
				// No WS trimming for positional args
				$argBuf[] = [ 'dpKey' => $param, 'name' => null, 'value' => $escapedValue['v'] ];
			}
		}

		// If no explicit format is provided, default format is:
		// - 'inline' for new args
		// - whatever format is available from data-parsoid for old args
		// (aka, overriding formatParamName/formatParamValue)
		//
		// If an unedited node OR if paramFormat is unspecified,
		// this strategy prevents unnecessary normalization
		// of edited transclusions which don't have valid
		// templatedata formatting information.

		// "magic case": If the format string ends with a newline, an extra newline is added
		// between the template name and the first parameter.

		foreach ( $argBuf as $arg ) {
			$name = $arg['name'];
			$val = $arg['value'];
			if ( $name === null ) {
				// We are serializing a positional parameter.
				// Whitespace is significant for these and
				// formatting would change semantics.
				$name = '';
				$modFormatParamName = '|_';
				$modFormatParamValue = '_';
			} elseif ( $name === '' ) {
				// No spacing for blank parameters ({{foo|=bar}})
				// This should be an edge case and probably only for
				// inline-formatted templates, but we are consciously
				// forcing this default here. Can revisit if this is
				// ever a problem.
				$modFormatParamName = '|_=';
				$modFormatParamValue = '_';
			} else {
				// Preserve existing spacing, esp if there was a comment
				// embedded in it. Otherwise, follow TemplateData's lead.
				// NOTE: In either case, we are forcibly normalizing
				// non-block-formatted transclusions into block formats
				// by adding missing newlines.
				$spc = $dpArgInfoMap[$arg['dpKey']]->spc ?? null;
				if ( $spc && ( !$format || preg_match( Utils::COMMENT_REGEXP, $spc[3] ?? '' ) ) ) {
					$nl = ( substr( $formatParamName, 0, 1 ) === "\n" ) ? "\n" : '';
					$modFormatParamName = $nl . '|' . $spc[0] . '_' . $spc[1] . '=' . $spc[2];
					$modFormatParamValue = '_' . $spc[3];
				} else {
					$modFormatParamName = $formatParamName;
					$modFormatParamValue = $formatParamValue;
				}
			}

			// Don't create duplicate newlines.
			$trailing = preg_match( self::TRAILING_COMMENT_OR_WS_AFTER_NL_REGEXP, $buf );
			if ( $trailing && substr( $formatParamName, 0, 1 ) === "\n" ) {
				$modFormatParamName = substr( $formatParamName, 1 );
			}

			$buf .= $this->formatStringSubst( $modFormatParamName, $name, $forceTrim );
			$buf .= $this->formatStringSubst( $modFormatParamValue, $val, $forceTrim );
		}

		// Don't create duplicate newlines.
		if ( preg_match( self::TRAILING_COMMENT_OR_WS_AFTER_NL_REGEXP, $buf )
			 && substr( $formatEnd, 0, 1 ) === "\n"
		) {
			$buf .= substr( $formatEnd, 1 );
		} else {
			$buf .= $formatEnd;
		}

		if ( $formatEOL ) {
			if ( $nextPart === null ) {
				// This is the last part of the block. Add the \n only
				// if the next non-comment node is not a text node
				// of if the text node doesn't have a leading \n.
				$next = DiffDOMUtils::nextNonDeletedSibling( $node );
				while ( $next instanceof Comment ) {
					$next = DiffDOMUtils::nextNonDeletedSibling( $next );
				}
				if ( !( $next instanceof Text ) || substr( $next->nodeValue, 0, 1 ) !== "\n" ) {
					$buf .= "\n";
				}
			} elseif ( !is_string( $nextPart ) || substr( $nextPart, 0, 1 ) !== "\n" ) {
				// If nextPart is another template, and it wants a leading nl,
				// this \n we add here will count towards that because of the
				// formatSOL check at the top.
				$buf .= "\n";
			}
		}

		return $buf;
	}

	/**
	 * Serialize a template from its parts.
	 * @param SerializerState $state
	 * @param Element $node
	 * @param list<stdClass|string> $srcParts Template parts from TemplateInfo::getDataMw()
	 * @return string
	 */
	public function serializeFromParts(
		SerializerState $state, Element $node, array $srcParts
	): string {
		$useTplData = WTUtils::isNewElt( $node ) || DiffUtils::hasDiffMarkers( $node );
		$buf = '';
		foreach ( $srcParts as $i => $part ) {
			if ( is_string( $part ) ) {
				$buf .= $part;
				continue;
			}

			$prevPart = $srcParts[$i - 1] ?? null;
			$nextPart = $srcParts[$i + 1] ?? null;

			$isTplArg = isset( $part->templatearg );
			$tpl = $part->templatearg ?? $part->template ?? null;

			if ( !isset( $tpl->target->wt ) ) {
				// Maybe we should just raise a ClientError
				$this->env->log( 'error', 'data-mw.parts array is malformed: ',
					DOMCompat::getOuterHTML( $node ), PHPUtils::jsonEncode( $srcParts ) );
				continue;
			}

			// Account for clients leaving off the params array, presumably when empty.
			// See T291741
			$tpl->params ??= (object)[];

			if ( $isTplArg ) {
				$buf = $this->serializePart(
					$state, $buf, $node, 'templatearg', $tpl, null, $prevPart,
					$nextPart
				);
				continue;
			}

			// transclusion: tpl or parser function
			$tplHref = $tpl->target->href ?? null;
			$isTpl = is_string( $tplHref );
			$type = $isTpl ? 'template' : 'parserfunction';

			// While the API supports fetching multiple template data objects in one call,
			// we will fetch one at a time to benefit from cached responses.
			//
			// Fetch template data for the template
			$tplData = null;
			$apiResp = null;
			if ( $isTpl && $useTplData ) {
				try {
					$title = Title::newFromText(
						PHPUtils::stripPrefix( Utils::decodeURIComponent( $tplHref ), './' ),
						$this->env->getSiteConfig()
					);
					$tplData = $this->env->getDataAccess()->fetchTemplateData( $this->env->getPageConfig(), $title );
				} catch ( Exception $err ) {
					// Log the error, and use default serialization mode.
					// Better to misformat a transclusion than to lose an edit.
					$this->env->log( 'error/html2wt/tpldata', $err );
				}
			}
			// If the template doesn't exist, or does but has no TemplateData, ignore it
			if ( !empty( $tplData['missing'] ) || !empty( $tplData['notemplatedata'] ) ) {
				$tplData = null;
			}
			$buf = $this->serializePart( $state, $buf, $node, $type, $tpl, $tplData, $prevPart, $nextPart );
		}
		return $buf;
	}

	public function serializeExtensionStartTag( Element $node, SerializerState $state ): string {
		$dataMw = DOMDataUtils::getDataMw( $node );
		$extTagName = $dataMw->name;

		// Serialize extension attributes in normalized form as:
		// key='value'
		// FIXME: with no dataParsoid, shadow info will mark it as new
		$attrs = (array)( $dataMw->attrs ?? [] );
		$extTok = new TagTk( $extTagName, array_map( static function ( $key ) use ( $attrs ) {
			return new KV( $key, $attrs[$key] );
		}, array_keys( $attrs ) ) );

		$about = DOMCompat::getAttribute( $node, 'about' );
		if ( $about !== null ) {
			$extTok->addAttribute( 'about', $about );
		}
		$typeof = DOMCompat::getAttribute( $node, 'typeof' );
		if ( $typeof !== null ) {
			$extTok->addAttribute( 'typeof', $typeof );
		}

		$attrStr = $this->serializeAttributes( $node, $extTok );
		$src = '<' . $extTagName;
		if ( $attrStr ) {
			$src .= ' ' . $attrStr;
		}
		return $src . ( !empty( $dataMw->body ) ? '>' : ' />' );
	}

	public function defaultExtensionHandler( Element $node, SerializerState $state ): string {
		$dp = DOMDataUtils::getDataParsoid( $node );
		$dataMw = DOMDataUtils::getDataMw( $node );
		$src = $this->serializeExtensionStartTag( $node, $state );
		if ( !isset( $dataMw->body ) ) {
			return $src; // We self-closed this already.
		} elseif ( is_string( $dataMw->body->extsrc ?? null ) ) {
			$src .= $dataMw->body->extsrc;
		} elseif ( isset( $dp->src ) ) {
			$this->env->log(
				'error/html2wt/ext',
				'Extension data-mw missing for: ' . DOMCompat::getOuterHTML( $node )
			);
			return $dp->src;
		} else {
			$this->env->log(
				'error/html2wt/ext',
				'Extension src unavailable for: ' . DOMCompat::getOuterHTML( $node )
			);
		}
		return $src . '</' . $dataMw->name . '>';
	}

	/**
	 * Consolidate separator handling when emitting text.
	 * @param string $res
	 * @param Node $node
	 */
	private function serializeText( string $res, Node $node ): void {
		$state = $this->state;

		// Deal with trailing separator-like text (at least 1 newline and other whitespace)
		preg_match( self::$separatorREs['sepSuffixWithNlsRE'], $res, $newSepMatch );
		$res = preg_replace( self::$separatorREs['sepSuffixWithNlsRE'], '', $res, 1 );

		if ( !$state->inIndentPre ) {
			// Strip leading newlines and other whitespace
			if ( preg_match( self::$separatorREs['sepPrefixWithNlsRE'], $res, $match ) ) {
				$state->appendSep( $match[0] );
				$res = substr( $res, strlen( $match[0] ) );
			}
		}

		if ( $state->needsEscaping ) {
			$res = Utils::escapeWtEntities( $res );
		}
		$state->emitChunk( $res, $node );

		// Move trailing newlines into the next separator
		if ( $newSepMatch ) {
			if ( !$state->sep->src ) {
				$state->appendSep( $newSepMatch[0] );
			} else {
				/* SSS FIXME: what are we doing with the stripped NLs?? */
			}
		}
	}

	/**
	 * Serialize the content of a text node
	 * @param Node $node
	 * @return Node|null
	 */
	private function serializeTextNode( Node $node ): ?Node {
		$this->state->needsEscaping = true;
		$this->serializeText( $node->nodeValue, $node );
		$this->state->needsEscaping = false;
		return $node->nextSibling;
	}

	/**
	 * Emit non-separator wikitext that does not need to be escaped.
	 * @param string $res
	 * @param Node $node
	 */
	public function emitWikitext( string $res, Node $node ): void {
		$this->serializeText( $res, $node );
	}

	/**
	 * DOM-based serialization
	 * @param Element $node
	 * @param DOMHandler $domHandler
	 * @return Node|null
	 */
	private function serializeNodeInternal( Element $node, DOMHandler $domHandler ) {
		// To serialize a node from source, the node should satisfy these
		// conditions:
		//
		// 1. It should not have a diff marker or be in a modified subtree
		//    WTS should not be in a subtree with a modification flag that
		//    applies to every node of a subtree (rather than an indication
		//    that some node in the subtree is modified).
		//
		// 2. It should continue to be valid in any surrounding edited context
		//    For some nodes, modification of surrounding context
		//    can change serialized output of this node
		//    (ex: <td>s and whether you emit | or || for them)
		//
		// 3. It should have valid, usable DSR
		//
		// 4. Either it has non-zero positive DSR width, or meets one of the
		//    following:
		//
		//    4a. It is content like <p><br/><p> or an automatically-inserted
		//        wikitext <references/> (HTML <ol>) (will have dsr-width 0)
		//    4b. it is fostered content (will have dsr-width 0)
		//    4c. it is misnested content (will have dsr-width 0)
		//
		// SSS FIXME: Additionally, we can guard against buggy DSR with
		// some validity checks. We can test that non-sep src content
		// leading wikitext markup corresponds to the node type.
		//
		// Ex: If node.nodeName is 'UL', then src[0] should be '*'
		//
		// TO BE DONE

		$state = $this->state;
		$wrapperUnmodified = false;
		$dp = DOMDataUtils::getDataParsoid( $node );

		if ( $state->selserMode
			&& !$state->inInsertedContent
			&& WTSUtils::origSrcValidInEditedContext( $state, $node )
			&& Utils::isValidDSR( $dp->dsr ?? null )
			&& ( $dp->dsr->end > $dp->dsr->start
				// FIXME: <p><br/></p>
				// nodes that have dsr width 0 because currently,
				// we emit newlines outside the p-nodes. So, this check
				// tries to handle that scenario.
				|| (
					$dp->dsr->end === $dp->dsr->start && (
						in_array( DOMCompat::nodeName( $node ), [ 'p', 'br' ], true )
						|| !empty( DOMDataUtils::getDataMw( $node )->autoGenerated )
						// FIXME: This is only necessary while outputContentVersion
						// 2.1.2 - 2.2.0 are still valid
						|| DOMUtils::hasTypeOf( $node, 'mw:Placeholder/StrippedTag' )
					)
				)
				|| !empty( $dp->fostered )
				|| !empty( $dp->misnested )
			)
		) {
			if ( !DiffUtils::hasDiffMarkers( $node ) ) {
				// If this HTML node will disappear in wikitext because of
				// zero width, then the separator constraints will carry over
				// to the node's children.
				//
				// Since we dont recurse into 'node' in selser mode, we update the
				// separator constraintInfo to apply to 'node' and its first child.
				//
				// We could clear constraintInfo altogether which would be
				// correct (but could normalize separators and introduce dirty
				// diffs unnecessarily).

				$state->currNodeUnmodified = true;

				if ( WTUtils::isZeroWidthWikitextElt( $node )
					&& $node->hasChildNodes()
					&& ( $state->sep->constraints['constraintInfo']['sepType'] ?? null ) === 'sibling'
				) {
					$state->sep->constraints['constraintInfo']['onSOL'] = $state->onSOL;
					$state->sep->constraints['constraintInfo']['sepType'] = 'parent-child';
					$state->sep->constraints['constraintInfo']['nodeA'] = $node;
					$state->sep->constraints['constraintInfo']['nodeB'] = $node->firstChild;
				}

				$out = $state->getOrigSrc( $dp->dsr->start, $dp->dsr->end ) ?? '';

				$this->trace( 'ORIG-src with DSR', static function () use ( $dp, $out ) {
					return '[' . $dp->dsr->start . ',' . $dp->dsr->end . '] = '
						. PHPUtils::jsonEncode( $out );
				} );

				// When reusing source, we should only suppress serializing
				// to a single line for the cases we've allowed in normal serialization.
				// <a> tags might look surprising here, but, here is the rationale.
				// If some link syntax (wikilink, extlink, etc.) accepted a newline
				// originally, we can safely let it through here. There is no need to have
				// specific checks for wikilnks / extlinks / ... etc. The only concern is
				// if the surrounding context in which this link-syntax is embedded also
				// breaks the link syntax. There is no such syntax right now.
				// FIXME: Note the limitation here, that if these nodes are nested
				// in something as trivial as an i / b, the suppression won't happen
				// and we'll dirty the text.
				$suppressSLC = WTUtils::isFirstEncapsulationWrapperNode( $node )
					|| DOMUtils::hasTypeOf( $node, 'mw:Nowiki' )
					|| in_array( DOMCompat::nodeName( $node ), [ 'dl', 'ul', 'ol', 'a' ], true )
					|| ( DOMCompat::nodeName( $node ) === 'table'
						&& DOMCompat::nodeName( $node->parentNode ) === 'dd'
						&& DiffDOMUtils::previousNonSepSibling( $node ) === null );

				// Use selser to serialize this text!  The original
				// wikitext is `out`.  But first allow
				// `ConstrainedText.fromSelSer` to figure out the right
				// type of ConstrainedText chunk(s) to use to represent
				// `out`, based on the node type.  Since we might actually
				// have to break this wikitext into multiple chunks,
				// `fromSelSer` returns an array.
				if ( $suppressSLC ) {
					$state->singleLineContext->disable();
				}
				foreach ( ConstrainedText::fromSelSer( $out, $node, $dp, $this->env ) as $ct ) {
					$state->emitChunk( $ct, $ct->node );
				}
				if ( $suppressSLC ) {
					$state->singleLineContext->pop();
				}

				// Skip over encapsulated content since it has already been
				// serialized.
				if ( WTUtils::isFirstEncapsulationWrapperNode( $node ) ) {
					return WTUtils::skipOverEncapsulatedContent( $node );
				} else {
					return $node->nextSibling;
				}
			}

			$wrapperUnmodified = DiffUtils::onlySubtreeChanged( $node ) &&
				WTSUtils::hasValidTagWidths( $dp->dsr ?? null );
		}

		$state->currNodeUnmodified = false;

		$currentInsertedState = $state->inInsertedContent;

		$inInsertedContent = $state->selserMode && DiffUtils::hasInsertedDiffMark( $node );

		if ( $inInsertedContent ) {
			$state->inInsertedContent = true;
		}

		$next = $domHandler->handle( $node, $state, $wrapperUnmodified );

		if ( $inInsertedContent ) {
			$state->inInsertedContent = $currentInsertedState;
		}

		return $next;
	}

	/**
	 * Internal worker. Recursively serialize a DOM subtree.
	 * @private
	 * @param Node $node
	 * @return ?Node
	 */
	public function serializeNode( Node $node ): ?Node {
		$nodeName = DOMCompat::nodeName( $node );
		$domHandler = $method = null;
		$domHandlerFactory = new DOMHandlerFactory();
		$state = $this->state;
		$state->currNode = $node;

		if ( $state->selserMode ) {
			$this->trace(
				static function () use ( $node ) {
					return WTSUtils::traceNodeName( $node );
				},
				'; prev-unmodified: ', $state->prevNodeUnmodified,
				'; SOL: ', $state->onSOL );
		} else {
			$this->trace(
				static function () use ( $node ) {
					return WTSUtils::traceNodeName( $node );
				},
				'; SOL: ', $state->onSOL );
		}

		switch ( $node->nodeType ) {
			case XML_ELEMENT_NODE:
				'@phan-var Element $node';/** @var Element $node */
				// Ignore DiffMarker metas, but clear unmodified node state
				if ( DiffUtils::isDiffMarker( $node ) ) {
					$state->updateModificationFlags( $node );
					// `state.sep.lastSourceNode` is cleared here so that removed
					// separators between otherwise unmodified nodes don't get
					// restored.
					$state->updateSep( $node );
					return $node->nextSibling;
				}
				$domHandler = $domHandlerFactory->getDOMHandler( $node );
				$method = [ $this, 'serializeNodeInternal' ];
				break;
			case XML_TEXT_NODE:
				// This code assumes that the DOM is in normalized form with no
				// run of text nodes.
				// Accumulate whitespace from the text node into state.sep.src
				$text = $node->nodeValue;
				if ( !$state->inIndentPre
					// PORT-FIXME: original uses this->state->serializer->separatorREs
					// but that does not seem useful
					&& preg_match( self::$separatorREs['pureSepRE'], $text )
				) {
					$state->appendSep( $text );
					return $node->nextSibling;
				}
				if ( $state->selserMode ) {
					$prev = $node->previousSibling;
					if ( !$state->inInsertedContent && (
						( !$prev && DOMUtils::atTheTop( $node->parentNode ) ) ||
						( $prev && !DiffUtils::isDiffMarker( $prev ) )
					) ) {
						$state->currNodeUnmodified = true;
					} else {
						$state->currNodeUnmodified = false;
					}
				}

				$domHandler = new DOMHandler( false );
				$method = [ $this, 'serializeTextNode' ];
				break;
			case XML_COMMENT_NODE:
				// Merge this into separators
				$state->appendSep( WTSUtils::commentWT( $node->nodeValue ) );
				return $node->nextSibling;
			default:
				throw new InternalException( 'Unhandled node type: ' . $node->nodeType );
		}

		$prev = DiffDOMUtils::previousNonSepSibling( $node ) ?: $node->parentNode;
		$this->env->log( 'debug/wts', 'Before constraints for ' . $nodeName );
		$state->separators->updateSeparatorConstraints(
			$prev, $domHandlerFactory->getDOMHandler( $prev ),
			$node, $domHandler
		);

		$this->env->log( 'debug/wts', 'Calling serialization handler for ' . $nodeName );
		$nextNode = call_user_func( $method, $node, $domHandler );

		$next = DiffDOMUtils::nextNonSepSibling( $node ) ?: $node->parentNode;
		$this->env->log( 'debug/wts', 'After constraints for ' . $nodeName );
		$state->separators->updateSeparatorConstraints(
			$node, $domHandler,
			$next, $domHandlerFactory->getDOMHandler( $next )
		);

		// Update modification flags
		$state->updateModificationFlags( $node );

		return $nextNode;
	}

	private function stripUnnecessaryHeadingNowikis( string $line ): string {
		$state = $this->state;
		if ( !$state->hasHeadingEscapes ) {
			return $line;
		}

		$escaper = static function ( string $wt ) use ( $state ) {
			$ret = $state->serializer->wteHandlers->escapedText( $state, false, $wt, false, true );
			return $ret;
		};

		preg_match( self::HEADING_NOWIKI_REGEXP, $line, $match );
		if ( $match && !preg_match( self::COMMENT_OR_WS_REGEXP, $match[2] ) ) {
			// The nowikiing was spurious since the trailing = is not in EOL position
			return $escaper( $match[1] ) . $match[2];
		} else {
			// All is good.
			return $line;
		}
	}

	private function stripUnnecessaryIndentPreNowikis(): void {
		// FIXME: The solTransparentWikitextRegexp includes redirects, which really
		// only belong at the SOF and should be unique. See the "New redirect" test.
		$noWikiRegexp = '@^'
			. PHPUtils::reStrip( $this->env->getSiteConfig()->solTransparentWikitextNoWsRegexp(), '@' )
			. '((?i:<nowiki>\s+</nowiki>))([^\n]*(?:\n|$))' . '@Dm';
		$pieces = preg_split( $noWikiRegexp, $this->state->out, -1, PREG_SPLIT_DELIM_CAPTURE );
		$out = $pieces[0];
		for ( $i = 1;  $i < count( $pieces );  $i += 4 ) {
			$out .= $pieces[$i];
			$nowiki = $pieces[$i + 1];
			$rest = $pieces[$i + 2];
			// Ignore comments
			preg_match_all( '/<[^!][^<>]*>/', $rest, $htmlTags );

			// Not required if just sol transparent wt.
			$reqd = !preg_match( $this->env->getSiteConfig()->solTransparentWikitextRegexp(), $rest );

			if ( $reqd ) {
				foreach ( $htmlTags[0] as $j => $rawTagName ) {
					// Strip </, attributes, and > to get the tagname
					$tagName = preg_replace( '/<\/?|\s.*|>/', '', $rawTagName );
					if ( !isset( Consts::$HTML['HTML5Tags'][$tagName] ) ) {
						// If we encounter any tag that is not a html5 tag,
						// it could be an extension tag. We could do a more complex
						// regexp or tokenize the string to determine if any block tags
						// show up outside the extension tag. But, for now, we just
						// conservatively bail and leave the nowiki as is.
						$reqd = true;
						break;
					} elseif ( TokenUtils::isWikitextBlockTag( $tagName ) ) {
						// FIXME: Extension tags shadowing html5 tags might not
						// have block semantics.
						// Block tags on a line suppress nowikis
						$reqd = false;
					}
				}
			}

			if ( !$reqd ) {
				$nowiki = preg_replace( '#^<nowiki>(\s+)</nowiki>#', '$1', $nowiki, 1 );
			} else {
				$solTransparentWikitextNoWsRegexpFragment = PHPUtils::reStrip(
					$this->env->getSiteConfig()->solTransparentWikitextNoWsRegexp(), '/' );
				$wsReplacementRE = '/^(' . $solTransparentWikitextNoWsRegexpFragment . ')\s+/';
				// Replace all leading whitespace
				do {
					$oldRest = $rest;
					$rest = preg_replace( $wsReplacementRE, '$1', $rest );
				} while ( $rest !== $oldRest );

				// Protect against sol-sensitive wikitext characters
				$solCharsTest = '/^' . $solTransparentWikitextNoWsRegexpFragment . '[=*#:;]/';
				$nowiki = preg_replace( '#^<nowiki>(\s+)</nowiki>#',
					preg_match( $solCharsTest, $rest ) ? '<nowiki/>' : '', $nowiki, 1 );
			}
			$out = $out . $nowiki . $rest . $pieces[$i + 3];
		}
		$this->state->out = $out;
	}

	/**
	 * This implements a heuristic to strip two common sources of <nowiki/>s.
	 * When <i> and <b> tags are matched up properly,
	 * - any single ' char before <i> or <b> does not need <nowiki/> protection.
	 * - any single ' char before </i> or </b> does not need <nowiki/> protection.
	 * @param string $line
	 * @return string
	 */
	private function stripUnnecessaryQuoteNowikis( string $line ): string {
		if ( !$this->state->hasQuoteNowikis ) {
			return $line;
		}

		// Optimization: We are interested in <nowiki/>s before quote chars.
		// So, skip this if we don't have both.
		if ( !( preg_match( '#<nowiki\s*/>#', $line ) && preg_match( "/'/", $line ) ) ) {
			return $line;
		}

		// * Split out all the [[ ]] {{ }} '' ''' ''''' <..> </...>
		//   parens in the regexp mean that the split segments will
		//   be spliced into the result array as the odd elements.
		// * If we match up the tags properly and we see opening
		//   <i> / <b> / <i><b> tags preceded by a '<nowiki/>, we
		//   can remove all those nowikis.
		//   Ex: '<nowiki/>''foo'' bar '<nowiki/>'''baz'''
		// * If we match up the tags properly and we see closing
		//   <i> / <b> / <i><b> tags preceded by a '<nowiki/>, we
		//   can remove all those nowikis.
		//   Ex: ''foo'<nowiki/>'' bar '''baz'<nowiki/>'''
		// phpcs:ignore Generic.Files.LineLength.TooLong
		$p = preg_split( "#('''''|'''|''|\[\[|\]\]|\{\{|\}\}|<\w+(?:\s+[^>]*?|\s*?)/?>|</\w+\s*>)#", $line, -1, PREG_SPLIT_DELIM_CAPTURE );

		// Which nowiki do we strip out?
		$nowikiIndex = -1;

		// Verify that everything else is properly paired up.
		$stack = [];
		$quotesOnStack = 0;
		$n = count( $p );
		$nonHtmlTag = null;
		for ( $j = 1;  $j < $n;  $j += 2 ) {
			// For HTML tags, pull out just the tag name for clearer code below.
			preg_match( '#^<(/?\w+)#', $p[$j], $matches );
			$tag = mb_strtolower( $matches[1] ?? $p[$j] );
			$tagLen = strlen( $tag );
			$selfClose = false;
			if ( str_ends_with( $p[$j], '/>' ) ) {
				$tag .= '/';
				$selfClose = true;
			}

			// Ignore non-html-tag (<nowiki> OR extension tag) blocks
			if ( !$nonHtmlTag ) {
				if ( isset( $this->env->getSiteConfig()->getExtensionTagNameMap()[$tag] ) ) {
					$nonHtmlTag = $tag;
					continue;
				}
			} else {
				if ( $tagLen > 0 && $tag[0] === '/' && substr( $tag, 1 ) === $nonHtmlTag ) {
					$nonHtmlTag = null;
				}
				continue;
			}

			if ( $tag === ']]' ) {
				if ( array_pop( $stack ) !== '[[' ) {
					return $line;
				}
			} elseif ( $tag === '}}' ) {
				if ( array_pop( $stack ) !== '{{' ) {
					return $line;
				}
			} elseif ( $tagLen > 0 && $tag[0] === '/' ) { // closing html tag
				// match html/ext tags
				$openTag = array_pop( $stack );
				if ( $tag !== ( '/' . $openTag ) ) {
					return $line;
				}
			} elseif ( $tag === 'nowiki/' ) {
				// We only want to process:
				// - trailing single quotes (bar')
				// - or single quotes by themselves without a preceding '' sequence
				if ( substr( $p[$j - 1], -1 ) === "'"
					&& !( $p[$j - 1] === "'" && $j > 1 && substr( $p[$j - 2], -2 ) === "''" )
					// Consider <b>foo<i>bar'</i>baz</b> or <b>foo'<i>bar'</i>baz</b>.
					// The <nowiki/> before the <i> or </i> cannot be stripped
					// if the <i> is embedded inside another quote.
					&& ( $quotesOnStack === 0
						// The only strippable scenario with a single quote elt on stack
						// is: ''bar'<nowiki/>''
						//   -> ["", "''", "bar'", "<nowiki/>", "", "''"]
						|| ( $quotesOnStack === 1
							&& $j + 2 < $n
							&& $p[$j + 1] === ''
							&& $p[$j + 2][0] === "'"
							&& $p[$j + 2] === PHPUtils::lastItem( $stack ) ) )
				) {
					$nowikiIndex = $j;
				}
				continue;
			} elseif ( $selfClose || $tag === 'br' ) {
				// Skip over self-closing tags or what should have been self-closed.
				// ( While we could do this for all void tags defined in
				//   mediawiki.wikitext.constants.js, <br> is the most common
				//   culprit. )
				continue;
			} elseif ( $tagLen > 0 && $tag[0] === "'" && PHPUtils::lastItem( $stack ) === $tag ) {
				array_pop( $stack );
				$quotesOnStack--;
			} else {
				$stack[] = $tag;
				if ( $tagLen > 0 && $tag[0] === "'" ) {
					$quotesOnStack++;
				}
			}
		}

		if ( count( $stack ) ) {
			return $line;
		}

		if ( $nowikiIndex !== -1 ) {
			// We can only remove the final trailing nowiki.
			//
			// HTML  : <i>'foo'</i>
			// line  : ''<nowiki/>'foo'<nowiki/>''
			$p[$nowikiIndex] = '';
			return implode( '', $p );
		} else {
			return $line;
		}
	}

	/**
	 * Serialize an HTML DOM.
	 *
	 * WARNING: You probably want to use WikitextContentModelHandler::fromDOM instead.
	 *
	 * @param Document|DocumentFragment $node
	 * @param bool $selserMode
	 * @return string
	 */
	public function serializeDOM(
		Node $node, bool $selserMode = false
	): string {
		Assert::parameterType(
			Document::class . '|' . DocumentFragment::class,
			$node, '$node' );

		if ( $node instanceof Document ) {
			$node = DOMCompat::getBody( $node );
		}

		$this->logType = $selserMode ? 'trace/selser' : 'trace/wts';

		$state = $this->state;
		$state->initMode( $selserMode );

		$domNormalizer = new DOMNormalizer( $state );
		$domNormalizer->normalize( $node );

		if ( $this->env->hasDumpFlag( 'dom:post-normal' ) ) {
			$options = [ 'storeDiffMark' => true ];
			$this->env->writeDump( ContentUtils::dumpDOM( $node, 'DOM: post-normal', $options ) );
		}

		$state->kickOffSerialize( $node );

		if ( $state->hasIndentPreNowikis ) {
			// FIXME: Perhaps this can be done on a per-line basis
			// rather than do one post-pass on the entire document.
			$this->stripUnnecessaryIndentPreNowikis();
		}

		$splitLines = $state->selserMode
			|| $state->hasQuoteNowikis
			|| $state->hasSelfClosingNowikis
			|| $state->hasHeadingEscapes;

		if ( $splitLines ) {
			$state->out = implode( "\n", array_map( function ( $line ) {
				// FIXME: Perhaps this can be done on a per-line basis
				// rather than do one post-pass on the entire document.
				$line = $this->stripUnnecessaryQuoteNowikis( $line );

				return $this->stripUnnecessaryHeadingNowikis( $line );
			}, explode( "\n", $state->out ) ) );
		}

		if ( $state->redirectText && $state->redirectText !== 'unbuffered' ) {
			$firstLine = explode( "\n", $state->out, 1 )[0];
			$nl = preg_match( '/^(\s|$)/D', $firstLine ) ? '' : "\n";
			$state->out = $state->redirectText . $nl . $state->out;
		}

		return $state->out;
	}

	/**
	 * @note Porting note: this replaces the pattern $serializer->env->log( $serializer->logType, ... )
	 * @param mixed ...$args
	 */
	public function trace( ...$args ) {
		$this->env->log( $this->logType, ...$args );
	}

}
