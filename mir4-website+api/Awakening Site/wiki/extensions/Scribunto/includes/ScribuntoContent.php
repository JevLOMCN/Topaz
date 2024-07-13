<?php
/**
 * Scribunto Content Model
 *
 * @file
 * @ingroup Extensions
 * @ingroup Scribunto
 *
 * @author Brad Jorsch <bjorsch@wikimedia.org>
 */

namespace MediaWiki\Extension\Scribunto;

use MediaWiki\Title\Title;
use TextContent;

/**
 * Represents the content of a Scribunto script page
 */
class ScribuntoContent extends TextContent {

	/**
	 * @param string $text
	 */
	public function __construct( $text ) {
		parent::__construct( $text, CONTENT_MODEL_SCRIBUNTO );
	}

	/**
	 * @inheritDoc
	 */
	public function updateRedirect( Title $target ) {
		return Scribunto::newDefaultEngine()->updateRedirect( $this, $target );
	}

	/**
	 * @inheritDoc
	 */
	public function getRedirectTarget() {
		return Scribunto::newDefaultEngine()->getRedirectTarget( $this );
	}
}
