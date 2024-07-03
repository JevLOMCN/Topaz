<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Mocks;

use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Wikimedia\Bcp47Code\Bcp47Code;
use Wikimedia\Bcp47Code\Bcp47CodeValue;
use Wikimedia\Parsoid\Config\SiteConfig;
use Wikimedia\Parsoid\Config\StubMetadataCollector;
use Wikimedia\Parsoid\Core\ContentMetadataCollector;
use Wikimedia\Parsoid\Core\LinkTarget;
use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\Utils\Title;
use Wikimedia\Parsoid\Utils\Utils;

class MockSiteConfig extends SiteConfig {

	/** @var ?int Unix timestamp */
	private $fakeTimestamp = 946782245; // 2000-01-02T03:04:05Z

	/** @var int */
	private $timezoneOffset = 0; // UTC

	/** @var bool */
	private $interwikiMagic = true;

	/** @var array */
	private $linterOverrides = [];

	private const NAMESPACE_MAP = [
		'media' => -2,
		'special' => -1,
		'' => 0,
		'talk' => 1,
		'user' => 2,
		'user_talk' => 3,
		// Last one will be used by namespaceName
		'project' => 4, 'wp' => 4, 'wikipedia' => 4,
		'project_talk' => 5, 'wt' => 5, 'wikipedia_talk' => 5,
		'file' => 6,
		'file_talk' => 7,
		'help' => 12,
		'category' => 14,
		'category_talk' => 15,
	];

	/** @var array<int,bool> */
	protected $namespacesWithSubpages = [];

	/** @var array */
	protected $interwikiMap = [];

	/** @var int */
	private $maxDepth = 40;

	/** @var string|null */
	private $linkPrefixRegex = null;

	/** @var string|bool */
	private $externalLinkTarget;

	public function __construct( array $opts ) {
		parent::__construct();

		if ( isset( $opts['linting'] ) ) {
			$this->linterEnabled = $opts['linting'];
		}
		if ( isset( $opts['maxDepth'] ) ) {
			$this->maxDepth = $opts['maxDepth'];
		}
		if ( isset( $opts['linterOverrides'] ) ) {
			$this->linterOverrides = $opts['linterOverrides'];
		}
		$this->linkPrefixRegex = $opts['linkPrefixRegex'] ?? null;
		$this->linkTrailRegex = $opts['linkTrailRegex'] ?? '/^([a-z]+)/sD'; // enwiki default
		$this->externalLinkTarget = $opts['externallinktarget'] ?? false;

		// Use Monolog's PHP console handler
		$logger = new Logger( "Parsoid CLI" );
		$handler = new ErrorLogHandler();
		$handler->setFormatter( new LineFormatter( '%message%' ) );
		$logger->pushHandler( $handler );
		$this->setLogger( $logger );
	}

	public function getLinterConfig(): array {
		return $this->linterOverrides + parent::getLinterConfig();
	}

	public function allowedExternalImagePrefixes(): array {
		return [];
	}

	public function baseURI(): string {
		return '//my.wiki.example/wikix/';
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
		return '/(?i:#REDIRECT)/';
	}

	public function categoryRegexp(): string {
		return '/Category/';
	}

	public function bswRegexp(): string {
		return '/' .
				'NOGLOBAL|DISAMBIG|NOCOLLABORATIONHUBTOC|nocollaborationhubtoc|NOTOC|notoc|' .
				'NOGALLERY|nogallery|FORCETOC|forcetoc|TOC|toc|NOEDITSECTION|noeditsection|' .
				'NOTITLECONVERT|notitleconvert|NOTC|notc|NOCONTENTCONVERT|nocontentconvert|' .
				'NOCC|nocc|NEWSECTIONLINK|NONEWSECTIONLINK|HIDDENCAT|INDEX|NOINDEX|STATICREDIRECT' .
			'/';
	}

	/** @inheritDoc */
	public function canonicalNamespaceId( string $name ): ?int {
		return self::NAMESPACE_MAP[$name] ?? null;
	}

	/** @inheritDoc */
	public function namespaceId( string $name ): ?int {
		$name = Utils::normalizeNamespaceName( $name );
		return self::NAMESPACE_MAP[$name] ?? null;
	}

	/** @inheritDoc */
	public function namespaceName( int $ns ): ?string {
		static $map = null;
		if ( $map === null ) {
			$map = array_flip( self::NAMESPACE_MAP );
		}
		if ( !isset( $map[$ns] ) ) {
			return null;
		}
		return ucwords( strtr( $map[$ns], '_', ' ' ) );
	}

	/** @inheritDoc */
	public function namespaceHasSubpages( int $ns ): bool {
		return !empty( $this->namespacesWithSubpages[$ns] );
	}

	/** @inheritDoc */
	public function namespaceCase( int $ns ): string {
		return 'first-letter';
	}

	/** @inheritDoc */
	public function specialPageLocalName( string $alias ): ?string {
		return null;
	}

	public function setInterwikiMagic( bool $val ): void {
		$this->interwikiMagic = $val;
	}

	public function interwikiMagic(): bool {
		return $this->interwikiMagic;
	}

	public function interwikiMap(): array {
		return $this->interwikiMap;
	}

	public function iwp(): string {
		return 'mywiki';
	}

	public function legalTitleChars(): string {
		return ' %!"$&\'()*,\-.\/0-9:;=?@A-Z\\\\^_`a-z~\x80-\xFF+';
	}

	public function linkPrefixRegex(): ?string {
		return $this->linkPrefixRegex;
	}

	protected function linkTrail(): string {
		// @phan-suppress-previous-line PhanPluginNeverReturnMethod
		throw new \BadMethodCallException(
			'Should not be used. linkTrailRegex() is overridden here.' );
	}

	public function linkTrailRegex(): ?string {
		return $this->linkTrailRegex;
	}

	public function langBcp47(): Bcp47Code {
		return new Bcp47CodeValue( 'en' );
	}

	public function mainPageLinkTarget(): LinkTarget {
		return Title::newFromText( 'Main Page', $this );
	}

	/** @inheritDoc */
	public function getMWConfigValue( string $key ) {
		switch ( $key ) {
			case 'CiteResponsiveReferences':
				return true;
			case 'CiteResponsiveReferencesThreshold':
				return 10;
			default:
				return null;
		}
	}

	public function rtl(): bool {
		return false;
	}

	/** @inheritDoc */
	public function langConverterEnabledBcp47( Bcp47Code $lang ): bool {
		return $lang->toBcp47Code() === 'sr';
	}

	public function script(): string {
		return '/wx/index.php';
	}

	public function scriptpath(): string {
		return '/wx';
	}

	public function server(): string {
		return '//my.wiki.example';
	}

	public function timezoneOffset(): int {
		return $this->timezoneOffset;
	}

	/** @inheritDoc */
	public function variantsFor( Bcp47Code $lang ): ?array {
		switch ( $lang->toBcp47Code() ) {
			case 'sr':
				return [
				'base' => new Bcp47CodeValue( 'sr' ),
				'fallbacks' => [
					new Bcp47CodeValue( 'sr-Cyrl' )
				]
			];
			case 'sr-Cyrl':
				return [
				'base' => new Bcp47CodeValue( 'sr' ),
				'fallbacks' => [
					new Bcp47CodeValue( 'sr' )
				]
			];
			case 'sr-Latn':
				return [
				'base' => new Bcp47CodeValue( 'sr' ),
				'fallbacks' => [
					new Bcp47CodeValue( 'sr' )
				]
			];
			default:
				return null;
		}
	}

	public function widthOption(): int {
		return 220;
	}

	/** @inheritDoc */
	protected function getVariableIDs(): array {
		return []; // None for now
	}

	/** @inheritDoc */
	protected function haveComputedFunctionSynonyms(): bool {
		return false;
	}

	/** @inheritDoc */
	protected function updateFunctionSynonym( string $func, string $magicword, bool $caseSensitive ): void {
		/* Nothing for now. Look at src/Config/Api/SiteConfig when mocking is needed. */
	}

	/** @inheritDoc */
	protected function getMagicWords(): array {
		return [
			'toc'             => [ 0, '__TOC__' ],
			'img_thumbnail'   => [ 1, 'thumb' ],
			'img_framed'      => [ 1, 'frame', 'framed' ],
			'img_frameless'   => [ 1, 'frameless' ],
			'img_manualthumb' => [ 1, 'thumbnail=$1', 'thumb=$1' ],
			'img_none'        => [ 1, 'none' ],
			'img_left'        => [ 1, 'left' ],
			'img_right'       => [ 1, 'right' ],
			// T345026: 'sub' should follow 'img_sub' to match dewikivoyage
			'img_sub'         => [ 1, 'sub' ],
			'sub'             => [ 0, 'sub' ],
			'notoc'           => [ 0, '__NOTOC__' ],
			'timedmedia_loop' => [ 0, 'loop' ],
			'timedmedia_muted' => [ 0, 'muted' ],
		];
	}

	/** @inheritDoc */
	public function getMagicWordMatcher( string $id ): string {
		if ( $id === 'toc' ) {
			return '/^TOC$/';
		} else {
			return '/(?!)/';
		}
	}

	/** @inheritDoc */
	public function getParameterizedAliasMatcher( array $words ): callable {
		$paramMWs = [
			'img_lossy' => "/^(?:(?i:lossy\=(.*?)))$/uS",
			'timedmedia_thumbtime' => "/^(?:(?i:thumbtime\=(.*?)))$/uS",
			'timedmedia_starttime' => "/^(?:(?i:start\=(.*?)))$/uS",
			'timedmedia_endtime' => "/^(?:(?i:end\=(.*?)))$/uS",
			'timedmedia_disablecontrols' => "/^(?:(?i:disablecontrols\=(.*?)))$/uS",
			'img_manualthumb' => "/^(?:(?:thumbnail\=(.*?)|thumb\=(.*?)))$/uS",
			'img_width' => "/^(?:(?:(.*?)px))$/uS",
			'img_lang' => "/^(?:(?:lang\=(.*?)))$/uS",
			'img_page' => "/^(?:(?:page\=(.*?)|page (.*?)))$/uS",
			'img_upright' => "/^(?:(?:upright\=(.*?)|upright (.*?)))$/uS",
			'img_link' => "/^(?:(?:link\=(.*?)))$/uS",
			'img_alt' => "/^(?:(?:alt\=(.*?)))$/uS",
			'img_class' => "/^(?:(?:class\=(.*?)))$/uS"
		];
		$regexes = array_intersect_key( $paramMWs, array_flip( $words ) );
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

	/** @inheritDoc */
	protected function getNonNativeExtensionTags(): array {
		return [
			'indicator' => true,
			'timeline' => true,
			'hiero' => true,
			'charinsert' => true,
			'inputbox' => true,
			'source' => true,
			'syntaxhighlight' => true,
			'section' => true,
			'score' => true,
			'templatedata' => true,
			'math' => true,
			'ce' => true,
			'chem' => true,
			'graph' => true,
			'maplink' => true,
			'categorytree' => true,
			'templatestyles' => true
		];
	}

	/** @inheritDoc */
	public function getMaxTemplateDepth(): int {
		return $this->maxDepth;
	}

	/** @inheritDoc */
	protected function getSpecialPageAliases( string $specialPage ): array {
		if ( $specialPage === 'Booksources' ) {
			return [ 'Booksources', 'BookSources' ]; // Mock value
		} else {
			throw new \BadMethodCallException( 'Not implemented' );
		}
	}

	/** @inheritDoc */
	protected function getSpecialNSAliases(): array {
		return [ "Special", "special" ]; // Mock value
	}

	/** @inheritDoc */
	protected function getProtocols(): array {
		return [ "http:", "https:", "irc:", "ircs:", "news:", "ftp:", "mailto:", "gopher:", "//" ];
	}

	public function fakeTimestamp(): ?int {
		return $this->fakeTimestamp;
	}

	/**
	 * Set the fake timestamp for testing
	 * @param ?int $ts Unix timestamp
	 */
	public function setFakeTimestamp( ?int $ts ): void {
		$this->fakeTimestamp = $ts;
	}

	/**
	 * Set the timezone offset for testing
	 * @param int $offset Offset from UTC
	 */
	public function setTimezoneOffset( int $offset ): void {
		$this->timezoneOffset = $offset;
	}

	public function scrubBidiChars(): bool {
		return true;
	}

	/** @inheritDoc */
	public function getNoFollowConfig(): array {
		return [
			'nofollow' => true,
			'nsexceptions' => [ 1 ],
			'domainexceptions' => [ 'www.example.com' ]
		];
	}

	/** @inheritDoc */
	public function getExternalLinkTarget() {
		return $this->externalLinkTarget;
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
}
