<?php
declare( strict_types = 1 );

namespace Cite\Parsoid;

use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\Ext\DOMUtils;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;
use Wikimedia\Parsoid\Utils\DOMCompat;

/**
 * Helper class used by `<references>` implementation.
 * @license GPL-2.0-or-later
 */
class RefGroup {

	public string $name;
	/** @var RefGroupItem[] */
	public array $refs = [];
	/** @var array<string,RefGroupItem> Lookup map only for named refs */
	public array $indexByName = [];

	public function __construct( string $group = '' ) {
		$this->name = $group;
	}

	/**
	 * Generate leading linkbacks
	 */
	private static function createLinkback(
		ParsoidExtensionAPI $extApi, string $target, ?string $group,
		string $text, Document $ownerDoc
	): Element {
		$a = $ownerDoc->createElement( 'a' );
		$span = $ownerDoc->createElement( 'span' );
		$a->setAttribute( 'href', $extApi->getPageUri() . '#' . $target );
		$span->setAttribute( 'class', 'mw-linkback-text' );
		if ( $group ) {
			$a->setAttribute( 'data-mw-group', $group );
		}
		$span->appendChild( $ownerDoc->createTextNode( $text . ' ' ) );
		$a->appendChild( $span );
		return $a;
	}

	public function renderLine(
		ParsoidExtensionAPI $extApi, Element $refsList, RefGroupItem $ref
	): void {
		$ownerDoc = $refsList->ownerDocument;

		// Generate the li and set ref content first, so the HTML gets parsed.
		// We then append the rest of the ref nodes before the first node
		$li = $ownerDoc->createElement( 'li' );
		$refDir = $ref->dir;
		$refTarget = $ref->target;
		$refContentId = $ref->contentId;
		$refGroup = $ref->group;
		DOMUtils::addAttributes( $li, [
				'about' => '#' . $refTarget,
				'id' => $refTarget,
				'class' => ( $refDir === 'rtl' || $refDir === 'ltr' ) ? 'mw-cite-dir-' . $refDir : null
			]
		);
		$reftextSpan = $ownerDoc->createElement( 'span' );
		DOMUtils::addAttributes(
			$reftextSpan,
			[
				'id' => 'mw-reference-text-' . $refTarget,
				'class' => 'mw-reference-text',
			]
		);
		if ( $refContentId ) {
			// `sup` is the wrapper created by Ref::sourceToDom()'s call to
			// `extApi->extTagToDOM()`.  Only its contents are relevant.
			$sup = $extApi->getContentDOM( $refContentId )->firstChild;
			DOMUtils::migrateChildren( $sup, $reftextSpan );
			'@phan-var Element $sup';  /** @var Element $sup */
			DOMCompat::remove( $sup );
			$extApi->clearContentDOM( $refContentId );
		}
		$li->appendChild( $reftextSpan );

		if ( count( $ref->linkbacks ) === 1 ) {
			$linkback = self::createLinkback( $extApi, $ref->id, $refGroup, "↑", $ownerDoc );
			DOMUtils::addRel( $linkback, 'mw:referencedBy' );
			$li->insertBefore( $linkback, $reftextSpan );
		} else {
			// 'mw:referencedBy' span wrapper
			$span = $ownerDoc->createElement( 'span' );
			DOMUtils::addRel( $span, 'mw:referencedBy' );
			$li->insertBefore( $span, $reftextSpan );

			foreach ( $ref->linkbacks as $i => $lb ) {
				$span->appendChild(
					self::createLinkback( $extApi, $lb, $refGroup, (string)( $i + 1 ), $ownerDoc )
				);
			}
		}

		// Space before content node
		$li->insertBefore( $ownerDoc->createTextNode( ' ' ), $reftextSpan );

		// Add it to the ref list
		$refsList->appendChild( $li );
	}
}
