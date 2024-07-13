<?php

namespace MediaWiki\Skin\Timeless;

use MediaWiki\ResourceLoader\Context;
use MediaWiki\ResourceLoader\SkinModule;

/**
 * ResourceLoader module to set some LESS variables for the skin
 */
class TimelessVariablesModule extends SkinModule {
	/**
	 * Add our LESS variables
	 *
	 * @param Context $context
	 * @return array LESS variables
	 */
	protected function getLessVars( Context $context ) {
		$vars = parent::getLessVars( $context );
		$config = $this->getConfig();

		// Backdrop image
		$backdrop = $config->get( 'TimelessBackdropImage' );

		if ( $backdrop === 'cat.svg' ) {
			// expand default
			$backdrop = 'images/cat.svg';
		}

		return array_merge(
			$vars,
			[
				'backdrop-image' => "url($backdrop)",
				// 'logo-image' => ''
				// 'wordmark-image' => ''
				// +width cutoffs ...
			]
		);
	}

	/**
	 * Register the config var with the caching stuff so it properly updates the cache
	 *
	 * @param Context $context
	 * @return array
	 */
	public function getDefinitionSummary( Context $context ) {
		$summary = parent::getDefinitionSummary( $context );
		$summary[] = [
			'TimelessBackdropImage' => $this->getConfig()->get( 'TimelessBackdropImage' )
		];
		return $summary;
	}
}
