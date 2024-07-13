<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

namespace MediaWiki\SyntaxHighlight;

use Content;
use ExtensionRegistry;
use FormatJson;
use IContextSource;
use MediaWiki\Api\Hook\ApiFormatHighlightHook;
use MediaWiki\Content\Hook\ContentGetParserOutputHook;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\Hook\SoftwareInfoHook;
use MediaWiki\Html\Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Parser\Sanitizer;
use MediaWiki\Status\Status;
use MediaWiki\Title\Title;
use Parser;
use ParserOptions;
use RuntimeException;
use TextContent;
use WANObjectCache;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\Ext\ExtensionTagHandler;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;

class SyntaxHighlight extends ExtensionTagHandler implements
	ParserFirstCallInitHook,
	ContentGetParserOutputHook,
	ApiFormatHighlightHook,
	SoftwareInfoHook
{

	/** @var string CSS class for syntax-highlighted code. Public as used by the updateCSS maintenance script. */
	public const HIGHLIGHT_CSS_CLASS = 'mw-highlight';

	/** @var int Cache version. Increment whenever the HTML changes. */
	private const CACHE_VERSION = 2;

	/** @var array<string,string> Mapping of MIME-types to lexer names. */
	private static $mimeLexers = [
		'text/javascript'  => 'javascript',
		'application/json' => 'javascript',
		'text/xml'         => 'xml',
	];

	/**
	 * Returns the maximum number of lines that may be selected for highlighting
	 *
	 * @return int
	 */
	private static function getMaxLines(): int {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		return $config->get( 'SyntaxHighlightMaxLines' );
	}

	/**
	 * Returns the maximum input size for the highlighter
	 *
	 * @return int
	 */
	private static function getMaxBytes(): int {
		$config = MediaWikiServices::getInstance()->getMainConfig();
		return $config->get( 'SyntaxHighlightMaxBytes' );
	}

	/**
	 * Get the Pygments lexer name for a particular language.
	 *
	 * @param string|null $lang Language name.
	 * @return string|null Lexer name, or null if no matching lexer.
	 */
	private static function getLexer( $lang ) {
		static $lexers = null;

		if ( $lang === null ) {
			return null;
		}

		$lexers ??= Pygmentize::getLexers();

		$lexer = strtolower( $lang );

		if ( isset( $lexers[$lexer] ) ) {
			return $lexer;
		}

		$geshi2pygments = SyntaxHighlightGeSHiCompat::getGeSHiToPygmentsMap();

		// Check if this is a GeSHi lexer name for which there exists
		// a compatible Pygments lexer with a different name.
		if ( isset( $geshi2pygments[$lexer] ) ) {
			$lexer = $geshi2pygments[$lexer];
			if ( isset( $lexers[$lexer] ) ) {
				return $lexer;
			}
		}

		return null;
	}

	/**
	 * Register parser hook
	 *
	 * @param Parser $parser
	 */
	public function onParserFirstCallInit( $parser ) {
		$parser->setHook( 'source', [ self::class, 'parserHookSource' ] );
		$parser->setHook( 'syntaxhighlight', [ self::class, 'parserHook' ] );
	}

	/**
	 * Parser hook for <source> to add deprecated tracking category
	 *
	 * @param ?string $text
	 * @param array $args
	 * @param Parser $parser
	 * @return string
	 */
	public static function parserHookSource( $text, $args, $parser ) {
		$parser->addTrackingCategory( 'syntaxhighlight-source-category' );
		return self::parserHook( $text, $args, $parser );
	}

	/**
	 * @return string[]
	 */
	private static function getModuleStyles(): array {
		return [ 'ext.pygments' ];
	}

	/**
	 *
	 * @param string $text
	 * @param array $args
	 * @param ?Parser $parser
	 * @return array
	 */
	private static function processContent( string $text, array $args, ?Parser $parser = null ): array {
		// Don't trim leading spaces away, just the linefeeds
		$out = rtrim( trim( $text, "\n" ) );
		$trackingCats = [];

		// Convert deprecated attributes
		if ( isset( $args['enclose'] ) ) {
			if ( $args['enclose'] === 'none' ) {
				$args['inline'] = true;
			}
			unset( $args['enclose'] );
			$trackingCats[] = 'syntaxhighlight-enclose-category';
		}

		$lexer = $args['lang'] ?? '';

		$result = self::highlight( $out, $lexer, $args, $parser );
		if ( !$result->isGood() ) {
			$trackingCats[] = 'syntaxhighlight-error-category';
		}

		return [ 'html' => $result->getValue(), 'cats' => $trackingCats ];
	}

	/**
	 * Parser hook for both <source> and <syntaxhighlight> logic
	 *
	 * @param ?string $text
	 * @param array $args
	 * @param Parser $parser
	 * @return string
	 */
	public static function parserHook( $text, $args, $parser ) {
		// Replace strip markers (For e.g. {{#tag:syntaxhighlight|<nowiki>...}})
		$out = $parser->getStripState()->unstripNoWiki( $text ?? '' );

		$result = self::processContent( $out, $args, $parser );
		foreach ( $result['cats'] as $cat ) {
			$parser->addTrackingCategory( $cat );
		}

		// Register CSS
		$parser->getOutput()->addModuleStyles( self::getModuleStyles() );
		if ( !empty( $args['linelinks'] ) ) {
			$parser->getOutput()->addModules( [ 'ext.pygments.linenumbers' ] );
		}

		return $result['html'];
	}

	/** @inheritDoc */
	public function sourceToDom(
		ParsoidExtensionAPI $extApi, string $text, array $extArgs
	): ?DocumentFragment {
		$result = self::processContent( $text, $extApi->extArgsToArray( $extArgs ) );

		// FIXME: There is no API method in Parsoid to add tracking categories
		// So, $result['cats'] is being ignored

		// Register CSS
		$extApi->addModuleStyles( self::getModuleStyles() );

		return $extApi->htmlToDom( $result['html'] );
	}

	/**
	 * Unwrap the <div> wrapper of the Pygments output
	 *
	 * @param string $out Output
	 * @return string Unwrapped output
	 */
	private static function unwrap( string $out ): string {
		if ( $out !== '' ) {
			$m = [];
			if ( preg_match( '/^<div class="?mw-highlight"?>(.*)<\/div>$/s', trim( $out ), $m ) ) {
				$out = trim( $m[1] );
			} else {
				throw new RuntimeException( 'Unexpected output from Pygments encountered' );
			}
		}
		return $out;
	}

	/**
	 * @param string $code
	 * @param bool $isInline
	 * @return string HTML
	 */
	private static function plainCodeWrap( $code, $isInline ) {
		if ( $isInline ) {
			return htmlspecialchars( $code, ENT_NOQUOTES );
		}

		return Html::rawElement(
			'div',
			[ 'class' => self::HIGHLIGHT_CSS_CLASS ],
			Html::element( 'pre', [], $code )
		);
	}

	/**
	 * @param string $code
	 * @param string|null $lang
	 * @param array $args
	 * @param Parser|null $parser Parser, if generating content to be parsed.
	 * @return Status
	 */
	private static function highlightInner( $code, $lang = null, $args = [], ?Parser $parser = null ) {
		$status = new Status;

		$lexer = self::getLexer( $lang );
		if ( $lexer === null && $lang !== null ) {
			$status->warning( 'syntaxhighlight-error-unknown-language', $lang );
		}

		// For empty tag, output nothing instead of empty <pre>.
		if ( $code === '' ) {
			$status->value = '';
			return $status;
		}

		$length = strlen( $code );
		if ( strlen( $code ) > self::getMaxBytes() ) {
			// Disable syntax highlighting
			$lexer = null;
			$status->warning(
				'syntaxhighlight-error-exceeds-size-limit',
				$length,
				self::getMaxBytes()
			);
		}

		$isInline = isset( $args['inline'] );
		$showLines = isset( $args['line'] );

		if ( $isInline ) {
			$code = trim( $code );
		}

		if ( $lexer === null ) {
			// When syntax highlighting is disabled..
			$status->value = self::plainCodeWrap( $code, $isInline );
			return $status;
		}

		if ( $parser && !$parser->incrementExpensiveFunctionCount() ) {
			// Highlighting is expensive, return unstyled
			$status->value = self::plainCodeWrap( $code, $isInline );
			return $status;
		}

		$options = [
			'cssclass' => self::HIGHLIGHT_CSS_CLASS,
			'encoding' => 'utf-8',
		];

		// Line numbers
		if ( $showLines ) {
			$options['linenos'] = 'inline';
		}

		if ( $lexer === 'php' && strpos( $code, '<?php' ) === false ) {
			$options['startinline'] = 1;
		}

		// Highlight specific lines
		if ( isset( $args['highlight'] ) ) {
			$lines = self::parseHighlightLines( $args['highlight'] );
			if ( count( $lines ) ) {
				$options['hl_lines'] = implode( ' ', $lines );
			}
		}

		// Starting line number
		if ( isset( $args['start'] ) && ctype_digit( $args['start'] ) ) {
			$options['linenostart'] = (int)$args['start'];
		}

		if ( !empty( $args['linelinks'] ) ) {
			$options['linespans'] = $args['linelinks'];
		}

		if ( $isInline ) {
			$options['nowrap'] = 1;
		}

		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$error = null;
		$output = $cache->getWithSetCallback(
			$cache->makeGlobalKey( 'highlight', self::makeCacheKeyHash( $code, $lexer, $options ) ),
			$cache::TTL_MONTH,
			static function ( $oldValue, &$ttl ) use ( $code, $lexer, $options, &$error ) {
				try {
					return Pygmentize::highlight( $lexer, $code, $options );
				} catch ( PygmentsException $e ) {
					$ttl = WANObjectCache::TTL_UNCACHEABLE;
					$error = $e->getMessage();
					return null;
				}
			}
		);

		if ( $error !== null || $output === null ) {
			$status->warning( 'syntaxhighlight-error-pygments-invocation-failure' );
			if ( $error !== null ) {
				wfWarn( 'Failed to invoke Pygments: ' . $error );
			} else {
				wfWarn( 'Invoking Pygments returned blank output with no error response' );
			}

			// Fall back to preformatted code without syntax highlighting
			$output = self::plainCodeWrap( $code, $isInline );
		}

		$status->value = $output;

		return $status;
	}

	/**
	 * Highlight a code-block using a particular lexer.
	 *
	 * This produces raw HTML (wrapped by Status), the caller is responsible
	 * for making sure the "ext.pygments" module is loaded in the output.
	 *
	 * @param string $code Code to highlight.
	 * @param string|null $lang Language name, or null to use plain markup.
	 * @param array $args Associative array of additional arguments.
	 *  If it contains a 'line' key, the output will include line numbers.
	 *  If it includes a 'highlight' key, the value will be parsed as a
	 *   comma-separated list of lines and line-ranges to highlight.
	 *  If it contains a 'start' key, the value will be used as the line at which to
	 *   start highlighting.
	 *  If it contains a 'inline' key, the output will not be wrapped in `<div><pre/></div>`.
	 *  If it contains a 'linelinks' key, lines will have links and anchors with a prefix
	 *   of the value. Similar to the lineanchors+linespans features in Pygments.
	 * @param Parser|null $parser Parser, if generating content to be parsed.
	 * @return Status Status object, with HTML representing the highlighted
	 *  code as its value.
	 */
	public static function highlight( $code, $lang = null, $args = [], ?Parser $parser = null ) {
		$status = self::highlightInner( $code, $lang, $args, $parser );
		$output = $status->getValue();

		$isInline = isset( $args['inline'] );
		$showLines = isset( $args['line'] );
		$lexer = self::getLexer( $lang );

		// Post-Pygment HTML transformations.

		if ( $showLines ) {
			$lineReplace = Html::element( 'span', [ 'class' => 'linenos', 'data-line' => '$1' ] );
			if ( !empty( $args['linelinks'] ) ) {
				$lineReplace = Html::rawElement(
					'a',
					[ 'href' => '#' . $args['linelinks'] . '-$1' ],
					$lineReplace
				);
			}
			// Convert line numbers to data attributes so they
			// can be displayed as CSS generated content and be
			// unselectable in all browsers.
			$output = preg_replace(
				'`<span class="linenos">\s*([^<]*)\s*</span>`',
				$lineReplace,
				$output
			);
		}

		// Allow certain HTML attributes
		$htmlAttribs = Sanitizer::validateAttributes(
			$args, array_flip( [ 'style', 'class', 'id' ] )
		);

		$dir = ( isset( $args['dir'] ) && $args['dir'] === 'rtl' ) ? 'rtl' : 'ltr';

		// Build class list
		$classList = [];
		if ( isset( $htmlAttribs['class'] ) ) {
			$classList[] = $htmlAttribs['class'];
		}
		$classList[] = self::HIGHLIGHT_CSS_CLASS;
		if ( $lexer !== null ) {
			$classList[] = self::HIGHLIGHT_CSS_CLASS . '-lang-' . $lexer;
		}
		$classList[] = 'mw-content-' . $dir;
		if ( $showLines ) {
			$classList[] = self::HIGHLIGHT_CSS_CLASS . '-lines';
		}
		$htmlAttribs['class'] = implode( ' ', $classList );
		$htmlAttribs['dir'] = $dir;
		'@phan-var array{class:string,dir:string} $htmlAttribs';

		if ( $isInline ) {
			// We've already trimmed the input $code before highlighting,
			// but pygment's standard out adds a line break afterwards,
			// which would then be preserved in the paragraph that wraps this,
			// and become visible as a space. Avoid that.
			$output = trim( $output );

			// Enforce inlineness. Stray newlines may result in unexpected list and paragraph processing
			// (also known as doBlockLevels()).
			$output = str_replace( "\n", ' ', $output );
			$output = Html::rawElement( 'code', $htmlAttribs, $output );
		} else {
			$output = self::unwrap( $output );

			if ( $parser ) {
				// Use 'nowiki' strip marker to prevent list processing (also known as doBlockLevels()).
				// However, leave the wrapping <div/> outside to prevent <p/>-wrapping.
				$marker = $parser::MARKER_PREFIX . '-syntaxhighlightinner-' .
					sprintf( '%08X', $parser->mMarkerIndex++ ) . $parser::MARKER_SUFFIX;
				$parser->getStripState()->addNoWiki( $marker, $output );
				$output = $marker;
			}

			$output = Html::openElement( 'div', $htmlAttribs ) .
				$output .
				Html::closeElement( 'div' );
		}

		$status->value = $output;
		return $status;
	}

	/**
	 * Construct a cache key for the results of a Pygments invocation.
	 *
	 * @param string $code Code to be highlighted.
	 * @param string $lexer Lexer name.
	 * @param array $options Options array.
	 * @return string Cache key.
	 */
	private static function makeCacheKeyHash( $code, $lexer, $options ) {
		$optionString = FormatJson::encode( $options, false, FormatJson::ALL_OK );
		return md5( "{$code}|{$lexer}|{$optionString}|" . self::CACHE_VERSION );
	}

	/**
	 * Take an input specifying a list of lines to highlight, returning
	 * a raw list of matching line numbers.
	 *
	 * Input is comma-separated list of lines or line ranges.
	 *
	 * @param string $lineSpec
	 * @return int[] Line numbers.
	 */
	protected static function parseHighlightLines( $lineSpec ) {
		$lines = [];
		$values = array_map( 'trim', explode( ',', $lineSpec ) );
		foreach ( $values as $value ) {
			if ( ctype_digit( $value ) ) {
				$lines[] = (int)$value;
			} elseif ( strpos( $value, '-' ) !== false ) {
				[ $start, $end ] = array_map( 'intval', explode( '-', $value ) );
				if ( self::validHighlightRange( $start, $end ) ) {
					for ( $i = $start; $i <= $end; $i++ ) {
						$lines[] = $i;
					}
				}
			}
			if ( count( $lines ) > self::getMaxLines() ) {
				$lines = array_slice( $lines, 0, self::getMaxLines() );
				break;
			}
		}
		return $lines;
	}

	/**
	 * Validate a provided input range
	 * @param int $start
	 * @param int $end
	 * @return bool
	 */
	protected static function validHighlightRange( $start, $end ) {
		// Since we're taking this tiny range and producing a an
		// array of every integer between them, it would be trivial
		// to DoS the system by asking for a huge range.
		// Impose an arbitrary limit on the number of lines in a
		// given range to reduce the impact.
		return $start > 0 &&
			$start < $end &&
			$end - $start < self::getMaxLines();
	}

	/**
	 * Hook into Content::getParserOutput to provide syntax highlighting for
	 * script content.
	 *
	 * @param Content $content
	 * @param Title $title
	 * @param int $revId
	 * @param ParserOptions $options
	 * @param bool $generateHtml
	 * @param ParserOutput &$parserOutput
	 * @return bool
	 * @since MW 1.21
	 */
	public function onContentGetParserOutput( $content, $title,
		$revId, $options, $generateHtml, &$parserOutput
	) {
		global $wgTextModelsToParse;

		// Hope that the "SyntaxHighlightModels" attribute does not contain silly types.
		if ( !( $content instanceof TextContent ) ) {
			// Oops! Non-text content? Let MediaWiki handle this.
			return true;
		}

		if ( !$generateHtml ) {
			// Nothing special for us to do, let MediaWiki handle this.
			return true;
		}

		// Determine the SyntaxHighlight language from the page's
		// content model. Extensions can extend the default CSS/JS
		// mapping by setting the SyntaxHighlightModels attribute.
		$extension = ExtensionRegistry::getInstance();
		$models = $extension->getAttribute( 'SyntaxHighlightModels' ) + [
			CONTENT_MODEL_CSS => 'css',
			CONTENT_MODEL_JAVASCRIPT => 'javascript',
		];
		$model = $content->getModel();
		if ( !isset( $models[$model] ) ) {
			// We don't care about this model, carry on.
			return true;
		}
		$lexer = $models[$model];
		$text = $content->getText();

		// Parse using the standard parser to get links etc. into the database, HTML is replaced below.
		// We could do this using $content->fillParserOutput(), but alas it is 'protected'.
		if ( in_array( $model, $wgTextModelsToParse, true ) ) {
			$parserOutput = MediaWikiServices::getInstance()->getParser()
				->parse( $text, $title, $options, true, true, $revId );
		}

		$status = self::highlight( $text, $lexer, [ 'line' => true, 'linelinks' => 'L' ] );
		if ( !$status->isOK() ) {
			return true;
		}
		$out = $status->getValue();

		$parserOutput->addModuleStyles( self::getModuleStyles() );
		$parserOutput->addModules( [ 'ext.pygments.linenumbers' ] );
		$parserOutput->setText( $out );

		// Inform MediaWiki that we have parsed this page and it shouldn't mess with it.
		return false;
	}

	/**
	 * Hook to provide syntax highlighting for API pretty-printed output
	 *
	 * @param IContextSource $context
	 * @param string $text
	 * @param string $mime
	 * @param string $format
	 * @since MW 1.24
	 * @return bool
	 */
	public function onApiFormatHighlight( $context, $text, $mime, $format ) {
		if ( !isset( self::$mimeLexers[$mime] ) ) {
			return true;
		}

		$lexer = self::$mimeLexers[$mime];
		$status = self::highlight( $text, $lexer );
		if ( !$status->isOK() ) {
			return true;
		}

		$out = $status->getValue();
		if ( preg_match( '/^<pre([^>]*)>/i', $out, $m ) ) {
			$attrs = Sanitizer::decodeTagAttributes( $m[1] );
			$attrs['class'] .= ' api-pretty-content';
			$encodedAttrs = Sanitizer::safeEncodeTagAttributes( $attrs );
			$out = '<pre' . $encodedAttrs . '>' . substr( $out, strlen( $m[0] ) );
		}
		$output = $context->getOutput();
		$output->addModuleStyles( self::getModuleStyles() );
		$output->addHTML( '<div dir="ltr">' . $out . '</div>' );

		// Inform MediaWiki that we have parsed this page and it shouldn't mess with it.
		return false;
	}

	/**
	 * Hook to add Pygments version to Special:Version
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SoftwareInfo
	 * @param array &$software
	 */
	public function onSoftwareInfo( &$software ) {
		try {
			$software['[https://pygments.org/ Pygments]'] = Pygmentize::getVersion();
		} catch ( PygmentsException $e ) {
			// pass
		}
	}
}
