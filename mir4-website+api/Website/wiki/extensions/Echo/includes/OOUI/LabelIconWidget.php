<?php

namespace MediaWiki\Extension\Notifications\OOUI;

use OOUI\IconElement;
use OOUI\LabelElement;
use OOUI\Tag;
use OOUI\TitledElement;
use OOUI\Widget;

/**
 * Widget combining a label and icon
 */
class LabelIconWidget extends Widget {
	use IconElement;
	use LabelElement;
	use TitledElement;

	/**
	 * @param array $config Configuration options
	 *  - string|HtmlSnippet $config['label'] Label text
	 *  - string $config['title'] Title text
	 *  - string $config['icon'] Icon key
	 */
	public function __construct( $config ) {
		parent::__construct( $config );

		$tableRow = new Tag( 'div' );
		$tableRow->setAttributes( [
			'class' => 'oo-ui-labelIconWidget-row',
		] );

		$icon = new Tag( 'div' );
		$label = new Tag( 'div' );

		$this->initializeIconElement( array_merge( $config, [ 'iconElement' => $icon ] ) );
		$this->initializeLabelElement( array_merge( $config, [ 'labelElement' => $label ] ) );
		$this->initializeTitledElement( $config );

		$this->addClasses( [ 'oo-ui-labelIconWidget' ] );
		$tableRow->appendContent( $icon, $label );
		$this->appendContent( $tableRow );
	}
}
