<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Ext;

use stdClass;
use Wikimedia\Parsoid\DOM\Element;
use Wikimedia\Parsoid\NodeData\DataMw;
use Wikimedia\Parsoid\NodeData\DataParsoid;
use Wikimedia\Parsoid\Utils\DOMDataUtils as DDU;

/**
 * This class provides DOM data helpers needed by extensions.
 * These helpers support fetching / updating attributes of DOM nodes.
 */
class DOMDataUtils {
	/**
	 * Get data parsoid info from DOM element
	 * @param Element $elt
	 * @return DataParsoid ( this is mostly used for type hinting )
	 */
	public static function getDataParsoid( Element $elt ): DataParsoid {
		return DDU::getDataParsoid( $elt );
	}

	/**
	 * Set data parsoid info on a DOM element
	 * @param Element $elt
	 * @param ?DataParsoid $dp data-parsoid
	 */
	public static function setDataParsoid( Element $elt, ?DataParsoid $dp ): void {
		DDU::setDataParsoid( $elt, $dp );
	}

	/**
	 * Get data meta wiki info from a DOM element
	 * @param Element $elt
	 * @return ?DataMw
	 */
	public static function getDataMw( Element $elt ): ?DataMw {
		return DDU::getDataMw( $elt );
	}

	/**
	 * Check if there is meta wiki info on a DOM element
	 * @param Element $elt
	 * @return bool
	 */
	public static function dataMwExists( Element $elt ): bool {
		return DDU::validDataMw( $elt );
	}

	/**
	 * Set data meta wiki info from a DOM element
	 * @param Element $elt
	 * @param ?DataMw $dmw data-mw
	 */
	public static function setDataMw( Element $elt, ?DataMw $dmw ): void {
		DDU::setDataMw( $elt, $dmw );
	}

	/**
	 * Get data diff info from a DOM element.
	 * @param Element $elt
	 * @return ?stdClass
	 */
	public static function getDataParsoidDiff( Element $elt ): ?stdClass {
		return DDU::getDataParsoidDiff( $elt );
	}

	/**
	 * Set data diff info on a DOM element.
	 * @param Element $elt
	 * @param ?stdClass $diffObj data-parsoid-diff object
	 */
	public static function setDataParsoidDiff( Element $elt, ?stdClass $diffObj ): void {
		DDU::setDataParsoidDiff( $elt, $diffObj );
	}

	/**
	 * Does this node have any attributes? This method is the preferred way of
	 * interrogating this property since Parsoid DOMs might have Parsoid-internal
	 * attributes added.
	 * @param Element $elt
	 * @return bool
	 */
	public static function noAttrs( Element $elt ): bool {
		return DDU::noAttrs( $elt );
	}

	/**
	 * Clones a node and its data bag
	 * @param Element $elt
	 * @param bool $deep
	 * @return Element
	 */
	public static function cloneNode( Element $elt, bool $deep ): Element {
		return DDU::cloneNode( $elt, $deep );
	}
}
