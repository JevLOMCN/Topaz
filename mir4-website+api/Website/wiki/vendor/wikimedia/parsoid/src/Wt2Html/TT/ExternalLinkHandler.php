<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\TT;

use Wikimedia\Parsoid\Core\Sanitizer;
use Wikimedia\Parsoid\NodeData\DataParsoid;
use Wikimedia\Parsoid\Tokens\EndTagTk;
use Wikimedia\Parsoid\Tokens\KV;
use Wikimedia\Parsoid\Tokens\SelfclosingTagTk;
use Wikimedia\Parsoid\Tokens\TagTk;
use Wikimedia\Parsoid\Tokens\Token;
use Wikimedia\Parsoid\Utils\PipelineUtils;
use Wikimedia\Parsoid\Utils\TokenUtils;
use Wikimedia\Parsoid\Wt2Html\PegTokenizer;

class ExternalLinkHandler extends TokenHandler {
	/** @var PegTokenizer */
	private $urlParser;

	/** @inheritDoc */
	public function __construct( object $manager, array $options ) {
		parent::__construct( $manager, $options );

		// Create a new peg parser for image options.
		if ( !$this->urlParser ) {
			// Actually the regular tokenizer, but we'll call it with the
			// url rule only.
			$this->urlParser = new PegTokenizer( $this->env );
		}
	}

	private static function imageExtensions( string $str ): bool {
		switch ( $str ) {
			case 'jpg': // fall through
			case 'png': // fall through
			case 'gif': // fall through
			case 'svg':
				return true;
			default:
				return false;
		}
	}

	private function arraySome( array $array, callable $fn ): bool {
		foreach ( $array as $value ) {
			if ( $fn( $value ) ) {
				return true;
			}
		}
		return false;
	}

	private function hasImageLink( string $href ): bool {
		$allowedPrefixes = $this->env->getSiteConfig()->allowedExternalImagePrefixes();
		$bits = explode( '.', $href );
		$hasImageExtension = count( $bits ) > 1 &&
			self::imageExtensions( end( $bits ) ) &&
			preg_match( '#^https?://#i', $href );
		// Typical settings for mediawiki configuration variables
		// $wgAllowExternalImages and $wgAllowExternalImagesFrom will
		// result in values like these:
		//  allowedPrefixes = undefined; // no external images
		//  allowedPrefixes = [''];      // allow all external images
		//  allowedPrefixes = ['http://127.0.0.1/', 'http://example.com'];
		// Note that the values include the http:// or https:// protocol.
		// See https://phabricator.wikimedia.org/T53092
		return $hasImageExtension &&
			// true if some prefix in the list matches href
			self::arraySome( $allowedPrefixes, static function ( string $prefix ) use ( &$href ) {
				return $prefix === "" || strpos( $href, $prefix ) === 0;
			} );
	}

	private function onUrlLink( Token $token ): ?TokenHandlerResult {
		$tagAttrs = null;
		$builtTag = null;
		$env = $this->env;
		$origHref = $token->getAttributeV( 'href' );
		$href = TokenUtils::tokensToString( $origHref );
		$dataParsoid = $token->dataParsoid->clone();

		if ( $this->hasImageLink( $href ) ) {
			$checkAlt = explode( '/', $href );
			$tagAttrs = [
				new KV( 'src', $href ),
				new KV( 'alt', end( $checkAlt ) ),
				new KV( 'rel', 'mw:externalImage' )
			];

			// combine with existing rdfa attrs
			$tagAttrs = WikiLinkHandler::buildLinkAttrs(
				$token->attribs, false, null, $tagAttrs )['attribs'];
			return new TokenHandlerResult(
				[ new SelfclosingTagTk( 'img', $tagAttrs, $dataParsoid ) ] );
		} else {
			$tagAttrs = [
				new KV( 'rel', 'mw:ExtLink' )
			];

			// combine with existing rdfa attrs
			// href is set explicitly below
			$tagAttrs = WikiLinkHandler::buildLinkAttrs(
				$token->attribs, false, null, $tagAttrs )['attribs'];
			$builtTag = new TagTk( 'a', $tagAttrs, $dataParsoid );
			$dataParsoid->stx = 'url';

			if ( !$this->options['inTemplate'] ) {
				// Since we messed with the text of the link, we need
				// to preserve the original in the RT data. Or else.
				$builtTag->addNormalizedAttribute(
					'href', $href, $token->getWTSource( $this->manager->getFrame() )
				);
			} else {
				$builtTag->addAttribute( 'href', $href );
			}

			$dp = new DataParsoid;
			$dp->tsr = $dataParsoid->tsr->expandTsrK()->value;
			return new TokenHandlerResult( [
					$builtTag,
					// Make sure there are no IDN-ignored characters in the text so
					// the user doesn't accidentally copy any.
					Sanitizer::cleanUrl( $env->getSiteConfig(), $href, '' ), // mode could be 'wikilink'
					new EndTagTk(
						'a',
						[],
						$dp
					)
				]
			);
		}
	}

	/**
	 * Bracketed external link
	 * @param Token $token
	 * @return TokenHandlerResult|null
	 */
	private function onExtLink( Token $token ): ?TokenHandlerResult {
		$newAttrs = null;
		$aStart = null;
		$env = $this->env;
		$origHref = $token->getAttributeV( 'href' );
		$hasExpandedAttrs = TokenUtils::hasTypeOf( $token, 'mw:ExpandedAttrs' );
		$href = TokenUtils::tokensToString( $origHref );
		$hrefWithEntities = TokenUtils::tokensToString( $origHref, false, [
				'includeEntities' => true
			]
		);
		$content = $token->getAttributeV( 'mw:content' );
		$dataParsoid = $token->dataParsoid->clone();
		$magLinkType = TokenUtils::matchTypeOf(
			$token, '#^mw:(Ext|Wiki)Link/(ISBN|RFC|PMID)$#'
		);
		$tokens = null;

		if ( $magLinkType ) {
			$newHref = $href;
			$newRel = 'mw:ExtLink';
			if ( str_ends_with( $magLinkType, '/ISBN' ) ) {
				$newHref = $env->getSiteConfig()->relativeLinkPrefix() . $href;
				// ISBNs use mw:WikiLink instead of mw:ExtLink
				$newRel = 'mw:WikiLink';
			}
			$newAttrs = [
				new KV( 'href', $newHref ),
				new KV( 'rel', $newRel )
			];
			$token->removeAttribute( 'typeof' );

			// SSS FIXME: Right now, Parsoid does not support templating
			// of ISBN attributes.  So, "ISBN {{1x|1234567890}}" will not
			// parse as you might expect it to.  As a result, this code below
			// that attempts to combine rdf attrs from earlier is unnecessary
			// right now.  But, it will become necessary if Parsoid starts
			// supporting templating of ISBN attributes.
			//
			// combine with existing rdfa attrs
			$newAttrs = WikiLinkHandler::buildLinkAttrs(
				$token->attribs, false, null, $newAttrs )['attribs'];
			$aStart = new TagTk( 'a', $newAttrs, $dataParsoid );
			$tokens = array_merge( [ $aStart ],
				is_array( $content ) ? $content : [ $content ], [ new EndTagTk( 'a' ) ] );
			return new TokenHandlerResult( $tokens );
		} elseif ( ( !$hasExpandedAttrs && is_string( $origHref ) ) ||
					$this->urlParser->tokenizeURL( $hrefWithEntities ) !== false
		) {
			if ( is_array( $content ) && count( $content ) === 1 && is_string( $content[0] ) ) {
				$src = $content[0];
				if ( $env->getSiteConfig()->hasValidProtocol( $src ) &&
					$this->urlParser->tokenizeURL( $src ) !== false &&
					$this->hasImageLink( $src )
				) {
					$checkAlt = explode( '/', $src );
					$dp = new DataParsoid;
					$dp->type = 'extlink';
					$content = [ new SelfclosingTagTk( 'img', [
						new KV( 'src', $src ),
						new KV( 'alt', end( $checkAlt ) )
						], $dp
					) ];
				}
			}

			$newAttrs = [ new KV( 'rel', 'mw:ExtLink' ) ];
			// combine with existing rdfa attrs
			// href is set explicitly below
			$newAttrs = WikiLinkHandler::buildLinkAttrs(
				$token->attribs, false, null, $newAttrs )['attribs'];
			$aStart = new TagTk( 'a', $newAttrs, $dataParsoid );

			if ( !$this->options['inTemplate'] ) {
				// If we are from a top-level page, add normalized attr info for
				// accurate roundtripping of original content.
				//
				// extLinkContentOffsets->start covers all spaces before content
				// and we need src without those spaces.
				$tsr0a = $dataParsoid->tsr->start + 1;
				$tsr1a = $dataParsoid->extLinkContentOffsets->start -
					strlen( $token->getAttributeV( 'spaces' ) ?? '' );
				$length = $tsr1a - $tsr0a;
				$aStart->addNormalizedAttribute( 'href', $href,
					substr( $this->manager->getFrame()->getSrcText(), $tsr0a, $length ) );
			} else {
				$aStart->addAttribute( 'href', $href );
			}

			$content = PipelineUtils::getDOMFragmentToken(
				$content,
				$dataParsoid->tsr ? $dataParsoid->extLinkContentOffsets : null,
				[ 'inlineContext' => true, 'token' => $token ]
			);

			$tokens = [ $aStart, $content, new EndTagTk( 'a' ) ];
			return new TokenHandlerResult( $tokens );
		} else {
			// Not a link, convert href to plain text.
			return new TokenHandlerResult( WikiLinkHandler::bailTokens( $this->manager, $token ) );
		}
	}

	/** @inheritDoc */
	public function onTag( Token $token ): ?TokenHandlerResult {
		switch ( $token->getName() ) {
			case 'urllink':
				return $this->onUrlLink( $token );
			case 'extlink':
				return $this->onExtLink( $token );
			default:
				return null;
		}
	}
}
