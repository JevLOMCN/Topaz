<?php

declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Config\Api;

use Wikimedia\Assert\Assert;
use Wikimedia\Bcp47Code\Bcp47Code;
use Wikimedia\Parsoid\Config\PageConfig as IPageConfig;
use Wikimedia\Parsoid\Config\PageContent;
use Wikimedia\Parsoid\Config\SiteConfig as ISiteConfig;
use Wikimedia\Parsoid\Mocks\MockPageContent;
use Wikimedia\Parsoid\Utils\Title;
use Wikimedia\Parsoid\Utils\Utils;

/**
 * PageConfig via MediaWiki's Action API
 *
 * Note this is intended for testing, not performance.
 */
class PageConfig extends IPageConfig {

	/** @var ?ApiHelper */
	private $api;

	private ISiteConfig $siteConfig;

	/** @var Title */
	private $title;

	/** @var string|null */
	private $revid;

	/** @var array<string,mixed>|null */
	private $page;

	/** @var array<string,mixed>|null */
	private $rev;

	/** @var PageContent|null */
	private $content;

	/** @var ?Bcp47Code */
	private $pagelanguage;

	/** @var string|null */
	private $pagelanguageDir;

	/**
	 * @param ?ApiHelper $api (only needed if $opts doesn't provide page info)
	 * @param ISiteConfig $siteConfig
	 * @param array $opts
	 */
	public function __construct( ?ApiHelper $api, ISiteConfig $siteConfig, array $opts ) {
		parent::__construct();
		$this->api = $api;
		$this->siteConfig = $siteConfig;

		if ( !isset( $opts['title'] ) ) {
			throw new \InvalidArgumentException( '$opts[\'title\'] must be set' );
		}
		if ( !( $opts['title'] instanceof Title ) ) {
			throw new \InvalidArgumentException( '$opts[\'title\'] must be a Title' );
		}
		$this->title = $opts['title'];
		$this->revid = $opts['revid'] ?? null;
		# pageLanguage can/should be passed as a Bcp47Code object
		$this->pagelanguage = !empty( $opts['pageLanguage'] ) ?
			Utils::mwCodeToBcp47( $opts['pageLanguage'] ) : null;
		$this->pagelanguageDir = $opts['pageLanguageDir'] ?? null;

		// This option is primarily used to mock the page content.
		if ( isset( $opts['pageContent'] ) && empty( $opts['loadData'] ) ) {
			$this->mockPageContent( $opts );
		} else {
			Assert::invariant( $api !== null, 'Cannot load page info without an API' );
			# Lazily load later
			$this->page = null;
			$this->rev = null;

			if ( isset( $opts['pageContent'] ) ) {
				$this->loadData();
				$this->rev = [
					'slots' => [ 'main' => $opts['pageContent'] ],
				];
			}
		}
	}

	private function mockPageContent( array $opts ): void {
		$this->page = [
			'title' => $this->title->getPrefixedText(),
			'ns' => $this->title->getNamespace(),
			'pageid' => -1,
			'pagelanguage' => $opts['pageLanguage'] ?? 'en',
			'pagelanguagedir' => $opts['pageLanguageDir'] ?? 'ltr',
		];
		if ( isset( $opts['pageContent'] ) ) {
			$this->rev = [
				'slots' => [ 'main' => $opts['pageContent'] ],
			];
		}
	}

	private function loadData() {
		if ( $this->page !== null ) {
			return;
		}

		$params = [
			'action' => 'query',
			'prop' => 'info|revisions',
			'rvprop' => 'ids|timestamp|user|userid|sha1|size|content',
			'rvslots' => '*',
		];

		if ( !empty( $this->revid ) ) {
			$params['revids'] = $this->revid;
		} else {
			$params['titles'] = $this->title->getPrefixedDBKey();
			$params['rvlimit'] = 1;
		}

		$content = $this->api->makeRequest( $params );
		if ( !isset( $content['query']['pages'][0] ) ) {
			throw new \RuntimeException( 'Request for page failed' );
		}
		$this->page = $content['query']['pages'][0];

		$this->rev = $this->page['revisions'][0] ?? [];
		unset( $this->page['revisions'] );

		if ( isset( $this->rev['timestamp'] ) ) {
			$this->rev['timestamp'] = preg_replace( '/\D/', '', $this->rev['timestamp'] );
		}

		// Well, we tried but the page probably doesn't exist
		if ( !$this->rev ) {
			$this->mockPageContent( [] );  // FIXME: T234549
		}
	}

	/** @inheritDoc */
	public function getContentModel(): string {
		$this->loadData();
		return $this->rev['slots']['main']['contentmodel'] ?? 'wikitext';
	}

	/** @inheritDoc */
	public function getLinkTarget(): Title {
		$this->loadData();
		return Title::newFromText(
			$this->page['title'], $this->siteConfig, $this->page['ns']
		);
	}

	/** @inheritDoc */
	public function getPageId(): int {
		$this->loadData();
		return $this->page['pageid'] ?? 0;
	}

	/** @inheritDoc */
	public function getPageLanguageBcp47(): Bcp47Code {
		$this->loadData();
		# Note that 'en' is a last-resort fail-safe fallback; it shouldn't
		# ever be reached in practice.
		return $this->pagelanguage ??
			# T320662: core should provide an API to get the BCP-47 form directly
			Utils::mwCodeToBcp47( $this->page['pagelanguage'] ?? 'en' );
	}

	/** @inheritDoc */
	public function getPageLanguageDir(): string {
		$this->loadData();
		return $this->pagelanguageDir ?? $this->page['pagelanguagedir'] ?? 'ltr';
	}

	/** @inheritDoc */
	public function getRevisionId(): ?int {
		$this->loadData();
		return $this->rev['revid'] ?? null;
	}

	/** @inheritDoc */
	public function getParentRevisionId(): ?int {
		$this->loadData();
		return $this->rev['parentid'] ?? null;
	}

	/** @inheritDoc */
	public function getRevisionTimestamp(): ?string {
		$this->loadData();
		return $this->rev['timestamp'] ?? null;
	}

	/** @inheritDoc */
	public function getRevisionUser(): ?string {
		$this->loadData();
		return $this->rev['user'] ?? null;
	}

	/** @inheritDoc */
	public function getRevisionUserId(): ?int {
		$this->loadData();
		return $this->rev['userid'] ?? null;
	}

	/** @inheritDoc */
	public function getRevisionSha1(): ?string {
		$this->loadData();
		return $this->rev['sha1'] ?? null;
	}

	/** @inheritDoc */
	public function getRevisionSize(): ?int {
		$this->loadData();
		return $this->rev['size'] ?? null;
	}

	/** @inheritDoc */
	public function getRevisionContent(): ?PageContent {
		$this->loadData();
		if ( $this->rev && !$this->content ) {
			$this->content = new MockPageContent( $this->rev['slots'] );
		}
		return $this->content;
	}
}
