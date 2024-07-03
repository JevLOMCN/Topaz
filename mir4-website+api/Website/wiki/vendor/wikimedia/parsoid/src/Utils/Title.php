<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Utils;

use Wikimedia\Assert\Assert;
use Wikimedia\IPUtils;
use Wikimedia\Parsoid\Config\SiteConfig;
use Wikimedia\Parsoid\Core\LinkTarget;
use Wikimedia\Parsoid\Core\LinkTargetTrait;

class Title implements LinkTarget {
	use LinkTargetTrait;

	/** @var string */
	private $interwiki;

	/** @var int */
	private $namespaceId;

	/** @var string */
	private $namespaceName;

	/** @var string */
	private $dbkey;

	/** @var string */
	private $fragment;

	// cached values of prefixed title/key
	private ?string $prefixedDBKey = null;
	private ?string $prefixedText = null;

	/**
	 * @param string $interwiki Interwiki prefix, or empty string if none
	 * @param string $key Page DBkey (with underscores, not spaces)
	 * @param int $namespaceId
	 * @param string $namespaceName (with spaces, not underscores)
	 * @param ?string $fragment
	 */
	private function __construct(
		string $interwiki, string $key, int $namespaceId, string $namespaceName, ?string $fragment = null
	) {
		$this->interwiki = $interwiki;
		$this->dbkey = $key;
		$this->namespaceId = $namespaceId;
		$this->namespaceName = $namespaceName;
		$this->fragment = $fragment ?? '';
	}

	public static function newFromText(
		string $title, SiteConfig $siteConfig, ?int $defaultNs = null
	): Title {
		if ( $defaultNs === null ) {
			$defaultNs = 0;
		}
		$origTitle = $title;

		if ( !mb_check_encoding( $title, 'UTF-8' ) ) {
			throw new TitleException( "Bad UTF-8 in title \"$origTitle\"", 'title-invalid-utf8', $origTitle );
		}

		// Strip Unicode bidi override characters.
		$title = preg_replace( '/[\x{200E}\x{200F}\x{202A}-\x{202E}]+/u', '', $title );
		if ( $title === null ) {
			throw new TitleException( "Bad UTF-8 in title \"$origTitle\"", 'title-invalid-utf8', $origTitle );
		}

		// Clean up whitespace
		$title = preg_replace(
			'/[ _\x{00A0}\x{1680}\x{180E}\x{2000}-\x{200A}\x{2028}\x{2029}\x{202F}\x{205F}\x{3000}]+/u',
			'_', $title
		);
		// Trim _ from beginning and end
		$title = trim( $title, '_' );

		if ( str_contains( $title, \UtfNormal\Constants::UTF8_REPLACEMENT ) ) {
			throw new TitleException( "Bad UTF-8 in title \"$title\"", 'title-invalid-utf8', $title );
		}

		// Initial colon indicates main namespace rather than specified default
		// but should not create invalid {ns,title} pairs such as {0,Project:Foo}
		if ( $title !== '' && $title[0] === ':' ) {
			$title = ltrim( substr( $title, 1 ), '_' );
			$defaultNs = 0;
		}

		if ( $title === '' ) {
			throw new TitleException( 'Empty title', 'title-invalid-empty', $title );
		}

		$ns = $defaultNs;
		$interwiki = null;

		# Namespace or interwiki prefix
		$prefixRegexp = "/^(.+?)_*:_*(.*)$/S";
		// MediaWikiTitleCodec::splitTitleString wraps a loop around the
		// next section, to allow it to repeat this prefix processing if
		// an interwiki prefix is found which points at the local wiki.
		$m = [];
		if ( preg_match( $prefixRegexp, $title, $m ) ) {
			$p = $m[1];
			$nsId = $siteConfig->canonicalNamespaceId( $p ) ??
				$siteConfig->namespaceId( $p );
			if ( $nsId !== null ) {
				$title = $m[2];
				$ns = $nsId;
				# For Talk:X pages, check if X has a "namespace" prefix
				if (
					$nsId === $siteConfig->canonicalNamespaceId( 'talk' ) &&
					preg_match( $prefixRegexp, $title, $x )
				) {
					if ( $siteConfig->namespaceId( $x[1] ) ) {
						// Disallow Talk:File:x type titles.
						throw new TitleException(
							"Invalid Talk namespace title \"$origTitle\"", 'title-invalid-talk-namespace', $title
						);
					} elseif ( $siteConfig->interwikiMapNoNamespaces()[$x[1]] ?? null ) {
						// Disallow Talk:Interwiki:x type titles.
						throw new TitleException(
							"Invalid Talk namespace title \"$origTitle\"", 'title-invalid-talk-namespace', $title
						);
					}
				}
			} elseif ( $siteConfig->interwikiMapNoNamespaces()[$p] ?? null ) {
				# Interwiki link
				$title = $m[2];
				$interwiki = strtolower( $p );

				# We don't check for a redundant interwiki prefix to the
				# local wiki, like core does here in
				# MediaWikiTitleCodec::splitTitleString;
				# core then does a `continue` to repeat the processing

				// If there's an initial colon after the interwiki, that also
				// resets the default namespace
				if ( $title !== '' && $title[0] === ':' ) {
					$title = trim( substr( $title, 1 ), '_' );
					$ns = 0;
				}
			}
			# If there's no recognized interwiki or namespace,
			# then let the colon expression be part of the title
		}

		$fragment = null;
		$fragmentIndex = strpos( $title, '#' );
		if ( $fragmentIndex !== false ) {
			$fragment = substr( $title, $fragmentIndex + 1 );
			$title = rtrim( substr( $title, 0, $fragmentIndex ), '_' );
		}

		$illegalCharsRe = '/[^' . $siteConfig->legalTitleChars() . ']'
			// URL percent encoding sequences interfere with the ability
			// to round-trip titles -- you can't link to them consistently.
			. '|%[0-9A-Fa-f]{2}'
			// XML/HTML character references produce similar issues.
			. '|&[A-Za-z0-9\x80-\xff]+;/S';
		if ( preg_match( $illegalCharsRe, $title ) ) {
			throw new TitleException(
				"Invalid characters in title \"$origTitle\"", 'title-invalid-characters', $title
			);
		}

		// Pages with "/./" or "/../" appearing in the URLs will often be
		// unreachable due to the way web browsers deal with 'relative' URLs.
		// Also, they conflict with subpage syntax. Forbid them explicitly.
		if ( str_contains( $title, '.' ) && (
			$title === '.' || $title === '..' ||
			str_starts_with( $title, './' ) ||
			str_starts_with( $title, '../' ) ||
			str_contains( $title, '/./' ) ||
			str_contains( $title, '/../' ) ||
			str_ends_with( $title, '/.' ) ||
			str_ends_with( $title, '/..' )
		) ) {
			throw new TitleException(
				"Title \"$origTitle\" contains relative path components", 'title-invalid-relative', $title
			);
		}

		// Magic tilde sequences? Nu-uh!
		if ( str_contains( $title, '~~~' ) ) {
			throw new TitleException(
				"Title \"$origTitle\" contains ~~~", 'title-invalid-magic-tilde', $title
			);
		}

		$maxLength = $ns === $siteConfig->canonicalNamespaceId( 'special' ) ? 512 : 255;
		if ( strlen( $title ) > $maxLength ) {
			throw new TitleException(
				"Title \"$origTitle\" is too long", 'title-invalid-too-long', $title
			);
		}

		if ( $interwiki === null && $siteConfig->namespaceCase( $ns ) === 'first-letter' ) {
			$title = $siteConfig->ucfirst( $title );
		}

		# Can't make a link to a namespace alone... "empty" local links can only be
		# self-links with a fragment identifier.
		if ( $title === '' && $interwiki === null && $ns !== $siteConfig->canonicalNamespaceId( '' ) ) {
			throw new TitleException( 'Empty title', 'title-invalid-empty', $title );
		}

		// This is from MediaWikiTitleCodec::splitTitleString() in core
		if ( $title !== '' && ( # T329690
			$ns === $siteConfig->canonicalNamespaceId( 'user' ) ||
			$ns === $siteConfig->canonicalNamespaceId( 'user_talk' )
		) ) {
			$title = IPUtils::sanitizeIP( $title );
		}

		// Any remaining initial :s are illegal.
		if ( $title !== '' && $title[0] == ':' ) {
			throw new TitleException(
				'Leading colon title', 'title-invalid-leading-colon', $title
			);
		}

		// This is not in core's splitTitleString but matches
		// mediawiki-title's newFromText.
		if ( $ns === $siteConfig->canonicalNamespaceId( 'special' ) ) {
			$title = self::fixSpecialName( $siteConfig, $title );
		}

		$namespaceName = $siteConfig->namespaceName( $ns );
		return new self( $interwiki ?? '', $title, $ns, $namespaceName, $fragment );
	}

	/**
	 * The interwiki component of this LinkTarget.
	 * This is the empty string if there is no interwiki component.
	 *
	 * @return string
	 */
	public function getInterwiki(): string {
		return $this->interwiki;
	}

	/**
	 * Get the DBkey, prefixed with interwiki prefix if any.
	 * This is Parsoid's convention, which differs from core;
	 * use ::getDBkey() for a method compatible with core's
	 * convention.
	 *
	 * @return string
	 * @see ::getDBkey()
	 */
	public function getKey(): string {
		if ( $this->interwiki ) {
			return $this->interwiki . ':' . $this->dbkey;
		}
		return $this->dbkey;
	}

	/**
	 * Get the main part of the link target, in canonical database form.
	 *
	 * The main part is the link target without namespace prefix or hash fragment.
	 * The database form means that spaces become underscores, this is also
	 * used for URLs.
	 *
	 * @return string
	 */
	public function getDBkey(): string {
		return $this->dbkey;
	}

	/**
	 * Get the prefixed DBkey
	 * @return string
	 */
	public function getPrefixedDBKey(): string {
		if ( $this->prefixedDBKey === null ) {
			$this->prefixedDBKey = $this->namespaceName === '' ? '' :
				( strtr( $this->namespaceName, ' ', '_' ) . ':' );
			$this->prefixedDBKey .= $this->getKey();
		}
		return $this->prefixedDBKey;
	}

	/**
	 * Get the prefixed text
	 * @return string
	 */
	public function getPrefixedText(): string {
		if ( $this->prefixedText === null ) {
			$this->prefixedText = $this->namespaceName === '' ? '' :
				( $this->namespaceName . ':' );
			$this->prefixedText .= strtr( $this->getKey(), '_', ' ' );
		}
		return $this->prefixedText;
	}

	/**
	 * Get the namespace ID
	 * @return int
	 */
	public function getNamespace(): int {
		return $this->namespaceId;
	}

	/**
	 * Get the human-readable name for the namespace
	 * (with spaces, not underscores).
	 * @return string
	 */
	public function getNamespaceName(): string {
		return $this->namespaceName;
	}

	/**
	 * Get the link fragment in text form (i.e. the bit after the hash `#`).
	 *
	 * @return string link fragment
	 */
	public function getFragment(): string {
		return $this->fragment ?? '';
	}

	/**
	 * Compare with another title.
	 *
	 * @param Title $title
	 * @return bool
	 */
	public function equals( Title $title ) {
		return $this->getNamespace() === $title->getNamespace() &&
			$this->getKey() === $title->getKey();
	}

	/**
	 * Returns true if this is a special page.
	 *
	 * @return bool
	 */
	public function isSpecialPage() {
		return $this->getNamespace() === -1; // NS_SPECIAL;
	}

	/**
	 * Use the default special page alias.
	 *
	 * @param SiteConfig $siteConfig
	 * @param string $title
	 * @return string
	 */
	public static function fixSpecialName(
		SiteConfig $siteConfig, string $title
	): string {
		$parts = explode( '/', $title, 2 );
		$specialName = $siteConfig->specialPageLocalName( $parts[0] );
		if ( $specialName !== null ) {
			$parts[0] = $specialName;
			$title = implode( '/', $parts );
		}
		return $title;
	}

	/**
	 * Create a new LinkTarget with a different fragment on the same page.
	 *
	 * It is expected that the same type of object will be returned, but the
	 * only requirement is that it is a LinkTarget.
	 *
	 * @param string $fragment The fragment override, or "" to remove it.
	 *
	 * @return self
	 */
	public function createFragmentTarget( string $fragment ) {
		return new self( $this->interwiki, $this->dbkey, $this->namespaceId, $this->namespaceName, $fragment ?: null );
	}

	/**
	 * Convert LinkTarget from core (or other implementation) into a
	 * Parsoid Title.
	 *
	 * @param LinkTarget $linkTarget
	 * @return self
	 */
	public static function newFromLinkTarget(
		LinkTarget $linkTarget, SiteConfig $siteConfig
	) {
		if ( $linkTarget instanceof Title ) {
			return $linkTarget;
		}
		$ns = $linkTarget->getNamespace();
		$namespaceName = $siteConfig->namespaceName( $ns );
		Assert::invariant(
			$namespaceName !== null,
			"Badtitle ({$linkTarget}) in unknown namespace ({$ns})"
		);
		return new self(
			$linkTarget->getInterwiki(),
			$linkTarget->getDBkey(),
			$linkTarget->getNamespace(),
			$namespaceName,
			$linkTarget->getFragment()
		);
	}
}
