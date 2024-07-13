<?php

namespace MediaWiki\Extension\DiscussionTools\Tests;

use FormatJson;
use GenderCache;
use HashConfig;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Extension\DiscussionTools\CommentParser;
use MediaWiki\Interwiki\NullInterwikiLookup;
use MediaWiki\Languages\LanguageConverterFactory;
use MediaWiki\Languages\LanguageFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\StaticUserOptionsLookup;
use MediaWikiTitleCodec;
use MultiConfig;
use NamespaceInfo;
use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMUtils;

trait TestUtils {

	/**
	 * Create a Document from a string.
	 */
	protected static function createDocument( string $html ): Document {
		return DOMUtils::parseHTML( $html );
	}

	/**
	 * Return the node that is expected to contain thread items.
	 */
	protected static function getThreadContainer( Document $doc ): Element {
		// In tests created from Parsoid output, comments are contained directly in <body>.
		// In tests created from old parser output, comments are contained in <div class="mw-parser-output">.
		$body = DOMCompat::getBody( $doc );
		$wrapper = DOMCompat::querySelector( $body, 'div.mw-parser-output' );
		return $wrapper ?: $body;
	}

	/**
	 * Get text from path
	 */
	protected static function getText( string $relativePath ): string {
		return file_get_contents( __DIR__ . '/../' . $relativePath );
	}

	/**
	 * Write text to path
	 */
	protected static function overwriteTextFile( string $relativePath, string $text ): void {
		file_put_contents( __DIR__ . '/../' . $relativePath, $text );
	}

	/**
	 * Get parsed JSON from path
	 *
	 * @param string $relativePath
	 * @param bool $assoc See json_decode()
	 * @return array
	 */
	protected static function getJson( string $relativePath, bool $assoc = true ): array {
		$json = json_decode(
			file_get_contents( __DIR__ . '/' . $relativePath ),
			$assoc
		);
		return $json;
	}

	/**
	 * Write JSON to path
	 */
	protected static function overwriteJsonFile( string $relativePath, array $data ): void {
		$json = FormatJson::encode( $data, "\t", FormatJson::ALL_OK );
		file_put_contents( __DIR__ . '/' . $relativePath, $json . "\n" );
	}

	/**
	 * Get HTML from path
	 */
	protected static function getHtml( string $relativePath ): string {
		return file_get_contents( __DIR__ . '/../' . $relativePath );
	}

	/**
	 * Write HTML to path
	 */
	protected static function overwriteHtmlFile( string $relPath, Element $container, string $origRelPath ): void {
		// Do not use $doc->saveHtml(), it outputs an awful soup of HTML entities for documents with
		// non-ASCII characters
		$html = file_get_contents( __DIR__ . '/../' . $origRelPath );

		$newInnerHtml = DOMCompat::getInnerHTML( $container );

		if ( strtolower( $container->tagName ) === 'body' ) {
			// Apparently <body> innerHTML always has a trailing newline, even if the source HTML did not,
			// and we need to preserve whatever whitespace was there to avoid test failures
			preg_match( '`(\s*)(</body>|\z)`s', $html, $matches );
			$newInnerHtml = rtrim( $newInnerHtml ) . $matches[1];
		}

		// Quote \ and $ in the replacement text
		$quotedNewInnerHtml = strtr( $newInnerHtml, [ '\\' => '\\\\', '$' => '\\$' ] );

		if ( strtolower( $container->tagName ) === 'body' ) {
			if ( str_contains( $html, '<body' ) ) {
				$html = preg_replace(
					'`(<body[^>]*>)(.*)(</body>)`s',
					'$1' . $quotedNewInnerHtml . '$3',
					$html
				);
			} else {
				$html = $newInnerHtml;
			}
		} else {
			$html = preg_replace(
				'`(<div class="mw-parser-output">)(.*)(</div>)`s',
				'$1' . $quotedNewInnerHtml . '$3',
				$html
			);
		}

		file_put_contents( __DIR__ . '/../' . $relPath, $html );
	}

	private static function prepareConfig( array $config, array $data ): array {
		return [
			'LanguageCode' => $config['wgContentLanguage'],
			'ArticlePath' => $config['wgArticlePath'],
			// TODO: Move this to $config
			'Localtimezone' => $data['localTimezone'],

			// Defaults for NamespaceInfo
			'CanonicalNamespaceNames' => NamespaceInfo::CANONICAL_NAMES,
			'CapitalLinkOverrides' => [],
			'CapitalLinks' => true,
			'ContentNamespaces' => [ NS_MAIN ],
			'ExtraNamespaces' => [],
			'ExtraSignatureNamespaces' => [],
			'NamespaceContentModels' => [],
			'NamespacesWithSubpages' => [
				NS_TALK => true,
				NS_USER => true,
				NS_USER_TALK => true,
				NS_PROJECT => true,
				NS_PROJECT_TALK => true,
				NS_FILE_TALK => true,
				NS_MEDIAWIKI => true,
				NS_MEDIAWIKI_TALK => true,
				NS_TEMPLATE => true,
				NS_TEMPLATE_TALK => true,
				NS_HELP => true,
				NS_HELP_TALK => true,
				NS_CATEGORY_TALK => true
			],
			'NonincludableNamespaces' => [],

			// Defaults for LanguageFactory
			'DummyLanguageCodes' => [],

			// Defaults for LanguageConverterFactory
			'UsePigLatinVariant' => false,
			'DisableLangConversion' => false,
			'DisableTitleConversion' => false,

			// Defaults for Language
			'ExtraGenderNamespaces' => [],

			// Overrides
			'ExtraNamespaces' => array_diff_key( $config['wgFormattedNamespaces'], NamespaceInfo::CANONICAL_NAMES ),
			'MetaNamespace' => strtr( $config['wgFormattedNamespaces'][NS_PROJECT], ' ', '_' ),
			'MetaNamespaceTalk' => strtr( $config['wgFormattedNamespaces'][NS_PROJECT_TALK], ' ', '_' ),
			'NamespaceAliases' => $config['wgNamespaceIds'],
		];
	}

	public function createParser( array $config, array $data ): CommentParser {
		// TODO: Derive everything from $config and $data without using global services
		$services = MediaWikiServices::getInstance();

		$config = self::prepareConfig( $config, $data );

		$langConvFactory = new LanguageConverterFactory(
			new ServiceOptions( LanguageConverterFactory::CONSTRUCTOR_OPTIONS, $config ),
			$services->getObjectFactory(),
			static function () use ( $services ) {
				return $services->getLanguageFactory()->getLanguage( $config['LanguageCode'] );
			}
		);

		return new CommentParser(
			new HashConfig( $config ),
			$services->getLanguageFactory()->getLanguage( $config['LanguageCode'] ),
			$langConvFactory,
			new MockLanguageData( $data ),
			$this->createTitleParser( $config )
		);
	}

	public function createTitleParser( array $config ): MediaWikiTitleCodec {
		// TODO: Derive everything from $config and $data without using global services
		$services = MediaWikiServices::getInstance();

		if ( isset( $config['wgArticlePath'] ) ) {
			$config = self::prepareConfig( $config, [ 'localTimezone' => '' ] );
		}

		$nsInfo = new NamespaceInfo(
			new ServiceOptions( NamespaceInfo::CONSTRUCTOR_OPTIONS, $config ),
			$services->getHookContainer(),
			[],
			[]
		);

		$langFactory = new LanguageFactory(
			new ServiceOptions( LanguageFactory::CONSTRUCTOR_OPTIONS, $config ),
			$nsInfo,
			$services->getLocalisationCache(),
			$services->getLanguageNameUtils(),
			$services->getLanguageFallback(),
			$services->getLanguageConverterFactory(),
			$services->getHookContainer(),
			new MultiConfig( [ new HashConfig( $config ), $services->getMainConfig() ] )
		);

		$contLang = $langFactory->getLanguage( $config['LanguageCode'] );

		return new MediaWikiTitleCodec(
			$contLang,
			new GenderCache( $nsInfo, null, new StaticUserOptionsLookup( [] ) ),
			[],
			new NullInterwikiLookup(),
			$nsInfo
		);
	}
}
