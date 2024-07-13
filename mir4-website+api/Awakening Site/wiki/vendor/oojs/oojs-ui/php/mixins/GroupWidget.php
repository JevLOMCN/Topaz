<?php

namespace OOUI;

/**
 * Use together with ItemWidget to make disabled state inheritable.
 *
 * @abstract
 *
 * @method Widget[] getItems()
 */
trait GroupWidget {
	use GroupElement;

	/**
	 * @param bool $disabled
	 */
	public function setDisabled( $disabled ) {
		// @phan-suppress-next-line PhanTraitParentReference
		parent::setDisabled( $disabled );
		$modifiedItems = [];
		$items = $this->getItems();
		/** @var Widget $item */
		foreach ( $items as $item ) {
			$modifiedItems[] = $item->setDisabled( $disabled );
		}
		$this->clearItems();
		$this->addItems( $modifiedItems );
	}
}
