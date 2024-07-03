<?php

namespace OOUI;

/**
 * StackLayouts contain a series of PanelLayouts
 */
class StackLayout extends PanelLayout {

	use GroupElement;

	/** @var bool */
	protected $continuous;
	/** @var PanelLayout|null */
	protected $currentItem;

	/**
	 * @param array $config Configuration options
	 *      - bool $config['continuous'] Show all panels, one after another (default: false)
	 *      - PanelLayout[] $config['items'] Panel layouts to add to the stack layout.
	 */
	public function __construct( array $config = [] ) {
		$config = array_merge( [
			'preserveContent' => false,
			'scrollable' => $config['continuous'] ?? false
		], $config );

		// Parent constructor
		parent::__construct( $config );

		// Properties
		$this->continuous = $config['continuous'] ?? false;

		// Traits
		$this->initializeGroupElement( array_merge( $config, [ 'group' => $this ] ) );

		// Initialization
		$this->addClasses( [ 'oo-ui-stackLayout' ] );
		if ( $this->continuous ) {
			$this->addClasses( [ 'oo-ui-stackLayout-continuous' ] );
		}
		$this->addItems( $config['items'] ?? [] );
	}

	/**
	 * @param PanelLayout|null $item
	 */
	public function setItem( $item ) {
		if ( $item !== $this->currentItem ) {
			$items = $this->getItems();
			$this->updateHiddenState( $items, $item );
			$this->currentItem = $item;
		}
	}

	/**
	 * @param Element[] $items
	 * @param PanelLayout|null $selectedItem
	 */
	private function updateHiddenState( $items, $selectedItem ) {
		if ( !$this->continuous ) {
			foreach ( $items as $item ) {
				if ( !$selectedItem || $selectedItem !== $item ) {
					$item->toggle( false );
					$item->setAttributes( [ 'aria-hidden' => 'true' ] );
				}
			}
			if ( $selectedItem ) {
				$selectedItem->toggle( true );
				$selectedItem->removeAttributes( [ 'aria-hidden' ] );
			}
		}
	}

	/** @inheritDoc */
	public function getConfig( &$config ) {
		$config = parent::getConfig( $config );
		if ( $this->continuous ) {
			$config['continuous'] = $this->continuous;
			// scrollable default has changed to true
			if ( !$this->hasClass( 'oo-ui-panelLayout-scrollable' ) ) {
				$config['scrollable'] = false;
			} else {
				unset( $config['scrollable'] );
			}
		}
		return $config;
	}

}
