<?php

namespace Wikimedia\RemexHtml\TreeBuilder;

use Wikimedia\RemexHtml\HTMLData;
use Wikimedia\RemexHtml\Tokenizer\Attributes;

/**
 * The rules for parsing tokens in foreign content.
 *
 * This is not referred to as an insertion mode in the spec, but is
 * sufficiently similar to one that we can inherit from InsertionMode here.
 */
class InForeignContent extends InsertionMode {
	/**
	 * The list of tag names which unconditionally generate a parse error when
	 * seen in foreign content.
	 * @var array<string,bool>
	 */
	private static $notAllowed = [
		'b' => true,
		'big' => true,
		'blockquote' => true,
		'body' => true,
		'br' => true,
		'center' => true,
		'code' => true,
		'dd' => true,
		'div' => true,
		'dl' => true,
		'dt' => true,
		'em' => true,
		'embed' => true,
		'h1' => true,
		'h2' => true,
		'h3' => true,
		'h4' => true,
		'h5' => true,
		'h6' => true,
		'head' => true,
		'hr' => true,
		'i' => true,
		'img' => true,
		'li' => true,
		'listing' => true,
		'menu' => true,
		'meta' => true,
		'nobr' => true,
		'ol' => true,
		'p' => true,
		'pre' => true,
		'ruby' => true,
		's' => true,
		'small' => true,
		'span' => true,
		'strong' => true,
		'strike' => true,
		'sub' => true,
		'sup' => true,
		'table' => true,
		'tt' => true,
		'u' => true,
		'ul' => true,
		'var' => true,
	];

	/**
	 * The table for correcting the tag names of SVG elements, given in the
	 * "Any other start tag" section of the spec.
	 * @var array<string,string>
	 */
	private static $svgElementCase = [
		'altglyph' => 'altGlyph',
		'altglyphdef' => 'altGlyphDef',
		'altglyphitem' => 'altGlyphItem',
		'animatecolor' => 'animateColor',
		'animatemotion' => 'animateMotion',
		'animatetransform' => 'animateTransform',
		'clippath' => 'clipPath',
		'feblend' => 'feBlend',
		'fecolormatrix' => 'feColorMatrix',
		'fecomponenttransfer' => 'feComponentTransfer',
		'fecomposite' => 'feComposite',
		'feconvolvematrix' => 'feConvolveMatrix',
		'fediffuselighting' => 'feDiffuseLighting',
		'fedisplacementmap' => 'feDisplacementMap',
		'fedistantlight' => 'feDistantLight',
		'fedropshadow' => 'feDropShadow',
		'feflood' => 'feFlood',
		'fefunca' => 'feFuncA',
		'fefuncb' => 'feFuncB',
		'fefuncg' => 'feFuncG',
		'fefuncr' => 'feFuncR',
		'fegaussianblur' => 'feGaussianBlur',
		'feimage' => 'feImage',
		'femerge' => 'feMerge',
		'femergenode' => 'feMergeNode',
		'femorphology' => 'feMorphology',
		'feoffset' => 'feOffset',
		'fepointlight' => 'fePointLight',
		'fespecularlighting' => 'feSpecularLighting',
		'fespotlight' => 'feSpotLight',
		'fetile' => 'feTile',
		'feturbulence' => 'feTurbulence',
		'foreignobject' => 'foreignObject',
		'glyphref' => 'glyphRef',
		'lineargradient' => 'linearGradient',
		'radialgradient' => 'radialGradient',
		'textpath' => 'textPath',
	];

	public function characters( $text, $start, $length, $sourceStart, $sourceLength ) {
		$builder = $this->builder;

		while ( $length ) {
			$normalLength = strcspn( $text, "\0\t\n\f\r ", $start, $length );
			if ( $normalLength ) {
				$builder->framesetOK = false;
				$builder->insertCharacters( $text, $start, $normalLength,
					$sourceStart, $sourceLength );
			}
			$start += $normalLength;
			$length -= $normalLength;
			$sourceStart += $normalLength;
			$sourceLength -= $normalLength;
			if ( !$length ) {
				break;
			}

			$char = $text[$start];
			if ( $char === "\0" ) {
				$builder->error( "replaced null character", $sourceStart );
				$builder->insertCharacters( "\xef\xbf\xbd", 0, 3, $sourceStart, $sourceLength );
				$start++;
				$length--;
				$sourceStart++;
				$sourceLength--;
			} else {
				// Whitespace
				$wsLength = strspn( $text, "\t\n\f\r ", $start, $length );
				$builder->insertCharacters( $text, $start, $wsLength, $sourceStart, $wsLength );
				$start += $wsLength;
				$length -= $wsLength;
				$sourceStart += $wsLength;
				$sourceLength -= $wsLength;
			}
		}
	}

	private function isIntegrationPoint( Element $element ) {
		return $element->namespace === HTMLData::NS_HTML
			|| $element->isMathmlTextIntegration()
			|| $element->isHtmlIntegration();
	}

	public function startTag( $name, Attributes $attrs, $selfClose, $sourceStart, $sourceLength ) {
		$builder = $this->builder;
		$stack = $builder->stack;
		$dispatcher = $this->dispatcher;

		if ( isset( self::$notAllowed[$name] ) ) {
			$allowed = false;
		} elseif ( $name === 'font' && (
			isset( $attrs['color'] ) || isset( $attrs['face'] ) || isset( $attrs['size'] ) )
		) {
			$allowed = false;
		} else {
			$allowed = true;
		}

		if ( !$allowed ) {
			$builder->error( "unexpected <$name> tag in foreign content", $sourceStart );
			if ( !$builder->isFragment ) {
				do {
					$builder->pop( $sourceStart, 0 );
				} while ( $stack->current && !$this->isIntegrationPoint( $stack->current ) );
				$dispatcher->startTag( $name, $attrs, $selfClose, $sourceStart, $sourceLength );
				return;
			}
		}

		$acnNs = $builder->adjustedCurrentNode()->namespace;
		if ( $acnNs === HTMLData::NS_MATHML ) {
			$attrs = new ForeignAttributes( $attrs, 'math' );
		} elseif ( $acnNs === HTMLData::NS_SVG ) {
			$attrs = new ForeignAttributes( $attrs, 'svg' );
			$name = self::$svgElementCase[$name] ?? $name;
		} else {
			$attrs = new ForeignAttributes( $attrs, 'other' );
		}
		$dispatcher->ack = true;
		$builder->insertForeign( $acnNs, $name, $attrs, $selfClose, $sourceStart, $sourceLength );
	}

	public function endTag( $name, $sourceStart, $sourceLength ) {
		$builder = $this->builder;
		$stack = $builder->stack;
		$dispatcher = $this->dispatcher;

		$node = $stack->current;
		if ( strcasecmp( $node->name, $name ) !== 0 ) {
			$builder->error( "mismatched end tag in foreign content", $sourceStart );
		}
		for ( $idx = $stack->length() - 1; $idx > 0; $idx-- ) {
			if ( strcasecmp( $node->name, $name ) === 0 ) {
				$builder->popAllUpToElement( $node, $sourceStart, $sourceLength );
				break;
			}
			$node = $stack->item( $idx - 1 );
			if ( $node->namespace === HTMLData::NS_HTML ) {
				$dispatcher->getHandler()->endTag( $name, $sourceStart, $sourceLength );
				break;
			}
		}
	}

	public function endDocument( $pos ) {
		// @phan-suppress-previous-line PhanPluginNeverReturnMethod
		throw new TreeBuilderError( "unspecified, presumed unreachable" );
	}
}
