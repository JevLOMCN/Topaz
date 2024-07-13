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

namespace Wikimedia\Parsoid\Wt2Html\TT;

use Wikimedia\Parsoid\Config\SiteConfig;
use Wikimedia\Parsoid\Core\Sanitizer;
use Wikimedia\Parsoid\Tokens\EndTagTk;
use Wikimedia\Parsoid\Tokens\SelfclosingTagTk;
use Wikimedia\Parsoid\Tokens\TagTk;
use Wikimedia\Parsoid\Tokens\Token;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\TokenUtils;
use Wikimedia\Parsoid\Wikitext\Consts;
use Wikimedia\Parsoid\Wt2Html\Frame;
use Wikimedia\Parsoid\Wt2Html\TokenTransformManager;

class SanitizerHandler extends TokenHandler {
	/** @var bool */
	private $inTemplate;

	private const NO_END_TAG_SET = [ 'br' => true ];

	/**
	 * Sanitize a token.
	 *
	 * If the token is unmodified, return null.
	 *
	 * XXX: Make attribute sanitation reversible by storing round-trip info in
	 * $token->dataParsoid object (which is serialized as JSON in a data-parsoid
	 * attribute in the DOM).
	 *
	 * @param SiteConfig $siteConfig
	 * @param Frame $frame
	 * @param Token|string $token
	 * @param bool $inTemplate
	 * @return Token|string|null
	 */
	private function sanitizeToken(
		SiteConfig $siteConfig, Frame $frame, $token, bool $inTemplate
	) {
		$i = null;
		$l = null;
		$kv = null;
		$attribs = $token->attribs ?? null;
		$allowedTags = Consts::$Sanitizer['AllowedLiteralTags'];

		if ( TokenUtils::isHTMLTag( $token )
			&& ( empty( $allowedTags[$token->getName()] )
				|| ( $token instanceof EndTagTk && !empty( self::NO_END_TAG_SET[$token->getName()] ) )
			)
		) { // unknown tag -- convert to plain text
			if ( !$inTemplate && !empty( $token->dataParsoid->tsr ) ) {
				// Just get the original token source, so that we can avoid
				// whitespace differences.
				$token = $token->getWTSource( $frame );
			} elseif ( !( $token instanceof EndTagTk ) ) {
				// Handle things without a TSR: For example template or extension
				// content. Whitespace in these is not necessarily preserved.
				$buf = '<' . $token->getName();
				for ( $i = 0, $l = count( $attribs );  $i < $l;  $i++ ) {
					$kv = $attribs[$i];
					$buf .= ' ' . TokenUtils::tokensToString( $kv->k ) .
						"='" . TokenUtils::tokensToString( $kv->v ) . "'";
				}
				if ( $token instanceof SelfclosingTagTk ) {
					$buf .= ' /';
				}
				$buf .= '>';
				$token = $buf;
			} else {
				$token = '</' . $token->getName() . '>';
			}
			return $token;
		}

		if ( $attribs && count( $attribs ) > 0 ) {
			// Sanitize attributes
			if ( $token instanceof TagTk || $token instanceof SelfclosingTagTk ) {
				$newAttrs = Sanitizer::sanitizeTagAttrs( $siteConfig, null, $token, $attribs );

				// Reset token attribs and rebuild
				$token->attribs = [];

				// SSS FIXME: We are right now adding shadow information for all sanitized
				// attributes.  This is being done to minimize dirty diffs for the first
				// cut.  It can be reasonably argued that we can permanently delete dangerous
				// and unacceptable attributes in the interest of safety/security and the
				// resultant dirty diffs should be acceptable.  But, this is something to do
				// in the future once we have passed the initial tests of parsoid acceptance.
				foreach ( $newAttrs as $k => $v ) {
					// explicit check against null to prevent discarding empty strings
					if ( $v[0] !== null ) {
						$token->addNormalizedAttribute( $k, $v[0], $v[1] );
					} else {
						$token->setShadowInfo( $v[2], $v[0], $v[1] );
					}
				}
			} else {
				// EndTagTk, drop attributes
				$token->attribs = [];
			}
			return $token;
		}

		return null;
	}

	/**
	 * @param TokenTransformManager $manager manager enviroment
	 * @param array $options various configuration options
	 */
	public function __construct( TokenTransformManager $manager, array $options ) {
		parent::__construct( $manager, $options );
		$this->inTemplate = $options['inTemplate'];
	}

	/**
	 * @inheritDoc
	 */
	public function onAny( $token ): ?TokenHandlerResult {
		if ( is_string( $token ) ) {
			return null;
		}
		$env = $this->env;
		$env->log( 'trace/sanitizer', $this->pipelineId, static function () use ( $token ) {
			return PHPUtils::jsonEncode( $token );
		} );

		// Pass through a transparent line meta-token
		if ( TokenUtils::isEmptyLineMetaToken( $token ) ) {
			$env->log( 'trace/sanitizer', $this->pipelineId, '--unchanged--' );
			return null;
		}

		$token = $this->sanitizeToken(
			$env->getSiteConfig(), $this->manager->getFrame(), $token, $this->inTemplate
		);

		$env->log( 'trace/sanitizer', $this->pipelineId, static function () use ( $token ) {
			return ' ---> ' . PHPUtils::jsonEncode( $token );
		} );
		return $token === null ? null : new TokenHandlerResult( [ $token ] );
	}
}
