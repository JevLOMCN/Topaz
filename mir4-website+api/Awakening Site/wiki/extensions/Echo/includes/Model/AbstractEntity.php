<?php

namespace MediaWiki\Extension\Notifications\Model;

/**
 * Abstract entity for Echo model
 */
abstract class AbstractEntity {

	/**
	 * Convert an entity's property to array
	 * @return array
	 */
	abstract public function toDbArray();

}
