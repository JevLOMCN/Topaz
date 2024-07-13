<?php
namespace MediaWiki\Skins\Vector\Components;

use Language;
use MediaWiki\MediaWikiServices;
use MediaWiki\StubObject\StubUserLang;

/**
 * VectorComponentVariants component
 */
class VectorComponentVariants implements VectorComponent {
	/** @var array */
	private $menuData;
	/** @var Language|StubUserLang */
	private $pageLang;
	/** @var string */
	private $ariaLabel;

	/**
	 * @param array $menuData
	 * @param Language|StubUserLang $pageLang
	 * @param string $ariaLabel
	 */
	public function __construct( array $menuData, $pageLang, string $ariaLabel ) {
		$this->menuData = $menuData;
		$this->pageLang = $pageLang;
		$this->ariaLabel = $ariaLabel;
	}

	/**
	 * Use the selected variant for the dropdown label
	 * @return string
	 */
	private function getDropdownLabel(): string {
		$languageConverterFactory = MediaWikiServices::getInstance()->getLanguageConverterFactory();
		$converter = $languageConverterFactory->getLanguageConverter( $this->pageLang );
		return $this->pageLang->getVariantname(
			$converter->getPreferredVariant()
		);
	}

	/**
	 * Get the variants dropdown data
	 * @return array
	 */
	private function getDropdownData() {
		$dropdown = new VectorComponentDropdown(
			$this->menuData[ 'id' ],
			$this->getDropdownLabel(),
			// Hide dropdown if menu is empty
			$this->menuData[ 'is-empty' ] ? 'emptyPortlet' : ''
		);
		$dropdownData = $dropdown->getTemplateData();
		$dropdownData['aria-label'] = $this->ariaLabel;
		return $dropdownData;
	}

	/**
	 * Get the variants menu data
	 * @return array
	 */
	private function getMenuDropdownData() {
		// Remove label from variants menu
		$this->menuData['label'] = null;
		$menu = new VectorComponentMenu( $this->menuData );
		return $menu->getTemplateData();
	}

	/**
	 * @inheritDoc
	 */
	public function getTemplateData(): array {
		return [
			'data-variants-dropdown' => $this->getDropdownData(),
			'data-variants-menu' => $this->getMenuDropdownData()
		];
	}
}
