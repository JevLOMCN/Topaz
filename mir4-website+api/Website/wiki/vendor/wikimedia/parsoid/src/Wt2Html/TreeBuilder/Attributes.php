<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Wt2Html\TreeBuilder;

use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\Utils\DOMDataUtils;
use Wikimedia\RemexHtml\Tokenizer\PlainAttributes;

class Attributes extends PlainAttributes {
	private $document;

	/**
	 * @param Document $document
	 * @param array $data
	 */
	public function __construct( Document $document, $data = [] ) {
		$this->document = $document;
		parent::__construct( $data );
	}

	public function clone() {
		if ( isset( $this->data[DOMDataUtils::DATA_OBJECT_ATTR_NAME] ) ) {
			$newAttrs = $this->data;
			$data = DOMDataUtils::getBag( $this->document )->getObject(
				(int)$this->data[DOMDataUtils::DATA_OBJECT_ATTR_NAME] );
			$newData = $data->cloneNodeData( ( $newAttrs['typeof'] ?? null ) === 'mw:ExpandedAttrs' );

			// - If autoInserted(Start|End)Token flags are set, set the corresponding
			//   autoInserted(Start|End) flag. Clear the token flags on the
			//   already-processed nodes but let them propagate further down
			//   so that autoInserted(Start|End) flags can be set on all clones.
			// - If not, clear autoInserted* flags since TreeEventStage needs
			//   to set them again based on the HTML token stream.
			if ( isset( $data->parsoid->autoInsertedStartToken ) ) {
				unset( $data->parsoid->autoInsertedStartToken );
				$newData->parsoid->autoInsertedStart = true;
			} else {
				unset( $newData->parsoid->autoInsertedStart );
			}
			if ( isset( $data->parsoid->autoInsertedEndToken ) ) {
				unset( $data->parsoid->autoInsertedEndToken );
				$newData->parsoid->autoInsertedEnd = true;
			} else {
				unset( $newData->parsoid->autoInsertedEnd );
			}

			$newAttrs[DOMDataUtils::DATA_OBJECT_ATTR_NAME] =
				DOMDataUtils::stashObjectInDoc( $this->document, $newData );
			return new self( $this->document, $newAttrs );
		} else {
			return $this;
		}
	}
}
