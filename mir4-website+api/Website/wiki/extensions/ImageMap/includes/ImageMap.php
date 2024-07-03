<?php
/**
 * Main file for extension ImageMap.
 *
 * @file
 * @ingroup Extensions
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

namespace MediaWiki\Extension\ImageMap;

use DOMDocumentFragment;
use DOMElement;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;
use MediaWiki\Parser\Sanitizer;
use MediaWiki\Title\Title;
use Parser;
use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\Ext\WTUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Xml;

class ImageMap implements ParserFirstCallInitHook {

	private const TOP_RIGHT = 0;
	private const BOTTOM_RIGHT = 1;
	private const BOTTOM_LEFT = 2;
	private const TOP_LEFT = 3;
	private const NONE = 4;

	private const DESC_TYPE_MAP = [
		'top-right', 'bottom-right', 'bottom-left', 'top-left'
	];

	/**
	 * @param Parser $parser
	 */
	public function onParserFirstCallInit( $parser ) {
		$parser->setHook( 'imagemap', [ $this, 'render' ] );
	}

	/**
	 * @param string $input
	 * @param array $params
	 * @param Parser $parser
	 * @return string HTML (Image map, or error message)
	 */
	public function render( $input, $params, Parser $parser ) {
		global $wgUrlProtocols;
		$config = MediaWikiServices::getInstance()->getMainConfig();
		$enableLegacyMediaDOM = $config->get( 'ParserEnableLegacyMediaDOM' );

		$lines = explode( "\n", $input );

		$first = true;
		$scale = 1;
		$imageNode = null;
		$domDoc = DOMCompat::newDocument( true );
		$domFragment = null;
		$thumbWidth = 0;
		$thumbHeight = 0;
		$imageTitle = null;
		$mapHTML = '';
		$links = [];
		$explicitNone = false;

		// Define canonical desc types to allow i18n of 'imagemap_desc_types'
		$descTypesCanonical = 'top-right, bottom-right, bottom-left, top-left, none';
		$descType = self::BOTTOM_RIGHT;
		$defaultLinkAttribs = false;
		$realMap = true;
		$extLinks = [];
		$services = MediaWikiServices::getInstance();
		$repoGroup = $services->getRepoGroup();
		$badFileLookup = $services->getBadFileLookup();
		foreach ( $lines as $lineNum => $line ) {
			$lineNum++;
			$externLink = false;

			$line = trim( $line );
			if ( $line === '' || $line[0] === '#' ) {
				continue;
			}

			if ( $first ) {
				$first = false;

				// The first line should have an image specification on it
				// Extract it and render the HTML
				$bits = explode( '|', $line, 2 );
				$image = $bits[0];
				$options = $bits[1] ?? '';
				$imageTitle = Title::newFromText( $image );
				if ( !$imageTitle || !$imageTitle->inNamespace( NS_FILE ) ) {
					return $this->error( 'imagemap_no_image' );
				}
				if ( $badFileLookup->isBadFile( $imageTitle->getDBkey(), $parser->getTitle() ) ) {
					return $this->error( 'imagemap_bad_image' );
				}
				// Parse the options so we can use links and the like in the caption
				$parsedOptions = $options === '' ? '' : $parser->recursiveTagParse( $options );

				if ( !$enableLegacyMediaDOM ) {
					$explicitNone = preg_match( '/(^|\|)none(\||$)/D', $parsedOptions );
					if ( !$explicitNone ) {
						$parsedOptions .= '|none';
					}
				}

				$imageHTML = $parser->makeImage( $imageTitle, $parsedOptions );
				$parser->replaceLinkHolders( $imageHTML );
				$imageHTML = $parser->getStripState()->unstripBoth( $imageHTML );
				$imageHTML = Sanitizer::normalizeCharReferences( $imageHTML );

				$domFragment = $domDoc->createDocumentFragment();
				DOMUtils::setFragmentInnerHTML( $domFragment, $imageHTML );
				$imageNode = DOMCompat::querySelector( $domFragment, 'img' );
				if ( !$imageNode ) {
					return $this->error( 'imagemap_invalid_image' );
				}
				$thumbWidth = (int)$imageNode->getAttribute( 'width' );
				$thumbHeight = (int)$imageNode->getAttribute( 'height' );

				$imageObj = $repoGroup->findFile( $imageTitle );
				if ( !$imageObj || !$imageObj->exists() ) {
					return $this->error( 'imagemap_invalid_image' );
				}
				// Add the linear dimensions to avoid inaccuracy in the scale
				// factor when one is much larger than the other
				// (sx+sy)/(x+y) = s
				$denominator = $imageObj->getWidth() + $imageObj->getHeight();
				$numerator = $thumbWidth + $thumbHeight;
				if ( $denominator <= 0 || $numerator <= 0 ) {
					return $this->error( 'imagemap_invalid_image' );
				}
				$scale = $numerator / $denominator;
				continue;
			}

			// Handle desc spec
			$cmd = strtok( $line, " \t" );
			if ( $cmd === 'desc' ) {
				$typesText = wfMessage( 'imagemap_desc_types' )->inContentLanguage()->text();
				if ( $descTypesCanonical !== $typesText ) {
					// i18n desc types exists
					$typesText = $descTypesCanonical . ', ' . $typesText;
				}
				$types = array_map( 'trim', explode( ',', $typesText ) );
				$type = trim( strtok( '' ) ?: '' );
				$descType = array_search( $type, $types );
				if ( $descType > 4 ) {
					// A localized descType is used. Subtract 5 to reach the canonical desc type.
					$descType -= 5;
				}
				// <0? In theory never, but paranoia...
				if ( $descType === false || $descType < 0 ) {
					return $this->error( 'imagemap_invalid_desc', $typesText );
				}
				continue;
			}

			$title = false;
			$alt = '';
			// Find the link
			$link = trim( strstr( $line, '[' ) );
			$m = [];
			if ( preg_match( '/^ \[\[  ([^|]*+)  \|  ([^\]]*+)  \]\] \w* $ /x', $link, $m ) ) {
				$title = Title::newFromText( $m[1] );
				$alt = trim( $m[2] );
			} elseif ( preg_match( '/^ \[\[  ([^\]]*+) \]\] \w* $ /x', $link, $m ) ) {
				$title = Title::newFromText( $m[1] );
				if ( $title === null ) {
					return $this->error( 'imagemap_invalid_title', $lineNum );
				}
				$alt = $title->getFullText();
			} elseif ( in_array( substr( $link, 1, strpos( $link, '//' ) + 1 ), $wgUrlProtocols )
				|| in_array( substr( $link, 1, strpos( $link, ':' ) ), $wgUrlProtocols )
			) {
				if ( preg_match( '/^ \[  ([^\s]*+)  \s  ([^\]]*+)  \] \w* $ /x', $link, $m ) ) {
					$title = $m[1];
					$alt = trim( $m[2] );
					$externLink = true;
				} elseif ( preg_match( '/^ \[  ([^\]]*+) \] \w* $ /x', $link, $m ) ) {
					$title = $alt = trim( $m[1] );
					$externLink = true;
				}
			} else {
				return $this->error( 'imagemap_no_link', $lineNum );
			}
			if ( !$title ) {
				return $this->error( 'imagemap_invalid_title', $lineNum );
			}

			$shapeSpec = substr( $line, 0, -strlen( $link ) );

			// Tokenize shape spec
			$shape = strtok( $shapeSpec, " \t" );
			switch ( $shape ) {
				case 'default':
					$coords = [];
					break;
				case 'rect':
					$coords = $this->tokenizeCoords( $lineNum, 4 );
					if ( !is_array( $coords ) ) {
						return $coords;
					}
					break;
				case 'circle':
					$coords = $this->tokenizeCoords( $lineNum, 3 );
					if ( !is_array( $coords ) ) {
						return $coords;
					}
					break;
				case 'poly':
					$coords = $this->tokenizeCoords( $lineNum, 1, true );
					if ( !is_array( $coords ) ) {
						return $coords;
					}
					if ( count( $coords ) % 2 !== 0 ) {
						return $this->error( 'imagemap_poly_odd', $lineNum );
					}
					break;
				default:
					return $this->error( 'imagemap_unrecognised_shape', $lineNum );
			}

			// Scale the coords using the size of the source image
			foreach ( $coords as $i => $c ) {
				$coords[$i] = (int)round( $c * $scale );
			}

			// Construct the area tag
			$attribs = [];
			if ( $externLink ) {
				// Get the 'target' and 'rel' attributes for external link.
				$attribs = $parser->getExternalLinkAttribs( $title );

				$attribs['href'] = $title;
				$attribs['class'] = 'plainlinks';
			} elseif ( $title->getFragment() !== '' && $title->getPrefixedDBkey() === '' ) {
				// XXX: kluge to handle [[#Fragment]] links, should really fix getLocalURL()
				// in Title.php to return an empty string in this case
				$attribs['href'] = $title->getFragmentForURL();
			} else {
				$attribs['href'] = $title->getLocalURL() . $title->getFragmentForURL();
			}
			if ( $shape !== 'default' ) {
				$attribs['shape'] = $shape;
			}
			if ( $coords ) {
				$attribs['coords'] = implode( ',', $coords );
			}
			if ( $alt !== '' ) {
				if ( $shape !== 'default' ) {
					$attribs['alt'] = $alt;
				}
				$attribs['title'] = $alt;
			}
			if ( $shape === 'default' ) {
				$defaultLinkAttribs = $attribs;
			} else {
				// @phan-suppress-next-line SecurityCheck-DoubleEscaped
				$mapHTML .= Xml::element( 'area', $attribs ) . "\n";
			}
			if ( $externLink ) {
				$extLinks[] = $title;
			} else {
				$links[] = $title;
			}
		}

		if ( !$imageNode || !$domFragment ) {
			return $this->error( 'imagemap_no_image' );
		}

		if ( $mapHTML === '' ) {
			// no areas defined, default only. It's not a real imagemap, so we do not need some tags
			$realMap = false;
		}

		if ( $realMap ) {
			// Construct the map
			// Add a hash of the map HTML to avoid breaking cached HTML fragments that are
			// later joined together on the one page (T18471).
			// The only way these hashes can clash is if the map is identical, in which
			// case it wouldn't matter that the "wrong" map was used.
			$mapName = 'ImageMap_' . substr( md5( $mapHTML ), 0, 16 );
			$mapHTML = "<map name=\"$mapName\">\n$mapHTML</map>\n";

			// Alter the image tag
			$imageNode->setAttribute( 'usemap', "#$mapName" );
		}

		if ( $mapHTML !== '' ) {
			$mapFragment = $domDoc->createDocumentFragment();
			DOMUtils::setFragmentInnerHTML( $mapFragment, $mapHTML );
			$mapNode = $mapFragment->firstChild;
		}

		$div = null;

		if ( $enableLegacyMediaDOM ) {
			// Add a surrounding div, remove the default link to the description page
			$anchor = $imageNode->parentNode;
			$parent = $anchor->parentNode;
			'@phan-var DOMElement $anchor';

			// Handle cases where there are no anchors, like `|link=`
			if ( $anchor instanceof DOMDocumentFragment ) {
				$parent = $anchor;
				$anchor = $imageNode;
			}

			$div = $domDoc->createElement( 'div' );
			$parent->insertBefore( $div, $anchor );
			$div->setAttribute( 'class', 'noresize' );
			if ( $defaultLinkAttribs ) {
				$defaultAnchor = $domDoc->createElement( 'a' );
				$div->appendChild( $defaultAnchor );
				foreach ( $defaultLinkAttribs as $name => $value ) {
					$defaultAnchor->setAttribute( $name, $value );
				}
				$imageParent = $defaultAnchor;
			} else {
				$imageParent = $div;
			}

			// Add the map HTML to the div
			if ( isset( $mapNode ) ) {
				$div->appendChild( $mapNode );
			}

			$imageParent->appendChild( $imageNode->cloneNode( true ) );
			$parent->removeChild( $anchor );
		} else {
			$anchor = $imageNode->parentNode;
			$wrapper = $anchor->parentNode;
			Assert::precondition( $wrapper instanceof DOMElement, 'Anchor node has a parent' );
			'@phan-var DOMElement $anchor';

			$classes = $wrapper->getAttribute( 'class' );

			// For T22030
			$classes .= ( $classes ? ' ' : '' ) . 'noresize';

			// Remove that class if it was only added while forcing a block
			if ( !$explicitNone ) {
				$classes = trim( preg_replace( '/ ?mw-halign-none/', '', $classes ) );
			}

			$wrapper->setAttribute( 'class', $classes );

			if ( $defaultLinkAttribs ) {
				$imageParent = $domDoc->createElement( 'a' );
				foreach ( $defaultLinkAttribs as $name => $value ) {
					$imageParent->setAttribute( $name, $value );
				}
			} else {
				$imageParent = $domDoc->createElement( 'span' );
			}
			$wrapper->insertBefore( $imageParent, $anchor );

			if ( !WTUtils::hasVisibleCaption( $wrapper ) ) {
				$caption = DOMCompat::querySelector( $domFragment, 'figcaption' );
				$captionText = trim( WTUtils::textContentFromCaption( $caption ) );
				if ( $captionText ) {
					$imageParent->setAttribute( 'title', $captionText );
				}
			}

			if ( isset( $mapNode ) ) {
				$wrapper->insertBefore( $mapNode, $anchor );
			}

			$imageParent->appendChild( $imageNode->cloneNode( true ) );
			$wrapper->removeChild( $anchor );
		}

		$parserOutput = $parser->getOutput();

		if ( $enableLegacyMediaDOM ) {
			// Determine whether a "magnify" link is present
			$magnify = DOMCompat::querySelector( $domFragment, '.magnify' );
			if ( !$magnify && $descType !== self::NONE ) {
				// Add image description link
				if ( $descType === self::TOP_LEFT || $descType === self::BOTTOM_LEFT ) {
					$marginLeft = 0;
				} else {
					$marginLeft = $thumbWidth - 20;
				}
				if ( $descType === self::TOP_LEFT || $descType === self::TOP_RIGHT ) {
					$marginTop = -$thumbHeight;
					// 1px hack for IE, to stop it poking out the top
					$marginTop++;
				} else {
					$marginTop = -20;
				}
				$div->setAttribute( 'style', "height: {$thumbHeight}px; width: {$thumbWidth}px; " );
				$descWrapper = $domDoc->createElement( 'div' );
				$div->appendChild( $descWrapper );
				$descWrapper->setAttribute( 'style',
					"margin-left: {$marginLeft}px; " .
						"margin-top: {$marginTop}px; " .
						"text-align: left;"
				);

				$descAnchor = $domDoc->createElement( 'a' );
				$descWrapper->appendChild( $descAnchor );
				$descAnchor->setAttribute( 'href', $imageTitle->getLocalURL() );
				$descAnchor->setAttribute(
					'title',
					wfMessage( 'imagemap_description' )->inContentLanguage()->text()
				);
				$descImg = $domDoc->createElement( 'img' );
				$descAnchor->appendChild( $descImg );
				$descImg->setAttribute(
					'alt',
					wfMessage( 'imagemap_description' )->inContentLanguage()->text()
				);
				$url = $config->get( 'ExtensionAssetsPath' ) . '/ImageMap/resources/desc-20.png';
				$descImg->setAttribute(
					'src',
					OutputPage::transformResourcePath( $config, $url )
				);
				$descImg->setAttribute( 'style', 'border: none;' );
			}
		} else {
			'@phan-var DOMElement $wrapper';
			$typeOf = $wrapper->getAttribute( 'typeof' );
			if ( preg_match( '#\bmw:File/Thumb\b#', $typeOf ) ) {
				// $imageNode was cloned above
				$img = $imageParent->firstChild;
				'@phan-var DOMElement $img';
				if ( !$img->hasAttribute( 'resource' ) ) {
					$img->setAttribute( 'resource', $imageTitle->getLocalURL() );
				}
			} elseif ( $descType !== self::NONE ) {
				// The following classes are used here:
				// * mw-ext-imagemap-desc-top-right
				// * mw-ext-imagemap-desc-bottom-right
				// * mw-ext-imagemap-desc-bottom-left
				// * mw-ext-imagemap-desc-top-left
				DOMCompat::getClassList( $wrapper )->add(
					'mw-ext-imagemap-desc-' . self::DESC_TYPE_MAP[$descType]
				);
				// $imageNode was cloned above
				$img = $imageParent->firstChild;
				'@phan-var DOMElement $img';
				if ( !$img->hasAttribute( 'resource' ) ) {
					$img->setAttribute( 'resource', $imageTitle->getLocalURL() );
				}
				$parserOutput->addModules( [ 'ext.imagemap' ] );
				$parserOutput->addModuleStyles( [ 'ext.imagemap.styles' ] );
			}
		}

		// Output the result (XHTML-compliant)
		$output = DOMUtils::getFragmentInnerHTML( $domFragment );

		// Register links
		foreach ( $links as $title ) {
			if ( $title->isExternal() || $title->getNamespace() === NS_SPECIAL ) {
				// Don't register special or interwiki links...
			} elseif ( $title->getNamespace() === NS_MEDIA ) {
				// Regular Media: links are recorded as image usages
				$parserOutput->addImage( $title->getDBkey() );
			} else {
				// Plain ol' link
				$parserOutput->addLink( $title );
			}
		}
		foreach ( $extLinks as $title ) {
			$parserOutput->addExternalLink( $title );
		}
		// Armour output against broken parser
		return str_replace( "\n", '', $output );
	}

	/**
	 * @param int|string $lineNum Line number, for error reporting
	 * @param int $minCount Minimum token count
	 * @param bool $allowNegative
	 * @return array|string String with error (HTML), or array of coordinates
	 */
	private function tokenizeCoords( $lineNum, $minCount = 0, $allowNegative = false ) {
		$coords = [];
		$coord = strtok( " \t" );
		while ( $coord !== false ) {
			if ( !is_numeric( $coord ) || $coord > 1e9 || ( !$allowNegative && $coord < 0 ) ) {
				return $this->error( 'imagemap_invalid_coord', $lineNum );
			}
			$coords[] = $coord;
			$coord = strtok( " \t" );
		}
		if ( count( $coords ) < $minCount ) {
			// TODO: Should this also check there aren't too many coords?
			return $this->error( 'imagemap_missing_coord', $lineNum );
		}
		return $coords;
	}

	/**
	 * @param string $name
	 * @param string|int|bool $line
	 * @return string HTML
	 */
	private function error( $name, $line = false ) {
		return '<p class="error">' . wfMessage( $name, $line )->parse() . '</p>';
	}
}
