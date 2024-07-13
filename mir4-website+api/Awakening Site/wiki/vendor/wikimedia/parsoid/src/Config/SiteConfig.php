<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Config;

use Liuggio\StatsdClient\Factory\StatsdDataFactoryInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Wikimedia\Assert\Assert;
use Wikimedia\Bcp47Code\Bcp47Code;
use Wikimedia\ObjectFactory\ObjectFactory;
use Wikimedia\Parsoid\Core\ContentMetadataCollector;
use Wikimedia\Parsoid\Core\ContentModelHandler;
use Wikimedia\Parsoid\Core\LinkTarget;
use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\Ext\AnnotationStripper;
use Wikimedia\Parsoid\Ext\ExtensionModule;
use Wikimedia\Parsoid\Ext\ExtensionTagHandler;
use Wikimedia\Parsoid\Ext\Gallery\Gallery;
use Wikimedia\Parsoid\Ext\Indicator\Indicator;
use Wikimedia\Parsoid\Ext\JSON\JSON;
use Wikimedia\Parsoid\Ext\LST\LST;
use Wikimedia\Parsoid\Ext\Nowiki\Nowiki;
use Wikimedia\Parsoid\Ext\Pre\Pre;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\Utils;
use Wikimedia\Parsoid\Wikitext\Consts;

/**
 * Site-level configuration interface for Parsoid
 *
 * This includes both global configuration and wiki-level configuration.
 */
abstract class SiteConfig {
	/**
	 * FIXME: not private so that ParserTests can reset these variables
	 * since they reuse site config and other objects between tests for
	 * efficiency reasons.
	 *
	 * @var array|null
	 */
	protected $mwAliases;

	/** @var array|null */
	private $behaviorSwitches;

	/** @var array|null */
	private $variables;

	/** @var array|null */
	private $mediaOptions;

	/** @var array|null */
	protected $functionSynonyms;

	/** @var string[] */
	private $protocolsRegexes = [];

	/**
	 * FIXME: not private so that ParserTests can reset these variables
	 * since they reuse site config and other objects between tests for
	 * efficiency reasons.
	 * @var array|null
	 */
	protected $interwikiMapNoNamespaces;

	/**
	 * FIXME: not private so that ParserTests can reset these variables
	 * since they reuse site config and other objects between tests for
	 * efficiency reasons.
	 * @var string|null|bool
	 */
	protected $linkTrailRegex = false;

	/**
	 * These extension modules provide "core" functionality
	 * and their implementations live in the Parsoid repo.
	 *
	 * @var class-string<ExtensionModule>[]
	 */
	private static $coreExtModules = [
		// content modules
		JSON::class,
		// extension tags
		Nowiki::class,
		Pre::class,
		Gallery::class,
		Indicator::class,
		// The following implementations will move to their own repositories
		// soon, but for now are implemented in the Parsoid repo.
		LST::class
	];

	/**
	 * Array specifying fully qualified class name for Parsoid-compatible extensions
	 * @var ?array<int,ExtensionModule>
	 */
	private $extModules = null;
	/**
	 * Private counter to assign IDs to $extModules
	 * @var int
	 */
	private $extModuleNextId = 0;

	// phpcs:disable Generic.Files.LineLength.TooLong

	/**
	 * Register a Parsoid extension module.
	 * @param string|array{name:string}|array{factory:callable}|array{class:class-string<ExtensionModule>} $configOrSpec
	 *  Either an object factory specification for an ExtensionModule object,
	 *  or else the configuration array which ExtensionModule::getConfig()
	 *  would return.  (The latter is preferred, but our internal extensions
	 *  use the former.)
	 * @return int An integer identifier that can be passed to
	 *  ::unregisterExtensionModule to remove this extension (
	 */
	final public function registerExtensionModule( $configOrSpec ): int {
		$this->getExtensionModules(); // ensure it's initialized w/ core modules
		if ( is_string( $configOrSpec ) || isset( $configOrSpec['class'] ) || isset( $configOrSpec['factory'] ) ) {
			// Treat this as an object factory spec for an ExtensionModule
			// ObjectFactory::createObject accepts an array, not just a callable (phan bug)
			// @phan-suppress-next-line PhanTypeInvalidCallableArraySize
			$module = $this->getObjectFactory()->createObject( $configOrSpec, [
				'allowClassName' => true,
				'assertClass' => ExtensionModule::class,
			] );
		} else {
			// Treat this as a configuration array, create a new anonymous
			// ExtensionModule object for it.
			$module = new class( $configOrSpec ) implements ExtensionModule {
				private $config;

				/** @param array $config */
				public function __construct( $config ) {
					$this->config = $config;
				}

				/** @inheritDoc */
				public function getConfig(): array {
					return $this->config;
				}
			};
		}
		$extId = $this->extModuleNextId++;
		$this->extModules[$extId] = $module;
		// remove cached extConfig to ensure this registration is picked up
		$this->extConfig = null;
		return $extId;
	}

	// phpcs:enable Generic.Files.LineLength.TooLong

	/**
	 * Unregister a Parsoid extension module.  This is typically used
	 * only for testing purposes in order to reset a shared SiteConfig
	 * to its original configuration.
	 * @param int $extId The value returned by the call to
	 *   ::registerExtensionModule()
	 */
	final public function unregisterExtensionModule( int $extId ): void {
		unset( $this->extModules[$extId] );
		$this->extConfig = null; // remove cached extConfig
	}

	/**
	 * Return the set of Parsoid extension modules associated with this
	 * SiteConfig.
	 *
	 * @return ExtensionModule[]
	 */
	final public function getExtensionModules() {
		if ( $this->extModules === null ) {
			$this->extModules = [];
			foreach ( self::$coreExtModules as $m ) {
				$this->extModules[$this->extModuleNextId++] = new $m();
			}
		}
		return array_values( $this->extModules );
	}

	/** @var LoggerInterface|null */
	protected $logger = null;

	/** @var int */
	protected $iwMatcherBatchSize = 4096;

	/** @var array|null */
	private $iwMatcher = null;

	/** @var bool */
	protected $addHTMLTemplateParameters = false;

	/** @var bool */
	protected $scrubBidiChars = false;

	/** @var bool */
	protected $linterEnabled = false;

	/** @var ?array */
	protected $extConfig = null;

	/**
	 * Tag handlers for some extensions currently explicit call unstripNowiki
	 * first thing in their handlers. They do this to strip <nowiki>..</nowiki>
	 * wrappers around args when encountered in the {{#tag:...}} parser function.
	 * However, this strategy won't work for Parsoid which calls the preprocessor
	 * to get expanded wikitext. In this mode, <nowiki> wrappers won't be stripped
	 * and this leads to functional differences in parsing and output.
	 *
	 * See T203293 and T299103 for more details.
	 *
	 * To get around this, T299103 proposes that extensions that require this support
	 * set a config flag in their Parsoid extension config. On the Parsoid end, we
	 * then let the legacy parser know of these tags. When such extension tags are
	 * encountered in the {{#tag:...}} parser function handler (see tagObj function
	 * in CoreParserFunctions.php), that handler can than automatically strip these
	 * nowiki wrappers on behalf of the extension.
	 *
	 * This serves two purposes. For one, it lets Parsoid support these extensions
	 * in this nowiki use edge case. For another, extensions that register handlers
	 * with Parsoid can get rid of explicit calls to unstripNowiki() in the
	 * tag handlers for the legacy parser.
	 *
	 * This property maintains an array of tags that need this support.
	 *
	 * @var array an associative array of tag names
	 */
	private $t299103Tags = [];

	/**
	 * Base constructor.
	 *
	 * This constructor is public because it is used to create mock objects
	 * in our test suite.
	 */
	public function __construct() {
	}

	/************************************************************************//**
	 * @name   Global config
	 * @{
	 */

	/**
	 * General log channel
	 * @return LoggerInterface
	 */
	public function getLogger(): LoggerInterface {
		if ( $this->logger === null ) {
			$this->logger = new NullLogger;
		}
		return $this->logger;
	}

	/**
	 * Set the log channel, for debugging
	 * @param ?LoggerInterface $logger
	 */
	public function setLogger( ?LoggerInterface $logger ): void {
		$this->logger = $logger;
	}

	/**
	 * Default gallery options for this wiki.
	 * @return array<string,string|int|bool>
	 */
	public function galleryOptions(): array {
		return [
			'imagesPerRow' => 0,
			'imageWidth' => 120,
			'imageHeight' => 120,
			'captionLength' => true,
			'showBytes' => true,
			'showDimensions' => true,
			'mode' => 'traditional',
		];
	}

	/**
	 * When processing template parameters, parse them to HTML and add it to the
	 * template parameters data.
	 * @return bool
	 */
	public function addHTMLTemplateParameters(): bool {
		return $this->addHTMLTemplateParameters;
	}

	/**
	 * Whether to enable linter Backend.
	 * Consults the allow list and block list from ::getLinterConfig().
	 *
	 * @param null $type If $type is null or omitted, returns true if *any* linting
	 *   type is enabled; otherwise returns true only if the specified
	 *   linting type is enabled.
	 * @return bool If $type is null or omitted, returns true if *any* linting
	 *   type is enabled; otherwise returns true only if the specified
	 *   linting type is enabled.
	 */
	final public function linting( ?string $type = null ) {
		if ( !$this->linterEnabled ) {
			return false;
		}
		$lintConfig = $this->getLinterConfig();
		// Allow list
		$allowList = $lintConfig['enabled'] ?? null;
		if ( is_array( $allowList ) ) {
			if ( $type === null ) {
				return count( $allowList ) > 0;
			}
			return $allowList[$type] ?? false;
		}
		// Block list
		if ( $type === null ) {
			return true;
		}
		$blockList = $lintConfig['disabled'] ?? null;
		if ( is_array( $blockList ) ) {
			return !( $blockList[$type] ?? false );
		}
		// No specific configuration
		return true;
	}

	/**
	 * Statistics aggregator, for counting and timing.
	 *
	 * @return StatsdDataFactoryInterface|null
	 */
	public function metrics(): ?StatsdDataFactoryInterface {
		return null;
	}

	/**
	 * If enabled, bidi chars adjacent to category links will be stripped
	 * in the html -> wt serialization pass.
	 * @return bool
	 */
	public function scrubBidiChars(): bool {
		return $this->scrubBidiChars;
	}

	/** @} */

	/************************************************************************//**
	 * @name   Wiki config
	 * @{
	 */

	/**
	 * Allowed external image URL prefixes.
	 *
	 * @return string[] The empty array matches no URLs. The empty string matches
	 *  all URLs.
	 */
	abstract public function allowedExternalImagePrefixes(): array;

	/**
	 * Site base URI
	 *
	 * This would be the URI found in `<base href="..." />`.
	 *
	 * @return string
	 */
	abstract public function baseURI(): string;

	/**
	 * Prefix for relative links
	 *
	 * Prefix to prepend to a page title to link to that page.
	 * Intended to be relative to the URI returned by baseURI().
	 *
	 * If possible, keep the default "./" so clients need not know this value
	 * to extract titles from link hrefs.
	 *
	 * @return string
	 */
	public function relativeLinkPrefix(): string {
		return './';
	}

	/**
	 * Regex matching all double-underscore magic words
	 * @return string
	 */
	public function bswPagePropRegexp(): string {
		static $bswPagePropRegexp = null;
		if ( $bswPagePropRegexp === null ) {
			$bswRegexp = $this->bswRegexp();
			$bswPagePropRegexp =
				'@(?:^|\\s)mw:PageProp/(?:' .
				PHPUtils::reStrip( $bswRegexp, '@' ) .
				')(?=$|\\s)@uDS';
		}
		return $bswPagePropRegexp;
	}

	/**
	 * Map a canonical namespace name to its index
	 *
	 * @note This replaces canonicalNamespaces
	 * @param string $name all-lowercase and with underscores rather than spaces.
	 * @return int|null
	 */
	abstract public function canonicalNamespaceId( string $name ): ?int;

	/**
	 * Map a namespace name to its index
	 *
	 * @note This replaces canonicalNamespaces
	 * @param string $name
	 * @return int|null
	 */
	abstract public function namespaceId( string $name ): ?int;

	/**
	 * Map a namespace index to its preferred name
	 * (with spaces, not underscores).
	 *
	 * @note This replaces namespaceNames
	 * @param int $ns
	 * @return string|null
	 */
	abstract public function namespaceName( int $ns ): ?string;

	/**
	 * Test if a namespace has subpages
	 *
	 * @note This replaces namespacesWithSubpages
	 * @param int $ns
	 * @return bool
	 */
	abstract public function namespaceHasSubpages( int $ns ): bool;

	/**
	 * Return namespace case setting
	 * @param int $ns
	 * @return string 'first-letter' or 'case-sensitive'
	 */
	abstract public function namespaceCase( int $ns ): string;

	/**
	 * Test if a namespace is a talk namespace
	 *
	 * @note This replaces title.getNamespace().isATalkNamespace()
	 * @param int $ns
	 * @return bool
	 */
	public function namespaceIsTalk( int $ns ): bool {
		return $ns > 0 && $ns % 2;
	}

	/**
	 * Uppercasing method for titles
	 * @param string $str
	 * @return string
	 */
	public function ucfirst( string $str ): string {
		$o = ord( $str );
		if ( $o < 96 ) { // if already uppercase...
			return $str;
		} elseif ( $o < 128 ) {
			if ( $str[0] === 'i' &&
				 in_array( $this->langBcp47()->toBcp47Code(), [ 'az', 'tr', 'kaa', 'kk' ], true )
			) {
				return 'İ' . mb_substr( $str, 1 );
			}
			return ucfirst( $str ); // use PHP's ucfirst()
		} else {
			// fall back to more complex logic in case of multibyte strings
			$char = mb_substr( $str, 0, 1 );
			return mb_strtoupper( $char ) . mb_substr( $str, 1 );
		}
	}

	/**
	 * Get the default local name for a special page
	 * @param string $alias Special page alias
	 * @return string|null
	 */
	abstract public function specialPageLocalName( string $alias ): ?string;

	/**
	 * Treat language links as magic connectors, not inline links
	 * @return bool
	 */
	abstract public function interwikiMagic(): bool;

	/**
	 * Interwiki link data
	 * @return array<string,array> Keys are interwiki prefixes, values are arrays with the following keys:
	 *   - prefix: (string) The interwiki prefix, same as the key.
	 *   - url: (string) Target URL, containing a '$1' to be replaced by the interwiki target.
	 *   - protorel: (bool, optional) Whether the url may be accessed by both http:// and https://.
	 *   - local: (bool, optional) Whether the interwiki link is considered local (to the wikifarm).
	 *   - localinterwiki: (bool, optional) Whether the interwiki link points to the current wiki.
	 *   - language: (bool, optional) Whether the interwiki link is a language link.
	 *   - extralanglink: (bool, optional) Whether the interwiki link is an "extra language link".
	 *   - linktext: (string, optional) For "extra language links", the link text.
	 *  (booleans marked "optional" must be omitted if false)
	 */
	abstract public function interwikiMap(): array;

	/**
	 * Interwiki link data, after removing items that conflict with namespace names.
	 * (In case of such conflict, namespace wins, interwiki is ignored.)
	 * @return array<string,array> See interwikiMap()
	 */
	public function interwikiMapNoNamespaces(): array {
		if ( $this->interwikiMapNoNamespaces === null ) {
			$this->interwikiMapNoNamespaces = [];
			foreach ( $this->interwikiMap() as $key => $value ) {
				if ( $this->namespaceId( $key ) === null ) {
					$this->interwikiMapNoNamespaces[$key] = $value;
				}
			}
		}
		return $this->interwikiMapNoNamespaces;
	}

	/**
	 * Match interwiki URLs
	 * @param string $href Link to match against
	 * @return string[]|null Two values [ string $key, string $target ] on success, null on no match.
	 */
	public function interwikiMatcher( string $href ): ?array {
		if ( $this->iwMatcher === null ) {
			$keys = [ [], [] ];
			$patterns = [ [], [] ];
			foreach ( $this->interwikiMapNoNamespaces() as $key => $iw ) {
				$lang = (int)( !empty( $iw['language'] ) );

				$url = $iw['url'];
				$protocolRelative = substr( $url, 0, 2 ) === '//';
				if ( !empty( $iw['protorel'] ) ) {
					$url = preg_replace( '/^https?:/', '', $url );
					$protocolRelative = true;
				}

				// full-url match pattern
				$keys[$lang][] = $key;
				$patterns[$lang][] =
					// Support protocol-relative URLs
					( $protocolRelative ? '(?:https?:)?' : '' )
					// Convert placeholder to group match
					. strtr( preg_quote( $url, '/' ), [ '\\$1' => '(.*?)' ] );

				if ( !empty( $iw['local'] ) ) {
					// ./$interwikiPrefix:$title and
					// $interwikiPrefix%3A$title shortcuts
					// are recognized and the local wiki forwards
					// these shortcuts to the remote wiki

					$keys[$lang][] = $key;
					$patterns[$lang][] = '^\\.\\/' . $iw['prefix'] . ':(.*?)';

					$keys[$lang][] = $key;
					$patterns[$lang][] = '^' . $iw['prefix'] . '%3A(.*?)';
				}
			}

			// Prefer language matches over non-language matches
			$numLangs = count( $keys[1] );
			$keys = array_merge( $keys[1], $keys[0] );
			$patterns = array_merge( $patterns[1], $patterns[0] );

			// Chunk patterns into reasonably sized regexes
			$this->iwMatcher = [];
			$batchStart = 0;
			$batchLen = 0;
			foreach ( $patterns as $i => $pat ) {
				$len = strlen( $pat );
				if ( $i !== $batchStart && $batchLen + $len > $this->iwMatcherBatchSize ) {
					$this->iwMatcher[] = [
						array_slice( $keys, $batchStart, $i - $batchStart ),
						'/^(?:' . implode( '|', array_slice( $patterns, $batchStart, $i - $batchStart ) ) . ')$/Di',
						$numLangs - $batchStart,
					];
					$batchStart = $i;
					$batchLen = $len;
				} else {
					$batchLen += $len;
				}
			}
			$i = count( $patterns );
			if ( $i > $batchStart ) {
				$this->iwMatcher[] = [
					array_slice( $keys, $batchStart, $i - $batchStart ),
					'/^(?:' . implode( '|', array_slice( $patterns, $batchStart, $i - $batchStart ) ) . ')$/Di',
					$numLangs - $batchStart,
				];
			}
		}

		foreach ( $this->iwMatcher as [ $keys, $regex, $numLangs ] ) {
			if ( preg_match( $regex, $href, $m, PREG_UNMATCHED_AS_NULL ) ) {
				foreach ( $keys as $i => $key ) {
					if ( isset( $m[$i + 1] ) ) {
						if ( $i < $numLangs ) {
							// Escape language interwikis with a colon
							$key = ':' . $key;
						}
						return [ $key, $m[$i + 1] ];
					}
				}
			}
		}
		return null;
	}

	/**
	 * Wiki identifier, for cache keys.
	 * Should match a key in mwApiMap()?
	 * @return string
	 */
	abstract public function iwp(): string;

	/**
	 * Legal title characters
	 *
	 * Regex is intended to match bytes, not Unicode characters.
	 *
	 * @return string Regex character class (i.e. the bit that goes inside `[]`)
	 */
	abstract public function legalTitleChars(): string;

	/**
	 * Link prefix regular expression.
	 * @return string|null
	 */
	abstract public function linkPrefixRegex(): ?string;

	/**
	 * Return raw link trail regexp from config
	 * @return string
	 */
	abstract protected function linkTrail(): string;

	/**
	 * Link trail regular expression.
	 * @return string|null
	 */
	public function linkTrailRegex(): ?string {
		if ( $this->linkTrailRegex === false ) {
			$trail = $this->linkTrail();
			$trail = str_replace( '(.*)$', '', $trail );
			if ( strpos( $trail, '()' ) !== false ) {
				// Empty regex from zh-hans
				$this->linkTrailRegex = null;
			} else {
				$this->linkTrailRegex = $trail;
			}
		}
		return $this->linkTrailRegex;
	}

	/**
	 * Wiki language code.
	 * @return Bcp47Code BCP-47 language code
	 */
	abstract public function langBcp47(): Bcp47Code;

	/**
	 * Main page title, as LinkTarget
	 * @return LinkTarget
	 */
	abstract public function mainPageLinkTarget(): LinkTarget;

	/**
	 * Lookup config
	 * @param string $key
	 * @return mixed|null config value for $key, if present or null, if not.
	 */
	abstract public function getMWConfigValue( string $key );

	/**
	 * Whether the wiki language is right-to-left
	 * @return bool
	 */
	abstract public function rtl(): bool;

	/**
	 * Whether language converter is enabled for the specified language
	 * @param Bcp47Code $lang
	 * @return bool
	 */
	abstract public function langConverterEnabledBcp47( Bcp47Code $lang ): bool;

	/**
	 * The URL path to index.php.
	 * @return string
	 */
	abstract public function script(): string;

	/**
	 * FIXME: This is only used to compute the modules path below
	 * and maybe shouldn't be exposed.
	 *
	 * The base wiki path
	 * @return string
	 */
	abstract public function scriptpath(): string;

	/**
	 * The base URL of the server.
	 * @return string
	 */
	abstract public function server(): string;

	/**
	 * Export content metadata via meta tags (and via a stylesheet
	 * for now to aid some clients).
	 *
	 * @param Document $document
	 * @param ContentMetadataCollector $metadata
	 * @param string $defaultTitle The default title to display, as an
	 *   unescaped string
	 * @param Bcp47Code $lang a BCP-47 language code
	 */
	abstract public function exportMetadataToHeadBcp47(
		Document $document,
		ContentMetadataCollector $metadata,
		string $defaultTitle,
		Bcp47Code $lang
	): void;

	/**
	 * Helper function to create <head> elements from metadata.
	 * @param Document $document
	 * @param string $modulesLoadURI
	 * @param string[] $modules
	 * @param string[] $moduleStyles
	 * @param array<string,mixed> $jsConfigVars
	 * @param string $htmlTitle The display title, as escaped HTML
	 * @param string|Bcp47Code $lang a MediaWiki-internal language code string,
	 *   or a Bcp47Code object (latter is preferred)
	 */
	protected function exportMetadataHelper(
		Document $document,
		string $modulesLoadURI,
		array $modules,
		array $moduleStyles,
		array $jsConfigVars,
		string $htmlTitle,
		$lang
	): void {
		$lang = Utils::mwCodeToBcp47( $lang, true, $this->getLogger() );
		// Display title
		$titleElement = DOMCompat::querySelector( $document, 'title' );
		if ( !$titleElement ) {
			$titleElement = DOMUtils::appendToHead( $document, 'title' );
		}
		DOMCompat::setInnerHTML( $titleElement, $htmlTitle );
		// JsConfigVars
		$content = null;
		try {
			if ( $jsConfigVars ) {
				$content = PHPUtils::jsonEncode( $jsConfigVars );
			}
		} catch ( \Exception $e ) {
			// Similar to ResourceLoader::makeConfigSetScript.  See T289358
			$this->getLogger()->log(
				LogLevel::WARNING,
				'JSON serialization of config data failed. ' .
				'This usually means the config data is not valid UTF-8.'
			);
		}
		if ( $content ) {
			DOMUtils::appendToHead( $document, 'meta', [
				'property' => 'mw:jsConfigVars',
				'content' => $content,
			] );
		}
		// Styles from modules returned from preprocessor / parse requests
		if ( $modules ) {
			// mw:generalModules can be processed via JS (and async) and are usually (but
			// not always) JS scripts.
			DOMUtils::appendToHead( $document, 'meta', [
				'property' => 'mw:generalModules',
				'content' => implode( '|', array_unique( $modules ) )
			] );
		}
		// Styles from modules returned from preprocessor / parse requests
		if ( $moduleStyles ) {
			// mw:moduleStyles are CSS modules that are render-blocking.
			DOMUtils::appendToHead( $document, 'meta', [
				'property' => 'mw:moduleStyles',
				'content' => implode( '|', array_unique( $moduleStyles ) )
			] );
		}
		/*
		* While unnecessary for Wikimedia clients, a stylesheet url in
		* the <head> is useful for clients like Kiwix and others who
		* might not want to process the meta tags to construct the
		* resourceloader url.
		*
		* Given that these clients will be consuming Parsoid HTML outside
		* a MediaWiki skin, the clients are effectively responsible for
		* their own "skin". But, once again, as a courtesy, we are
		* hardcoding the vector skin modules for them. But, note that
		* this may cause page elements to render differently than how
		* they render on Wikimedia sites with the vector skin since this
		* is probably missing a number of other modules.
		*
		* All that said, note that JS-generated parts of the page will
		* still require them to have more intimate knowledge of how to
		* process the JS modules. Except for <graph>s, page content
		* doesn't require JS modules at this point. So, where these
		* clients want to invest in the necessary logic to construct a
		* better resourceloader url, they could simply delete / ignore
		* this stylesheet.
		*/
		$moreStyles = array_merge( $moduleStyles, [
			'mediawiki.skinning.content.parsoid',
			// Use the base styles that API output and fallback skin use.
			'mediawiki.skinning.interface',
			// Make sure to include contents of user generated styles
			// e.g. MediaWiki:Common.css / MediaWiki:Mobile.css
			'site.styles'
		] );
		# need to use MW-internal language code for constructing resource
		# loader path.
		$langMw = Utils::bcp47ToMwCode( $lang );
		$styleURI = $modulesLoadURI . '?lang=' . $langMw . '&modules=' .
			PHPUtils::encodeURIComponent( implode( '|', array_unique( $moreStyles ) ) ) .
			'&only=styles&skin=vector';
		DOMUtils::appendToHead( $document, 'link', [ 'rel' => 'stylesheet', 'href' => $styleURI ] );
	}

	/**
	 * A regexp matching the localized 'REDIRECT' marker for this wiki.
	 * The regexp should be delimited, but should not have boundary anchors
	 * or capture groups.
	 * @return string
	 */
	abstract public function redirectRegexp(): string;

	/**
	 * A regexp matching the localized 'Category' prefix for this wiki.
	 * The regexp should be delimited, but should not have boundary anchors
	 * or capture groups.
	 * @return string
	 */
	abstract public function categoryRegexp(): string;

	/**
	 * A regexp matching localized behavior switches for this wiki.
	 * The regexp should be delimited, but should not have boundary anchors
	 * or capture groups.
	 * @return string
	 */
	abstract public function bswRegexp(): string;

	/**
	 * A regex matching a line containing just whitespace, comments, and
	 * sol transparent links and behavior switches.
	 * @return string
	 */
	public function solTransparentWikitextRegexp(): string {
		// cscott sadly says: Note that this depends on the precise
		// localization of the magic words of this particular wiki.
		static $solTransparentWikitextRegexp = null;
		if ( $solTransparentWikitextRegexp === null ) {
			$redirect = PHPUtils::reStrip( $this->redirectRegexp(), '@' );
			$category = PHPUtils::reStrip( $this->categoryRegexp(), '@' );
			$bswRegexp = PHPUtils::reStrip( $this->bswRegexp(), '@' );
			$comment = PHPUtils::reStrip( Utils::COMMENT_REGEXP, '@' );
			$solTransparentWikitextRegexp = '@' .
				'^[ \t\n\r\0\x0b]*' .
				'(?:' .
				'(?:' . $redirect . ')' .
				'[ \t\n\r\x0c]*(?::[ \t\n\r\x0c]*)?\[\[[^\]]+\]\]' .
				')?' .
				'(?:' .
				'\[\[' . $category . '\:[^\]]*?\]\]|' .
				'__(?:' . $bswRegexp . ')__|' .
				$comment . '|' .
				'[ \t\n\r\0\x0b]' .
				')*$@';
		}
		return $solTransparentWikitextRegexp;
	}

	/**
	 * A regex matching a line containing just comments and
	 * sol transparent links and behavior switches.
	 *
	 * @param bool $addIncludes
	 * @return string
	 */
	public function solTransparentWikitextNoWsRegexp(
		bool $addIncludes = false
	): string {
		// cscott sadly says: Note that this depends on the precise
		// localization of the magic words of this particular wiki.
		static $solTransparentWikitextNoWsRegexp = null;
		if ( $solTransparentWikitextNoWsRegexp === null ) {
			$redirect = PHPUtils::reStrip( $this->redirectRegexp(), '@' );
			$category = PHPUtils::reStrip( $this->categoryRegexp(), '@' );
			$bswRegexp = PHPUtils::reStrip( $this->bswRegexp(), '@' );
			$comment = PHPUtils::reStrip( Utils::COMMENT_REGEXP, '@' );
			$solTransparentWikitextNoWsRegexp = '@' .
				'((?:' .
				  '(?:' . $redirect . ')' .
				  '[ \t\n\r\x0c]*(?::[ \t\n\r\x0c]*)?\[\[[^\]]+\]\]' .
				')?' .
				'(?:' .
				'\[\[' . $category . '\:[^\]]*?\]\]|' .
				'__(?:' . $bswRegexp . ')__|' .
				$comment .
				// FIXME(SSS): What about onlyinclude and noinclude?
				( $addIncludes ? '|<includeonly>[\S\s]*?</includeonly>' : '' ) .
				')*)@';
		}
		return $solTransparentWikitextNoWsRegexp;
	}

	/**
	 * The wiki's time zone offset
	 * @return int Minutes east of UTC
	 */
	abstract public function timezoneOffset(): int;

	/**
	 * Language variant information for the given language (or null if
	 * unknown).
	 * @param Bcp47Code $lang The language for which you want variant information
	 * @return ?array{base:Bcp47Code,fallbacks:Bcp47Code[]} an array with
	 * two fields:
	 *   - base: (Bcp47Code) Base BCP-47 language code (e.g. "zh")
	 *   - fallbacks: (Bcp47Code[]) Fallback variants, as BCP-47 codes
	 */
	abstract public function variantsFor( Bcp47Code $lang ): ?array;

	/**
	 * Default thumbnail width
	 */
	abstract public function widthOption(): int;

	abstract protected function getVariableIDs(): array;

	abstract protected function getMagicWords(): array;

	/**
	 * Does the SiteConfig provide precomputed function synonyms?
	 * If no, the SiteConfig is expected to provide an implementation
	 * for updateFunctionSynonym.
	 */
	protected function haveComputedFunctionSynonyms(): bool {
		return true;
	}

	/**
	 * Get a list of precomputed function synonyms
	 */
	protected function getFunctionSynonyms(): array {
		return [];
	}

	protected function updateFunctionSynonym( string $func, string $magicword, bool $caseSensitive ): void {
		throw new \RuntimeException( "Unexpected code path!" );
	}

	private function populateMagicWords() {
		if ( !empty( $this->mwAliases ) ) {
			return;
		}

		$this->mwAliases = $this->behaviorSwitches = $this->variables = $this->mediaOptions = [];
		$variablesMap = PHPUtils::makeSet( $this->getVariableIDs() );
		$this->functionSynonyms = $this->getFunctionSynonyms();
		$haveSynonyms = $this->haveComputedFunctionSynonyms();
		foreach ( $this->getMagicWords() as $magicword => $aliases ) {
			$caseSensitive = array_shift( $aliases );
			$isVariable = isset( $variablesMap[$magicword] );
			$isMediaOption = preg_match( '/^(img|timedmedia)_/', $magicword );
			foreach ( $aliases as $alias ) {
				$this->mwAliases[$magicword][] = $alias;
				if ( !$caseSensitive ) {
					$alias = mb_strtolower( $alias );
					$this->mwAliases[$magicword][] = $alias;
				}
				if ( substr( $alias, 0, 2 ) === '__' ) {
					$this->behaviorSwitches[$alias] = [ $caseSensitive, $magicword ];
				}
				if ( $isVariable ) {
					$this->variables[$alias] = $magicword;
				}
				if ( $isMediaOption ) {
					$this->mediaOptions[$alias] = [ $caseSensitive, $magicword ];
				}
				if ( !$haveSynonyms ) {
					$this->updateFunctionSynonym( $alias, $magicword, (bool)$caseSensitive );
				}
			}
		}
	}

	/**
	 * List all magic words by canonical name
	 * @return string[][] Keys are canonical names, values are arrays of aliases.
	 */
	public function mwAliases(): array {
		$this->populateMagicWords();
		return $this->mwAliases;
	}

	/**
	 * Return canonical magic word for a function hook
	 * @param string $str
	 * @return string|null
	 */
	public function getMagicWordForFunctionHook( string $str ): ?string {
		$this->populateMagicWords();
		return $this->functionSynonyms[1][$str] ??
			# Case insensitive functions
			$this->functionSynonyms[0][mb_strtolower( $str )] ?? null;
	}

	/**
	 * Return canonical magic word for a variable
	 * @param string $str
	 * @return string|null
	 */
	public function getMagicWordForVariable( string $str ): ?string {
		$this->populateMagicWords();
		return $this->variables[$str] ?? null;
	}

	private static function getMagicWordCanonicalName( array $mws, string $word ): ?string {
		if ( isset( $mws[$word] ) ) {
			return $mws[$word][1];
		}
		$mw = $mws[mb_strtolower( $word )] ?? null;
		return ( $mw && !$mw[0] ) ? $mw[1] : null;
	}

	/**
	 * Return canonical magic word for a media option
	 * @param string $word
	 * @return string|null
	 */
	public function getMagicWordForMediaOption( string $word ): ?string {
		$this->populateMagicWords();
		return self::getMagicWordCanonicalName( $this->mediaOptions, $word );
	}

	/**
	 * Return canonical magic word for a behavior switch
	 * @param string $word
	 * @return string|null
	 */
	public function getMagicWordForBehaviorSwitch( string $word ): ?string {
		$this->populateMagicWords();
		return self::getMagicWordCanonicalName( $this->behaviorSwitches, $word );
	}

	/**
	 * Check if a string is a recognized behavior switch.
	 *
	 * @param string $word
	 * @return bool
	 */
	public function isBehaviorSwitch( string $word ): bool {
		return $this->getMagicWordForBehaviorSwitch( $word ) !== null;
	}

	/**
	 * Convert the internal canonical magic word name to the wikitext alias.
	 * @param string $word Canonical magic word name
	 * @param string $suggest Suggested alias (used as fallback and preferred choice)
	 * @return string
	 */
	public function getMagicWordWT( string $word, string $suggest ): string {
		$aliases = $this->mwAliases()[$word] ?? null;
		if ( !$aliases ) {
			return $suggest;
		}
		$ind = 0;
		if ( $suggest ) {
			$ind = array_search( $suggest, $aliases, true );
		}
		return $aliases[$ind ?: 0];
	}

	/**
	 * Get a regexp matching a localized magic word, given its id.
	 *
	 * FIXME: misleading function name
	 *
	 * @param string $id
	 * @return string
	 */
	abstract public function getMagicWordMatcher( string $id ): string;

	/**
	 * Get a matcher function for fetching values out of interpolated magic words,
	 * ie those with `$1` in their aliases.
	 *
	 * The matcher takes a string and returns null if it doesn't match any of
	 * the words, or an associative array if it did match:
	 *  - k: The magic word that matched
	 *  - v: The value of $1 that was matched
	 * (the JS also returned 'a' with the specific alias that matched, but that
	 * seems to be unused and so is omitted here)
	 *
	 * @param string[] $words Magic words to match
	 * @return callable
	 */
	abstract protected function getParameterizedAliasMatcher( array $words ): callable;

	/**
	 * Get a matcher function for fetching values out of interpolated magic words
	 * which are media prefix options.
	 *
	 * The matcher takes a string and returns null if it doesn't match any of
	 * the words, or an associative array if it did match:
	 *  - k: The magic word that matched
	 *  - v: The value of $1 that was matched
	 * (the JS also returned 'a' with the specific alias that matched, but that
	 * seems to be unused and so is omitted here)
	 *
	 * @return callable
	 */
	final public function getMediaPrefixParameterizedAliasMatcher(): callable {
		// PORT-FIXME: this shouldn't be a constant, we should fetch these
		// from the SiteConfig.  Further, we probably need a hook here so
		// Parsoid can handle media options defined in extensions... in
		// particular timedmedia_* magic words from Extension:TimedMediaHandler
		$mws = array_keys( Consts::$Media['PrefixOptions'] );
		return $this->getParameterizedAliasMatcher( $mws );
	}

	/**
	 * Get the maximum template depth
	 *
	 * @return int
	 */
	abstract public function getMaxTemplateDepth(): int;

	/**
	 * Return name spaces aliases for the NS_SPECIAL namespace
	 * @return array
	 */
	abstract protected function getSpecialNSAliases(): array;

	/**
	 * Return Special Page aliases for a special page name
	 * @param string $specialPage
	 * @return array
	 */
	abstract protected function getSpecialPageAliases( string $specialPage ): array;

	/**
	 * Quote a title regex
	 *
	 * Assumes '/' as the delimiter, and replaces spaces or underscores with
	 * `[ _]` so either will be matched.
	 *
	 * @param string $s
	 * @param string $delimiter Defaults to '/'
	 * @return string
	 */
	protected static function quoteTitleRe( string $s, string $delimiter = '/' ): string {
		$s = preg_quote( $s, $delimiter );
		$s = strtr( $s, [
			' ' => '[ _]',
			'_' => '[ _]',
		] );
		return $s;
	}

	/**
	 * Matcher for ISBN/RFC/PMID URL patterns, returning the type and number.
	 *
	 * The match method takes a string and returns false on no match or a tuple
	 * like this on match: [ 'RFC', '12345' ]
	 *
	 * @return callable
	 */
	public function getExtResourceURLPatternMatcher(): callable {
		$nsAliases = implode( '|', array_unique( $this->getSpecialNSAliases() ) );
		$pageAliases = implode( '|', array_map( [ $this, 'quoteTitleRe' ],
			$this->getSpecialPageAliases( 'Booksources' )
		) );

		// cscott wants a mention of T145590 here ("Update Parsoid to be compatible with magic links
		// being disabled")
		$pats = [
			'ISBN' => '(?:\.\.?/)*(?i:' . $nsAliases . ')(?:%3[Aa]|:)'
				. '(?i:' . $pageAliases . ')(?:%2[Ff]|/)(?P<ISBN>\d+[Xx]?)',
			'RFC' => '[^/]*//tools\.ietf\.org/html/rfc(?P<RFC>\w+)',
			'PMID' => '[^/]*//www\.ncbi\.nlm\.nih\.gov/pubmed/(?P<PMID>\w+)\?dopt=Abstract',
		];
		$regex = '!^(?:' . implode( '|', $pats ) . ')$!';
		return static function ( $text ) use ( $pats, $regex ) {
			if ( preg_match( $regex, $text, $m ) ) {
				foreach ( $pats as $k => $re ) {
					if ( isset( $m[$k] ) && $m[$k] !== '' ) {
						return [ $k, $m[$k] ];
					}
				}
			}
			return false;
		};
	}

	/**
	 * Return the desired linter configuration.  These are heuristic values
	 * which have hardcoded defaults but could be overridden on a per-wiki
	 * basis.
	 * @return array{enabled?:string[],disabled?:string[],maxTableColumnHeuristic?:int,maxTableRowsToCheck?:int}
	 */
	public function getLinterConfig(): array {
		return [
			// Allow list for specific lint types.
			// Takes precedence over block list.
			'enabled' => null,
			// Block list for specific lint types.
			// Not used if an allow list is set.
			'disabled' => null,
			// The maximum columns in a table before the table is considered
			// large
			'maxTableColumnHeuristic' => 5,
			// The maximum rows (header or data) to be checked for the large
			// table lint
			// - If we consider the first N rows to be representative of the
			//   table, and the table is well-formed and uniform, it is
			//   sufficent to check the first N rows to check if the table is
			//   "large".
			// - This heuristic is used together with the
			//   'maxTableColumnHeuristic' to identify "large tables".
			'maxTableRowsToCheck' => 10,
			// Max length of content covered by 'white-space:nowrap' CSS
			// that we consider "safe" when Tidy is replaced.  Beyond that,
			// wikitext will have to be fixed up to manually insert whitespace
			// at the right places.  Length in bytes.
			'tidyWhitespaceBugMaxLength' => 100,
		];
	}

	/**
	 * Serialize ISBN/RFC/PMID URL patterns
	 *
	 * @param string[] $match As returned by the getExtResourceURLPatternMatcher() matcher
	 * @param string $href Fallback link target, if $match is invalid.
	 * @param string $content Link text
	 * @return string
	 */
	public function makeExtResourceURL( array $match, string $href, string $content ): string {
		$normalized = preg_replace(
			'/[ \x{00A0}\x{1680}\x{2000}-\x{200A}\x{202F}\x{205F}\x{3000}]+/u', ' ',
			Utils::decodeWtEntities( $content )
		);

		// TODO: T145590 ("Update Parsoid to be compatible with magic links being disabled")
		switch ( $match[0] ) {
			case 'ISBN':
				$normalized = strtoupper( preg_replace( '/[\- \t]/', '', $normalized ) );
				// validate ISBN length and format, so as not to produce magic links
				// which aren't actually magic
				$valid = preg_match( '/^ISBN(97[89])?\d{9}(\d|X)$/D', $normalized );
				if ( implode( '', $match ) === $normalized && $valid ) {
					return $content;
				}
				// strip "./" prefix. TODO: Use relativeLinkPrefix() instead?
				$href = PHPUtils::stripPrefix( $href, './' );
				return "[[$href|$content]]";

			case 'RFC':
			case 'PMID':
				$normalized = preg_replace( '/[ \t]/', '', $normalized );
				return implode( '', $match ) === $normalized ? $content : "[$href $content]";

			default:
				throw new \InvalidArgumentException( "Invalid match type '{$match[0]}'" );
		}
	}

	/**
	 * Get the list of valid protocols
	 * @return array
	 */
	abstract protected function getProtocols(): array;

	/**
	 * Get a regex fragment matching URL protocols, quoted for an exclamation
	 * mark delimiter. The case-insensitive option should be used.
	 *
	 * @param bool $excludeProtRel Whether to exclude protocol-relative URLs
	 * @return string
	 */
	public function getProtocolsRegex( bool $excludeProtRel = false ) {
		$excludeProtRel = (int)$excludeProtRel;
		if ( !isset( $this->protocolsRegexes[$excludeProtRel] ) ) {
			$parts = [];
			foreach ( $this->getProtocols() as $protocol ) {
				if ( !$excludeProtRel || $protocol !== '//' ) {
					$parts[] = preg_quote( $protocol, '!' );
				}
			}
			$this->protocolsRegexes[$excludeProtRel] = implode( '|', $parts );
		}
		return $this->protocolsRegexes[$excludeProtRel];
	}

	/**
	 * Matcher for valid protocols, must be anchored at start of string.
	 * @param string $potentialLink
	 * @return bool Whether $potentialLink begins with a valid protocol
	 */
	public function hasValidProtocol( string $potentialLink ): bool {
		$re = '!^(?:' . $this->getProtocolsRegex() . ')!i';
		return (bool)preg_match( $re, $potentialLink );
	}

	/**
	 * Matcher for valid protocols, may occur at any point within string.
	 * @param string $potentialLink
	 * @return bool Whether $potentialLink contains a valid protocol
	 */
	public function findValidProtocol( string $potentialLink ): bool {
		$re = '!(?:\W|^)(?:' . $this->getProtocolsRegex() . ')!i';
		return (bool)preg_match( $re, $potentialLink );
	}

	/** @} */

	/**
	 * Fake timestamp, for unit tests.
	 * @return int|null Unix timestamp, or null to not fake it
	 */
	public function fakeTimestamp(): ?int {
		return null;
	}

	/**
	 * Get an array of defined extension tags, with the lower case name in the
	 * key, the value arbitrary. This is the set of extension tags that are
	 * configured in M/W core. $coreExtModules may already be part of it,
	 * but eventually this distinction will disappear since all extension tags
	 * have to be defined against the Parsoid's extension API.
	 *
	 * @return array
	 */
	abstract protected function getNonNativeExtensionTags(): array;

	/**
	 * Return an object factory to use when instantiating extensions.
	 * (This is assumed to be plumbed up to an appropriate service container.)
	 * @return ObjectFactory The object factory to use for extensions
	 */
	public function getObjectFactory(): ObjectFactory {
		// Default implementation returns an object factory with an
		// empty service container.
		return new ObjectFactory( new class() implements ContainerInterface {

			/**
			 * @param string $id
			 * @return never
			 */
			public function get( $id ) {
				throw new class( "Empty service container" ) extends \Error
					implements NotFoundExceptionInterface {
				};
			}

			/**
			 * @param string $id
			 * @return false
			 */
			public function has( $id ): bool {
				return false;
			}
		} );
	}

	/**
	 * FIXME: might benefit from T250230 (caching) but see T270307 --
	 * currently SiteConfig::unregisterExtensionModule() is called
	 * during testing, which requires invalidating $this->extConfig.
	 * (See also SiteConfig::fakeTimestamp() etc.)  We'd probably need
	 * to more fully separate/mock the "testing SiteConfig" as well
	 * as provide a way for parser options to en/disable individual
	 * registered modules before this class can be considered immutable
	 * and cached.
	 */
	private function constructExtConfig() {
		$this->extConfig = [
			'allTags'        => [],
			'parsoidExtTags' => [],
			'annotationTags' => [],
			'domProcessors'  => [],
			'annotationStrippers' => [],
			'contentModels'  => [],
		];

		// There may be some tags defined by the parent wiki which have no
		// associated parsoid modules; for now we handle these by invoking
		// the legacy parser.
		$this->extConfig['allTags'] = $this->getNonNativeExtensionTags();

		foreach ( $this->getExtensionModules() as $module ) {
			$this->processExtensionModule( $module );
		}
	}

	/**
	 * @param string $lowerTagName
	 * @return bool
	 */
	public function tagNeedsNowikiStrippedInTagPF( string $lowerTagName ): bool {
		return isset( $this->t299103Tags[$lowerTagName] );
	}

	/**
	 * Register a Parsoid-compatible extension
	 * @param ExtensionModule $ext
	 */
	protected function processExtensionModule( ExtensionModule $ext ): void {
		Assert::invariant( $this->extConfig !== null, "not yet inited!" );
		$extConfig = $ext->getConfig();
		Assert::invariant(
			isset( $extConfig['name'] ),
			"Every extension module must have a name."
		);
		$name = $extConfig['name'];

		// These are extension tag handlers.  They have
		// wt2html (sourceToDom), html2wt (domToWikitext), and
		// linter functionality.
		foreach ( $extConfig['tags'] ?? [] as $tagConfig ) {
			$lowerTagName = mb_strtolower( $tagConfig['name'] );
			$this->extConfig['allTags'][$lowerTagName] = true;
			$this->extConfig['parsoidExtTags'][$lowerTagName] = $tagConfig;
			// Deal with b/c nowiki stripping support needed by some extensions.
			// This register this tag with the legacy parser for
			// implicit nowiki stripping in {{#tag:..}} args for this tag.
			if ( isset( $tagConfig['options']['stripNowiki'] ) ) {
				$this->t299103Tags[$lowerTagName] = true;
			}
		}

		if ( isset( $extConfig['annotations'] ) ) {
			$annotationConfig = $extConfig['annotations'];
			$annotationTags = $annotationConfig['tagNames'] ?? $annotationConfig;
			foreach ( $annotationTags ?? [] as $aTag ) {
				$lowerTagName = mb_strtolower( $aTag );
				$this->extConfig['allTags'][$lowerTagName] = true;
				$this->extConfig['annotationTags'][$lowerTagName] = true;
			}
			if ( isset( $annotationConfig['annotationStripper'] ) ) {
				$obj = $this->getObjectFactory()->createObject( $annotationConfig['annotationStripper'], [
					'allowClassName' => true,
					'assertClass' => AnnotationStripper::class,
				] );
				$this->extConfig['annotationStrippers'][$name] = $obj;
			}
		}

		// Extension modules may also register dom processors.
		// This is for wt2htmlPostProcessor and html2wtPreProcessor
		// functionality.
		if ( isset( $extConfig['domProcessors'] ) ) {
			$this->extConfig['domProcessors'][$name] = $extConfig['domProcessors'];
		}

		foreach ( $extConfig['contentModels'] ?? [] as $cm => $spec ) {
			// For compatibility with mediawiki core, the first
			// registered extension wins.
			if ( isset( $this->extConfig['contentModels'][$cm] ) ) {
				continue;
			}
			$handler = $this->getObjectFactory()->createObject( $spec, [
				'allowClassName' => true,
				'assertClass' => ContentModelHandler::class,
			] );
			$this->extConfig['contentModels'][$cm] = $handler;
		}
	}

	protected function getExtConfig(): array {
		if ( !$this->extConfig ) {
			$this->constructExtConfig();
		}
		return $this->extConfig;
	}

	/**
	 * Return a ContentModelHandler for the specified $contentmodel, if one is registered.
	 * If null is returned, will use the default wikitext content model handler.
	 *
	 * @param string $contentmodel
	 * @return ContentModelHandler|null
	 */
	public function getContentModelHandler( string $contentmodel ): ?ContentModelHandler {
		return ( $this->getExtConfig() )['contentModels'][$contentmodel] ?? null;
	}

	/**
	 * Returns all the annotationStrippers that are defined as annotation configuration
	 * @return array<AnnotationStripper>
	 */
	public function getAnnotationStrippers(): array {
		$res = $this->getExtConfig()['annotationStrippers'] ?? [];
		// ensures stability of the method list order
		ksort( $res );
		return array_values( $res );
	}

	/**
	 * Determine whether a given name, which must have already been converted
	 * to lower case, is a valid extension tag name.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function isExtensionTag( string $name ): bool {
		return isset( $this->getExtensionTagNameMap()[$name] );
	}

	/**
	 * @param string $tagName is $tagName an annotation tag?
	 * @return bool
	 */
	public function isAnnotationTag( string $tagName ): bool {
		return $this->getExtConfig()['annotationTags'][mb_strtolower( $tagName )] ?? false;
	}

	/**
	 * Get an array of defined annotation tags in lower case
	 * @return array
	 */
	public function getAnnotationTags(): array {
		$extConfig = $this->getExtConfig();
		return array_keys( $extConfig['annotationTags'] );
	}

	/**
	 * Get an array of defined extension tags, with the lower case name
	 * in the key, and the value being arbitrary.
	 *
	 * @return array
	 */
	public function getExtensionTagNameMap(): array {
		$extConfig = $this->getExtConfig();
		return $extConfig['allTags'];
	}

	/**
	 * @param string $tagName Extension tag name
	 * @return array|null
	 */
	public function getExtTagConfig( string $tagName ): ?array {
		$extConfig = $this->getExtConfig();
		return $extConfig['parsoidExtTags'][mb_strtolower( $tagName )] ?? null;
	}

	private $tagHandlerCache = [];

	/**
	 * @param string $tagName Extension tag name
	 * @return ExtensionTagHandler|null
	 *   Returns the implementation of the named extension, if there is one.
	 */
	public function getExtTagImpl( string $tagName ): ?ExtensionTagHandler {
		if ( !array_key_exists( $tagName, $this->tagHandlerCache ) ) {
			$tagConfig = $this->getExtTagConfig( $tagName );
			$this->tagHandlerCache[$tagName] = isset( $tagConfig['handler'] ) ?
				$this->getObjectFactory()->createObject( $tagConfig['handler'], [
					'allowClassName' => true,
					'assertClass' => ExtensionTagHandler::class,
				] ) : null;
		}

		return $this->tagHandlerCache[$tagName];
	}

	/**
	 * Return an array mapping extension name to an array of object factory
	 * specs for Ext\DOMProcessor objects
	 * @return array
	 */
	public function getExtDOMProcessors(): array {
		$extConfig = $this->getExtConfig();
		return $extConfig['domProcessors'];
	}

	/** @var array<string,int> */
	protected $wt2htmlLimits = [
		// We won't handle pages beyond this size
		'wikitextSize' => 2048 * 1024, // ParserOptions::maxIncludeSize

		// Max list items per page
		'listItem' => 30000,

		// Max table cells per page
		'tableCell' => 30000,

		// Max transclusions per page
		'transclusion' => 10000,

		// DISABLED for now
		// Max images per page
		'image' => 1000,

		// Max top-level token size
		'token' => 1000000, // 1M
	];

	/**
	 * @return array<string,int>
	 */
	public function getWt2HtmlLimits(): array {
		return $this->wt2htmlLimits;
	}

	/** @var array<string,int> */
	protected $html2wtLimits = [
		// We refuse to serialize HTML strings bigger than this
		'htmlSize' => 10000000, // 10M
	];

	/**
	 * @return array<string,int>
	 */
	public function getHtml2WtLimits(): array {
		return $this->html2wtLimits;
	}

	/**
	 * @param ?string $filePath File to log to (if null, logs to console)
	 * @return Logger
	 */
	public static function createLogger( ?string $filePath = null ): Logger {
		// Use Monolog's PHP console handler
		$logger = new Logger( "Parsoid CLI" );
		$format = '%message%';
		if ( $filePath ) {
			$handler = new StreamHandler( $filePath );
			$format .= "\n";
		} else {
			$handler = new ErrorLogHandler();
		}
		// Don't suppress inline newlines
		$handler->setFormatter( new LineFormatter( $format, null, true ) );
		$logger->pushHandler( $handler );

		if ( $filePath ) {
			// Separator between logs since StreamHandler appends
			$logger->log( Logger::INFO, "-------------- starting fresh log --------------" );
		}

		return $logger;
	}

	abstract public function getNoFollowConfig(): array;

	/** @return string|false */
	abstract public function getExternalLinkTarget();
}
