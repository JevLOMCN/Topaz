<?php

namespace MediaWiki\Extension\Math\Widget;

use OOUI\SearchInputWidget;

class WikibaseEntitySelector extends SearchInputWidget {

	public function __construct( array $config = [] ) {
		parent::__construct( $config );
		$this->addClasses( [ 'mw-math-widget-wb-entity-selector' ] );
	}

	protected function getJavaScriptClassName() {
		return 'mw.widgets.MathWbEntitySelector';
	}
}
