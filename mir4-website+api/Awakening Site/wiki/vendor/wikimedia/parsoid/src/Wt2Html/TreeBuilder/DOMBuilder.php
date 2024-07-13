<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\TreeBuilder;

use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\RemexHtml\DOM\DOMBuilder as RemexDOMBuilder;

/**
 * This is the DOMBuilder subclass used by Wt2Html
 */
class DOMBuilder extends RemexDOMBuilder {
	public function __construct() {
		parent::__construct( [
			'suppressHtmlNamespace' => true,
			# 'suppressIdAttribute' => true,
			#'domExceptionClass' => \Wikimdedia\Dodo\DOMException::class,
		] );
	}

	/**
	 * @param string|null $doctypeName
	 * @param string|null $public
	 * @param string|null $system
	 * @return \DOMDocument
	 */
	protected function createDocument(
		string $doctypeName = null,
		string $public = null,
		string $system = null
	) {
		// @phan-suppress-next-line PhanTypeMismatchReturn
		return DOMCompat::newDocument( $doctypeName === 'html' );
	}
}
