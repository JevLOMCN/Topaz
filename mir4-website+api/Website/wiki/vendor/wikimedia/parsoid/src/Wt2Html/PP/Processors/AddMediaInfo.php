<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\PP\Processors;

use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\Config\Env;
use Wikimedia\Parsoid\Core\Sanitizer;
use Wikimedia\Parsoid\DOM\DocumentFragment;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Html2Wt\WTSUtils;
use Wikimedia\Parsoid\NodeData\DataMw;
use Wikimedia\Parsoid\Utils\ContentUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\Title;
use Wikimedia\Parsoid\Utils\WTUtils;
use Wikimedia\Parsoid\Wikitext\Consts;
use Wikimedia\Parsoid\Wt2Html\PegTokenizer;
use Wikimedia\Parsoid\Wt2Html\Wt2HtmlDOMProcessor;

class AddMediaInfo implements Wt2HtmlDOMProcessor {
	/**
	 * Extract the dimensions for media.
	 *
	 * @param Env $env
	 * @param array $attrs
	 * @param array $info
	 * @phan-param array{size:array{height?:int,width?:int},format:string} $attrs
	 * @return array
	 */
	private static function handleSize( Env $env, array $attrs, array $info ): array {
		$height = $info['height'];
		$width = $info['width'];

		Assert::invariant(
			is_numeric( $height ) && $height !== NAN,
			'Expected $height as a valid number'
		);
		Assert::invariant(
			is_numeric( $width ) && $width !== NAN,
			'Expected $width as a valid number'
		);

		if ( !empty( $info['thumburl'] ) && !empty( $info['thumbheight'] ) ) {
			$height = $info['thumbheight'];
		}

		if ( !empty( $info['thumburl'] ) && !empty( $info['thumbwidth'] ) ) {
			$width = $info['thumbwidth'];
		}

		// Audio files don't have dimensions, so we fallback to these arbitrary
		// defaults, and the "mw-default-audio-height" class is added.
		if ( $info['mediatype'] === 'AUDIO' ) {
			$height = /* height || */32; // Arguably, audio should respect a defined height
			$width = $width ?: $env->getSiteConfig()->widthOption();
		}

		// Handle client-side upscaling (including 'border')

		$mustRender = $info['mustRender'] ?? $info['mediatype'] !== 'BITMAP';

		// Calculate the scaling ratio from the user-specified width and height
		$ratio = null;
		if ( !empty( $attrs['dims']['height'] ) && !empty( $info['height'] ) ) {
			$ratio = $attrs['dims']['height'] / $info['height'];
		}
		if ( !empty( $attrs['dims']['width'] ) && !empty( $info['width'] ) ) {
			$r = $attrs['dims']['width'] / $info['width'];
			$ratio = ( $ratio === null || $r < $ratio ) ? $r : $ratio;
		}

		// If the user requested upscaling, then this is denied in the thumbnail
		// and frameless format, except for files with mustRender.
		if (
			$ratio !== null && $ratio > 1 && !$mustRender &&
			( $attrs['format'] === 'Thumb' || $attrs['format'] === 'Frameless' )
		) {
			// Upscaling denied
			$height = $info['height'];
			$width = $info['width'];
		}

		return [ 'height' => $height, 'width' => $width ];
	}

	/**
	 * This is a port of TMH's parseTimeString()
	 *
	 * @param string $timeString
	 * @param int|float|null $length
	 * @return int|float|null
	 */
	private static function parseTimeString(
		string $timeString, $length = null
	) {
		$parts = explode( ':', $timeString );
		$time = 0;
		$countParts = count( $parts );
		if ( $countParts > 3 ) {
			return null;
		}
		for ( $i = 0;  $i < $countParts;  $i++ ) {
			if ( !is_numeric( $parts[$i] ) ) {
				return null;
			}
			$time += floatval( $parts[$i] ) * pow( 60, $countParts - 1 - $i );
		}
		if ( $time < 0 ) {
			$time = 0;
		} elseif ( $length !== null ) {
			if ( $time > $length ) {
				$time = $length - 1;
			}
		}
		return $time;
	}

	/**
	 * Handle media fragments
	 * https://www.w3.org/TR/media-frags/
	 *
	 * @param array $info
	 * @param DataMw $dataMw
	 * @return string
	 */
	private static function parseFrag( array $info, DataMw $dataMw ): string {
		$frag = '';
		$starttime = WTSUtils::getAttrFromDataMw( $dataMw, 'starttime', true );
		$endtime = WTSUtils::getAttrFromDataMw( $dataMw, 'endtime', true );
		if ( $starttime || $endtime ) {
			$frag .= '#t=';
			if ( $starttime ) {
				$time = self::parseTimeString( $starttime[1]->txt, $info['duration'] ?? null );
				if ( $time !== null ) {
					$frag .= $time;
				}
			}
			if ( $endtime ) {
				$time = self::parseTimeString( $endtime[1]->txt, $info['duration'] ?? null );
				if ( $time !== null ) {
					$frag .= ',' . $time;
				}
			}
		}
		return $frag;
	}

	private static function addSources(
		Element $elt, array $info, DataMw $dataMw, bool $hasDimension
	): void {
		$doc = $elt->ownerDocument;
		$frag = self::parseFrag( $info, $dataMw );

		if ( is_array( $info['thumbdata']['derivatives'] ?? null ) ) {
			// BatchAPI's `getAPIData`
			$derivatives = $info['thumbdata']['derivatives'];
		} elseif ( is_array( $info['derivatives'] ?? null ) ) {
			// "videoinfo" prop
			$derivatives = $info['derivatives'];
		} else {
			$derivatives = [
				[
					'src' => $info['url'],
					'type' => $info['mime'],
					'width' => (string)$info['width'],
					'height' => (string)$info['height'],
				],
			];
		}

		foreach ( $derivatives as $o ) {
			$source = $doc->createElement( 'source' );
			$source->setAttribute( 'src', $o['src'] . $frag );
			$source->setAttribute( 'type', $o['type'] );  // T339375
			$fromFile = isset( $o['transcodekey'] ) ? '' : '-file';
			if ( $hasDimension ) {
				$source->setAttribute( 'data' . $fromFile . '-width', (string)$o['width'] );
				$source->setAttribute( 'data' . $fromFile . '-height', (string)$o['height'] );
			}
			if ( !$fromFile ) {
				$source->setAttribute( 'data-transcodekey', $o['transcodekey'] );
			}
			$elt->appendChild( $source );
		}
	}

	private static function addTracks( Element $elt, array $info ): void {
		$doc = $elt->ownerDocument;
		if ( is_array( $info['thumbdata']['timedtext'] ?? null ) ) {
			// BatchAPI's `getAPIData`
			$timedtext = $info['thumbdata']['timedtext'];
		} elseif ( is_array( $info['timedtext'] ?? null ) ) {
			// "videoinfo" prop
			$timedtext = $info['timedtext'];
		} else {
			$timedtext = [];
		}
		foreach ( $timedtext as $o ) {
			$track = $doc->createElement( 'track' );
			$track->setAttribute( 'kind', $o['kind'] ?? '' );
			$track->setAttribute( 'type', $o['type'] ?? '' );
			$track->setAttribute( 'src', $o['src'] ?? '' );
			$track->setAttribute( 'srclang', $o['srclang'] ?? '' );
			$track->setAttribute( 'label', $o['label'] ?? '' );
			$track->setAttribute( 'data-mwtitle', $o['title'] ?? '' );
			$track->setAttribute( 'data-dir', $o['dir'] ?? '' );
			$elt->appendChild( $track );
		}
	}

	/**
	 * Abstract way to get the path for an image given an info object.
	 *
	 * @param array $info
	 * @return string
	 */
	private static function getPath( array $info ) {
		$path = '';
		if ( !empty( $info['thumburl'] ) ) {
			$path = $info['thumburl'];
		} elseif ( !empty( $info['url'] ) ) {
			$path = $info['url'];
		}
		return $path;
	}

	/**
	 * @param Env $env
	 * @param Element $span
	 * @param array $attrs
	 * @param array $info
	 * @param DataMw $dataMw
	 * @param Element $container
	 * @param string|null $alt Unused, but matches the signature of handlers
	 * @return Element
	 */
	private static function handleAudio(
		Env $env, Element $span, array $attrs, array $info, DataMw $dataMw,
		Element $container, ?string $alt
	): Element {
		$doc = $span->ownerDocument;
		$audio = $doc->createElement( 'audio' );

		$audio->setAttribute( 'controls', '' );
		$audio->setAttribute( 'preload', 'none' );

		$muted = WTSUtils::getAttrFromDataMw( $dataMw, 'muted', false );
		if ( $muted ) {
			$audio->setAttribute( 'muted', '' );
		}
		$loop = WTSUtils::getAttrFromDataMw( $dataMw, 'loop', false );
		if ( $loop ) {
			$audio->setAttribute( 'loop', '' );
		}

		$size = self::handleSize( $env, $attrs, $info );
		DOMDataUtils::addNormalizedAttribute( $audio, 'height', (string)$size['height'], null, true );
		DOMDataUtils::addNormalizedAttribute( $audio, 'width', (string)$size['width'], null, true );

		// Hardcoded until defined heights are respected.
		// See `AddMediaInfo.handleSize`
		DOMCompat::getClassList( $container )->add( 'mw-default-audio-height' );

		self::copyOverAttribute( $audio, $span, 'resource' );

		if ( $span->hasAttribute( 'lang' ) ) {
			self::copyOverAttribute( $audio, $span, 'lang' );
		}

		if ( $info['duration'] ?? null ) {
			$audio->setAttribute( 'data-durationhint', (string)ceil( (float)$info['duration'] ) );
		}

		self::addSources( $audio, $info, $dataMw, false );
		self::addTracks( $audio, $info );

		return $audio;
	}

	/**
	 * @param Env $env
	 * @param Element $span
	 * @param array $attrs
	 * @param array $info
	 * @param DataMw $dataMw
	 * @param Element $container
	 * @param string|null $alt Unused, but matches the signature of handlers
	 * @return Element
	 */
	private static function handleVideo(
		Env $env, Element $span, array $attrs, array $info, DataMw $dataMw,
		Element $container, ?string $alt
	): Element {
		$doc = $span->ownerDocument;
		$video = $doc->createElement( 'video' );

		if ( !empty( $info['thumburl'] ) ) {
			$video->setAttribute( 'poster', self::getPath( $info ) );
		}

		$video->setAttribute( 'controls', '' );
		$video->setAttribute( 'preload', 'none' );

		$muted = WTSUtils::getAttrFromDataMw( $dataMw, 'muted', false );
		if ( $muted ) {
			$video->setAttribute( 'muted', '' );
		}
		$loop = WTSUtils::getAttrFromDataMw( $dataMw, 'loop', false );
		if ( $loop ) {
			$video->setAttribute( 'loop', '' );
		}

		$size = self::handleSize( $env, $attrs, $info );
		DOMDataUtils::addNormalizedAttribute( $video, 'height', (string)$size['height'], null, true );
		DOMDataUtils::addNormalizedAttribute( $video, 'width', (string)$size['width'], null, true );

		self::copyOverAttribute( $video, $span, 'resource' );

		if ( $span->hasAttribute( 'lang' ) ) {
			self::copyOverAttribute( $video, $span, 'lang' );
		}

		if ( $info['duration'] ?? null ) {
			$video->setAttribute( 'data-durationhint', (string)ceil( (float)$info['duration'] ) );
		}

		self::addSources( $video, $info, $dataMw, true );
		self::addTracks( $video, $info );

		return $video;
	}

	/**
	 * Set up the actual image structure, attributes, etc.
	 *
	 * @param Env $env
	 * @param Element $span
	 * @param array $attrs
	 * @param array $info
	 * @param DataMw $dataMw
	 * @param Element $container
	 * @param string|null $alt
	 * @return Element
	 */
	private static function handleImage(
		Env $env, Element $span, array $attrs, array $info, DataMw $dataMw,
		Element $container, ?string $alt
	): Element {
		$doc = $span->ownerDocument;
		$img = $doc->createElement( 'img' );

		if ( $alt !== null ) {
			$img->setAttribute( 'alt', $alt );
		}

		self::copyOverAttribute( $img, $span, 'resource' );

		$img->setAttribute( 'src', self::getPath( $info ) );
		$img->setAttribute( 'decoding', 'async' );

		if ( $span->hasAttribute( 'lang' ) ) {
			self::copyOverAttribute( $img, $span, 'lang' );
		}

		// Add (read-only) information about original file size (T64881)
		$img->setAttribute( 'data-file-width', (string)$info['width'] );
		$img->setAttribute( 'data-file-height', (string)$info['height'] );
		$img->setAttribute( 'data-file-type', strtolower( $info['mediatype'] ?? '' ) );

		$size = self::handleSize( $env, $attrs, $info );
		DOMDataUtils::addNormalizedAttribute( $img, 'height', (string)$size['height'], null, true );
		DOMDataUtils::addNormalizedAttribute( $img, 'width', (string)$size['width'], null, true );

		// Handle "responsive" images, i.e. srcset
		if ( !empty( $info['responsiveUrls'] ) ) {
			$candidates = [];
			foreach ( $info['responsiveUrls'] as $density => $url ) {
				$candidates[] = $url . ' ' . $density . 'x';
			}
			if ( $candidates ) {
				$img->setAttribute( 'srcset', implode( ', ', $candidates ) );
			}
		}

		return $img;
	}

	private static function makeErr(
		string $key, string $message, ?array $params = null
	): array {
		$e = [ 'key' => $key, 'message' => $message ];
		// Additional error info for clients that could fix the error.
		if ( $params !== null ) {
			$e['params'] = $params;
		}
		return $e;
	}

	private static function handleErrors(
		Element $container, Element $span, array $errs, DataMw $dataMw,
		?string $alt
	): void {
		if ( !DOMUtils::hasTypeOf( $container, 'mw:Error' ) ) {
			DOMUtils::addTypeOf( $container, 'mw:Error', true );
		}
		if ( is_array( $dataMw->errors ?? null ) ) {
			$errs = array_merge( $dataMw->errors, $errs );
		}
		$dataMw->errors = $errs;
		if ( $alt !== null ) {
			DOMCompat::replaceChildren( $span, $span->ownerDocument->createTextNode( $alt ) );
		}
	}

	private static function copyOverAttribute(
		Element $elt, Element $span, string $attribute
	): void {
		DOMDataUtils::addNormalizedAttribute(
			$elt,
			$attribute,
			DOMCompat::getAttribute( $span, $attribute ),
			WTSUtils::getAttributeShadowInfo( $span, $attribute )['value']
		);
	}

	private static function replaceAnchor(
		Env $env, PegTokenizer $urlParser, Element $container,
		Element $oldAnchor, array $attrs, DataMw $dataMw, bool $isImage,
		?string $captionText, int $page, string $lang
	): Element {
		$doc = $oldAnchor->ownerDocument;
		$attr = WTSUtils::getAttrFromDataMw( $dataMw, 'link', true );

		if ( $isImage ) {
			$anchor = $doc->createElement( 'a' );
			$addDescriptionLink = static function ( Title $title ) use ( $env, $anchor, $page, $lang ) {
				$href = $env->makeLink( $title );
				$qs = [];
				if ( $page > 0 ) {
					$qs['page'] = $page;
				}
				if ( $lang ) {
					$qs['lang'] = $lang;
				}
				if ( $qs ) {
					$href .= '?' . http_build_query( $qs );
				}
				$anchor->setAttribute( 'href', $href );
				$anchor->setAttribute( 'class', 'mw-file-description' );
			};
			if ( $attr !== null ) {
				$discard = true;
				$val = $attr[1]->txt;
				if ( $val === '' ) {
					// No href if link= was specified
					$anchor = $doc->createElement( 'span' );
				} elseif ( $urlParser->tokenizeURL( $val ) !== false ) {
					// An external link!
					$href = Sanitizer::cleanUrl( $env->getSiteConfig(), $val, 'external' );
					$anchor->setAttribute( 'href', $href );
					// Similar to AddLinkAttributes
					$extLinkAttribs = $env->getExternalLinkAttribs( $href );
					foreach ( $extLinkAttribs as $key => $val ) {
						if ( $key === 'rel' ) {
							foreach ( $val as $v ) {
								DOMUtils::addRel( $anchor, $v );
							}
						} else {
							$anchor->setAttribute( $key, $val );
						}
					}
				} else {
					$link = $env->makeTitleFromText( $val, null, true );
					if ( $link !== null ) {
						$anchor->setAttribute( 'href', $env->makeLink( $link ) );
						$anchor->setAttribute( 'title', $link->getPrefixedText() );
					} else {
						// Treat same as if link weren't present
						$addDescriptionLink( $attrs['title'] );
						// but preserve for roundtripping
						$discard = false;
					}
				}
				if ( $discard ) {
					WTSUtils::getAttrFromDataMw( $dataMw, 'link', /* keep */false );
				}
			} else {
				$addDescriptionLink( $attrs['title'] );
			}
		} else {
			$anchor = $doc->createElement( 'span' );
		}

		if ( $captionText ) {
			$anchor->setAttribute( 'title', $captionText );
		}

		$oldAnchor->parentNode->replaceChild( $anchor, $oldAnchor );
		return $anchor;
	}

	/**
	 * @inheritDoc
	 */
	public function run(
		Env $env, Node $root, array $options = [], bool $atTopLevel = false
	): void {
		'@phan-var Element|DocumentFragment $root';  // @var Element|DocumentFragment $root
		$urlParser = new PegTokenizer( $env );

		$validContainers = [];
		$files = [];

		$containers = DOMCompat::querySelectorAll( $root, '[typeof*="mw:File"]' );

		foreach ( $containers as $container ) {
			// DOMFragmentWrappers assume the element name of their outermost
			// content so, depending how the above query is written, we're
			// protecting against getting a figure of the wrong type.  However,
			// since we're currently using typeof, it shouldn't be a problem.
			// Also note that info for the media nested in the fragment has
			// already been added in their respective pipeline.
			Assert::invariant(
				!WTUtils::isDOMFragmentWrapper( $container ),
				'Media info for fragment was already added'
			);

			// We expect this structure to be predictable based on how it's
			// emitted in the TT/WikiLinkHandler but treebuilding may have
			// messed that up for us.
			$anchor = $container;
			$reopenedAFE = [];
			do {
				// An active formatting element may have been reopened inside
				// the wrapper if a content model violation was encountered
				// during treebuiling.  Try to be a little lenient about that
				// instead of bailing out
				$anchor = $anchor->firstChild;
				$anchorNodeName = DOMCompat::nodeName( $anchor );
				if ( $anchorNodeName !== 'a' ) {
					$reopenedAFE[] = $anchor;
				}
			} while (
				$anchorNodeName !== 'a' &&
				isset( Consts::$HTML['FormattingTags'][$anchorNodeName] )
			);
			if ( $anchorNodeName !== 'a' ) {
				$env->log( 'error', 'Unexpected structure when adding media info.' );
				continue;
			}
			$span = $anchor->firstChild;
			if ( !( $span instanceof Element && DOMCompat::nodeName( $span ) === 'span' ) ) {
				$env->log( 'error', 'Unexpected structure when adding media info.' );
				continue;
			}
			$caption = $anchor->nextSibling;
			$isInlineMedia = WTUtils::isInlineMedia( $container );
			if ( !$isInlineMedia && DOMCompat::nodeName( $caption ) !== 'figcaption' ) {
				$env->log( 'error', 'Unexpected structure when adding media info.' );
				continue;
			}

			// For T314059.  Migrate any active formatting tags we found open
			// inside the container to the ficaption to conform to the spec.
			// This should simplify selectors for clients and styling.
			// TODO: Consider exposing these as lints
			if ( $reopenedAFE ) {
				$firstAFE = $reopenedAFE[0];
				$lastAFE = $reopenedAFE[count( $reopenedAFE ) - 1];
				DOMUtils::migrateChildren( $lastAFE, $container );
				if ( $isInlineMedia ) {
					// Remove the formatting elements, they are of no use
					// We could migrate them into the caption in data-mw,
					// but that doesn't seem worthwhile
					$firstAFE->parentNode->removeChild( $firstAFE );
				} else {
					// Move the formatting elements into the figcaption
					DOMUtils::migrateChildren( $caption, $lastAFE );
					$caption->appendChild( $firstAFE );
					// Unconditionally clear tsr out of an abundance of caution
					// These tags should already be annotated as autoinserted anyways
					foreach ( $reopenedAFE as $afe ) {
						DOMDataUtils::getDataParsoid( $afe )->tsr = null;
					}
				}
			}

			$dataMw = DOMDataUtils::getDataMw( $container );

			$dims = [
				'width' => (int)DOMCompat::getAttribute( $span, 'data-width' ) ?: null,
				'height' => (int)DOMCompat::getAttribute( $span, 'data-height' ) ?: null,
			];

			$page = WTSUtils::getAttrFromDataMw( $dataMw, 'page', true );
			if ( $page ) {
				$dims['page'] = $page[1]->txt;
			}

			$lang = DOMCompat::getAttribute( $span, 'lang' );
			if ( $lang !== null ) {
				$dims['lang'] = $lang;
			}

			// "starttime" should be used if "thumbtime" isn't present,
			// but only for rendering.
			// "starttime" should be used if "thumbtime" isn't present,
			// but only for rendering.
			$thumbtime = WTSUtils::getAttrFromDataMw( $dataMw, 'thumbtime', true );
			$starttime = WTSUtils::getAttrFromDataMw( $dataMw, 'starttime', true );
			if ( $thumbtime || $starttime ) {
				$seek = isset( $thumbtime[1] )
					? $thumbtime[1]->txt
					: ( isset( $starttime[1] ) ? $starttime[1]->txt : '' );
				$seek = self::parseTimeString( $seek );
				if ( $seek !== null ) {
					$dims['seek'] = $seek;
				}
			}

			$attrs = [
				'dims' => $dims,
				'format' => WTUtils::getMediaFormat( $container ),
				'title' => $env->makeTitleFromText( $span->textContent ),
			];

			$file = [ $attrs['title']->getKey(), $dims ];
			$infoKey = md5( json_encode( $file ) );
			$files[$infoKey] = $file;
			$errs = [];

			$manualKey = null;
			$manualthumb = WTSUtils::getAttrFromDataMw( $dataMw, 'manualthumb', true );
			if ( $manualthumb !== null ) {
				$val = $manualthumb[1]->txt;
				$title = $env->makeTitleFromText( $val, $attrs['title']->getNamespace(), true );
				if ( $title === null ) {
					$errs[] = self::makeErr(
						'apierror-invalidtitle',
						'Invalid thumbnail title.',
						[ 'name' => $val ]
					);
				} else {
					$file = [ $title->getKey(), $dims ];
					$manualKey = md5( json_encode( $file ) );
					$files[$manualKey] = $file;
				}
			}

			$validContainers[] = [
				'container' => $container,
				'attrs' => $attrs,
				// Pass the anchor because we did some work to find it above
				'anchor' => $anchor,
				'infoKey' => $infoKey,
				'manualKey' => $manualKey,
				'errs' => $errs,
			];
		}

		if ( !$validContainers ) {
			return;
		}

		$start = microtime( true );

		$infos = $env->getDataAccess()->getFileInfo(
			$env->getPageConfig(),
			array_values( $files )
		);

		if ( $env->profiling() ) {
			$profile = $env->getCurrentProfile();
			$profile->bumpMWTime( "Media", 1000 * ( microtime( true ) - $start ), "api" );
			$profile->bumpCount( "Media" );
		}

		$files = array_combine(
			array_keys( $files ),
			$infos
		);

		$hasThumb = false;
		$needsTMHModules = false;

		foreach ( $validContainers as $c ) {
			$container = $c['container'];
			$anchor = $c['anchor'];
			$span = $anchor->firstChild;
			$attrs = $c['attrs'];
			$dataMw = DOMDataUtils::getDataMw( $container );
			$errs = $c['errs'];

			$hasThumb = $hasThumb || DOMUtils::hasTypeOf( $container, 'mw:File/Thumb' );

			$info = $files[$c['infoKey']];
			if ( !$info ) {
				$errs[] = self::makeErr( 'apierror-filedoesnotexist', 'This image does not exist.' );
			} elseif ( isset( $info['thumberror'] ) ) {
				$errs[] = self::makeErr( 'apierror-unknownerror', $info['thumberror'] );
			}

			// FIXME: Should we fallback to $info if there are errors with $manualinfo?
			// What does the legacy parser do?
			if ( $c['manualKey'] !== null ) {
				$manualinfo = $files[$c['manualKey']];
				if ( !$manualinfo ) {
					$errs[] = self::makeErr( 'apierror-filedoesnotexist', 'This image does not exist.' );
				} elseif ( isset( $manualinfo['thumberror'] ) ) {
					$errs[] = self::makeErr( 'apierror-unknownerror', $manualinfo['thumberror'] );
				} else {
					$info = $manualinfo;
				}
			}

			if ( $info['badFile'] ?? false ) {
				$errs[] = self::makeErr( 'apierror-badfile', 'This image is on the bad file list.' );
			}

			if ( WTUtils::hasVisibleCaption( $container ) ) {
				$captionText = null;
			} else {
				if ( WTUtils::isInlineMedia( $container ) ) {
					$caption = ContentUtils::createAndLoadDocumentFragment(
						$container->ownerDocument, $dataMw->caption ?? ''
					);
				} else {
					$caption = DOMCompat::querySelector( $container, 'figcaption' );
					// If the caption had tokens, it was placed in a DOMFragment
					// and we haven't unpacked yet
					if (
						$caption->firstChild &&
						DOMUtils::hasTypeOf( $caption->firstChild, 'mw:DOMFragment' )
					) {
						$id = DOMDataUtils::getDataParsoid( $caption->firstChild )->html;
						$caption = $env->getDOMFragment( $id );
					}
				}
				$captionText = trim( WTUtils::textContentFromCaption( $caption ) );

				// The sanitizer isn't going to do anything with a string value
				// for alt/title and since we're going to use dom element setters,
				// quote escaping should be fine.  Note that if sanitization does
				// happen here, it should also be done to $altFromCaption so that
				// string comparison matches, where necessary.
				//
				// $sanitizedArgs = Sanitizer::sanitizeTagAttrs( $env->getSiteConfig(), 'img', null, [
				// 	new KV( 'alt', $captionText )  // Could be a 'title' too
				// ] );
				// $captionText = $sanitizedArgs['alt'][0];
			}

			// Info relates to the thumb, not necessarily the file.
			// The distinction matters for manualthumb, in which case only
			// the "resource" copied over from the span relates to the file.

			switch ( $info['mediatype'] ?? '' ) {
				case 'AUDIO':
					$handler = 'handleAudio';
					$isImage = false;
					break;
				case 'VIDEO':
					$handler = 'handleVideo';
					$isImage = false;
					break;
				default:
					$handler = 'handleImage';
					$isImage = true;
					break;
			}

			$alt = null;
			$keepAltInDataMw = !$isImage || $errs;
			$attr = WTSUtils::getAttrFromDataMw( $dataMw, 'alt', $keepAltInDataMw );
			if ( $attr !== null ) {
				$alt = $attr[1]->txt;
			} elseif ( $captionText ) {
				$alt = $captionText;
			}

			// Add mw:Error to the RDFa type.
			if ( $errs ) {
				self::handleErrors( $container, $span, $errs, $dataMw, $alt );
				continue;
			}

			$needsTMHModules = $needsTMHModules || !$isImage;

			$env->getMetadata()->addImage(
				$attrs['title'],
				$info['timestamp'] ?? null,
				$info['sha1'] ?? null,
			);

			$elt = self::$handler( $env, $span, $attrs, $info, $dataMw, $container, $alt );
			DOMCompat::getClassList( $elt )->add( 'mw-file-element' );

			$anchor = self::replaceAnchor(
				$env, $urlParser, $container, $anchor, $attrs, $dataMw, $isImage, $captionText,
				(int)( $attrs['dims']['page'] ?? 0 ),
				$attrs['dims']['lang'] ?? ''
			);
			$anchor->appendChild( $elt );

			if ( isset( $dataMw->attribs ) && count( $dataMw->attribs ) === 0 ) {
				unset( $dataMw->attribs );
			}
		}

		if ( $hasThumb ) {
			$env->getMetadata()->addModules( [ 'mediawiki.page.media' ] );
		}

		if ( $needsTMHModules ) {
			$env->getMetadata()->addModuleStyles( [ 'ext.tmh.player.styles' ] );
			$env->getMetadata()->addModules( [ 'ext.tmh.player' ] );
		}
	}
}
