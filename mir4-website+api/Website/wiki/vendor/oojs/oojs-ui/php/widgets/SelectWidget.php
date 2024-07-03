<?php

namespace OOUI;

/**
 * A SelectWidget is of a generic selection of options.
 *
 * Should be used in conjunction with OptionWidget
 *
 * @method OptionWidget[] getItems()
 */
class SelectWidget extends Widget {

	use GroupWidget;

	/**
	 * @var bool
	 */
	protected $multiselect;

	/**
	 * @param array $config Configuration options
	 *      - OptionWidget[] $config['items'] OptionWidget objects to add to the select
	 *      - bool $config['multiselect'] Allow for multiple selections
	 */
	public function __construct( array $config = [] ) {
		$config = array_merge(
			[ 'items' => [] ],
			$config
		);

		$this->initializeGroupElement( array_merge( $config, [ 'group' => $this ] ) );

		$this->multiselect = $config['multiselect'] ?? false;

		$this->addClasses( [
			'oo-ui-selectWidget',
			'oo-ui-selectWidget-unpressed',
		] );
		$this->setAttributes( [
			'role' => 'listbox',
			'aria-multiselectable' => $this->multiselect ? 'true' : 'false',
		] );

		$this->addItems( $config['items'] );
		parent::__construct( $config );
	}

	/**
	 * @return OptionWidget[]|OptionWidget|null
	 */
	public function findSelectedItems() {
		/** @var OptionWidget[] $selected */
		$selected = array_filter( $this->getItems(), static function ( $item ) {
			return $item->isSelected();
		} );

		return $this->multiselect ?
			$selected :
			( count( $selected ) ?
				$selected[ 0 ] :
				null );
	}

	/**
	 * @return OptionWidget[]|OptionWidget|null
	 */
	public function findSelectedItem() {
		return $this->findSelectedItems();
	}

	/**
	 * Programmatically select an option by its data. If the `data` parameter is omitted,
	 * or if the item does not exist, all options will be deselected.
	 *
	 * @param mixed $data Value of the item to select, omit to deselect all
	 * @return $this
	 */
	public function selectItemByData( $data = null ) {
		$itemFromData = $this->findItemFromData( $data );
		'@phan-var OptionWidget|null $itemFromData';
		if ( $data === null || $itemFromData === null ) {
			return $this->selectItem();
		}
		return $this->selectItem( $itemFromData );
	}

	/**
	 * Unselect an option by its reference.
	 *
	 * @param OptionWidget $item Item to unselect, omit to deselect all
	 * @return $this
	 */
	public function unselectItem( $item ) {
		if ( $item ) {
			$item->setSelected( false );
		} else {
			foreach ( $this->getItems() as $i ) {
				if ( $i->isSelected() ) {
					$i->setSelected( false );
				}
			}
		}

		return $this;
	}

	/**
	 * Select an option by its reference.
	 *
	 * @param OptionWidget|null $item Item to select, omit to deselect all
	 * @return $this
	 */
	public function selectItem( $item = null ) {
		if ( $this->multiselect && $item ) {
			$item->setSelected( true );
		} else {
			foreach ( $this->getItems() as $i ) {
				$selected = $item === $i;
				if ( $i->isSelected() !== $selected ) {
					$i->setSelected( $selected );
				}
			}
		}

		return $this;
	}
}
