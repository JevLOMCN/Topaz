<?php

namespace MediaWiki\Extension\Math;

use DataValues\StringValue;
use InvalidArgumentException;
use MediaWiki\Html\Html;
use ValueFormatters\ValueFormatter;
use Wikibase\Lib\Formatters\SnakFormatter;

/**
 * Formats the tex string based on the known formats
 * - text/plain: used in the value input field of Wikidata
 * - text/x-wiki: wikitext
 * - text/html: used in Wikidata to display the value of properties
 * Formats can look like this: "text/html; disposition=diff"
 * or just "text/plain"
 */

class MathFormatter implements ValueFormatter {

	/**
	 * @var string One of the SnakFormatter::FORMAT_... constants.
	 */
	private $format;

	/**
	 * Loads format to distinguish the type of formatting
	 *
	 * @param string $format One of the SnakFormatter::FORMAT_... constants.
	 */
	public function __construct( $format ) {
		$this->format = $format;
	}

	/**
	 * @param StringValue $value
	 *
	 * @throws InvalidArgumentException if not called with a StringValue
	 * @return string
	 */
	public function format( $value ) {
		if ( !( $value instanceof StringValue ) ) {
			throw new InvalidArgumentException( '$value must be a StringValue' );
		}

		$tex = $value->getValue();

		switch ( $this->format ) {
			case SnakFormatter::FORMAT_PLAIN:
				return $tex;
			case SnakFormatter::FORMAT_WIKI:
				return "<math>$tex</math>";

			// Intentionally fall back to MathML output in all other, possibly unknown cases.
			default:
				$renderer = new MathMathML( $tex );

				if ( $renderer->checkTeX() && $renderer->render() ) {
					$html = $renderer->getHtmlOutput();
				} else {
					$html = $renderer->getLastError();
				}

				if ( $this->format === SnakFormatter::FORMAT_HTML_DIFF ) {
					$html = $this->formatDetails( $html, $tex );
				}

				return $html;
		}
	}

	/**
	 * Constructs a detailed HTML rendering for use in diff views.
	 *
	 * @param string $valueHtml
	 * @param string $tex
	 *
	 * @return string HTML
	 */
	private function formatDetails( $valueHtml, $tex ) {
		$html = Html::rawElement( 'h4',
			[ 'class' => 'wb-details wb-math-details wb-math-rendered' ],
			$valueHtml
		);

		$html .= Html::rawElement( 'div',
			[ 'class' => 'wb-details wb-math-details' ],
			Html::element( 'code', [], $tex )
		);

		return $html;
	}

	/**
	 * @return string One of the SnakFormatter::FORMAT_... constants.
	 */
	public function getFormat() {
		return $this->format;
	}

}
