<?php

namespace OOUI;

class BlankTheme extends Theme {

	/* Methods */

	/** @inheritDoc */
	public function getElementClasses( Element $element ) {
		// Parent method
		$classes = parent::getElementClasses( $element );

		// Add classes to $classes['on'] or $classes['off']

		return $classes;
	}
}
