<?php

namespace MediaWiki\Extension\VisualEditor;

use MediaWiki\Permissions\Authority;
use MediaWiki\Rest\Handler\Helper\PageRestHelperFactory;
use RequestContext;

/**
 * @since 1.40
 */
class VisualEditorParsoidClientFactory {

	/**
	 * @internal For use by ServiceWiring.php only or when locating the service
	 * @var string
	 */
	public const SERVICE_NAME = 'VisualEditor.ParsoidClientFactory';

	private PageRestHelperFactory $pageRestHelperFactory;

	public function __construct(
		PageRestHelperFactory $pageRestHelperFactory
	) {
		$this->pageRestHelperFactory = $pageRestHelperFactory;
	}

	/**
	 * Create a ParsoidClient for accessing Parsoid.
	 *
	 * @param string|string[]|false $cookiesToForward
	 * @param Authority|null $performer
	 *
	 * @return ParsoidClient
	 */
	public function createParsoidClient(
		/* Kept for compatibility with other extensions */ $cookiesToForward,
		?Authority $performer = null
	): ParsoidClient {
		if ( $performer === null ) {
			$performer = RequestContext::getMain()->getAuthority();
		}

		return $this->createDirectClient( $performer );
	}

	/**
	 * Create a ParsoidClient for accessing Parsoid.
	 *
	 * @param Authority $performer
	 *
	 * @return ParsoidClient
	 */
	private function createDirectClient( Authority $performer ): ParsoidClient {
		return new DirectParsoidClient(
			$this->pageRestHelperFactory,
			$performer
		);
	}

}
