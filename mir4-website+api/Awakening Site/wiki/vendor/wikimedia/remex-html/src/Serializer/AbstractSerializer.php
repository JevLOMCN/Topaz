<?php
namespace Wikimedia\RemexHtml\Serializer;

use Wikimedia\RemexHtml\TreeBuilder\TreeHandler;

interface AbstractSerializer extends TreeHandler {
	/**
	 * Get the serialized result of tree construction
	 *
	 * @return string
	 */
	public function getResult();
}
