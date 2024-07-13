<?php

namespace OOUI;

/**
 * Element containing a sequence of child elements.
 *
 * @abstract
 */
trait GroupElement {
	/**
	 * List of items in the group as Elements.
	 *
	 * @var Element[]
	 */
	protected $items = [];

	/**
	 * @var Tag
	 */
	protected $group;

	/**
	 * @param array $config Configuration options
	 */
	public function initializeGroupElement( array $config = [] ) {
		// Properties
		$this->group = $config['group'] ?? new Tag( 'div' );

		$this->registerConfigCallback( function ( &$config ) {
			$config['items'] = $this->items;
		} );
	}

	/**
	 * Check if there are no items.
	 *
	 * @return bool Group is empty
	 */
	public function isEmpty() {
		return $this->items === [];
	}

	/**
	 * Get items.
	 *
	 * @return Element[] Items
	 */
	public function getItems() {
		return $this->items;
	}

	/**
	 * @param mixed $data
	 * @return Element|null
	 */
	public function findItemFromData( $data ) {
		$items = $this->getItems();
		// TODO: Support non-string $data using a hash (e.g. json_encode)
		foreach ( $items as $item ) {
			if ( $item->getData() === $data ) {
				return $item;
			}
		}
		return null;
	}

	/**
	 * Add items.
	 *
	 * Adding an existing item will move it.
	 *
	 * @param Element[] $items Items
	 * @param int|null $index Index to insert items at
	 * @return $this
	 */
	public function addItems( array $items, $index = null ) {
		foreach ( $items as $item ) {
			// Check if item exists then remove it first, effectively "moving" it
			$currentIndex = array_search( $item, $this->items, true );
			if ( $currentIndex !== false ) {
				$this->removeItems( [ $item ] );
				// Adjust index to compensate for removal
				if ( $currentIndex < $index ) {
					$index--;
				}
			}
			// Add the item
			$item->setElementGroup( $this );
		}

		if ( $index === null || $index < 0 || $index >= count( $this->items ) ) {
			$this->items = array_merge( $this->items, $items );
		} else {
			array_splice( $this->items, $index, 0, $items );
		}

		// Update actual target element contents to reflect our list
		$this->group->clearContent();
		$this->group->appendContent( $this->items );

		return $this;
	}

	/**
	 * Remove items.
	 *
	 * @param Element[] $items Items to remove
	 * @return $this
	 */
	public function removeItems( $items ) {
		foreach ( $items as $item ) {
			$index = array_search( $item, $this->items, true );
			if ( $index !== false ) {
				$item->setElementGroup( null );
				array_splice( $this->items, $index, 1 );
			}
		}

		// Update actual target element contents to reflect our list
		$this->group->clearContent();
		$this->group->appendContent( $this->items );

		return $this;
	}

	/**
	 * Clear all items.
	 *
	 * Items will be detached, not removed, so they can be used later.
	 *
	 * @return $this
	 */
	public function clearItems() {
		foreach ( $this->items as $item ) {
			$item->setElementGroup( null );
		}

		$this->items = [];
		$this->group->clearContent();

		return $this;
	}

	/**
	 * @param callable $func
	 */
	abstract public function registerConfigCallback( callable $func );
}
