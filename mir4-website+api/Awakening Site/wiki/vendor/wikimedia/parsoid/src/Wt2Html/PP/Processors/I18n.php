<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\PP\Processors;

use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Wt2Html\Wt2HtmlDOMProcessor;

/** This pass adds placeholders for i18n messages. It will eventually be replaced by a HTML2HTML pass in core. */
class I18n implements Wt2HtmlDOMProcessor {

	/**
	 * @inheritDoc
	 */
	public function run(
		Env $env, Node $root, array $options = [], bool $atTopLevel = false
	): void {
		'@phan-var Element|DocumentFragment $root';  // @var Element|DocumentFragment $root
		$spans = DOMCompat::querySelectorAll( $root, 'span[typeof~="mw:I18n"]' );
		foreach ( $spans as $span ) {
			DOMUtils::removeTypeOf( $span, 'mw:I18n' );
			$i18n = DOMDataUtils::getDataNodeI18n( $span );
			$span->appendChild(
				$span->ownerDocument->createTextNode( $i18n->key )
			);
		}
	}

}
