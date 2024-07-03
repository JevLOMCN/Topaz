<?php

namespace OOUI;

/**
 * Layout made of a fieldset and optional legend.
 *
 * Just add FieldLayout items.
 */
class FieldsetLayout extends Layout {
	use IconElement;
	use LabelElement;
	use GroupElement;

	/* Static Properties */

	/** @var string */
	public static $tagName = 'fieldset';

	/** @var Tag */
	protected $header;
	/** @var string */
	protected $helpText;
	/** @var string|false */
	protected $helpInline;

	/**
	 * @param array $config Configuration options
	 *      - FieldLayout[] $config['items'] Items to add
	 *      - string|HtmlSnippet $config['help'] Explanatory text shown as a '?' icon, or inline.
	 *      - bool $config['helpInline'] Whether or not the help should be inline,
	 *          or shown when the "help" icon is clicked. (default: false)
	 */
	public function __construct( array $config = [] ) {
		// Parent constructor
		parent::__construct( $config );

		// Traits
		$this->initializeIconElement( $config );
		$this->initializeLabelElement( $config );
		$this->initializeGroupElement( $config );

		// Properties
		$this->header = new Tag( 'legend' );
		$this->helpText = $config['help'] ?? '';
		$this->helpInline = $config['helpInline'] ?? false;

		// Initialization
		$this->header
			->addClasses( [ 'oo-ui-fieldsetLayout-header' ] )
			->appendContent( $this->icon, $this->label );
		$this->group->addClasses( [ 'oo-ui-fieldsetLayout-group' ] );
		$this
			->addClasses( [ 'oo-ui-fieldsetLayout' ] )
			->prependContent( $this->header, $this->group );

		if ( $this->helpText ) {
			if ( $this->helpInline ) {
				$helpWidget = new LabelWidget( [
					'classes' => [ 'oo-ui-inline-help' ],
					'label' => $this->helpText,
				] );
				$this->prependContent( $this->header, $helpWidget, $this->group );
			} else {
				$helpWidget = new ButtonWidget( [
					'classes' => [ 'oo-ui-fieldsetLayout-help' ],
					'framed' => false,
					'icon' => 'info',
					'title' => $this->helpText,
					// TODO We have no way to use localisation messages in PHP
					// (and to use different languages when used from MediaWiki)
					// 'label' => msg( 'ooui-field-help' ),
					// 'invisibleLabel' => true,
				] );
				$this->header->appendContent( $helpWidget );
			}
		}

		if ( isset( $config['items'] ) ) {
			$this->addItems( $config['items'] );
		}
	}

	/** @inheritDoc */
	public function getConfig( &$config ) {
		$config['$overlay'] = true;
		if ( $this->helpText !== '' ) {
			$config['help'] = $this->helpText;
		}
		if ( $this->helpInline ) {
			$config['helpInline'] = $this->helpInline;
		}
		return parent::getConfig( $config );
	}
}
