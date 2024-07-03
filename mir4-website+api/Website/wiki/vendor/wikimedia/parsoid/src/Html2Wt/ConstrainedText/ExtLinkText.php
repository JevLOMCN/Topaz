<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt\ConstrainedText;

use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\Config\SiteConfig;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\NodeData\DataParsoid;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMUtils;

/**
 * An external link, like `[http://example.com]`.
 */
class ExtLinkText extends ConstrainedText {
	/**
	 * @param string $text
	 * @param Element $node
	 * @param SiteConfig $siteConfig
	 * @param string $type
	 *   The type of the link, as described by the `rel` attribute.
	 */
	public function __construct(
		string $text, Element $node,
		SiteConfig $siteConfig, string $type
	) {
		parent::__construct( [
				'text' => $text,
				'node' => $node
			]
		);
	}

	protected static function fromSelSerImpl(
		string $text, Element $node, DataParsoid $dataParsoid,
		Env $env, array $opts
	): ?ExtLinkText {
		$stx = $dataParsoid->stx ?? '';
		if ( DOMUtils::hasRel( $node, 'mw:ExtLink' ) &&
			!in_array( $stx, [ 'simple', 'piped' ], true )
		) {
			$rel = DOMCompat::getAttribute( $node, 'rel' );
			return new ExtLinkText( $text, $node, $env->getSiteConfig(), $rel );
		}
		return null;
	}
}
