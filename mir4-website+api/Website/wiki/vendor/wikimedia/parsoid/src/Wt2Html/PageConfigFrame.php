<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html;

use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\Config\PageConfig;
use Wikimedia\Parsoid\Config\SiteConfig;
use Wikimedia\Parsoid\Utils\Title;

/**
 * A special subclass of frame used for the topmost frame in the environment;
 * gets most of its actual data from a PageConfig object.
 */
class PageConfigFrame extends Frame {

	/**
	 * Create a top-level frame.
	 * @param Env $env
	 * @param PageConfig $pageConfig
	 * @param SiteConfig $siteConfig
	 */
	public function __construct(
		Env $env, PageConfig $pageConfig, SiteConfig $siteConfig
	) {
		$rev = $pageConfig->getRevisionContent();
		$content = $rev ? $rev->getContent( 'main' ) : '';
		parent::__construct(
			Title::newFromLinkTarget( $pageConfig->getLinkTarget(), $siteConfig ),
			$env,
			[],
			$content,
			null
		);
	}

	// XXX: override getSrcText() to mirror $pageConfig, if the $pageConfig
	// is mutable?
}
