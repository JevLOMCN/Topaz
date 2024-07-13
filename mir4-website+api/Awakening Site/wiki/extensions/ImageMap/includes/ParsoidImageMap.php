<?php
declare( strict_types = 1 );

namespace MediaWiki\Extension\ImageMap;

use DOMNode;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\Ext\DOMDataUtils;
use Wikimedia\Parsoid\Ext\DOMUtils;
use Wikimedia\Parsoid\Ext\ExtensionError;
use Wikimedia\Parsoid\Ext\ExtensionModule;
use Wikimedia\Parsoid\Ext\ExtensionTagHandler;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;
use Wikimedia\Parsoid\Ext\WTUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;

/**
 * This is an adaptation of the existing ImageMap extension of the legacy
 * parser.
 *
 * Syntax:
 * <imagemap>
 * Image:Foo.jpg | 100px | picture of a foo
 *
 * rect    0  0  50 50  [[Foo type A]]
 * circle  50 50 20     [[Foo type B]]
 *
 * desc bottom-left
 * </imagemap>
 *
 * Coordinates are relative to the source image, not the thumbnail.
 */

class ParsoidImageMap extends ExtensionTagHandler implements ExtensionModule {

	private const TOP_RIGHT = 0;
	private const BOTTOM_RIGHT = 1;
	private const BOTTOM_LEFT = 2;
	private const TOP_LEFT = 3;
	private const NONE = 4;

	private const DESC_TYPE_MAP = [
		'top-right', 'bottom-right', 'bottom-left', 'top-left'
	];

	/** @inheritDoc */
	public function getConfig(): array {
		return [
			'name' => 'ImageMap',
			'tags' => [
				[
					'name' => 'imagemap',
					'handler' => self::class,
					'options' => [
						'outputHasCoreMwDomSpecMarkup' => true
					],
				]
			]
		];
	}

	/** @inheritDoc */
	public function sourceToDom(
		ParsoidExtensionAPI $extApi, string $src, array $extArgs
	): DocumentFragment {
		$domFragment = $extApi->getTopLevelDoc()->createDocumentFragment();

		$thumb = null;
		$anchor = null;
		$imageNode = null;
		$mapHTML = null;

		// Define canonical desc types to allow i18n of 'imagemap_desc_types'
		$descTypesCanonical = 'top-right, bottom-right, bottom-left, top-left, none';
		$descType = self::BOTTOM_RIGHT;

		$scale = 1;
		$lineNum = 0;
		$first = true;
		$defaultLinkAttribs = null;

		$nextOffset = $extApi->extTag->getOffsets()->innerStart();

		$lines = explode( "\n", $src );

		foreach ( $lines as $line ) {
			++$lineNum;

			$offset = $nextOffset;
			// +1 for the nl
			$nextOffset = $offset + strlen( $line ) + 1;
			$offset += strlen( $line ) - strlen( ltrim( $line ) );

			$line = trim( $line );

			if ( $line == '' || $line[0] == '#' ) {
				continue;
			}

			if ( $first ) {
				$first = false;

				// The first line should have an image specification on it
				// Extract it and render the HTML
				$bits = explode( '|', $line, 2 );
				if ( count( $bits ) == 1 ) {
					$image = $bits[0];
					$options = '';
				} else {
					[ $image, $options ] = $bits;
					$options = '|' . $options;
				}

				$imageOpts = [
					[ $options, $offset + strlen( $image ) ],
				];

				$thumb = $extApi->renderMedia(
					$image, $imageOpts, $error,
					// NOTE(T290044): Imagemaps are always rendered as blocks
					true
				);
				if ( !$thumb ) {
					throw new ExtensionError( $error );
				}

				$anchor = $thumb->firstChild;
				$imageNode = $anchor->firstChild;

				// Could be a span
				if ( DOMCompat::nodeName( $imageNode ) !== 'img' ) {
					throw new ExtensionError( 'imagemap_invalid_image' );
				}
				DOMUtils::assertElt( $imageNode );

				// Add the linear dimensions to avoid inaccuracy in the scale
				// factor when one is much larger than the other
				// (sx+sy)/(x+y) = s

				$thumbWidth = (int)( $imageNode->getAttribute( 'width' ) );
				$thumbHeight = (int)( $imageNode->getAttribute( 'height' ) );
				$imageWidth = (int)( $imageNode->getAttribute( 'data-file-width' ) );
				$imageHeight = (int)( $imageNode->getAttribute( 'data-file-height' ) );

				$denominator = $imageWidth + $imageHeight;
				$numerator = $thumbWidth + $thumbHeight;
				if ( $denominator <= 0 || $numerator <= 0 ) {
					throw new ExtensionError( 'imagemap_invalid_image' );
				}
				$scale = $numerator / $denominator;
				continue;
			}

			// Handle desc spec
			$cmd = strtok( $line, " \t" );
			if ( $cmd == 'desc' ) {
				$typesText = wfMessage( 'imagemap_desc_types' )->inContentLanguage()->text();
				if ( $descTypesCanonical != $typesText ) {
					// i18n desc types exists
					$typesText = $descTypesCanonical . ', ' . $typesText;
				}
				$types = array_map( 'trim', explode( ',', $typesText ) );
				$type = trim( strtok( '' ) ?: '' );
				$descType = array_search( $type, $types, true );
				if ( $descType > 4 ) {
					// A localized descType is used. Subtract 5 to reach the canonical desc type.
					$descType -= 5;
				}
				// <0? In theory never, but paranoia...
				if ( $descType === false || $descType < 0 ) {
					throw new ExtensionError( 'imagemap_invalid_desc', $typesText );
				}
				continue;
			}

			// Find the link

			$link = trim( strstr( $line, '[' ) ?: '' );
			if ( !$link ) {
				throw new ExtensionError( 'imagemap_no_link', $lineNum );
			}

			// FIXME: Omits DSR offsets, which will be more relevant when VE
			// supports HTML editing of maps.

			$linkFragment = $extApi->wikitextToDOM(
				$link,
				[
					'parseOpts' => [
						'extTag' => 'imagemap',
						'context' => 'inline',
					],
					// Create new frame, because $link doesn't literally
					// appear on the page, it has been hand-crafted here
					'processInNewFrame' => true
				],
				// sol
				true
			);
			$a = DOMCompat::querySelector( $linkFragment, 'a' );
			if ( $a == null ) {
				// Meh, might be for other reasons
				throw new ExtensionError( 'imagemap_invalid_title', $lineNum );
			}
			DOMUtils::assertElt( $a );

			$href = $a->getAttribute( 'href' );
			$externLink = DOMUtils::matchRel( $a, '#^mw:ExtLink#D' ) !== null;
			$alt = '';

			$hasContent = $externLink || ( DOMDataUtils::getDataParsoid( $a )->stx ?? null ) === 'piped';

			if ( $hasContent ) {
				// FIXME: The legacy extension does ad hoc link parsing, which
				// results in link content not interpreting wikitext syntax.
				// Here we produce a known difference by just taking the text
				// content of the resulting dom.
				// See the test, "Link with wikitext syntax in content"
				$alt = trim( $a->textContent );
			}

			$shapeSpec = substr( $line, 0, -strlen( $link ) );

			// Tokenize shape spec
			$shape = strtok( $shapeSpec, " \t" );
			switch ( $shape ) {
				case 'default':
					$coords = [];
					break;
				case 'rect':
					$coords = self::tokenizeCoords( $lineNum, 4 );
					break;
				case 'circle':
					$coords = self::tokenizeCoords( $lineNum, 3 );
					break;
				case 'poly':
					$coords = self::tokenizeCoords( $lineNum, 1, true );
					if ( count( $coords ) % 2 !== 0 ) {
						throw new ExtensionError( 'imagemap_poly_odd', $lineNum );
					}
					break;
				default:
					$coords = [];
					throw new ExtensionError( 'imagemap_unrecognised_shape', $lineNum );
			}

			// Scale the coords using the size of the source image
			foreach ( $coords as $i => $c ) {
				$coords[$i] = (int)round( $c * $scale );
			}

			// Construct the area tag

			$attribs = [ 'href' => $href ];
			if ( $externLink ) {
				$attribs['class'] = 'plainlinks';
				// The AddLinkAttributes pass isn't run on nested pipelines
				// so $a doesn't have rel/target attributes to copy over
				$extLinkAttribs = $extApi->getExternalLinkAttribs( $href );
				if ( isset( $extLinkAttribs['rel'] ) ) {
					$attribs['rel'] = implode( ' ', $extLinkAttribs['rel'] );
				}
				if ( isset( $extLinkAttribs['target'] ) ) {
					$attribs['target'] = $extLinkAttribs['target'];
				}
			}
			if ( $shape != 'default' ) {
				$attribs['shape'] = $shape;
			}
			if ( $coords ) {
				$attribs['coords'] = implode( ',', $coords );
			}
			if ( $alt != '' ) {
				if ( $shape != 'default' ) {
					$attribs['alt'] = $alt;
				}
				$attribs['title'] = $alt;
			}
			if ( $shape == 'default' ) {
				$defaultLinkAttribs = $attribs;
			} else {
				if ( $mapHTML == null ) {
					$mapHTML = $domFragment->ownerDocument->createElement( 'map' );
				}
				$area = $domFragment->ownerDocument->createElement( 'area' );
				foreach ( $attribs as $key => $val ) {
					$area->setAttribute( $key, $val );
				}
				$mapHTML->appendChild( $area );
			}
		}

		// Ugh! This is messy.
		// The proxy classes aren't visible to phan here.
		// Maybe we should get rid of those since we are unlikely
		// to go the Dodo route since there is a proposal to introduce
		// a HTML5 parsing and updated DOM library in newer PHP versions.
		//
		// Help out phan since it doesn't seem to be able to look
		// at the definitions in vendor?
		'@phan-var Element $thumb';
		'@phan-var DOMNode $anchor';
		'@phan-var Element $imageNode';

		if ( $first ) {
			throw new ExtensionError( 'imagemap_no_image' );
		}

		if ( $mapHTML != null ) {
			// Construct the map

			// Add a hash of the map HTML to avoid breaking cached HTML fragments that are
			// later joined together on the one page (T18471).
			// The only way these hashes can clash is if the map is identical, in which
			// case it wouldn't matter that the "wrong" map was used.
			$mapName = 'ImageMap_' . substr( md5( DOMCompat::getInnerHTML( $mapHTML ) ), 0, 16 );
			$mapHTML->setAttribute( 'name', $mapName );

			// Alter the image tag
			$imageNode->setAttribute( 'usemap', "#$mapName" );

			$thumb->insertBefore( $mapHTML, $imageNode->parentNode->nextSibling );
		}

		// For T22030
		DOMCompat::getClassList( $thumb )->add( 'noresize' );

		// Determine whether a "magnify" link is present
		$typeOf = $thumb->getAttribute( 'typeof' );
		if ( !preg_match( '#\bmw:File/Thumb\b#', $typeOf ) && $descType !== self::NONE ) {
			// The following classes are used here:
			// * mw-ext-imagemap-desc-top-right
			// * mw-ext-imagemap-desc-bottom-right
			// * mw-ext-imagemap-desc-bottom-left
			// * mw-ext-imagemap-desc-top-left
			DOMCompat::getClassList( $thumb )->add(
				'mw-ext-imagemap-desc-' . self::DESC_TYPE_MAP[$descType]
			);
		}

		if ( $defaultLinkAttribs ) {
			$defaultAnchor = $domFragment->ownerDocument->createElement( 'a' );
			foreach ( $defaultLinkAttribs as $name => $value ) {
				$defaultAnchor->setAttribute( $name, $value );
			}
		} else {
			$defaultAnchor = $domFragment->ownerDocument->createElement( 'span' );
		}
		$defaultAnchor->appendChild( $imageNode );
		$thumb->replaceChild( $defaultAnchor, $anchor );

		if ( !WTUtils::hasVisibleCaption( $thumb ) ) {
			$caption = DOMCompat::querySelector( $thumb, 'figcaption' );
			$captionText = trim( $caption->textContent );
			if ( $captionText ) {
				$defaultAnchor->setAttribute( 'title', $captionText );
			}
		}

		$extApi->getMetadata()->addModules( $this->getModules() );
		$extApi->getMetadata()->addModuleStyles( $this->getModuleStyles() );

		$domFragment->appendChild( $thumb );
		return $domFragment;
	}

	/**
	 * @param int $lineNum Line number, for error reporting
	 * @param int $minCount Minimum token count
	 * @param bool $allowNegative
	 * @return array Array of coordinates
	 * @throws ExtensionError
	 */
	private static function tokenizeCoords(
		int $lineNum, int $minCount = 0, $allowNegative = false
	) {
		$coords = [];
		$coord = strtok( " \t" );
		while ( $coord !== false ) {
			if ( !is_numeric( $coord ) || $coord > 1e9 || ( !$allowNegative && $coord < 0 ) ) {
				throw new ExtensionError( 'imagemap_invalid_coord', $lineNum );
			}
			$coords[] = $coord;
			$coord = strtok( " \t" );
		}
		if ( count( $coords ) < $minCount ) {
			// TODO: Should this also check there aren't too many coords?
			throw new ExtensionError( 'imagemap_missing_coord', $lineNum );
		}
		return $coords;
	}

	/**
	 * @return array
	 */
	public function getModules(): array {
		return [ 'ext.imagemap' ];
	}

	/**
	 * @return array
	 */
	public function getModuleStyles(): array {
		return [ 'ext.imagemap.styles' ];
	}

}
