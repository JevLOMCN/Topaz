<?php

namespace MediaWiki\Extension\Gadgets;

use InvalidArgumentException;

/**
 * @internal For use in tests.
 */
class StaticGadgetRepo extends GadgetRepo {

	/**
	 * @var Gadget[]
	 */
	private array $gadgets;

	public function __construct( array $gadgets ) {
		$this->gadgets = $gadgets;
	}

	public function getGadgetIds(): array {
		return array_keys( $this->gadgets );
	}

	public function getGadget( string $id ): Gadget {
		if ( !array_key_exists( $id, $this->gadgets ) ) {
			throw new InvalidArgumentException();
		}
		return $this->gadgets[$id];
	}
}
