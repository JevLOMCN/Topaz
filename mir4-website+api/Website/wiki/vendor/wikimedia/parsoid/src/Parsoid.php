<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid;

use Composer\InstalledVersions;
use Composer\Semver\Comparator;
use Composer\Semver\Semver;
use InvalidArgumentException;
use LogicException;
use Wikimedia\Bcp47Code\Bcp47Code;
use Wikimedia\Parsoid\Config\DataAccess;
use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\Config\PageConfig;
use Wikimedia\Parsoid\Config\SiteConfig;
use Wikimedia\Parsoid\Config\StubMetadataCollector;
use Wikimedia\Parsoid\Core\ContentMetadataCollector;
use Wikimedia\Parsoid\Core\PageBundle;
use Wikimedia\Parsoid\Core\ResourceLimitExceededException;
use Wikimedia\Parsoid\Core\SelserData;
use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;
use Wikimedia\Parsoid\Language\LanguageConverter;
use Wikimedia\Parsoid\Logger\LintLogger;
use Wikimedia\Parsoid\Utils\ContentUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\Timing;
use Wikimedia\Parsoid\Utils\Utils;
use Wikimedia\Parsoid\Wikitext\Wikitext;
use Wikimedia\Parsoid\Wt2Html\PP\Processors\AddRedLinks;
use Wikimedia\Parsoid\Wt2Html\PP\Processors\ConvertOffsets;

class Parsoid {

	/**
	 * Available HTML content versions.
	 * @see https://www.mediawiki.org/wiki/Parsoid/API#Content_Negotiation
	 * @see https://www.mediawiki.org/wiki/Specs/HTML#Versioning
	 */
	public const AVAILABLE_VERSIONS = [ '2.8.0', '999.0.0' ];

	private const DOWNGRADES = [
		[ 'from' => '999.0.0', 'to' => '2.0.0', 'func' => 'downgrade999to2' ],
	];

	/** @var SiteConfig */
	private $siteConfig;

	/** @var DataAccess */
	private $dataAccess;

	public function __construct(
		SiteConfig $siteConfig, DataAccess $dataAccess
	) {
		$this->siteConfig = $siteConfig;
		$this->dataAccess = $dataAccess;
	}

	/**
	 * Returns the currently-installed version of Parsoid.
	 * @return string
	 */
	public static function version(): string {
		try {
			// See https://getcomposer.org/doc/07-runtime.md#knowing-the-version-of-package-x
			return InstalledVersions::getVersion( 'wikimedia/parsoid' ) ??
				// From the composer runtime API docs:
				// "It is nonetheless a good idea to make sure you
				// handle the null return value as gracefully as
				// possible for safety."
				'null';
		} catch ( \Throwable $t ) {
			// Belt-and-suspenders protection against parts of the composer
			// runtime API being absent in production.
			return 'error';
		}
	}

	/**
	 * Returns the default HTML content version
	 * @return string
	 */
	public static function defaultHTMLVersion(): string {
		return self::AVAILABLE_VERSIONS[0];
	}

	/**
	 * See if any content version Parsoid knows how to produce satisfies the
	 * the supplied version, when interpreted with semver caret semantics.
	 * This will allow us to make backwards compatible changes, without the need
	 * for clients to bump the version in their headers all the time.
	 *
	 * @param string $version
	 * @return string|null
	 */
	public static function resolveContentVersion( string $version ) {
		foreach ( self::AVAILABLE_VERSIONS as $i => $a ) {
			if ( Semver::satisfies( $a, "^{$version}" ) &&
				// The section wrapping in 1.6.x should have induced a major
				// version bump, since it requires upgrading clients to
				// handle it.  We therefore hardcode this in so that we can
				// fail hard.
				Comparator::greaterThanOrEqualTo( $version, '1.6.0' )
			) {
				return $a;
			}
		}
		return null;
	}

	/**
	 * Determine if language conversion is enabled, aka if the optional
	 * wikimedia/langconv library is installed.
	 * @return bool True if the wikimedia/langconv library is available
	 */
	public static function supportsLanguageConversion(): bool {
		return class_exists( '\Wikimedia\LangConv\ReplacementMachine' );
	}

	private function setupCommonOptions( array $options ): array {
		$envOptions = [];
		if ( isset( $options['offsetType'] ) ) {
			$envOptions['offsetType'] = $options['offsetType'];
		}
		if ( isset( $options['traceFlags'] ) ) {
			$envOptions['traceFlags'] = $options['traceFlags'];
		}
		if ( isset( $options['dumpFlags'] ) ) {
			$envOptions['dumpFlags'] = $options['dumpFlags'];
		}
		if ( isset( $options['debugFlags'] ) ) {
			$envOptions['debugFlags'] = $options['debugFlags'];
		}
		if ( !empty( $options['htmlVariantLanguage'] ) ) {
			$envOptions['htmlVariantLanguage'] = $options['htmlVariantLanguage'];
		}
		if ( !empty( $options['wtVariantLanguage'] ) ) {
			$envOptions['wtVariantLanguage'] = $options['wtVariantLanguage'];
		}
		if ( isset( $options['logLevels'] ) ) {
			$envOptions['logLevels'] = $options['logLevels'];
		}
		return $envOptions;
	}

	/**
	 * Parsing code shared between the next two methods.
	 *
	 * @param PageConfig $pageConfig
	 * @param ContentMetadataCollector $metadata
	 * @param array $options See wikitext2html.
	 * @return array
	 */
	private function parseWikitext(
		PageConfig $pageConfig,
		ContentMetadataCollector $metadata,
		array $options = []
	): array {
		$envOptions = $this->setupCommonOptions( $options );
		if ( isset( $options['outputContentVersion'] ) ) {
			$envOptions['outputContentVersion'] = $options['outputContentVersion'];
		}
		$envOptions['discardDataParsoid'] = !empty( $options['discardDataParsoid'] );
		if ( isset( $options['wrapSections'] ) ) {
			$envOptions['wrapSections'] = (bool)$options['wrapSections'];
		}
		if ( isset( $options['pageBundle'] ) ) {
			$envOptions['pageBundle'] = (bool)$options['pageBundle'];
		}
		if ( isset( $options['logLinterData'] ) ) {
			$envOptions['logLinterData'] = (bool)$options['logLinterData'];
		}
		$envOptions['skipLanguageConversionPass'] =
			$options['skipLanguageConversionPass'] ?? false;

		$env = new Env(
			$this->siteConfig, $pageConfig, $this->dataAccess, $metadata, $envOptions
		);
		if ( !$env->compareWt2HtmlLimit(
			'wikitextSize', strlen( $env->topFrame->getSrcText() )
		) ) {
			throw new ResourceLimitExceededException(
				"wt2html: wikitextSize limit exceeded"
			);
		}
		$contentmodel = $options['contentmodel'] ?? null;
		$handler = $env->getContentHandler( $contentmodel );
		$extApi = new ParsoidExtensionAPI( $env );
		return [ $env, $handler->toDOM( $extApi ), $contentmodel ];
	}

	/**
	 * Parse the wikitext supplied in a `PageConfig` to HTML.
	 *
	 * @param PageConfig $pageConfig
	 * @param array $options [
	 *   'wrapSections'         => (bool) Whether `<section>` wrappers should be added.
	 *   'pageBundle'           => (bool) Sets ids on nodes and stores
	 *                                    data-* attributes in a JSON blob.
	 *   'body_only'            => (bool|null) Only return the <body> children (T181657)
	 *   'outputContentVersion' => (string|null) Version of HTML to output.
	 *                                           `null` returns the default version.
	 *   'contentmodel'         => (string|null) The content model of the input.
	 *   'discardDataParsoid'   => (bool) Drop all data-parsoid annotations.
	 *   'offsetType'           => (string) ucs2, char, byte are valid values
	 *                                      what kind of source offsets should be emitted?
	 *   'skipLanguageConversionPass'  => (bool) Skip the language variant conversion pass (defaults to false)
	 *   'htmlVariantLanguage'  => (Bcp47Code) If non-null, the language variant used for Parsoid HTML.
	 *   'wtVariantLanguage'    => (Bcp47Code) If non-null, the language variant used for wikitext.
	 *   'logLinterData'        => (bool) Should we log linter data if linting is enabled?
	 *   'traceFlags'           => (array) associative array with tracing options
	 *   'dumpFlags'            => (array) associative array with dump options
	 *   'debugFlags'           => (array) associative array with debug options
	 *   'logLevels'            => (string[]) Levels to log
	 * ]
	 * @param ?array &$headers
	 * @param ?ContentMetadataCollector $metadata Pass in a CMC in order to
	 *  collect and retrieve metadata about the parse.
	 * @return PageBundle|string
	 */
	public function wikitext2html(
		PageConfig $pageConfig, array $options = [], ?array &$headers = null,
		?ContentMetadataCollector $metadata = null
	) {
		if ( $metadata === null ) {
			$metadata = new StubMetadataCollector( $this->siteConfig );
		}

		$parseTiming = Timing::start();
		[ $env, $doc, $contentmodel ] = $this->parseWikitext( $pageConfig, $metadata, $options );
		$parseTime = $parseTiming->end();

		// FIXME: Does this belong in parseWikitext so that the other endpoint
		// is covered as well?  It probably depends on expectations of the
		// Rest API.  If callers of /page/lint/ assume that will update the
		// results on the Special page.
		if ( $env->getSiteConfig()->linting() ) {
			( new LintLogger( $env ) )->logLintOutput();
		}

		$headers = DOMUtils::findHttpEquivHeaders( $doc );
		$body_only = !empty( $options['body_only'] );
		$node = $body_only ? DOMCompat::getBody( $doc ) : $doc;

		if ( $env->pageBundle ) {
			$out = ContentUtils::extractDpAndSerialize( $node, [
				'innerXML' => $body_only,
			] );
		} else {
			$out = [
				'html' => ContentUtils::toXML( $node, [
					'innerXML' => $body_only,
				] ),
			];
		}

		$this->recordParseMetrics( $env, $parseTime, $out );

		if ( $env->pageBundle ) {
			return new PageBundle(
				$out['html'],
				$out['pb']->parsoid, $out['pb']->mw ?? null,
				$env->getOutputContentVersion(),
				$headers,
				$contentmodel
			);
		} else {
			return $out['html'];
		}
	}

	/**
	 *
	 */
	private function recordParseMetrics(
		Env $env, float $parseTime, array $out
	) {
		$metrics = $this->siteConfig->metrics();
		if ( !$metrics ) {
			return;
		}

		$pageConfig = $env->getPageConfig();

		// This is somewhat suspect because ParsoidHandler::tryToCreatePageConfig
		// can set a revision id on a MutableRevisionRecord, but it might be simpler
		// to make that go away
		if ( $pageConfig->getRevisionId() ) {
			$mstr = 'pageWithOldid';
		} else {
			$mstr = 'wt';
		}

		$metrics->timing( "entry.wt2html.{$mstr}.parse", $parseTime );

		if ( Semver::satisfies(
			$env->getOutputContentVersion(), '!=' . self::defaultHTMLVersion()
		) ) {
			$metrics->increment( 'entry.wt2html.parse.version.notdefault' );
		}

		$metrics->timing(
			"entry.wt2html.{$mstr}.size.input",
			// @phan-suppress-next-line PhanDeprecatedFunction
			strlen( $pageConfig->getPageMainContent() )
		);

		$outSize = strlen( $out['html'] );
		$metrics->timing( "entry.wt2html.{$mstr}.size.output", $outSize );

		if ( $parseTime > 10 && $outSize > 100 ) {
			// * Don't bother with this metric for really small parse times
			//   p99 for initialization time is ~7ms according to grafana.
			//   So, 10ms ensures that startup overheads don't skew the metrics
			// * For body_only=false requests, <head> section isn't generated
			//   and if the output is small, per-request overheads can skew
			//   the timePerKB metrics.
			//
			// NOTE: This is slightly misleading since there are fixed costs
			// for generating output like the <head> section and should be factored in,
			// but this is good enough for now as a useful first degree of approxmation.
			$timePerKB = $parseTime * 1024 / $outSize;
			$metrics->timing( 'entry.wt2html.timePerKB', $timePerKB );
		}
	}

	/**
	 * Lint the wikitext supplied in a `PageConfig`.
	 *
	 * @param PageConfig $pageConfig
	 * @param array $options See wikitext2html.
	 * @return array
	 */
	public function wikitext2lint(
		PageConfig $pageConfig, array $options = []
	): array {
		$metadata = new StubMetadataCollector( $this->siteConfig );
		[ $env, ] = $this->parseWikitext( $pageConfig, $metadata, $options );
		return $env->getLints();
	}

	/**
	 * Serialize DOM to wikitext.
	 *
	 * @param PageConfig $pageConfig
	 * @param Document $doc Data attributes are expected to have been applied
	 *   already.  Loading them will happen once the environment is created.
	 * @param array $options [
	 *   'inputContentVersion' => (string) The content version of the input.
	 *     Necessary if it differs from the current default in order to
	 *     account for any serialization differences.
	 *   'offsetType'          => (string) ucs2, char, byte are valid values
	 *                                     what kind of source offsets are present in the HTML?
	 *   'contentmodel'        => (string|null) The content model of the input.
	 *   'htmlVariantLanguage' => (Bcp47Code) If non-null, the language variant used for Parsoid HTML.
	 *   'wtVariantLanguage'   => (Bcp47Code) If non-null, the language variant used for wikitext.
	 *   'traceFlags'          => (array) associative array with tracing options
	 *   'dumpFlags'           => (array) associative array with dump options
	 *   'debugFlags'          => (array) associative array with debug options
	 *   'logLevels'           => (string[]) Levels to log
	 *   'htmlSize'            => (int) Size of the HTML that generated $doc
	 * ]
	 * @param ?SelserData $selserData
	 * @return string
	 */
	public function dom2wikitext(
		PageConfig $pageConfig, Document $doc, array $options = [],
		?SelserData $selserData = null
	): string {
		$envOptions = $this->setupCommonOptions( $options );
		if ( isset( $options['inputContentVersion'] ) ) {
			$envOptions['inputContentVersion'] = $options['inputContentVersion'];
		}
		$envOptions['topLevelDoc'] = $doc;
		$metadata = new StubMetadataCollector( $this->siteConfig );
		$env = new Env(
			$this->siteConfig, $pageConfig, $this->dataAccess, $metadata, $envOptions
		);
		$env->bumpHtml2WtResourceUse( 'htmlSize', $options['htmlSize'] ?? 0 );
		$contentmodel = $options['contentmodel'] ?? null;
		$handler = $env->getContentHandler( $contentmodel );
		$extApi = new ParsoidExtensionAPI( $env );

		$serialTiming = Timing::start();
		$wikitext = $handler->fromDOM( $extApi, $selserData );
		$serialTime = $serialTiming->end();

		$this->recordSerializationMetrics( $options, $serialTime, $wikitext );

		return $wikitext;
	}

	/**
	 *
	 */
	private function recordSerializationMetrics(
		array $options, float $serialTime, string $wikitext
	) {
		$metrics = $this->siteConfig->metrics();
		if ( !$metrics ) {
			return;
		}

		$htmlSize = $options['htmlSize'] ?? 0;
		$metrics->timing( 'entry.html2wt.size.input', $htmlSize );

		if ( isset( $options['inputContentVersion'] ) ) {
			$metrics->increment(
				'entry.html2wt.original.version.' . $options['inputContentVersion']
			);
		}

		$metrics->timing( 'entry.html2wt.total', $serialTime );
		$metrics->timing( 'entry.html2wt.size.output', strlen( $wikitext ) );

		if ( $htmlSize ) {  // Avoid division by zero
			// NOTE: the name timePerInputKB is misleading, since $htmlSize is
			//       in characters, not bytes.
			$timePerInputKB = $serialTime * 1024 / $htmlSize;
			$metrics->timing( 'entry.html2wt.timePerInputKB', $timePerInputKB );
		}
	}

	/**
	 * Serialize HTML to wikitext.  Convenience method for dom2wikitext.
	 *
	 * @param PageConfig $pageConfig
	 * @param string $html
	 * @param array $options
	 * @param ?SelserData $selserData
	 * @return string
	 */
	public function html2wikitext(
		PageConfig $pageConfig, string $html, array $options = [],
		?SelserData $selserData = null
	): string {
		$doc = DOMUtils::parseHTML( $html, true );
		$options['htmlSize'] ??= mb_strlen( $html );
		return $this->dom2wikitext( $pageConfig, $doc, $options, $selserData );
	}

	/**
	 * Update the supplied PageBundle based on the `$update` type.
	 *
	 *   'convertoffsets': Convert offsets between formats (byte, char, ucs2)
	 *   'redlinks': Refreshes the classes of known, missing, etc. links.
	 *   'variant': Converts the HTML based on the supplied variant.
	 *
	 * Note that these are DOM transforms, and not roundtrips through wikitext.
	 *
	 * @param PageConfig $pageConfig
	 * @param string $update 'redlinks'|'variant'
	 * @param PageBundle $pb
	 * @param array $options
	 * @return PageBundle
	 */
	public function pb2pb(
		PageConfig $pageConfig, string $update, PageBundle $pb,
		array $options = []
	): PageBundle {
		$envOptions = [
			'pageBundle' => true,
			'topLevelDoc' => DOMUtils::parseHTML( $pb->toHtml(), true ),
		];
		$metadata = new StubMetadataCollector( $this->siteConfig );
		$env = new Env(
			$this->siteConfig, $pageConfig, $this->dataAccess, $metadata, $envOptions
		);
		$doc = $env->topLevelDoc;
		DOMDataUtils::visitAndLoadDataAttribs(
			DOMCompat::getBody( $doc ), [ 'markNew' => true ]
		);

		$dataBagPB = DOMDataUtils::getPageBundle( $doc );
		switch ( $update ) {
			case 'convertoffsets':
				ContentUtils::convertOffsets(
					$env, $doc, $options['inputOffsetType'], $options['outputOffsetType']
				);
				$dataBagPB->parsoid['offsetType'] = $options['outputOffsetType'];
				$dataBagPB->parsoid['counter'] = $pb->parsoid['counter'];
				break;

			case 'redlinks':
				ContentUtils::convertOffsets(
					$env, $doc, $env->getRequestOffsetType(), 'byte'
				);
				( new AddRedLinks() )->run( $env, DOMCompat::getBody( $doc ) );
				( new ConvertOffsets() )->run( $env, DOMCompat::getBody( $doc ), [], true );
				break;

			case 'variant':
				ContentUtils::convertOffsets(
					$env, $doc, $env->getRequestOffsetType(), 'byte'
				);

				// Note that `maybeConvert` could still be a no-op, in case the
				// __NOCONTENTCONVERT__ magic word is present, or the htmlVariant
				// is a base language code or otherwise invalid.
				$hasWtVariant = $options['variant']['wikitext'] ??
					// Deprecated name for this option:
					$options['variant']['source'] ?? false;
				LanguageConverter::maybeConvert(
					$env, $doc,
					Utils::mwCodeToBcp47(
						$options['variant']['html'] ??
						// Deprecated name for this option:
						$options['variant']['target'],
						// Be strict in what we accept.
						true, $this->siteConfig->getLogger()
					),
					$hasWtVariant ?
					Utils::mwCodeToBcp47(
						$options['variant']['wikitext'] ??
						// Deprecated name for this option:
						$options['variant']['source'],
						// Be strict in what we accept.
						true, $this->siteConfig->getLogger()
					) : null
				);

				// NOTE: Keep this in sync with code in core's LanguageVariantConverter
				// Update content-language and vary headers.
				DOMUtils::addHttpEquivHeaders( $doc, [
					'content-language' => $env->htmlContentLanguageBcp47()->toBcp47Code(),
					'vary' => $env->htmlVary()
				] );

				( new ConvertOffsets() )->run( $env, DOMCompat::getBody( $doc ), [], true );
				break;

			default:
				throw new LogicException( $update . 'is an unknown transformation' );
		}

		DOMDataUtils::visitAndStoreDataAttribs(
			DOMCompat::getBody( $doc ), [
				'discardDataParsoid' => $env->discardDataParsoid,
				'storeInPageBundle' => $env->pageBundle,
				'env' => $env,
			]
		);
		$body_only = !empty( $options['body_only'] );
		$node = $body_only ? DOMCompat::getBody( $doc ) : $doc;
		DOMDataUtils::injectPageBundle( $doc, $dataBagPB );
		$out = ContentUtils::extractDpAndSerialize( $node, [
			'innerXML' => $body_only,
		] );
		return new PageBundle(
			$out['html'],
			$out['pb']->parsoid, $out['pb']->mw ?? null,
			// Prefer the passed in version, since this was just a transformation
			$pb->version ?? $env->getOutputContentVersion(),
			DOMUtils::findHttpEquivHeaders( $doc ),
			// Prefer the passed in content model
			$pb->contentmodel ?? $pageConfig->getContentModel()
		);
	}

	/**
	 * Perform pre-save transformations with top-level templates subst'd.
	 *
	 * @param PageConfig $pageConfig
	 * @param string $wikitext
	 * @return string
	 */
	public function substTopLevelTemplates(
		PageConfig $pageConfig, string $wikitext
	): string {
		$metadata = new StubMetadataCollector( $this->siteConfig );
		$env = new Env( $this->siteConfig, $pageConfig, $this->dataAccess, $metadata );
		return Wikitext::pst( $env, $wikitext, true /* $substTLTemplates */ );
	}

	/**
	 * Check whether a given content version can be downgraded to the requested
	 * content version.
	 *
	 * @param string $from Current content version
	 * @param string $to Requested content version
	 * @return string[]|null The downgrade that will fulfill the request, as
	 *   [ 'from' => <old version>, 'to' => <new version> ], or null if it
	 *   can't be fulfilled.
	 */
	public static function findDowngrade( string $from, string $to ): ?array {
		foreach ( self::DOWNGRADES as [ 'from' => $dgFrom, 'to' => $dgTo ] ) {
			if (
				Semver::satisfies( $from, "^$dgFrom" ) &&
				Semver::satisfies( $to, "^$dgTo" )
			) {
				// FIXME: Make this a class?
				return [ 'from' => $dgFrom, 'to' => $dgTo ];
			}
		}
		return null;
	}

	/**
	 * Downgrade a document to an older content version.
	 *
	 * @param string[] $dg Value returned by findDowngrade().
	 * @param PageBundle $pageBundle
	 */
	public static function downgrade(
		array $dg, PageBundle $pageBundle
	): void {
		foreach ( self::DOWNGRADES as [ 'from' => $dgFrom, 'to' => $dgTo, 'func' => $dgFunc ] ) {
			if ( $dg['from'] === $dgFrom && $dg['to'] === $dgTo ) {
				call_user_func( [ self::class, $dgFunc ], $pageBundle );

				// FIXME: Maybe this resolve should just be part of the $dg
				$pageBundle->version = self::resolveContentVersion( $dg['to'] );

				// FIXME: Maybe this should be a helper to avoid the rt
				$doc = DOMUtils::parseHTML( $pageBundle->html );
				// Match the http-equiv meta to the content-type header
				$meta = DOMCompat::querySelector( $doc,
					'meta[property="mw:htmlVersion"], meta[property="mw:html:version"]' );
				if ( $meta ) {
					$meta->setAttribute( 'content', $pageBundle->version );
					$pageBundle->html = ContentUtils::toXML( $doc );
				}

				return;
			}
		}
		throw new InvalidArgumentException(
			"Unsupported downgrade: {$dg['from']} -> {$dg['to']}"
		);
	}

	/**
	 * Check if language variant conversion is implemented for a language
	 *
	 * @internal FIXME: Remove once Parsoid's language variant work is completed
	 * @param PageConfig $pageConfig
	 * @param Bcp47Code $htmlVariant Variant language to check
	 * @return bool
	 */
	public function implementsLanguageConversionBcp47( PageConfig $pageConfig, Bcp47Code $htmlVariant ): bool {
		// Hardcode disable zh lang conversion support since Parsoid's
		// implementation is incomplete and not performant.
		if ( $pageConfig->getPageLanguageBcp47()->toBcp47Code() === 'zh' ) {
			return false;
		}

		$metadata = new StubMetadataCollector( $this->siteConfig );
		$env = new Env( $this->siteConfig, $pageConfig, $this->dataAccess, $metadata );
		return LanguageConverter::implementsLanguageConversionBcp47( $env, $htmlVariant );
	}

	/**
	 * Downgrade the given document and pagebundle from 999.x to 2.x.
	 *
	 * @param PageBundle $pageBundle
	 */
	private static function downgrade999to2( PageBundle $pageBundle ) {
		// Effectively, skip applying data-parsoid.  Note that if we were to
		// support a pb2html downgrade, we'd need to apply the full thing,
		// but that would create complications where ids would be left behind.
		// See the comment in around `DOMDataUtils::applyPageBundle`
		$newPageBundle = new PageBundle(
			$pageBundle->html,
			[ 'ids' => [] ],
			$pageBundle->mw
		);
		$pageBundle->html = $newPageBundle->toHtml();
		// Now, modify the pagebundle to the expected form.  This is important
		// since, at least in the serialization path, the original pb will be
		// applied to the modified content and its presence could cause lost
		// deletions.
		$pageBundle->mw = [ 'ids' => [] ];
	}
}
