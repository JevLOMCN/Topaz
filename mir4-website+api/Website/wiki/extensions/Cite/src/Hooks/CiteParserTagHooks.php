<?php

namespace Cite\Hooks;

use Cite\Cite;
use Parser;
use PPFrame;

/**
 * @license GPL-2.0-or-later
 */
class CiteParserTagHooks {

	/**
	 * Enables the two <ref> and <references> tags.
	 */
	public static function register( Parser $parser ): void {
		$parser->setHook( 'ref', [ __CLASS__, 'ref' ] );
		$parser->setHook( 'references', [ __CLASS__, 'references' ] );
	}

	/**
	 * Parser hook for the <ref> tag.
	 *
	 * @param ?string $text Raw, untrimmed wikitext content of the <ref> tag, if any
	 * @param string[] $argv
	 * @param Parser $parser
	 * @param PPFrame $frame
	 *
	 * @return string HTML
	 */
	public static function ref(
		?string $text,
		array $argv,
		Parser $parser,
		PPFrame $frame
	): string {
		$cite = self::citeForParser( $parser );
		$result = $cite->ref( $parser, $text, $argv );

		if ( $result === null ) {
			return htmlspecialchars( "<ref>$text</ref>" );
		}

		$parserOutput = $parser->getOutput();
		$parserOutput->addModules( [ 'ext.cite.ux-enhancements' ] );
		$parserOutput->addModuleStyles( [ 'ext.cite.styles' ] );

		$frame->setVolatile();
		return $result;
	}

	/**
	 * Parser hook for the <references> tag.
	 *
	 * @param ?string $text Raw, untrimmed wikitext content of the <references> tag, if any
	 * @param string[] $argv
	 * @param Parser $parser
	 * @param PPFrame $frame
	 *
	 * @return string HTML
	 */
	public static function references(
		?string $text,
		array $argv,
		Parser $parser,
		PPFrame $frame
	): string {
		$cite = self::citeForParser( $parser );
		$result = $cite->references( $parser, $text, $argv );

		if ( $result === null ) {
			return htmlspecialchars( $text === null
				? "<references/>"
				: "<references>$text</references>"
			);
		}

		$frame->setVolatile();
		return $result;
	}

	/**
	 * Get or create Cite state for this parser.
	 */
	private static function citeForParser( Parser $parser ): Cite {
		$parser->extCite ??= new Cite( $parser );
		return $parser->extCite;
	}

}
