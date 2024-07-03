<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Ext\Gallery;

use stdClass;
use Wikimedia\Assert\UnreachableException;
use Wikimedia\Parsoid\Core\DomSourceRange;
use Wikimedia\Parsoid\Core\MediaStructure;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Text;
use Wikimedia\Parsoid\Ext\DiffDOMUtils;
use Wikimedia\Parsoid\Ext\DiffUtils;
use Wikimedia\Parsoid\Ext\DOMDataUtils;
use Wikimedia\Parsoid\Ext\DOMUtils;
use Wikimedia\Parsoid\Ext\ExtensionModule;
use Wikimedia\Parsoid\Ext\ExtensionTagHandler;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;
use Wikimedia\Parsoid\Utils\DOMCompat;

/**
 * Implements the php parser's `renderImageGallery` natively.
 *
 * Params to support (on the extension tag):
 * - showfilename
 * - caption
 * - mode
 * - widths
 * - heights
 * - perrow
 *
 * A proposed spec is at: https://phabricator.wikimedia.org/P2506
 */
class Gallery extends ExtensionTagHandler implements ExtensionModule {

	/** @inheritDoc */
	public function getConfig(): array {
		return [
			'name' => 'Gallery',
			'tags' => [
				[
					'name' => 'gallery',
					'handler' => self::class,
					'options' => [
						'wt2html' => [
							'customizesDataMw' => true,
						],
						'outputHasCoreMwDomSpecMarkup' => true
					],
				]
			],
		];
	}

	/**
	 * Parse the gallery caption.
	 * @param ParsoidExtensionAPI $extApi
	 * @param array $extArgs
	 * @return ?DocumentFragment
	 */
	private function pCaption(
		ParsoidExtensionAPI $extApi, array $extArgs
	): ?DocumentFragment {
		return $extApi->extArgToDOM( $extArgs, 'caption' );
	}

	/**
	 * Parse a single line of the gallery.
	 * @param ParsoidExtensionAPI $extApi
	 * @param string $line
	 * @param int $lineStartOffset
	 * @param Opts $opts
	 * @return ParsedLine|null
	 */
	private static function pLine(
		ParsoidExtensionAPI $extApi, string $line, int $lineStartOffset,
		Opts $opts
	): ?ParsedLine {
		// Regexp from php's `renderImageGallery`
		if ( !preg_match( '/^([^|]+)(\|(?:.*))?$/D', $line, $matches ) ) {
			return null;
		}

		$oTitleStr = $matches[1];
		$imageOptStr = $matches[2] ?? '';

		// TODO: % indicates rawurldecode.

		$mode = Mode::byName( $opts->mode );

		$imageOpts = [
			[ $imageOptStr, $lineStartOffset + strlen( $oTitleStr ) ],
			// T305628: Dimensions are last one wins so ensure this takes
			// precedence over anything in $imageOptStr
			"|{$mode->dimensions( $opts )}",
		];

		$fileNs = $extApi->getSiteConfig()->canonicalNamespaceId( 'file' );

		$noPrefix = false;
		$title = $extApi->makeTitle( $oTitleStr, 0 );
		if ( $title === null || $title->getNamespace() !== $fileNs ) {
			// Try again, this time with a default namespace
			$title = $extApi->makeTitle( $oTitleStr, $fileNs );
			$noPrefix = true;
		}
		if ( $title === null || $title->getNamespace() !== $fileNs ) {
			return null;
		}

		if ( $noPrefix ) {
			// Take advantage of $fileNs to give us the right namespace, since,
			// the explicit prefix isn't necessary in galleries but for the
			// wikilink syntax it is.  Ex,
			//
			// <gallery>
			// Test.png
			// </gallery>
			//
			// vs [[File:Test.png]], here the File: prefix is necessary
			//
			// Note, this is no longer from source now
			$titleStr = $title->getPrefixedDBKey();
		} else {
			$titleStr = $oTitleStr;
		}

		$thumb = $extApi->renderMedia(
			$titleStr, $imageOpts, $error,
			// Force block for an easier structure to manipulate, otherwise
			// we have to pull the caption out of the data-mw
			true,
			// Suppress media formats since they aren't valid gallery media
			// options and we don't want to deal with rendering differences
			true
		);
		if ( !$thumb || DOMCompat::nodeName( $thumb ) !== 'figure' ) {
			return null;
		}

		if ( $noPrefix ) {
			// Fiddling with the shadow attribute below, rather than using
			// DOMDataUtils::setShadowInfoIfModified, since WikiLinkHandler::renderFile
			// always sets a shadow (at minimum for the relative './') and that
			// method preserves the original source from the first time it's called,
			// though there's a FIXME to remove that behaviour.
			$media = $thumb->firstChild->firstChild;
			$dp = DOMDataUtils::getDataParsoid( $media );
			$dp->sa['resource'] = $oTitleStr;
		}

		$doc = $thumb->ownerDocument;
		$rdfaType = DOMCompat::getAttribute( $thumb, 'typeof' ) ?? '';

		// Detach figcaption as well
		$figcaption = DOMCompat::querySelector( $thumb, 'figcaption' );
		DOMCompat::remove( $figcaption );

		if ( $opts->showfilename ) {
			$file = $title->getPrefixedDBKey();
			$galleryfilename = $doc->createElement( 'a' );
			$galleryfilename->setAttribute( 'href', $extApi->getTitleUri( $title ) );
			$galleryfilename->setAttribute( 'class', 'galleryfilename galleryfilename-truncate' );
			$galleryfilename->setAttribute( 'title', $file );
			$galleryfilename->appendChild( $doc->createTextNode( $file ) );
			$figcaption->insertBefore( $galleryfilename, $figcaption->firstChild );
		}

		$gallerytext = null;
		for (
			$capChild = $figcaption->firstChild;
			$capChild !== null;
			$capChild = $capChild->nextSibling
		) {
			if (
				$capChild instanceof Text &&
				preg_match( '/^\s*$/D', $capChild->nodeValue )
			) {
				// skip blank text nodes
				continue;
			}
			// Found a non-blank node!
			$gallerytext = $figcaption;
			break;
		}

		$dsr = new DomSourceRange( $lineStartOffset, $lineStartOffset + strlen( $line ), null, null );
		return new ParsedLine( $thumb, $gallerytext, $rdfaType, $dsr );
	}

	/** @inheritDoc */
	public function sourceToDom(
		ParsoidExtensionAPI $extApi, string $content, array $args
	): DocumentFragment {
		$attrs = $extApi->extArgsToArray( $args );
		$opts = new Opts( $extApi, $attrs );

		$offset = $extApi->extTag->getOffsets()->innerStart();

		// Prepare the lines for processing
		$lines = explode( "\n", $content );
		$lines = array_map( static function ( $line ) use ( &$offset ) {
				$lineObj = [ 'line' => $line, 'offset' => $offset ];
				$offset += strlen( $line ) + 1; // For the nl
				return $lineObj;
		}, $lines );

		$caption = $opts->caption ? $this->pCaption( $extApi, $args ) : null;
		$lines = array_map( function ( $lineObj ) use ( $extApi, $opts ) {
			return $this->pLine(
				$extApi, $lineObj['line'], $lineObj['offset'], $opts
			);
		}, $lines );

		// Drop invalid lines like "References: 5."
		$lines = array_filter( $lines, static function ( $lineObj ) {
			return $lineObj !== null;
		} );

		$mode = Mode::byName( $opts->mode );
		$extApi->getMetadata()->addModules( $mode->getModules() );
		$extApi->getMetadata()->addModuleStyles( $mode->getModuleStyles() );
		$domFragment = $mode->render( $extApi, $opts, $caption, $lines );

		$dataMw = $extApi->extTag->getDefaultDataMw();

		// Remove extsrc from native extensions
		if (
			// Self-closed tags don't have a body but unsetting on it induces one
			isset( $dataMw->body )
		) {
			unset( $dataMw->body->extsrc );
		}

		// Remove the caption since it's redundant with the HTML
		// and we prefer editing it there.
		unset( $dataMw->attrs->caption );

		DOMDataUtils::setDataMw( $domFragment->firstChild, $dataMw );

		return $domFragment;
	}

	private function contentHandler(
		ParsoidExtensionAPI $extApi, Element $node
	): string {
		$content = "\n";
		for ( $child = $node->firstChild; $child; $child = $child->nextSibling ) {
			switch ( $child->nodeType ) {
				case XML_ELEMENT_NODE:
					DOMUtils::assertElt( $child );
					// Ignore if it isn't a "gallerybox"
					if (
					DOMCompat::nodeName( $child ) !== 'li' ||
					!DOMUtils::hasClass( $child, 'gallerybox' )
					) {
						break;
					}
					$oContent = $extApi->getOrigSrc(
					$child, false, [ DiffUtils::class, 'subtreeUnchanged' ]
					);
					if ( $oContent !== null ) {
						$content .= $oContent . "\n";
						break;
					}
					$div = DOMCompat::querySelector( $child, '.thumb' );
					if ( !$div ) {
						break;
					}
					$gallerytext = DOMCompat::querySelector( $child, '.gallerytext' );
					if ( $gallerytext ) {
						$showfilename = DOMCompat::querySelector( $gallerytext, '.galleryfilename' );
						if ( $showfilename ) {
							DOMCompat::remove( $showfilename ); // Destructive to the DOM!
						}
					}
					$thumb = DiffDOMUtils::firstNonSepChild( $div );
					$ms = MediaStructure::parse( $thumb );
					if ( $ms ) {
						// Unlike other inline media, the caption isn't found in the data-mw
						// of the container element.  Hopefully this won't be necessary after T268250
						$ms->captionElt = $gallerytext;
						// Destructive to the DOM!  But, a convenient way to get the serializer
						// to ignore the fake dimensions that were added in pLine when parsing.
						DOMCompat::getClassList( $ms->containerElt )->add( 'mw-default-size' );
						[ $line, $options ] = $extApi->serializeMedia( $ms );
						if ( $options ) {
							$line .= '|' . $options;
						}
					} else {
						// TODO: Previously (<=1.5.0), we rendered valid titles
						// returning mw:Error (apierror-filedoesnotexist) as
						// plaintext.  Continue to serialize this content until
						// that version is no longer supported.
						$line = $div->textContent;
						if ( $gallerytext ) {
							$caption = $extApi->domChildrenToWikitext(
							$gallerytext, $extApi::IN_IMG_CAPTION
							);
							// Drop empty captions
							if ( !preg_match( '/^\s*$/D', $caption ) ) {
								$line .= '|' . $caption;
							}
						}
					}
					// Ensure that this only takes one line since gallery
					// tag content is split by line
					$line = str_replace( "\n", ' ', $line );
					$content .= $line . "\n";
					break;
				case XML_TEXT_NODE:
				case XML_COMMENT_NODE:
					// Ignore it
					break;
				default:
					throw new UnreachableException( 'should not be here!' );
			}
		}
		return $content;
	}

	/** @inheritDoc */
	public function domToWikitext(
		ParsoidExtensionAPI $extApi, Element $node, bool $wrapperUnmodified
	) {
		$dataMw = DOMDataUtils::getDataMw( $node );
		$dataMw->attrs ??= new stdClass;
		// Handle the "gallerycaption" first
		$galcaption = DOMCompat::querySelector( $node, 'li.gallerycaption' );
		if ( $galcaption ) {
			$dataMw->attrs->caption = $extApi->domChildrenToWikitext(
				$galcaption, $extApi::IN_IMG_CAPTION | $extApi::IN_OPTION
			);
			// Destructive to the DOM!
			// However, removing it simplifies some of the logic below.
			// Hopefully this won't be necessary after T268250
			DOMCompat::remove( $galcaption );
		}

		// Not having a body is a signal that the extension tag was parsed
		// as self-closed but, when serializing, we should make sure that
		// no content was added, otherwise it's uneditable.
		//
		// This relies on the caption having been removed above
		if ( DiffDOMUtils::firstNonSepChild( $node ) !== null ) {
			$dataMw->body ??= new stdClass;
		}

		$startTagSrc = $extApi->extStartTagToWikitext( $node );

		if ( !isset( $dataMw->body ) ) {
			return $startTagSrc; // We self-closed this already.
		} else {
			$content = $extApi->getOrigSrc(
				$node, true,
				// The gallerycaption is nested as a list item but shouldn't
				// be considered when deciding if the body can be reused.
				// Hopefully this won't be necessary after T268250
				//
				// Even though we've removed the caption from the DOM above,
				// it was present during DOM diff'ing, so a call to
				// DiffUtils::subtreeUnchanged is insufficient.
				static function ( Element $elt ): bool {
					for ( $child = $elt->firstChild; $child; $child = $child->nextSibling ) {
						if ( DiffUtils::hasDiffMarkers( $child ) ) {
							return false;
						}
					}
					return true;
				}
			);
			if ( $content === null ) {
				$content = $this->contentHandler( $extApi, $node );
			}
			return $startTagSrc . $content . '</' . $dataMw->name . '>';
		}
	}

	/** @inheritDoc */
	public function diffHandler(
		ParsoidExtensionAPI $extApi, callable $domDiff, Element $origNode,
		Element $editedNode
	): bool {
		return call_user_func( $domDiff, $origNode, $editedNode );
	}
}
