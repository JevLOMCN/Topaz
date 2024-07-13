<?php
declare( strict_types = 1 );

namespace Wikimedia\Parsoid\Core;

use Wikimedia\Parsoid\DOM\Document;
use Wikimedia\Parsoid\Ext\ParsoidExtensionAPI;

abstract class ContentModelHandler {

	/**
	 * @param ParsoidExtensionAPI $extApi
	 * @return Document
	 */
	abstract public function toDOM( ParsoidExtensionAPI $extApi ): Document;

	/**
	 * @param ParsoidExtensionAPI $extApi
	 * @param ?SelserData $selserData
	 * @return string
	 */
	abstract public function fromDOM(
		ParsoidExtensionAPI $extApi, ?SelserData $selserData = null
	): string;

}
