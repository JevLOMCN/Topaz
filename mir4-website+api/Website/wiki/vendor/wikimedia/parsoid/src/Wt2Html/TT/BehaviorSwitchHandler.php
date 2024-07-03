<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\TT;

use Wikimedia\Parsoid\Tokens\KV;
use Wikimedia\Parsoid\Tokens\SelfclosingTagTk;
use Wikimedia\Parsoid\Tokens\Token;
use Wikimedia\Parsoid\Wt2Html\TokenTransformManager;

/**
 * Handler for behavior switches, like '__TOC__' and similar.
 */
class BehaviorSwitchHandler extends TokenHandler {

	public function __construct( TokenTransformManager $manager, array $options ) {
		parent::__construct( $manager, $options );
	}

	/**
	 * Main handler.
	 * See {@link TokenTransformManager#addTransform}'s transformation parameter.
	 *
	 * @param Token $token
	 * @return TokenHandlerResult
	 */
	public function onBehaviorSwitch( Token $token ): TokenHandlerResult {
		$env = $this->env;
		$magicWord = $env->getSiteConfig()->getMagicWordForBehaviorSwitch( $token->attribs[0]->v );
		$env->setBehaviorSwitch( $magicWord, true );
		$metaToken = new SelfclosingTagTk(
			'meta',
			[ new KV( 'property', 'mw:PageProp/' . $magicWord ) ],
			$token->dataParsoid->clone()
		);

		return new TokenHandlerResult( [ $metaToken ] );
	}

	/**
	 * @inheritDoc
	 */
	public function onTag( Token $token ): ?TokenHandlerResult {
		return $token->getName() === 'behavior-switch' ? $this->onBehaviorSwitch( $token ) : null;
	}
}
