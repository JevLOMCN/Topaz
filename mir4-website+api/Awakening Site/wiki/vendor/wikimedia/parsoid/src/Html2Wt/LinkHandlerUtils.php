<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt;

use stdClass;
use UnexpectedValueException;
use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\Core\MediaStructure;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\Html2Wt\ConstrainedText\AutoURLLinkText;
use Wikimedia\Parsoid\Html2Wt\ConstrainedText\ConstrainedText;
use Wikimedia\Parsoid\Html2Wt\ConstrainedText\ExtLinkText;
use Wikimedia\Parsoid\Html2Wt\ConstrainedText\MagicLinkText;
use Wikimedia\Parsoid\Html2Wt\ConstrainedText\WikiLinkText;
use Wikimedia\Parsoid\NodeData\DataParsoid;
use Wikimedia\Parsoid\NodeData\TempData;
use Wikimedia\Parsoid\Utils\ContentUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\TokenUtils;
use Wikimedia\Parsoid\Utils\UrlUtils;
use Wikimedia\Parsoid\Utils\Utils;
use Wikimedia\Parsoid\Utils\WTUtils;
use Wikimedia\Parsoid\Wt2Html\TokenizerUtils;

/**
 * Serializes link markup.
 */
class LinkHandlerUtils {
	private static $REDIRECT_TEST_RE = '/^([ \t\n\r\0\x0b])*$/D';
	private static $MW_TITLE_WHITESPACE_RE
		= '/[ _\xA0\x{1680}\x{180E}\x{2000}-\x{200A}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}]+/u';

	/**
	 * Split a string based on a prefix and suffix
	 *
	 * @param string $contentString
	 * @param DataParsoid $dp Containing ->prefix and ->tail
	 * @return stdClass
	 */
	private static function splitLinkContentString( string $contentString, DataParsoid $dp ): stdClass {
		$tail = $dp->tail ?? '';
		$prefix = $dp->prefix ?? '';

		$tailLen = strlen( $tail );
		if ( $tailLen && substr( $contentString, -$tailLen ) === $tail ) {
			// strip the tail off the content
			$contentString = substr( $contentString, 0, -$tailLen );
		} else {
			$tail = '';
		}

		$prefixLen = strlen( $prefix );
		if ( $prefixLen && substr( $contentString, 0, $prefixLen ) === $prefix ) {
			$contentString = substr( $contentString, $prefixLen );
		} else {
			$prefix = '';
		}

		return (object)[
			'contentString' => $contentString,
			'tail' => $tail,
			'prefix' => $prefix,
		];
	}

	/**
	 * Helper function for munging protocol-less absolute URLs:
	 * If this URL is absolute, but doesn't contain a protocol,
	 * try to find a localinterwiki protocol that would work.
	 *
	 * @param Env $env
	 * @param Element $node
	 * @return string
	 */
	private static function getHref( Env $env, Element $node ): string {
		$href = DOMCompat::getAttribute( $node, 'href' ) ?? '';
		if ( ( $href[0] ?? '' ) === '/' && ( $href[1] ?? '' ) !== '/' ) {
			// protocol-less but absolute.  let's find a base href
			foreach ( $env->getSiteConfig()->interwikiMapNoNamespaces() as $prefix => $interwikiInfo ) {
				if ( isset( $interwikiInfo['localinterwiki'] ) && isset( $interwikiInfo['url'] ) ) {
					$base = $interwikiInfo['url'];

					// evaluate the url relative to this base
					$nhref = UrlUtils::expandUrl( $href, $base );

					// can this match the pattern?
					$re = '/^' . strtr( preg_quote( $base, '/' ), [ '\\$1' => '.*' ] ) . '$/sD';
					if ( preg_match( $re, $nhref ) ) {
						return $nhref;
					}
				}
			}
		}
		return $href;
	}

	/**
	 * Normalize an interwiki prefix (?)
	 * @param string $str
	 * @return string
	 */
	private static function normalizeIWP( string $str ): string {
		return PHPUtils::stripPrefix( trim( strtolower( $str ) ), ':' );
	}

	/**
	 * Escape a link target, and indicate if it's valid
	 * @param string $linkTarget
	 * @param SerializerState $state
	 * @return stdClass
	 */
	private static function escapeLinkTarget( string $linkTarget, SerializerState $state ): stdClass {
		// Entity-escape the content.
		$linkTarget = Utils::escapeWtEntities( $linkTarget );
		return (object)[
			'linkTarget' => $linkTarget,
			// Is this an invalid link?
			'invalidLink' => !$state->getEnv()->isValidLinkTarget( $linkTarget ) ||
				// `isValidLinkTarget` omits fragments (the part after #) so,
				// even though "|" is an invalid character, we still need to ensure
				// it doesn't appear in there.  The percent encoded version is fine
				// in the fragment, since it won't break the parse.
				strpos( $linkTarget, '|' ) !== false,
		];
	}

	/**
	 * Get the plain text content of the node, if it can be represented as such
	 *
	 * NOTE: This function seems a little inconsistent about what's considered
	 * null and what's an empty string.  For example, no children is null
	 * but a single diffMarker gets a string?  One of the current callers
	 * seems to subtly depend on that though.
	 *
	 * FIXME(T254501): This function can return `$node->textContent` instead
	 * of the string concatenation once mw:DisplaySpace is preprocessed away.
	 *
	 * @param Node $node
	 * @return ?string
	 */
	private static function getContentString( Node $node ): ?string {
		if ( !$node->hasChildNodes() ) {
			return null;
		}
		$contentString = '';
		$child = $node->firstChild;
		while ( $child ) {
			if ( $child instanceof Text ) {
				$contentString .= $child->nodeValue;
			} elseif ( DOMUtils::hasTypeOf( $child, 'mw:DisplaySpace' ) ) {
				$contentString .= ' ';
			} elseif ( DiffUtils::isDiffMarker( $child ) ) {
			} else {
				return null;
			}
			$child = $child->nextSibling;
		}
		return $contentString;
	}

	/**
	 * Helper function for getting RT data from the tokens
	 * @param Env $env
	 * @param Element $node
	 * @param SerializerState $state
	 * @return stdClass
	 */
	private static function getLinkRoundTripData(
		Env $env, Element $node, SerializerState $state
	): stdClass {
		$dp = DOMDataUtils::getDataParsoid( $node );
		$siteConfig = $env->getSiteConfig();
		$rtData = (object)[
			'type' => null, // could be null
			'href' => null, // filled in below
			'origHref' => null, // filled in below
			'target' => null, // filled in below
			'tail' => $dp->tail ?? '',
			'prefix' => $dp->prefix ?? '',
			'linkType' => null
		];
		$rtData->content = new stdClass;

		// Figure out the type of the link
		if ( $node->hasAttribute( 'rel' ) ) {
			$rel = DOMCompat::getAttribute( $node, 'rel' ) ?? '';
			// Parsoid only emits and recognizes ExtLink, WikiLink, and PageProp rel values.
			// Everything else defaults to ExtLink during serialization (unless it is
			// serializable to a wikilink)
			// We're keeping the preg_match here instead of going through DOMUtils::matchRel
			// because we have \b guards to handle the multivalue, and we're keeping the matches,
			// which matchRel doesn't do.
			if ( preg_match( '/\b(mw:(WikiLink|ExtLink|MediaLink|PageProp)\S*)\b/', $rel, $typeMatch ) ) {
				$rtData->type = $typeMatch[1];
				// Strip link subtype info
				if ( $typeMatch[2] === 'WikiLink' || $typeMatch[2] === 'ExtLink' ) {
					$rtData->type = 'mw:' . $typeMatch[2];
				}
			}
		}

		// Default link type if nothing else is set
		if ( $rtData->type === null && !DOMUtils::selectMediaElt( $node ) ) {
			$rtData->type = 'mw:ExtLink';
		}

		// Get href, and save the token's "real" href for comparison
		$href = self::getHref( $env, $node );
		$rtData->origHref = $href;
		$rtData->href = preg_replace( '#^(\.\.?/)+#', '', $href, 1 );

		// WikiLinks should be relative (but see below); fixup the link type
		// if a WikiLink has an absolute URL.
		// (This may get converted back to a WikiLink below, in the interwiki
		// handling code.)
		if ( $rtData->type === 'mw:WikiLink' &&
			( preg_match( '#^(\w+:)?//#', $rtData->href ) ||
				substr( $rtData->origHref ?? '', 0, 1 ) === '/' )
		) {
			$rtData->type = 'mw:ExtLink';
		}

		// Now get the target from rt data
		$rtData->target = $state->serializer->serializedAttrVal( $node, 'href' );

		// Check if the link content has been modified or is newly inserted content.
		// FIXME: This will only work with selser of course. Hard to test without selser.
		if (
			$state->inInsertedContent ||
			DiffUtils::hasDiffMark( $node, DiffMarkers::SUBTREE_CHANGED )
		) {
			$rtData->contentModified = true;
		}

		// Get the content string or tokens
		$contentString = self::getContentString( $node );
		if ( $contentString !== null ) {
			if ( !empty( $rtData->target['value'] ) && $rtData->target['value'] !== $contentString ) {
				// Try to identify a new potential tail
				$contentParts = self::splitLinkContentString( $contentString, $dp );
				$rtData->content->string = $contentParts->contentString;
				$rtData->tail = $contentParts->tail;
				$rtData->prefix = $contentParts->prefix;
			} else {
				$rtData->tail = '';
				$rtData->prefix = '';
				$rtData->content->string = $contentString;
			}
		} elseif ( $node->hasChildNodes() ) {
			$rtData->contentNode = $node;
		} elseif ( $rtData->type === 'mw:PageProp/redirect' ) {
			$rtData->isRedirect = true;
			$rtData->prefix = $dp->src
				?? ( ( $siteConfig->mwAliases()['redirect'][0] ?? '#REDIRECT' ) . ' ' );
		}

		// Update link type based on additional analysis.
		// What might look like external links might be serializable as a wikilink.
		$target = &$rtData->target;

		// mw:MediaLink annotations are considered authoritative
		// and interwiki link matches aren't made for these
		if ( $rtData->type === 'mw:MediaLink' ) {
			// Parse title from resource attribute (see analog in image handling)
			$resource = $state->serializer->serializedAttrVal( $node, 'resource' );
			if ( $resource['value'] === null ) {
				// from non-parsoid HTML: try to reconstruct resource from href?
				// (See similar code which tries to guess resource from <img src>)
				$mediaPrefix = $siteConfig->namespaceName( $siteConfig->namespaceId( 'media' ) );
				$slashPos = strrpos( $rtData->origHref, '/' );
				$fileName = $slashPos === false ? $rtData->origHref :
					substr( $rtData->origHref, $slashPos + 1 );
				$resource = [
					'value' => $mediaPrefix . ':' . $fileName,
					'fromsrc' => false,
					'modified' => false
				];
			}
			$rtData->target = $resource;
			$rtData->href = preg_replace( '#^(\.\.?/)+#', '', $rtData->target['value'], 1 );
			return $rtData;
		}

		// Check if the href matches any of our interwiki URL patterns
		$interwikiMatch = $siteConfig->interwikiMatcher( $href );
		if ( !$interwikiMatch ) {
			return $rtData;
		}

		$iw = $siteConfig->interwikiMapNoNamespaces()[ltrim( $interwikiMatch[0], ':' )];
		$localInterwiki = !empty( $iw['local'] );

		// Only to be used in question mark check, since other checks want to include the fragment
		$targetForQmarkCheck = $interwikiMatch[1];
		// FIXME: If ever the default value for $wgExternalInterwikiFragmentMode
		// changes, we can reduce this by always stripping off the fragment
		// identifier, since in "html5" mode, that isn't encoded.  At present,
		// we can only do that if we know it's a local interwiki link.
		if ( $localInterwiki ) {
			$withoutFragment = strstr( $targetForQmarkCheck, '#', true );
			if ( $withoutFragment !== false ) {
				$targetForQmarkCheck = $withoutFragment;
			}
		}

		if (
			// Question mark is a valid title char, so it won't fail the test below,
			// but gets percent encoded on the way out since it has special
			// semantics in a url.  That will break the url we're serializing, so
			// protect it.
			strpos( $targetForQmarkCheck, '?' ) === false &&
			// Ensure we have a valid link target, otherwise falling back to extlink
			// is preferable, since it won't serialize as a link.
			(
				$interwikiMatch[1] === '' || !self::escapeLinkTarget(
					// Append the prefix since we want to validate the target
					// with respect to it being an interwiki.
					$interwikiMatch[0] . ':' . $interwikiMatch[1],
					$state
				)->invalidLink
			) &&
			// ExtLinks should have content to convert.
			(
				$rtData->type !== 'mw:ExtLink' ||
				!empty( $rtData->content->string ) ||
				!empty( $rtData->contentNode )
			) &&
			( !empty( $dp->isIW ) || !empty( $target['modified'] ) || !empty( $rtData->contentModified ) )
		) {
			// External link that is really an interwiki link. Convert it.
			// TODO: Leaving this for backwards compatibility, remove when 1.5 is no longer bound
			if ( $rtData->type === 'mw:ExtLink' ) {
				$rtData->type = 'mw:WikiLink';
			}
			$rtData->isInterwiki = true;
			$iwMap = $siteConfig->interwikiMapNoNamespaces();
			// could this be confused with a language link?
			$iwi = $iwMap[self::normalizeIWP( $interwikiMatch[0] )] ?? null;
			$rtData->isInterwikiLang = $iwi && isset( $iwi['language'] );
			// is this our own wiki?
			$rtData->isLocal = $iwi && isset( $iwi['localinterwiki'] );
			// strip off localinterwiki prefixes
			$localPrefix = '';
			$oldPrefix = null;
			while ( true ) {
				$tmp = substr( $target['value'], strlen( $localPrefix ) );
				if ( !preg_match( '/^(:?([^:]+)):/', $tmp, $oldPrefix ) ) {
					break;
				}
				$iwi = $iwMap[Utils::normalizeNamespaceName( $oldPrefix[2] )] ?? null;
				if ( !$iwi || !isset( $iwi['localinterwiki'] ) ) {
					break;
				}
				$localPrefix .= $oldPrefix[1] . ':';
			}

			if ( !empty( $target['fromsrc'] ) && empty( $target['modified'] ) ) {
				// Leave the target alone!
			} else {
				if ( $rtData->type === 'mw:PageProp/Language' ) {
					$targetValue = implode( ':', $interwikiMatch );
					// Strip initial colon
					if ( $targetValue[0] === ':' ) {
						$targetValue = substr( $targetValue, 1 );
					}
					$target['value'] = $targetValue;
				} elseif (
					$oldPrefix && ( // Should we preserve the old prefix?
						strcasecmp( $oldPrefix[1], $interwikiMatch[0] ) === 0 ||
						// Check if the old prefix mapped to the same URL as
						// the new one. Use the old one if that's the case.
						// Example: [[w:Foo]] vs. [[:en:Foo]]
						( $iwMap[self::normalizeIWP( $oldPrefix[1] )]['url'] ?? null )
							=== ( $iwMap[self::normalizeIWP( $interwikiMatch[0] )]['url'] ?? null )
					)
				) {
					// Reuse old prefix capitalization
					if ( Utils::decodeWtEntities( substr( $target['value'], strlen( $oldPrefix[1] ) + 1 ) )
						!== $interwikiMatch[1]
					) {
						// Modified, update target.value.
						$target['value'] = $localPrefix . $oldPrefix[1] . ':' . $interwikiMatch[1];
					}
					// Ensure that we generate an interwiki link and not a language link!
					if ( $rtData->isInterwikiLang && $target['value'][0] !== ':' ) {
						$target['value'] = ':' . $target['value'];
					}
				} else { // Else: preserve old encoding
					if ( !empty( $rtData->isLocal ) ) {
						// - interwikiMatch[0] will be something like ":en" or "w"
						// - This tests whether the interwiki-like link is actually
						// a local wikilink.

						$target['value'] = $interwikiMatch[1];
						// interwikiMatch[1] may start with a language link prefix,
						// ensure that we generate interwiki link syntax in that case. (T292022)
						if (
							preg_match( '/^([^:]+):/', $target['value'], $match ) &&
							!empty( $iwMap[self::normalizeIWP( $match[1] )]['language'] )
						) {
							$target['value'] = ':' . $target['value'];
						}

						$rtData->isInterwiki = $rtData->isInterwikiLang = false;
					} else {
						$target['value'] = implode( ':', $interwikiMatch );
					}
				}
			}
		}

		return $rtData;
	}

	/**
	 * The provided URL is already percent-encoded -- but it may still
	 * not be safe for wikitext.  Add additional escapes to make the URL
	 * wikitext-safe. Don't touch percent escapes already in the url,
	 * though!
	 * @param string $urlStr
	 * @return string
	 */
	private static function escapeExtLinkURL( string $urlStr ): string {
		// this regexp is the negation of EXT_LINK_URL_CLASS in the PHP parser
		return preg_replace(
			// IPv6 host names are bracketed with [].  Entity-decode these.
			'!^([a-z][^:/]*:)?//&#x5B;([0-9a-f:.]+)&#x5D;(:\d|/|$)!iD',
			'$1//[$2]$3',
			preg_replace_callback(
				// phpcs:ignore Generic.Files.LineLength.TooLong
				'/[\]\[<>"\x00-\x20\x7F\x{A0}\x{1680}\x{180E}\x{2000}-\x{200A}\x{202F}\x{205F}\x{3000}]|-(?=\{)/u',
				static function ( $m ) {
					return Utils::entityEncodeAll( $m[0] );
				},
				$urlStr
			),
			1
		);
	}

	/**
	 * Add a colon escape to a wikilink target string if needed.
	 * @param Env $env
	 * @param string $linkTarget
	 * @param stdClass $linkData
	 * @return string
	 */
	private static function addColonEscape(
		Env $env, string $linkTarget, stdClass $linkData
	): string {
		$linkTitle = $env->makeTitleFromText( $linkTarget );
		$categoryNs = $env->getSiteConfig()->canonicalNamespaceId( 'category' );
		$fileNs = $env->getSiteConfig()->canonicalNamespaceId( 'file' );

		if ( ( $linkTitle->getNamespace() === $categoryNs || $linkTitle->getNamespace() === $fileNs ) &&
			$linkData->type === 'mw:WikiLink' &&
			$linkTarget[0] !== ':' ) {
			// Escape category and file links
			return ':' . $linkTarget;
		} else {
			return $linkTarget;
		}
	}

	/**
	 * Test if something is a URL link
	 * @param Env $env
	 * @param Element $node
	 * @param stdClass $linkData
	 * @return bool
	 */
	private static function isURLLink( Env $env, Element $node, stdClass $linkData ): bool {
		$target = $linkData->target;

		// Get plain text content, if any
		$contentStr = self::getContentString( $node );

		// First check if we can serialize as an URL link
		return ( $contentStr !== null && $contentStr !== '' ) &&
			// Can we minimize this?
			( $target['value'] === $contentStr || self::getHref( $env, $node ) === $contentStr ) &&
			// protocol-relative url links not allowed in text
			// (see autourl rule in peg tokenizer, T32269)
			!str_starts_with( $contentStr, '//' ) && Utils::isProtocolValid( $contentStr, $env ) &&
			!self::hasAutoUrlTerminatingChars( $contentStr );
	}

	/**
	 * The legacy parser Parser.php::makeFreeExternalLink terminates an autourl when encountering
	 * some characters; since we wish to mimic that behaviour we need this method to check whether
	 * the provided URL is in that case.
	 * @param string $url
	 * @return bool
	 */
	private static function hasAutoUrlTerminatingChars( string $url ): bool {
		$sep = TokenizerUtils::getAutoUrlTerminatingChars( strpos( $url, '(' ) !== false );
		return str_contains( $sep, substr( $url, -1 ) );
	}

	/**
	 * Figure out if we need a piped or simple link
	 * @param Env $env
	 * @param DataParsoid $dp
	 * @param array $target
	 * @param stdClass $linkData
	 * @return bool
	 */
	private static function isSimpleWikiLink(
		Env $env, DataParsoid $dp, array $target, stdClass $linkData
	): bool {
		$canUseSimple = false;
		$contentString = $linkData->content->string ?? null;

		// FIXME (SSS):
		// 1. Revisit this logic to see if all these checks
		// are still relevant or whether this can be simplified somehow.
		// 2. There are also duplicate computations for env.normalizedTitleKey(..)
		// and Util.decodeURIComponent(..) that could be removed.
		// 3. This could potentially be refactored as if-then chains.

		// Would need to pipe for any non-string content.
		// Preserve unmodified or non-minimal piped links.
		if ( $contentString !== null &&
			( !empty( $target['modified'] ) || !empty( $linkData->contentModified ) ||
				( $dp->stx ?? null ) !== 'piped'
			) &&
			// Relative links are not simple
			!str_starts_with( $contentString, './' )
		) {
			// Strip colon escapes from the original target as that is
			// stripped when deriving the content string.
			// Strip ./ prefixes as well since they are relative link prefixes
			// added to all titles.
			// The prefix stripping, when it occurs, also includes spaces before the prefix.
			// Finally, we also remove trailing spaces because these are removed for <a> links
			// by DOMNormalizer::moveTrailingSpacesOut, and we wouldn't want that to lead to the
			// link getting piped for only that reason.
			$strippedTargetValue = rtrim(
				preg_replace( '#^\s*(:|\./)#', '', $target['value'], 1 )
			);

			// Strip colon escape after prefix for interwikis
			if ( !empty( $linkData->isInterwiki ) ) {
				$strippedTargetValue = preg_replace( '#^(\w+:):#', '$1', $strippedTargetValue, 1 );
			}

			$decodedTarget = Utils::decodeWtEntities( $strippedTargetValue );
			// Deal with the protocol-relative link scenario as well
			$hrefHasProto = preg_match( '#^(\w+:)?//#', $linkData->href );

			// Normalize content string and decoded target before comparison.
			// Piped links don't come down this path => it is safe to normalize both.
			$contentString = str_replace( '_', ' ', $contentString );
			$decodedTarget = str_replace( '_', ' ', $decodedTarget );

			// See if the (normalized) content matches the
			// target, either shadowed or actual.
			$canUseSimple =
				$contentString === $decodedTarget ||
				// try wrapped in forward slashes in case they were stripped
				( '/' . $contentString . '/' ) === $decodedTarget ||
				// normalize as titles and compare
				// FIXME: This will strip an interwiki prefix.  Is that right?
				$env->normalizedTitleKey( $contentString, true )
					=== preg_replace( self::$MW_TITLE_WHITESPACE_RE, '_', $decodedTarget ) ||
				// Relative link
				(
					(
						$env->getSiteConfig()->namespaceHasSubpages(
							$env->getContextTitle()->getNamespace()
						) &&
						preg_match( '#^\.\./.*[^/]$#D', $strippedTargetValue ) &&
						$contentString === $env->resolveTitle( $strippedTargetValue )
					) ||
					(
						preg_match( '#^\.\./.*?/$#D', $strippedTargetValue ) &&
						$contentString === preg_replace( '#^(?:\.\./)+(.*?)/$#D', '$1', $strippedTargetValue, 1 )
					)
				) ||
				// if content == href this could be a simple link... eg [[Foo]].
				// but if href is an absolute url with protocol, this won't
				// work: [[http://example.com]] is not a valid simple link!
				(
					!$hrefHasProto &&
					// Always compare against decoded uri because
					// <a rel="mw:WikiLink" href="7%25 Solution">7%25 Solution</a></p>
					// should serialize as [[7% Solution|7%25 Solution]]
					(
						$contentString === Utils::decodeURIComponent( $linkData->href ) ||
						// normalize with underscores for comparison with href
						$env->normalizedTitleKey( $contentString, true )
							=== Utils::decodeURIComponent( $linkData->href )
					)
				);
		}

		return $canUseSimple;
	}

	/**
	 * Serialize as wiki link
	 * @param Element $node
	 * @param SerializerState $state
	 * @param stdClass $linkData
	 */
	private static function serializeAsWikiLink(
		Element $node, SerializerState $state, stdClass $linkData
	): void {
		$contentParts = null;
		$contentSrc = '';
		$isPiped = false;
		$needsEscaping = true;
		$env = $state->getEnv();
		$siteConfig = $env->getSiteConfig();
		$target = $linkData->target;
		$dp = DOMDataUtils::getDataParsoid( $node );

		// Decode any link that did not come from the source (data-mw/parsoid)
		// Links that come from data-mw/data-parsoid will be true titles,
		// but links that come from hrefs will need to be url-decoded.
		// Ex: <a href="/wiki/A%3Fb">Foobar</a>
		if ( empty( $target['fromsrc'] ) ) {
			// Omit fragments from decoding
			$hash = strpos( $target['value'], '#' );
			if ( $hash !== false ) {
				$target['value'] = Utils::decodeURIComponent( substr( $target['value'], 0, $hash ) )
					. substr( $target['value'], $hash );
			} else {
				$target['value'] = Utils::decodeURIComponent( $target['value'] );
			}
		}

		// Special-case handling for category links
		if ( $linkData->type === 'mw:PageProp/Category' ) {
			// Split target and sort key in $target['value'].
			// The sort key shows up as "#something" in there.
			// However, watch out for parser functions that start with "{{#"
			// The atomic group is essential to prevent "{{#" parser function prefix
			// from getting split at the "{{" and "#" where the "{{" matches the
			// [^#]* and the "#" matches after separately.
			if ( preg_match( '/^((?>{{#|[^#])*)#(.*)/', $target['value'], $targetParts ) ) {
				$target['value'] = strtr( preg_replace( '#^(\.\.?/)*#', '', $targetParts[1], 1 ), '_', ' ' );
				// FIXME: Reverse `Sanitizer.sanitizeTitleURI(strContent).replace(/#/g, '%23');`
				$strContent = Utils::decodeURIComponent( $targetParts[2] );
				$contentParts = self::splitLinkContentString( $strContent, $dp );
				$linkData->content->string = $contentParts->contentString;
				$dp->tail = $linkData->tail = $contentParts->tail;
				$dp->prefix = $linkData->prefix = $contentParts->prefix;
			} else { // No sort key, will serialize to simple link
				// Normalize the content string
				$linkData->content->string = strtr(
					PHPUtils::stripPrefix( $target['value'], './' ), '_', ' '
				);
			}

			// Special-case handling for template-affected sort keys
			// FIXME: sort keys cannot be modified yet, but if they are,
			// we need to fully shadow the sort key.
			// if ( !target.modified ) {
			// The target and source key was not modified
			$sortKeySrc = $state->serializer->serializedAttrVal( $node, 'mw:sortKey' );
			if ( isset( $sortKeySrc['value'] ) ) {
				$linkData->contentNode = null;
				$linkData->content->string = $sortKeySrc['value'];
				// TODO: generalize this flag. It is already used by
				// getAttributeShadowInfo. Maybe use the same
				// structure as its return value?
				$linkData->content->fromsrc = true;
			}
			// }
		} else {
			if ( $linkData->type === 'mw:PageProp/Language' ) {
				// Fix up the content string
				// TODO: see if linkData can be cleaner!
				$linkData->content->string ??= Utils::decodeWtEntities( $target['value'] );
			}
		}

		// The string value of the content, if it is plain text.
		$linkTarget = null;
		$escapedTgt = null;
		if ( !empty( $linkData->isRedirect ) ) {
			$linkTarget = $target['value'];
			if ( !empty( $target['modified'] ) || empty( $target['fromsrc'] ) ) {
				$linkTarget = strtr( preg_replace( '#^(\.\.?/)*#', '', $linkTarget, 1 ), '_', ' ' );
				$escapedTgt = self::escapeLinkTarget( $linkTarget, $state );
				$linkTarget = $escapedTgt->linkTarget;
				// Determine if it's a redirect to a category, in which case
				// it needs a ':' on front to distingish from a category link.
				if ( preg_match( '/^([^:]+)[:]/', $linkTarget, $categoryMatch ) ) {
					$ns = $siteConfig->namespaceId( Utils::normalizeNamespaceName( $categoryMatch[1] ) );
					if ( $ns === $siteConfig->canonicalNamespaceId( 'category' ) ) {
						// Check that the next node isn't a category link,
						// in which case we don't want the ':'.
						$nextNode = $node->nextSibling;
						if ( !(
							$nextNode instanceof Element && DOMCompat::nodeName( $nextNode ) === 'link' &&
							DOMUtils::hasRel( $nextNode, 'mw:PageProp/Category' ) &&
							DOMCompat::getAttribute( $nextNode, 'href' ) === DOMCompat::getAttribute( $node, 'href' )
						) ) {
							$linkTarget = ':' . $linkTarget;
						}
					}
				}
			}
		} elseif ( self::isSimpleWikiLink( $env, $dp, $target, $linkData ) ) {
			// Simple case
			if ( empty( $target['modified'] ) && empty( $linkData->contentModified ) ) {
				$linkTarget = PHPUtils::stripPrefix( $target['value'], './' );
			} else {
				// If token has templated attrs or is a subpage, use target.value
				// since content string will be drastically different.
				if ( WTUtils::hasExpandedAttrsType( $node ) ||
					preg_match( '#(^|/)\.\./#', $target['value'] )
				) {
					$linkTarget = PHPUtils::stripPrefix( $target['value'], './' );
				} else {
					$escapedTgt = self::escapeLinkTarget( $linkData->content->string, $state );
					if ( !$escapedTgt->invalidLink ) {
						$linkTarget = self::addColonEscape( $env, $escapedTgt->linkTarget, $linkData );
					} else {
						$linkTarget = $escapedTgt->linkTarget;
					}
				}
				if ( !empty( $linkData->isInterwikiLang ) &&
					$linkTarget[0] !== ':' &&
					$linkData->type !== 'mw:PageProp/Language'
				) {
					// ensure interwiki links can't be confused with
					// interlanguage links.
					$linkTarget = ':' . $linkTarget;
				}
			}
		} elseif ( self::isURLLink( $state->getEnv(), $node, $linkData )
			/* && empty( $linkData->isInterwiki ) */
		) {
			// Uncomment the above check if we want [[wikipedia:Foo|http://en.wikipedia.org/wiki/Foo]]
			// for '<a href="http://en.wikipedia.org/wiki/Foo">http://en.wikipedia.org/wiki/Foo</a>'
			$linkData->linkType = 'mw:URLLink';
		} else {
			// Emit piped wikilink syntax
			$isPiped = true;

			// First get the content source
			if ( !empty( $linkData->contentNode ) ) {
				$cs = $state->serializeLinkChildrenToString(
					$linkData->contentNode,
					[ $state->serializer->wteHandlers, 'wikilinkHandler' ]
				);
				// strip off the tail and handle the pipe trick
				$contentParts = self::splitLinkContentString( $cs, $dp );
				$contentSrc = $contentParts->contentString;
				$dp->tail = $contentParts->tail;
				$linkData->tail = $contentParts->tail;
				$dp->prefix = $contentParts->prefix;
				$linkData->prefix = $contentParts->prefix;
				$needsEscaping = false;
			} else {
				$contentSrc = $linkData->content->string ?? '';
				$needsEscaping = empty( $linkData->content->fromsrc );
			}

			if ( $contentSrc === '' && $linkData->type !== 'mw:PageProp/Category' ) {
				// Protect empty link content from PST pipe trick
				$contentSrc = '<nowiki/>';
				$needsEscaping = false;
			}

			$linkTarget = $target['value'];
			if ( !empty( $target['modified'] ) || empty( $target['fromsrc'] ) ) {
				// Links starting with ./ shouldn't get _ replaced with ' '
				$linkContentIsRelative = str_starts_with( $linkData->content->string ?? '', './' );
				$linkTarget = preg_replace( '#^(\.\.?/)*#', '', $linkTarget, 1 );
				if ( empty( $linkData->isInterwiki ) && !$linkContentIsRelative ) {
					$linkTarget = strtr( $linkTarget, '_', ' ' );
				}
				$escapedTgt = self::escapeLinkTarget( $linkTarget, $state );
				$linkTarget = $escapedTgt->linkTarget;
			}

			// If we are reusing the target from source, we don't
			// need to worry about colon-escaping because it will
			// be in the right form already.
			//
			// Trying to eliminate this check and always check for
			// colon-escaping seems a bit tricky when the reused
			// target has encoded entities that won't resolve to
			// valid titles.
			if ( ( !$escapedTgt || !$escapedTgt->invalidLink ) && empty( $target['fromsrc'] ) ) {
				$linkTarget = self::addColonEscape( $env, $linkTarget, $linkData );
			}
		}
		if ( $linkData->linkType === 'mw:URLLink' ) {
			$state->emitChunk( new AutoURLLinkText( $node->textContent, $node ), $node );
			return;
		}

		if ( !empty( $linkData->isRedirect ) ) {
			// Drop duplicates
			if ( $state->redirectText !== null ) {
				return;
			}

			// Buffer redirect text if it is not in start of file position
			if ( !preg_match( self::$REDIRECT_TEST_RE, $state->out . $state->currLine->text ) ) {
				$state->redirectText = $linkData->prefix . '[[' . $linkTarget . ']]';
				$state->emitChunk( '', $node ); // Flush separators for this node
				// Flush separators for this node
				return;
			}

			// Set to some non-null string
			$state->redirectText = 'unbuffered';
		}

		$pipedText = null;
		if ( $escapedTgt && $escapedTgt->invalidLink ) {
			// If the link target was invalid, instead of emitting an invalid link,
			// omit the link and serialize just the content instead. But, log the
			// invalid html for Parsoid clients to investigate later.
			$state->getEnv()->log(
				'error/html2wt/link', 'Bad title text', DOMCompat::getOuterHTML( $node )
			);

			// For non-piped content, use the original invalid link text
			$pipedText = $isPiped ? $contentSrc : $linkTarget;
			$state->needsEscaping = $needsEscaping;
			$state->emitChunk( $linkData->prefix . $pipedText . $linkData->tail, $node );
		} else {
			if ( $isPiped && $needsEscaping ) {
				// We are definitely not in sol context since content
				// will be preceded by "[[" or "[" text in target wikitext.
				$pipedText = '|' . $state->serializer->wteHandlers
					->escapeLinkContent( $state, $contentSrc, false, $node, false );
			} elseif ( $isPiped ) {
				$pipedText = '|' . $contentSrc;
			} else {
				$pipedText = '';
			}
			if ( $isPiped ) {
				$state->singleLineContext->disable();
			}
			$state->emitChunk( new WikiLinkText(
				$linkData->prefix . '[[' . $linkTarget . $pipedText . ']]' . $linkData->tail,
				$node, $siteConfig, $linkData->type
			), $node );
			if ( $isPiped ) {
				$state->singleLineContext->pop();
			}
		}
	}

	/**
	 * Serialize as external link
	 * @param Element $node
	 * @param SerializerState $state
	 * @param stdClass $linkData
	 */
	private static function serializeAsExtLink(
		Element $node, SerializerState $state, stdClass $linkData
	): void {
		$target = $linkData->target;
		$urlStr = $target['value'];
		if ( !empty( $target['modified'] ) || empty( $target['fromsrc'] ) ) {
			// We expect modified hrefs to be percent-encoded already, so
			// don't need to encode them here any more. Unmodified hrefs are
			// just using the original encoding anyway.
			// BUT we do have to encode certain special wikitext
			// characters (like []) which aren't necessarily
			// percent-encoded because they are valid in URLs and HTML5
			$urlStr = self::escapeExtLinkURL( $urlStr );
		}

		if ( self::isURLLink( $state->getEnv(), $node, $linkData ) ) {
			// Serialize as URL link
			$state->emitChunk( new AutoURLLinkText( $urlStr, $node ), $node );
			return;
		}

		$siteConfig = $state->getEnv()->getSiteConfig();

		// TODO: match vs. interwikis too
		$magicLinkMatch = $siteConfig->getExtResourceURLPatternMatcher()(
			Utils::decodeURI( $linkData->origHref )
		);
		$pureHashMatch = substr( $urlStr, 0, 1 ) === '#';
		// Fully serialize the content
		$contentStr = $state->serializeLinkChildrenToString(
			$node,
			[ $state->serializer->wteHandlers, $pureHashMatch ? 'wikilinkHandler' : 'aHandler' ]
		);
		// First check for ISBN/RFC/PMID links. We rely on selser to
		// preserve non-minimal forms.
		if ( $magicLinkMatch ) {
			$serialized = $siteConfig->makeExtResourceURL(
				$magicLinkMatch, $target['value'], $contentStr
			);
			if ( $serialized[0] === '[' ) {
				// Serialization as a magic link failed (perhaps the
				// content string wasn't appropriate).
				$state->emitChunk(
					( $magicLinkMatch[0] === 'ISBN' ) ?
					new WikiLinkText( $serialized, $node, $siteConfig, 'mw:WikiLink' ) :
					new ExtLinkText( $serialized, $node, $siteConfig, 'mw:ExtLink' ),
					$node
				);
			} else {
				$state->emitChunk( new MagicLinkText( $serialized, $node ), $node );
			}
			return;
		} else {
			// serialize as auto-numbered external link
			// [http://example.com]
			$linktext = null;
			$class = null;
			// If it's just anchor text, serialize as an internal link.
			if ( $pureHashMatch ) {
				$class = WikiLinkText::class;
				$linktext = '[[' . $urlStr . ( ( $contentStr ) ? '|' . $contentStr : '' ) . ']]';
			} else {
				$class = ExtLinkText::class;
				$linktext = '[' . $urlStr . ( ( $contentStr ) ? ' ' . $contentStr : '' ) . ']';
			}
			$state->emitChunk( new $class( $linktext, $node, $siteConfig, $linkData->type ), $node );
			return;
		}
	}

	/**
	 * Main link handler.
	 * @param SerializerState $state
	 * @param Element $node
	 */
	public static function linkHandler( SerializerState $state, Element $node ): void {
		// TODO: handle internal/external links etc using RDFa and dataParsoid
		// Also convert unannotated html links without advanced attributes to
		// external wiki links for html import. Might want to consider converting
		// relative links without path component and file extension to wiki links.
		$env = $state->getEnv();
		$siteConfig = $env->getSiteConfig();

		// Get the rt data from the token and tplAttrs
		$linkData = self::getLinkRoundTripData( $env, $node, $state );
		$linkType = $linkData->type;
		if ( $siteConfig->getExtResourceURLPatternMatcher()( Utils::decodeURI( $linkData->origHref ) ) ) {
			// Override the 'rel' type if this is a magic link
			$linkType = 'mw:ExtLink';
		}
		if ( $linkType !== null && isset( $linkData->target['value'] ) ) {
			// We have a type and target info
			if ( $linkType === 'mw:WikiLink' || $linkType === 'mw:MediaLink' ||
				preg_match( TokenUtils::SOL_TRANSPARENT_LINK_REGEX, $linkType )
			) {
				// [[..]] links: normal, category, redirect, or lang links
				// (except images)
				self::serializeAsWikiLink( $node, $state, $linkData );
				return;
			} elseif ( $linkType === 'mw:ExtLink' ) {
				// [..] links, autolinks, ISBN, RFC, PMID
				self::serializeAsExtLink( $node, $state, $linkData );
				return;
			} else {
				throw new UnexpectedValueException(
					'Unhandled link serialization scenario: ' . DOMCompat::getOuterHTML( $node )
				);
			}
		} else {
			$safeAttr = [
				'href' => true,
				'rel' => true,
				'class' => true,
				'title' => true,
				DOMDataUtils::DATA_OBJECT_ATTR_NAME => true
			];

			$isComplexLink = false;
			foreach ( DOMUtils::attributes( $node ) as $name => $value ) {
				// XXX: Don't drop rel and class in every case once a tags are
				// actually supported in the MW default config?
				if ( !isset( $safeAttr[$name] ) ) {
					$isComplexLink = true;
					break;
				}
			}

			if ( $isComplexLink ) {
				$env->log( 'error/html2wt/link', 'Encountered', DOMCompat::getOuterHTML( $node ),
					'-- serializing as extlink and dropping <a> attributes unsupported in wikitext.'
				);
			} else {
				$media = DOMUtils::selectMediaElt( $node );  // TODO: Handle missing media too
				$isFigure = $media instanceof Element && $media->parentNode === $node;
				if ( $isFigure ) {
					// this is a basic html figure: <a><img></a>
					self::figureHandler( $state, $node, new MediaStructure( $media, $node ) );
					return;
				}
			}

			// href is already percent-encoded, etc., but it might contain
			// spaces or other wikitext nasties.  escape the nasties.
			$hrefStr = self::escapeExtLinkURL( self::getHref( $env, $node ) );
			$handler = [ $state->serializer->wteHandlers, 'aHandler' ];
			$str = $state->serializeLinkChildrenToString( $node, $handler );
			$chunk = null;
			if ( !$hrefStr ) {
				// Without an href, we just emit the string as text.
				// However, to preserve targets for anchor links,
				// serialize as a span with a name.
				$name = DOMCompat::getAttribute( $node, 'name' );
				if ( $name !== null ) {
					$doc = $node->ownerDocument;
					$span = $doc->createElement( 'span' );
					$span->setAttribute( 'name', $name );
					$span->appendChild( $doc->createTextNode( $str ) );
					$chunk = DOMCompat::getOuterHTML( $span );
				} else {
					$chunk = $str;
				}
			} else {
				$chunk = new ExtLinkText( '[' . $hrefStr . ' ' . $str . ']',
					$node, $siteConfig, 'mw:ExtLink'
				);
			}
			$state->emitChunk( $chunk, $node );
		}
	}

	/**
	 * Main figure handler.
	 *
	 * @param SerializerState $state
	 * @param Element $node
	 * @param ?MediaStructure $ms
	 */
	public static function figureHandler(
		SerializerState $state, Element $node, ?MediaStructure $ms
	): void {
		if ( !$ms ) {
			$state->getEnv()->log(
				'error/html2wt/figure',
				"Couldn't parse media structure: ",
				DOMCompat::getOuterHTML( $node )
			);
			return;
		}
		$ct = self::figureToConstrainedText( $state, $ms );
		$state->emitChunk( $ct ?? '', $node );
	}

	/**
	 * Serialize a figure to contrained text.
	 *
	 * WARN: There's probably more to do to ensure this is purely functional,
	 * no side-effects (ie. calls to state->emit) happen while processing.
	 *
	 * @param SerializerState $state
	 * @param MediaStructure $ms
	 * @return ?ConstrainedText
	 */
	public static function figureToConstrainedText(
		SerializerState $state, MediaStructure $ms
	): ?ConstrainedText {
		$env = $state->getEnv();
		$outerElt = $ms->containerElt ?? $ms->mediaElt;
		$linkElt = $ms->linkElt;
		$elt = $ms->mediaElt;
		$captionElt = $ms->captionElt;
		$format = WTUtils::getMediaFormat( $outerElt );

		// Try to identify the local title to use for this image.
		$resource = $state->serializer->serializedImageAttrVal( $outerElt, $elt, 'resource' );
		if ( !isset( $resource['value'] ) ) {
			// from non-parsoid HTML: try to reconstruct resource from src?
			// (this won't work for manual-thumb images)
			$src = DOMCompat::getAttribute( $elt, 'src' );
			if ( $src === null ) {
				$env->log( 'error/html2wt/figure',
					'In WSP.figureHandler, img does not have resource or src:',
					DOMCompat::getOuterHTML( $outerElt )
				);
				return null;
			}
			if ( preg_match( '/^https?:/', $src ) ) {
				// external image link, presumably $wgAllowExternalImages=true
				return new AutoURLLinkText( $src, $outerElt );
			}
			$resource = [
				'value' => $src,
				'fromsrc' => false,
				'modified' => false
			];
		}
		if ( empty( $resource['fromsrc'] ) ) {
			$resource['value'] = preg_replace( '#^(\.\.?/)+#', '', $resource['value'], 1 );
		}

		$nopts = [];
		$outerDP = DOMDataUtils::getDataParsoid( $outerElt );
		$outerDMW = DOMDataUtils::getDataMw( $outerElt );
		$mwAliases = $state->getEnv()->getSiteConfig()->mwAliases();

		// Return ref to the array element in case it is modified
		$getOpt = static function & ( $key ) use ( &$outerDP ): ?array {
			$null = null;
			if ( empty( $outerDP->optList ) ) {
				return $null;
			}
			foreach ( $outerDP->optList as $opt ) {
				if ( ( $opt['ck'] ?? null ) === $key ) {
					return $opt;
				}
			}
			return $null;
		};
		// Return ref to the array element in case it is modified
		$getLastOpt = static function & ( $key ) use ( &$outerDP ): ?array {
			$null = null;
			$opts = $outerDP->optList ?? [];
			for ( $i = count( $opts ) - 1;  $i >= 0;  $i-- ) {
				if ( ( $opts[$i]['ck'] ?? null ) === $key ) {
					return $opts[$i];
				}
			}
			return $null;
		};

		// Try to identify the local title to use for the link.
		$link = null;

		$linkFromDataMw = WTSUtils::getAttrFromDataMw( $outerDMW, 'link', true );
		if ( $linkFromDataMw !== null ) {
			// "link" attribute on the `outerElt` takes precedence
			if ( isset( $linkFromDataMw[1]->html ) ) {
				$link = $state->serializer->getAttributeValueAsShadowInfo( $outerElt, 'link' );
			} else {
				$link = [
					'value' => "link={$linkFromDataMw[1]->txt}",
					'modified' => false,
					'fromsrc' => false,
					'fromDataMW' => true
				];
			}
		} elseif ( $linkElt && $linkElt->hasAttribute( 'href' ) ) {
			$link = $state->serializer->serializedImageAttrVal( $outerElt, $linkElt, 'href' );
			if ( empty( $link['fromsrc'] ) ) {
				// strip page or lang parameter if present on href
				$strippedHref = preg_replace(
					'#[?]((?:page=\d+)|(?:lang=[a-z]+(?:-[a-z]+)*))$#Di',
					'',
					DOMCompat::getAttribute( $linkElt, 'href' ) ?? ''
				);
				if ( $strippedHref === DOMCompat::getAttribute( $elt, 'resource' ) ) {
					// default link: same place as resource
					$link = $resource;
				}
				$link['value'] = preg_replace( '#^(\.\.?/)+#', '', $link['value'], 1 );
			}
		} else {
			// Otherwise, just try and get it from data-mw
			$link = $state->serializer->getAttributeValueAsShadowInfo( $outerElt, 'href' );
		}

		if ( $link && empty( $link['modified'] ) && empty( $link['fromsrc'] ) ) {
			$linkOpt = $getOpt( 'link' );
			if ( $linkOpt ) {
				$link['fromsrc'] = true;
				$link['value'] = $linkOpt['ak'];
			}
		}

		// Reconstruct the caption
		if ( !$captionElt && is_string( $outerDMW->caption ?? null ) ) {
			// IMPORTANT: Assign to a variable to prevent the fragment
			// from getting GCed before we are done with it.
			$fragment = ContentUtils::createAndLoadDocumentFragment(
				$outerElt->ownerDocument, $outerDMW->caption,
				[ 'markNew' => true ]
			);
			// FIXME: We should just be able to serialize the children of the
			// fragment, however, we need some way of marking this as being
			// inInsertedContent so that any bare text is assured to be escaped
			$captionElt = $outerElt->ownerDocument->createElement( 'div' );
			DOMDataUtils::getDataParsoid( $captionElt )->setTempFlag( TempData::IS_NEW );
			DOMUtils::migrateChildren( $fragment, $captionElt );
			// Needs a parent node in order for WTS to be happy
			$fragment->appendChild( $captionElt );
		}

		$caption = null;
		if ( $captionElt ) {
			$caption = $state->serializeCaptionChildrenToString(
				$captionElt, [ $state->serializer->wteHandlers, 'mediaOptionHandler' ]
			);

			// Alt stuff
			if ( !WTUtils::hasVisibleCaption( $outerElt ) && $elt->hasAttribute( 'alt' ) ) {
				$altOnElt = trim( DOMCompat::getAttribute( $elt, 'alt' ) ?? '' );
				$altFromCaption = trim( WTUtils::textContentFromCaption( $captionElt ) );
				// The first condition is to support an empty \alt=\ option
				// when no caption is present
				if ( $altOnElt && ( $altOnElt === $altFromCaption ) ) {
					$elt->removeAttribute( 'alt' );
				}
			}
		}

		// Fetch the alt (if any)
		$alt = $state->serializer->serializedImageAttrVal( $outerElt, $elt, 'alt' );
		// Fetch the lang (if any)
		$lang = $state->serializer->serializedImageAttrVal( $outerElt, $elt, 'lang' );
		// Fetch the muted (if any)
		$muted = $state->serializer->serializedImageAttrVal( $outerElt, $elt, 'muted' );
		// Fetch the loop (if any)
		$loop = $state->serializer->serializedImageAttrVal( $outerElt, $elt, 'loop' );

		// Ok, start assembling options, beginning with link & alt & lang
		// Other media don't have links in output.
		$linkCond = DOMCompat::nodeName( $elt ) === 'img';
		if ( $linkCond && $link ) {
			// Check whether the link goes to the default place, in which
			// case an explicit link tag isn't needed.
			// The link may be external, or may include wikitext template markup,
			// therefore check first that it parses to a title.
			$linkTitle = $env->normalizedTitleKey(
				Utils::decodeURIComponent( $link['value'] ), true
			);
			$resourceTitle = $env->normalizedTitleKey(
				Utils::decodeURIComponent( $resource['value'] ), true
			);
			if (
				$link['value'] === $resource['value'] ||
				( $linkTitle !== null && $linkTitle === $resourceTitle )
			) {
				$linkCond = false; // No explicit link attribute needed
			}
		}

		// "alt" for non-image is handle below
		$altCond = $alt['value'] !== null && DOMCompat::nodeName( $elt ) === 'img';

		// This loop handles media options which *mostly* correspond 1-1 with
		// HTML attributes.  `img_$name` is the name of the media option,
		// and $value is the Parsoid "shadow info" for the attribute.
		// $cond tells us whether we need to explicitly output this option;
		// if it is false we are using an implicit default.
		// `lang` and `alt` are fairly straightforward.  `link`
		// is a little trickier, since we need to massage/fake the shadow
		// info because it doesn't come *directly* from the attribute.
		// link comes from the combination of a[href], img[src], and
		// img[resource], etc;
		foreach ( [
			[ 'name' => 'link', 'value' => $link, 'cond' => $linkCond, 'alias' => 'img_link' ],
			[ 'name' => 'alt', 'value' => $alt, 'cond' => $altCond, 'alias' => 'img_alt' ],
			[ 'name' => 'lang', 'value' => $lang, 'cond' => isset( $lang['value'] ), 'alias' => 'img_lang' ],
			[ 'name' => 'muted', 'value' => $muted, 'cond' => isset( $muted['value'] ), 'alias' => 'timedmedia_muted' ],
			[ 'name' => 'loop', 'value' => $loop, 'cond' => isset( $loop['value'] ), 'alias' => 'timedmedia_loop' ],
		] as $o ) {
			if ( !$o['cond'] ) {
				continue;
			}
			if ( $o['value'] && !empty( $o['value']['fromsrc'] ) ) {
				$nopts[] = [
					'ck' => $o['name'],
					'ak' => [ $o['value']['value'] ],
				];
			} else {
				$value = $o['value'] ? $o['value']['value'] : '';
				if ( $o['value'] && in_array( $o['name'], [ 'link', 'alt' ], true ) ) {
					// see WikiLinkHandler::isWikitextOpt(): link and alt are allowed
					// to contain arbitrary wikitext, even though it is stripped
					// to a string before emitting.
					$value = $state->serializer->wteHandlers->escapeLinkContent(
						$state, $value, false, $outerElt, true
					);
				}
				$nopts[] = [
					'ck' => $o['name'],
					'v' => $value,
					'ak' => $mwAliases[$o['alias']],
				];
			}
		}

		// Now we handle media options which all come from space-separated
		// values in a single HTML attribute, `class`.  (But note that there
		// can also be "extra" classes added by `img_class` as well.)
		$classes = DOMCompat::getClassList( $outerElt );
		$extra = []; // 'extra' classes
		$val = null;

		foreach ( $classes as $c ) {
			switch ( $c ) {
				case 'mw-halign-none':
				case 'mw-halign-right':
				case 'mw-halign-left':
				case 'mw-halign-center':
					$val = substr( $c, 10 ); // strip mw-halign- prefix
					$nopts[] = [
						'ck' => $val,
						'ak' => $mwAliases['img_' . $val],
					];
					break;

				case 'mw-valign-top':
				case 'mw-valign-middle':
				case 'mw-valign-baseline':
				case 'mw-valign-sub':
				case 'mw-valign-super':
				case 'mw-valign-text-top':
				case 'mw-valign-bottom':
				case 'mw-valign-text-bottom':
					$val = strtr( substr( $c, 10 ), '-', '_' ); // strip mw-valign and '-' to '_'
					$nopts[] = [
						'ck' => $val,
						'ak' => $mwAliases['img_' . $val],
					];
					break;

				case 'mw-image-border':
					$nopts[] = [
						'ck' => 'border',
						'ak' => $mwAliases['img_border'],
					];
					break;

				case 'mw-default-size':
				case 'mw-default-audio-height':
					// handled below
					break;

				default:
					$extra[] = $c;
					break;
			}
		}

		if ( count( $extra ) ) {
			$nopts[] = [
				'ck' => 'class',
				'v' => implode( ' ', $extra ),
				'ak' => $mwAliases['img_class'],
			];
		}

		// Now we handle parameters which don't have a representation
		// as HTML attributes; they are set only from the data-mw
		// values.  (In theory they could perhaps be reverse engineered
		// from the thumbnail URL, but that would be fragile and expose
		// thumbnail implementation to the editor so we don't do that.)
		$mwParams = [
			[ 'prop' => 'thumb', 'ck' => 'manualthumb', 'alias' => 'img_manualthumb' ],
			[ 'prop' => 'page', 'ck' => 'page', 'alias' => 'img_page' ],
			// Video specific
			[ 'prop' => 'starttime', 'ck' => 'starttime', 'alias' => 'timedmedia_starttime' ],
			[ 'prop' => 'endtime', 'ck' => 'endtime', 'alias' => 'timedmedia_endtime' ],
			[ 'prop' => 'thumbtime', 'ck' => 'thumbtime', 'alias' => 'timedmedia_thumbtime' ]
		];

		// `img_link` and `img_alt` are only surfaced as HTML attributes
		// for image media. For all other media we treat them as set only
		// from data-mw.
		if ( DOMCompat::nodeName( $elt ) !== 'img' ) {
			$mwParams[] = [ 'prop' => 'link', 'ck' => 'link', 'alias' => 'img_link' ];
			$mwParams[] = [ 'prop' => 'alt', 'ck' => 'alt', 'alias' => 'img_alt' ];
		}

		$hasManualthumb = false;
		foreach ( $mwParams as $o ) {
			$v = $outerDMW->{$o['prop']} ?? null;
			if ( $v === null ) {
				$a = WTSUtils::getAttrFromDataMw( $outerDMW, $o['ck'], true );
				if ( $a !== null ) {
					if ( isset( $a[1]->html ) ) {
						$si = $state->serializer->getAttributeValueAsShadowInfo( $outerElt, $o['ck'] );
						if ( isset( $si['value'] ) ) {
							$nopts[] = [
								'ck' => $o['ck'],
								'ak' => [ $si['value'] ],
							];
							continue;
						}
					} else {
						$v = $a[1]->txt;
					}
				}
			}
			if ( $v !== null ) {
				$ak = $state->serializer->getAttributeValue(
					$outerElt, $o['ck']
				) ?? $mwAliases[$o['alias']];
				$nopts[] = [
					'ck' => $o['ck'],
					'ak' => $ak,
					'v' => $v
				];
				// Piggyback this here ...
				if ( $o['prop'] === 'thumb' ) {
					$hasManualthumb = true;
					$format = '';
				}
			}
		}

		// These media options come from the HTML `typeof` attribute.
		switch ( $format ) {
			case 'Thumb':
				$nopts[] = [
					'ck' => 'thumbnail',
					'ak' => $state->serializer->getAttributeValue(
						$outerElt, 'thumbnail'
					) ?? $mwAliases['img_thumbnail'],
				];
				break;
			case 'Frame':
				$nopts[] = [
					'ck' => 'framed',
					'ak' => $state->serializer->getAttributeValue(
						$outerElt, 'framed'
					) ?? $mwAliases['img_framed'],
				];
				break;
			case 'Frameless':
				$nopts[] = [
					'ck' => 'frameless',
					'ak' => $state->serializer->getAttributeValue(
						$outerElt, 'frameless'
					) ?? $mwAliases['img_frameless'],
				];
				break;
		}

		// Now handle the size-related options.  This is complicated!
		// We consider the `height`, `data-height`, `width`, and
		// `data-width` attributes, as well as the `typeof` and the `class`.

		// Get the user-specified height from wikitext
		$wh = $state->serializer->serializedImageAttrVal(
			$outerElt, $elt, $ms->isRedLink() ? 'data-height' : 'height'
		);
		// Get the user-specified width from wikitext
		$ww = $state->serializer->serializedImageAttrVal(
			$outerElt, $elt, $ms->isRedLink() ? 'data-width' : 'width'
		);

		$sizeUnmodified = !empty( $ww['fromDataMW'] ) ||
			( empty( $ww['modified'] ) && empty( $wh['modified'] ) );
		$upright = $getOpt( 'upright' );

		// XXX: Infer upright factor from default size for all thumbs by default?
		// Better for scaling with user prefs, but requires knowledge about
		// default used in VE.
		if ( $sizeUnmodified && $upright &&
			// Only serialize upright where it is actually respected
			// This causes some dirty diffs, but makes sure that we don't
			// produce nonsensical output after a type switch.
			// TODO: Only strip if type was actually modified.
			in_array( $format, [ 'Frameless', 'Thumb' ], true )
		) {
			// preserve upright option
			$nopts[] = [
				'ck' => $upright['ck'],
				'ak' => [ $upright['ak'] ], // FIXME: don't use ak here!
			];
		}

		if (
			!DOMUtils::hasClass( $outerElt, 'mw-default-size' ) &&
			$format !== 'Frame' && !$hasManualthumb
		) {
			$size = $getLastOpt( 'width' );
			$sizeString = (string)( $size['ak'] ?? '' );
			if ( $sizeString === '' && !empty( $ww['fromDataMW'] ) ) {
				$sizeString = (string)( $ww['value'] ?? '' );
			}
			if ( $sizeUnmodified && $sizeString !== '' ) {
				// preserve original width/height string if not touched
				$nopts[] = [
					'ck' => 'width',
					'v' => $sizeString, // original size string
					'ak' => [ '$1' ], // don't add px or the like
				];
			} else {
				$bbox = null;
				// Serialize to a square bounding box
				if ( isset( $ww['value'] ) && preg_match( '/^\d+/', $ww['value'] ) ) {
					$bbox = intval( $ww['value'] );
				}
				if ( isset( $wh['value'] ) && preg_match( '/^\d+/', $wh['value'] ) &&
					// As with "mw-default-size", editing clients should remove the
					// "mw-default-audio-height" if they want to factor a defined
					// height into the bounding box size.  However, note that, at
					// present, a defined height for audio is ignored while parsing,
					// so this only has the effect of modifying the width.
					(
						DOMCompat::nodeName( $elt ) !== 'audio' ||
						!DOMUtils::hasClass( $outerElt, 'mw-default-audio-height' )
					)
				) {
					$height = intval( $wh['value'] );
					if ( $bbox === null || $height > $bbox ) {
						$bbox = $height;
					}
				}
				if ( $bbox !== null ) {
					$nopts[] = [
						'ck' => 'width',
						// MediaWiki interprets 100px as a width
						// restriction only, so we need to make the bounding
						// box explicitly square (100x100px). The 'px' is
						// added by the alias though, and can be localized.
						'v' => $bbox . 'x' . $bbox,
						'ak' => $mwAliases['img_width'], // adds the 'px' suffix
					];
				}
			}
		}

		$opts = $outerDP->optList ?? []; // original wikitext options

		// Add bogus options from old optlist in order to round-trip cleanly (T64500)
		foreach ( $opts as $o ) {
			if ( ( $o['ck'] ?? null ) === 'bogus' ) {
				$nopts[] = [
					'ck' => 'bogus',
					'ak' => [ $o['ak'] ],
				];
			}
		}

		// Put the caption last, by default.
		if ( is_string( $caption ) ) {
			$nopts[] = [
				'ck' => 'caption',
				'ak' => [ $caption ],
			];
		}

		// ok, sort the new options to match the order given in the old optlist
		// and try to match up the aliases used
		$changed = false;
		foreach ( $nopts as &$no ) {
			// Make sure we have an array here. Default in data-parsoid is
			// actually a string.
			// FIXME: don't reuse ak for two different things!
			if ( !is_array( $no['ak'] ) ) {
				$no['ak'] = [ $no['ak'] ];
			}

			$no['sortId'] = count( $opts );
			$idx = -1;
			foreach ( $opts as $i => $o ) {
				if ( ( $o['ck'] ?? null ) === $no['ck'] &&
					// for bogus options, make sure the source matches too.
					( $o['ck'] !== 'bogus' || $o['ak'] === $no['ak'][0] )
				) {
					$idx = $i;
					break;
				}
			}
			if ( $idx < 0 ) {
				// Preferred words are first in the alias list
				// (but not in old versions of mediawiki).
				$no['ak'] = $no['ak'][0];
				$changed = true;
				continue;
			}

			$no['sortId'] = $idx;
			// use a matching alias, if there is one
			$a = null;
			foreach ( $no['ak'] as $b ) {
				// note the trim() here; that allows us to snarf eccentric
				// whitespace from the original option wikitext
				$b2 = $b;
				if ( isset( $no['v'] ) ) {
					$b2 = str_replace( '$1', $no['v'], $b );
				}
				if ( $b2 === trim( implode( ',', (array)$opts[$idx]['ak'] ) ) ) {
					$a = $b;
					break;
				}
			}
			// use the alias (incl whitespace) from the original option wikitext
			// if found; otherwise use the last alias given (English default by
			// convention that works everywhere).
			// TODO: use first alias (localized) instead for RTL languages (T53852)
			if ( $a !== null && $no['ck'] !== 'caption' ) {
				$no['ak'] = $opts[$idx]['ak'];
				unset( $no['v'] ); // prevent double substitution
			} else {
				$no['ak'] = PHPUtils::lastItem( $no['ak'] );
				if ( !( $no['ck'] === 'caption' && $a !== null ) ) {
					$changed = true;
				}
			}
		}

		// Filter out bogus options if the image options/caption have changed.
		if ( $changed ) {
			$nopts = array_filter( $nopts, static function ( $no ) {
				return $no['ck'] !== 'bogus';
			} );
			// empty captions should get filtered out in this case, too (T64264)
			$nopts = array_filter( $nopts, static function ( $no ) {
				return !( $no['ck'] === 'caption' && $no['ak'] === '' );
			} );
		}

		// sort!
		usort( $nopts, static function ( $a, $b ) {
			return $a['sortId'] <=> $b['sortId'];
		} );

		// emit all the options as wikitext!
		$wikitext = '[[' . $resource['value'];
		foreach ( $nopts as $o ) {
			$wikitext .= '|';
			if ( isset( $o['v'] ) ) {
				$wikitext .= str_replace( '$1', $o['v'], $o['ak'] );
			} else {
				$wikitext .= $o['ak'];
			}
		}
		$wikitext .= ']]';

		return new WikiLinkText(
			$wikitext, $outerElt, $state->getEnv()->getSiteConfig(), 'mw:File'
		);
	}

}
