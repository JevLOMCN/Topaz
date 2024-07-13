<?php

namespace MediaWiki\Extension\Thanks;

use MediaWiki\MediaWikiServices;

/**
 * Service container class for the Thanks extension.
 */
class ThanksServices {

	/** @var MediaWikiServices */
	private MediaWikiServices $services;

	/**
	 * Convenience method for returning an instance without having to use new, for chaining.
	 * @param MediaWikiServices $services
	 * @return self
	 */
	public static function wrap( MediaWikiServices $services ): self {
		return new self( $services );
	}

	/**
	 * @param MediaWikiServices $services
	 */
	public function __construct( MediaWikiServices $services ) {
		$this->services = $services;
	}

	/** @return ThanksQueryHelper */
	public function getQueryHelper(): ThanksQueryHelper {
		return $this->services->get( 'ThanksQueryHelper' );
	}

}
