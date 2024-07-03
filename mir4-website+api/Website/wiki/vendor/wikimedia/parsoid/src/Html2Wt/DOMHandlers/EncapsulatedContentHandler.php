<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Html2Wt\DOMHandlers;

use LogicException;
use Wikimedia\Assert\Assert;
use Wikimedia\Parsoid\Core\ClientError;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\DOM\Node;
use Wikimedia\Parsoid\Html2Wt\SerializerState;
use Wikimedia\Parsoid\Utils\DiffDOMUtils;
use Wikimedia\Parsoid\Utils\DOMCompat;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\Parsoid\Utils\DOMUtils;
use Wikimedia\Parsoid\Utils\PHPUtils;
use Wikimedia\Parsoid\Utils\WTUtils;

class EncapsulatedContentHandler extends DOMHandler {

	/** @var array[] Maps list item HTML elements to the expected parent element */
	private $parentMap = [
		'li' => [ 'ul', 'ol' ],
		'dt' => [ 'dl' ],
		'dd' => [ 'dl' ],
	];

	public function __construct() {
		parent::__construct( false );
	}

	/**
	 * @inheritDoc
	 * @throws ClientError
	 */
	public function handle(
		Element $node, SerializerState $state, bool $wrapperUnmodified = false
	): ?Node {
		$env = $state->getEnv();
		$serializer = $state->serializer;
		$dp = DOMDataUtils::getDataParsoid( $node );
		$dataMw = DOMDataUtils::getDataMw( $node );
		$src = null;
		$transclusionType = DOMUtils::matchTypeOf( $node, '/^mw:(Transclusion|Param)$/' );
		$extTagName = WTUtils::getExtTagName( $node );
		if ( $transclusionType ) {
			if ( is_array( $dataMw->parts ?? null ) ) {
				$src = $serializer->serializeFromParts( $state, $node, $dataMw->parts );
			} elseif ( isset( $dp->src ) ) {
				$env->log( 'error', 'data-mw.parts is not an array: ', DOMCompat::getOuterHTML( $node ),
					PHPUtils::jsonEncode( $dataMw ) );
				$src = $dp->src;
			} else {
				throw new ClientError(
					"Cannot serialize $transclusionType without data-mw.parts or data-parsoid.src"
				);
			}
		} elseif ( $extTagName ) {
			// Set name since downstream code assumes it
			if ( ( $dataMw->name ?? '' ) === '' ) {
				$dataMw->name = $extTagName;
			}
			$src = false;
			$ext = $env->getSiteConfig()->getExtTagImpl( $extTagName );
			if ( $ext ) {
				$src = $ext->domToWikitext( $state->extApi, $node, $wrapperUnmodified );
			}
			if ( $src === false ) {
				$src = $serializer->defaultExtensionHandler( $node, $state );
			}
		} elseif ( DOMUtils::hasTypeOf( $node, 'mw:LanguageVariant' ) ) {
			$state->serializer->languageVariantHandler( $node );
			return $node->nextSibling;
		} else {
			throw new LogicException( 'Should never reach here' );
		}
		$state->singleLineContext->disable();
		// FIXME: https://phabricator.wikimedia.org/T184779
		$src = ( $dataMw->extPrefix ?? '' ) . $src
			. ( $dataMw->extSuffix ?? '' );
		$serializer->emitWikitext( $this->handleListPrefix( $node, $state ) . $src, $node );
		$state->singleLineContext->pop();
		return WTUtils::skipOverEncapsulatedContent( $node );
	}

	// XXX: This is questionable, as the template can expand
	// to newlines too. Which default should we pick for new
	// content? We don't really want to make separator
	// newlines in HTML significant for the semantics of the
	// template content.

	/** @inheritDoc */
	public function before( Element $node, Node $otherNode, SerializerState $state ): array {
		// Handle native extension constraints.  Only apply to plain extension tags.
		$extTagName = WTUtils::getExtTagName( $node );
		if ( $extTagName && !DOMUtils::hasTypeOf( $node, 'mw:Transclusion' ) ) {
			$extConfig = $state->getEnv()->getSiteConfig()->getExtTagConfig( $extTagName );
			if (
				( $extConfig['options']['html2wt']['format'] ?? '' ) === 'block' &&
				WTUtils::isNewElt( $node )
			) {
				return [ 'min' => 1, 'max' => 2 ];
			}
		}

		// If this content came from a multi-part-template-block
		// use the first node in that block for determining
		// newline constraints.
		$dp = DOMDataUtils::getDataParsoid( $node );
		if ( isset( $dp->firstWikitextNode ) ) {
			// Note: this should match the case returned by DOMCompat::nodeName
			// so that this is effectively a case-insensitive comparison here.
			// (ie, data-parsoid could have either uppercase tag names or
			// lowercase tag names and this code should still work.)
			$ftn = mb_strtolower( $dp->firstWikitextNode, "UTF-8" );
			$h = ( new DOMHandlerFactory )->newFromTagHandler( $ftn );
			if ( !$h && ( $dp->stx ?? null ) === 'html' && $ftn !== 'a_html' ) {
				$h = new FallbackHTMLHandler();
			}
			if ( $h ) {
				return $h->before( $node, $otherNode, $state );
			}
		}

		// default behavior
		return [];
	}

	/** @inheritDoc */
	public function after( Element $node, Node $otherNode, SerializerState $state ): array {
		// Handle native extension constraints.  Only apply to plain extension tags.
		$extTagName = WTUtils::getExtTagName( $node );
		if ( $extTagName && !DOMUtils::hasTypeOf( $node, 'mw:Transclusion' ) ) {
			$extConfig = $state->getEnv()->getSiteConfig()->getExtTagConfig( $extTagName );
			if (
				( $extConfig['options']['html2wt']['format'] ?? '' ) === 'block' &&
				WTUtils::isNewElt( $node ) && !DOMUtils::atTheTop( $otherNode )
			) {
				return [ 'min' => 1, 'max' => 2 ];
			}
		}

		// default behavior
		return [];
	}

	private function handleListPrefix( Element $node, SerializerState $state ): string {
		$bullets = '';
		if ( DOMUtils::isListOrListItem( $node )
			&& !$this->parentBulletsHaveBeenEmitted( $node )
			&& !DiffDOMUtils::previousNonSepSibling( $node ) // Maybe consider parentNode.
			&& $this->isTplListWithoutSharedPrefix( $node )
			// Nothing to do for definition list rows,
			// since we're emitting for the parent node.
			 && !( DOMCompat::nodeName( $node ) === 'dd'
				   && ( DOMDataUtils::getDataParsoid( $node )->stx ?? null ) === 'row' )
		) {
			// phan fails to infer that the parent of a Element is always a Element
			$parentNode = $node->parentNode;
			'@phan-var Element $parentNode';
			$bullets = $this->getListBullets( $state, $parentNode );
		}
		return $bullets;
	}

	/**
	 * Normally we wait until hitting the deepest nested list element before
	 * emitting bullets. However, if one of those list elements is about-id
	 * marked, the tag handler will serialize content from data-mw parts or src.
	 * This is a problem when a list wasn't assigned the shared prefix of bullets.
	 * For example,
	 *
	 * ** a
	 * ** b
	 *
	 * Will assign bullets as,
	 *
	 * <ul><li-*>
	 * <ul>
	 * <li-*> a</li>   <!-- no shared prefix  -->
	 * <li-**> b</li>  <!-- gets both bullets -->
	 * </ul>
	 * </li></ul>
	 *
	 * For the b-li, getListsBullets will walk up and emit the two bullets it was
	 * assigned. If it was about-id marked, the parts would contain the two bullet
	 * start tag it was assigned. However, for the a-li, only one bullet is
	 * associated. When it's about-id marked, serializing the data-mw parts or
	 * src would miss the bullet assigned to the container li.
	 *
	 * @param Element $node
	 * @return bool
	 */
	private function isTplListWithoutSharedPrefix( Element $node ): bool {
		if ( !WTUtils::isEncapsulationWrapper( $node ) ) {
			return false;
		}

		if ( DOMUtils::hasTypeOf( $node, 'mw:Transclusion' ) ) {
			// If the first part is a string, template ranges were expanded to
			// include this list element. That may be trouble. Otherwise,
			// containers aren't part of the template source and we should emit
			// them.
			$dataMw = DOMDataUtils::getDataMw( $node );
			if ( !isset( $dataMw->parts ) || !is_string( $dataMw->parts[0] ) ) {
				return true;
			}
			// Less than two bullets indicates that a shared prefix was not
			// assigned to this element. A safe indication that we should call
			// getListsBullets on the containing list element.
			return !preg_match( '/^[*#:;]{2,}$/D', $dataMw->parts[0] );
		} elseif ( DOMUtils::matchTypeOf( $node, '/^mw:(Extension|Param)/' ) ) {
			// Containers won't ever be part of the src here, so emit them.
			return true;
		} else {
			return false;
		}
	}

	private function parentBulletsHaveBeenEmitted( Element $node ): bool {
		if ( WTUtils::isLiteralHTMLNode( $node ) ) {
			return true;
		} elseif ( DOMUtils::isList( $node ) ) {
			return !DOMUtils::isListItem( $node->parentNode );
		} else {
			Assert::invariant( DOMUtils::isListItem( $node ),
				'$node must be a list, list item or literal html node' );
			$parentNode = $node->parentNode;
			// Skip builder-inserted wrappers
			while ( $this->isBuilderInsertedElt( $parentNode ) ) {
				$parentNode = $parentNode->parentNode;
			}
			return !in_array(
				DOMCompat::nodeName( $parentNode ),
				$this->parentMap[DOMCompat::nodeName( $node )],
				true
			);
		}
	}
}
