<?php

namespace OOUI;

class WikimediaUITheme extends Theme {

	/* Methods */

	/** @inheritDoc */
	public function getElementClasses( Element $element ) {
		$variants = [
			'invert' => false,
			'progressive' => false,
			'destructive' => false,
			'error' => false,
			'warning' => false,
			'success' => false
		];

		// Parent method
		$classes = parent::getElementClasses( $element );

		if (
			$element instanceof IconWidget &&
			$element->hasClass( 'oo-ui-checkboxInputWidget-checkIcon' )
		) {
			// Icon on CheckboxInputWidget
			$variants['invert'] = true;
		} elseif ( $element->supports( [ 'hasFlag' ] ) ) {
			// @phan-suppress-next-line PhanUndeclaredMethod
			$isFramed = $element->supports( [ 'isFramed' ] ) && $element->isFramed();
			// @phan-suppress-next-line PhanUndeclaredMethod
			$isActive = $element->supports( [ 'isActive' ] ) && $element->isActive();
			if ( $isFramed && ( $isActive || $element->isDisabled() || $element->hasFlag( 'primary' ) ) ) {
				// Button with a dark background, use white icon
				$variants['invert'] = true;
			} elseif ( !$isFramed && $element->isDisabled() && !$element->hasFlag( 'invert' ) ) {
				// Frameless disabled button, always use black icon regardless of flags
				$variants['invert'] = false;
			} elseif ( !$element->isDisabled() ) {
				// Any other kind of button, use the right colored icon if available
				$variants['progressive'] = $element->hasFlag( 'progressive' );
				$variants['destructive'] = $element->hasFlag( 'destructive' );
				$variants['invert'] = $element->hasFlag( 'invert' );
				$variants['error'] = $element->hasFlag( 'error' );
				$variants['warning'] = $element->hasFlag( 'warning' );
				$variants['success'] = $element->hasFlag( 'success' );
			}
		}

		foreach ( $variants as $variant => $toggle ) {
			$classes[$toggle ? 'on' : 'off'][] = 'oo-ui-image-' . $variant;
		}

		return $classes;
	}
}
