<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Mocks;

use Wikimedia\Bcp47Code\Bcp47Code;
use Wikimedia\Parsoid\Config\PageConfig;
use Wikimedia\Parsoid\Config\PageContent;
use Wikimedia\Parsoid\Config\SiteConfig;
use Wikimedia\Parsoid\Core\LinkTarget;
use Wikimedia\Parsoid\Utils\Title;
use Wikimedia\Parsoid\Utils\Utils;

class MockPageConfig extends PageConfig {
	private SiteConfig $siteConfig;

	/** @var ?PageContent */
	private $content;

	/** @var int */
	private $pageid;

	private Title $title;

	private Bcp47Code $pagelanguage;

	/** @var ?string */
	private $pagelanguageDir;

	/**
	 * Construct a mock environment object for use in tests
	 * @param SiteConfig $siteConfig
	 * @param array $opts
	 * @param ?PageContent $content
	 */
	public function __construct( SiteConfig $siteConfig, array $opts, ?PageContent $content ) {
		$this->siteConfig = $siteConfig;
		$this->content = $content;
		$this->title = Title::newFromText( $opts['title'] ?? 'TestPage', $siteConfig, $opts['pagens'] ?? null );
		$this->pageid = $opts['pageid'] ?? -1;
		$this->pagelanguage = Utils::mwCodeToBcp47( $opts['pageLanguage'] ?? 'en' );
		$this->pagelanguageDir = $opts['pageLanguageDir'] ?? null;
	}

	/** @inheritDoc */
	public function getContentModel(): string {
		return 'wikitext';
	}

	/** @inheritDoc */
	public function getLinkTarget(): LinkTarget {
		return $this->title;
	}

	/** @inheritDoc */
	public function getPageId(): int {
		return $this->pageid;
	}

	/** @inheritDoc */
	public function getPageLanguageBcp47(): Bcp47Code {
		return $this->pagelanguage;
	}

	/** @inheritDoc */
	public function getPageLanguageDir(): string {
		return $this->pagelanguageDir ?? 'rtl';
	}

	/** @inheritDoc */
	public function getRevisionId(): ?int {
		return 1;
	}

	/** @inheritDoc */
	public function getParentRevisionId(): ?int {
		return null;
	}

	/** @inheritDoc */
	public function getRevisionTimestamp(): ?string {
		return null;
	}

	/** @inheritDoc */
	public function getRevisionUser(): ?string {
		// @phan-suppress-previous-line PhanPluginNeverReturnMethod
		throw new \BadMethodCallException( 'Not implemented' );
	}

	/** @inheritDoc */
	public function getRevisionUserId(): ?int {
		// @phan-suppress-previous-line PhanPluginNeverReturnMethod
		throw new \BadMethodCallException( 'Not implemented' );
	}

	/** @inheritDoc */
	public function getRevisionSha1(): ?string {
		return null;
	}

	/** @inheritDoc */
	public function getRevisionSize(): ?int {
		// @phan-suppress-previous-line PhanPluginNeverReturnMethod
		throw new \BadMethodCallException( 'Not implemented' );
	}

	/** @inheritDoc */
	public function getRevisionContent(): ?PageContent {
		return $this->content;
	}

}
