<?php

namespace OOUI;

/**
 * IndexLayouts contain TabPanelLayout layouts as well as TabSelectWidget tabs that allow users
 * to navigate through the tab panels and select which one to display.
 *
 * Default php rendering shows all the tabs
 */
class IndexLayout extends MenuLayout {

	/**
	 * @var StackLayout
	 */
	protected $stackLayout;
	/**
	 * @var PanelLayout
	 */
	protected $tabPanel;
	/**
	 * @var TabPanelLayout[]
	 */
	protected $tabPanels;
	/**
	 * @var TabSelectWidget
	 */
	protected $tabSelectWidget;
	/**
	 * @var bool
	 */
	protected $autoFocus;
	/**
	 * @var bool
	 */
	protected $continuous;
	/**
	 * @var string
	 */
	protected $currentTabPanelName;

	/**
	 * @param array $config Configuration options
	 *      - bool $config['continuous'] Focus on the first focusable element when a new tab panel is
	 *        displayed. Disabled on mobile. (default: false)
	 *      - bool $config['autoFocus'] (default: true)
	 *      - bool $config['framed'] (default: true)
	 */
	public function __construct( array $config = [] ) {
		$config = array_merge(
			$config,
			[ 'menuPosition' => 'top' ]
		);

		parent::__construct( $config );

		$this->tabPanels = [];
		$this->continuous = $config['continuous'] ?? false;

		$this->stackLayout = $this->contentPanel ?? new StackLayout( [
			'continuous' => $this->continuous,
			'expanded' => $this->expanded
		] );
		$this->setContentPanel( $this->stackLayout );
		$this->autoFocus = $config['autoFocus'] ?? true;

		$this->tabSelectWidget = new TabSelectWidget( [
			'framed' => $config['framed'] ?? true
		] );
		$this->tabPanel = $this->menuPanel ?? new PanelLayout( [
			'expanded' => $this->expanded,
			'preserveContent' => false
		] );
		$this->setMenuPanel( $this->tabPanel );

		$this->toggleMenu( true );

		$this->addClasses( [ 'oo-ui-indexLayout' ] );
		$this->stackLayout->addClasses( [ 'oo-ui-indexLayout-stackLayout' ] );
		$this->tabPanel
			->addClasses( [ 'oo-ui-indexLayout-tabPanel' ] )
			->appendContent( $this->tabSelectWidget );
	}

	/** @inheritDoc */
	public function getConfig( &$config ) {
		$config = parent::getConfig( $config );
		if ( !$this->autoFocus ) {
			$config['autoFocus'] = $this->autoFocus;
		}
		if ( $this->continuous ) {
			$config['continuous'] = $this->continuous;
		}
		if ( count( $this->tabPanels ) ) {
			$config['tabPanels'] = $this->tabPanels;
		}
		// menuPosition is not configurable
		unset( $config['menuPosition'] );
		$config['tabSelectWidget'] = $this->tabSelectWidget;
		// stackLayout and tabPanel are identical to
		// contentPanel and menuPanel in MenuLayout
		return $config;
	}

	/**
	 * Get the tabs widget.
	 *
	 * @return TabSelectWidget Tabs widget
	 */
	public function getTabs() {
		return $this->tabSelectWidget;
	}

	/**
	 * Get a tab panel by its symbolic name.
	 *
	 * @param string $name Symbolic name of table panel
	 * @return TabPanelLayout|null Tab panel, if found
	 */
	public function getTabPanel( $name ): ?TabPanelLayout {
		return $this->tabPanels[$name] ?? null;
	}

	/**
	 * @return TabPanelLayout|null
	 */
	public function getCurrentTabPanel() {
		$name = $this->getCurrentTabPanelName();
		return $name ? $this->getTabPanel( $name ) : null;
	}

	/**
	 * @return string
	 */
	public function getCurrentTabPanelName() {
		return $this->currentTabPanelName;
	}

	/**
	 * Add tab panels to the index layout
	 *
	 * When tab panels are added with the same names as existing tab panels, the existing tab panels
	 * will be automatically removed before the new tab panels are added.
	 *
	 * @param TabPanelLayout[] $tabPanels Tab panels to add
	 */
	public function addTabPanels( array $tabPanels ) {
		$tabItems = [];
		foreach ( $tabPanels as $tabPanel ) {
			$this->tabPanels[ $tabPanel->getName() ] = $tabPanel;
			$tabItem = new TabOptionWidget( array_merge( [
				'data' => $tabPanel->getName(),
				'label' => $tabPanel->getLabel(),
			], $tabPanel->getTabItemConfig() ) );
			// TODO: Set aria-labelledby/aria-controls as in .js
			$tabItems[] = $tabItem;
		}
		$this->tabSelectWidget->addItems( $tabItems );
		$this->stackLayout->addItems( $tabPanels );

		// Select the first item
		$this->getTabs()->selectItem( $tabItems[ 0 ] );
	}

	/**
	 * Set the current tab panel by symbolic name.
	 *
	 * @param string $name Symbolic name of tab panel
	 */
	public function setTabPanel( $name ) {
		if ( $name !== $this->currentTabPanelName ) {
			$tabPanel = $this->getTabPanel( $name );
			$previousTabPanel = $this->getCurrentTabPanel();
			$selectedItem = $this->getTabs()->findSelectedItem();
			if ( !$selectedItem || $selectedItem->getData() !== $name ) {
				$this->getTabs()->selectItemByData( $name );
			}
			if ( $tabPanel ) {
				if ( $previousTabPanel ) {
					$previousTabPanel->setActive( false );
				}
				$this->currentTabPanelName = $name;
				$tabPanel->setActive( true );
				$this->stackLayout->setItem( $tabPanel );
			}
		}
	}
}
