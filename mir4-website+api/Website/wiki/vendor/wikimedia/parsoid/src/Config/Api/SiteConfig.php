<?php

declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Config\Api;

use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use Wikimedia\Bcp47Code\Bcp47Code;
use Wikimedia\Parsoid\Config\SiteConfig as ISiteConfig;
use Wikimedia\Parsoid\Config\StubMetadataCollector;
use Wikimedia\Parsoid\Core\ContentMetadataCollector;
use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\Mocks\MockMetrics;
use Wikimedia\Parsoid\Utils\ConfigUtils;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\Title;
use Wikimedia\Parsoid\Utils\UrlUtils;
use Wikimedia\Parsoid\Utils\Utils;

/**
 * SiteConfig via MediaWiki's Action API
 *
 * Note this is intended for testing, not performance.
 */
class SiteConfig extends ISiteConfig {

	/** @var ApiHelper */
	private $api;

	/** @var array|null */
	private $siteData;

	/** @var array|null */
	private $protocols;

	/** @var string|null */
	private $baseUri;

	/** @var string|null */
	private $relativeLinkPrefix;

	/** @var string */
	private $savedCategoryRegexp;

	/** @var string */
	private $savedRedirectRegexp;

	/** @var string */
	private $savedBswRegexp;

	/** @var array<int,string> */
	protected $nsNames = [];

	/** @var array<int,string> */
	protected $nsCase = [];

	/** @var array<string,int> */
	protected $nsIds = [];

	/** @var array<string,int> */
	protected $nsCanon = [];

	/** @var array<int,bool> */
	protected $nsWithSubpages = [];

	/** @var array<string,string> */
	private $specialPageNames = [];

	/** @var array */
	private $specialPageAliases = [];

	/** @var array|null */
	private $interwikiMap;

	/** @var array<string,array>|null Keys are stored as lowercased BCP-47 code strings */
	private $variants;

	/** @var array<string,bool>|null Keys are stored as lowercased BCP-47 code strings */
	private $langConverterEnabled;

	/** @var array|null */
	private $apiMagicWords;

	/** @var array|null */
	private $paramMWs;

	/** @var array|null */
	private $apiVariables;

	/** @var array|null */
	private $apiFunctionHooks;

	/** @var array|null */
	private $allMWs;

	/** @var array|null */
	private $extensionTags;

	/** @var int|null */
	private $widthOption;

	/** @var int */
	private $maxDepth = 40;

	private $featureDetectionDone = false;
	private $hasVideoInfo = false;

	/** @var string[] Base parameters for a siteinfo query */
	public const SITE_CONFIG_QUERY_PARAMS = [
		'action' => 'query',
		'meta' => 'siteinfo',
		'siprop' => 'general|protocols|namespaces|namespacealiases|magicwords|interwikimap|'
			. 'languagevariants|defaultoptions|specialpagealiases|extensiontags|'
			. 'functionhooks|variables',
	];

	public function __construct( ApiHelper $api, array $opts ) {
		parent::__construct();

		$this->api = $api;

		$this->linterEnabled = (bool)( $opts['linting'] ?? false );
		$this->addHTMLTemplateParameters = (bool)( $opts['addHTMLTemplateParameters'] ?? false );

		if ( isset( $opts['maxDepth'] ) ) {
			$this->maxDepth = (int)$opts['maxDepth'];
		}

		$this->setLogger( $opts['logger'] ?? self::createLogger() );

		if ( isset( $opts['wt2htmlLimits'] ) ) {
			$this->wt2htmlLimits = array_merge(
				$this->wt2htmlLimits, $opts['wt2htmlLimits']
			);
		}
		if ( isset( $opts['html2wtLimits'] ) ) {
			$this->html2wtLimits = array_merge(
				$this->html2wtLimits, $opts['html2wtLimits']
			);
		}
	}

	protected function reset() {
		$this->siteData = null;
		$this->baseUri = null;
		$this->relativeLinkPrefix = null;
		// Superclass value reset since parsertests reuse SiteConfig objects
		$this->linkTrailRegex = false;
		$this->mwAliases = null;
		$this->interwikiMapNoNamespaces = null;
	}

	/**
	 * Combine sets of regex fragments
	 * @param string[][] $res
	 *  - $regexes[0] are case-insensitive regex fragments. Must not be empty.
	 *  - $regexes[1] are case-sensitive regex fragments. Must not be empty.
	 * @return string Combined regex fragment. May be an alternation. Assumes
	 *  the outer environment is case-sensitive.
	 */
	private function combineRegexArrays( array $res ): string {
		if ( $res ) {
			if ( isset( $res[0] ) ) {
				$res[0] = '(?i:' . implode( '|', $res[0] ) . ')';
			}
			if ( isset( $res[1] ) ) {
				$res[1] = '(?:' . implode( '|', $res[1] ) . ')';
			}
			return implode( '|', $res );
		}
		// None? Return a failing regex
		return '(?!)';
	}

	/**
	 * Add a new namespace to the config
	 *
	 * Protected access to let mocks and parser tests versions
	 * add new namespaces as required.
	 *
	 * @param array $ns Namespace info
	 */
	protected function addNamespace( array $ns ): void {
		$id = (int)$ns['id'];
		$this->nsNames[$id] = $ns['name'];
		$this->nsIds[Utils::normalizeNamespaceName( $ns['name'] )] = $id;
		$this->nsCanon[Utils::normalizeNamespaceName( $ns['canonical'] ?? $ns['name'] )] = $id;
		if ( $ns['subpages'] ) {
			$this->nsWithSubpages[$id] = true;
		}
		$this->nsCase[$id] = (string)$ns['case'];
	}

	private function detectFeatures(): void {
		if ( !$this->featureDetectionDone ) {
			$this->featureDetectionDone = true;
			$data = $this->api->makeRequest( [ 'action' => 'paraminfo', 'modules' => 'query' ] );
			$props = $data["paraminfo"]["modules"][0]["parameters"]["0"]["type"] ?? [];
			$this->hasVideoInfo = in_array( 'videoinfo', $props, true );
		}
	}

	public function hasVideoInfo(): bool {
		$this->detectFeatures();
		return $this->hasVideoInfo;
	}

	/**
	 * Load site data from the Action API, if necessary
	 */
	private function loadSiteData(): void {
		if ( $this->siteData !== null ) {
			return;
		}

		$data = $this->api->makeRequest( self::SITE_CONFIG_QUERY_PARAMS )['query'];

		$this->siteData = $data['general'];
		$this->widthOption = $data['general']['thumblimits'][$data['defaultoptions']['thumbsize']];
		$this->protocols = $data['protocols'];
		$this->apiVariables = $data['variables'];
		$this->apiFunctionHooks = PHPUtils::makeSet( $data['functionhooks'] );

		// Process namespace data from API
		$this->nsNames = [];
		$this->nsCase = [];
		$this->nsIds = [];
		$this->nsCanon = [];
		$this->nsWithSubpages = [];
		foreach ( $data['namespaces'] as $ns ) {
			$this->addNamespace( $ns );
		}
		foreach ( $data['namespacealiases'] as $ns ) {
			$this->nsIds[Utils::normalizeNamespaceName( $ns['alias'] )] = $ns['id'];
		}

		// Process magic word data from API
		$bsws = [];
		$this->paramMWs = [];
		$this->allMWs = [];

		// Recast the API results in the format that core MediaWiki returns internally
		// This enables us to use the Production SiteConfig without changes and add the
		// extra overhead to this developer API usage.
		$this->apiMagicWords = [];
		foreach ( $data['magicwords'] as $mw ) {
			$cs = (int)$mw['case-sensitive'];
			$mwName = $mw['name'];
			$this->apiMagicWords[$mwName][] = $cs;
			$pmws = [];
			$allMWs = [];
			foreach ( $mw['aliases'] as $alias ) {
				$this->apiMagicWords[$mwName][] = $alias;
				// Aliases for double underscore mws include the underscores
				if ( substr( $alias, 0, 2 ) === '__' && substr( $alias, -2 ) === '__' ) {
					$bsws[$cs][] = preg_quote( substr( $alias, 2, -2 ), '@' );
				}
				if ( strpos( $alias, '$1' ) !== false ) {
					$pmws[$cs][] = strtr( preg_quote( $alias, '/' ), [ '\\$1' => "(.*?)" ] );
				}
				$allMWs[$cs][] = preg_quote( $alias, '/' );
			}

			if ( $pmws ) {
				$this->paramMWs[$mwName] = '/^(?:' . $this->combineRegexArrays( $pmws ) . ')$/uDS';
			}
			$this->allMWs[$mwName] = '/^(?:' . $this->combineRegexArrays( $allMWs ) . ')$/D';
		}

		$bswRegexp = $this->combineRegexArrays( $bsws );

		// Parse interwiki map data from the API
		$this->interwikiMap = ConfigUtils::computeInterwikiMap( $data['interwikimap'] );

		// Parse variant data from the API
		# T320662: API should return these in BCP-47 forms
		$this->langConverterEnabled = [];
		$this->variants = [];
		foreach ( $data['languagevariants'] as $base => $variants ) {
			$baseBcp47 = Utils::mwCodeToBcp47( $base );
			if ( $this->siteData['langconversion'] ) {
				$baseKey = strtolower( $baseBcp47->toBcp47Code() );
				$this->langConverterEnabled[$baseKey] = true;
				foreach ( $variants as $code => $vdata ) {
					$variantKey = strtolower( Utils::mwCodeToBcp47( $code )->toBcp47Code() );
					$this->variants[$variantKey] = [
						'base' => $baseBcp47,
						'fallbacks' => array_map(
							[ Utils::class, 'mwCodeToBcp47' ],
							$vdata['fallbacks']
						),
					];
				}
			}
		}

		// Parse extension tag data from the API
		$this->extensionTags = [];
		foreach ( $data['extensiontags'] as $tag ) {
			$tag = preg_replace( '/^<|>$/D', '', $tag );
			$this->ensureExtensionTag( $tag );
		}

		$this->specialPageAliases = $data['specialpagealiases'];
		$this->specialPageNames = [];
		foreach ( $this->specialPageAliases as $special ) {
			$alias = strtr( mb_strtoupper( $special['realname'] ), ' ', '_' );
			$this->specialPageNames[$alias] = $special['aliases'][0];
			foreach ( $special['aliases'] as $alias ) {
				$alias = strtr( mb_strtoupper( $alias ), ' ', '_' );
				$this->specialPageNames[$alias] = $special['aliases'][0];
			}
		}

		$redirect = '(?i:\#REDIRECT)';
		$quote = static function ( $s ) {
			$q = preg_quote( $s, '@' );
			# Note that PHP < 7.3 doesn't escape # in preg_quote.  That means
			# that the $redirect regexp will fail if used with the `x` flag.
			# Manually hack around this for PHP 7.2; can remove this workaround
			# once minimum PHP version >= 7.3
			if ( preg_quote( '#' ) === '#' ) {
				$q = str_replace( '#', '\\#', $q );
			}
			return $q;
		};
		foreach ( $data['magicwords'] as $mw ) {
			if ( $mw['name'] === 'redirect' ) {
				$redirect = implode( '|', array_map( $quote, $mw['aliases'] ) );
				if ( !$mw['case-sensitive'] ) {
					$redirect = '(?i:' . $redirect . ')';
				}
				break;
			}
		}
		// `$this->nsNames[14]` is set earlier by the calls to `$this->addNamespace( $ns )`
		// @phan-suppress-next-line PhanCoalescingAlwaysNull
		$category = $this->quoteTitleRe( $this->nsNames[14] ?? 'Category', '@' );
		if ( $category !== 'Category' ) {
			$category = "(?:$category|Category)";
		}

		$this->savedCategoryRegexp = "@{$category}@";
		$this->savedRedirectRegexp = "@{$redirect}@";
		$this->savedBswRegexp = "@{$bswRegexp}@";
	}

	public function galleryOptions(): array {
		$this->loadSiteData();
		return $this->siteData['galleryoptions'];
	}

	public function allowedExternalImagePrefixes(): array {
		$this->loadSiteData();
		return $this->siteData['externalimages'] ?? [];
	}

	/**
	 * Determine the article base URI and relative prefix
	 */
	private function determineArticlePath(): void {
		$this->loadSiteData();

		$url = $this->siteData['server'] . $this->siteData['articlepath'];

		if ( substr( $url, -2 ) !== '$1' ) {
			throw new \UnexpectedValueException( "Article path '$url' does not have '$1' at the end" );
		}
		$url = substr( $url, 0, -2 );

		$bits = UrlUtils::parseUrl( $url );
		if ( !$bits ) {
			throw new \UnexpectedValueException( "Failed to parse article path '$url'" );
		}

		if ( empty( $bits['path'] ) ) {
			$path = '/';
		} else {
			$path = UrlUtils::removeDotSegments( $bits['path'] );
		}

		$relParts = [ 'query' => true, 'fragment' => true ];
		$base = array_diff_key( $bits, $relParts );
		$rel = array_intersect_key( $bits, $relParts );

		$i = strrpos( $path, '/' );
		$base['path'] = substr( $path, 0, $i + 1 );
		$rel['path'] = '.' . substr( $path, $i );

		$this->baseUri = UrlUtils::assembleUrl( $base );
		$this->relativeLinkPrefix = UrlUtils::assembleUrl( $rel );
	}

	public function baseURI(): string {
		if ( $this->baseUri === null ) {
			$this->determineArticlePath();
		}
		return $this->baseUri;
	}

	public function relativeLinkPrefix(): string {
		if ( $this->relativeLinkPrefix === null ) {
			$this->determineArticlePath();
		}
		return $this->relativeLinkPrefix;
	}

	/** @inheritDoc */
	public function canonicalNamespaceId( string $name ): ?int {
		$this->loadSiteData();
		return $this->nsCanon[Utils::normalizeNamespaceName( $name )] ?? null;
	}

	/** @inheritDoc */
	public function namespaceId( string $name ): ?int {
		$this->loadSiteData();
		$name = Utils::normalizeNamespaceName( $name );
		return $this->nsCanon[$name] ?? $this->nsIds[$name] ?? null;
	}

	/** @inheritDoc */
	public function namespaceName( int $ns ): ?string {
		$this->loadSiteData();
		return $this->nsNames[$ns] ?? null;
	}

	/** @inheritDoc */
	public function namespaceHasSubpages( int $ns ): bool {
		$this->loadSiteData();
		return $this->nsWithSubpages[$ns] ?? false;
	}

	/** @inheritDoc */
	public function namespaceCase( int $ns ): string {
		$this->loadSiteData();
		return $this->nsCase[$ns] ?? 'first-letter';
	}

	/** @inheritDoc */
	public function specialPageLocalName( string $alias ): ?string {
		$this->loadSiteData();
		$alias = strtr( mb_strtoupper( $alias ), ' ', '_' );
		return $this->specialPageNames[$alias] ?? null;
	}

	public function interwikiMagic(): bool {
		$this->loadSiteData();
		return $this->siteData['interwikimagic'];
	}

	public function interwikiMap(): array {
		$this->loadSiteData();
		return $this->interwikiMap;
	}

	public function iwp(): string {
		$this->loadSiteData();
		return $this->siteData['wikiid'];
	}

	public function legalTitleChars(): string {
		$this->loadSiteData();
		return $this->siteData['legaltitlechars'];
	}

	public function linkPrefixRegex(): ?string {
		$this->loadSiteData();

		if ( !empty( $this->siteData['linkprefixcharset'] ) ) {
			return '/[' . $this->siteData['linkprefixcharset'] . ']+$/Du';
		} else {
			// We don't care about super-old MediaWiki, so don't try to parse 'linkprefix'.
			return null;
		}
	}

	/** @inheritDoc */
	protected function linkTrail(): string {
		$this->loadSiteData();
		return $this->siteData['linktrail'];
	}

	public function langBcp47(): Bcp47Code {
		$this->loadSiteData();
		return Utils::mwCodeToBcp47( $this->siteData['lang'] );
	}

	public function mainpage(): string {
		$this->loadSiteData();
		return $this->siteData['mainpage'];
	}

	public function mainPageLinkTarget(): Title {
		$this->loadSiteData();
		return Title::newFromText( $this->siteData['mainpage'], $this );
	}

	/** @inheritDoc */
	public function getMWConfigValue( string $key ) {
		$this->loadSiteData();
		switch ( $key ) {
			// Hardcoded values for these 2 keys
			case 'CiteResponsiveReferences':
				return $this->siteData['citeresponsivereferences'] ?? false;

			case 'CiteResponsiveReferencesThreshold':
				return 10;

			// We can add more hardcoded keys based on testing needs
			// but null is the default for keys unsupported in this mode.
			default:
				return null;
		}
	}

	public function rtl(): bool {
		$this->loadSiteData();
		return $this->siteData['rtl'];
	}

	/** @inheritDoc */
	public function langConverterEnabledBcp47( Bcp47Code $lang ): bool {
		$this->loadSiteData();
		return $this->langConverterEnabled[strtolower( $lang->toBcp47Code() )] ?? false;
	}

	public function script(): string {
		$this->loadSiteData();
		return $this->siteData['script'];
	}

	public function scriptpath(): string {
		$this->loadSiteData();
		return $this->siteData['scriptpath'];
	}

	public function server(): string {
		$this->loadSiteData();
		return $this->siteData['server'];
	}

	/**
	 * @inheritDoc
	 */
	public function exportMetadataToHeadBcp47(
		Document $document,
		ContentMetadataCollector $metadata,
		string $defaultTitle,
		Bcp47Code $lang
	): void {
		'@phan-var StubMetadataCollector $metadata'; // @var StubMetadataCollector $metadata
		$moduleLoadURI = $this->server() . $this->scriptpath() . '/load.php';
		// Parsoid/JS always made this protocol-relative, so match
		// that (for now at least)
		$moduleLoadURI = preg_replace( '#^https?://#', '//', $moduleLoadURI );
		// Look for a displaytitle.
		$displayTitle = $metadata->getPageProperty( 'displaytitle' ) ??
			// Use the default title, properly escaped
			Utils::escapeHtml( $defaultTitle );
		$this->exportMetadataHelper(
			$document,
			$moduleLoadURI,
			$metadata->getModules(),
			$metadata->getModuleStyles(),
			$metadata->getJsConfigVars(),
			$displayTitle,
			$lang
		);
	}

	public function redirectRegexp(): string {
		$this->loadSiteData();
		return $this->savedRedirectRegexp;
	}

	public function categoryRegexp(): string {
		$this->loadSiteData();
		return $this->savedCategoryRegexp;
	}

	public function bswRegexp(): string {
		$this->loadSiteData();
		return $this->savedBswRegexp;
	}

	public function timezoneOffset(): int {
		$this->loadSiteData();
		return $this->siteData['timeoffset'];
	}

	/** @inheritDoc */
	public function variantsFor( Bcp47Code $lang ): ?array {
		$this->loadSiteData();
		return $this->variants[strtolower( $lang->toBcp47Code() )] ?? null;
	}

	public function widthOption(): int {
		$this->loadSiteData();
		return $this->widthOption;
	}

	/** @inheritDoc */
	protected function getVariableIDs(): array {
		$this->loadSiteData();
		return $this->apiVariables;
	}

	/** @inheritDoc */
	protected function haveComputedFunctionSynonyms(): bool {
		return false;
	}

	private static $noHashFunctions = null;

	/** @inheritDoc */
	protected function updateFunctionSynonym( string $func, string $magicword, bool $caseSensitive ): void {
		if ( !$this->apiFunctionHooks ) {
			$this->loadSiteData();
		}
		if ( isset( $this->apiFunctionHooks[$magicword] ) ) {
			if ( !self::$noHashFunctions ) {
				// FIXME: This is an approximation only computed in non-integrated mode for
				// commandline and developer testing. This set is probably not up to date
				// and also doesn't reflect no-hash functions registered by extensions
				// via setFunctionHook calls. As such, you might run into GOTCHAs during
				// debugging of production issues in standalone / API config mode.
				self::$noHashFunctions = PHPUtils::makeSet( [
					'ns', 'nse', 'urlencode', 'lcfirst', 'ucfirst', 'lc', 'uc',
					'localurl', 'localurle', 'fullurl', 'fullurle', 'canonicalurl',
					'canonicalurle', 'formatnum', 'grammar', 'gender', 'plural', 'bidi',
					'numberofpages', 'numberofusers', 'numberofactiveusers',
					'numberofarticles', 'numberoffiles', 'numberofadmins',
					'numberingroup', 'numberofedits', 'language',
					'padleft', 'padright', 'anchorencode', 'defaultsort', 'filepath',
					'pagesincategory', 'pagesize', 'protectionlevel', 'protectionexpiry',
					'namespacee', 'namespacenumber', 'talkspace', 'talkspacee',
					'subjectspace', 'subjectspacee', 'pagename', 'pagenamee',
					'fullpagename', 'fullpagenamee', 'rootpagename', 'rootpagenamee',
					'basepagename', 'basepagenamee', 'subpagename', 'subpagenamee',
					'talkpagename', 'talkpagenamee', 'subjectpagename',
					'subjectpagenamee', 'pageid', 'revisionid', 'revisionday',
					'revisionday2', 'revisionmonth', 'revisionmonth1', 'revisionyear',
					'revisiontimestamp', 'revisionuser', 'cascadingsources',
					// Special callbacks in core
					'namespace', 'int', 'displaytitle', 'pagesinnamespace',
				] );
			}

			$syn = $func;
			if ( substr( $syn, -1 ) === ':' ) {
				$syn = substr( $syn, 0, -1 );
			}
			if ( !isset( self::$noHashFunctions[$magicword] ) ) {
				$syn = '#' . $syn;
			}
			$this->functionSynonyms[intval( $caseSensitive )][$syn] = $magicword;
		}
	}

	/** @inheritDoc */
	protected function getMagicWords(): array {
		$this->loadSiteData();
		return $this->apiMagicWords;
	}

	/** @inheritDoc */
	public function getMagicWordMatcher( string $id ): string {
		$this->loadSiteData();
		return $this->allMWs[$id] ?? '/^(?!)$/';
	}

	/** @inheritDoc */
	public function getParameterizedAliasMatcher( array $words ): callable {
		$this->loadSiteData();
		$regexes = array_intersect_key( $this->paramMWs, array_flip( $words ) );
		return static function ( $text ) use ( $regexes ) {
			/**
			 * $name is the canonical magic word name
			 * $re has patterns for matching aliases
			 */
			foreach ( $regexes as $name => $re ) {
				if ( preg_match( $re, $text, $m ) ) {
					unset( $m[0] );

					// Ex. regexp here is, /^(?:(?:|vinculo\=(.*?)|enlace\=(.*?)|link\=(.*?)))$/uS
					// Check all the capture groups for a value, if not, it's safe to return an
					// empty string since we did get a match.
					foreach ( $m as $v ) {
						if ( $v !== '' ) {
							return [ 'k' => $name, 'v' => $v ];
						}
					}
					return [ 'k' => $name, 'v' => '' ];
				}
			}
			return null;
		};
	}

	/**
	 * This function is public so it can be used to synchronize env for
	 * hybrid parserTests.  The parserTests setup includes the definition
	 * of a number of non-standard extension tags, whose names are passed
	 * over from the JS side in hybrid testing.
	 * @param string $tag Name of an extension tag assumed to be present
	 */
	public function ensureExtensionTag( string $tag ): void {
		$this->loadSiteData();
		$this->extensionTags[mb_strtolower( $tag )] = true;
	}

	/** @inheritDoc */
	protected function getNonNativeExtensionTags(): array {
		$this->loadSiteData();
		return $this->extensionTags;
	}

	/** @inheritDoc */
	public function getMaxTemplateDepth(): int {
		// Not in the API result
		return $this->maxDepth;
	}

	/** @inheritDoc */
	protected function getSpecialNSAliases(): array {
		$nsAliases = [
			'Special',
		];
		foreach ( $this->nsIds as $name => $id ) {
			if ( $id === -1 ) {
				$nsAliases[] = $this->quoteTitleRe( $name, '!' );
			}
		}
		return $nsAliases;
	}

	/** @inheritDoc */
	protected function getSpecialPageAliases( string $specialPage ): array {
		$spAliases = [ $specialPage ];
		foreach ( $this->specialPageAliases as $special ) {
			if ( $special['realname'] === $specialPage ) {
				$spAliases = array_merge( $spAliases, $special['aliases'] );
				break;
			}
		}
		return $spAliases;
	}

	/** @inheritDoc */
	protected function getProtocols(): array {
		$this->loadSiteData();
		return $this->protocols;
	}

	/** @var ?MockMetrics */
	private $metrics;

	/** @inheritDoc */
	public function metrics(): ?StatsdDataFactoryInterface {
		if ( $this->metrics === null ) {
			$this->metrics = new MockMetrics();
		}
		return $this->metrics;
	}

	/** @inheritDoc */
	public function getNoFollowConfig(): array {
		$this->loadSiteData();
		return [
			'nofollow' => $this->siteData['nofollowlinks'] ?? true,
			'nsexceptions' => $this->siteData['nofollownsexceptions'] ?? [],
			'domainexceptions' => $this->siteData['nofollowdomainexceptions'] ?? [ 'mediawiki.org' ]
		];
	}

	/** @inheritDoc */
	public function getExternalLinkTarget() {
		$this->loadSiteData();
		return $this->siteData['externallinktarget'] ?? false;
	}
}
