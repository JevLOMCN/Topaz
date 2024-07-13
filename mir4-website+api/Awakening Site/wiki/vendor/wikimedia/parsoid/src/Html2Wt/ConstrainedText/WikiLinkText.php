<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt\ConstrainedText;

use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\Config\SiteConfig;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\NodeData\DataParsoid;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\PHPUtils;

/**
 * An internal wiki link, like `[[Foo]]`.
 */
class WikiLinkText extends RegExpConstrainedText {
	/** @var bool */
	private $greedy = false;

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
		// category links/external links/images don't use link trails or prefixes
		$noTrails = preg_match( '#^mw:WikiLink(/Interwiki)?$#D', $type ) === 0;
		$badPrefix = '/(^|[^\[])(\[\[)*\[$/D';
		$linkPrefixRegex = $siteConfig->linkPrefixRegex();
		if ( !$noTrails && $linkPrefixRegex ) {
			$badPrefix =
				'/(' . PHPUtils::reStrip( $linkPrefixRegex, '/' ) . ')' .
				'|(' . PHPUtils::reStrip( $badPrefix, '/' ) . ')/uD';
		}
		parent::__construct( [
			'text' => $text,
			'node' => $node,
			'badPrefix' => $badPrefix,
			'badSuffix' => ( $noTrails ) ? null : $siteConfig->linkTrailRegex(),
		] );
		// We match link trails greedily when they exist.
		if ( !( $noTrails || substr( $text, -1 ) === ']' ) ) {
			$this->greedy = true;
		}
	}

	/** @inheritDoc */
	public function escape( State $state ): Result {
		$r = parent::escape( $state );
		// If previous token was also a WikiLink, its linktrail will
		// eat up any possible linkprefix characters, so we don't need
		// a <nowiki> in this case.  (Eg: [[a]]-[[b]] in iswiki; the -
		// character is both a link prefix and a link trail, but it gets
		// preferentially associated with the [[a]] as a link trail.)
		$r->greedy = $this->greedy;
		return $r;
	}

	protected static function fromSelSerImpl(
		string $text, Element $node, DataParsoid $dataParsoid,
		Env $env, array $opts
	): ?WikiLinkText {
		$stx = $dataParsoid->stx ?? '';
		if (
			DOMUtils::matchRel( $node, '#^mw:WikiLink(/Interwiki)?$#D' ) &&
			in_array( $stx, [ 'simple', 'piped' ], true )
		) {
			$rel = DOMCompat::getAttribute( $node, 'rel' );
			return new WikiLinkText( $text, $node, $env->getSiteConfig(), $rel );
		}
		return null;
	}
}
