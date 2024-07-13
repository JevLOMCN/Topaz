<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Config;

use Wikimedia\Bcp47Code\Bcp47Code;
use Wikimedia\Parsoid\Core\LinkTarget;

/**
 * Page-level configuration interface for Parsoid
 */
abstract class PageConfig {

	/**
	 * Non-null to record the fact that conversion has been done on
	 * this page (to the specified variant).
	 * @var ?Bcp47Code
	 */
	private $htmlVariant = null;

	/**
	 * Base constructor.
	 *
	 * This constructor is public because it is used to create mock objects
	 * in our test suite.
	 */
	public function __construct() {
	}

	/**
	 * Get content model
	 * @return string
	 */
	abstract public function getContentModel(): string;

	/**
	 * Whether to suppress the Table of Contents for this page
	 * (a function of content model).
	 * @return bool
	 */
	public function getSuppressTOC(): bool {
		// This will eventually be abstract; for now default to 'false'
		return false;
	}

	/**
	 * The page's title, as a LinkTarget.
	 * @return LinkTarget
	 */
	abstract public function getLinkTarget(): LinkTarget;

	/**
	 * The page's namespace ID
	 * @return int
	 * @deprecated Use ::getLinkTarget()->getNamespace() instead
	 */
	public function getNs(): int {
		return $this->getLinkTarget()->getNamespace();
	}

	/**
	 * The page's ID, if any
	 * @return int 0 if the page doesn't (yet?) exist
	 */
	abstract public function getPageId(): int;

	/**
	 * The page's language code.
	 *
	 * @return Bcp47Code a BCP-47 language code
	 */
	abstract public function getPageLanguageBcp47(): Bcp47Code;

	/**
	 * The page's language direction
	 * @return string 'ltr' or 'rtl'
	 */
	abstract public function getPageLanguageDir(): string;

	/**
	 * The revision's ID, if any
	 * @return int|null
	 */
	abstract public function getRevisionId(): ?int;

	/**
	 * The revision's parent ID, if any
	 * @return int|null
	 */
	abstract public function getParentRevisionId(): ?int;

	/**
	 * The revision's timestamp, if any
	 * @return string|null "YYYYMMDDHHIISS" format
	 */
	abstract public function getRevisionTimestamp(): ?string;

	/**
	 * The revision's author's user name, if any
	 * @return string|null
	 */
	abstract public function getRevisionUser(): ?string;

	/**
	 * The revision's author's user ID, if any
	 * @return int|null 0 if the user is not registered
	 */
	abstract public function getRevisionUserId(): ?int;

	/**
	 * The revision's SHA1 checksum, if any
	 * @return string|null Hex encoded
	 */
	abstract public function getRevisionSha1(): ?string;

	/**
	 * The revision's length, if known
	 * @return int|null Bytes
	 */
	abstract public function getRevisionSize(): ?int;

	/**
	 * The revision's content
	 * @return PageContent|null
	 */
	abstract public function getRevisionContent(): ?PageContent;

	/**
	 * Get the page's language variant
	 * @return ?Bcp47Code a BCP-47 language code
	 */
	public function getVariantBcp47(): ?Bcp47Code {
		return $this->htmlVariant; # stored as BCP-47
	}

	/**
	 * Set the page's language variant.  (Records the fact that
	 * conversion has been done in the parser pipeline.)
	 * @param Bcp47Code $htmlVariant a BCP-47 language code
	 */
	public function setVariantBcp47( Bcp47Code $htmlVariant ): void {
		$this->htmlVariant = $htmlVariant; # stored as BCP-47
	}

	/**
	 * FIXME: Once we remove the hardcoded slot name here,
	 * the name of this method could be updated, if necessary.
	 *
	 * Shortcut method to get page source
	 * @deprecated Use $this->topFrame->getSrcText()
	 * @return string
	 */
	public function getPageMainContent(): string {
		return $this->getRevisionContent()->getContent( 'main' );
	}

}
